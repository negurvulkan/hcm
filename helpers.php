<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';

use App\Core\Csrf;
use App\Party\PartyRepository;

$user = auth_require('helpers');
$pdo = app_pdo();
$partyRepository = new PartyRepository($pdo);

$stations = db_all(
    'SELECT s.id, s.name, e.title AS event_title '
    . 'FROM stations s '
    . 'LEFT JOIN events e ON e.id = s.event_id '
    . 'ORDER BY s.name'
);
$roles = db_all('SELECT * FROM helper_roles ORDER BY active DESC, name ASC');
$events = db_all('SELECT id, title FROM events ORDER BY start_date DESC, title ASC');
$persons = $partyRepository->personOptions();

$editRoleId = isset($_GET['edit_role']) ? (int) $_GET['edit_role'] : 0;
$editRole = $editRoleId > 0 ? db_first('SELECT * FROM helper_roles WHERE id = :id', ['id' => $editRoleId]) : null;

$editShiftId = isset($_GET['edit_shift']) ? (int) $_GET['edit_shift'] : 0;
$editShift = $editShiftId > 0 ? db_first('SELECT * FROM shifts WHERE id = :id', ['id' => $editShiftId]) : null;
if ($editShift) {
    if (!empty($editShift['starts_at'])) {
        $editShift['starts_at'] = date('Y-m-d\TH:i', strtotime($editShift['starts_at']));
    }
    if (!empty($editShift['ends_at'])) {
        $editShift['ends_at'] = date('Y-m-d\TH:i', strtotime($editShift['ends_at']));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('helpers.validation.csrf_invalid'));
        header('Location: helpers.php');
        exit;
    }

    require_write_access('helpers');
    $action = $_POST['action'] ?? '';
    $now = (new DateTimeImmutable())->format('c');

    if ($action === 'create_role' || $action === 'update_role') {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $key = trim((string) ($_POST['role_key'] ?? ''));
        $color = trim((string) ($_POST['color'] ?? ''));
        $active = isset($_POST['active']) && (string) $_POST['active'] === '1' ? 1 : 0;

        if ($name === '') {
            flash('error', t('helpers.validation.role_name_required'));
            header('Location: helpers.php' . ($roleId ? '?edit_role=' . $roleId : ''));
            exit;
        }

        if ($key === '') {
            $key = strtolower(preg_replace('/[^a-z0-9_]+/i', '-', $name));
        }

        if ($action === 'update_role' && $roleId > 0) {
            db_execute(
                'UPDATE helper_roles SET role_key = :key, name = :name, color = :color, active = :active, updated_at = :updated WHERE id = :id',
                [
                    'key' => $key,
                    'name' => $name,
                    'color' => $color !== '' ? $color : null,
                    'active' => $active,
                    'updated' => $now,
                    'id' => $roleId,
                ]
            );
            flash('success', t('helpers.flash.role_updated'));
        } else {
            db_execute(
                'INSERT INTO helper_roles (role_key, name, color, active, created_at, updated_at) VALUES (:key, :name, :color, :active, :created, :created)',
                [
                    'key' => $key,
                    'name' => $name,
                    'color' => $color !== '' ? $color : null,
                    'active' => $active,
                    'created' => $now,
                ]
            );
            flash('success', t('helpers.flash.role_created'));
        }

        header('Location: helpers.php');
        exit;
    }

    if ($action === 'delete_role') {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        if ($roleId > 0) {
            $shiftCount = db_first('SELECT COUNT(*) AS count FROM shifts WHERE role_id = :role', ['role' => $roleId]);
            if ($shiftCount && (int) $shiftCount['count'] > 0) {
                flash('error', t('helpers.validation.role_in_use'));
            } else {
                db_execute('DELETE FROM helper_roles WHERE id = :id', ['id' => $roleId]);
                flash('success', t('helpers.flash.role_deleted'));
            }
        }
        header('Location: helpers.php');
        exit;
    }

    if ($action === 'create_shift' || $action === 'update_shift') {
        $shiftId = (int) ($_POST['shift_id'] ?? 0);
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $stationId = (int) ($_POST['station_id'] ?? 0) ?: null;
        $eventId = (int) ($_POST['event_id'] ?? 0) ?: null;
        $personId = (int) ($_POST['person_id'] ?? 0) ?: null;
        $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAt = trim((string) ($_POST['ends_at'] ?? ''));
        $status = $_POST['status'] ?? 'open';
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($roleId <= 0) {
            flash('error', t('helpers.validation.role_required'));
            header('Location: helpers.php' . ($shiftId ? '?edit_shift=' . $shiftId : ''));
            exit;
        }

        $startsAtValue = $startsAt !== '' ? (new DateTimeImmutable($startsAt))->format('c') : null;
        $endsAtValue = $endsAt !== '' ? (new DateTimeImmutable($endsAt))->format('c') : null;

        if ($action === 'update_shift' && $shiftId > 0) {
            db_execute(
                'UPDATE shifts SET event_id = :event_id, role_id = :role_id, station_id = :station_id, person_id = :person_id, starts_at = :starts_at, ends_at = :ends_at, status = :status, notes = :notes, updated_at = :updated WHERE id = :id',
                [
                    'event_id' => $eventId,
                    'role_id' => $roleId,
                    'station_id' => $stationId,
                    'person_id' => $personId,
                    'starts_at' => $startsAtValue,
                    'ends_at' => $endsAtValue,
                    'status' => $status,
                    'notes' => $notes !== '' ? $notes : null,
                    'updated' => $now,
                    'id' => $shiftId,
                ]
            );
            flash('success', t('helpers.flash.shift_updated'));
        } else {
            db_execute(
                'INSERT INTO shifts (event_id, role_id, station_id, person_id, starts_at, ends_at, status, token_id, notes, created_at, updated_at) '
                . 'VALUES (:event_id, :role_id, :station_id, :person_id, :starts_at, :ends_at, :status, NULL, :notes, :created, :created)',
                [
                    'event_id' => $eventId,
                    'role_id' => $roleId,
                    'station_id' => $stationId,
                    'person_id' => $personId,
                    'starts_at' => $startsAtValue,
                    'ends_at' => $endsAtValue,
                    'status' => $status !== '' ? $status : 'open',
                    'notes' => $notes !== '' ? $notes : null,
                    'created' => $now,
                ]
            );
            flash('success', t('helpers.flash.shift_created'));
        }

        header('Location: helpers.php');
        exit;
    }

    if ($action === 'delete_shift') {
        $shiftId = (int) ($_POST['shift_id'] ?? 0);
        if ($shiftId > 0) {
            db_execute('DELETE FROM shifts WHERE id = :id', ['id' => $shiftId]);
            flash('success', t('helpers.flash.shift_deleted'));
        }
        header('Location: helpers.php');
        exit;
    }

    if ($action === 'duplicate_shift') {
        $shiftId = (int) ($_POST['shift_id'] ?? 0);
        if ($shiftId > 0) {
            $shift = db_first('SELECT * FROM shifts WHERE id = :id', ['id' => $shiftId]);
            if ($shift) {
                db_execute(
                    'INSERT INTO shifts (event_id, role_id, station_id, person_id, starts_at, ends_at, status, token_id, notes, created_at, updated_at) '
                    . 'VALUES (:event_id, :role_id, :station_id, NULL, :starts_at, :ends_at, :status, NULL, :notes, :created, :created)',
                    [
                        'event_id' => $shift['event_id'],
                        'role_id' => $shift['role_id'],
                        'station_id' => $shift['station_id'],
                        'starts_at' => $shift['starts_at'],
                        'ends_at' => $shift['ends_at'],
                        'status' => 'open',
                        'notes' => $shift['notes'] ?? null,
                        'created' => $now,
                    ]
                );
                flash('success', t('helpers.flash.shift_duplicated'));
            }
        }
        header('Location: helpers.php');
        exit;
    }

    if ($action === 'set_status') {
        $shiftId = (int) ($_POST['shift_id'] ?? 0);
        $targetStatus = $_POST['target_status'] ?? '';
        $allowed = ['open', 'assigned', 'active', 'done'];
        if ($shiftId > 0 && in_array($targetStatus, $allowed, true)) {
            $current = db_first('SELECT status FROM shifts WHERE id = :id', ['id' => $shiftId]);
            if ($current) {
                $currentStatus = $current['status'] ?? 'open';
                $validTransitions = [
                    'open' => ['assigned'],
                    'assigned' => ['open', 'active'],
                    'active' => ['assigned', 'done'],
                    'done' => ['active'],
                ];
                $canTransition = in_array($targetStatus, $validTransitions[$currentStatus] ?? [], true);
                if ($canTransition) {
                    db_execute('UPDATE shifts SET status = :status, updated_at = :updated WHERE id = :id', [
                        'status' => $targetStatus,
                        'updated' => $now,
                        'id' => $shiftId,
                    ]);
                    audit_log('shifts', $shiftId, 'status_change', ['status' => $currentStatus], ['status' => $targetStatus]);
                    flash('success', t('helpers.flash.status_updated'));
                } else {
                    flash('error', t('helpers.validation.status_transition_invalid'));
                }
            }
        }
        header('Location: helpers.php');
        exit;
    }
}

