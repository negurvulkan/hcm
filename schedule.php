<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/audit.php';

$user = auth_require('schedule');
$isAdmin = auth_is_admin($user);
$activeEvent = event_active();
$classesSql = 'SELECT c.id, c.label, c.event_id, e.title FROM classes c JOIN events e ON e.id = c.event_id';
if (!$isAdmin) {
    if (!$activeEvent) {
        $classes = [];
    } else {
        $classes = db_all($classesSql . ' WHERE e.id = :event_id ORDER BY e.title, c.label', ['event_id' => (int) $activeEvent['id']]);
    }
} else {
    $classes = db_all($classesSql . ' ORDER BY e.title, c.label');
}
if (!$classes) {
    render_page('schedule.tpl', [
        'title' => 'Zeitplan',
        'page' => 'schedule',
        'classes' => [],
        'selectedClass' => null,
        'items' => [],
        'shifts' => [],
    ]);
    exit;
}

$classId = (int) ($_GET['class_id'] ?? $classes[0]['id']);
$selectedClass = $classId ? db_first('SELECT c.*, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]) : null;
if (!$selectedClass || !event_accessible($user, (int) $selectedClass['event_id'])) {
    $classId = (int) $classes[0]['id'];
    $selectedClass = db_first('SELECT c.*, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]);
    if (!$selectedClass || !event_accessible($user, (int) $selectedClass['event_id'])) {
        flash('error', 'Keine Berechtigung für dieses Turnier.');
        render_page('schedule.tpl', [
            'title' => 'Zeitplan',
            'page' => 'schedule',
            'classes' => [],
            'selectedClass' => null,
            'items' => [],
            'shifts' => [],
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: schedule.php?class_id=' . $classId);
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'shift') {
        $minutes = (int) ($_POST['minutes'] ?? 0);
        if ($minutes !== 0) {
            $items = db_all('SELECT id, planned_start FROM startlist_items WHERE class_id = :class_id ORDER BY position', ['class_id' => $classId]);
            foreach ($items as $item) {
                if (!$item['planned_start']) {
                    continue;
                }
                $before = $item;
                $start = new \DateTimeImmutable($item['planned_start']);
                $interval = new \DateInterval('PT' . abs($minutes) . 'M');
                if ($minutes < 0) {
                    $interval->invert = 1;
                }
                $new = $start->add($interval);
                db_execute('UPDATE startlist_items SET planned_start = :start, updated_at = :updated WHERE id = :id', [
                    'start' => $new->format('c'),
                    'updated' => (new \DateTimeImmutable())->format('c'),
                    'id' => $item['id'],
                ]);
                audit_log('startlist_items', (int) $item['id'], 'time_shift', $before, ['planned_start' => $new->format('c')]);
            }
            db_execute('INSERT INTO schedule_shifts (class_id, shift_minutes, created_at) VALUES (:class_id, :shift, :created)', [
                'class_id' => $classId,
                'shift' => $minutes,
                'created' => (new \DateTimeImmutable())->format('c'),
            ]);
            db_execute('INSERT INTO notifications (type, payload, created_at) VALUES (:type, :payload, :created)', [
                'type' => 'schedule_shift',
                'payload' => json_encode([
                    'class_id' => $classId,
                    'message' => ($minutes > 0 ? '+' : '') . $minutes . ' Minuten für ' . $selectedClass['label'],
                ], JSON_THROW_ON_ERROR),
                'created' => (new \DateTimeImmutable())->format('c'),
            ]);
            flash('success', 'Zeitplan verschoben.');
        }
        header('Location: schedule.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'update_item') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $time = trim((string) ($_POST['planned_start'] ?? ''));
        $item = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $itemId]);
        if ($item) {
            $before = $item;
            db_execute('UPDATE startlist_items SET planned_start = :start, updated_at = :updated WHERE id = :id', [
                'start' => $time ?: null,
                'updated' => (new \DateTimeImmutable())->format('c'),
                'id' => $itemId,
            ]);
            $after = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $itemId]);
            audit_log('startlist_items', $itemId, 'time_update', $before, $after);
            flash('success', 'Slot aktualisiert.');
        }
        header('Location: schedule.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'delete_item') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $itemId]);
        if ($item) {
            db_execute('DELETE FROM results WHERE startlist_id = :id', ['id' => $itemId]);
            db_execute('DELETE FROM startlist_items WHERE id = :id', ['id' => $itemId]);
            audit_log('startlist_items', $itemId, 'delete', $item, null);
            flash('success', 'Slot entfernt.');
        }
        header('Location: schedule.php?class_id=' . $classId);
        exit;
    }
}

$items = db_all('SELECT si.id, si.position, si.planned_start, p.name AS rider, h.name AS horse FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
$shifts = db_all('SELECT shift_minutes, created_at FROM schedule_shifts WHERE class_id = :class_id ORDER BY id DESC LIMIT 10', ['class_id' => $classId]);

render_page('schedule.tpl', [
    'title' => 'Zeitplan',
    'page' => 'schedule',
    'classes' => $classes,
    'selectedClass' => $selectedClass,
    'items' => $items,
    'shifts' => $shifts,
]);
