<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/app/helpers/sync.php';

use App\Services\InstanceConfiguration;
use App\Sync\Scopes;
use App\Sync\Since;
use App\Sync\SyncException;
use App\Sync\SyncRequest;

$user = auth_require('instance');
$instance = instance_config();
$cursor = getSyncCursor();
$scopes = sync_available_scopes();
$logs = sync_recent_logs();
$diffSummary = null;
$errors = [];
$success = null;

$canPull = !($instance->get('instance_role') === InstanceConfiguration::ROLE_LOCAL && $instance->get('operation_mode') === InstanceConfiguration::MODE_PRE_TOURNAMENT);
$canPush = $instance->canWrite();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        $errors[] = 'Sicherheitsprüfung fehlgeschlagen.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'diff') {
                $since = new Since($cursor->value());
                $scopeList = isset($_POST['scope']) ? (array) $_POST['scope'] : $scopes;
                $changeSet = exportChanges($since, new Scopes($scopeList));
                $counts = [];
                foreach ($changeSet->all() as $scope => $items) {
                    $counts[$scope] = count($items);
                }
                $diffSummary = [
                    'since' => $since->value(),
                    'counts' => $counts,
                ];
                sync_log_operation('outbound', 'diff', array_keys($counts), 'completed', 'Dry-Run über UI', ['entities' => $counts]);
                $success = 'Dry-Run erfolgreich ausgeführt.';
            } elseif ($action === 'pull' && $canPull) {
                sync_log_operation('outbound', 'pull', $scopes, 'queued', 'Manueller Pull ausgelöst.');
                $success = 'Pull-Vorgang wurde vorbereitet.';
            } elseif ($action === 'push' && $canPush) {
                enforceReadWritePolicy(new SyncRequest('push', 'POST', true, $scopes));
                sync_log_operation('inbound', 'push', $scopes, 'queued', 'Manueller Push ausgelöst.');
                $success = 'Push-Vorgang wurde vorbereitet.';
            } else {
                $errors[] = 'Aktion nicht verfügbar.';
            }
        } catch (SyncException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
    $logs = sync_recent_logs();
}

$pdo = app_pdo();
$stmt = $pdo->prepare('SELECT value FROM system_settings WHERE setting_key = :key LIMIT 1');
$stmt->execute(['key' => 'sync_last_completed_at']);
$lastSyncAt = $stmt->fetchColumn() ?: null;

render_page('sync.tpl', [
    'title' => 'Sync',
    'page' => 'sync',
    'user' => $user,
    'cursor' => $cursor,
    'scopes' => $scopes,
    'logs' => $logs,
    'diffSummary' => $diffSummary,
    'errors' => $errors,
    'success' => $success,
    'canPull' => $canPull,
    'canPush' => $canPush,
    'peer' => [
        'base_url' => $instance->get('peer_base_url'),
        'turnier_id' => $instance->get('peer_turnier_id'),
    ],
    'lastSyncAt' => $lastSyncAt,
]);
