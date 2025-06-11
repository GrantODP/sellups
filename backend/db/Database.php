<?php

require_once './backend/config/Config.php';

class Database
{
  private static ?PDO $pdo = null;

  public static function connect(): void
  {
    if (self::$pdo != null) {
      return;
    }

    $host = C2Config::get('database', "host");
    $user = C2Config::get('database', "user");
    $port = C2Config::get('database', "port");
    $password = C2Config::get('database', "password");
    $dbname = C2Config::get('database', "dbname");
    $conn = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    self::$pdo = $conn;
  }

  public static function db(): ?PDO
  {
    if (self::$pdo === null) {
      throw new Exception("Database not connected.");
    }
    return self::$pdo;
  }
}
