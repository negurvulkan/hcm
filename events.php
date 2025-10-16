<?php
require __DIR__ . '/auth.php';

use DateTimeImmutable;

$user = auth_require('events');

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

    if ($action === 'delete') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId) {
            $hasClasses = db_first('SELECT COUNT(*) AS cnt FROM classes WHERE event_id = :id', ['id' => $eventId]);
            if ($hasClasses && (int) $hasClasses['cnt'] > 0) {
                flash('error', 'Turnier besitzt Prüfungen und kann nicht gelöscht werden.');
            } else {
                db_execute('DELETE FROM events WHERE id = :id', ['id' => $eventId]);
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

$events = db_all('SELECT * FROM events ORDER BY start_date DESC, id DESC');
foreach ($events as &$event) {
    $event['venues_list'] = $event['venues'] ? json_decode($event['venues'], true, 512, JSON_THROW_ON_ERROR) : [];
}
unset($event);

render_page('events.tpl', [
    'title' => 'Turniere',
    'page' => 'events',
    'events' => $events,
    'editEvent' => $editEvent,
]);
