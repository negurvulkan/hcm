<?php
namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    public static function connect(array $config): PDO
    {
        $driver = $config['driver'] ?? 'sqlite';

        try {
            if ($driver === 'sqlite') {
                $database = $config['database'] ?? __DIR__ . '/../../storage/database.sqlite';
                $dir = dirname($database);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $dsn = 'sqlite:' . $database;
                $pdo = new PDO($dsn);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec('PRAGMA foreign_keys = ON;');
                return $pdo;
            }

            if ($driver === 'mysql') {
                $host = $config['host'] ?? '127.0.0.1';
                $dbname = $config['database'] ?? '';
                $port = $config['port'] ?? '3306';
                $charset = $config['charset'] ?? 'utf8mb4';
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $dbname, $charset);
                $pdo = new PDO($dsn, $config['username'] ?? '', $config['password'] ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                return $pdo;
            }
        } catch (PDOException $exception) {
            throw new RuntimeException('Verbindung zur Datenbank fehlgeschlagen: ' . $exception->getMessage(), 0, $exception);
        }

        throw new RuntimeException('Unbekannter Datenbanktreiber: ' . $driver);
    }
}
