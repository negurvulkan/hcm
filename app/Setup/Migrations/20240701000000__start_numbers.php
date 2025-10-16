<?php

return [
    'description' => 'Startnummernkonfiguration und Vergabe',
    'up' => static function (\PDO $pdo, string $driver): void {
        $datetime = $driver === 'mysql' ? 'DATETIME' : 'TEXT';
        $string = $driver === 'mysql' ? 'VARCHAR(190)' : 'TEXT';
        $pdo->exec('ALTER TABLE events ADD COLUMN start_number_rules TEXT');
        $pdo->exec('ALTER TABLE entries ADD COLUMN start_number_assignment_id INTEGER');
        $pdo->exec('ALTER TABLE entries ADD COLUMN start_number_raw INTEGER');
        $pdo->exec('ALTER TABLE entries ADD COLUMN start_number_display VARCHAR(120)');
        $pdo->exec('ALTER TABLE entries ADD COLUMN start_number_rule_snapshot TEXT');
        $pdo->exec('ALTER TABLE entries ADD COLUMN start_number_allocation_entity VARCHAR(40)');
        $pdo->exec('ALTER TABLE entries ADD COLUMN start_number_locked_at ' . $datetime);
        $pdo->exec('ALTER TABLE startlist_items ADD COLUMN start_number_assignment_id INTEGER');
        $pdo->exec('ALTER TABLE startlist_items ADD COLUMN start_number_raw INTEGER');
        $pdo->exec('ALTER TABLE startlist_items ADD COLUMN start_number_display VARCHAR(120)');
        $pdo->exec('ALTER TABLE startlist_items ADD COLUMN start_number_rule_snapshot TEXT');
        $pdo->exec('ALTER TABLE startlist_items ADD COLUMN start_number_allocation_entity VARCHAR(40)');
        $pdo->exec('ALTER TABLE startlist_items ADD COLUMN start_number_locked_at ' . $datetime);
        $pdo->exec('CREATE TABLE IF NOT EXISTS start_number_assignments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id INTEGER NOT NULL,
            class_id INTEGER,
            arena ' . $string . ',
            day ' . $string . ',
            scope_key ' . $string . ' NOT NULL,
            rule_scope VARCHAR(40) NOT NULL,
            rule_snapshot TEXT NOT NULL,
            allocation_entity VARCHAR(40) NOT NULL,
            allocation_time VARCHAR(40) NOT NULL,
            subject_type VARCHAR(40) NOT NULL,
            subject_key ' . $string . ' NOT NULL,
            subject_payload TEXT,
            rider_id INTEGER,
            horse_id INTEGER,
            club_id INTEGER,
            start_number_raw INTEGER NOT NULL,
            start_number_display VARCHAR(120) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT ' . $pdo->quote('active') . ',
            locked_at ' . $datetime . ',
            released_at ' . $datetime . ',
            release_reason VARCHAR(120),
            created_by VARCHAR(120),
            created_at ' . $datetime . ' NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_start_number_assignments_event ON start_number_assignments (event_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_start_number_assignments_scope ON start_number_assignments (scope_key)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_start_number_assignments_subject ON start_number_assignments (subject_key)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_start_number_assignments_horse ON start_number_assignments (horse_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_start_number_assignments_rider ON start_number_assignments (rider_id)');
    },
];
