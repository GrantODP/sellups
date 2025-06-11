<?php

class C2Config
{
  public static $data = [];

  public static function load(): void
  {

    self::$data = require './backend/config/sys_config.php';
  }

  public static function get(string $table, string $key): mixed
  {
    return self::$data[$table][$key] ?? null;
  }

  public static function getAll(): array
  {
    return self::$data;
  }
}
