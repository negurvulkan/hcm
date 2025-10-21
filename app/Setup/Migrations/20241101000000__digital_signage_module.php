<?php

return [
    'description' => 'Digital Signage Grundtabellen',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idColumn = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $textType = 'TEXT';
        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS signage_layouts (
    id {$idColumn},
    event_id INTEGER NULL,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    description {$textType} NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    theme_json {$textType} NULL,
    canvas_width INTEGER NOT NULL DEFAULT 1920,
    canvas_height INTEGER NOT NULL DEFAULT 1080,
    layers_json {$textType} NOT NULL DEFAULT '[]',
    timeline_json {$textType} NOT NULL DEFAULT '[]',
    data_sources_json {$textType} NOT NULL DEFAULT '[]',
    options_json {$textType} NOT NULL DEFAULT '{}',
    version INTEGER NOT NULL DEFAULT 1,
    current_revision_id INTEGER NULL,
    published_at {$textType} NULL,
    created_by INTEGER NULL,
    updated_by INTEGER NULL,
    created_at {$textType} NOT NULL,
    updated_at {$textType} NOT NULL
)
SQL
        );

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS signage_layout_revisions (
    id {$idColumn},
    layout_id INTEGER NOT NULL,
    version INTEGER NOT NULL,
    comment {$textType} NULL,
    layers_json {$textType} NOT NULL,
    timeline_json {$textType} NOT NULL,
    data_sources_json {$textType} NOT NULL,
    options_json {$textType} NOT NULL,
    theme_json {$textType} NULL,
    created_by INTEGER NULL,
    created_at {$textType} NOT NULL
)
SQL
        );
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS signage_layout_revisions_layout_version_idx ON signage_layout_revisions (layout_id, version)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS signage_displays (
    id {$idColumn},
    name VARCHAR(180) NOT NULL,
    display_group VARCHAR(120) NOT NULL DEFAULT 'default',
    location VARCHAR(180) NULL,
    description {$textType} NULL,
    access_token VARCHAR(190) NOT NULL UNIQUE,
    assigned_layout_id INTEGER NULL,
    assigned_playlist_id INTEGER NULL,
    last_seen_at {$textType} NULL,
    heartbeat_interval INTEGER NOT NULL DEFAULT 60,
    hardware_info {$textType} NULL,
    settings_json {$textType} NOT NULL DEFAULT '{}',
    created_at {$textType} NOT NULL,
    updated_at {$textType} NOT NULL
)
SQL
        );

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS signage_playlists (
    id {$idColumn},
    layout_id INTEGER NULL,
    title VARCHAR(180) NOT NULL,
    display_group VARCHAR(120) NOT NULL,
    starts_at {$textType} NULL,
    ends_at {$textType} NULL,
    rotation_seconds INTEGER NOT NULL DEFAULT 30,
    priority INTEGER NOT NULL DEFAULT 0,
    is_enabled INTEGER NOT NULL DEFAULT 1,
    created_at {$textType} NOT NULL,
    updated_at {$textType} NOT NULL
)
SQL
        );

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS signage_playlist_items (
    id {$idColumn},
    playlist_id INTEGER NOT NULL,
    layout_id INTEGER NOT NULL,
    label VARCHAR(180) NULL,
    duration_seconds INTEGER NOT NULL DEFAULT 30,
    position INTEGER NOT NULL DEFAULT 0,
    options_json {$textType} NOT NULL DEFAULT '{}'
)
SQL
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS signage_playlist_items_playlist_idx ON signage_playlist_items (playlist_id, position)');

        $updateTimestamp = static function (string $table) use ($pdo, $now): void {
            $stmt = $pdo->query("SELECT COUNT(*) FROM " . $table);
            if ($stmt && (int) $stmt->fetchColumn() > 0) {
                $pdo->exec("UPDATE " . $table . " SET created_at = COALESCE(created_at, '{$now}'), updated_at = COALESCE(updated_at, '{$now}')");
            }
        };
        $updateTimestamp('signage_layouts');
        $updateTimestamp('signage_displays');
        $updateTimestamp('signage_playlists');
    },
];
