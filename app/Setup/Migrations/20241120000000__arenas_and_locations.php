<?php

return [
    'description' => 'Locations, arenas and resource scheduling',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idColumn = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $intType = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
        $smallIntType = $driver === 'mysql' ? 'SMALLINT' : 'INTEGER';
        $stringType = $driver === 'mysql' ? 'VARCHAR(190)' : 'TEXT';
        $shortStringType = $driver === 'mysql' ? 'VARCHAR(120)' : 'TEXT';
        $textType = 'TEXT';
        $jsonType = 'TEXT';
        $boolType = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';
        $timestampType = $driver === 'mysql' ? 'DATETIME' : 'TEXT';
        $decimalType = $driver === 'mysql' ? 'DECIMAL(10,6)' : 'NUMERIC';

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS locations (
    id {$idColumn},
    name {$stringType} NOT NULL,
    address {$textType} NULL,
    geo_lat {$decimalType} NULL,
    geo_lng {$decimalType} NULL,
    notes {$textType} NULL,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL
)
SQL
        );

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS locations_name_idx ON locations (name)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS arenas (
    id {$idColumn},
    location_id {$intType} NULL,
    name {$stringType} NOT NULL,
    type {$shortStringType} NOT NULL DEFAULT 'outdoor',
    surface {$shortStringType} NULL,
    length_m {$smallIntType} NULL,
    width_m {$smallIntType} NULL,
    covered {$boolType} NOT NULL DEFAULT 0,
    lighting {$boolType} NOT NULL DEFAULT 0,
    drainage {$boolType} NOT NULL DEFAULT 0,
    capacity {$intType} NULL,
    notes {$textType} NULL,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
)
SQL
        );

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS arenas_name_location_idx ON arenas (name, location_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS arenas_location_idx ON arenas (location_id)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS arena_features (
    id {$idColumn},
    arena_id {$intType} NOT NULL,
    feature_key {$shortStringType} NOT NULL,
    feature_value {$textType} NULL,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL,
    FOREIGN KEY (arena_id) REFERENCES arenas(id) ON DELETE CASCADE
)
SQL
        );

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS arena_features_unique ON arena_features (arena_id, feature_key)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS arena_endpoints (
    id {$idColumn},
    arena_id {$intType} NOT NULL,
    kind {$shortStringType} NOT NULL,
    endpoint_url {$textType} NULL,
    config_json {$jsonType} NOT NULL DEFAULT '{}',
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL,
    FOREIGN KEY (arena_id) REFERENCES arenas(id) ON DELETE CASCADE
)
SQL
        );

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS arena_endpoints_unique ON arena_endpoints (arena_id, kind)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS resources (
    id {$idColumn},
    name {$stringType} NOT NULL,
    resource_type {$shortStringType} NOT NULL,
    specifications {$jsonType} NOT NULL DEFAULT '{}',
    storage_location {$stringType} NULL,
    notes {$textType} NULL,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL
)
SQL
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS resources_type_idx ON resources (resource_type)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS resource_bookings (
    id {$idColumn},
    resource_id {$intType} NOT NULL,
    arena_id {$intType} NULL,
    event_id {$intType} NULL,
    start_time {$timestampType} NOT NULL,
    end_time {$timestampType} NOT NULL,
    purpose {$shortStringType} NOT NULL DEFAULT 'class',
    buffer_before_minutes {$smallIntType} NOT NULL DEFAULT 0,
    buffer_after_minutes {$smallIntType} NOT NULL DEFAULT 0,
    notes {$textType} NULL,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
    FOREIGN KEY (arena_id) REFERENCES arenas(id) ON DELETE SET NULL
)
SQL
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS resource_bookings_resource_idx ON resource_bookings (resource_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS resource_bookings_arena_idx ON resource_bookings (arena_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS resource_bookings_event_idx ON resource_bookings (event_id)');
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS resource_bookings_unique ON resource_bookings (resource_id, start_time, end_time)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS arena_availability (
    id {$idColumn},
    arena_id {$intType} NOT NULL,
    start_time {$timestampType} NOT NULL,
    end_time {$timestampType} NOT NULL,
    reason {$shortStringType} NULL,
    status {$shortStringType} NOT NULL DEFAULT 'blocked',
    notes {$textType} NULL,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL,
    FOREIGN KEY (arena_id) REFERENCES arenas(id) ON DELETE CASCADE
)
SQL
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS arena_availability_arena_idx ON arena_availability (arena_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS arena_availability_period_idx ON arena_availability (start_time, end_time)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS event_arenas (
    id {$idColumn},
    event_id {$intType} NOT NULL,
    arena_id {$intType} NOT NULL,
    display_name {$stringType} NULL,
    remarks {$textType} NULL,
    temp_surface {$shortStringType} NULL,
    blocked_times {$jsonType} NOT NULL DEFAULT '[]',
    warmup_arena_id {$intType} NULL,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (arena_id) REFERENCES arenas(id) ON DELETE CASCADE,
    FOREIGN KEY (warmup_arena_id) REFERENCES event_arenas(id) ON DELETE SET NULL
)
SQL
        );

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS event_arenas_unique ON event_arenas (event_id, arena_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS event_arenas_event_idx ON event_arenas (event_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS event_arenas_arena_idx ON event_arenas (arena_id)');

        $classesColumnType = $driver === 'mysql' ? 'INT UNSIGNED NULL' : 'INTEGER NULL';
        $pdo->exec('ALTER TABLE classes ADD COLUMN event_arena_id ' . $classesColumnType);
        $pdo->exec('CREATE INDEX IF NOT EXISTS classes_event_arena_idx ON classes (event_arena_id)');

        if ($driver === 'mysql') {
            $pdo->exec('ALTER TABLE classes ADD CONSTRAINT classes_event_arena_fk FOREIGN KEY (event_arena_id) REFERENCES event_arenas(id) ON DELETE SET NULL');
        }
    },
];
