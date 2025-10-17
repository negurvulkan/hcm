<?php
require __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Services\InstanceConfiguration;
use DateTimeImmutable;

header('Content-Type: application/json');

if (!App::has('config') || !App::has('pdo')) {
    http_response_code(503);
    echo json_encode(['status' => 'offline', 'message' => t('system.config.not_loaded')]);
    exit;
}

$pdo = App::get('pdo');
$instance = App::has('instance') ? App::get('instance') : new InstanceConfiguration($pdo);

$response = [
    'status' => 'ok',
    'timestamp' => (new DateTimeImmutable())->format('c'),
    'role' => $instance->get('instance_role'),
    'mode' => $instance->get('operation_mode'),
    'read_only' => !$instance->canWrite(),
    'version' => InstanceConfiguration::VERSION,
];

echo json_encode($response, JSON_PRETTY_PRINT);
