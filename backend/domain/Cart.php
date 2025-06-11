<?php

require_once './backend/db/Database.php';
require_once './backend/core/Result.php';
require_once './backend/domain/User.php';
require_once './backend/domain/Listing.php';


class Cart
{
  public $user_id;
  public array $cart_items = [];

  public static $MAX_CART_ITEM = 10;

  public function __construct(int $user_id, array $items = [])
  {
    $this->user_id = $user_id;

    foreach ($items as $row) {
      $this->cart_items[$row['listing_id']] = $row['quantity'];
    }
  }

  public function has_items(): bool
  {
    return !empty($this->cart_items);
  }




  public static function add_to_cart(User $user, Listing $listing, int $count): Result
  {
    $count = min($count, self::$MAX_CART_ITEM);
    try {
      Database::connect();
      $db = Database::db();

      $sql = "INSERT INTO cart_items (user_id, listing_id, quantity) 
        VALUES (:user_id, :listing_id, :quantity)
        ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)";

      $stmt = $db->prepare($sql);
      $stmt->execute([
        ':user_id' => $user->id,
        ':listing_id' => $listing->listing_id,
        ':quantity' => $count,
      ]);
      return Result::Ok(0);
    } catch (PDOException $e) {
      return Result::Err(new UnauthorizedError($e->getMessage()));
    }
  }

  public static function checkout(User $user): Result
  {

    try {
      Database::connect();
      $db = Database::db();

      $db->beginTransaction();
      $sql = "SELECT * FROM cart_items WHERE user_id = :user_id";

      $stmt = $db->prepare($sql);
      $stmt->execute([
        ':user_id' => $user->id,
      ]);

      $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $cart = null;

      if (!empty($cart_items)) {
        $sql_delete = "DELETE FROM cart_items WHERE user_id = :user_id";
        $stmt_delete = $db->prepare($sql_delete);
        $stmt_delete->execute([':user_id' => $user->id]);
        $cart = new Cart($user->id, $cart_items);
      } else {
        $cart =  new Cart($user->id);
      }

      $db->commit();


      return Result::Ok($cart);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
  }

  public static function get_cart(User $user): Result
  {

    try {
      Database::connect();
      $db = Database::db();

      $sql = "SELECT * FROM cart_items WHERE user_id = :user_id";

      $stmt = $db->prepare($sql);
      $stmt->execute([
        ':user_id' => $user->id,
      ]);

      $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $cart = null;

      if (!empty($cart_items)) {
        $cart = new Cart($user->id, $cart_items);
      } else {
        $cart =  new Cart($user->id);
      }

      return Result::Ok($cart);
    } catch (PDOException $e) {
      return Result::Err(new UnauthorizedError($e->getMessage()));
    }
  }

  public static function remove_from_cart($user_id, $listing_id): Result
  {
    try {
      Database::connect();
      $db = Database::db();


      $sql = "DELETE FROM cart_items WHERE user_id = :user_id AND listing_id = :listing_id";
      $stmt = $db->prepare($sql);
      $stmt->execute([
        ':user_id' => $user_id,
        ':listing_id' => $listing_id,
      ]);

      if ($stmt->rowCount() === 0) {
        return Result::Err(new NotFoundError("Listing not found in cart"));
      }

      return Result::Ok(0);
    } catch (PDOException $e) {
      return Result::Err(new UnauthorizedError($e->getMessage()));
    }
  }
}
