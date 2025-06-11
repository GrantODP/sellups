<?php

require_once './backend/db/Database.php';
require_once './backend/core/Result.php';
require_once './backend/domain/Cart.php';
require_once './backend/domain/Listing.php';

class SellerOrder
{
  public int $order_id;
  public array $items;
  public int $seller_id;
  public float $total;
}

class Order
{

  public int $order_id;
  public array $items;
  public int $user_id;
  public float $total;
  public $status;

  public function __construct(array $items, array $order)
  {
    $this->order_id = $order['order_id'];
    $this->items = $items;
    $this->total = $order['total_amount'];
    $this->user_id = $order['user_id'];
    $this->status = $order['status'];
  }

  public static function create_order(Cart $cart): Result
  {
    if (!$cart->has_items()) {
      return Result::Err("No items to construct a order");
    }
    $ids = array_keys($cart->cart_items);
    $listings = Listing::get_listings($ids);
    $total_amount = self::calc_total($cart, $listings);
    try {
      Database::connect();
      $db = Database::db();

      $db->beginTransaction();


      $stmt_order = $db->prepare("
        INSERT INTO orders (user_id, total_amount) 
        VALUES (:user_id, :total_amount)
      ");

      $stmt_order->execute([
        ':user_id' => $cart->user_id,
        ':total_amount' => $total_amount,
      ]);

      $order_id = $db->lastInsertId();

      $stmt_item = $db->prepare("
        INSERT INTO order_items (order_id, listing_id, quantity, price) 
        VALUES (:order_id, :listing_id, :quantity, :price)
    ");

      foreach ($listings as $item) {
        $id = $item['listing_id'];
        $stmt_item->execute([
          ':order_id' => $order_id,
          ':listing_id' => $id,
          ':quantity' => $cart->cart_items[$id],
          ':price' => $item['price'],
        ]);
      }

      $db->commit();
      return Result::Ok(null);
    } catch (PDOException $e) {

      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }

  public static function get_orders(User $user): Result
  {
    try {
      Database::connect();
      $db = Database::db();

      $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = :id ORDER BY created_at DESC");

      $stmt->execute([
        ':id' => $user->id,
      ]);

      $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

      return Result::Ok($orders);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }

  public static function get_seller_orders(Seller $seller): Result
  {
    try {
      Database::connect();
      $db = Database::db();

      $sql =
        "SELECT 
    orders.order_id,
    orders.user_id AS buyer_id,
    orders.*,
    listings.seller_id,
    listings.listing_id
FROM 
    orders
JOIN 
    order_items ON orders.order_id = order_items.order_id
JOIN 
    listings ON order_items.listing_id = listings.listing_id
WHERE 
    listings.seller_id = :id";

      $params = [
        ':id' => $seller->seller_id,
      ];

      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

      return Result::Ok($orders);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }
  public static function can_get_order(Seller $seller, Order $order): Result
  {
    try {
      Database::connect();
      $db = Database::db();

      $sql =
        "SELECT
    order_items.*
FROM 
    orders
JOIN 
    order_items ON orders.order_id = order_items.order_id
JOIN 
    listings ON order_items.listing_id = listings.listing_id
WHERE 
    listings.seller_id = :id AND order_items.order_id = :oid";

      $params = [
        ':id' => $seller->seller_id,
        ':oid' => $order->order_id,
      ];

      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if ($stmt->rowCount() == 0) {
        return Result::Ok(false);
      } else {

        return Result::Ok(true);
      }
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }

  public static function get_order(int $order_id): ?Order
  {
    try {
      Database::connect();
      $db = Database::db();

      $stmt = $db->prepare("SELECT listing_id, quantity, price, subtotal FROM order_items WHERE order_id = :id");
      $stmt_total = $db->prepare("SELECT total_amount, user_id,status, order_id FROM orders WHERE order_id = :id");

      $stmt->execute([
        ':id' => $order_id,
      ]);
      $stmt_total->execute([
        ':id' => $order_id,
      ]);


      $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $total = $stmt_total->fetch(PDO::FETCH_ASSOC);

      if (empty($order_items)) {
        return null;
      }

      if (empty($stmt_total)) {
        return null;
      }
      return new Order($order_items, $total);
    } catch (PDOException $e) {
      echo $e->getMessage();
      return null;
    }
  }
  public static function delete_order(User $user, int $order_id): Result
  {

    try {
      Database::connect();
      $db = Database::db();

      $stmt = $db->prepare("DELETE FROM orders WHERE order_id = :order_id AND user_id = :user_id");
      $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $user->id,
      ]);

      if ($stmt->rowCount() === 0) {
        return Result::Err(new NotFoundError('Order for user not found'));
      }
      return Result::Ok(true);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }

  public function pay($db)
  {
    $this->update_status($db, 'paid');
  }

  public function update_status($db, $status): bool
  {
    $stmt = $db->prepare("UPDATE orders SET status = :status WHERE order_id = :id");

    $stmt->execute([
      ':id' => $this->order_id,
      ':status' => $status,
    ]);

    return $stmt->rowCount() == 0;
  }

  public static function update_order_status($status, Order $order): Result
  {
    if ($order->status === $status) {
      return Result::Ok(true);
    }
    if ($order->status === 'cancelled') {
      return Result::Err(new ConflictError("Order is cancelled"));
    }

    if ($order->status === "paid" && !$status === 'delivered') {
      return Result::Err(new ConflictError("Cant cancel for already paid order"));
    }
    if ($order->status === "delivered") {
      return Result::Err(new ConflictError("Cant update already delivered order"));
    }
    if ($order->status === "pending" && !$status === 'paid') {
      return Result::Err(new ConflictError("Order must be paid before any delivery"));
    }

    try {
      Database::connect();
      $db = Database::db();
      return Result::Ok($order->update_status($db, $status));
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }

  public static function calc_total(Cart $cart, array $listings): float
  {
    $amount = 0.00;

    foreach ($listings as $listing) {
      $id = $listing['listing_id'];
      $price = $listing['price'];
      $count = $cart->cart_items[$id];
      $amount = $amount + ($price * $count);
    }

    return $amount;
  }

  public static function has_paid_ordered($user_id, Listing $listing): Result
  {
    try {
      Database::connect();
      $db = Database::db();

      $stmtOrders = $db->prepare("
    SELECT order_id 
    FROM orders 
    WHERE user_id = :user_id 
      AND status IN ('paid', 'delivered')
");
      $stmtOrders->execute([':user_id' => $user_id]);
      $paidOrders = $stmtOrders->fetchAll(PDO::FETCH_COLUMN);

      if (empty($paidOrders)) {
        return Result::Ok(false);
      }

      $stmtItems = $db->prepare("
            SELECT * 
            FROM order_items 
            WHERE order_id = :order_id 
              AND listing_id = :listing_id 
            LIMIT 1
        ");

      foreach ($paidOrders as $orderId) {
        $stmtItems->execute([
          ':order_id' => $orderId,
          ':listing_id' => $listing->listing_id
        ]);

        if ($stmtItems->fetchColumn()) {
          return Result::Ok(true); // found listing in a paid order
        }
      }

      return Result::Ok(false);
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
      return Result::Err(new InternalServerError($e->getMessage()));
    }
  }
}
