<?php

return [
    'description' => 'Grundlegende Tabellen für Sync-Cursor, Logs und Transaktionen',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idPrimary = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $text = $driver === 'mysql' ? 'TEXT' : 'TEXT';
        $varchar = $driver === 'mysql' ? 'VARCHAR(190)' : 'TEXT';
        $timestamp = $driver === 'mysql' ? 'DATETIME' : 'TEXT';
        $bigint = $driver === 'mysql' ? 'BIGINT' : 'INTEGER';

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS sync_state (
    id {$idPrimary},
    scope {$varchar} NOT NULL,
    entity_id {$varchar} NOT NULL,
    version {$varchar} NOT NULL,
    version_epoch {$bigint} NOT NULL,
    checksum {$varchar} NULL,
    payload_meta {$text} NULL,
    updated_at {$timestamp} NOT NULL,
    UNIQUE(scope, entity_id)
)
SQL
        );

        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS sync_logs (
    id {$idPrimary},
    direction {$varchar} NOT NULL,
    operation {$varchar} NOT NULL,
    scopes {$text} NULL,
    counts {$text} NULL,
    status {$varchar} NOT NULL,
    message {$text} NULL,
    duration_ms {$bigint} NULL,
    actor {$varchar} NULL,
    transaction_id {$varchar} NULL,
    created_at {$timestamp} NOT NULL
)
SQL
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_sync_logs_created_at ON sync_logs (created_at)');

        $transactionIdType = $driver === 'mysql' ? 'VARCHAR(64)' : 'TEXT';
        $pdo->exec(
            <<<SQL
CREATE TABLE IF NOT EXISTS sync_transactions (
    id {$transactionIdType} PRIMARY KEY,
    direction {$varchar} NOT NULL,
    operation {$varchar} NOT NULL,
    scopes {$text} NULL,
    status {$varchar} NOT NULL,
    summary {$text} NULL,
    created_at {$timestamp} NOT NULL,
    acknowledged_at {$timestamp} NULL
)
SQL
        );

        $columns = [
            ['table' => 'parties', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'party_roles', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'person_profiles', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'organization_profiles', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'horses', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'clubs', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'events', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'classes', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'entries', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'schedule_shifts', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'helper_shifts', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
            ['table' => 'results', 'column' => 'updated_at', 'definition' => $timestamp . ' NULL'],
        ];

        $syncColumnExists = static function (\PDO $pdo, string $driver, string $table, string $column): bool {
            if ($driver === 'mysql') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
                $stmt->execute(['table' => $table, 'column' => $column]);
                return (bool) $stmt->fetchColumn();
            }

            $stmt = $pdo->query("PRAGMA table_info('" . $table . "')");
            if (!$stmt) {
                return false;
            }

            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $info) {
                if (strcasecmp((string) $info['name'], $column) === 0) {
                    return true;
                }
            }

            return false;
        };

        foreach ($columns as $column) {
            if (!$syncColumnExists($pdo, $driver, $column['table'], $column['column'])) {
                $pdo->exec(sprintf('ALTER TABLE %s ADD COLUMN %s %s', $column['table'], $column['column'], $column['definition']));
            }
        }
    },
];
