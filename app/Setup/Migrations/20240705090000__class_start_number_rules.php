<?php

return [
    'description' => 'Startnummernregeln auf Klassenebene',
    'up' => static function (\PDO $pdo, string $driver): void {
        $pdo->exec('ALTER TABLE classes ADD COLUMN start_number_rules TEXT');
    },
];
