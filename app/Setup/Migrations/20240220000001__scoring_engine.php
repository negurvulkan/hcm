<?php

use PDO;

return [
    'description' => 'Add scoring engine columns',
    'up' => function (PDO $pdo, string $driver): void {
        $addColumn = function (string $table, string $column, string $definition) use ($pdo, $driver): void {
            if (scoring_column_exists($pdo, $driver, $table, $column)) {
                return;
            }
            $pdo->exec(sprintf('ALTER TABLE %s ADD COLUMN %s %s', $table, $column, $definition));
        };

        $addColumn('events', 'scoring_rule_json', 'TEXT');
        $addColumn('classes', 'scoring_rule_snapshot', 'TEXT');
        $addColumn('results', 'breakdown_json', 'TEXT');
        $addColumn('results', 'rule_snapshot', 'TEXT');
        $addColumn('results', 'engine_version', 'VARCHAR(40)');
        $addColumn('results', 'tiebreak_path', 'TEXT');
        $addColumn('results', 'rank', 'INTEGER');
        $addColumn('results', 'eliminated', 'INTEGER DEFAULT 0');
    },
];

function scoring_column_exists(PDO $pdo, string $driver, string $table, string $column): bool
{
    if ($driver === 'mysql') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (bool) $stmt->fetchColumn();
    }
    $stmt = $pdo->query("PRAGMA table_info('" . $table . "')");
    if (!$stmt) {
        return false;
    }
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $info) {
        if (strcasecmp($info['name'], $column) === 0) {
            return true;
        }
    }
    return false;
}
