<?php

declare(strict_types=1);

namespace CreditTransactionService\Tests\TestCase;

use CreditTransactionService\Infrastructure\Config\Environment;
use PDO;

class DatabaseMigration
{
    public static function migrate(): void
    {
        $pdo = self::getConnection();

        // Drop and recreate test database
        $dbName = Environment::get('DB_DATABASE');
        $pdo->exec("DROP DATABASE IF EXISTS {$dbName}");
        $pdo->exec("CREATE DATABASE {$dbName}");
        $pdo->exec("USE {$dbName}");

        $sql = file_get_contents(__DIR__.'/../../../docker/mysql/init/init-migration.sql');
        $pdo->exec($sql);
    }

    public static function getConnection(): PDO
    {
        return new PDO(
            sprintf(
                'mysql:host=%s;port=%s',
                Environment::get('DB_HOST'),
                Environment::get('DB_PORT')
            ),
            Environment::get('DB_USERNAME'),
            Environment::get('DB_PASSWORD'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    }
}
