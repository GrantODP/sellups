<?php

require_once "./frontend/core/View.php";

class PageController
{

  public static function get_ad_page(string $slug)
  {
    $cookie_name = "ad_slug";
    $cookie_val = $slug;
    setcookie($cookie_name, $cookie_val);
    return Views::get_view('ad.php');
  }

  public static function get_all_ads_page()
  {
    return Views::get_view('all_ads.php');
  }
  public static function get_user()
  {
    return Views::get_view('user.php');
  }
  public static function login()
  {
    return Views::get_view('login.html');
  }

  public static function payment()
  {
    return Views::get_view('payment.php');
  }

  public static function post_ad()
  {
    return Views::get_view('post_ad.php');
  }
  public static function create_account()
  {
    return Views::get_view('create_account.html');
  }

  public static function seller()
  {
    return Views::get_view('seller.php');
  }
}
