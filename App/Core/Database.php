<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function connect()
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    "mysql:host=" .
                        $_ENV["DB_HOST"] .
                        ";dbname=" .
                        $_ENV["DB_NAME"] .
                        ";charset=utf8",
                    $_ENV["DB_USERNAME"],
                    $_ENV["DB_PASSWORD"] ?? "",
                );
                self::$pdo->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION,
                );
                // Burada FETCH_ASSOC modunu varsayılan yapıyoruz
                self::$pdo->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC,
                );
                self::$pdo->exec("SET time_zone = '+00:00'");
            } catch (PDOException $e) {
                throw new \RuntimeException(
                    "Database connection error: " . $e->getMessage(),
                );
            }
        }
        return self::$pdo;
    }
}
