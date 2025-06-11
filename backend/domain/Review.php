<?php
require_once './backend/db/Database.php';
require_once './backend/util/Util.php';
require_once './backend/core/Result.php';
require_once './backend/domain/Order.php';



class Review
{
  public string $message;
  public int $user_id;
  public int $listing_id;
  public int $rating;


  public function __construct(array $data)
  {
    $this->message = trim($data['message']) ?? '';
    $this->user_id = trim($data['user_id']);
    $this->listing_id = trim($data['listing_id']);
    $this->rating = trim($data['rating']);
  }

  public  function write(): Result
  {

    try {
      Database::connect();

      $db = Database::db();

      $stmt = $db->prepare("INSERT INTO reviews (user_id, listing_id, score, message) VALUES(:user, :list, :rate, :message)
        ON DUPLICATE KEY UPDATE
        score = VALUES(score),
        message = VALUES(message)");
      $stmt->bindValue(":user", $this->user_id);
      $stmt->bindValue(":list", $this->listing_id);
      $stmt->bindValue(":message", $this->message);
      $stmt->bindValue(":rate", $this->rating);
      $stmt->execute();
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
    return Result::Ok(null);
  }

  public static function get_listing_reviews(int $listing_id): ?array
  {

    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("
  SELECT r.review_id, u.name, r.score AS rating, r.message, r.created_at
  FROM reviews r
  JOIN users u ON r.user_id = u.user_id
  WHERE r.listing_id = :id
");
      $stmt->bindValue(":id", $listing_id);
      $stmt->execute();

      $review = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if ($review) {
        return  $review;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }

  public static function get_review(int $id): ?array
  {

    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("
  SELECT *  FROM reviews r
  JOIN users u ON r.user_id = u.user_id
  WHERE r.review_id = :id
");
      $stmt->bindValue(":id", $id);
      $stmt->execute();

      $review = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($review) {
        return  $review;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }

  public static function get_user_reviews(int $uid, int $listing_id = 0): ?array
  {
    try {
      Database::connect();

      $db = Database::db();
      $params = [];
      $sql = "
  SELECT *  FROM reviews r
  JOIN users u ON r.user_id = u.user_id
  WHERE r.user_id = :id
";
      $params[':id'] = $uid;

      if (!empty($listing_id)) {
        $sql = $sql . " AND listing_id = :lid ";
        $params[':lid'] = $listing_id;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
      } else {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $review = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      if ($review) {
        return  $review;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }

  public static function edit_review($id, string $message, int $rating): Result
  {
    $rating = min($rating, 5);

    try {
      Database::connect();
      $db = Database::db();

      $stmt = $db->prepare("UPDATE reviews SET score = :rate, message = :message WHERE review_id = :id");
      $stmt->bindValue(":message", $message);
      $stmt->bindValue(":rate", $rating);
      $stmt->bindValue(":id", $id);
      $stmt->execute();

      $affected = $stmt->rowCount();
      if ($affected === 0) {
        return Result::Err(new NotFoundError("No review found with that ID, or no changes made"));
      }
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
    return Result::Ok(null);
  }
}
