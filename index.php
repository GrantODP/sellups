<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
  echo "Error [$errno]: $errstr in $errfile on line $errline\n";
  echo "Call Stack:\n";
  debug_print_backtrace();
});
// Load required classes (use autoloading in real projects)
require_once './backend/core/Responder.php';
require_once './backend/core/RequestRouter.php';
require_once './backend/config/Config.php';
require_once './backend/db/Database.php';
require_once './backend/domain/User.php';
require_once './backend/controllers/UserController.php';
require_once './backend/domain/Listing.php';
require_once './backend/domain/Seller.php';
require_once './backend/core/Result.php';


C2Config::load();
/* Database::connect(); */




$router = new Router();

//UserController
$router->add_post('/api/v1/user/create', 'UserController::post');
$router->add_post('/api/v1/user/report', 'UserController::report');
$router->add_post('/api/v1/login', 'UserController::login');
$router->add_post('/api/v1/logout', 'UserController::logout');
$router->add_get('/api/v1/user', 'UserController::get_user');
$router->add_get('/api/v1/auth/status', 'UserController::auth_valid');
$router->add_post('/api/v1/user/message', 'UserController::send_message');
$router->add_post('/api/v1/user/message-seller', 'UserController::message_seller');
$router->add_get('/api/v1/user/conversations', 'UserController::get_conversations');
$router->add_get('/api/v1/user/message', 'UserController::get_messages_by_conversation');
$router->add_post('/api/v1/user/cart', 'UserController::add_to_cart');
$router->add_get('/api/v1/user/cart', 'UserController::get_cart');
$router->add_delete('/api/v1/user/cart', 'UserController::delete_cart_item');
$router->add_post('/api/v1/user/cart/checkout', 'UserController::checkout');
$router->add_get('/api/v1/user/orders', 'UserController::get_orders');
$router->add_get('/api/v1/user/reviews', 'UserController::get_user_reviews');
$router->add_delete('/api/v1/user/orders', 'UserController::delete_order');
$router->add_post('/api/v1/user/orders/pay', 'UserController::pay_order');
$router->add_put('/api/v1/user', 'UserController::update');
$router->add_put('/api/v1/user/password', 'UserController::update_password');
$router->add_put('/api/v1/user/reviews', 'UserController::edit_review');
$router->add_post('/api/v1/listings/reviews', 'UserController::write_review');
$router->add_get('/api/v1/user/seller', 'UserController::get_seller');
$router->add_post('/api/v1/user/profile-pic', 'UserController::upload_profile_pic');
$router->add_get('/api/v1/user/orders/listings', 'UserController::get_is_listing_paid');

//SellerController
$router->add_post('/api/v1/listings', 'SellerController::post_listing');
$router->add_get('/api/v1/sellers/listings', 'SellerController::get_listings');
$router->add_get('/api/v1/sellers', 'SellerController::get_seller');
$router->add_get('/api/v1/sellers/rating', 'SellerController::get_rating');
$router->add_get('/api/v1/sellers/orders', 'SellerController::get_orders');
$router->add_post('/api/v1/sellers/listings', 'SellerController::update_listing');
$router->add_delete('/api/v1/sellers/listings', 'SellerController::delete_listing');
$router->add_post('/api/v1/listings/media', 'SellerController::add_listing_images');
$router->add_post('/api/v1/sellers/orders', 'SellerController::update_order');
//Listing controller
$router->add_get('/api/v1/listings/{slug}', 'ListingController::get_listing');
$router->add_get('/api/v1/listings', 'ListingController::get_listing_single');
$router->add_get('/api/v1/listings/rating', 'ListingController::get_rating');
$router->add_get('/api/v1/listings/reviews', 'ListingController::get_reviews');
$router->add_get('/api/v1/locations', 'ListingController::get_locations');
/*$router->add_get('/c2c- commerce-site/api/v1/listings', 'ListingController::get_listing');*/
$router->add_get('/api/v1/listings/category', 'ListingController::get_listings_with_cat');
$router->add_get('/api/v1/listings/evaluate', 'ListingController::evaluate');
$router->add_get('/api/v1/listings/media', 'ListingController::get_listing_images');
$router->add_get('/api/v1/listings/search', 'ListingController::search_listing');
$router->add_get('/api/v1/listings/preview', 'ListingController::get_listing_preview');

//Categories
$router->add_get('/api/v1/categories', 'CategoryController::get_all');


//Admin
$router->add_get('/api/v1/admin/users', 'AdminController::get_users');
$router->add_get('/api/v1/admin/sellers', 'AdminController::get_sellers');
$router->add_delete('/api/v1/admin/users', 'AdminController::delete_user');
$router->add_delete('/api/v1/admin/listings', 'AdminController::delete_listing');
$router->add_delete('/api/v1/admin', 'AdminController::delete_admin');
$router->add_post('/api/v1/admin', 'AdminController::create_admin');
$router->add_post('/api/v1/admin/categories', 'AdminController::category');
$router->add_post('/api/v1/admin/seller/verification', 'AdminController::update_seller_verification');
$router->add_post('/api/v1/admin/user/password', 'AdminController::update_user_password');
$router->add_post('/api/v1/admin/user-admin', 'AdminController::make_user_admin');
$router->add_put('/api/v1/admin/categories', 'AdminController::category');
//Media
$router->add_get('/media/{slug}', 'MediaController::get');





//ONLY GETS
//Views 

$router->add_get('/test', 'TestController::test');
$router->add_get('/ads/{slug}', 'PageController::get_ad_page');
$router->add_get('/ads', 'PageController::get_all_ads_page');
$router->add_get('/browse', 'PageController::get_all_ads_page');
$router->add_get('', 'PageController::get_all_ads_page');
$router->add_get('/user', 'PageController::get_user');
$router->add_get('/login', 'PageController::login');
$router->add_get('/pay', 'PageController::payment');
$router->add_get('/post-ad', 'PageController::post_ad');
$router->add_get('/create-account', 'PageController::create_account');
$router->add_get('/seller', 'PageController::seller');

$router->add_get('/admin', 'PageAdminController::admin');











$default = 'PageController::get_all_ads_page';

$router->handle("", $default);

ob_end_flush();