$filters = [
    'status' => $_GET['status'] ?? 'all',
    'role_id' => (int) ($_GET['role_id'] ?? 0),
    'station_id' => (int) ($_GET['station_id'] ?? 0),
    'person_id' => (int) ($_GET['person_id'] ?? 0),
    'day' => trim((string) ($_GET['day'] ?? '')),
];

$params = [];
$conditions = [];
if ($filters['status'] !== 'all') {
    $conditions[] = 'sh.status = :status';
    $params['status'] = $filters['status'];
}
if ($filters['role_id'] > 0) {
    $conditions[] = 'sh.role_id = :role_id';
    $params['role_id'] = $filters['role_id'];
}
if ($filters['station_id'] > 0) {
    $conditions[] = 'sh.station_id = :station_id';
    $params['station_id'] = $filters['station_id'];
}
if ($filters['person_id'] > 0) {
    $conditions[] = 'sh.person_id = :person_id';
    $params['person_id'] = $filters['person_id'];
}
if ($filters['day'] !== '') {
    $conditions[] = 'DATE(sh.starts_at) = :day';
    $params['day'] = $filters['day'];
}

$sql = 'SELECT sh.*, hr.name AS role_name, hr.color AS role_color, st.name AS station_name, pr.display_name AS person_name, e.title AS event_title,'
    . ' tk.token AS shift_token, tk.expires_at AS shift_token_expires_at,'
    . ' (
        SELECT token FROM tokens stt
        WHERE stt.station_id = sh.station_id AND stt.purpose = \'checkin\'
        ORDER BY stt.expires_at DESC, stt.id DESC
        LIMIT 1
      ) AS station_token'
    . ' FROM shifts sh '
    . 'LEFT JOIN helper_roles hr ON hr.id = sh.role_id '
    . 'LEFT JOIN stations st ON st.id = sh.station_id '
    . 'LEFT JOIN parties pr ON pr.id = sh.person_id '
    . 'LEFT JOIN events e ON e.id = sh.event_id '
    . 'LEFT JOIN tokens tk ON tk.id = sh.token_id';
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY COALESCE(sh.starts_at, sh.created_at) ASC';

$shifts = db_all($sql, $params);

$statusOptions = [
    'open' => t('helpers.statuses.open'),
    'assigned' => t('helpers.statuses.assigned'),
    'active' => t('helpers.statuses.active'),
    'done' => t('helpers.statuses.done'),
];
$statusClasses = [
    'open' => 'bg-secondary',
    'assigned' => 'bg-info text-dark',
    'active' => 'bg-success',
    'done' => 'bg-dark',
];

render_page('helpers.tpl', [
    'titleKey' => 'helpers.title',
    'page' => 'helpers',
    'roles' => $roles,
    'stations' => $stations,
    'events' => $events,
    'persons' => $persons,
    'shifts' => $shifts,
    'filters' => $filters,
    'editRole' => $editRole,
    'editShift' => $editShift,
    'statusOptions' => $statusOptions,
    'statusClasses' => $statusClasses,
    'extraScripts' => ['public/assets/js/helpers-autocomplete.js'],
]);
