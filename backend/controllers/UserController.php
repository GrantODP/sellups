<?php
require_once './backend/domain/User.php';
require_once './backend/domain/Listing.php';
require_once './backend/domain/Cart.php';
require_once './backend/domain/Order.php';
require_once './backend/domain/Report.php';
require_once './backend/domain/Review.php';
require_once './backend/domain/Message.php';
require_once './backend/domain/Payment.php';
require_once './backend/core/Token.php';;
require_once './backend/core/Authorizer.php';;
require_once './backend/util/Util.php';

class UserController
{

  // POST /user/create
  public static function post()
  {

    $data = get_input_json();
    if (!has_required_keys($data, ['name', 'password', 'email', 'contact'])) {
      Responder::bad_request("Invalid input");
      return;
    }


    $result = User::create($data);

    if ($result->isErr()) {
      $error = $result->unwrapErr();
      return Responder::error($error->message, $error->code);
    }

    return Responder::success();
  }

  // POST /login
  public static function login()
  {
    $data = get_input_json();
    if (empty($data)) {
      Responder::bad_request("invalid login submited");
      return;
    }

    if (!has_required_keys($data, ['email', 'password'])) {
      Responder::bad_request("Invalid input");
      return;
    }
    $email = trim($data['email']);
    $password = trim($data['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return Responder::bad_request($email . " is not a valid email");
    }

    $user = User::get_by_email($email);

    if (empty($user)) {
      Responder::unauthorized("No user with email $email");
    }

    $res_valid = Authorizer::validate($user->id, $password);
    if ($res_valid->isErr()) {
      $error = $res_valid->unwrapErr();
      return Responder::error($error->message, $error->code);
    }


    $token = Tokener::get_token($user->id);
    if ($token->isErr()) {
      $token = Tokener::gen_user_token($user->id);
    }

    if ($token->isErr()) {
      Responder::server_error('Unable to generate auth token');
      return;
    }

    setcookie(
      'auth_token',
      $token->unwrap(),
      [
        'expires' => time() + 3600, // 1 hour
        'path' => '/',
        'secure' => true,     // Only send over HTTPS
        'httponly' => true,   // Inaccessible to JavaScript
        'samesite' => 'Strict' // Prevent CSRF
      ]
    );
    return Responder::success();
  }

  // POST /logout
  public static function logout()
  {
    setcookie(
      'auth_token',
      '',
      [
        'expires' => time() - 3600, // 1 hour
        'path' => '/',
        'secure' => true,     // Only send over HTTPS
        'httponly' => true,   // Inaccessible to JavaScript
        'samesite' => 'Strict' // Prevent CSRF
      ]
    );
    return Responder::success();
  }

  // GET /user
  public static function get_user()
  {
    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      Responder::not_found("user not found");
    } else {
      Responder::success($user);
      return;
    }
  }

  // GET /user/reviews
  public static function get_user_reviews()
  {
    $id = trim($_GET['id'] ?? 0);

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("user not found");
    }

    $reviews = Review::get_user_reviews($user->id, $id);

