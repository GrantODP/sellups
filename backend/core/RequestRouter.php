<?php

require_once 'Responder.php';

$back_pattern = __DIR__ . "/../controllers/*.php";
$front_pattern = __DIR__ . "/../../frontend/controllers/*.php";
foreach (glob($back_pattern) as $filename) {

  require_once $filename;
}
foreach (glob($front_pattern) as $filename) {

  require_once $filename;
}
class Router
{
  private $get_routes = [];
  private $post_routes = [];
  private $put_routes = [];
  private $delete_routes = [];
  private $dynamic_get_routes = [];

  public function add_post(string $path, $controller)
  {
    $this->post_routes[$path] = $controller;
  }
  public function add_put(string $path, $controller)
  {
    $this->put_routes[$path] = $controller;
  }
  public function add_delete(string $path, $controller)
  {
    $this->delete_routes[$path] = $controller;
  }
  public function add_get(string $path, $controller)
  {
    if (self::is_pattern($path)) {
      $dyn_path = self::convert_to_pattern($path);
      return $this->dynamic_get_routes[$dyn_path] = $controller;
    }

    $this->get_routes[$path] = $controller;
  }

  public function get($path)
  {

    if (self::call($path, $this->get_routes)) {
      return;
    }

    if (self::dynamic_call($path, $this->dynamic_get_routes)) {
      return;
    }
    return Responder::not_found("Unknown request");
  }
  public function post($path)
  {
    if (self::call($path, $this->post_routes)) {
      return;
    };

    return Responder::not_found("Unknown request");
  }
  public function put($path)
  {
    if (self::call($path, $this->put_routes)) {
      return;
    };

    return Responder::not_found("Unknown request");
  }

  public function delete_op($path)
  {
    if (self::call($path, $this->delete_routes)) {
      return;
    };

    return Responder::not_found("Unknown request");
  }

  private static function clean_uri($uri): string
  {
    $uri = rtrim($uri, '/');
    $uri = $uri === '' ? '' : $uri;
    return $uri;
  }

  public function handle($default = "", $controller = "")
  {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    $uri = parse_url($uri, PHP_URL_PATH);
    $uri = self::clean_uri($uri);
    if ($method === 'GET') {
      $this->get($uri);
    } elseif ($method === 'POST') {
      $this->post($uri);
    } elseif ($method === 'PUT') {
      $this->put($uri);
    } elseif ($method === 'DELETE') {
      $this->delete_op($uri);
    }

    if ($default === self::clean_uri($uri)) {
      call_user_func($controller);
    }
  }
  private static function call($path, $router): bool
  {

    $controller = $router[$path] ?? null;

    //route not found
    if ($controller == null) {
      return false;
    }
    try {
      call_user_func($controller);
    } catch (Throwable $e) {
      Responder::server_error($e->getMessage());
    }
    return true;
  }

  private static function dynamic_call($path, $router)
  {

    foreach ($router as $pattern => $controller) {
      if (preg_match($pattern, $path, $matches)) {

        array_shift($matches);
        try {
          return call_user_func_array($controller, $matches);
        } catch (Throwable $e) {
          Responder::server_error($e->getMessage());
        }
        return true;
      }
    }
    return false;
  }

  private static function is_pattern($path)
  {
    return strpos($path, '{') !== false || preg_match('/[\(\^]/', $path);
  }

  private static function convert_to_pattern($path)
  {
    $pattern = preg_replace('#\{[^}]+\}#', '([^/]+)', $path);
    return '#^' . $pattern . '$#';
  }
}
