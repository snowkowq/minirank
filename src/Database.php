<?php

declare(strict_types=1);

namespace App;

use PDO;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $path = dirname(__DIR__) . '/data/minirank.sqlite';

            self::$connection = new PDO(
                'sqlite:' . $path,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            self::$connection->exec('PRAGMA foreign_keys = ON');
        }

        return self::$connection;
    }
}