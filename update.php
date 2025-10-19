<?php
require __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Setup\Updater;

$writeError = static function (string $message): void {
    $line = $message . PHP_EOL;

    if (\defined('STDERR')) {
        \fwrite(STDERR, $line);

        return;
    }

    \error_log($message);
};

if (!App::has('config')) {
    $writeError(t('cli.update.config_missing'));
    exit(1);
}

$config = App::get('config');
$dbConfig = $config['db'] ?? [];

try {
    Updater::run($dbConfig, static function (string $message): void {
        echo $message . PHP_EOL;
    });
    echo t('cli.update.done') . PHP_EOL;
} catch (\Throwable $exception) {
    $writeError(t('cli.update.failed', ['message' => $exception->getMessage()]));
    exit(1);
}
