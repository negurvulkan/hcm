<?php
require __DIR__ . '/auth.php';

$user = auth_require('helpers');
$persons = db_all('SELECT id, name FROM persons ORDER BY name');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: helpers.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $role = trim((string) ($_POST['role'] ?? ''));
        $station = trim((string) ($_POST['station'] ?? ''));
        $personId = (int) ($_POST['person_id'] ?? 0) ?: null;
        $start = trim((string) ($_POST['start_time'] ?? ''));
        $end = trim((string) ($_POST['end_time'] ?? ''));
        if ($role === '') {
            flash('error', 'Rolle angeben.');
        } else {
            $conflict = null;
            if ($personId && $start && $end) {
                $conflict = db_first('SELECT * FROM helper_shifts WHERE person_id = :person AND ((:start BETWEEN start_time AND end_time) OR (:end BETWEEN start_time AND end_time))', [
                    'person' => $personId,
                    'start' => $start,
                    'end' => $end,
                ]);
            }
            if ($conflict) {
                flash('error', 'Konflikt mit bestehender Schicht.');
            } else {
                db_execute('INSERT INTO helper_shifts (role, station, person_id, start_time, end_time, token, created_at) VALUES (:role, :station, :person, :start, :end, :token, :created)', [
                    'role' => $role,
                    'station' => $station ?: null,
                    'person' => $personId,
                    'start' => $start ?: null,
                    'end' => $end ?: null,
                    'token' => bin2hex(random_bytes(6)),
                    'created' => (new \DateTimeImmutable())->format('c'),
                ]);
                flash('success', 'Schicht angelegt.');
            }
        }
        header('Location: helpers.php');
        exit;
    }
    if ($action === 'checkin') {
        $shiftId = (int) ($_POST['shift_id'] ?? 0);
        if ($shiftId) {
            db_execute('UPDATE helper_shifts SET checked_in_at = :time WHERE id = :id', [
                'time' => (new \DateTimeImmutable())->format('c'),
                'id' => $shiftId,
            ]);
            flash('success', 'Check-in registriert.');
        }
        header('Location: helpers.php');
        exit;
    }
}

$shifts = db_all('SELECT hs.*, p.name AS person FROM helper_shifts hs LEFT JOIN persons p ON p.id = hs.person_id ORDER BY hs.start_time');

render_page('helpers.tpl', [
    'title' => 'Helferkoordination',
    'page' => 'helpers',
    'persons' => $persons,
    'shifts' => $shifts,
]);
