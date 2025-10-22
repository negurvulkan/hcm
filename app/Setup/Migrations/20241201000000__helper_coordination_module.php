<?php

return [
    'description' => 'Helper coordination core tables',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idColumn = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $booleanType = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';
        $timestampType = $driver === 'mysql' ? 'DATETIME' : 'TEXT';
        $textType = 'TEXT';
        $varchar190 = $driver === 'mysql' ? 'VARCHAR(190)' : 'TEXT';
        $varchar120 = $driver === 'mysql' ? 'VARCHAR(120)' : 'TEXT';
        $varchar60 = $driver === 'mysql' ? 'VARCHAR(60)' : 'TEXT';
        $intType = $driver === 'mysql' ? 'INT' : 'INTEGER';

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS stations (
    id {$idColumn},
    event_id {$intType} NULL,
    name {$varchar190} NOT NULL,
    description {$textType} NULL,
    location_json {$textType} NOT NULL DEFAULT '{}',
    responsible_person_id {$intType} NULL,
    equipment_json {$textType} NOT NULL DEFAULT '[]',
    active {$booleanType} NOT NULL DEFAULT 1,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL
)
SQL
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS stations_event_idx ON stations (event_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS stations_active_idx ON stations (active)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS helper_roles (
    id {$idColumn},
    role_key {$varchar60} NOT NULL UNIQUE,
    name {$varchar190} NOT NULL,
    color {$varchar60} NULL,
    active {$booleanType} NOT NULL DEFAULT 1,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL
)
SQL
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS helper_roles_active_idx ON helper_roles (active)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS shifts (
    id {$idColumn},
    event_id {$intType} NULL,
    role_id {$intType} NOT NULL,
    station_id {$intType} NULL,
    person_id {$intType} NULL,
    starts_at {$timestampType} NULL,
    ends_at {$timestampType} NULL,
    status {$varchar60} NOT NULL DEFAULT 'open',
    token_id {$intType} NULL,
    notes {$textType} NULL,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL
)
SQL
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS shifts_event_idx ON shifts (event_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS shifts_role_idx ON shifts (role_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS shifts_station_idx ON shifts (station_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS shifts_person_idx ON shifts (person_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS shifts_status_idx ON shifts (status)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS tokens (
    id {$idColumn},
    token {$varchar120} NOT NULL UNIQUE,
    station_id {$intType} NULL,
    shift_id {$intType} NULL,
    purpose {$varchar60} NOT NULL,
    expires_at {$timestampType} NULL,
    used_at {$timestampType} NULL,
    nonce {$varchar120} NOT NULL,
    created_by {$intType} NULL,
    created_at {$timestampType} NOT NULL
)
SQL
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS tokens_station_idx ON tokens (station_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS tokens_shift_idx ON tokens (shift_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS tokens_purpose_idx ON tokens (purpose)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS tokens_expires_idx ON tokens (expires_at)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS checkins (
    id {$idColumn},
    person_id {$intType} NULL,
    station_id {$intType} NULL,
    shift_id {$intType} NULL,
    type {$varchar60} NOT NULL,
    ts {$timestampType} NOT NULL,
    source {$varchar60} NOT NULL,
    token_id {$intType} NULL,
    geo_json {$textType} NULL,
    created_at {$timestampType} NOT NULL
)
SQL
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS checkins_person_idx ON checkins (person_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS checkins_station_idx ON checkins (station_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS checkins_shift_idx ON checkins (shift_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS checkins_type_idx ON checkins (type)');
    },
];
