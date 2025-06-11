<?php

require_once './backend/db/Database.php';
require_once './backend/core/Result.php';
require_once './backend/core/Authorizer.php';
require_once './backend/domain/User.php';
require_once './backend/domain/Listing.php';




class Admin
{



  public static function insert_admin(User $user): Result
  {
    try {
      Database::connect();
      $db = Database::db();

      $check = $db->prepare("SELECT * FROM admin WHERE user_id = :uid LIMIT 1");
      $check->execute([':uid' => $user->id]);

      if ($check->rowCount() > 0) {
        return Result::Err(new ConflictError("User is already an admin"));
      }

      $stmt = $db->prepare("INSERT INTO admin (user_id) VALUES (:uid)");
      $stmt->execute([':uid' => $user->id]);

      if ($stmt->rowCount() === 0) {
        return Result::Err(new InternalServerError("Failed to insert admin"));
      }

      return Result::Ok(true);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
  }

  public static function delete_admin(User $user): Result
  {
    try {
      Database::connect();
      $db = Database::db();

      $check = $db->prepare("DELETE FROM admin WHERE user_id = :uid LIMIT 1");
      $check->execute([':uid' => $user->id]);

      if ($check->rowCount() === 0) {
        return Result::Err(new NotFoundError("User is not a admin"));
      }

      return Result::Ok(true);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
  }

  public static function auth_admin(User $user): Result
  {
    try {
      Database::connect();
      $db = Database::db();

      $check = $db->prepare("SELECT * FROM admin WHERE user_id = :uid LIMIT 1");
      $check->execute([':uid' => $user->id]);

      if ($check->rowCount() == 0) {
        return Result::Err(new UnauthorizedError("User is not a admin"));
      }

      return Result::Ok(true);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
  }

  public static function change_user_password(User $user, $password): Result
  {
    try {
      Database::connect();
      $db = Database::db();
      $res = Authorizer::update_validation_force($db, $user->id, $password);
      return $res;
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
  }
}
