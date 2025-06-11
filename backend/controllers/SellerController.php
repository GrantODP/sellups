<?php
require_once './backend/domain/User.php';
require_once './backend/domain/Listing.php';
require_once './backend/domain/Location.php';
require_once './backend/domain/Seller.php';
require_once './backend/domain/Rating.php';
require_once './backend/core/Token.php';;
require_once './backend/core/Authorizer.php';;
require_once './backend/util/Util.php';


class SellerController
{


  // POST /listings
  public static function post_listing()
  {
    $input = get_input_json();
    if (empty($input)) {
      return Responder::bad_request("No json provided or failed parsing json");
    }
    if (!has_required_keys($input, ['price', 'cat_id', 'province', 'city', 'title'])) {
      return Responder::bad_request("Missing one or more of the following parameters: {price, cat_id, province, city, title");
    }

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $seller = Seller::get_or_insert($auth_token->user_id());

    $location = Location::get_or_insert(
      sentence_case($input['province']),
      sentence_case($input['city'])
    );
    if (empty($seller)) {
      return Responder::server_error("Unable to create a seller");
    }

    if ($location->isErr()) {
      $error = $location->unwrapErr();
      return Responder::server_error("Unable to find or make location: $error");
    }

    $input['seller_id'] = $seller->seller_id;
    $input['location_id'] = $location->unwrap();
    $listing_submission = new ListingSubmission($input);
    $list_result = Listing::post($listing_submission);

    if ($list_result->isErr()) {
      return Responder::server_error("Error posting a ad:" . $list_result->unwrapErr());
    }

    return Responder::success();
  }
  // GET /seller/listings
  public static function get_listings()
  {
    $seller_id = $_GET["id"] ?? 0;
    $listings = Listing::get_by_sid($seller_id);
    return Responder::success($listings);
  }

  // GET /seller
  public static function get_seller()
  {

    $id = $_GET['id'] ?? null;
    if ($id === null) {
      return Responder::bad_request("missing id");
    }

    $seller = Seller::get_seller($id);

    if ($seller == null) {
      return Responder::server_error("Unable to find seller");
    }


    return Responder::success($seller);
  }
  // GET /seller/rating
  public static function get_rating()
  {

    $id = $_GET['id'] ?? null;
    if ($id === null) {
      return Responder::bad_request("missing id");
    }

    $rating = Rating::get_seller_score($id);

    if ($rating == null) {
      return Responder::server_error("Unable to find rating for seller: " . $id);
    }

    return Responder::success($rating);
  }

  // PUT /sellers/listings
  public static function update_listing()
  {

    $input = get_input_json();
    if (empty($input)) {
      return Responder::bad_request("No json provided or failed parsing json");
    }
    if (!has_required_keys($input, ['listing_id', 'price', 'title', 'description',])) {
      return Responder::bad_request("Missing one or more   parameters");
    }

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }
    $listing = Listing::get_by_id($input['listing_id']);
    $seller = Seller::get_or_insert($auth_token->user_id());
    if (empty($listing)) {
      return Responder::not_found("No listing matching id");
    }
    if (empty($seller)) {
      return Responder::forbidden("User is not a seller");
    }

    if ($listing->seller_id != $seller->seller_id) {
      return Responder::forbidden("Seller is not the owner of listing");
    }

    $input['seller_id'] = $seller->seller_id;
    $sub = new ListingSubmission($input, $listing->listing_id);

    $result = Listing::update($sub);

    if ($result->isErr()) {
      return Responder::server_error($result->unwrapErr());
    }

    return Responder::success();
  }

  // DELETE /seller/listings
  public static function delete_listing()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }
    $listing_id = $_GET["id"] ?? 0;
    $seller = Seller::get_seller_by_user_id($auth_token->user_id());

    if (empty($seller)) {
      return Responder::forbidden("User is not a seller");
    }


    $result = Listing::delete_listing($seller, $listing_id);

    if ($result->isErr()) {
      return Responder::result_error($result);
    }

    $can_update = $result->unwrap();
    if (!$can_update) {
      return Responder::not_found("Seller does not have listing matching id");
    }

    return Responder::success();
  }
  //POST /listings/media
  public static function add_listing_images()
  {
    if (!isset($_FILES['images'])) {
      return Responder::bad_request("No files uploaded");
    }

    $id = $_GET['id'] ?? null;
    if ($id === null) {
      return Responder::bad_request("missing id");
    }

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::bad_request($auth_token->message());
    }

    $seller = Seller::get_seller_by_user_id($auth_token->user_id());
    $listing = Listing::get_by_id($id);

    if (empty($listing)) {
      return Responder::server_error("Unable to find listing: " . $id);
    }
    if (empty($seller)) {
      return Responder::server_error("Unable to find seller for listing: " . $id);
    }

    if ($listing->seller_id !== $seller->seller_id) {
      return Responder::unauthorized("Not authorized to edit listing");
    }


    $result = Image::save($listing->listing_id);

    if ($result->isErr()) {
      return Responder::result_error($result);
    }


    return Responder::success($result->unwrap());
  }

  //GET sellers/orders
  public static function get_orders()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $seller = Seller::get_seller_by_user_id($auth_token->user_id());

    if (empty($seller)) {
      return Responder::not_found("No seller found matching auth token");
    }

    //Single order
    $order_id = (int) ($_GET["id"] ?? 0);
    if ($order_id) {
      $order = Order::get_order($order_id);

      if (!$order) {
        return Responder::not_found("Order not found");
      }

      $result_bool = Order::can_get_order($seller, $order);

      if ($result_bool->isErr()) {
        return Responder::result_error($result_bool);
      }

      if (!$result_bool->unwrap()) {
        return Responder::forbidden("User not authorized to view order");
      }

      return Responder::success($order);
    } else {
      //get all
      $result = Order::get_seller_orders($seller);

      if ($result->isErr()) {
        return Responder::error($result->unwrapErr());
      }

      return Responder::success($result->unwrap());
    }
  }
  // PUT sellers/orders
  public static function update_order()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $seller = Seller::get_seller_by_user_id($auth_token->user_id());

    if (empty($seller)) {
      return Responder::not_found("No seller found matching auth token");
    }

    $data = get_input_json();

    $order_id = $data["id"] ?? 0;
    $status = trim($data["status"] ?? "");
    $accepted = ['paid', 'delivered', 'cancelled'];

    if (!in_array($status, $accepted)) {
      return Responder::bad_request("Invalid status parameter $status");
    }

    if ($order_id && $status) {
      $order = Order::get_order($order_id);

      if (!$order) {
        return Responder::not_found("Order not found");
      }

      $result_bool = Order::can_get_order($seller, $order);

      if ($result_bool->isErr()) {
        return Responder::result_error($result_bool);
      }

      if (!$result_bool->unwrap()) {
        return Responder::forbidden("User not authorized to view order");
      }


      $result = Order::update_order_status($status, $order);

      if ($result->isErr()) {
        return Responder::result_error($result);
      }

      return Responder::success($result->unwrap());
    }

    return Responder::bad_request("Missing request parameters");
  }
}
