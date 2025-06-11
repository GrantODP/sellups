<?php
require_once './backend/core/Result.php';
require_once './backend/db/Database.php';
require_once './backend/util/Util.php';


class Category
{
  public string $name;
  public string $description;
  public string $cat_id;


  public function __construct(array $data)
  {
    $this->name = $data['name'];
    $this->description = $data['description'];
    $this->cat_id = $data['cat_id'];
  }



  public static function get_by_id(string $id): ?Category
  {

    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("SELECT * FROM category WHERE cat_id = :id LIMIT 1");
      $stmt->bindValue(':id', $id);
      $stmt->execute();

      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($row) {
        $cat =  new Category($row);
        return  $cat;
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
      $stmt = $db->prepare("SELECT * FROM category");
      $stmt->execute();

      $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if ($cats) {
        return  $cats;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }

  public static function add_category($name, $description): Result
  {
    try {
      Database::connect();
      $db = Database::db();
      $stmt = $db->prepare("INSERT INTO category (name, description) VALUES(:n, :d)");
      $stmt->execute([':n' => $name, ':d' => $description]);

      if ($stmt->rowCount() == 0) {
        return Result::Err(new InternalServerError("Failed to insert category"));
      }
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
    return Result::Ok(true);
  }
  public static function update_category($id, $name, $description): Result
  {
    try {
      Database::connect();
      $db = Database::db();
      $stmt = $db->prepare("UPDATE category SET name = :name, description = :description WHERE cat_id = :id");
      $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':id' => $id
      ]);

      if ($stmt->rowCount() == 0) {
        return Result::Err(new NoContent("No change occured"));
      }
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
    return Result::Ok(true);
  }
}
