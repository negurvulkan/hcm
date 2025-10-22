<?php
require __DIR__ . '/auth.php';

use App\Core\Csrf;
use App\Party\PartyRepository;

if (!function_exists('stations_decode_json')) {
    function stations_decode_json(?string $value, array $default): array
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }
}

if (!function_exists('stations_store_location')) {
    function stations_store_location(array $input): string
    {
        $location = [
            'label' => trim((string) ($input['location_label'] ?? '')),
            'details' => trim((string) ($input['location_details'] ?? '')),
            'emergency_url' => trim((string) ($input['emergency_url'] ?? '')),
        ];
        return json_encode($location, JSON_THROW_ON_ERROR);
    }
}

if (!function_exists('stations_store_equipment')) {
    function stations_store_equipment(string $equipment): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $equipment);
        $items = array_values(
            array_filter(
                array_map('trim', explode("\n", $normalized)),
                static fn ($line) => $line !== ''
            )
        );
        return json_encode($items, JSON_THROW_ON_ERROR);
    }
}


if (!function_exists('stations_ensure_token')) {
    function stations_ensure_token(int $stationId, int $userId, bool $forceNew = false): array
    {
        $now = new DateTimeImmutable();
        $current = db_first(
            'SELECT * FROM tokens WHERE station_id = :station AND purpose = :purpose ORDER BY expires_at DESC, id DESC LIMIT 1',
            [
                'station' => $stationId,
                'purpose' => 'checkin',
            ]
        );

        if ($current && !$forceNew) {
            $expiresAt = $current['expires_at'] ?? null;
            if ($expiresAt === null || strtotime($expiresAt) > $now->getTimestamp() + 60) {
                return $current;
            }
        }

        $token = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $expires = $now->modify('+8 hours')->format('c');

        db_execute(
            'INSERT INTO tokens (token, station_id, shift_id, purpose, expires_at, nonce, created_by, created_at) '
            . 'VALUES (:token, :station, NULL, :purpose, :expires, :nonce, :created_by, :created)',
            [
                'token' => $token,
                'station' => $stationId,
                'purpose' => 'checkin',
                'expires' => $expires,
                'nonce' => $nonce,
                'created_by' => $userId,
                'created' => $now->format('c'),
            ]
        );

        return db_first('SELECT * FROM tokens WHERE token = :token', ['token' => $token]);
    }
}

