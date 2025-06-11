<?php
require_once './backend/domain/Category.php';

class CategoryController
{

  public static function get_all()
  {

    $cats = Category::get_all();

    if ($cats === null) {
      return Responder::server_error("Unable to retrieve categories");
    }

    return Responder::success($cats);
  }
}
