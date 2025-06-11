<?php

require_once './backend/db/Database.php';
require_once './backend/core/Result.php';

enum TokenStatus: string
{
  case Expired = 'expired';
  case Valid = 'valid';
  case Invalid = 'invalid';
  case Unknown = 'unknown';
  case Missing = 'missing';
}

class Token
{
  public array $token;
  public TokenStatus $status;

  public function __construct(array $token, TokenStatus $status)
  {
    $this->token = $token;
    $this->status = $status;
  }

  public function is_valid(): bool
  {
    return $this->status == TokenStatus::Valid;
  }

  public function user_id(): string
  {
    return $this->token['user_id'];
  }

  public function expire_time(): int
  {
    return $this->token['expire_at'];
  }


  public function message(): string
  {
    return $this->status->value;
  }
}

class Tokener
{
  private static $length = 16;
  public static $expire_duration = 1800;
  // Generate a secure random token
  public static function generate(): string
  {
    do {
      $token = bin2hex(random_bytes(self::$length / 2)); // 16-character token
    } while (self::exists($token));

    return $token;
  }


  public static function get_token($user_id): Result
  {
    try{
    Database::connect();
    $db = Database::db();
    
    $stmt = $db->prepare("SELECT token FROM tokens WHERE user_id = :id LIMIT 1");

    $stmt->execute(['id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (empty($row)) {
      return Result::Err(new NotFoundError("No token with matching user"));
    }

    return Result::Ok($row['token']);
    } catch(PDOException $e)
    {
      return Result::Err(new InternalServerError($e->getMessage()));

    }
  }

  public static function gen_user_token($user_id): Result
  {
    $token = self::generate();
    $result = self::save_token($user_id, $token);

    if ($result->isOk()) {
      return Result::Ok($token);
    } else {
      return $result;
    }
  }

  public static function save_token($user_id, $token): Result
  {
    try {

      $db = Database::db();
      $stmt = $db->prepare("INSERT INTO tokens (token, user_id, expires_at) VALUES (:token, :user_id, :expires_at)");
      $expires_at = date('Y-m-d H:i:s', time() + self::$expire_duration);

      $stmt->execute([
        ':token' => $token,
        ':user_id' => $user_id,
        ':expires_at' => $expires_at
      ]);
    } catch (PDOException $e) {
      return Result::Err(new UnauthorizedError($e->getMessage()));
    }
    return Result::Ok(null);
  }

  public static function get_user_id_from_token(string $token): Result
  {
    try {
      Database::connect();
      $db = Database::db();
      $stmt = $db->prepare("SELECT * FROM tokens WHERE token = :token LIMIT 1");
      $stmt->execute(['token' => $token]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if (empty($row)) {
        return Result::Err(new NotFoundError("Auth token not found. Must login"));
      }
    } catch (PDOException $e) {
      return Result::Err(new UnauthorizedError($e->getMessage()));
    }

    return Result::Ok($row);
  }




  private static function exists(string $token): bool
  {
    Database::connect();

    $db = Database::db();

    $stmt = $db->prepare("SELECT * FROM tokens WHERE token = :token LIMIT 1");
    $stmt->execute(['token' => $token]);
    return $stmt->fetchColumn() !== false;
  }
}
