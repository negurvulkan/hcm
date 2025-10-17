<?php
require __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Setup\Updater;

if (!App::has('config')) {
    fwrite(STDERR, t('cli.update.config_missing') . PHP_EOL);
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
    fwrite(STDERR, t('cli.update.failed', ['message' => $exception->getMessage()]) . PHP_EOL);
    exit(1);
}
