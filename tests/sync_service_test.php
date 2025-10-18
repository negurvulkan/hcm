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
use App\Sync\SyncRepository;
use App\Sync\SyncService;

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
    'CREATE TABLE parties (id INTEGER PRIMARY KEY AUTOINCREMENT, party_type TEXT, display_name TEXT, sort_name TEXT, email TEXT, phone TEXT, created_at TEXT, updated_at TEXT)',
    'CREATE TABLE person_profiles (party_id INTEGER PRIMARY KEY, club_id INTEGER, preferred_locale TEXT, updated_at TEXT)',
    'CREATE TABLE party_roles (id INTEGER PRIMARY KEY AUTOINCREMENT, party_id INTEGER, role TEXT, context TEXT, assigned_at TEXT, updated_at TEXT)',
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
$changeSet->add('parties', [
    'id' => '1',
    'version' => '2024-07-20T10:01:00+00:00',
    'data' => [
        'id' => 1,
        'party_type' => 'person',
        'display_name' => 'Anna Mustermann',
        'sort_name' => 'anna mustermann',
        'email' => 'anna@example.com',
        'phone' => '+491234567',
        'created_at' => '2024-07-19T08:00:00+00:00',
        'updated_at' => '2024-07-20T10:01:00+00:00',
    ],
]);
$changeSet->add('person_profiles', [
    'id' => '1',
    'version' => '2024-07-20T10:01:00+00:00',
    'data' => [
        'party_id' => 1,
        'club_id' => 1,
        'preferred_locale' => null,
        'updated_at' => '2024-07-20T10:01:00+00:00',
    ],
]);
$changeSet->add('party_roles', [
    'id' => '1',
    'version' => '2024-07-20T10:01:00+00:00',
    'data' => [
        'id' => 1,
        'party_id' => 1,
        'role' => 'judge',
        'context' => 'system',
        'assigned_at' => '2024-07-19T08:00:00+00:00',
        'updated_at' => '2024-07-20T10:01:00+00:00',
    ],
]);

$pdo->exec("INSERT INTO parties (id, party_type, display_name, sort_name, email, phone, created_at, updated_at) VALUES (1, 'person', 'Anna Mustermann', 'anna mustermann', 'anna@example.com', '+491234567', '2024-07-19T08:00:00+00:00', '2024-07-20T10:01:00+00:00')");

$report = importChanges($changeSet);
$accepted = $report->toArray()['accepted'] ?? [];
if (count($accepted['clubs'] ?? []) !== 1 || count($accepted['parties'] ?? []) !== 1 || count($accepted['person_profiles'] ?? []) !== 1 || count($accepted['party_roles'] ?? []) !== 1) {
    throw new RuntimeException('Import sollte Datensätze übernehmen.');
}

$initialMessage = $accepted['parties'][0]['message'] ?? null;
if ($initialMessage !== 'inserted') {
    throw new RuntimeException('Initialer Import sollte als insert gelten.');
}

$resolvedCursor = sync_resolve_push_cursor($changeSet, $report);
if (!$resolvedCursor instanceof SyncCursor) {
    throw new RuntimeException('Push sollte Cursor ableiten.');
}
setSyncCursor($resolvedCursor);
$storedCursor = getSyncCursor();
if ($storedCursor->value() !== $resolvedCursor->value()) {
    throw new RuntimeException('Cursor sollte gespeichert werden.');
}

$diff = exportChanges(new Since('2024-07-19T00:00:00+00:00'), new Scopes(['parties']));
if (count($diff->forScope('parties')) !== 1) {
    throw new RuntimeException('Diff sollte aktualisierte Partei melden.');
}

$pull = sync_pull_entities(['parties' => ['ids' => [1]]]);
$pulled = $pull->forScope('parties')[0]['data'] ?? [];
if (($pulled['display_name'] ?? null) !== 'Anna Mustermann') {
    throw new RuntimeException('Pull sollte vollständige Partei liefern.');
}

