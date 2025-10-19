<?php

declare(strict_types=1);

use PDOException;

return [
    'description' => 'Erweiterte Navigationseinträge',
    'up' => static function (\PDO $pdo, string $driver): void {
        $addColumn = static function (string $sql) use ($pdo): void {
            try {
                $pdo->exec($sql);
            } catch (PDOException $exception) {
                $message = strtolower($exception->getMessage());
                if (strpos($message, 'duplicate column') !== false || strpos($message, 'already exists') !== false) {
                    return;
                }

                throw $exception;
            }
        };

        if ($driver === 'mysql') {
            $addColumn('ALTER TABLE navigation_items ADD COLUMN is_custom TINYINT(1) NOT NULL DEFAULT 0');
            $addColumn('ALTER TABLE navigation_items ADD COLUMN label_i18n TEXT NULL');
        } else {
            $addColumn('ALTER TABLE navigation_items ADD COLUMN is_custom INTEGER NOT NULL DEFAULT 0');
            $addColumn('ALTER TABLE navigation_items ADD COLUMN label_i18n TEXT NULL');
        }
    },
];
