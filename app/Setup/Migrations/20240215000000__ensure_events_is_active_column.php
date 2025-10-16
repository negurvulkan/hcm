<?php

return [
    'description' => 'Stellt sicher, dass die Spalte is_active in events vorhanden ist',
    'up' => static function (\PDO $pdo, string $driver): void {
        if ($driver === 'mysql') {
            $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'is_active'");
            if ($stmt && $stmt->fetch() !== false) {
                return;
            }

            $pdo->exec("ALTER TABLE events ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 0");
            return;
        }

        $columns = $pdo->query('PRAGMA table_info(events)')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'is_active') {
                return;
            }
        }

        $pdo->exec('ALTER TABLE events ADD COLUMN is_active INTEGER NOT NULL DEFAULT 0');
    },
];
