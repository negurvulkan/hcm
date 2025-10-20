<?php

declare(strict_types=1);

return [
    'description' => 'Abteilungen pro Prüfung und Zuordnung der Nennungen',
    'up' => static function (\PDO $pdo, string $driver): void {
        $tableExists = static function (\PDO $pdo, string $driver, string $table): bool {
            if ($driver === 'mysql') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
                $stmt->execute(['table' => $table]);
                return (bool) $stmt->fetchColumn();
            }

            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = '" . $table . "'");
            if (!$stmt) {
                return false;
            }

            return (bool) $stmt->fetchColumn();
        };

        $columnExists = static function (\PDO $pdo, string $driver, string $table, string $column): bool {
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

        $idPrimary = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $string = $driver === 'mysql' ? 'VARCHAR(160)' : 'TEXT';
        $labelType = $driver === 'mysql' ? 'VARCHAR(120)' : 'TEXT';
        $datetime = $driver === 'mysql' ? 'DATETIME' : 'TEXT';

        if (!$tableExists($pdo, $driver, 'class_departments')) {
            $pdo->exec('CREATE TABLE class_departments (
                id ' . $idPrimary . ',
                class_id INTEGER NOT NULL,
                label ' . $labelType . ' NOT NULL,
                normalized_label ' . $string . ' NOT NULL,
                position INTEGER NOT NULL,
                created_at ' . $datetime . ' NOT NULL,
                updated_at ' . $datetime . ' NOT NULL
            )');
            $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_class_departments_unique ON class_departments (class_id, normalized_label)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_class_departments_class ON class_departments (class_id)');
        }

        if (!$columnExists($pdo, $driver, 'entries', 'department_id')) {
            $pdo->exec('ALTER TABLE entries ADD COLUMN department_id INTEGER');
        }

        $sanitize = static function (string $value): string {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return '';
            }
            $collapsed = preg_replace('/\s+/', ' ', $trimmed);
            if (function_exists('mb_substr')) {
                return mb_substr($collapsed, 0, 120);
            }
            return substr($collapsed, 0, 120);
        };

        $normalize = static function (string $value): string {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return '';
            }
            $collapsed = preg_replace('/\s+/', ' ', $trimmed);
            return function_exists('mb_strtolower') ? mb_strtolower($collapsed, 'UTF-8') : strtolower($collapsed);
        };

        $departmentsByClass = [];
        $positionsByClass = [];

        $existingDepartmentsStmt = $pdo->query('SELECT id, class_id, label, normalized_label, position FROM class_departments');
        if ($existingDepartmentsStmt) {
            while ($row = $existingDepartmentsStmt->fetch(\PDO::FETCH_ASSOC)) {
                $classId = (int) ($row['class_id'] ?? 0);
                if ($classId <= 0) {
                    continue;
                }
                $normalized = (string) ($row['normalized_label'] ?? '');
                $departmentsByClass[$classId][$normalized] = [
                    'id' => (int) $row['id'],
                    'label' => (string) $row['label'],
                    'position' => (int) $row['position'],
                ];
                $positionsByClass[$classId] = max($positionsByClass[$classId] ?? 0, (int) $row['position']);
            }
        }

        $entryStmt = $pdo->query('SELECT id, class_id, department FROM entries WHERE department IS NOT NULL AND TRIM(department) != ""');
        if ($entryStmt) {
            $insertDept = $pdo->prepare('INSERT INTO class_departments (class_id, label, normalized_label, position, created_at, updated_at) VALUES (:class_id, :label, :normalized, :position, :created, :updated)');
            $updateEntry = $pdo->prepare('UPDATE entries SET department = :label, department_id = :department_id WHERE id = :id');
            while ($row = $entryStmt->fetch(\PDO::FETCH_ASSOC)) {
                $entryId = (int) ($row['id'] ?? 0);
                $classId = (int) ($row['class_id'] ?? 0);
                $label = $sanitize((string) ($row['department'] ?? ''));
                if ($entryId <= 0 || $classId <= 0 || $label === '') {
                    continue;
                }
                $normalized = $normalize($label);
                if ($normalized === '') {
                    continue;
                }
                if (!isset($departmentsByClass[$classId][$normalized])) {
                    $positionsByClass[$classId] = ($positionsByClass[$classId] ?? 0) + 1;
                    $now = (new \DateTimeImmutable())->format('c');
                    $insertDept->execute([
                        'class_id' => $classId,
                        'label' => $label,
                        'normalized' => $normalized,
                        'position' => $positionsByClass[$classId],
                        'created' => $now,
                        'updated' => $now,
                    ]);
                    $deptId = (int) $pdo->lastInsertId();
                    $departmentsByClass[$classId][$normalized] = [
                        'id' => $deptId,
                        'label' => $label,
                        'position' => $positionsByClass[$classId],
                    ];
                } else {
                    $deptId = (int) $departmentsByClass[$classId][$normalized]['id'];
                    $label = (string) $departmentsByClass[$classId][$normalized]['label'];
                }
                $updateEntry->execute([
                    'label' => $label,
                    'department_id' => $departmentsByClass[$classId][$normalized]['id'],
                    'id' => $entryId,
                ]);
            }
        }
    },
];
