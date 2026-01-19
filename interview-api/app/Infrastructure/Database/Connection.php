<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class Connection
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(string $dsn = 'sqlite::memory:'): PDO
    {
        if (self::$instance === null) {
            self::$instance = new PDO($dsn, options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public static function createFromPdo(PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
