<?php
require_once './backend/core/Result.php';
require_once './backend/db/Database.php';
require_once './backend/util/Util.php';

class Image
{

  public string $path;

  public function __construct($path)
  {
    $this->path = "media/" . $path;
  }


  public function inline_data(): array
  {
    $image = base64_encode(file_get_contents($this->path));
    $mime = mime_content_type($this->path);
    return [
      'inline_data' => [
        'mime_type' => $mime,
        'data' => $image,
      ],
    ];
  }

  public static function get(int $image_id): ?Image
  {

    try {
      Database::connect();

      $db = Database::db();


      $stmt = $db->prepare('SELECT * FROM images WHERE id = :id');
      $stmt->bindValue(':id', $image_id);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (empty($row)) {
        return null;
      }
      $image =  new Image($row['file_path']);
      return  $image;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }

    return null;
  }

  /** @return Image[]|null */
  public static function get_listing_image_paths(int $listing_id): ?array
  {
    try {
      Database::connect();

      $db = Database::db();


      $stmt = $db->prepare('SELECT file_path FROM images WHERE listing_id = :id');
      $stmt->bindValue(':id', $listing_id);
      $stmt->execute();
      $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (empty($images)) {
        return null;
      }
      return  $images;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }

    return null;
  }

  /** @return Result */
  //result can return null because there is none to return so result is still ok 
  //error only if database or unknown error
  public static function get_listing_images(int $listing_id): Result
  {
    try {
      Database::connect();

      $db = Database::db();


      $stmt = $db->prepare('SELECT file_path FROM images WHERE listing_id = :id');
      $stmt->bindValue(':id', $listing_id);
      $stmt->execute();
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (empty($rows)) {
        return Result::Ok(null);
      }
      $images = [];
      foreach ($rows as $row) {
        $images[] = new Image($row['file_path']);
      }

      return Result::Ok($images);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
  }


  public static function store_image($id, int $max = 5): Result
  {
    $uploaded = [];
    $errors = [];
    $target_dir = realpath(__DIR__ . '/../../media/');
    $files = $_FILES['images'];

    // Handle single and multiple files
    $is_multi = is_array($files['tmp_name']);
    $file_count = $is_multi ? count($files['tmp_name']) : 1;

    if ($file_count > $max) {
      $file_count = $max;
    }

    for ($i = 0; $i < $file_count; $i++) {
      $tmp_name = $is_multi ? $files['tmp_name'][$i] : $files['tmp_name'];
      $original_name = $is_multi ? $files['name'][$i] : $files['name'];

      $name = basename($original_name);
      $name = $id . '-' . $name;

      $extension = pathinfo($original_name, PATHINFO_EXTENSION);
      $target = substr(md5($name), 0, 16) . '.' . strtolower($extension); // hash + extension
      $target_loc = $target_dir . '/' . $target;

      if (move_uploaded_file($tmp_name, $target_loc)) {
        $uploaded[] = $target;
      } else {
        $errors[] = $name;
      }
    }

    if (empty($uploaded)) {
      return Result::Err(new BadRequestError(json_encode($errors)));
    }

    return Result::Ok($uploaded);
  }


  public static function save(int $listing_id): Result
  {
    $store_result = self::store_image($listing_id);
    if ($store_result->isErr()) {
      return $store_result;
    }
    $uploaded = $store_result->unwrap();
    $placeholders = [];
    $values = [];

    foreach ($uploaded as $upload) {
      $placeholders[] = '(?, ?)';
      $values[] = $upload;
      $values[] = $listing_id;
    }
    try {
      Database::connect();

      $db = Database::db();
      $sql = "INSERT INTO images (file_path, listing_id) VALUES " . implode(", ", $placeholders) . " ON DUPLICATE KEY UPDATE file_path = VALUES(file_path) ";
      $stmt = $db->prepare($sql);
      $stmt->execute($values);
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }

    return Result::Ok(null);
  }
}
