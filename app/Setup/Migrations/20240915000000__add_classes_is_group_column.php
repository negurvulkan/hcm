<?php

declare(strict_types=1);

return [
    'description' => 'Fügt die Spalte is_group zur Tabelle classes hinzu',
    'up' => static function (\PDO $pdo, string $driver): void {
        $hasColumn = static function (\PDO $pdo, string $driver, string $table, string $column): bool {
            if ($driver === 'mysql') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
                $stmt->execute(['table' => $table, 'column' => $column]);

                return (bool) $stmt->fetchColumn();
            }

            $stmt = $pdo->query("PRAGMA table_info('" . $table . "')");
            if (!$stmt) {
                return false;
            }

            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $info) {
                if (strcasecmp((string) ($info['name'] ?? ''), $column) === 0) {
                    return true;
                }
            }

            return false;
        };

        if ($hasColumn($pdo, $driver, 'classes', 'is_group')) {
            return;
        }

        if ($driver === 'mysql') {
            $pdo->exec('ALTER TABLE classes ADD COLUMN is_group TINYINT(1) NOT NULL DEFAULT 0');

            return;
        }

        $pdo->exec('ALTER TABLE classes ADD COLUMN is_group INTEGER NOT NULL DEFAULT 0');
    },
];
