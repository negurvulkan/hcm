<?php
require __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Setup\Updater;

if (!App::has('config')) {
    fwrite(STDERR, "Keine Konfiguration gefunden. Bitte zuerst setup.php ausführen." . PHP_EOL);
    exit(1);
}

$config = App::get('config');
$dbConfig = $config['db'] ?? [];

try {
    Updater::run($dbConfig, static function (string $message): void {
        echo $message . PHP_EOL;
    });
    echo 'Update abgeschlossen.' . PHP_EOL;
} catch (\Throwable $exception) {
    fwrite(STDERR, 'Update fehlgeschlagen: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
