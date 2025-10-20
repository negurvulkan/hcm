<?php
require __DIR__ . '/app/bootstrap.php';

use App\Signage\SignageRepository;

$token = (string) ($_GET['token'] ?? '');
if ($token === '') {
    http_response_code(400);
    echo 'Token missing.';
    return;
}

$repository = new SignageRepository();
$state = $repository->resolveDisplayState($token);

$view = app_view();
if (($state['status'] ?? '') !== 'ok') {
    echo $view->render('signage/player.tpl', [
        'title' => 'Digital Signage',
        'state' => $state,
        'token' => $token,
    ]);
    return;
}

$displayName = $state['display']['name'] ?? 'Display';

echo $view->render('signage/player.tpl', [
    'title' => $displayName,
    'state' => $state,
    'token' => $token,
]);
