<?php

return [
    'description' => 'Sponsoren- und Partnerverzeichnis',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idColumn = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $textType = 'TEXT';
        $decimalType = $driver === 'mysql' ? 'DECIMAL(10,2)' : 'NUMERIC';
        $booleanType = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';
        $dateType = 'DATE';
        $timestampType = $driver === 'mysql' ? 'DATETIME' : 'TEXT';

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS sponsors (
    id {$idColumn},
    name VARCHAR(190) NOT NULL,
    display_name VARCHAR(190) NULL,
    type VARCHAR(60) NOT NULL DEFAULT 'company',
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    contact_person VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(90) NOT NULL,
    address {$textType} NOT NULL,
    tier VARCHAR(40) NOT NULL DEFAULT 'partner',
    value {$decimalType} NULL,
    value_type VARCHAR(40) NOT NULL DEFAULT 'cash',
    contract_start {$dateType} NULL,
    contract_end {$dateType} NULL,
    invoice_required {$booleanType} NOT NULL DEFAULT 0,
    invoice_number VARCHAR(120) NULL,
    logo_path VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    description_short {$textType} NULL,
    description_long {$textType} NULL,
    priority INTEGER NOT NULL DEFAULT 0,
    color_primary VARCHAR(7) NULL,
    tagline VARCHAR(255) NULL,
    show_on_website {$booleanType} NOT NULL DEFAULT 1,
    show_on_signage {$booleanType} NOT NULL DEFAULT 1,
    show_in_program {$booleanType} NOT NULL DEFAULT 1,
    overlay_template VARCHAR(120) NULL,
    display_duration INTEGER NULL,
    display_frequency INTEGER NULL,
    linked_event_id INTEGER NULL,
    contract_file VARCHAR(255) NULL,
    logo_variants {$textType} NOT NULL DEFAULT '[]',
    media_package {$textType} NOT NULL DEFAULT '[]',
    notes_internal {$textType} NULL,
    documents {$textType} NOT NULL DEFAULT '[]',
    sponsorship_history {$textType} NOT NULL DEFAULT '[]',
    display_stats {$textType} NOT NULL DEFAULT '{}',
    last_contacted {$dateType} NULL,
    follow_up_date {$dateType} NULL,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL
)
SQL
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS sponsors_status_idx ON sponsors (status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS sponsors_priority_idx ON sponsors (priority)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS sponsors_signage_idx ON sponsors (show_on_signage, status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS sponsors_event_idx ON sponsors (linked_event_id)');
    },
];
