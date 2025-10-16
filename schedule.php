<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/audit.php';

$user = auth_require('schedule');
$classes = db_all('SELECT c.id, c.label, e.title FROM classes c JOIN events e ON e.id = c.event_id ORDER BY e.title, c.label');
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
$selectedClass = db_first('SELECT c.*, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]);
if (!$selectedClass) {
    $selectedClass = $classes[0];
    $classId = (int) $selectedClass['id'];
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
}

$items = db_all('SELECT si.position, si.planned_start, p.name AS rider, h.name AS horse FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
$shifts = db_all('SELECT shift_minutes, created_at FROM schedule_shifts WHERE class_id = :class_id ORDER BY id DESC LIMIT 10', ['class_id' => $classId]);

render_page('schedule.tpl', [
    'title' => 'Zeitplan',
    'page' => 'schedule',
    'classes' => $classes,
    'selectedClass' => $selectedClass,
    'items' => $items,
    'shifts' => $shifts,
]);
