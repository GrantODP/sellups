<?php

require_once './backend/db/Database.php';
require_once './backend/core/Result.php';
require_once './backend/domain/User.php';
require_once './backend/domain/Listing.php';


class Report
{

  public int $user_id;
  public int $listing_id;
  public string $message;

  public function __construct(User $user, Listing $listing, string $message)
  {

    $this->user_id = $user->id;
    $this->listing_id = $listing->listing_id;
    $this->message = $message;
  }


  public function submit(): Result
  {
    try {
      Database::connect();

      $db = Database::db();

      $stmt = $db->prepare("INSERT into report (user_id, listing_id, message) VALUES(:uid, :lid, :m)");
      $stmt->execute([
        ':uid' => $this->user_id,
        ':lid' => $this->listing_id,
        ':m' => $this->message,
      ]);

      if ($stmt->rowCount() == 0) {
        return Result::Err(new BadRequestError("User already has a report"));
      }

      return Result::Ok(0);
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
      return Result::Err(new InternalServerError("Error: " . $e->getMessage()));
    }
  }
}
