<?php

declare(strict_types=1);

use App\Core\Rbac;
use App\Navigation\NavigationRepository;

return [
    'description' => 'Konfigurierbare Navigation je Rolle',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idPrimary = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $varchar = $driver === 'mysql' ? 'VARCHAR(190)' : 'TEXT';
        $timestamp = $driver === 'mysql' ? 'DATETIME' : 'TEXT';

        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS navigation_groups (
    id {$idPrimary},
    role VARCHAR(60) NOT NULL,
    label_key {$varchar},
    label_i18n TEXT,
    position INTEGER NOT NULL DEFAULT 0,
    created_at {$timestamp} NOT NULL,
    updated_at {$timestamp} NOT NULL
)
SQL
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS navigation_groups_role_idx ON navigation_groups (role)');

        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS navigation_items (
    id {$idPrimary},
    role VARCHAR(60) NOT NULL,
    item_key VARCHAR(120) NOT NULL,
    target {$varchar} NOT NULL,
    group_id INTEGER,
    variant VARCHAR(20) NOT NULL DEFAULT "primary",
    position INTEGER NOT NULL DEFAULT 0,
    created_at {$timestamp} NOT NULL,
    updated_at {$timestamp} NOT NULL,
    FOREIGN KEY (group_id) REFERENCES navigation_groups(id) ON DELETE CASCADE
)
SQL
        );

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS navigation_items_role_key_unique ON navigation_items (role, item_key)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS navigation_items_role_idx ON navigation_items (role)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS navigation_items_group_idx ON navigation_items (group_id)');

        $repository = new NavigationRepository($pdo);
        foreach (Rbac::ROLES as $role) {
            $layout = Rbac::defaultLayoutForRole($role);
            if (empty($layout['groups']) || empty($layout['items'])) {
                continue;
            }
            $repository->replaceLayout($role, $layout['groups'], $layout['items']);
        }
    },
];
