<?php
use App\Core\App;
use App\Core\Database;
use App\Core\SmartyView;

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

$configFile = __DIR__ . '/../config/app.php';
if (!is_file($configFile)) {
    return;
}

$config = require $configFile;

App::set('config', $config);
App::set('pdo', Database::connect($config['db'] ?? []));
$view = new SmartyView(__DIR__ . '/../templates');
$view->share('appName', $config['app']['name'] ?? 'Turnier-App');
App::set('view', $view);
