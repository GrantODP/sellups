<?php

class Responder
{

  public static function json($data, int $status = 200): void
  {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
  }

  public static function success($data = [], string $message = 'Success', int $status = 200): void
  {
    self::json([
      'status' => 'success',
      'message' => $message,
      'data' => $data
    ], $status);
  }
  public static function success_paged(int $page_num, int $limit, $data = [], string $message = 'Success', int $status = 200): void
  {
    self::json([
      'status' => 'success',
      'message' => $message,
      'page' => $page_num,
      'limit' => $limit,
      'data' => $data
    ], $status);
  }
  public static function error(string $message = 'An error occurred', int $status = 400): void
  {
    self::json([
      'status' => 'error',
      'message' => $message
    ], $status);
  }
  public static function bad_request(string $message = 'Bad Request'): void
  {
    self::error($message, 400);
  }

  public static function unauthorized(string $message = 'Unauthorized'): void
  {
    self::error($message, 401);
  }

  public static function forbidden(string $message = 'Forbidden'): void
  {
    self::error($message, 403);
  }

  public static function not_found(string $message = 'Not Found'): void
  {
    self::error($message, 404);
  }

  public static function server_error(string $message = 'Internal Server Error'): void
  {
    self::error($message, 500);
  }

  public static function result_error(Result $result): void
  {
    $error = $result->unwrapErr();
    self::error($error->message, $error->code);
  }
}
