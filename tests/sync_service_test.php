<?php
declare(strict_types=1);

use App\Core\App;
use App\I18n\Translator;
use App\Services\InstanceConfiguration;
use App\Sync\ChangeSet;
use App\Sync\Scopes;
use App\Sync\Since;
use App\Sync\SyncCursor;
use App\Sync\SyncException;
use App\Sync\SyncRequest;

require __DIR__ . '/../app/Core/App.php';
require __DIR__ . '/../app/Services/InstanceConfiguration.php';
require __DIR__ . '/../app/I18n/Translator.php';
require __DIR__ . '/../app/helpers/i18n.php';
require __DIR__ . '/../app/Sync/SyncException.php';
require __DIR__ . '/../app/Sync/SyncCursor.php';
require __DIR__ . '/../app/Sync/Since.php';
require __DIR__ . '/../app/Sync/Scopes.php';
require __DIR__ . '/../app/Sync/ChangeSet.php';
require __DIR__ . '/../app/Sync/ImportReport.php';
require __DIR__ . '/../app/Sync/ValidationReport.php';
require __DIR__ . '/../app/Sync/SyncRequest.php';
require __DIR__ . '/../app/Sync/SyncRepository.php';
require __DIR__ . '/../app/Sync/SyncService.php';
require __DIR__ . '/../app/helpers/sync.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$schema = [
    'CREATE TABLE system_settings (setting_key TEXT PRIMARY KEY, value TEXT, updated_at TEXT NOT NULL)',
    'CREATE TABLE persons (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, phone TEXT, roles TEXT, club_id INTEGER, created_at TEXT, updated_at TEXT)',
    'CREATE TABLE clubs (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, short_name TEXT, updated_at TEXT)',
    'CREATE TABLE sync_state (id INTEGER PRIMARY KEY AUTOINCREMENT, scope TEXT, entity_id TEXT, version TEXT, version_epoch INTEGER, checksum TEXT, payload_meta TEXT, updated_at TEXT)',
    'CREATE UNIQUE INDEX idx_sync_state_scope_id ON sync_state (scope, entity_id)',
    'CREATE TABLE sync_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, direction TEXT, operation TEXT, scopes TEXT, counts TEXT, status TEXT, message TEXT, duration_ms INTEGER, actor TEXT, transaction_id TEXT, created_at TEXT)',
    'CREATE TABLE sync_transactions (id TEXT PRIMARY KEY, direction TEXT, operation TEXT, scopes TEXT, status TEXT, summary TEXT, created_at TEXT, acknowledged_at TEXT)',
];

foreach ($schema as $sql) {
    $pdo->exec($sql);
}

