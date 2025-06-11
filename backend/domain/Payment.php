<?php

require_once './backend/db/Database.php';
require_once './backend/core/Result.php';
require_once './backend/domain/Order.php';


class PaymentGateway
{

  public static function pay(): bool
  {
    return true;
  }
}

class PaymentSubmission
{
  public Order $order;
  public array $payment_info;
}

class Payment
{


  public static function pay_order(Order $order): Result
  {

    $paid = PaymentGateway::pay();
    if (!$paid) {
      return Result::Ok(false);
    }
    try {
      Database::connect();
      $db = Database::db();
      $db->beginTransaction();
      $order->pay($db);
      $stmt = $db->prepare("INSERT INTO payments (order_id, method, amount) VALUES(:id, :method, :amount)");
      $stmt->execute([
        ':id' => $order->order_id,
        ':method' => "card",
        'amount' => $order->total,
      ]);
      $db->commit();
      return Result::Ok(true);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }
}
