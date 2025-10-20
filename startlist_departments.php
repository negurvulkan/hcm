<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/audit.php';
require_once __DIR__ . '/app/helpers/startlist.php';

header('Content-Type: application/json');

$user = auth_require('startlist');

$respond = static function (int $status, array $payload): void {
    http_response_code($status);
    try {
        echo json_encode($payload, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        http_response_code(500);
        echo '{"success":false,"message":"Encoding failed"}';
    }
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(405, ['success' => false, 'message' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
$input = [];
if ($raw !== false && $raw !== '') {
    try {
        $input = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        $respond(400, ['success' => false, 'message' => 'Invalid JSON payload']);
    }
}
if (!is_array($input)) {
    $respond(400, ['success' => false, 'message' => 'Invalid request']);
}

if (!Csrf::check($input['_token'] ?? null)) {
    $respond(422, ['success' => false, 'message' => t('startlist.validation.csrf_invalid')]);
}

require_write_access('startlist', ['json' => true]);

$classId = (int) ($input['class_id'] ?? 0);
if ($classId <= 0) {
    $respond(422, ['success' => false, 'message' => t('startlist.departments.errors.class_required')]);
}

$class = db_first('SELECT c.*, e.id AS event_id FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]);
if (!$class || !event_accessible($user, (int) $class['event_id'])) {
    $respond(403, ['success' => false, 'message' => t('startlist.departments.errors.forbidden')]);
}

$action = (string) ($input['action'] ?? '');
$pdo = app_pdo();

switch ($action) {
    case 'create':
        $labelRaw = (string) ($input['label'] ?? '');
        $label = startlist_sanitize_department_label($labelRaw);
        if ($label === '') {
            $respond(422, ['success' => false, 'message' => t('startlist.departments.errors.label_required')]);
        }
        $normalized = startlist_normalize_department($label);
        $existing = db_first('SELECT * FROM class_departments WHERE class_id = :class_id AND normalized_label = :normalized', [
            'class_id' => $classId,
            'normalized' => $normalized,
        ]);
        if ($existing) {
            $respond(409, ['success' => false, 'message' => t('startlist.departments.errors.duplicate')]);
        }
        $now = (new \DateTimeImmutable())->format('c');
        $positionRow = db_first('SELECT MAX(position) AS max_position FROM class_departments WHERE class_id = :class_id', [
            'class_id' => $classId,
        ]);
        $position = (int) ($positionRow['max_position'] ?? 0) + 1;
        db_execute('INSERT INTO class_departments (class_id, label, normalized_label, position, created_at, updated_at) VALUES (:class_id, :label, :normalized, :position, :created, :updated)', [
            'class_id' => $classId,
            'label' => $label,
            'normalized' => $normalized,
            'position' => $position,
            'created' => $now,
            'updated' => $now,
        ]);
        $departmentId = (int) $pdo->lastInsertId();
        $department = db_first('SELECT * FROM class_departments WHERE id = :id', ['id' => $departmentId]);
        if ($department) {
            audit_log('class_departments', $departmentId, 'create', null, $department);
        }
        $respond(201, [
            'success' => true,
            'department' => $department,
        ]);
        break;
    case 'rename':
        $departmentId = (int) ($input['department_id'] ?? 0);
        $labelRaw = (string) ($input['label'] ?? '');
        $label = startlist_sanitize_department_label($labelRaw);
        if ($departmentId <= 0 || $label === '') {
            $respond(422, ['success' => false, 'message' => t('startlist.departments.errors.label_required')]);
        }
        $department = db_first('SELECT * FROM class_departments WHERE id = :id', ['id' => $departmentId]);
        if (!$department || (int) $department['class_id'] !== $classId) {
            $respond(404, ['success' => false, 'message' => t('startlist.departments.errors.not_found')]);
        }
        $normalized = startlist_normalize_department($label);
        $duplicate = db_first('SELECT id FROM class_departments WHERE class_id = :class_id AND normalized_label = :normalized AND id <> :id', [
            'class_id' => $classId,
            'normalized' => $normalized,
            'id' => $departmentId,
        ]);
        if ($duplicate) {
            $respond(409, ['success' => false, 'message' => t('startlist.departments.errors.duplicate')]);
        }
        $before = $department;
        $timestamp = (new \DateTimeImmutable())->format('c');
        db_execute('UPDATE class_departments SET label = :label, normalized_label = :normalized, updated_at = :updated WHERE id = :id', [
            'label' => $label,
            'normalized' => $normalized,
            'updated' => $timestamp,
            'id' => $departmentId,
        ]);
        db_execute('UPDATE entries SET department = :label WHERE department_id = :id', [
            'label' => $label,
            'id' => $departmentId,
        ]);
        $after = db_first('SELECT * FROM class_departments WHERE id = :id', ['id' => $departmentId]);
        audit_log('class_departments', $departmentId, 'update', $before, $after);
        $oldKey = 'department:' . $classId . ':' . ($before['normalized_label'] ?? startlist_normalize_department($before['label'] ?? ''));
        $newKey = 'department:' . $classId . ':' . $normalized;
        $assignments = db_all('SELECT id, subject_payload FROM start_number_assignments WHERE subject_type = "department" AND subject_key = :key', ['key' => $oldKey]);
        foreach ($assignments as $assignment) {
            $payload = [];
            $payloadRaw = $assignment['subject_payload'] ?? null;
            if (is_string($payloadRaw) && $payloadRaw !== '') {
                try {
                    $decoded = json_decode($payloadRaw, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $payload = $decoded;
                    }
                } catch (\JsonException $exception) {
                    $payload = [];
                }
            }
            $payload['department'] = $normalized;
            $payload['department_label'] = $label;
            try {
                $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                $payloadJson = json_encode(['department' => $normalized, 'department_label' => $label]);
            }
            db_execute('UPDATE start_number_assignments SET subject_payload = :payload, subject_key = :key WHERE id = :id', [
                'payload' => $payloadJson,
                'key' => $newKey,
                'id' => (int) $assignment['id'],
            ]);
        }
        $respond(200, [
            'success' => true,
            'department' => $after,
        ]);
        break;
    case 'delete':
        $departmentId = (int) ($input['department_id'] ?? 0);
        if ($departmentId <= 0) {
            $respond(422, ['success' => false, 'message' => t('startlist.departments.errors.not_found')]);
        }
        $department = db_first('SELECT * FROM class_departments WHERE id = :id', ['id' => $departmentId]);
        if (!$department || (int) $department['class_id'] !== $classId) {
            $respond(404, ['success' => false, 'message' => t('startlist.departments.errors.not_found')]);
        }
        $assigned = db_first('SELECT COUNT(*) AS cnt FROM entries WHERE department_id = :id', ['id' => $departmentId]);
        if (($assigned['cnt'] ?? 0) > 0) {
            $respond(409, ['success' => false, 'message' => t('startlist.departments.errors.in_use')]);
        }
        $subjectKey = 'department:' . $classId . ':' . ($department['normalized_label'] ?? startlist_normalize_department($department['label'] ?? ''));
        $assignmentCount = db_first('SELECT COUNT(*) AS cnt FROM start_number_assignments WHERE subject_type = "department" AND subject_key = :key AND status = "active"', ['key' => $subjectKey]);
        if (($assignmentCount['cnt'] ?? 0) > 0) {
            $respond(409, ['success' => false, 'message' => t('startlist.departments.errors.assigned_numbers')]);
        }
        audit_log('class_departments', $departmentId, 'delete', $department, null);
        db_execute('DELETE FROM class_departments WHERE id = :id', ['id' => $departmentId]);
        $respond(200, ['success' => true]);
        break;
    case 'reorder':
        $order = $input['order'] ?? [];
        if (!is_array($order)) {
            $respond(422, ['success' => false, 'message' => t('startlist.departments.errors.order_invalid')]);
        }
        $order = array_values(array_filter(array_map(static fn ($id) => (int) $id, $order), static fn ($id) => $id > 0));
        if (!$order) {
            $respond(200, ['success' => true]);
        }
        $validIds = db_all('SELECT id FROM class_departments WHERE class_id = :class_id', ['class_id' => $classId]);
        $validMap = array_flip(array_map(static fn ($row) => (int) $row['id'], $validIds));
        $position = 1;
        foreach ($order as $id) {
            if (!isset($validMap[$id])) {
                continue;
            }
            db_execute('UPDATE class_departments SET position = :position, updated_at = :updated WHERE id = :id', [
                'position' => $position,
                'updated' => (new \DateTimeImmutable())->format('c'),
                'id' => $id,
            ]);
            $position++;
        }
        $respond(200, ['success' => true]);
        break;
    case 'assign':
        $itemIds = $input['item_ids'] ?? [];
        if (!is_array($itemIds) || !$itemIds) {
            $respond(422, ['success' => false, 'message' => t('startlist.departments.errors.assignment_invalid')]);
        }
        $itemIds = array_values(array_filter(array_map(static fn ($id) => (int) $id, $itemIds), static fn ($id) => $id > 0));
        if (!$itemIds) {
            $respond(422, ['success' => false, 'message' => t('startlist.departments.errors.assignment_invalid')]);
        }
        $targetDepartmentId = isset($input['department_id']) ? (int) $input['department_id'] : 0;
        $targetDepartment = null;
        if ($targetDepartmentId > 0) {
            $targetDepartment = db_first('SELECT * FROM class_departments WHERE id = :id', ['id' => $targetDepartmentId]);
            if (!$targetDepartment || (int) $targetDepartment['class_id'] !== $classId) {
                $respond(404, ['success' => false, 'message' => t('startlist.departments.errors.not_found')]);
            }
        }
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $items = $pdo->prepare('SELECT si.id, si.entry_id, e.department, e.department_id FROM startlist_items si JOIN entries e ON e.id = si.entry_id WHERE si.class_id = ? AND si.id IN (' . $placeholders . ')');
        $params = array_merge([$classId], $itemIds);
        $items->execute($params);
        $rows = $items->fetchAll(\PDO::FETCH_ASSOC);
        if (!$rows) {
            $respond(404, ['success' => false, 'message' => t('startlist.departments.errors.items_not_found')]);
        }
        $label = $targetDepartment['label'] ?? null;
        $departmentId = $targetDepartmentId > 0 ? $targetDepartmentId : null;
        foreach ($rows as $row) {
            $entryId = (int) ($row['entry_id'] ?? 0);
            if ($entryId <= 0) {
                continue;
            }
            $before = [
                'department' => $row['department'] ?? null,
                'department_id' => $row['department_id'] ?? null,
            ];
            db_execute('UPDATE entries SET department = :department, department_id = :department_id WHERE id = :id', [
                'department' => $label,
                'department_id' => $departmentId,
                'id' => $entryId,
            ]);
            $after = [
                'department' => $label,
                'department_id' => $departmentId,
            ];
            audit_log('entries', $entryId, 'department_update', $before, $after);
        }
        $respond(200, ['success' => true]);
        break;
    default:
        $respond(400, ['success' => false, 'message' => 'Unknown action']);
}
