<?php
require_once './backend/core/Result.php';
require_once './backend/db/Database.php';
require_once './backend/util/Util.php';
class ListingSubmission
{
  public int $id;
  public string $seller_id;
  public string $price;
  public string $cat_id;
  public string $location_id;

  public string $title;
  public ?string $description;
  public string $slug;

  public function __construct(array $data, $listing_id = 0)
  {
    $this->id = $listing_id;
    $this->seller_id = $data['seller_id'];
    $this->price = $data['price'];
    $this->cat_id = $data['cat_id'] ?? 0;
    $this->location_id = $data['location_id'] ?? 0;
    $this->title = $data['title'];
    $this->description = $data['description'] ?? null;
    $this->slug = $this->seller_id  . '-' .  gen_slug($this->title);
  }
}

class Listing
{

  public string $listing_id;
  public string $seller_id;
  public string $price;
  public string $date;
  public string $cat_id;
  public string $province;
  public string $city;
  public string $title;
  public ?string $description;
  public string $slug;

  public function __construct(array $data)
  {
    $this->listing_id = $data['listing_id'];
    $this->seller_id = $data['seller_id'];
    $this->price = $data['price'];
    $this->date = $data['created_at'];
    $this->cat_id = $data['cat_id'];
    $this->province = $data['province'];
    $this->city = $data['city'];
    $this->title = $data['title'];
    $this->description = $data['description'] ?? null;
    $this->slug = $data['slug'] ?? null;
  }


  public static function post(ListingSubmission $submission): Result
  {

    try {
      Database::connect();

      $db = Database::db();
      $db->beginTransaction();
      $stmt_list = $db->prepare("INSERT INTO listings (seller_id, price) VALUES (:sellid, :price)");
      $stmt_list->execute([
        ':sellid' => $submission->seller_id,
        ':price' => $submission->price,
      ]);

      $listing_id = $db->lastInsertId();
      $stmt_ad = $db->prepare("INSERT INTO listing_ad (listing_id, cat_id, location_id, title, description, slug) VALUES (:listid, :cat_id, :loc, :title, :descp, :slug)");
      $stmt_ad->execute([
        ':listid' => $listing_id,
        ':cat_id' => $submission->cat_id,
        ':loc' => $submission->location_id,
        ':title' => $submission->title,
        ':descp' => $submission->description,
        ':slug' => $submission->slug,
      ]);
      $db->commit();
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }
    return Result::Ok(null);
  }

  public static function get_by_id(int $listing_id): ?Listing
  { {
      try {
        Database::connect();

        $db = Database::db();
        $sql = "
SELECT 
  l.listing_id,
  l.seller_id,
  l.price,
  l.date_posted,
  la.ad_id,
  la.cat_id,
  la.location_id,
  la.title,
  la.description,
  la.created_at,
  la.slug,
  loc.province,
  loc.city
FROM listings l
JOIN listing_ad la ON l.listing_id = la.listing_id
JOIN location loc ON la.location_id = loc.location_id
WHERE l.listing_id = :id
";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $listing_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
          $listing =  new Listing($row);
          return  $listing;
        }
      } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
      }

