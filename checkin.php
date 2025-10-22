<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';

use App\Core\Csrf;
use App\Core\RateLimiter;

if (!function_exists('checkin_decode_json')) {
    function checkin_decode_json(?string $value, array $default): array
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }
}

$tokenValue = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
$tokenRow = null;
$station = null;
$tokenExpired = false;
$errors = [];

if ($tokenValue !== '') {
    $tokenRow = db_first(
        'SELECT t.*, s.name AS station_name, s.location_json, s.equipment_json, s.description AS station_description, s.id AS station_id '
        . 'FROM tokens t JOIN stations s ON s.id = t.station_id WHERE t.token = :token AND t.purpose = :purpose',
        [
            'token' => $tokenValue,
            'purpose' => 'checkin',
        ]
    );

    if ($tokenRow) {
        $expiresAt = $tokenRow['expires_at'] ?? null;
        if ($expiresAt !== null && strtotime($expiresAt) < time()) {
            $tokenExpired = true;
        }
        $station = [
            'id' => (int) $tokenRow['station_id'],
            'name' => $tokenRow['station_name'],
            'description' => $tokenRow['station_description'],
            'location' => checkin_decode_json($tokenRow['location_json'] ?? '{}', [
                'label' => '',
                'details' => '',
                'emergency_url' => '',
            ]),
            'equipment' => checkin_decode_json($tokenRow['equipment_json'] ?? '[]', []),
        ];
    } else {
        flash('error', t('stations.checkin.invalid_token'));
    }
} else {
    flash('error', t('stations.checkin.invalid_token'));
}

if (!isset($_SESSION['station_checkin_nonce'])) {
    $_SESSION['station_checkin_nonce'] = [];
}

