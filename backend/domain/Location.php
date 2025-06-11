<?php
require_once './backend/core/Result.php';
require_once './backend/db/Database.php';
require_once './backend/util/Util.php';


class Location
{



  public static function get_or_insert($province, $city): Result
  {
    try {
      Database::connect();

      $db = Database::db();


      $stmt = $db->prepare("SELECT * FROM location WHERE province = :province AND city = :city LIMIT 1");
      $stmt->execute([
        ':province' => $province,
        ':city' => $city,
      ]);

      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!empty($row)) {
        return  Result::Ok($row['location_id']);
      }

      $stmt = $db->prepare("INSERT INTO location (province, city) VALUES (:province, :city)");
      $stmt->execute([
        ':province' => $province,
        ':city' => $city,
      ]);
      return Result::Ok($db->lastInsertId());
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }
  public static function get_all(): Result
  {
    try {
      Database::connect();

      $db = Database::db();

      $stmt = $db->prepare("SELECT * FROM location");
      $stmt->execute();

      $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (empty($row)) {
        return  Result::Err(new NotFoundError("No locations are available"));
      }
      return Result::Ok($row);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }
}
