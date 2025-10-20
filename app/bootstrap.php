<?php
use App\Core\App;
use App\Core\Database;
use App\Core\SmartyView;
use App\I18n\LocaleManager;
use App\I18n\Translator;
use App\Services\InstanceConfiguration;
use App\Setup\Updater;

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = __DIR__ . '/' . str_replace('App\\', '', $class) . '.php';
        $path = str_replace('\\', '/', $path);
        if (is_file($path)) {
            require $path;
        }
    }
});

require_once __DIR__ . '/helpers/i18n.php';

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

if (App::has('pdo')) {
    $driver = $config['db']['driver'] ?? 'sqlite';
    Updater::runOnConnection(App::get('pdo'), $driver);
}
$localeManager = new LocaleManager($config['app']['locales'] ?? ['de', 'en'], $config['app']['default_locale'] ?? 'de');
$currentLocale = $localeManager->detect();
$translator = new Translator($currentLocale, $config['app']['fallback_locale'] ?? 'de', $config['app']['lang_path'] ?? (__DIR__ . '/../lang'));
App::set('locale_manager', $localeManager);
App::set('locale', $currentLocale);
App::set('translator', $translator);
$view = new SmartyView(__DIR__ . '/../templates');
$view->share('appName', $config['app']['name'] ?? 'Turniermanagement V2');
$view->share('currentLocale', $currentLocale);
$view->share('availableLocales', $localeManager->supported());
$view->share('translations', $translator->all());
if (App::has('pdo')) {
    $instance = new InstanceConfiguration(App::get('pdo'));
    App::set('instance', $instance);
    $view->share('instance', $instance->viewContext());
}
App::set('view', $view);
