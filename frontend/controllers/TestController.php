<?php
require_once './frontend/core/View.php';

class TestController
{


  public static function test()
  {
    Views::get_view('main');
    /*Views::get_script('script');*/
    /*Views::get_stylesheet('styles');*/
  }
}
