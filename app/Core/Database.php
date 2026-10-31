<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = self::config();
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('pdo_mysql PHP eklentisi aktif değil. Hosting panelinden etkinleştirin.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );

        self::$connection = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }

    /** @return array{host:string,port:int,database:string,username:string,password:string} */
    private static function config(): array
    {
        $file = dirname(__DIR__, 2) . '/config/app.php';
        $fileConfig = is_file($file) ? require $file : [];
        $db = is_array($fileConfig) ? ($fileConfig['database'] ?? []) : [];

        return [
            'host' => (string) ($db['host'] ?? getenv('DB_HOST') ?: '127.0.0.1'),
            'port' => (int) ($db['port'] ?? getenv('DB_PORT') ?: 3306),
            'database' => (string) ($db['database'] ?? getenv('DB_DATABASE') ?: 'takimbul'),
            'username' => (string) ($db['username'] ?? getenv('DB_USERNAME') ?: 'root'),
            'password' => (string) ($db['password'] ?? getenv('DB_PASSWORD') ?: ''),
        ];
    }
}
