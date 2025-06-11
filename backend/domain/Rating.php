<?php
require_once './backend/db/Database.php';
require_once './backend/core/Result.php';
require_once './backend/util/Util.php';



class Rating
{

  public float $rating;
  public int $count;

  public function __construct(array $rating)
  {
    $this->rating = $rating['rating'] ?? 0;
    $this->count = $rating['count'] ?? 0;
  }


  public static function get_listing_score(int $listing_id): ?Rating
  {

    try {
      Database::connect();

      $db = Database::db();

      $rating = $db->prepare("SELECT AVG(score) AS rating, COUNT(*) AS count FROM reviews WHERE listing_id = :id ");
      $rating->bindValue(":id", $listing_id);
      $rating->execute();

      $score = $rating->fetch(PDO::FETCH_ASSOC);


      if ($score) {
        $rate =  new Rating($score);
        return  $rate;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }
  public static function get_seller_score(int $seller_id): ?Rating
  {

    try {
      Database::connect();

      $db = Database::db();
      $rating = $db->prepare("
  SELECT AVG(r.score) AS rating, COUNT(*) AS count
  FROM reviews r
  JOIN listings l ON r.listing_id = l.listing_id
  WHERE l.seller_id = :id
");
      $rating->bindValue(":id", $seller_id);
      $rating->execute();

      $score = $rating->fetch(PDO::FETCH_ASSOC);

      if ($score) {
        $rate =  new Rating($score);
        return  $rate;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }
}
