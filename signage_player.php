<?php
require __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Core\SmartyView;
use App\Signage\SignageRepository;

$token = (string) ($_GET['token'] ?? '');
if ($token === '') {
    http_response_code(400);
    echo 'Token missing.';
    return;
}

$repository = new SignageRepository();
$state = $repository->resolveDisplayState($token);

if (function_exists('app_view')) {
    $view = app_view();
} else {
    $view = App::get('view');
    if (!$view instanceof SmartyView) {
        throw new \RuntimeException('View layer is not available.');
    }
}
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
