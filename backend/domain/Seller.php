<?php
require_once './backend/core/Result.php';
require_once './backend/db/Database.php';

class Seller
{

  public string $seller_id;
  public string $verification;
  public string $created_at;
  public string $name;
  /* public string $email; */
  public string $contact;



  public function __construct(array $seller)
  {
    $this->seller_id = $seller['seller_id'];
    $this->created_at = $seller['created_at'];
    $this->verification = $seller['verification_status'];
    $this->name = $seller['name'];
    /* $this->email = $seller['email']; */
    $this->contact = $seller['contact'];
  }


  public static function post(int $user_id): Result
  {

    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("INSERT INTO sellers (user_id) VALUES (:userid)");
      $stmt->execute([
        ':userid' => $user_id,
      ]);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
    return Result::Ok(null);
  }


  public static function get_seller(int $seller_id): ?Seller
  {

    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("
  SELECT s.*, u.*
  FROM sellers s
  JOIN users u ON s.user_id = u.user_id
  WHERE s.seller_id = :seller
  LIMIT 1
");
      $stmt->bindValue(':seller', $seller_id);
      $stmt->execute();

      $row = $stmt->fetch(PDO::FETCH_ASSOC);


      if ($row) {
        $seller =  new Seller($row);
        return  $seller;
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
      $stmt = $db->prepare("
  SELECT s.*, u.*
  FROM sellers s
  JOIN users u ON s.user_id = u.user_id
  WHERE s.seller_id = :seller
  LIMIT 1
");
      $stmt->execute();

      $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $row;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }
  public static function get_user_id(int $seller_id): ?int
  {

    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("SELECT user_id FROM sellers WHERE seller_id = :seller LIMIT 1");
      $stmt->bindValue(':seller', $seller_id);
      $stmt->execute();

      $id = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($id) {
        return  $id['user_id'];
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }

  public static function get_seller_by_user_id(int $user_id): ?Seller
  {

    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("
  SELECT s.*, u.*
  FROM sellers s
  JOIN users u ON s.user_id = u.user_id
  WHERE u.user_id = :user_id
  LIMIT 1
");
      $stmt->bindValue(':user_id', $user_id);
      $stmt->execute();

      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($row) {
        $seller =  new Seller($row);
        return  $seller;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }

  public static function get_or_insert(int $user_id): ?Seller
  {

    $seller = self::get_seller_by_user_id($user_id);

    if ($seller != null) {
      return $seller;
    }

    $result = self::post($user_id);

    if (!$result->isOk()) {
      return null;
    }

    return self::get_seller_by_user_id($user_id);
  }
  public static function update_verification(int $sell_id, $status): Result
  {

    try {
      Database::connect();
      $db = Database::db();
      $db->beginTransaction();
      self::_update_verification($db, $sell_id, $status);
      $db->commit();
      return Result::Ok(0);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }

  static function _update_verification($db, int $seller_id, string $status)
  {
    $stmt = $db->prepare("UPDATE sellers SET verification_status = :status WHERE seller_id = :id");
    $stmt->execute([
      ':status' => $status,
      ':id' => $seller_id,
    ]);
  }
}
