<?php
use App\Core\App;
use App\Core\Database;
use App\Core\SmartyView;
use App\Services\InstanceConfiguration;

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = __DIR__ . '/' . str_replace('App\\', '', $class) . '.php';
        $path = str_replace('\\', '/', $path);
        if (is_file($path)) {
            require $path;
        }
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$key, $value] = array_map('trim', array_pad(explode('=', $line, 2), 2, ''));
        if ($key !== '') {
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

$configFile = __DIR__ . '/../config/app.php';
if (!is_file($configFile)) {
    return;
}

$config = require $configFile;

App::set('config', $config);
App::set('pdo', Database::connect($config['db'] ?? []));
$view = new SmartyView(__DIR__ . '/../templates');
$view->share('appName', $config['app']['name'] ?? 'Turniermanagement V2');
if (App::has('pdo')) {
    $instance = new InstanceConfiguration(App::get('pdo'));
    App::set('instance', $instance);
    $view->share('instance', $instance->viewContext());
}
App::set('view', $view);
