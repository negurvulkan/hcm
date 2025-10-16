<?php
declare(strict_types=1);

use App\Services\InstanceConfiguration;

require __DIR__ . '/../app/Services/InstanceConfiguration.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE system_settings (setting_key TEXT PRIMARY KEY, value TEXT, updated_at TEXT NOT NULL)');
$pdo->exec('CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, is_active INTEGER)');
$pdo->exec('CREATE TABLE classes (id INTEGER PRIMARY KEY AUTOINCREMENT, event_id INTEGER, label TEXT)');
$pdo->exec('CREATE TABLE entries (id INTEGER PRIMARY KEY AUTOINCREMENT, event_id INTEGER, class_id INTEGER, person_id INTEGER, horse_id INTEGER, status TEXT, created_at TEXT)');
$pdo->exec('CREATE TABLE startlist_items (id INTEGER PRIMARY KEY AUTOINCREMENT, class_id INTEGER, entry_id INTEGER)');
$pdo->exec('CREATE TABLE results (id INTEGER PRIMARY KEY AUTOINCREMENT, startlist_id INTEGER)');

$pdo->exec("INSERT INTO events (id, title, is_active) VALUES (1, 'Demo', 1)");
$pdo->exec("INSERT INTO classes (id, event_id, label) VALUES (1, 1, 'Test')");
$pdo->exec("INSERT INTO entries (id, event_id, class_id, person_id, horse_id, status, created_at) VALUES (1, 1, 1, 1, 1, 'open', 'now')");
$pdo->exec("INSERT INTO startlist_items (id, class_id, entry_id) VALUES (1, 1, 1)");
$pdo->exec("INSERT INTO results (id, startlist_id) VALUES (1, 1)");

$config = new InstanceConfiguration($pdo);

if ($config->get('instance_role') !== InstanceConfiguration::ROLE_ONLINE) {
    throw new RuntimeException('Default role sollte ONLINE sein.');
}
if ($config->canWrite() !== true) {
    throw new RuntimeException('ONLINE sollte in PRE_TOURNAMENT schreibend sein.');
}

$changes = $config->save(['instance_role' => InstanceConfiguration::ROLE_MIRROR]);
if (($changes['after']['instance_role'] ?? null) !== InstanceConfiguration::ROLE_MIRROR) {
    throw new RuntimeException('Rollenwechsel nicht übernommen.');
}
if ($config->canWrite() !== false) {
    throw new RuntimeException('Mirror muss read-only sein.');
}

$config->save([
    'instance_role' => InstanceConfiguration::ROLE_LOCAL,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);
if ($config->canWrite() !== true) {
    throw new RuntimeException('Lokale Instanz im Turniermodus muss schreibend sein.');
}

$config->recordHealthResult(true, 'OK');
$health = $config->peerSummary();
if (($health['status'] ?? '') !== 'ok') {
    throw new RuntimeException('Health-Status sollte ok sein.');
}

$config->recordDryRun([
    'local' => ['entries' => 5, 'classes' => 2, 'results' => 1],
    'remote' => ['entries' => 4, 'classes' => 2, 'results' => 1],
    'differences' => ['entries' => 1, 'classes' => 0, 'results' => 0],
]);
$dryRun = $config->lastDryRun();
if (($dryRun['differences']['entries'] ?? null) !== 1) {
    throw new RuntimeException('Dry-Run Differenz nicht korrekt.');
}

$localSnapshot = $config->collectLocalCounts();
if (($localSnapshot['counts']['entries'] ?? 0) < 1) {
    throw new RuntimeException('Lokale Counts sollten Daten enthalten.');
}

echo "InstanceConfiguration tests passed\n";