      return null;
    }
  }

  public static function get_by_sid(int $seller_id): array
  { {
      try {
        Database::connect();

        $db = Database::db();
        $sql = "
SELECT 
  l.listing_id,
  l.seller_id,
  l.price,
  l.date_posted,
  la.ad_id,
  la.cat_id,
  la.location_id,
  la.title,
  la.description,
  la.created_at,
  la.slug,
  loc.province,
  loc.city
FROM listings l
JOIN listing_ad la ON l.listing_id = la.listing_id
JOIN location loc ON la.location_id = loc.location_id
WHERE l.seller_id = :id
";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $seller_id);
        $stmt->execute();

        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return  $row;
      } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
      }
      return [];
    }
  }

  public static function get_by_slug(string $slug): ?Listing
  {
    try {
      Database::connect();

      $db = Database::db();
      $sql = "
SELECT 
  l.listing_id,
  l.seller_id,
  l.price,
  l.date_posted,
  la.ad_id,
  la.cat_id,
  la.location_id,
  la.title,
  la.description,
  la.created_at,
  la.slug,
  loc.province,
  loc.city
FROM listings l
JOIN listing_ad la ON l.listing_id = la.listing_id
JOIN location loc ON la.location_id = loc.location_id
WHERE la.slug = :slug
";

      $stmt = $db->prepare($sql);
      $stmt->bindValue(':slug', $slug);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (empty($row)) {
        return null;
      }
      $listing =  new Listing($row);
      return  $listing;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }

    return null;
  }
  public static function get_all_by_category(string $cat_id): ?array
  {
    try {
      Database::connect();

      $db = Database::db();
      $sql = "
SELECT 
  l.listing_id,
  l.seller_id,
  l.price,
  l.date_posted,
  la.ad_id,
  la.cat_id,
  la.location_id,
  la.title,
  la.description,
  la.created_at,
  la.slug,
  loc.province,
  loc.city
FROM listings l
JOIN listing_ad la ON l.listing_id = la.listing_id
JOIN location loc ON la.location_id = loc.location_id
WHERE la.cat_id = :cat_id
";

      $stmt = $db->prepare($sql);
      $stmt->bindValue(':cat_id', $cat_id);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (empty($row)) {
        return null;
      }
      return  $row;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }

    return null;
  }

  public static function get_all_by_page(int $page, int $count, string $sort = "listing_id", bool $ascend = true): ?array
  {
    try {
      Database::connect();
      $offset = ($page - 1) * $count;
      $order = $ascend ? 'ASC' : 'DESC';

      $allowed = ['listing_id', 'price', 'date_posted', 'title', 'created_at'];
      if (!in_array($sort, $allowed)) {
        $sort = 'listing_id';
      }

      $db = Database::db();

      $sql = "
      SELECT 
        l.listing_id,
        l.seller_id,
        l.price,
        l.date_posted,
        la.ad_id,
        la.cat_id,
        la.location_id,
        la.title,
        la.description,
        la.created_at,
        la.slug,
        loc.province,
        loc.city
      FROM listings l
      JOIN listing_ad la ON l.listing_id = la.listing_id
      JOIN location loc ON la.location_id = loc.location_id
      ORDER BY $sort $order
      LIMIT :count OFFSET :offset
    ";

      $stmt = $db->prepare($sql);
      $stmt->bindValue(':count', $count, PDO::PARAM_INT);
      $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
      $stmt->execute();

      $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $lists ?: null;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
      return null;
    }
  }


  public static function get_by_col_and_page(string $column, int $id, int $page, int $count, string $sort = "date_posted", string $sort_dir = 'asc'): ?array
  {
    try {
      Database::connect();
      $offset = ($page - 1) * $count;
      $order = strtolower($sort_dir) === 'asc' ? 'ASC' : 'DESC';

      // Whitelist columns for WHERE and ORDER BY
      $allowedColumns = ['listing_id', 'seller_id', 'cat_id', 'location_id'];
      $allowedSort = ['listing_id', 'price', 'date_posted', 'title', 'created_at'];

      // Validate column and sort
      if (!in_array($column, $allowedColumns)) {
        $column = 'listing_id';
      }
      if (!in_array($sort, $allowedSort)) {
        $sort = 'date_posted';
      }

      $db = Database::db();

      $sql = "
      SELECT 
        l.listing_id,
        l.seller_id,
        l.price,
        l.date_posted,
        la.ad_id,
        la.cat_id,
        la.location_id,
        la.title,
        la.description,
        la.created_at,
        la.slug,
        loc.province,
        loc.city
      FROM listings l
      JOIN listing_ad la ON l.listing_id = la.listing_id
      JOIN location loc ON la.location_id = loc.location_id
    ";

      if ($id > 0) {
        $sql .= " WHERE $column = :value";
      }

      $sql .= " ORDER BY $sort $order LIMIT :count OFFSET :offset";

      $stmt = $db->prepare($sql);

      if ($id > 0) {
        $stmt->bindValue(':value', $id, PDO::PARAM_INT);
      }

      $stmt->bindValue(':count', $count, PDO::PARAM_INT);
      $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
      $stmt->execute();

      $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $lists ?: null;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
      return null;
    }
  }



  public static function fuzzy_find(string $search_term, int $cat_id = 0, int $location_id = 0): ?array
  {
    try {
      Database::connect();
      $db = Database::db();

      $sql = "
      SELECT 
        l.listing_id,
        l.seller_id,
        l.price,
        l.date_posted,
        la.ad_id,
        la.cat_id,
        la.location_id,
        la.title,
        la.description,
        la.created_at,
        la.slug,
        loc.province,
        loc.city
      FROM listings l
      JOIN listing_ad la ON l.listing_id = la.listing_id
      JOIN location loc ON la.location_id = loc.location_id
      WHERE MATCH (la.title) AGAINST (:search IN NATURAL LANGUAGE MODE)
    ";

      $params = [':search' => $search_term];

      if ($cat_id > 0) {
        $sql .= ' AND la.cat_id = :cid';
        $params[':cid'] = $cat_id;
      }
      if ($location_id > 0) {
        $sql .= ' AND la.location_id = :lid';
        $params[':lid'] = $location_id;
      }

      $stmt = $db->prepare($sql);
      $stmt->execute($params);

      $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $lists ?: null;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
      return null;
    }
  }


  public static function get_listings(array $ids): ?array
  {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    try {
      Database::connect();

      $db = Database::db();


      $stmt = $db->prepare("SELECT * FROM listings WHERE listing_id IN ($placeholders)");
      $stmt->execute($ids);
      $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (empty($row)) {
        return null;
      }
      return  $row;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }

    return null;
  }
  public static function update(ListingSubmission $sub): Result
  {
    try {
      Database::connect();

      $db = Database::db();
      $db->beginTransaction();
      self::update_price($db, $sub->id, $sub->price);
      self::update_ad($db, $sub);
      $db->commit();
    } catch (PDOException $e) {
      return Result::Err(new InternalServerError($e->getMessage()));
    }

    return Result::Ok(0);
  }

  public static function update_ad($db, ListingSubmission $sub)
  {
    $stmt = $db->prepare("UPDATE listing_ad SET title = :title, description = :descp, slug = :slug WHERE listing_id = :id");
    $stmt->execute([
      ":id" => $sub->id,
      ":title" => $sub->title,
      ":descp" => $sub->description,
      ":slug" => $sub->slug,
    ]);
  }

  public static function update_price($db, int $id, float $price)
  {
    $stmt = $db->prepare("UPDATE listings SET price = :p WHERE listing_id = :id");
    $stmt->execute([
      ":id" => $id,
      ":p" => $price,
    ]);
  }


  public static function delete_listing(Seller $sell, $id): Result
  {
    try {
      Database::connect();
      $db = Database::db();

      // Join listings and listing_ad to check ownership
      $stmt = $db->prepare("
      SELECT l.listing_id
      FROM listings l
      JOIN listing_ad la ON l.listing_id = la.listing_id
      WHERE l.seller_id = :sid AND l.listing_id = :lid
      LIMIT 1
    ");
      $stmt->execute([':lid' => $id, ':sid' => $sell->seller_id]);

      if ($stmt->rowCount() === 0) {
        return Result::Err(new NotFoundError("Listing not found for seller"));
      }

      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $lid = $row['listing_id'];

      return self::delete_listing_force($lid);
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
      return Result::Err(new InternalServerError($e->getMessage()));
    }

    return Result::Ok(true);
  }

  public static function delete_listing_force($id): Result
  {
    try {
      Database::connect();
      $db = Database::db();
      $stmt_del = $db->prepare("DELETE FROM listing_ad WHERE listing_id = :id");
      $stmt_del->execute([':id' => $id]);
      if ($stmt_del->rowCount() === 0) {
        return Result::Err(new NotFoundError("No listing found by id"));
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
      return Result::Err(new InternalServerError($e->getMessage()));
    }
    return Result::Ok(true);
  }
}
