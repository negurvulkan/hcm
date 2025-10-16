<?php
require __DIR__ . '/auth.php';

$user = auth_require('events');
$isAdmin = auth_is_admin($user);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editEvent = $editId ? db_first('SELECT * FROM events WHERE id = :id', ['id' => $editId]) : null;
if ($editEvent && $editEvent['venues']) {
    $editEvent['venues_list'] = json_decode($editEvent['venues'], true, 512, JSON_THROW_ON_ERROR) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: events.php');
        exit;
    }

    $action = $_POST['action'] ?? 'create';

    if (in_array($action, ['set_active', 'deactivate'], true)) {
        if (!$isAdmin) {
            flash('error', 'Nur Administratoren können Turniere aktivieren oder deaktivieren.');
            header('Location: events.php');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId) {
            if ($action === 'set_active') {
                db_execute('UPDATE events SET is_active = 0 WHERE is_active = 1');
                db_execute('UPDATE events SET is_active = 1 WHERE id = :id', ['id' => $eventId]);
                event_active(true);
                flash('success', 'Aktives Turnier aktualisiert.');
            } else {
                db_execute('UPDATE events SET is_active = 0 WHERE id = :id', ['id' => $eventId]);
                event_active(true);
                flash('success', 'Turnier deaktiviert.');
            }
        }
        header('Location: events.php');
        exit;
    }

    if ($action === 'delete') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId) {
            $hasClasses = db_first('SELECT COUNT(*) AS cnt FROM classes WHERE event_id = :id', ['id' => $eventId]);
            if ($hasClasses && (int) $hasClasses['cnt'] > 0) {
                flash('error', 'Turnier besitzt Prüfungen und kann nicht gelöscht werden.');
            } else {
                db_execute('DELETE FROM events WHERE id = :id', ['id' => $eventId]);
                event_active(true);
                flash('success', 'Turnier gelöscht.');
            }
        }
        header('Location: events.php');
        exit;
    }

    $eventId = (int) ($_POST['event_id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $start = trim((string) ($_POST['start_date'] ?? ''));
    $end = trim((string) ($_POST['end_date'] ?? ''));
    $venues = array_filter(array_map('trim', explode(',', (string) ($_POST['venues'] ?? ''))));

    if ($title === '') {
        flash('error', 'Titel erforderlich.');
    } else {
        $payload = [
            'title' => $title,
            'start' => $start ?: null,
            'end' => $end ?: null,
            'venues' => $venues ? json_encode(array_values($venues), JSON_THROW_ON_ERROR) : null,
        ];

        if ($action === 'update' && $eventId > 0) {
            db_execute(
                'UPDATE events SET title = :title, start_date = :start, end_date = :end, venues = :venues WHERE id = :id',
                $payload + ['id' => $eventId]
            );
            flash('success', 'Turnier aktualisiert.');
        } else {
            db_execute(
                'INSERT INTO events (title, start_date, end_date, venues) VALUES (:title, :start, :end, :venues)',
                $payload
            );
            flash('success', 'Turnier angelegt.');
        }
    }

    header('Location: events.php');
    exit;
}

$eventsQuery = 'SELECT * FROM events';
$params = [];
if (!$isAdmin) {
    $eventsQuery .= ' WHERE is_active = 1';
}
$eventsQuery .= ' ORDER BY start_date DESC, id DESC';
$events = db_all($eventsQuery, $params);
foreach ($events as &$event) {
    $event['venues_list'] = $event['venues'] ? json_decode($event['venues'], true, 512, JSON_THROW_ON_ERROR) : [];
}
unset($event);

render_page('events.tpl', [
    'title' => 'Turniere',
    'page' => 'events',
    'events' => $events,
    'editEvent' => $editEvent,
    'isAdmin' => $isAdmin,
]);
