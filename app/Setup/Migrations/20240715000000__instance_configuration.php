<?php

return [
    'description' => 'Systemweite Einstellungen für Instanz- und Betriebsmodus',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idType = $driver === 'mysql' ? 'VARCHAR(180)' : 'TEXT';
        $timestamp = $driver === 'mysql' ? 'DATETIME' : 'TEXT';

        $pdo->exec(
            sprintf(
                'CREATE TABLE IF NOT EXISTS system_settings (setting_key %s PRIMARY KEY, value TEXT NULL, updated_at %s NOT NULL)',
                $idType,
                $timestamp
            )
        );
    },
];

