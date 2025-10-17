<?php
require __DIR__ . '/auth.php';

use App\Party\PartyRepository;

$user = auth_require('helpers');
$partyRepository = new PartyRepository(app_pdo());
$persons = $partyRepository->personOptions();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editShift = $editId ? db_first('SELECT * FROM helper_shifts WHERE id = :id', ['id' => $editId]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('helpers.validation.csrf_invalid'));
        header('Location: helpers.php');
        exit;
    }
    require_write_access('helpers');
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $role = trim((string) ($_POST['role'] ?? ''));
        $station = trim((string) ($_POST['station'] ?? ''));
        $personId = (int) ($_POST['person_id'] ?? 0) ?: null;
        $start = trim((string) ($_POST['start_time'] ?? ''));
        $end = trim((string) ($_POST['end_time'] ?? ''));
        if ($role === '') {
            flash('error', t('helpers.validation.role_required'));
        } else {
            $conflict = null;
            if ($personId && $start && $end) {
                $conflict = db_first('SELECT * FROM helper_shifts WHERE party_id = :person AND ((:start BETWEEN start_time AND end_time) OR (:end BETWEEN start_time AND end_time))', [
                    'person' => $personId,
                    'start' => $start,
                    'end' => $end,
                ]);
            }
            if ($conflict) {
                flash('error', t('helpers.validation.conflict'));
            } else {
                db_execute('INSERT INTO helper_shifts (role, station, party_id, start_time, end_time, token, created_at) VALUES (:role, :station, :person, :start, :end, :token, :created)', [
                    'role' => $role,
                    'station' => $station ?: null,
                    'person' => $personId,
                    'start' => $start ?: null,
                    'end' => $end ?: null,
                    'token' => bin2hex(random_bytes(6)),
                    'created' => (new \DateTimeImmutable())->format('c'),
                ]);
                flash('success', t('helpers.flash.created'));
            }
        }
        header('Location: helpers.php');
        exit;
    }
    if ($action === 'update') {
        $shiftId = (int) ($_POST['shift_id'] ?? 0);
        $role = trim((string) ($_POST['role'] ?? ''));
        $station = trim((string) ($_POST['station'] ?? ''));
        $personId = (int) ($_POST['person_id'] ?? 0) ?: null;
        $start = trim((string) ($_POST['start_time'] ?? ''));
        $end = trim((string) ($_POST['end_time'] ?? ''));
        if ($shiftId && $role !== '') {
            db_execute('UPDATE helper_shifts SET role = :role, station = :station, party_id = :person, start_time = :start, end_time = :end WHERE id = :id', [
                'role' => $role,
                'station' => $station ?: null,
                'person' => $personId,
                'start' => $start ?: null,
                'end' => $end ?: null,
                'id' => $shiftId,
            ]);
            flash('success', t('helpers.flash.updated'));
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
            flash('success', t('helpers.flash.checked_in'));
        }
        header('Location: helpers.php');
        exit;
    }
    if ($action === 'delete') {
        $shiftId = (int) ($_POST['shift_id'] ?? 0);
        if ($shiftId) {
            db_execute('DELETE FROM helper_shifts WHERE id = :id', ['id' => $shiftId]);
            flash('success', t('helpers.flash.deleted'));
        }
        header('Location: helpers.php');
        exit;
    }
}

$shifts = db_all('SELECT hs.*, pr.display_name AS person FROM helper_shifts hs LEFT JOIN parties pr ON pr.id = hs.party_id ORDER BY hs.start_time');

render_page('helpers.tpl', [
    'titleKey' => 'helpers.title',
    'page' => 'helpers',
    'persons' => $persons,
    'shifts' => $shifts,
    'editShift' => $editShift,
]);
