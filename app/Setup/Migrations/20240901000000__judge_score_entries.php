<?php

declare(strict_types=1);

return [
    'description' => 'Einzelwertungen je Richter speichern',
    'up' => static function (\PDO $pdo, string $driver): void {
        $idPrimary = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $varchar = $driver === 'mysql' ? 'VARCHAR(190)' : 'TEXT';
        $timestamp = $driver === 'mysql' ? 'DATETIME' : 'TEXT';

        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS judge_scores (
    id {$idPrimary},
    startlist_id INTEGER NOT NULL,
    judge_key {$varchar} NOT NULL,
    judge_user_id INTEGER,
    judge_name {$varchar},
    components_json TEXT NOT NULL,
    fields_json TEXT NOT NULL DEFAULT '[]',
    submitted_at {$timestamp} NOT NULL,
    created_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at {$timestamp} NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (startlist_id) REFERENCES startlist_items(id) ON DELETE CASCADE
)
SQL
        );

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS judge_scores_start_judge_unique ON judge_scores (startlist_id, judge_key)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS judge_scores_start_idx ON judge_scores (startlist_id)');

        $selectResults = $pdo->query('SELECT id, startlist_id, scores_json, created_at, updated_at FROM results WHERE scores_json IS NOT NULL AND scores_json != ""');
        if ($selectResults) {
            $checkStmt = $pdo->prepare('SELECT id FROM judge_scores WHERE startlist_id = :start_id AND judge_key = :judge_key LIMIT 1');
            $insertStmt = $pdo->prepare('INSERT INTO judge_scores (startlist_id, judge_key, judge_user_id, judge_name, components_json, fields_json, submitted_at, created_at, updated_at) VALUES (:start_id, :judge_key, :judge_user_id, :judge_name, :components, :fields, :submitted_at, :created_at, :updated_at)');
            while ($row = $selectResults->fetch(PDO::FETCH_ASSOC)) {
                if (empty($row['scores_json'])) {
                    continue;
                }
                try {
                    $decoded = json_decode((string) $row['scores_json'], true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    continue;
                }
                $judges = is_array($decoded['input']['judges'] ?? null) ? $decoded['input']['judges'] : [];
                foreach ($judges as $entry) {
                    if (!is_array($entry) || empty($entry['id'])) {
                        continue;
                    }
                    $components = is_array($entry['components'] ?? null) ? $entry['components'] : [];
                    if (!empty($entry['lessons']) && is_array($entry['lessons'])) {
                        foreach ($entry['lessons'] as $lessonId => $lessonValue) {
                            if (!array_key_exists($lessonId, $components)) {
                                $components[$lessonId] = $lessonValue;
                            }
                        }
                    }
                    $fields = is_array($entry['fields'] ?? null) ? $entry['fields'] : [];
                    $checkStmt->execute([
                        'start_id' => $row['startlist_id'],
                        'judge_key' => $entry['id'],
                    ]);
                    if ($checkStmt->fetchColumn()) {
                        $checkStmt->closeCursor();
                        continue;
                    }
                    $checkStmt->closeCursor();
                    $judgeUserId = $entry['user']['id'] ?? null;
                    if ($judgeUserId !== null && $judgeUserId !== '') {
                        $judgeUserId = (int) $judgeUserId;
                    } else {
                        $judgeUserId = null;
                    }
                    $judgeName = $entry['user']['name'] ?? null;
                    $submittedAt = $entry['submitted_at'] ?? $row['updated_at'] ?? $row['created_at'] ?? date('c');
                    $insertStmt->execute([
                        'start_id' => $row['startlist_id'],
                        'judge_key' => $entry['id'],
                        'judge_user_id' => $judgeUserId,
                        'judge_name' => $judgeName,
                        'components' => json_encode($components, JSON_THROW_ON_ERROR),
                        'fields' => json_encode($fields, JSON_THROW_ON_ERROR),
                        'submitted_at' => $submittedAt,
                        'created_at' => $row['created_at'] ?? $submittedAt,
                        'updated_at' => $submittedAt,
                    ]);
                }
            }
        }
    },
];
