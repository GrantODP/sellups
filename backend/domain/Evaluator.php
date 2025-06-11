<?php
require_once './backend/core/Result.php';
require_once './backend/domain/LlmRequester.php';
require_once './backend/domain/Listing.php';
require_once './backend/db/Database.php';
require_once './backend/util/Util.php';



class AdEvaluator
{



  public static function evaluate(Listing $ad, Rating $rating, array $images): Result
  {


    $prompt = file_get_contents("./prompts/listing_prompt.txt");
    $listing = "Here is the listing details: Price: R$ad->price, description: $ad->description, title: $ad->title, date posted: $ad->date. ";
    $srating = "Here is the rating: $rating->rating out of 5 made by $rating->count reviews.";

    $prompt = $prompt . "\n" . $listing . "\n" . $srating;
    $response = LlmRequester::prompt_with_images($prompt, $images);
    if ($response->isErr()) {
      return $response;
    }
    $reval = "This is your response evaluate the response and correct it,Dont categorize old and new response just give them. Give your old response and your new evaluation: Also give your response as if you were an expert and not a ai agent " . $response->unwrap();
    $response = LlmRequester::prompt_with_images($reval, $images);

    return $response;
  }
}
