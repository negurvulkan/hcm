<?php
require __DIR__ . '/auth.php';

use App\Core\Csrf;
use App\Signage\Exceptions\NotFoundException;
use App\Signage\Exceptions\ValidationException;
use App\Signage\SignageApiHandler;
use App\Signage\SignageRepository;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = strtolower((string) ($_GET['action'] ?? ''));

$repository = new SignageRepository();
$handler = new SignageApiHandler($repository);

if ($action === 'player_state' && $method === 'GET') {
    $token = (string) ($_GET['token'] ?? '');
    if ($token === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'code' => 'TOKEN_REQUIRED', 'message' => 'Display-Token erforderlich.'], JSON_UNESCAPED_UNICODE);
        return;
    }
    echo json_encode($repository->resolveDisplayState($token), JSON_UNESCAPED_UNICODE);
    return;
}

if ($action === 'player_heartbeat' && $method === 'POST') {
    $payload = parse_json_payload();
    $token = (string) ($payload['token'] ?? '');
    if ($token === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'code' => 'TOKEN_REQUIRED', 'message' => 'Display-Token erforderlich.'], JSON_UNESCAPED_UNICODE);
        return;
    }
    $display = $repository->getDisplayByToken($token);
    if (!$display) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'code' => 'DISPLAY_NOT_FOUND'], JSON_UNESCAPED_UNICODE);
        return;
    }
    $repository->touchDisplay((int) $display['id'], $payload['hardware'] ?? null);
    echo json_encode(['status' => 'ok', 'heartbeat_at' => (new \DateTimeImmutable('now'))->format('c')], JSON_UNESCAPED_UNICODE);
    return;
}

$user = auth_require('signage');

if ($method === 'GET') {
    $eventId = event_active_id();
    $layouts = $repository->listLayouts($eventId);
    $displays = $repository->listDisplays();
    $playlists = $repository->listPlaylists();
    echo json_encode([
        'status' => 'ok',
        'layouts' => $layouts,
        'displays' => $displays,
        'playlists' => $playlists,
        'csrf' => csrf_token(),
    ], JSON_UNESCAPED_UNICODE);
    return;
}

if ($method !== 'POST') {
    respond_error('METHOD_NOT_ALLOWED', 'HTTP-Methode nicht unterstützt.', 405);
    return;
}

$payload = parse_json_payload();
if (!Csrf::check($payload['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    respond_error('CSRF_INVALID', 'CSRF-Prüfung fehlgeschlagen.', 419);
    return;
}

$writeActions = ['create_layout', 'update_layout', 'publish_layout', 'duplicate_layout', 'delete_layout', 'register_display', 'update_display', 'delete_display', 'save_playlist', 'delete_playlist'];
if (in_array($action, $writeActions, true)) {
    require_write_access('signage', ['json' => true]);
}

try {
    $result = $handler->perform($action, $payload, ['user_id' => (int) $user['id']]);
    respond_ok($result);
    return;
} catch (NotFoundException $exception) {
    respond_error($exception->errorCode(), $exception->getMessage(), 404);
    return;
} catch (ValidationException $exception) {
    $status = $exception->errorCode() === 'ACTION_UNKNOWN' ? 404 : 422;
    respond_error($exception->errorCode(), $exception->getMessage(), $status);
    return;
} catch (Throwable $exception) {
    respond_error('EXCEPTION', $exception->getMessage(), 400);
}

function parse_json_payload(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }
    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : [];
    } catch (Throwable) {
        return [];
    }
}

function respond_ok(array $payload = []): void
{
    echo json_encode(array_merge(['status' => 'ok'], $payload), JSON_UNESCAPED_UNICODE);
}

function respond_error(string $code, string $message, ?int $status = null): void
{
    if ($status !== null) {
        http_response_code($status);
    }
    echo json_encode([
        'status' => 'error',
        'code' => $code,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
}
