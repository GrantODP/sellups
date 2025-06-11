

<?php


class Views
{

  public static function get_view(string $view)
  {
    return include "./frontend/views/$view";
  }
}
