<?php
namespace App\Setup;

use App\Core\Database;
use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

class Updater
{
    public static function run(array $dbConfig, ?callable $logger = null): void
    {
        $pdo = Database::connect($dbConfig);
        self::runOnConnection($pdo, $dbConfig['driver'] ?? 'sqlite', $logger);
    }

    public static function runOnConnection(PDO $pdo, string $driver, ?callable $logger = null): void
    {
        self::ensureMigrationsTable($pdo, $driver);

        $applied = false;
        foreach (self::loadMigrations() as $version => $migration) {
            if (self::isApplied($pdo, $version)) {
                continue;
            }

            $description = $migration['description'] ?? $version;
            self::log($logger, sprintf('Starte Migration %s: %s', $version, $description));

            $pdo->beginTransaction();
            try {
                ($migration['up'])($pdo, $driver);
                self::markApplied($pdo, $version, $description);
                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw new RuntimeException(sprintf('Migration %s fehlgeschlagen: %s', $version, $exception->getMessage()), 0, $exception);
            }

            self::log($logger, sprintf('Migration %s abgeschlossen.', $version));
            $applied = true;
        }

        if ($applied) {
            self::log($logger, 'Alle Migrationen angewendet.');
        } else {
            self::log($logger, 'Datenbank ist bereits aktuell.');
        }
    }

    /**
     * @return array<string, array{description?: string, up: callable}>
     */
    private static function loadMigrations(): array
    {
        $dir = __DIR__ . '/Migrations';
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.php');
        if (!$files) {
            return [];
        }

        sort($files);

        $migrations = [];
        foreach ($files as $file) {
            $base = basename($file, '.php');
            $parts = explode('__', $base, 2);
            $version = $parts[0];
            $definition = require $file;

            if (isset($migrations[$version])) {
                throw new RuntimeException(sprintf('Migration %s verwendet eine bereits bestehende Versionsnummer %s.', $file, $version));
            }

            if (!is_array($definition) || !isset($definition['up']) || !is_callable($definition['up'])) {
                throw new RuntimeException(sprintf('Migration %s muss ein Array mit einem aufrufbaren "up"-Schlüssel zurückgeben.', $file));
            }

            $description = $definition['description'] ?? ($parts[1] ?? $base);
            $migrations[$version] = [
                'description' => $description,
                'up' => $definition['up'],
            ];
        }

        return $migrations;
    }

    private static function ensureMigrationsTable(PDO $pdo, string $driver): void
    {
        $timestampType = $driver === 'mysql' ? 'DATETIME' : 'TEXT';
        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(180) PRIMARY KEY,
    description TEXT,
    executed_at {$timestampType} NOT NULL
)
SQL
        );
    }

    private static function isApplied(PDO $pdo, string $version): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version LIMIT 1');
        $stmt->execute(['version' => $version]);

        return (bool) $stmt->fetchColumn();
    }

    private static function markApplied(PDO $pdo, string $version, string $description): void
    {
        $executedAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (version, description, executed_at) VALUES (:version, :description, :executed_at)');
        $stmt->execute([
            'version' => $version,
            'description' => $description,
            'executed_at' => $executedAt,
        ]);
    }

    private static function log(?callable $logger, string $message): void
    {
        if ($logger) {
            $logger($message);
        }
    }
}
