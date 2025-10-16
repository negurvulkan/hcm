<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/helpers/sync.php';

use App\Core\App;
use App\Core\RateLimiter;
use App\Services\InstanceConfiguration;
use App\Sync\ChangeSet;
use App\Sync\Scopes;
use App\Sync\Since;
use App\Sync\SyncCursor;
use App\Sync\SyncException;
use App\Sync\SyncRequest;
use DateTimeImmutable;
use Throwable;

header('Content-Type: application/json');

try {
    if (!App::has('instance') || !App::has('pdo')) {
        throw new SyncException('SERVICE_UNAVAILABLE', 'Instanz nicht initialisiert.', 503);
    }

    $instance = App::get('instance');
    if (!$instance instanceof InstanceConfiguration) {
        throw new SyncException('SERVICE_UNAVAILABLE', 'Konfiguration fehlt.', 503);
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = resolve_action();

    if ($action === 'info' && $method === 'GET') {
        respond(200, sync_info_payload($instance));
        return;
    }

    if ($method !== 'POST') {
        throw new SyncException('NOT_FOUND', 'Endpunkt nicht gefunden.', 404);
    }

    enforce_token($instance);

    $payload = parse_json_body();

    switch ($action) {
        case 'diff':
            $response = handle_diff($payload, $instance);
            respond(200, $response);
            return;
        case 'pull':
            rate_limit('pull');
            $response = handle_pull($payload, $instance);
            respond(200, $response);
            return;
        case 'push':
            rate_limit('push');
            $response = handle_push($payload, $instance);
            respond(200, $response);
            return;
        case 'ack':
            $response = handle_ack($payload);
            respond(200, $response);
            return;
        default:
            throw new SyncException('NOT_FOUND', 'Endpunkt nicht gefunden.', 404);
    }
} catch (SyncException $exception) {
    http_response_code($exception->getStatus());
    echo json_encode($exception->toArray(), JSON_UNESCAPED_UNICODE);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 'INTERNAL_ERROR',
        'message' => $throwable->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

function resolve_action(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $path = trim($uri, '/');
    if (str_starts_with($path, 'sync/')) {
        $path = substr($path, strlen('sync/'));
    } elseif ($path === 'sync') {
        $path = '';
    }

    if ($path === '' && isset($_GET['action'])) {
        return strtolower((string) $_GET['action']);
    }

    if ($path === '') {
        return '';
    }

    $segments = explode('/', $path);
    return strtolower((string) ($segments[0] ?? ''));
}

function parse_json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new SyncException('SCHEMA_VALIDATION_FAILED', 'Ungültiger JSON-Body.');
    }

    return $decoded;
}

function enforce_token(InstanceConfiguration $instance): void
{
    $required = $instance->get('peer_api_token');
    if ($required === null || $required === '') {
        return;
    }

    $provided = null;
    $headers = getallheaders();
    if ($headers) {
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if ($auth && stripos($auth, 'Bearer ') === 0) {
            $provided = trim(substr($auth, 7));
        }
        if (!$provided) {
            $provided = $headers['X-API-Token'] ?? $headers['x-api-token'] ?? null;
        }
    }

    if (!$provided && isset($_SERVER['HTTP_X_API_TOKEN'])) {
        $provided = $_SERVER['HTTP_X_API_TOKEN'];
    }

    if (!$provided && isset($_GET['token'])) {
        $provided = (string) $_GET['token'];
    }

    if (!$provided || !hash_equals($required, (string) $provided)) {
        throw new SyncException('UNAUTHORIZED', 'Peer-Token ungültig.', 401);
    }
}

