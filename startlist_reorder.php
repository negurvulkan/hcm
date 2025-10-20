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
    $respond(422, ['success' => false, 'message' => t('startlist.reorder.order_invalid')]);
}

$class = db_first('SELECT c.*, e.id AS event_id FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]);
if (!$class || !event_accessible($user, (int) $class['event_id'])) {
    $respond(403, ['success' => false, 'message' => t('startlist.reorder.forbidden')]);
}

$action = (string) ($input['action'] ?? '');
if ($action !== 'reorder') {
    $respond(400, ['success' => false, 'message' => 'Unknown action']);
}

$orderInput = $input['order'] ?? [];
if (!is_array($orderInput)) {
    $respond(422, ['success' => false, 'message' => t('startlist.reorder.order_invalid')]);
}

$order = array_values(array_filter(array_map(static fn ($id) => (int) $id, $orderInput), static fn ($id) => $id > 0));

$isGroupClass = !empty($class['is_group']);
$updatedCount = 0;

if ($isGroupClass) {
    $items = db_all('SELECT si.*, e.department FROM startlist_items si JOIN entries e ON e.id = si.entry_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
    if ($items) {
        $beforeMap = [];
        foreach ($items as $row) {
            $beforeMap[(int) $row['id']] = $row;
        }
        $groups = startlist_group_entries($items);
        if ($groups) {
            $groupMap = [];
            foreach ($groups as $group) {
                $primaryId = (int) ($group['primary']['id'] ?? 0);
                if ($primaryId > 0) {
                    $groupMap[$primaryId] = $group;
                }
            }
            $orderedGroups = [];
            $seen = [];
            foreach ($order as $id) {
                if (isset($groupMap[$id]) && !isset($seen[$id])) {
                    $orderedGroups[] = $groupMap[$id];
                    $seen[$id] = true;
                }
            }
            foreach ($groups as $group) {
                $primaryId = (int) ($group['primary']['id'] ?? 0);
                if ($primaryId > 0 && !isset($seen[$primaryId])) {
                    $orderedGroups[] = $group;
                    $seen[$primaryId] = true;
                }
            }
            if (count($orderedGroups) !== count($groups)) {
                $respond(422, ['success' => false, 'message' => t('startlist.reorder.order_invalid')]);
            }
            $updates = startlist_resequence_positions($orderedGroups);
            if ($updates) {
                $timestamp = (new \DateTimeImmutable())->format('c');
                foreach ($updates as $update) {
                    db_execute('UPDATE startlist_items SET position = :position, updated_at = :updated WHERE id = :id', [
                        'position' => $update['position'],
                        'updated' => $timestamp,
                        'id' => $update['id'],
                    ]);
                    $before = $beforeMap[$update['id']] ?? null;
                    audit_log('startlist_items', $update['id'], 'reorder', $before, ['position' => $update['position']]);
                    $updatedCount++;
                }
            }
        }
    }
} else {
    $items = db_all('SELECT * FROM startlist_items WHERE class_id = :class_id ORDER BY position', ['class_id' => $classId]);
    if ($items) {
        $beforeMap = [];
        foreach ($items as $row) {
            $beforeMap[(int) $row['id']] = $row;
        }
        $existingIds = array_map(static fn (array $row): int => (int) $row['id'], $items);
        $finalOrder = [];
        $seen = [];
        foreach ($order as $id) {
            if (in_array($id, $existingIds, true) && !isset($seen[$id])) {
                $finalOrder[] = $id;
                $seen[$id] = true;
            }
        }
        foreach ($existingIds as $id) {
            if (!isset($seen[$id])) {
                $finalOrder[] = $id;
                $seen[$id] = true;
            }
        }
        if (count($finalOrder) !== count($existingIds)) {
            $respond(422, ['success' => false, 'message' => t('startlist.reorder.order_invalid')]);
        }
        $timestamp = (new \DateTimeImmutable())->format('c');
        foreach ($finalOrder as $index => $id) {
            $position = $index + 1;
            $before = $beforeMap[$id] ?? null;
            if ($before && (int) ($before['position'] ?? 0) === $position) {
                continue;
            }
            db_execute('UPDATE startlist_items SET position = :position, updated_at = :updated WHERE id = :id', [
                'position' => $position,
                'updated' => $timestamp,
                'id' => $id,
            ]);
            audit_log('startlist_items', $id, 'reorder', $before, ['position' => $position]);
            $updatedCount++;
        }
    }
}

$respond(200, ['success' => true, 'updated' => $updatedCount]);
