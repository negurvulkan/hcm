<?php
require __DIR__ . '/auth.php';

use DateTimeImmutable;

$user = auth_require('events');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
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
        if ($eventId > 0) {
            db_execute(
                'UPDATE events SET title = :title, start_date = :start, end_date = :end, venues = :venues WHERE id = :id',
                [
                    'title' => $title,
                    'start' => $start ?: null,
                    'end' => $end ?: null,
                    'venues' => $venues ? json_encode(array_values($venues), JSON_THROW_ON_ERROR) : null,
                    'id' => $eventId,
                ]
            );
            flash('success', 'Turnier aktualisiert.');
        } else {
            db_execute(
                'INSERT INTO events (title, start_date, end_date, venues) VALUES (:title, :start, :end, :venues)',
                [
                    'title' => $title,
                    'start' => $start ?: null,
                    'end' => $end ?: null,
                    'venues' => $venues ? json_encode(array_values($venues), JSON_THROW_ON_ERROR) : null,
                ]
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
]);