$skeleton = exportChanges(new Since('2024-07-19T00:00:00+00:00'), new Scopes(['parties']));
$hydrated = sync_hydrate_change_set($skeleton);
if (!$hydrated instanceof ChangeSet) {
    throw new RuntimeException('Hydrierter ChangeSet sollte erzeugt werden.');
}
$hydratedParties = $hydrated->forScope('parties')[0]['data'] ?? null;
if (!is_array($hydratedParties) || ($hydratedParties['display_name'] ?? null) !== 'Anna Mustermann') {
    throw new RuntimeException('Hydrierter ChangeSet sollte Daten enthalten.');
}
$expectedCursor = sync_highest_cursor($skeleton);
if ($expectedCursor instanceof SyncCursor && $hydrated->cursor() !== $expectedCursor->value()) {
    throw new RuntimeException('Hydrierter ChangeSet sollte höchsten Cursor übernehmen.');
}

$arrayReport = [
    'accepted' => ['parties' => [['id' => '1', 'message' => 'updated']]],
    'rejected' => ['clubs' => [['id' => '2', 'reason' => 'INVALID', 'message' => 'Missing club']]],
    'errors' => [['code' => 'E_INTERNAL', 'message' => 'boom']],
];
$convertedReport = sync_import_report_from_array($arrayReport);
$convertedArray = $convertedReport->toArray();
if (($convertedArray['accepted']['parties'][0]['message'] ?? null) !== 'updated') {
    throw new RuntimeException('Konvertierter Report sollte Accepted übernehmen.');
}
if (($convertedArray['rejected']['clubs'][0]['reason'] ?? null) !== 'INVALID') {
    throw new RuntimeException('Konvertierter Report sollte Rejected übernehmen.');
}
if (($convertedArray['errors'][0]['code'] ?? null) !== 'E_INTERNAL') {
    throw new RuntimeException('Konvertierter Report sollte Errors übernehmen.');
}

$reportSecond = importChanges($changeSet);
$acceptedSecond = $reportSecond->toArray()['accepted']['parties'][0]['message'] ?? null;
if ($acceptedSecond !== 'noop') {
    throw new RuntimeException('Idempotenter Push sollte als noop markiert sein.');
}

