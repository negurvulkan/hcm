<?php

declare(strict_types=1);

require __DIR__ . '/auth.php';

use App\Core\Csrf;
use App\Modules\LayoutEditor\Exceptions\LayoutEditorException;

header('Content-Type: application/json');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$path = parse_url($uri, PHP_URL_PATH) ?? '/';
if ($scriptName !== '' && str_starts_with($path, $scriptName)) {
    $path = substr($path, strlen($scriptName));
}
$path = '/' . ltrim($path, '/');
if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}

$routes = require __DIR__ . '/routes/api.php';
$matched = null;
$params = [];

foreach ($routes as $route) {
    $routeMethod = strtoupper((string) ($route['method'] ?? 'GET'));
    if ($routeMethod !== $method) {
        continue;
    }

    $routePath = (string) ($route['path'] ?? '/');
    $pattern = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', rtrim($routePath, '/')) . '$#';
    if ($pattern === '#^$#') {
        $pattern = '#^/$#';
    }

    if (preg_match($pattern, $path === '' ? '/' : $path, $matches)) {
        $matched = $route;
        foreach ($matches as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $params[$key] = $value;
        }
        break;
    }
}

if ($matched === null) {
    respond_error('ROUTE_NOT_FOUND', 'Route nicht gefunden.', 404);
    return;
}

$user = auth_require($matched['permission'] ?? 'dashboard');

$payload = [];
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $payload = parse_json_payload();
}

if (!empty($matched['csrf'])) {
    $token = $payload['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Csrf::check($token)) {
        respond_error('CSRF_INVALID', 'CSRF-Prüfung fehlgeschlagen.', 419);
        return;
    }
}

if (!empty($matched['write'])) {
    require_write_access('layout_editor', ['json' => true]);
}

$handler = $matched['handler'] ?? null;
if (is_array($handler) && isset($handler[0], $handler[1]) && is_string($handler[0])) {
    $instance = new $handler[0]();
    $handler = [$instance, $handler[1]];
}

if (!is_callable($handler)) {
    respond_error('HANDLER_INVALID', 'Handler nicht gefunden.', 500);
    return;
}

try {
    $result = $handler([
        'params' => $params,
        'payload' => $payload,
        'query' => $_GET,
        'user' => $user,
    ]);
    respond_ok(is_array($result) ? $result : []);
} catch (LayoutEditorException $exception) {
    respond_error($exception->errorCode(), $exception->getMessage(), $exception->status());
} catch (\Throwable $exception) {
    respond_error('EXCEPTION', $exception->getMessage(), 500);
}

/**
 * @return array<string, mixed>
 */
function parse_json_payload(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (\Throwable) {
        return [];
    }

    return is_array($decoded) ? $decoded : [];
}

function respond_ok(array $payload = []): void
{
    echo json_encode(array_merge([
        'status' => 'ok',
        'csrf' => csrf_token(),
    ], $payload), JSON_UNESCAPED_UNICODE);
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
        'csrf' => csrf_token(),
    ], JSON_UNESCAPED_UNICODE);
}