App::set('pdo', $pdo);
$translator = new Translator('de', 'de', __DIR__ . '/../lang');
App::set('translator', $translator);
App::set('locale', 'de');
$instance = new InstanceConfiguration($pdo);
$instance->save([
    'instance_role' => InstanceConfiguration::ROLE_LOCAL,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);
App::set('instance', $instance);

$cursor = getSyncCursor();
if (!$cursor instanceof SyncCursor) {
    throw new RuntimeException('Cursor sollte verfügbar sein.');
}

$changeSet = new ChangeSet([], InstanceConfiguration::ROLE_LOCAL);
$changeSet->add('clubs', [
    'id' => '1',
    'version' => '2024-07-20T10:00:00+00:00',
    'data' => [
        'id' => 1,
        'name' => 'Reitclub Demo',
        'short_name' => 'RCD',
    ],
]);
$changeSet->add('persons', [
    'id' => '1',
    'version' => '2024-07-20T10:01:00+00:00',
    'data' => [
        'id' => 1,
        'name' => 'Anna Mustermann',
        'email' => 'anna@example.com',
        'phone' => '+491234567',
        'roles' => json_encode(['judge'], JSON_THROW_ON_ERROR),
        'club_id' => 1,
        'created_at' => '2024-07-19T08:00:00+00:00',
    ],
]);

$report = importChanges($changeSet);
$accepted = $report->toArray()['accepted'] ?? [];
if (count($accepted['clubs'] ?? []) !== 1 || count($accepted['persons'] ?? []) !== 1) {
    throw new RuntimeException('Import sollte Datensätze übernehmen.');
}

$diff = exportChanges(new Since('2024-07-19T00:00:00+00:00'), new Scopes(['persons']));
if (count($diff->forScope('persons')) !== 1) {
    throw new RuntimeException('Diff sollte aktualisierte Person melden.');
}

$pull = sync_pull_entities(['persons' => ['ids' => [1]]]);
$pulled = $pull->forScope('persons')[0]['data'] ?? [];
if (($pulled['name'] ?? null) !== 'Anna Mustermann') {
    throw new RuntimeException('Pull sollte vollständige Person liefern.');
}

$reportSecond = importChanges($changeSet);
$acceptedSecond = $reportSecond->toArray()['accepted']['persons'][0]['message'] ?? null;
if ($acceptedSecond !== 'noop') {
    throw new RuntimeException('Idempotenter Push sollte als noop markiert sein.');
}

$instance->save([
    'instance_role' => InstanceConfiguration::ROLE_ONLINE,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);

$staleChange = new ChangeSet([], InstanceConfiguration::ROLE_LOCAL);
$staleChange->add('persons', [
    'id' => '1',
    'version' => '2024-07-20T09:00:00+00:00',
    'data' => [
        'id' => 1,
        'name' => 'Anna Alt',
        'email' => 'anna@example.com',
        'phone' => '+491234567',
        'roles' => json_encode(['judge'], JSON_THROW_ON_ERROR),
        'club_id' => 1,
        'created_at' => '2024-07-19T08:00:00+00:00',
    ],
]);
$reportStale = importChanges($staleChange);
$acceptedStale = $reportStale->toArray()['accepted']['persons'][0]['message'] ?? null;
if ($acceptedStale !== 'updated') {
    throw new RuntimeException('Online-Instanz sollte lokale Daten bevorzugen, selbst wenn älter.');
}

$instance->save([
    'instance_role' => InstanceConfiguration::ROLE_LOCAL,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);
$remoteChange = new ChangeSet([], InstanceConfiguration::ROLE_ONLINE);
$remoteChange->add('persons', [
    'id' => '1',
    'version' => '2024-07-20T08:30:00+00:00',
    'data' => [
        'id' => 1,
        'name' => 'Anna Remote',
        'email' => 'anna@example.com',
        'phone' => '+491234567',
        'roles' => json_encode(['judge'], JSON_THROW_ON_ERROR),
        'club_id' => 1,
        'created_at' => '2024-07-19T08:00:00+00:00',
    ],
]);
$reportRemote = importChanges($remoteChange);
$rejectedRemote = $reportRemote->toArray()['rejected']['persons'][0]['reason'] ?? null;
if ($rejectedRemote !== 'CONFLICT_POLICY_VIOLATION') {
    throw new RuntimeException('Lokale Instanz muss Online-Konflikte ablehnen.');
}

$instance->save([
    'instance_role' => InstanceConfiguration::ROLE_ONLINE,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);

try {
    enforceReadWritePolicy(new SyncRequest('push', 'POST', true, ['persons']));
    throw new RuntimeException('Read-only Policy wurde nicht angewendet.');
} catch (SyncException $exception) {
    if ($exception->getErrorCode() !== 'READ_ONLY_MODE') {
        throw $exception;
    }
}

$instance->save([
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
    'instance_role' => InstanceConfiguration::ROLE_LOCAL,
]);

$transactionId = sync_create_transaction('inbound', 'push', ['persons'], ['accepted' => 1]);
if (!sync_acknowledge($transactionId)) {
    throw new RuntimeException('Transaktion sollte quittiert werden.');
}

sync_log_operation('inbound', 'push', ['persons'], 'completed', 'Test-Log');
$logs = sync_recent_logs();
if (!$logs || ($logs[0]['operation'] ?? '') !== 'push') {
    throw new RuntimeException('Logeintrag fehlt.');
}

echo "SyncService tests passed\n";
