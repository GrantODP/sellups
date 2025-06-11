<?php

require_once './backend/db/Database.php';
require_once './backend/core/Result.php';
require_once './backend/core/Authorizer.php';
require_once './backend/domain/ItemImage.php';

class UserEditSubmission
{

  public string $contact;
  public int $id;
  public function __construct($id, $data)
  {
    $this->id = $id;
    $this->contact = $data["contact"] ?? "";
  }
}

class User
{
  public int $id;
  public string $name;
  public string $email;
  public string $contact;
  public string $profile_pic;

  public function __construct(array $data)
  {
    $this->id = $data['user_id'];
    $this->name = $data['name'];
    $this->email = $data['email'];
    $this->contact = $data['contact'];
    $this->profile_pic = $data['profile_pic'] ?? "";
  }

  public static function get_by_id(string $user_id): ?User
  {
    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :userid LIMIT 1");
      $stmt->bindValue(':userid', $user_id);
      $stmt->execute();

      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($row) {
        $user =  new User($row);
        return  $user;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }

    return null;
  }

  public static function get_all(): ?array
  {
    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("SELECT * FROM users");
      $stmt->execute();


      $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
      // Rename 'user_id' to 'id' in each user record
      $renamed = array_map(function ($user) {
        $user['id'] = $user['user_id'];
        unset($user['user_id']);
        return $user;
      }, $row);

      return $renamed;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }

    return null;
  }

  public static function get_by_email(string $email = ""): ?User
  {
    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
      $stmt->bindValue(':email', $email);
      $stmt->execute();

      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($row) {
        $user =  new User($row);
        return  $user;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }

    return null;
  }

  public static function has_no_duplicate_info($db, $data): Result
  {
    $sql = "
        SELECT 
            EXISTS (SELECT 1 FROM users WHERE email = :email) AS email_exists,
            EXISTS (SELECT 1 FROM users WHERE contact = :contact) AS phone_exists,
            EXISTS (SELECT 1 FROM users WHERE name = :name) AS name_exists
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([
      ':email' => $data['email'],
      ':contact' => $data['contact'],
      ':name'  => $data['name'],
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['email_exists']) {
      return Result::Err(new ConflictError("Email already exists"));
    }

    if ($result['phone_exists']) {
      return Result::Err(new ConflictError("Phone number already exists or belongs to someone else"));
    }

    if ($result['name_exists']) {
      return Result::Err(new ConflictError("Name already exists"));
    }

    return Result::Ok(0);
  }

  public static function internal_create($db, $data)
  {

    $stmt = $db->prepare("INSERT INTO users (name, email, contact) VALUES (:name, :email, :contact)");
    $stmt->execute([
      ':name' => $data['name'],
      ':email' => $data['email'],
      ':contact' => $data['contact'],
    ]);
    $last_id = $db->lastInsertId();
    Authorizer::store_validation($db, $last_id, $data['password']);
  }

  public static function create($data): Result
  {
    $data['name'] = trim($data['name']);
    $data['password'] = trim($data['password']);
    $data['email'] = trim($data['email']);
    $data['contact'] = trim($data['contact']);

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      return Result::Err(new BadRequestError($data['email'] . " is not a valid email"));
    }

    try {
      Database::connect();

      $db = Database::db();
      $db->beginTransaction();

      $result = self::has_no_duplicate_info($db, $data);
      if ($result->isErr()) {
        return $result;
      }
      $result = self::internal_create($db, $data);

      $db->commit();
      return Result::Ok(0);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
  }

  public static function delete_user(int $user_id): Result
  {
    try {
      $db = Database::db();
      $stmt = $db->prepare("DELETE FROM users WHERE user_id = :user_id");
      $stmt->execute([':user_id' => $user_id]);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
    return Result::Ok(null);
  }

  public static function update_user_info(UserEditSubmission $edit): Result
  {

    try {
      Database::connect();
      $db = Database::db();
      $db->beginTransaction();
      if (!empty($edit->contact)) {
        self::update_contact($db, $edit->id, $edit->contact);
      } else {
        return Result::Err(new BadRequestError("No contact info to update with"));
      }
      $db->commit();
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
    return Result::Ok(null);
  }
  public static function update_password(int $user_id, string $password, string $old_password): Result
  {
    try {
      Database::connect();
      $db = Database::db();
      $db->beginTransaction();
      $updated = Authorizer::update_validation($db, $user_id, $password, $old_password);
      if ($updated->isErr()) {
        return $updated;
      }
      $db->commit();
      return Result::Ok(true);
    } catch (PDOException $e) {
      return Result::Err("Error: " . $e->getMessage());
    }
  }

  public static function update_contact($db, int $user_id, string $contact)
  {
    $stmt = $db->prepare("UPDATE users SET contact = :contact WHERE user_id = :id");
    $stmt->execute([
      ':contact' => $contact,
      ':id' => $user_id,
    ]);
  }

  public function update_profile_pic(): Result
  {
    try {
      $stored = Image::store_image($this->id, 1);
      if ($stored->isErr()) {
        return $stored;
      }
      $target_loc = $stored->unwrap()[0];

      Database::connect();
      $db = Database::db();
      $db->beginTransaction();
      self::internal_update_profile_pic($db, $this->id, $target_loc);
      $db->commit();
      return Result::Ok(true);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }
  static function internal_update_profile_pic($db, int $user_id, string $dir)
  {
    $stmt = $db->prepare("UPDATE users SET profile_pic = :pic WHERE user_id = :id");
    $stmt->execute([
      ':pic' => $dir,
      ':id' => $user_id,
    ]);
  }
}
