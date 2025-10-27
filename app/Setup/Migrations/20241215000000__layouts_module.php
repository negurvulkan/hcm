<?php

return [
    'description' => 'Layout-Bibliothek',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idColumn = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $textType = 'TEXT';

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS layouts (
    id {$idColumn},
    uuid VARCHAR(36) NOT NULL UNIQUE,
    slug VARCHAR(190) NOT NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    description {$textType} NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'general',
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    version INTEGER NOT NULL DEFAULT 1,
    data_json {$textType} NOT NULL DEFAULT '{}',
    meta_json {$textType} NOT NULL DEFAULT '{}',
    assets_json {$textType} NOT NULL DEFAULT '[]',
    owner_id INTEGER NULL,
    created_by INTEGER NULL,
    updated_by INTEGER NULL,
    approved_by INTEGER NULL,
    created_at {$textType} NOT NULL,
    updated_at {$textType} NOT NULL,
    approved_at {$textType} NULL,
    published_at {$textType} NULL
)
SQL
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS layouts_category_idx ON layouts (category)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS layouts_status_idx ON layouts (status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS layouts_owner_idx ON layouts (owner_id)');

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS layout_versions (
    id {$idColumn},
    layout_id INTEGER NOT NULL,
    version INTEGER NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    comment {$textType} NULL,
    data_json {$textType} NOT NULL,
    meta_json {$textType} NOT NULL DEFAULT '{}',
    assets_json {$textType} NOT NULL DEFAULT '[]',
    created_by INTEGER NULL,
    created_at {$textType} NOT NULL,
    approved_by INTEGER NULL,
    approved_at {$textType} NULL,
    FOREIGN KEY (layout_id) REFERENCES layouts(id) ON DELETE CASCADE
)
SQL
        );

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS layout_versions_unique ON layout_versions (layout_id, version)');
    },
];
