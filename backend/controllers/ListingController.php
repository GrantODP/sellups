<?php
require_once './backend/domain/User.php';
require_once './backend/domain/Listing.php';
require_once './backend/domain/Evaluator.php';
require_once './backend/domain/ItemImage.php';
require_once './backend/domain/Review.php';
require_once './backend/core/Token.php';;
require_once './backend/core/Result.php';;
require_once './backend/core/Authorizer.php';;
require_once './backend/util/Util.php';


class ListingController
{
  public static function get_listing($listing_slug)
  {
    $listing = $listing_slug ?? '';
    $listing = Listing::get_by_slug($listing);

    if (empty($listing)) {
      return Responder::not_found("Listing not found");
    }

    return Responder::success($listing);
  }
  public static function get_listing_single()
  {
    $id = $_GET['id'] ?? 0;
    $listing = Listing::get_by_id($id);

    if (empty($listing)) {
      return Responder::not_found("Listing not found");
    }

    return Responder::success($listing);
  }

  public static function get_listings_with_cat()
  {

    $id = $_GET['id'] ?? 0;
    $sort_val = $_GET['sort'] ?? null;
    $sort_dir = $_GET['dir'] ?? null;
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    $accepted_sort = ['price', 'date', 'title'];
    $accepted_sord_dir = ['asc', 'desc'];
    if (empty($sort_val) || !in_array($sort_val, $accepted_sort)) {
      $sort_val = 'date';
    }
    if (empty($sort_dir)  || !in_array($sort_dir, $accepted_sord_dir)) {
      $sort_dir = 'asc';
    }

    //todo: check if id, page and limit are ints
    $listings = Listing::get_by_col_and_page('cat_id', $id, $page, $limit, $sort_val, $sort_dir);
    if ($listings === null) {
      return Responder::not_found('No listings found in category');
    }
    return Responder::success_paged($page, $limit, $listings);
  }
  // GET /listing/rating
  public static function get_rating()
  {

    $id = $_GET['id'] ?? null;
    if ($id === null) {
      return Responder::bad_request("missing id");
    }

    $rating = Rating::get_listing_score($id);

    if ($rating == null) {
      return Responder::server_error("Unable to find rating for listing: " . $id);
    }

    return Responder::success($rating);
  }

  // GET /listing/reviews
  public static function get_reviews()
  {

    if (!has_required_keys($_GET, ['id'])) {
      return Responder::bad_request("missing id");
    }

    $id = $_GET['id'];

    $reviews = Review::get_listing_reviews($id);

    if ($reviews == null) {
      return Responder::not_found("Unable to find reviews for listing: " . $id);
    }

    return Responder::success($reviews);
  }


  //GET /listings/evaluate
  public static function evaluate()
  {

    $id = $_GET['id'] ?? null;
    if ($id === null) {
      return Responder::bad_request("missing id");
    }

    $rating = Rating::get_listing_score($id);

    $listing = Listing::get_by_id($id);
    if (empty($rating) || empty($listing)) {
      return Responder::server_error("Unable to find rating or listing for listing: " . $id);
    }

    $result = Image::get_listing_images($listing->listing_id);

    $images = $result->isOk() ?  ($result->unwrap() ?? []) : [];
    $res = AdEvaluator::evaluate($listing, $rating, $images);

    if ($res->isErr()) {
      return Responder::result_error($res);
    }

    return Responder::success($res->unwrap());
  }


  public static function get_listing_images()
  {

    $id = $_GET['id'] ?? null;
    if ($id === null) {
      return Responder::bad_request("missing id");
    }

    $result = Image::get_listing_images($id);

    if ($result->isErr()) {
      return Responder::result_error($result);
    }

    if (empty($result->unwrap())) {
      return Responder::not_found("Unable to find images for listing: " . $id);
    }

    return Responder::success($result->unwrap());
  }

  public static function get_listing_preview()
  {

    $id = $_GET['id'] ?? null;
    if ($id === null) {
      return Responder::bad_request("missing id");
    }

    $result = Image::get_listing_images($id);

    if ($result->isErr()) {
      return Responder::server_error("Server error");
    }

    if (empty($result->unwrap())) {
      return Responder::not_found("Unable to find preview for listing: " . $id);
    }

    return Responder::success($result->unwrap()[0]);
  }

  public static function search_listing()
  {
    if (!has_required_keys($_GET, ['query'])) {
      return Responder::bad_request('Missing query parameter');
    }

    $query = trim($_GET['query']);
    $cat_id = $_GET['cat'] ?? 0;
    $location_id = $_GET['loc'] ?? 0;

    $listings = Listing::fuzzy_find($query, $cat_id, $location_id);

    if ($listings == null) {
      $cerror = ($cat_id == 0) ? "" : ",category id:$cat_id";
      $lerror = ($location_id == 0) ? "" : ",location id:$location_id";
      return Responder::not_found("Listings matching '$query'$cerror$lerror not found");
    }
    return Responder::success($listings);
  }

  //GET locations
  public static function get_locations()
  {
    $location = Location::get_all();
    if ($location->isErr()) {
      $location = $location->unwrapErr();
      return Responder::error($location->message, $location->code);
    }

    return Responder::success($location->unwrap());
  }
}