$user = auth_require('stations');
$pdo = app_pdo();
$partyRepository = new PartyRepository($pdo);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editStation = null;
if ($editId > 0) {
    $editStation = db_first('SELECT * FROM stations WHERE id = :id', ['id' => $editId]);
    if ($editStation) {
        $editStation['location'] = stations_decode_json($editStation['location_json'], [
            'label' => '',
            'details' => '',
            'emergency_url' => '',
        ]);
        $editStation['equipment'] = stations_decode_json($editStation['equipment_json'], []);
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('stations.validation.csrf'));
        header('Location: stations.php');
        exit;
    }

    require_write_access('stations');
    $action = $_POST['action'] ?? '';
    $now = (new DateTimeImmutable())->format('c');

    if ($action === 'create' || $action === 'update') {
        $stationId = (int) ($_POST['station_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $eventId = (int) ($_POST['event_id'] ?? 0) ?: null;
        $description = trim((string) ($_POST['description'] ?? ''));
        $responsibleId = (int) ($_POST['responsible_person_id'] ?? 0) ?: null;
        $active = isset($_POST['active']) && (string) $_POST['active'] === '1' ? 1 : 0;

        if ($name === '') {
            $errors[] = t('stations.validation.name_required');
        }

        $locationJson = '[]';
        $equipmentJson = '[]';
        try {
            $locationJson = stations_store_location($_POST);
            $equipmentJson = stations_store_equipment((string) ($_POST['equipment'] ?? ''));
        } catch (Throwable) {
            $errors[] = t('stations.validation.json_failed');
        }

        if (!$errors) {
            if ($action === 'update' && $stationId > 0) {
                db_execute(
                    'UPDATE stations SET event_id = :event_id, name = :name, description = :description, location_json = :location,'
                    . ' responsible_person_id = :responsible, equipment_json = :equipment, active = :active, updated_at = :updated'
                    . ' WHERE id = :id',
                    [
                        'event_id' => $eventId,
                        'name' => $name,
                        'description' => $description !== '' ? $description : null,
                        'location' => $locationJson,
                        'responsible' => $responsibleId,
                        'equipment' => $equipmentJson,
                        'active' => $active,
                        'updated' => $now,
                        'id' => $stationId,
                    ]
                );
                flash('success', t('stations.flash.updated'));
                $refreshToken = isset($_POST['refresh_token']) && (string) $_POST['refresh_token'] === '1';
                if ($refreshToken) {
                    stations_ensure_token($stationId, (int) $user['id'], true);
                }
            } else {
                db_execute(
                    'INSERT INTO stations (event_id, name, description, location_json, responsible_person_id, equipment_json, active, created_at, updated_at) '
                    . 'VALUES (:event_id, :name, :description, :location, :responsible, :equipment, :active, :created, :created)',
                    [
                        'event_id' => $eventId,
                        'name' => $name,
                        'description' => $description !== '' ? $description : null,
                        'location' => $locationJson,
                        'responsible' => $responsibleId,
                        'equipment' => $equipmentJson,
                        'active' => $active,
                        'created' => $now,
                    ]
                );
                $newId = (int) $pdo->lastInsertId();
                stations_ensure_token($newId, (int) $user['id']);
                flash('success', t('stations.flash.created'));
            }
        }

        if ($errors) {
            foreach ($errors as $error) {
                flash('error', $error);
            }
        }

        header('Location: stations.php');
        exit;
    }

    if ($action === 'delete') {
        $stationId = (int) ($_POST['station_id'] ?? 0);
        if ($stationId > 0) {
            db_execute('DELETE FROM stations WHERE id = :id', ['id' => $stationId]);
            db_execute('DELETE FROM tokens WHERE station_id = :id', ['id' => $stationId]);
            flash('success', t('stations.flash.deleted'));
        }
        header('Location: stations.php');
        exit;
    }

    if ($action === 'toggle_active') {
        $stationId = (int) ($_POST['station_id'] ?? 0);
        $active = isset($_POST['set_active']) && (string) $_POST['set_active'] === '1' ? 1 : 0;
        if ($stationId > 0) {
            db_execute('UPDATE stations SET active = :active, updated_at = :updated WHERE id = :id', [
                'active' => $active,
                'updated' => $now,
                'id' => $stationId,
            ]);
            flash('success', $active ? t('stations.flash.activated') : t('stations.flash.deactivated'));
        }
        header('Location: stations.php');
        exit;
    }

    if ($action === 'regenerate_token') {
        $stationId = (int) ($_POST['station_id'] ?? 0);
        if ($stationId > 0) {
            stations_ensure_token($stationId, (int) $user['id'], true);
            flash('success', t('stations.flash.token_refreshed'));
        }
        header('Location: stations.php');
        exit;
    }
}

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'active' => $_GET['active'] ?? 'all',
    'event_id' => (int) ($_GET['event_id'] ?? 0),
    'responsible_id' => (int) ($_GET['responsible_id'] ?? 0),
];

$params = [];
$conditions = [];

if ($filters['q'] !== '') {
    $conditions[] = '(LOWER(s.name) LIKE :search OR LOWER(COALESCE(p.display_name, "")) LIKE :search)';
    $params['search'] = '%' . mb_strtolower($filters['q']) . '%';
}
if ($filters['active'] === '1') {
    $conditions[] = 's.active = 1';
} elseif ($filters['active'] === '0') {
    $conditions[] = 's.active = 0';
}
if ($filters['event_id'] > 0) {
    $conditions[] = 's.event_id = :event_id';
    $params['event_id'] = $filters['event_id'];
}
if ($filters['responsible_id'] > 0) {
    $conditions[] = 's.responsible_person_id = :responsible';
    $params['responsible'] = $filters['responsible_id'];
}

$sql = 'SELECT s.*, p.display_name AS responsible_name, e.title AS event_title,'
    . ' (
        SELECT token FROM tokens t WHERE t.station_id = s.id AND t.purpose = "checkin" ORDER BY t.expires_at DESC, t.id DESC LIMIT 1
      ) AS checkin_token,'
    . ' (
        SELECT expires_at FROM tokens t WHERE t.station_id = s.id AND t.purpose = "checkin" ORDER BY t.expires_at DESC, t.id DESC LIMIT 1
      ) AS checkin_expires_at'
    . ' FROM stations s'
    . ' LEFT JOIN parties p ON p.id = s.responsible_person_id'
    . ' LEFT JOIN events e ON e.id = s.event_id';
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY s.active DESC, s.name ASC';

$stations = db_all($sql, $params);

foreach ($stations as &$station) {
    $station['location'] = stations_decode_json($station['location_json'], [
        'label' => '',
        'details' => '',
        'emergency_url' => '',
    ]);
    $station['equipment'] = stations_decode_json($station['equipment_json'], []);
}
unset($station);

$events = db_all('SELECT id, title FROM events ORDER BY start_date DESC, title ASC');
$persons = $partyRepository->personOptions();

render_page('stations.tpl', [
    'titleKey' => 'stations.title',
    'page' => 'stations',
    'stations' => $stations,
    'persons' => $persons,
    'events' => $events,
    'filters' => $filters,
    'editStation' => $editStation,
]);
