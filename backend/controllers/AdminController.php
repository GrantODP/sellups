<?php

require_once './backend/core/Result.php';
require_once './backend/core/Responder.php';
require_once './backend/util/Util.php';
require_once './backend/admin/Admin.php';
require_once './backend/domain/User.php';
require_once './backend/domain/Category.php';



class AdminController
{


  public static function handle_admin(): Result
  {
    $auth_token = Authorizer::validate_token_header();
    if (!$auth_token->is_valid()) {
      return Result::Err(new UnauthorizedError($auth_token->message()));
    }

    $user = User::get_by_id($auth_token->user_id());

    if (empty($user)) {
      return Result::Err(new UnauthorizedError("No user found matching auth token"));
    }

    $admin_auth = Admin::auth_admin($user);
    return $admin_auth;
  }

  // POST admin
  public static function create_admin()
  {
    $auth = self::handle_admin();
    if ($auth->isErr()) {
      return Responder::result_error($auth);
    }
    $user_id = $_GET['id'] ?? 0;
    $user = User::get_by_id($user_id);

    if (empty($user)) {
      return Responder::not_found('No user by id');
    }

    $result = Admin::insert_admin($user);
    if ($result->isErr()) {
      return Responder::result_error($result);
    }

    return Responder::success();
  }
  // DELETE admin
  public static function delete_admin()
  {
    $auth = self::handle_admin();
    if ($auth->isErr()) {
      return Responder::result_error($auth);
    }
    $user_id = $_GET['id'] ?? 0;
    $user = User::get_by_id($user_id);

    if (empty($user)) {
      return Responder::not_found('No user by id');
    }

    $result = Admin::delete_admin($user);
    if ($result->isErr()) {
      return Responder::result_error($result);
    }

    return Responder::success();
  }


  // GET admin/users
  public static function get_users()
  {
    $auth = self::handle_admin();
    if ($auth->isErr()) {
      return Responder::result_error($auth);
    }
    $email = $_GET['email'] ?? '';
    if (empty($email)) {
      $users = User::get_all();
    } else {
      $users = User::get_by_email($email);
    }
    return Responder::success($users);
  }
  // GET admin/sellers
  public static function get_sellers()
  {
    $auth = self::handle_admin();
    if ($auth->isErr()) {
      return Responder::result_error($auth);
    }
    $uid = $_GET['uid'] ?? 0;
    $sell = Seller::get_seller_by_user_id($uid);
    return Responder::success($sell);
  }

  // DELETE admin/users
  public static function delete_user()
  {
    $auth = self::handle_admin();
    if ($auth->isErr()) {
      return Responder::result_error($auth);
    }

    $id = $_GET['id'] ?? 0;
    $res = User::delete_user($id);

    if ($res->isErr()) {
      return Responder::result_error($res);
    }

    return Responder::success($res);
  }

  // DELETE admin/listings
  public static function delete_listing()
  {
    $auth = self::handle_admin();
    if ($auth->isErr()) {
      return Responder::result_error($auth);
    }

    $list = $_GET['id'] ?? 0;

    $res = Listing::delete_listing_force($list);

    if ($res->isErr()) {
      return Responder::result_error($res);
    }

    return Responder::success();
  }

  // admin/categories
  public static function category()
  {
    $auth = self::handle_admin();
    if ($auth->isErr()) {
      return Responder::result_error($auth);
    }

    $data = get_input_json();
    $name = trim($data['name'] ?? '');
    $id = $data['id'] ?? 0;
    $descp = trim($data['descp'] ?? '');

    if (empty($name) || empty($descp)) {
      return Responder::bad_request("name or descp is empty");
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method == 'POST') {
      $res = Category::add_category($name, $descp);
    } else if ($method == 'PUT') {
      if (empty($id)) {
        return Responder::bad_request("Id not given for category");
      }
      $res = Category::update_category($id, $name, $descp);
    }

    if ($res->isErr()) {
      return Responder::result_error($res);
    }

    return Responder::success($res);
  }

  // POST admin/seller/verification
  public static function update_seller_verification()
  {
    $auth = self::handle_admin();
    if ($auth->isErr()) {
      return Responder::result_error($auth);
    }
    $data = get_input_json();
    $id = $data['id'] ?? 0;
    $status = trim($data['status'] ?? '');

    if (empty($id) || empty($status)) {
      return Responder::bad_request("Empty seller id or status");
    }

    $seller = Seller::get_seller($id);

    if (empty($seller)) {
      return Responder::not_found("Seller not found");
    }

    $res = Seller::update_verification($id, $status);
    if ($res->isErr()) {
      return Responder::result_error($res);
    }

    return Responder::success($res);
  }
  // POST admin/user/password
  public static function update_user_password()
  {
    $auth = self::handle_admin();
    if ($auth->isErr()) {
      return Responder::result_error($auth);
    }


    $data = get_input_json();
    $id = $data['id'] ?? 0;
    $pass = trim($data['password'] ?? '');
    if (empty($id) || empty($pass)) {
      return Responder::bad_request("Empty user id or password");
    }

    $user = User::get_by_id($id);

    if (empty($user)) {
      return Responder::not_found("User not found");
    }

    $res = Admin::change_user_password($user, $pass);
    if ($res->isErr()) {
      return Responder::result_error($res);
    }

    return Responder::success();
  }
  // POST admin/user-admin
  public static function make_user_admin()
  {
    $auth = self::handle_admin();
    if ($auth->isErr()) {
      return Responder::result_error($auth);
    }

    $id = $_GET['id'] ?? '';
    $user = User::get_by_id($id);


    if (empty($user)) {
      return Responder::not_found("User not found");
    }

    $res = Admin::insert_admin($user);
    if ($res->isErr()) {
      return Responder::result_error($res);
    }

    return Responder::success();
  }
}
