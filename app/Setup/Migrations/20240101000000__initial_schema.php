<?php

return [
    'description' => 'Initiales Schema für Benutzer, Stammdaten und Turnierverwaltung',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idPrimary = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $datetime = $driver === 'mysql' ? 'DATETIME' : 'TEXT';
        $boolean = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';

        $queries = [
            <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id {$idPrimary},
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL,
    created_at {$datetime} NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS parties (
    id {$idPrimary},
    party_type VARCHAR(40) NOT NULL,
    display_name VARCHAR(190) NOT NULL,
    sort_name VARCHAR(190),
    email VARCHAR(190),
    phone VARCHAR(80),
    created_at {$datetime} NOT NULL,
    updated_at {$datetime}
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS party_roles (
    id {$idPrimary},
    party_id INTEGER NOT NULL,
    role VARCHAR(60) NOT NULL,
    context VARCHAR(60) NOT NULL DEFAULT 'system',
    assigned_at {$datetime} NOT NULL,
    updated_at {$datetime}
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS person_profiles (
    party_id INTEGER PRIMARY KEY,
    club_id INTEGER,
    preferred_locale VARCHAR(10),
    updated_at {$datetime}
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS organization_profiles (
    party_id INTEGER PRIMARY KEY,
    category VARCHAR(60) NOT NULL,
    short_name VARCHAR(40),
    metadata TEXT,
    updated_at {$datetime}
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS horses (
    id {$idPrimary},
    name VARCHAR(160) NOT NULL,
    owner_party_id INTEGER,
    documents_ok {$boolean} DEFAULT 0,
    notes TEXT
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS clubs (
    id {$idPrimary},
    party_id INTEGER,
    name VARCHAR(160) NOT NULL,
    short_name VARCHAR(20) NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS events (
    id {$idPrimary},
    title VARCHAR(190) NOT NULL,
    start_date DATE,
    end_date DATE,
    venues TEXT,
    is_active {$boolean} NOT NULL DEFAULT 0
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS classes (
    id {$idPrimary},
    event_id INTEGER NOT NULL,
    label VARCHAR(190) NOT NULL,
    arena VARCHAR(120),
    start_time {$datetime},
    end_time {$datetime},
    max_starters INTEGER,
    judge_assignments TEXT,
    rules_json TEXT,
    tiebreaker_json TEXT,
    is_group INTEGER NOT NULL DEFAULT 0
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS entries (
    id {$idPrimary},
    event_id INTEGER NOT NULL,
    class_id INTEGER NOT NULL,
    party_id INTEGER NOT NULL,
    horse_id INTEGER NOT NULL,
    status VARCHAR(40) NOT NULL,
    department VARCHAR(120),
    fee_paid_at {$datetime},
    created_at {$datetime} NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS startlist_items (
    id {$idPrimary},
    class_id INTEGER NOT NULL,
    entry_id INTEGER NOT NULL,
    position INTEGER NOT NULL,
    planned_start {$datetime},
    state VARCHAR(40) NOT NULL DEFAULT 'scheduled',
    note TEXT,
    created_at {$datetime} NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at {$datetime}
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS schedule_shifts (
    id {$idPrimary},
    class_id INTEGER NOT NULL,
    shift_minutes INTEGER NOT NULL DEFAULT 0,
    created_at {$datetime} NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS results (
    id {$idPrimary},
    startlist_id INTEGER NOT NULL,
    scores_json TEXT,
    total DECIMAL(10,2),
    penalties DECIMAL(10,2),
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    signed_by VARCHAR(160),
    signed_at {$datetime},
    signature_hash VARCHAR(255),
    created_at {$datetime} NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS helper_shifts (
    id {$idPrimary},
    role VARCHAR(120) NOT NULL,
    station VARCHAR(120),
    party_id INTEGER,
    start_time {$datetime},
    end_time {$datetime},
    token VARCHAR(120) UNIQUE,
    checked_in_at {$datetime},
    created_at {$datetime} NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS notifications (
    id {$idPrimary},
    type VARCHAR(120) NOT NULL,
    payload TEXT NOT NULL,
    created_at {$datetime} NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS audit_log (
    id {$idPrimary},
    entity VARCHAR(80) NOT NULL,
    entity_id INTEGER NOT NULL,
    action VARCHAR(60) NOT NULL,
    user_id INTEGER,
    before_state TEXT,
    after_state TEXT,
    created_at {$datetime} NOT NULL
)
SQL,
            'CREATE UNIQUE INDEX IF NOT EXISTS party_roles_unique ON party_roles (party_id, role, context)',
            'CREATE INDEX IF NOT EXISTS parties_type_name_idx ON parties (party_type, sort_name)',
        ];

        foreach ($queries as $query) {
            $pdo->exec($query);
        }
    },
];