    return Responder::success($reviews);
  }

  // PUT /user
  public static function update()
  {
    $data = get_input_json();
    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("user not found matching auth");
    }
    $sub = new UserEditSubmission($user->id, $data);
    $result = User::update_user_info($sub);

    if ($result->isErr()) {
      $error = $result->unwrapErr();
      return Responder::error($error->message, $error->code);
    }
    return Responder::success();
  }

  // PUT /user/password
  public static function update_password()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $data = get_input_json();
    if (!has_required_keys($data, ['old_password', 'password'])) {
      Responder::bad_request("No password or old password provided");
      return;
    }
    $old = trim($data['old_password']);
    $password = trim($data['password']);

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("user not found");
    }

    $result = User::update_password($user->id, $password, $old);

    if ($result->isErr()) {
      $error = $result->unwrapErr();
      return Responder::error($error->message, $error->code);
    }

    return Responder::success();
  }

  // POST /user/message
  public static function send_message()
  {

    $data = get_input_json();

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }


    if (!has_required_keys($data, ['receiver', 'message'])) {
      Responder::bad_request("Invalid input");
      return;
    }


    $data['sender_id'] = $auth_token->user_id();

    $message = new Message($data);
    $result = $message->post();
    if ($result->isErr()) {
      Responder::server_error('Failed sending message: ' . $result->unwrapErr());
      return;
    }
    return Responder::success();
  }


  // POST /user/message-seller
  public static function message_seller()
  {

    $data = get_input_json();

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }


    if (!has_required_keys($data, ['receiver', 'message'])) {
      Responder::bad_request("Invalid input");
      return;
    }


    $data['sender_id'] = $auth_token->user_id();
    $user_id = Seller::get_user_id($data['receiver']);

    if ($user_id === null) {
      return Responder::not_found("No seller by id: " . $data['receiver']);
    }

    $data['receiver'] = $user_id;

    $message = new Message($data);
    $result = $message->post();
    if ($result->isErr()) {
      Responder::server_error('Failed sending message: ' . $result->unwrapErr());
      return;
    }
    return Responder::success();
  }

  //POST user/cart
  public static function add_to_cart()
  {
    $data = get_input_json();
    if (!has_required_keys($data, ['listing_id', 'count'])) {
      Responder::bad_request("Invalid json params");
      return;
    }

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());
    $listing = Listing::get_by_id(trim($data["listing_id"]));

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }
    if (empty($listing)) {

      return Responder::not_found("No listing found matching id");
    }

    $result = Cart::add_to_cart($user, $listing, $data["count"]);

    if ($result->isErr()) {
      return Responder::error($result->unwrapErr());
    }

    return Responder::success();
  }

  //GET user/cart
  public static function get_cart()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }

    $result = Cart::get_cart($user);

    if ($result->isErr()) {
      return Responder::error($result->unwrapErr());
    }

    return Responder::success($result->unwrap());
  }
  //DELETE user/cart
  public static function delete_cart_item()
  {
    $listing_id = $_GET["id"] ?? 0;
    if (empty($listing_id)) {
      return Responder::bad_request('No listing id provided');
    }

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }


    $result = Cart::remove_from_cart($user->id, $listing_id);

    if ($result->isErr()) {
      return Responder::error($result->unwrapErr());
    }

    return Responder::success();
  }

  //POST user/cart/checkout
  public static function checkout()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }


    $result_cart = Cart::checkout($user);

    if ($result_cart->isErr()) {
      return Responder::result_error($result_cart);
    }

    $order_result = Order::create_order($result_cart->unwrap());

    if ($order_result->isErr()) {
      return Responder::result_error($order_result);
    }

    return Responder::success();
  }

  //GET users/orders
  public static function get_orders()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }

    //Single order
    $order_id = $_GET["id"] ?? 0;
    if ($order_id) {
      $order = Order::get_order($order_id);
      if ($order->user_id != $user->id) {
        return Responder::forbidden("User not authorized to view order");
      }
      return Responder::success($order);
    } else {
      //get all
      $result = Order::get_orders($user);

      if ($result->isErr()) {
        return Responder::error($result->unwrapErr());
      }

      return Responder::success($result->unwrap());
    }
  }
  // DELETE user/orders
  public static function delete_order()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }

    $order_id = $_GET["id"] ?? 0;
    if (empty($order_id)) {
      return Responder::not_found("Order for user not found");
    }
    $result = Order::delete_order($user, $order_id);
    if ($result->isErr()) {
      return Responder::server_error($result->unwrapErr());
    }

    $changed = $result->unwrap();

    if (!$changed) {
      return Responder::not_found("User does not have an order matching id");
    }

    return Responder::success($result->unwrap());
  }

  //POST users/orders/pay
  public static function pay_order()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $data = get_input_json();

    if (!has_required_keys($data, ['order_id', 'payment_meta'])) {
      Responder::bad_request("Invalid json params");
      return;
    }
    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }


    $order = Order::get_order(trim($data['order_id']));

    if (empty($order)) {
      return Responder::not_found("No order matching id");
    }

    if ($order->user_id != $user->id) {
      return Responder::forbidden("User not authorized to pay order");
    }

    $payemnt = Payment::pay_order($order);

    if ($payemnt->isErr()) {
      return Responder::server_error($payemnt->unwrapErr());
    }


    if (!$payemnt->unwrap()) {
      return Responder::bad_request("Payment for $order->order_id failed");
    }

    return Responder::success($order);
  }
  //GET users/orders/listings
  public static function get_is_listing_paid()
  {

    $auth_token = Authorizer::validate_token_header();
    $listing = $_GET['id'] ?? 0;
    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }

    $listing = Listing::get_by_id($listing);

    if (empty($listing)) {
      return Responder::not_found("No listing found matching id");
    }

    $result = Order::has_paid_ordered($auth_token->user_id(), $listing);

    if ($result->isErr()) {
      return Responder::result_error($result);
    }

    return Responder::success($result->unwrap());
  }
  //PUT users/review
  public static function edit_review()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $data = get_input_json();
    if (!has_required_keys($data, ['message', 'rating'])) {
      Responder::bad_request("Invalid json params");
      return;
    }
    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }

    $review = Review::get_review(trim($data["review_id"]));

    if (!$review) {
      return Responder::not_found("Review matching id not found");
    }
    $uid = $review["user_id"] ?? 0;

    if ($uid != $user->id) {
      return Responder::forbidden("User not owner of review");
    }

    $result = Review::edit_review($review['review_id'], $data['message'], $data['rating']);
    if ($result->isErr()) {
      return Responder::result_error($result);
    }

    return Responder::success();
  }
  //POST users/report
  public static function report()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $data = get_input_json();

    if (!has_required_keys($data, ['listing_id', 'message'])) {
      Responder::bad_request("Invalid json params");
      return;
    }
    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }

    $listing_id =  $data['listing_id'] ?? 0;
    $message =  trim(($data['message'] ?? ''));

    $listing = Listing::get_by_id($listing_id);

    if (empty($listing)) {
      return Responder::not_found("No order matching id");
    }


    $report = new Report($user, $listing, $message);

    $result = $report->submit();


    if ($result->isErr()) {
      return Responder::result_error($result);
    }

    return Responder::success($report);
  }
  //GET auth/status
  public static function auth_valid()
  {

    $auth_token = Authorizer::validate_token_header();

    return Responder::success($auth_token->status);
  }

  // POST /listing/reviews
  public static function write_review()
  {

    $data = get_input_json();
    if (!has_required_keys($data, ['rating', 'listing_id'])) {
      return Responder::bad_request("Missing 1 or more review parameters ['rating', 'listing_id']");
    }

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::bad_request($auth_token->message());
    }
    $listing = $data['listing_id'] ?? 0;

    $listing = Listing::get_by_id($listing);

    if (empty($listing)) {
      return Responder::not_found("Listing not found");
    }

    $can_order = Order::has_paid_ordered($auth_token->user_id(), $listing);

    if ($can_order->isErr()) {
      return Responder::result_error($can_order);
    }

    if (!$can_order->unwrap()) {
      return Responder::forbidden("User has not paid for a listing");
    }

    $data['user_id'] = $auth_token->user_id();
    $review = new Review($data);
    $result = $review->write();

    if ($result->isErr()) {
      return Responder::error("unable to write review for listing: " . $review->listing_id);
    }

    return Responder::success();
  }

  //GET user/seller
  public static function get_seller()
  {

    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }


    $seller = Seller::get_seller_by_user_id($user->id);

    return Responder::success($seller);
  }
  //POST user/profile-pic
  public static function upload_profile_pic()
  {
    if (!isset($_FILES['images'])) {
      return Responder::bad_request("No image to upload");
    }
    $auth_token = Authorizer::validate_token_header();

    if (!$auth_token->is_valid()) {
      return Responder::unauthorized($auth_token->message());
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Responder::not_found("No user found matching auth token");
    }

    $result = $user->update_profile_pic();

    if ($result->isErr()) {
      return Responder::result_error($result);
    }

    return Responder::success();
  }
}
