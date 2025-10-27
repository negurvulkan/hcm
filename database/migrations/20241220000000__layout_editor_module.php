<?php

declare(strict_types=1);

return [
    'description' => 'Layout-Editor Tabellen',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idColumn = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $textType = 'TEXT';
        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS layouts (
    id {$idColumn},
    slug VARCHAR(190) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    description {$textType} NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    metadata_json {$textType} NOT NULL DEFAULT '{}',
    structure_json {$textType} NOT NULL DEFAULT '{}',
    version INTEGER NOT NULL DEFAULT 1,
    current_version_id INTEGER NULL,
    published_version_id INTEGER NULL,
    published_at {$textType} NULL,
    archived_at {$textType} NULL,
    is_locked INTEGER NOT NULL DEFAULT 0,
    locked_by INTEGER NULL,
    locked_at {$textType} NULL,
    created_by INTEGER NULL,
    updated_by INTEGER NULL,
    created_at {$textType} NOT NULL,
    updated_at {$textType} NOT NULL
)
SQL
        );

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS layout_versions (
    id {$idColumn},
    layout_id INTEGER NOT NULL,
    version INTEGER NOT NULL,
    comment {$textType} NULL,
    metadata_json {$textType} NOT NULL DEFAULT '{}',
    structure_json {$textType} NOT NULL DEFAULT '{}',
    created_by INTEGER NULL,
    created_at {$textType} NOT NULL
)
SQL
        );
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS layout_versions_layout_version_idx ON layout_versions (layout_id, version)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS layout_snippets (
    id {$idColumn},
    layout_id INTEGER NULL,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(120) NULL,
    metadata_json {$textType} NOT NULL DEFAULT '{}',
    snippet_json {$textType} NOT NULL DEFAULT '{}',
    created_by INTEGER NULL,
    updated_by INTEGER NULL,
    created_at {$textType} NOT NULL,
    updated_at {$textType} NOT NULL
)
SQL
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS layout_snippets_layout_idx ON layout_snippets (layout_id, category)');

        $setTimestamps = static function (string $table) use ($pdo, $now): void {
            $stmt = $pdo->query('SELECT COUNT(*) FROM ' . $table);
            if ($stmt && (int) $stmt->fetchColumn() > 0) {
                $pdo->exec('UPDATE ' . $table . " SET created_at = COALESCE(created_at, '{$now}'), updated_at = COALESCE(updated_at, '{$now}')");
            }
        };

        $setTimestamps('layouts');
        $setTimestamps('layout_snippets');
    },
];
