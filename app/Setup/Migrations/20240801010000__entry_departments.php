<?php

declare(strict_types=1);

return [
    'description' => 'Abteilungskennzeichen für Nennungen',
    'up' => static function (\PDO $pdo, string $driver): void {
        $columnExists = static function (\PDO $pdo, string $driver, string $table, string $column): bool {
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
                if (strcasecmp((string) $info['name'], $column) === 0) {
                    return true;
                }
            }

            return false;
        };

        if (!$columnExists($pdo, $driver, 'entries', 'department')) {
            $type = $driver === 'mysql' ? 'VARCHAR(120)' : 'TEXT';
            $pdo->exec('ALTER TABLE entries ADD COLUMN department ' . $type);
        }
    },
];