$instance->save([
    'instance_role' => InstanceConfiguration::ROLE_ONLINE,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);

$staleChange = new ChangeSet([], InstanceConfiguration::ROLE_LOCAL);
$staleChange->add('parties', [
    'id' => '1',
    'version' => '2024-07-20T09:00:00+00:00',
    'data' => [
        'id' => 1,
        'party_type' => 'person',
        'display_name' => 'Anna Alt',
        'sort_name' => 'anna alt',
        'email' => 'anna@example.com',
        'phone' => '+491234567',
        'created_at' => '2024-07-19T08:00:00+00:00',
        'updated_at' => '2024-07-20T09:00:00+00:00',
    ],
]);
$reportStale = importChanges($staleChange);
$acceptedStale = $reportStale->toArray()['accepted']['parties'][0]['message'] ?? null;
if ($acceptedStale !== 'updated') {
    throw new RuntimeException('Online-Instanz sollte lokale Daten bevorzugen, selbst wenn älter.');
}

$instance->save([
    'instance_role' => InstanceConfiguration::ROLE_LOCAL,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);
$remoteChange = new ChangeSet([], InstanceConfiguration::ROLE_ONLINE);
$remoteChange->add('parties', [
    'id' => '1',
    'version' => '2024-07-20T08:30:00+00:00',
    'data' => [
        'id' => 1,
        'party_type' => 'person',
        'display_name' => 'Anna Remote',
        'sort_name' => 'anna remote',
        'email' => 'anna@example.com',
        'phone' => '+491234567',
        'created_at' => '2024-07-19T08:00:00+00:00',
        'updated_at' => '2024-07-20T08:30:00+00:00',
    ],
]);
$reportRemote = importChanges($remoteChange);
$rejectedRemote = $reportRemote->toArray()['rejected']['parties'][0]['reason'] ?? null;
if ($rejectedRemote !== 'CONFLICT_POLICY_VIOLATION') {
    throw new RuntimeException('Lokale Instanz muss Online-Konflikte ablehnen.');
}

$instance->save([
    'instance_role' => InstanceConfiguration::ROLE_ONLINE,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);

try {
    enforceReadWritePolicy(new SyncRequest('push', 'POST', true, ['parties']));
} catch (SyncException $exception) {
    throw new RuntimeException('Online-Instanz sollte Pushes im Turnierbetrieb akzeptieren.', 0, $exception);
}

$instance->save([
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
    'instance_role' => InstanceConfiguration::ROLE_LOCAL,
]);

$transactionId = sync_create_transaction('inbound', 'push', ['parties'], ['accepted' => 1]);
if (!sync_acknowledge($transactionId)) {
    throw new RuntimeException('Transaktion sollte quittiert werden.');
}

sync_log_operation('inbound', 'push', ['parties'], 'completed', 'Test-Log');
$logs = sync_recent_logs();
if (!$logs || ($logs[0]['operation'] ?? '') !== 'push') {
    throw new RuntimeException('Logeintrag fehlt.');
}

$startNumberChange = new ChangeSet([], InstanceConfiguration::ROLE_ONLINE);
$startNumberChange->add('events', [
    'id' => '2',
    'version' => '2024-07-21T12:00:00+00:00',
    'data' => [
        'id' => 2,
        'title' => 'Demo Turnier',
        'start_date' => '2024-07-21',
        'end_date' => '2024-07-22',
        'venues' => 'Arena 1',
        'is_active' => 1,
        'scoring_rule_json' => '{}',
        'start_number_rules' => '{}',
        'updated_at' => '2024-07-21T12:00:00+00:00',
    ],
]);
$startNumberChange->add('entries', [
    'id' => '5',
    'version' => '2024-07-21T12:02:00+00:00',
    'data' => [
        'id' => 5,
        'event_id' => 2,
        'class_id' => 7,
        'party_id' => 1,
        'horse_id' => 3,
        'status' => 'confirmed',
        'fee_paid_at' => null,
        'created_at' => '2024-07-21T11:00:00+00:00',
        'updated_at' => '2024-07-21T12:02:00+00:00',
        'start_number_assignment_id' => 10,
        'start_number_raw' => 42,
        'start_number_display' => '42',
        'start_number_rule_snapshot' => '{}',
        'start_number_allocation_entity' => 'class:7',
        'start_number_locked_at' => '2024-07-21T12:03:00+00:00',
    ],
]);
$startNumberChange->add('starts', [
    'id' => '11',
    'version' => '2024-07-21T12:04:00+00:00',
    'data' => [
        'id' => 11,
        'class_id' => 7,
        'entry_id' => 5,
        'position' => 1,
        'planned_start' => '2024-07-21T12:10:00+00:00',
        'state' => 'scheduled',
        'note' => null,
        'created_at' => '2024-07-21T11:30:00+00:00',
        'updated_at' => '2024-07-21T12:04:00+00:00',
        'start_number_assignment_id' => 10,
        'start_number_raw' => 42,
        'start_number_display' => '42',
        'start_number_rule_snapshot' => '{}',
        'start_number_allocation_entity' => 'class:7',
        'start_number_locked_at' => '2024-07-21T12:05:00+00:00',
    ],
]);
$validation = validateDelta($startNumberChange);
if (!$validation->isValid()) {
    throw new RuntimeException('Startnummernfelder sollten für den Sync erlaubt sein.');
}

$deleteChange = new ChangeSet([], InstanceConfiguration::ROLE_LOCAL);
$deleteChange->add('parties', [
    'id' => '1',
    'version' => '2024-07-22T09:00:00+00:00',
    'meta' => ['deleted' => true],
]);

$validationDelete = validateDelta($deleteChange);
if (!$validationDelete->isValid()) {
    throw new RuntimeException('Löschungen sollten die Validierung bestehen.');
}

$deleteReport = importChanges($deleteChange);
$deleteMessage = $deleteReport->toArray()['accepted']['parties'][0]['message'] ?? null;
if ($deleteMessage !== 'deleted') {
    throw new RuntimeException('Löschungen sollten als deleted markiert werden.');
}

$countAfterDelete = (int) $pdo->query('SELECT COUNT(*) FROM parties')->fetchColumn();
if ($countAfterDelete !== 0) {
    throw new RuntimeException('Gelöschte Datensätze sollten entfernt sein.');
}

$deleteRepeat = importChanges($deleteChange);
$repeatMessage = $deleteRepeat->toArray()['accepted']['parties'][0]['message'] ?? null;
if ($repeatMessage !== 'noop') {
    throw new RuntimeException('Erneute Löschungen sollten noop liefern.');
}

$deletionDiff = exportChanges(new Since('2024-07-19T00:00:00+00:00'), new Scopes(['parties']));
$deletionRecord = $deletionDiff->forScope('parties')[0] ?? null;
if (!is_array($deletionRecord) || ($deletionRecord['meta']['deleted'] ?? false) !== true) {
    throw new RuntimeException('Diff sollte Löschungen kennzeichnen.');
}

$hydratedDeletion = sync_hydrate_change_set($deletionDiff);
if (!$hydratedDeletion instanceof ChangeSet) {
    throw new RuntimeException('Hydrierter ChangeSet sollte bei Löschungen erzeugt werden.');
}
$hydratedDeletionRecord = $hydratedDeletion->forScope('parties')[0] ?? null;
if (!is_array($hydratedDeletionRecord) || ($hydratedDeletionRecord['meta']['deleted'] ?? false) !== true) {
    throw new RuntimeException('Hydrierter ChangeSet sollte Löschungs-Metadaten behalten.');
}
if ($hydratedDeletionRecord['data'] !== null) {
    throw new RuntimeException('Hydrierte Löschungen sollten keinen Datensatz enthalten.');
}

$roundTrippedPayload = json_decode(json_encode($hydratedDeletion->toArray(), JSON_THROW_ON_ERROR), true);
if (!is_array($roundTrippedPayload)) {
    throw new RuntimeException('Serialisierte Löschung sollte rekonstruierbar sein.');
}

$roundTrippedDeletion = ChangeSet::fromPayload($roundTrippedPayload);

$remotePdo = new PDO('sqlite::memory:');
$remotePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
foreach ($schema as $sql) {
    $remotePdo->exec($sql);
}
$remotePdo->exec("INSERT INTO parties (id, party_type, display_name, sort_name, email, phone, created_at, updated_at) VALUES (1, 'person', 'Anna Mustermann', 'anna mustermann', 'anna@example.com', '+491234567', '2024-07-19T08:00:00+00:00', '2024-07-20T10:01:00+00:00')");

$remoteInstance = new InstanceConfiguration($remotePdo);
$remoteInstance->save([
    'instance_role' => InstanceConfiguration::ROLE_ONLINE,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);

$remoteService = new SyncService(new SyncRepository($remotePdo, $remoteInstance), $remoteInstance);
$remoteReport = $remoteService->importChanges($roundTrippedDeletion);
$remoteMessage = $remoteReport->toArray()['accepted']['parties'][0]['message'] ?? null;
if ($remoteMessage !== 'deleted') {
    throw new RuntimeException('Remote-Import sollte Löschungen anwenden.');
}

$remainingRemote = (int) $remotePdo->query('SELECT COUNT(*) FROM parties')->fetchColumn();
if ($remainingRemote !== 0) {
    throw new RuntimeException('Remote-Instanz sollte Datensatz nach Löschung entfernen.');
}

$remoteStateMeta = $remotePdo->prepare("SELECT payload_meta FROM sync_state WHERE scope = :scope AND entity_id = :id LIMIT 1");
$remoteStateMeta->execute(['scope' => 'parties', 'id' => '1']);
$stateMetaValue = $remoteStateMeta->fetchColumn();
$decodedStateMeta = $stateMetaValue !== false ? json_decode((string) $stateMetaValue, true) : null;
if (!is_array($decodedStateMeta) || ($decodedStateMeta['deleted'] ?? false) !== true) {
    throw new RuntimeException('Remote-Sync-State sollte Löschmarkierung setzen.');
}

$legacyPayload = $roundTrippedPayload;
unset($legacyPayload['entities']['parties'][0]['meta']['deleted']);
$legacyPayload['entities']['parties'][0]['meta'] = [];
$legacyDeletion = ChangeSet::fromPayload($legacyPayload);

$legacyPdo = new PDO('sqlite::memory:');
$legacyPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
foreach ($schema as $sql) {
    $legacyPdo->exec($sql);
}
$legacyPdo->exec("INSERT INTO parties (id, party_type, display_name, sort_name, email, phone, created_at, updated_at) VALUES (1, 'person', 'Anna Mustermann', 'anna mustermann', 'anna@example.com', '+491234567', '2024-07-19T08:00:00+00:00', '2024-07-20T10:01:00+00:00')");

$legacyInstance = new InstanceConfiguration($legacyPdo);
$legacyInstance->save([
    'instance_role' => InstanceConfiguration::ROLE_ONLINE,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);

$legacyService = new SyncService(new SyncRepository($legacyPdo, $legacyInstance), $legacyInstance);
$legacyReport = $legacyService->importChanges($legacyDeletion);
$legacyMessage = $legacyReport->toArray()['accepted']['parties'][0]['message'] ?? null;
if ($legacyMessage !== 'deleted') {
    throw new RuntimeException('Legacy-Import ohne Meta-Flag sollte Löschungen anwenden.');
}

$legacyRemaining = (int) $legacyPdo->query('SELECT COUNT(*) FROM parties')->fetchColumn();
if ($legacyRemaining !== 0) {
    throw new RuntimeException('Legacy-Instanz sollte Datensatz nach Löschung entfernen.');
}

$legacyChecksumPayload = $roundTrippedPayload;
unset($legacyChecksumPayload['entities']['parties'][0]['meta']['deleted']);
$legacyChecksumPayload['entities']['parties'][0]['meta'] = ['checksum' => null];
$legacyChecksumDeletion = ChangeSet::fromPayload($legacyChecksumPayload);

$legacyChecksumPdo = new PDO('sqlite::memory:');
$legacyChecksumPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
foreach ($schema as $sql) {
    $legacyChecksumPdo->exec($sql);
}
$legacyChecksumPdo->exec("INSERT INTO parties (id, party_type, display_name, sort_name, email, phone, created_at, updated_at) VALUES (1, 'person', 'Anna Mustermann', 'anna mustermann', 'anna@example.com', '+491234567', '2024-07-19T08:00:00+00:00', '2024-07-20T10:01:00+00:00')");

$legacyChecksumInstance = new InstanceConfiguration($legacyChecksumPdo);
$legacyChecksumInstance->save([
    'instance_role' => InstanceConfiguration::ROLE_ONLINE,
    'operation_mode' => InstanceConfiguration::MODE_TOURNAMENT,
]);

$legacyChecksumService = new SyncService(new SyncRepository($legacyChecksumPdo, $legacyChecksumInstance), $legacyChecksumInstance);
$legacyChecksumReport = $legacyChecksumService->importChanges($legacyChecksumDeletion);
$legacyChecksumMessage = $legacyChecksumReport->toArray()['accepted']['parties'][0]['message'] ?? null;
if ($legacyChecksumMessage !== 'deleted') {
    throw new RuntimeException('Legacy-Import mit leerem Checksum-Flag sollte Löschungen anwenden.');
}

$legacyChecksumRemaining = (int) $legacyChecksumPdo->query('SELECT COUNT(*) FROM parties')->fetchColumn();
if ($legacyChecksumRemaining !== 0) {
    throw new RuntimeException('Legacy-Instanz mit leerem Checksum-Flag sollte Datensatz nach Löschung entfernen.');
}

echo "SyncService tests passed\n";
