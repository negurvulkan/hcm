<?php
require __DIR__ . '/auth.php';

use App\Core\Csrf;
use App\LayoutEditor\LayoutEditorController;

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = strtolower((string) ($_GET['action'] ?? 'meta'));

$controller = new LayoutEditorController();
$user = auth_require('layout_editor');

if ($method === 'GET') {
    $meta = $controller->meta();
    $meta['csrf'] = csrf_token();
    echo json_encode($meta, JSON_UNESCAPED_UNICODE);
    return;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 'METHOD_NOT_ALLOWED',
        'message' => 'HTTP-Methode nicht unterstützt.',
        'csrf' => csrf_token(),
    ], JSON_UNESCAPED_UNICODE);
    return;
}

$raw = file_get_contents('php://input') ?: '';
$payload = [];
if ($raw !== '') {
    try {
        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        $payload = [];
    }
}

if (!Csrf::check($payload['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(419);
    echo json_encode([
        'status' => 'error',
        'code' => 'CSRF_INVALID',
        'message' => 'CSRF-Prüfung fehlgeschlagen.',
        'csrf' => csrf_token(),
    ], JSON_UNESCAPED_UNICODE);
    return;
}

if ($action === 'render') {
    $template = (string) ($payload['template'] ?? '');
    if ($template === '') {
        echo json_encode([
            'status' => 'ok',
            'html' => '',
            'csrf' => csrf_token(),
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $result = $controller->render($template);
    $result['csrf'] = csrf_token();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    return;
}

http_response_code(404);
echo json_encode([
    'status' => 'error',
    'code' => 'ACTION_UNKNOWN',
    'message' => 'Unbekannte Aktion.',
    'csrf' => csrf_token(),
], JSON_UNESCAPED_UNICODE);
