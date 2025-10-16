<?php
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/auth.php';

header('Content-Type: application/json');

$after = (int) ($_GET['after'] ?? 0);
$events = db_all('SELECT id, type, payload, created_at FROM notifications WHERE id > :after ORDER BY id', ['after' => $after]);
foreach ($events as &$event) {
    $event['payload'] = json_decode($event['payload'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
}
unset($event);

echo json_encode(['events' => $events], JSON_UNESCAPED_UNICODE);
