<?php
abstract class ErrorType
{
  public string $message;
  public int $code;

  public function __construct(string $message, int $status)
  {
    $this->message = $message;
    $this->code = $status;
  }

  public function get_message(): string
  {
    return $this->message;
  }

  public function get_code(): int
  {
    return $this->code;
  }
}

class BadRequestError extends ErrorType
{
  public function __construct(string $message = "Bad Request")
  {
    parent::__construct($message, 400);
  }
}

class UnauthorizedError extends ErrorType
{
  public function __construct(string $message = "Unauthorized")
  {
    parent::__construct($message, 401);
  }
}
class ConflictError extends ErrorType
{
  public function __construct(string $message = "Unauthorized")
  {
    parent::__construct($message, 409);
  }
}
class ForbiddenError extends ErrorType
{
  public function __construct(string $message = "Forbidden")
  {
    parent::__construct($message, 403);
  }
}

class NotFoundError extends ErrorType
{
  public function __construct(string $message = "Not Found")
  {
    parent::__construct($message, 404);
  }
}

class InternalServerError extends ErrorType
{
  public function __construct(string $message = "Internal Server Error")
  {
    parent::__construct($message, 500);
  }
}
class NoContent extends ErrorType
{
  public function __construct(string $message = "No Content")
  {
    parent::__construct($message, 204);
  }
}

abstract class Result
{
  abstract public function isOk(): bool;
  abstract public function isErr(): bool;
  abstract public function unwrap(): mixed;
  abstract public function unwrapErr(): mixed;

  public static function Ok(mixed $value): self
  {
    return new Ok($value);
  }

  public static function Err(mixed $error): self
  {
    return new Err($error);
  }
}

class Ok extends Result
{
  private mixed $value;

  public function __construct(mixed $value)
  {
    $this->value = $value;
  }

  public function isOk(): bool
  {
    return true;
  }

  public function isErr(): bool
  {
    return false;
  }

  public function unwrap(): mixed
  {
    return $this->value;
  }

  public function unwrapErr(): mixed
  {
    throw new RuntimeException("Tried to unwrapErr an Ok result.");
  }
}
class Err extends Result
{
  private mixed $error;

  public function __construct(mixed $error)
  {
    $this->error = $error;
  }

  public function isOk(): bool
  {
    return false;
  }

  public function isErr(): bool
  {
    return true;
  }

  public function unwrap(): mixed
  {
    throw new RuntimeException("Tried to unwrap an Err result: {$this->error}");
  }

  public function unwrapErr(): mixed
  {
    return $this->error;
  }
}
