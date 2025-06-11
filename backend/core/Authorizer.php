<?php

require_once './backend/core/Token.php';
require_once './backend/core/Result.php';
require_once './backend/util/Util.php';
require_once './backend/db/Database.php';

if (!function_exists('getallheaders')) {
  function getallheaders()
  {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
      if (str_starts_with($name, 'HTTP_')) {
        $key = str_replace('_', '-', strtolower(substr($name, 5)));
        $headers[ucwords($key, '-')] = $value;
      }
    }
    return $headers;
  }
}

class Authorizer
{

  public static $tokens = [];
  public static $token_duration = 900;


  public static function validate_token_header(): Token
  {
    $token = $_COOKIE['auth_token'] ?? null;

    if (empty($token)) {
      $headers = getallheaders();
      $auth_header = $headers['authorization'] ?? null;
      if (!empty($auth_header)) {
        $token = str_replace('Bearer ', '', $auth_header);
      }
    }

    if (empty($token)) {
      return new Token([], TokenStatus::Missing, 'No auth token provided');
    }

    $token_result = Tokener::get_user_id_from_token($token);

    if ($token_result->isErr()) {
      return new Token([], TokenStatus::Invalid, $token_result->unwrapErr());
    }

    return new Token($token_result->unwrap(), TokenStatus::Valid, $token_result->unwrap());
  }


  public static function hash_password($salt, string $password)
  {
    $hash = hash('sha256', $salt . $password);
    return $hash;
  }

  public static function get_salt()
  {
    return bin2hex(random_bytes(16));
  }

  public static function is_valid(string $password): bool
  {
    return preg_match(
      '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
      $password
    ) === 1;
  }



  public static function store_validation($db, int $user_id, string $password)
  {
    $salt = self::get_salt();
    $hash = self::hash_password($salt, $password);

    $sql = "INSERT INTO user_auth (user_id, password_hash, salt) VALUES (:user_id, :password_hash, :salt)";
    $stmt = $db->prepare($sql);

    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':password_hash', $hash, PDO::PARAM_STR);
    $stmt->bindParam(':salt', $salt, PDO::PARAM_STR);
    $stmt->execute();
  }

  public static function update_validation($db, int $user_id, string $password, $old_password): Result
  {
    $is_valid = self::validate_internal($db, $user_id, $old_password);

    if ($is_valid->isErr()) {
      return $is_valid;
    }
    return self::update_validation_force($db, $user_id, $password);
  }

  public static function update_validation_force($db, int $user_id, string $password)
  {
    $salt = self::get_salt();
    $hash = self::hash_password($salt, $password);

    $sql = "UPDATE user_auth SET password_hash = :pass, salt = :salt WHERE user_id = :id";
    $stmt = $db->prepare($sql);

    $stmt->bindParam(':pass', $hash, PDO::PARAM_STR);
    $stmt->bindParam(':salt', $salt, PDO::PARAM_STR);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return Result::Ok(true);
  }

  static function validate_internal($db, int $user_id, $password): Result
  {


    $sql = "
        SELECT password_hash, salt
        FROM user_auth 
        WHERE user_id = :id
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $user_id);

    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (empty($row)) {
      return Result::Err("No authentication matching user");
    }
    $salt = $row['salt'];
    $given = self::hash_password($salt, $password);
    $expected = $row['password_hash'];

    if ($given == $expected) {
      return Result::Ok(true);
    }

    return Result::Err(new UnauthorizedError('Password is incorrect'));
  }
  public static function validate(int $user_id, $password): Result
  {
    try {
      Database::connect();
      $db = Database::db();
      return self::validate_internal($db, $user_id, $password);
    } catch (PDOException $e) {

      return Result::Err(new InternalServerError($e->getMessage()));
    }

    return Result::Ok(false);
  }
}
