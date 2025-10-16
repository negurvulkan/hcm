<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/audit.php';

$user = auth_require('startlist');
$isAdmin = auth_is_admin($user);
$activeEvent = event_active();
$classesSql = 'SELECT c.id, c.label, c.event_id, e.title, c.start_time FROM classes c JOIN events e ON e.id = c.event_id';
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
    render_page('startlist.tpl', [
        'title' => 'Startlisten',
        'page' => 'startlist',
        'classes' => [],
        'selectedClass' => null,
        'startlist' => [],
        'conflicts' => [],
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
        render_page('startlist.tpl', [
            'title' => 'Startlisten',
            'page' => 'startlist',
            'classes' => [],
            'selectedClass' => null,
            'startlist' => [],
            'conflicts' => [],
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'generate') {
        $entries = db_all('SELECT e.id, p.name AS rider, h.name AS horse, p.club_id FROM entries e JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE e.class_id = :class_id AND e.status IN ("open", "paid")', ['class_id' => $classId]);
        if (!$entries) {
            flash('error', 'Keine Nennungen vorhanden.');
            header('Location: startlist.php?class_id=' . $classId);
            exit;
        }

        db_execute('DELETE FROM startlist_items WHERE class_id = :class_id', ['class_id' => $classId]);

        $grouped = [];
        foreach ($entries as $entry) {
            $key = (string) ($entry['club_id'] ?? '0');
            $grouped[$key][] = $entry;
        }

        ksort($grouped);
        $ordered = [];
        while ($grouped) {
            foreach (array_keys($grouped) as $club) {
                $item = array_shift($grouped[$club]);
                if ($item) {
                    $ordered[] = $item;
                }
                if (!$grouped[$club]) {
                    unset($grouped[$club]);
                }
            }
        }

        $current = $selectedClass['start_time'] ? new \DateTimeImmutable($selectedClass['start_time']) : new \DateTimeImmutable('today 09:00');
        foreach ($ordered as $position => $entry) {
            if ($position > 0) {
                $current = $current->add(new \DateInterval('PT6M'));
                if ($position % 5 === 0) {
                    $current = $current->add(new \DateInterval('PT5M'));
                }
            }
            db_execute(
                'INSERT INTO startlist_items (class_id, entry_id, position, planned_start, state, note, created_at, updated_at) VALUES (:class_id, :entry_id, :position, :planned_start, :state, :note, :created, :updated)',
                [
                    'class_id' => $classId,
                    'entry_id' => $entry['id'],
                    'position' => $position + 1,
                    'planned_start' => $current->format('c'),
                    'state' => 'scheduled',
                    'note' => null,
                    'created' => (new \DateTimeImmutable())->format('c'),
                    'updated' => (new \DateTimeImmutable())->format('c'),
                ]
            );
            $itemId = (int) app_pdo()->lastInsertId();
            audit_log('startlist_items', $itemId, 'generated', null, [
                'position' => $position + 1,
            ]);
        }

        flash('success', 'Startliste generiert.');
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'toggle_state') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $itemId]);
        if ($item) {
            $before = $item;
            $newState = $item['state'] === 'withdrawn' ? 'scheduled' : 'withdrawn';
            db_execute('UPDATE startlist_items SET state = :state, updated_at = :updated WHERE id = :id', [
                'state' => $newState,
                'updated' => (new \DateTimeImmutable())->format('c'),
                'id' => $itemId,
            ]);
            audit_log('startlist_items', $itemId, 'state_change', $before, ['state' => $newState]);
            flash('success', 'Status angepasst.');
        }
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'update_time') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $time = trim((string) ($_POST['planned_start'] ?? ''));
        $note = trim((string) ($_POST['note'] ?? ''));
        $item = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $itemId]);
        if ($item) {
            $before = $item;
            db_execute('UPDATE startlist_items SET planned_start = :start, note = :note, updated_at = :updated WHERE id = :id', [
                'start' => $time ?: null,
                'note' => $note !== '' ? $note : null,
                'updated' => (new \DateTimeImmutable())->format('c'),
                'id' => $itemId,
            ]);
            $after = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $itemId]);
            audit_log('startlist_items', $itemId, 'time_update', $before, $after);
            flash('success', 'Start aktualisiert.');
        }
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'delete_item') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $itemId]);
        if ($item) {
            db_execute('DELETE FROM results WHERE startlist_id = :id', ['id' => $itemId]);
            db_execute('DELETE FROM startlist_items WHERE id = :id', ['id' => $itemId]);
            audit_log('startlist_items', $itemId, 'delete', $item, null);
            flash('success', 'Start aus der Liste entfernt.');
        }
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'move') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $direction = $_POST['direction'] ?? 'up';
        $item = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $itemId]);
        if ($item) {
            $newPosition = max(1, (int) $item['position'] + ($direction === 'up' ? -1 : 1));
            $swap = db_first('SELECT * FROM startlist_items WHERE class_id = :class_id AND position = :position', [
                'class_id' => $classId,
                'position' => $newPosition,
            ]);
            if ($swap) {
                db_execute('UPDATE startlist_items SET position = :pos WHERE id = :id', [
                    'pos' => $item['position'],
                    'id' => $swap['id'],
                ]);
            }
            db_execute('UPDATE startlist_items SET position = :pos, updated_at = :updated WHERE id = :id', [
                'pos' => $newPosition,
                'updated' => (new \DateTimeImmutable())->format('c'),
                'id' => $itemId,
            ]);
            audit_log('startlist_items', $itemId, 'reorder', $item, ['position' => $newPosition]);
        }
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }
}

$startlist = db_all('SELECT si.*, e.status, p.name AS rider, h.name AS horse, h.id AS horse_id, p.club_id FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);

$conflicts = [];
foreach ($startlist as $index => $item) {
    if (!isset($startlist[$index - 1])) {
        continue;
    }
    $previous = $startlist[$index - 1];
    if ($previous['horse_id'] === $item['horse_id'] && abs($item['position'] - $previous['position']) < 3) {
        $conflicts[] = [$previous, $item];
    }
}

render_page('startlist.tpl', [
    'title' => 'Startlisten',
    'page' => 'startlist',
    'classes' => $classes,
    'selectedClass' => $selectedClass,
    'startlist' => $startlist,
    'conflicts' => $conflicts,
]);