function sync_info_payload(InstanceConfiguration $instance): array
{
    $counts = sync_entity_counts();
    $cursor = getSyncCursor();
    $pdo = App::get('pdo');
    $lastSync = null;
    if ($pdo) {
        $stmt = $pdo->prepare('SELECT value FROM system_settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute(['key' => 'sync_last_completed_at']);
        $lastSync = $stmt->fetchColumn() ?: null;
    }

    $local = $instance->collectLocalCounts();
    $event = $local['event'] ?? null;

    return [
        'instance_role' => $instance->get('instance_role'),
        'operation_mode' => $instance->get('operation_mode'),
        'version' => InstanceConfiguration::VERSION,
        'turnier_id' => $event['id'] ?? null,
        'last_sync_at' => $lastSync,
        'sync_cursor' => $cursor->value(),
        'entity_counts' => $counts,
    ];
}

function handle_diff(array $payload, InstanceConfiguration $instance): array
{
    $since = Since::fromMixed($payload['since'] ?? null);
    $scopes = new Scopes(isset($payload['scopes']) && is_array($payload['scopes']) ? $payload['scopes'] : null);

    $start = microtime(true);
    $changes = exportChanges($since, $scopes);
    $duration = (int) ((microtime(true) - $start) * 1000);

    sync_log_operation('outbound', 'diff', $scopes->toArray(), 'completed', 'Diff ausgeführt.', [
        'entities' => array_map(static fn (array $items) => count($items), $changes->all()),
    ], $duration);

    return $changes->toArray();
}

function handle_pull(array $payload, InstanceConfiguration $instance): array
{
    $scopes = $payload['entities'] ?? [];
    if (!is_array($scopes)) {
        throw new SyncException('SCHEMA_VALIDATION_FAILED', 'entities muss ein Objekt sein.');
    }

    $start = microtime(true);
    $changeSet = sync_pull_entities($scopes);
    $duration = (int) ((microtime(true) - $start) * 1000);

    sync_log_operation('outbound', 'pull', array_keys($scopes), 'completed', 'Pull ausgeliefert.', [
        'entities' => array_map(static fn (array $items) => count($items), $changeSet->all()),
    ], $duration);

    return $changeSet->toArray();
}

function handle_push(array $payload, InstanceConfiguration $instance): array
{
    $changeSet = ChangeSet::fromPayload($payload);
    $scopes = $changeSet->scopes();
    enforceReadWritePolicy(new SyncRequest('push', 'POST', true, $scopes));

    $validation = validateDelta($changeSet);
    if (!$validation->isValid()) {
        throw new SyncException('SCHEMA_VALIDATION_FAILED', json_encode($validation->toArray(), JSON_UNESCAPED_UNICODE), 422);
    }

    $start = microtime(true);
    $report = importChanges($changeSet);
    $duration = (int) ((microtime(true) - $start) * 1000);

    $accepted = array_sum(array_map('count', $report->toArray()['accepted'] ?? []));
    $rejected = array_sum(array_map('count', $report->toArray()['rejected'] ?? []));
    $transactionId = sync_create_transaction('inbound', 'push', $scopes, $report->toArray());

    sync_log_operation('inbound', 'push', $scopes, $report->hasErrors() ? 'error' : 'completed', 'Push verarbeitet.', [
        'accepted' => $accepted,
        'rejected' => $rejected,
    ], $duration, $transactionId);

    return [
        'transaction_id' => $transactionId,
        'report' => $report->toArray(),
    ];
}

function handle_ack(array $payload): array
{
    $transactionId = (string) ($payload['transaction_id'] ?? '');
    if ($transactionId === '') {
        throw new SyncException('INVALID_CURSOR', 'transaction_id fehlt.');
    }

    $acknowledged = sync_acknowledge($transactionId);
    sync_log_operation('inbound', 'ack', [$transactionId], $acknowledged ? 'completed' : 'skipped', 'Transaktion bestätigt.', [], null, $transactionId);

    return ['acknowledged' => $acknowledged];
}

function rate_limit(string $operation): void
{
    $actor = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $limiter = new RateLimiter('sync:' . $operation . ':' . $actor, 20, 60);
    if ($limiter->tooManyAttempts()) {
        throw new SyncException('RATE_LIMITED', 'Zu viele Aufrufe.', 429);
    }
    $limiter->hit();
}

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}

if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}