$nonce = bin2hex(random_bytes(16));
if ($tokenValue !== '' && $tokenRow) {
    $stored = $_SESSION['station_checkin_nonce'][$tokenValue] ?? null;
    $tokenNonce = (string) ($tokenRow['nonce'] ?? '');
    if (!is_array($stored) || ($stored['token_nonce'] ?? '') !== $tokenNonce) {
        $stored = [
            'value' => $nonce,
            'token_nonce' => $tokenNonce,
        ];
        $_SESSION['station_checkin_nonce'][$tokenValue] = $stored;
    }
    $nonce = $stored['value'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenRow && !$tokenExpired) {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('stations.validation.csrf'));
        header('Location: checkin.php?token=' . rawurlencode($tokenValue));
        exit;
    }

    $nonceData = $_SESSION['station_checkin_nonce'][$tokenValue] ?? null;
    $postedNonce = (string) ($_POST['nonce'] ?? '');
    $tokenNonce = (string) ($tokenRow['nonce'] ?? '');
    if (!is_array($nonceData) || ($nonceData['token_nonce'] ?? '') !== $tokenNonce || $postedNonce === '' || !hash_equals($nonceData['value'], $postedNonce)) {
        flash('error', t('stations.checkin.nonce_error'));
        header('Location: checkin.php?token=' . rawurlencode($tokenValue));
        exit;
    }

    $limiterKey = 'station-checkin:' . hash('sha256', $tokenValue . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
    $limiter = new RateLimiter($limiterKey, 5, 60);
    $personLimiter = null;

    $action = $_POST['action'] ?? '';
    $shiftId = (int) ($_POST['shift_id'] ?? 0);
    $shift = null;
    if ($shiftId > 0) {
        $shift = db_first(
            'SELECT sh.*, pr.display_name AS person_name FROM shifts sh '
            . 'LEFT JOIN parties pr ON pr.id = sh.person_id WHERE sh.id = :id',
            ['id' => $shiftId]
        );
    }

    if ($shift) {
        $personId = (int) ($shift['person_id'] ?? 0);
        if ($personId > 0) {
            $personLimiter = new RateLimiter('station-checkin-person:' . $personId, 5, 60);
        }
    }

    if ($limiter->tooManyAttempts() || ($personLimiter && $personLimiter->tooManyAttempts())) {
        flash('error', t('stations.checkin.rate_limited'));
        header('Location: checkin.php?token=' . rawurlencode($tokenValue));
        exit;
    }
    $limiter->hit();
    if ($personLimiter) {
        $personLimiter->hit();
    }

    if (!$shift || (int) ($shift['station_id'] ?? 0) !== (int) $station['id']) {
        flash('error', t('stations.checkin.shift_conflict'));
        header('Location: checkin.php?token=' . rawurlencode($tokenValue));
        exit;
    }

    $now = (new DateTimeImmutable())->format('c');
    $personId = (int) ($shift['person_id'] ?? 0) ?: null;
    $status = $shift['status'] ?? 'open';

    if ($action === 'in') {
        if (!in_array($status, ['open', 'assigned'], true)) {
            flash('error', t('stations.checkin.shift_conflict'));
            header('Location: checkin.php?token=' . rawurlencode($tokenValue));
            exit;
        }
        db_execute(
            'INSERT INTO checkins (person_id, station_id, shift_id, type, ts, source, token_id, geo_json, created_at) '
            . 'VALUES (:person_id, :station_id, :shift_id, :type, :ts, :source, :token_id, NULL, :created)',
            [
                'person_id' => $personId,
                'station_id' => $station['id'],
                'shift_id' => $shiftId,
                'type' => 'IN',
                'ts' => $now,
                'source' => 'QR',
                'token_id' => $tokenRow['id'],
                'created' => $now,
            ]
        );
        $checkinId = (int) app_pdo()->lastInsertId();
        db_execute(
            'UPDATE shifts SET status = :status, token_id = :token_id, updated_at = :updated WHERE id = :id',
            [
                'status' => 'active',
                'token_id' => $tokenRow['id'],
                'updated' => $now,
                'id' => $shiftId,
            ]
        );
        db_execute('UPDATE tokens SET used_at = :used WHERE id = :id', ['used' => $now, 'id' => $tokenRow['id']]);
        audit_log('checkins', $checkinId, 'create', null, [
            'id' => $checkinId,
            'person_id' => $personId,
            'station_id' => $station['id'],
            'shift_id' => $shiftId,
            'type' => 'IN',
            'ts' => $now,
            'source' => 'QR',
            'token_id' => $tokenRow['id'],
        ]);
        audit_log('shifts', $shiftId, 'status_change', ['status' => $status], ['status' => 'active']);
        flash('success', t('stations.checkin.flash_in'));
    } elseif ($action === 'out') {
        if ($status !== 'active') {
            flash('error', t('stations.checkin.shift_conflict'));
            header('Location: checkin.php?token=' . rawurlencode($tokenValue));
            exit;
        }
        db_execute(
            'INSERT INTO checkins (person_id, station_id, shift_id, type, ts, source, token_id, geo_json, created_at) '
            . 'VALUES (:person_id, :station_id, :shift_id, :type, :ts, :source, :token_id, NULL, :created)',
            [
                'person_id' => $personId,
                'station_id' => $station['id'],
                'shift_id' => $shiftId,
                'type' => 'OUT',
                'ts' => $now,
                'source' => 'QR',
                'token_id' => $tokenRow['id'],
                'created' => $now,
            ]
        );
        $checkinId = (int) app_pdo()->lastInsertId();
        db_execute(
            'UPDATE shifts SET status = :status, token_id = :token_id, updated_at = :updated WHERE id = :id',
            [
                'status' => 'done',
                'token_id' => $tokenRow['id'],
                'updated' => $now,
                'id' => $shiftId,
            ]
        );
        db_execute('UPDATE tokens SET used_at = :used WHERE id = :id', ['used' => $now, 'id' => $tokenRow['id']]);
        audit_log('checkins', $checkinId, 'create', null, [
            'id' => $checkinId,
            'person_id' => $personId,
            'station_id' => $station['id'],
            'shift_id' => $shiftId,
            'type' => 'OUT',
            'ts' => $now,
            'source' => 'QR',
            'token_id' => $tokenRow['id'],
        ]);
        audit_log('shifts', $shiftId, 'status_change', ['status' => $status], ['status' => 'done']);
        flash('success', t('stations.checkin.flash_out'));
    }

    $newTokenNonce = bin2hex(random_bytes(16));
    db_execute('UPDATE tokens SET nonce = :nonce WHERE id = :id', [
        'nonce' => $newTokenNonce,
        'id' => $tokenRow['id'],
    ]);
    $_SESSION['station_checkin_nonce'][$tokenValue] = [
        'value' => bin2hex(random_bytes(16)),
        'token_nonce' => $newTokenNonce,
    ];
    header('Location: checkin.php?token=' . rawurlencode($tokenValue));
    exit;
}

$shifts = [];
$recentCheckins = [];
if ($station) {
    $shifts = db_all(
        'SELECT sh.*, pr.display_name AS person_name, hr.name AS role_name '
        . 'FROM shifts sh '
        . 'LEFT JOIN parties pr ON pr.id = sh.person_id '
        . 'LEFT JOIN helper_roles hr ON hr.id = sh.role_id '
        . 'WHERE sh.station_id = :station ORDER BY COALESCE(sh.starts_at, sh.created_at) ASC',
        ['station' => $station['id']]
    );
    $recentCheckins = db_all(
        'SELECT c.*, pr.display_name AS person_name FROM checkins c '
        . 'LEFT JOIN parties pr ON pr.id = c.person_id '
        . 'WHERE c.station_id = :station ORDER BY c.ts DESC LIMIT 10',
        ['station' => $station['id']]
    );
}

render_page('checkin.tpl', [
    'title' => $station ? t('stations.checkin.title', ['station' => $station['name']]) : t('stations.checkin.invalid_token'),
    'page' => 'stations',
    'station' => $station,
    'token' => $tokenValue,
    'nonce' => $nonce,
    'tokenValid' => (bool) $tokenRow,
    'tokenExpired' => $tokenExpired,
    'shifts' => $shifts,
    'recentCheckins' => $recentCheckins,
]);
