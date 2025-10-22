<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/app/helpers/arenas.php';

use App\Core\Csrf;

$user = auth_require('arenas');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('arenas.validation.csrf_invalid'));
        header('Location: arenas.php');
        exit;
    }

    require_write_access('arenas');

    $action = $_POST['action'] ?? '';
    $now = (new DateTimeImmutable())->format('c');

    if ($action === 'create_location' || $action === 'update_location') {
        $locationId = (int) ($_POST['location_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($name === '') {
            flash('error', t('arenas.validation.location_name_required'));
            header('Location: arenas.php' . ($locationId ? '?edit_location=' . $locationId : ''));
            exit;
        }

        if ($action === 'update_location' && $locationId > 0) {
            db_execute(
                'UPDATE locations SET name = :name, address = :address, notes = :notes, updated_at = :updated WHERE id = :id',
                [
                    'id' => $locationId,
                    'name' => $name,
                    'address' => $address !== '' ? $address : null,
                    'notes' => $notes !== '' ? $notes : null,
                    'updated' => $now,
                ]
            );
            flash('success', t('arenas.flash.location_updated'));
        } else {
            db_execute(
                'INSERT INTO locations (name, address, notes, created_at, updated_at) VALUES (:name, :address, :notes, :created, :created)',
                [
                    'name' => $name,
                    'address' => $address !== '' ? $address : null,
                    'notes' => $notes !== '' ? $notes : null,
                    'created' => $now,
                ]
            );
            flash('success', t('arenas.flash.location_created'));
        }

        header('Location: arenas.php');
        exit;
    }

    if ($action === 'delete_location') {
        $locationId = (int) ($_POST['location_id'] ?? 0);
        if ($locationId > 0) {
            db_execute('DELETE FROM locations WHERE id = :id', ['id' => $locationId]);
            flash('success', t('arenas.flash.location_deleted'));
        }
        header('Location: arenas.php');
        exit;
    }

    if ($action === 'create_arena' || $action === 'update_arena') {
        $arenaId = (int) ($_POST['arena_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $locationId = (int) ($_POST['location_id'] ?? 0) ?: null;
        $type = (string) ($_POST['type'] ?? 'outdoor');
        $surface = trim((string) ($_POST['surface'] ?? ''));
        $length = (int) ($_POST['length_m'] ?? 0);
        $width = (int) ($_POST['width_m'] ?? 0);
        $covered = isset($_POST['covered']) && (string) $_POST['covered'] === '1' ? 1 : 0;
        $lighting = isset($_POST['lighting']) && (string) $_POST['lighting'] === '1' ? 1 : 0;
        $drainage = isset($_POST['drainage']) && (string) $_POST['drainage'] === '1' ? 1 : 0;
        $capacity = (int) ($_POST['capacity'] ?? 0);
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($name === '') {
            flash('error', t('arenas.validation.arena_name_required'));
            header('Location: arenas.php' . ($arenaId ? '?edit_arena=' . $arenaId : ''));
            exit;
        }

        $type = $type === 'indoor' ? 'indoor' : 'outdoor';

        $payload = [
            'name' => $name,
            'location_id' => $locationId,
            'type' => $type,
            'surface' => $surface !== '' ? $surface : null,
            'length_m' => $length > 0 ? $length : null,
            'width_m' => $width > 0 ? $width : null,
            'covered' => $covered,
            'lighting' => $lighting,
            'drainage' => $drainage,
            'capacity' => $capacity > 0 ? $capacity : null,
            'notes' => $notes !== '' ? $notes : null,
        ];

        if ($action === 'update_arena' && $arenaId > 0) {
            $payload['id'] = $arenaId;
            $payload['updated_at'] = $now;
            db_execute(
                'UPDATE arenas SET name = :name, location_id = :location_id, type = :type, surface = :surface, length_m = :length_m, width_m = :width_m, covered = :covered, lighting = :lighting, drainage = :drainage, capacity = :capacity, notes = :notes, updated_at = :updated_at WHERE id = :id',
                $payload
            );
            flash('success', t('arenas.flash.arena_updated'));
        } else {
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;
            db_execute(
                'INSERT INTO arenas (name, location_id, type, surface, length_m, width_m, covered, lighting, drainage, capacity, notes, created_at, updated_at) '
                . 'VALUES (:name, :location_id, :type, :surface, :length_m, :width_m, :covered, :lighting, :drainage, :capacity, :notes, :created_at, :updated_at)',
                $payload
            );
            flash('success', t('arenas.flash.arena_created'));
        }

        header('Location: arenas.php');
        exit;
    }

    if ($action === 'delete_arena') {
        $arenaId = (int) ($_POST['arena_id'] ?? 0);
        if ($arenaId > 0) {
            db_execute('DELETE FROM arenas WHERE id = :id', ['id' => $arenaId]);
            flash('success', t('arenas.flash.arena_deleted'));
        }
        header('Location: arenas.php');
        exit;
    }

    if ($action === 'create_event_arena' || $action === 'update_event_arena') {
        $eventArenaId = (int) ($_POST['event_arena_id'] ?? 0);
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $arenaId = (int) ($_POST['arena_id'] ?? 0);
        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        $tempSurface = trim((string) ($_POST['temp_surface'] ?? ''));
        $warmupId = (int) ($_POST['warmup_arena_id'] ?? 0);
        $remarks = trim((string) ($_POST['remarks'] ?? ''));

        if ($eventId <= 0 || $arenaId <= 0) {
            flash('error', t('arenas.validation.event_arena_required'));
            header('Location: arenas.php' . ($eventArenaId ? '?edit_event_arena=' . $eventArenaId : ''));
            exit;
        }

        if ($warmupId > 0) {
            $warmupRow = db_first('SELECT event_id FROM event_arenas WHERE id = :id', ['id' => $warmupId]);
            if (!$warmupRow || (int) $warmupRow['event_id'] !== $eventId) {
                flash('error', t('arenas.validation.warmup_invalid'));
                header('Location: arenas.php' . ($eventArenaId ? '?edit_event_arena=' . $eventArenaId : ''));
                exit;
            }
        } else {
            $warmupId = null;
        }

        $params = [
            'event_id' => $eventId,
            'arena_id' => $arenaId,
            'display_name' => $displayName !== '' ? $displayName : null,
            'temp_surface' => $tempSurface !== '' ? $tempSurface : null,
            'warmup_arena_id' => $warmupId,
            'remarks' => $remarks !== '' ? $remarks : null,
        ];

        if ($action === 'update_event_arena' && $eventArenaId > 0) {
            $params['id'] = $eventArenaId;
            $params['updated_at'] = $now;
            db_execute(
                'UPDATE event_arenas SET event_id = :event_id, arena_id = :arena_id, display_name = :display_name, temp_surface = :temp_surface, warmup_arena_id = :warmup_arena_id, remarks = :remarks, updated_at = :updated_at WHERE id = :id',
                $params
            );
            flash('success', t('arenas.flash.event_arena_updated'));
        } else {
            $existing = db_first('SELECT id FROM event_arenas WHERE event_id = :event AND arena_id = :arena', ['event' => $eventId, 'arena' => $arenaId]);
            if ($existing) {
                flash('error', t('arenas.validation.event_arena_duplicate'));
                header('Location: arenas.php' . ($eventArenaId ? '?edit_event_arena=' . $eventArenaId : ''));
                exit;
            }

            $params['blocked_times'] = json_encode([], JSON_THROW_ON_ERROR);
            $params['created_at'] = $now;
            $params['updated_at'] = $now;
            db_execute(
                'INSERT INTO event_arenas (event_id, arena_id, display_name, temp_surface, warmup_arena_id, remarks, blocked_times, created_at, updated_at) '
                . 'VALUES (:event_id, :arena_id, :display_name, :temp_surface, :warmup_arena_id, :remarks, :blocked_times, :created_at, :updated_at)',
                $params
            );
            flash('success', t('arenas.flash.event_arena_created'));
        }

        header('Location: arenas.php');
        exit;
    }

    if ($action === 'delete_event_arena') {
        $eventArenaId = (int) ($_POST['event_arena_id'] ?? 0);
        if ($eventArenaId > 0) {
            db_execute('DELETE FROM event_arenas WHERE id = :id', ['id' => $eventArenaId]);
            flash('success', t('arenas.flash.event_arena_deleted'));
        }
        header('Location: arenas.php');
        exit;
    }

    if ($action === 'create_availability') {
        $arenaId = (int) ($_POST['arena_id'] ?? 0);
        $start = trim((string) ($_POST['start_time'] ?? ''));
        $end = trim((string) ($_POST['end_time'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'blocked'));
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($arenaId <= 0 || $start === '' || $end === '') {
            flash('error', t('arenas.validation.availability_required'));
            header('Location: arenas.php');
            exit;
        }

        $startTimestamp = strtotime($start);
        $endTimestamp = strtotime($end);
        if ($startTimestamp === false || $endTimestamp === false || $endTimestamp <= $startTimestamp) {
            flash('error', t('arenas.validation.availability_range'));
            header('Location: arenas.php');
            exit;
        }

        $status = in_array($status, ['blocked', 'maintenance', 'available'], true) ? $status : 'blocked';

        db_execute(
            'INSERT INTO arena_availability (arena_id, start_time, end_time, reason, status, notes, created_at, updated_at) '
            . 'VALUES (:arena_id, :start_time, :end_time, :reason, :status, :notes, :created_at, :updated_at)',
            [
                'arena_id' => $arenaId,
                'start_time' => $start,
                'end_time' => $end,
                'reason' => $reason !== '' ? $reason : null,
                'status' => $status,
                'notes' => $notes !== '' ? $notes : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        flash('success', t('arenas.flash.availability_created'));
        header('Location: arenas.php');
        exit;
    }

    if ($action === 'delete_availability') {
        $availabilityId = (int) ($_POST['availability_id'] ?? 0);
        if ($availabilityId > 0) {
            db_execute('DELETE FROM arena_availability WHERE id = :id', ['id' => $availabilityId]);
            flash('success', t('arenas.flash.availability_deleted'));
        }
        header('Location: arenas.php');
        exit;
    }

    header('Location: arenas.php');
    exit;
}

$editLocationId = isset($_GET['edit_location']) ? (int) $_GET['edit_location'] : 0;
$editArenaId = isset($_GET['edit_arena']) ? (int) $_GET['edit_arena'] : 0;
$editEventArenaId = isset($_GET['edit_event_arena']) ? (int) $_GET['edit_event_arena'] : 0;

$editLocation = $editLocationId ? db_first('SELECT * FROM locations WHERE id = :id', ['id' => $editLocationId]) : null;
$editArena = $editArenaId ? db_first('SELECT * FROM arenas WHERE id = :id', ['id' => $editArenaId]) : null;
$editEventArena = $editEventArenaId ? db_first('SELECT * FROM event_arenas WHERE id = :id', ['id' => $editEventArenaId]) : null;

$locations = db_all('SELECT * FROM locations ORDER BY name');
$arenas = db_all('SELECT a.*, l.name AS location_name FROM arenas a LEFT JOIN locations l ON l.id = a.location_id ORDER BY a.name');
$events = db_all('SELECT id, title, start_date, end_date FROM events ORDER BY COALESCE(start_date, "9999-12-31") DESC, title');

$eventArenaRows = db_all(
    'SELECT ea.*, a.name AS arena_name, a.type AS arena_type, a.surface AS arena_surface, a.length_m, a.width_m, a.covered, a.lighting, a.drainage, a.capacity, l.name AS location_name, e.title AS event_title '
    . 'FROM event_arenas ea '
    . 'JOIN arenas a ON a.id = ea.arena_id '
    . 'LEFT JOIN locations l ON l.id = a.location_id '
    . 'JOIN events e ON e.id = ea.event_id '
    . 'ORDER BY e.start_date DESC, e.title, COALESCE(ea.display_name, a.name)'
);

$eventArenasByEvent = [];
foreach ($eventArenaRows as $row) {
    $eventId = (int) $row['event_id'];
    if (!isset($eventArenasByEvent[$eventId])) {
        $eventArenasByEvent[$eventId] = [];
    }
    $eventArenasByEvent[$eventId][] = arenas_format_event_arena($row);
}

$availabilityRows = db_all(
    'SELECT av.*, a.name AS arena_name FROM arena_availability av JOIN arenas a ON a.id = av.arena_id ORDER BY av.start_time DESC LIMIT 50'
);

foreach ($arenas as &$arena) {
    $summary = arenas_format_arena_summary($arena);
    $arena['summary'] = $summary['summary'];
}
unset($arena);

foreach ($eventArenaRows as &$row) {
    $row['option'] = arenas_format_event_arena($row);
}
unset($row);

render_page('arenas.tpl', [
    'title' => t('arenas.title'),
    'page' => 'arenas',
    'locations' => $locations,
    'arenas' => $arenas,
    'events' => $events,
    'eventArenaRows' => $eventArenaRows,
    'eventArenasByEvent' => $eventArenasByEvent,
    'availability' => $availabilityRows,
    'editLocation' => $editLocation,
    'editArena' => $editArena,
    'editEventArena' => $editEventArena,
]);
