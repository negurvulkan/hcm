<?php
require __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Services\InstanceConfiguration;
use DateTimeImmutable;
use PDO;
use PDOException;

header('Content-Type: application/json');

if (!App::has('config') || !App::has('pdo')) {
    http_response_code(503);
    echo json_encode(['status' => 'offline', 'message' => 'Konfiguration nicht geladen.']);
    exit;
}

$pdo = App::get('pdo');
$instance = App::has('instance') ? App::get('instance') : new InstanceConfiguration($pdo);
$storedToken = $instance->get('peer_api_token');
$tokenRequired = $storedToken !== null && $storedToken !== '';
$providedToken = null;

if (!empty($_SERVER['HTTP_AUTHORIZATION']) && stripos($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ') === 0) {
    $providedToken = trim(substr($_SERVER['HTTP_AUTHORIZATION'], 7));
} elseif (!empty($_SERVER['HTTP_X_API_TOKEN'])) {
    $providedToken = trim((string) $_SERVER['HTTP_X_API_TOKEN']);
} elseif (isset($_GET['token'])) {
    $providedToken = trim((string) $_GET['token']);
}

if ($tokenRequired) {
    if (!$providedToken || !hash_equals($storedToken, $providedToken)) {
        http_response_code(401);
        echo json_encode(['status' => 'unauthorized']);
        exit;
    }
}

try {
    $eventStmt = $pdo->query('SELECT * FROM events WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
    $event = $eventStmt ? $eventStmt->fetch(PDO::FETCH_ASSOC) : false;
} catch (PDOException) {
    $event = false;
}

$event = $event ?: null;
$eventId = $event['id'] ?? null;

$counts = [
    'entries' => 0,
    'classes' => 0,
    'results' => 0,
];

if ($eventId) {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM entries WHERE event_id = :event_id');
        $stmt->execute(['event_id' => $eventId]);
        $counts['entries'] = (int) $stmt->fetchColumn();
    } catch (PDOException) {
        $counts['entries'] = 0;
    }

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM classes WHERE event_id = :event_id');
        $stmt->execute(['event_id' => $eventId]);
        $counts['classes'] = (int) $stmt->fetchColumn();
    } catch (PDOException) {
        $counts['classes'] = 0;
    }

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id WHERE e.event_id = :event_id');
        $stmt->execute(['event_id' => $eventId]);
        $counts['results'] = (int) $stmt->fetchColumn();
    } catch (PDOException) {
        $counts['results'] = 0;
    }
}

$response = [
    'status' => 'ok',
    'timestamp' => (new DateTimeImmutable())->format('c'),
    'role' => $instance->get('instance_role'),
    'mode' => $instance->get('operation_mode'),
    'read_only' => !$instance->canWrite(),
    'turnier_id' => $eventId,
    'turnier_name' => $event['title'] ?? null,
    'counts' => $counts,
    'last_sync' => $instance->get('peer_last_dry_run_at'),
];

echo json_encode($response, JSON_PRETTY_PRINT);
