<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';

use App\Services\InstanceConfiguration;

$user = auth_require('instance');
$instanceConfig = instance_config();
$currentSettings = $instanceConfig->all();
$previousMode = $currentSettings['operation_mode'] ?? InstanceConfiguration::MODE_PRE_TOURNAMENT;
$hasPeerToken = $instanceConfig->hasPeerToken();

$form = [
    'instance_role' => $currentSettings['instance_role'] ?? InstanceConfiguration::ROLE_ONLINE,
    'operation_mode' => $currentSettings['operation_mode'] ?? InstanceConfiguration::MODE_PRE_TOURNAMENT,
    'peer_base_url' => $currentSettings['peer_base_url'] ?? '',
    'peer_turnier_id' => $currentSettings['peer_turnier_id'] ?? '',
];

$errors = [];
$hasPendingChanges = false;
$action = $_POST['action'] ?? 'save';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        $errors[] = t('instance.validation.csrf_invalid');
    }

    $form['instance_role'] = strtoupper((string) ($_POST['instance_role'] ?? $form['instance_role']));
    $form['operation_mode'] = strtoupper((string) ($_POST['operation_mode'] ?? $form['operation_mode']));
    $peerBaseProvided = array_key_exists('peer_base_url', $_POST);
    $form['peer_base_url'] = trim((string) ($_POST['peer_base_url'] ?? $form['peer_base_url']));
    $form['peer_turnier_id'] = trim((string) ($_POST['peer_turnier_id'] ?? $form['peer_turnier_id']));
    $tokenInput = trim((string) ($_POST['peer_api_token'] ?? ''));
    $clearToken = isset($_POST['peer_api_token_clear']);
    $checklistComplete = ($_POST['checklist_complete'] ?? '') === '1';

    $isLocalInstance = $form['instance_role'] === InstanceConfiguration::ROLE_LOCAL;
    $isOnlineInstance = $form['instance_role'] === InstanceConfiguration::ROLE_ONLINE;
    $isMirrorInstance = $form['instance_role'] === InstanceConfiguration::ROLE_MIRROR;
    $isTournamentMode = $form['operation_mode'] === InstanceConfiguration::MODE_TOURNAMENT;

    if ($clearToken) {
        $hasPeerToken = false;
    } elseif ($tokenInput !== '') {
        $hasPeerToken = true;
    }

    if ($isTournamentMode && $isOnlineInstance && !$isMirrorInstance) {
        if ($action === 'save' && $peerBaseProvided && $form['peer_base_url'] !== '') {
            $errors[] = t('instance.validation.peer_base_local_only');
        }
        $form['peer_base_url'] = '';
    }

    $updates = [
        'instance_role' => $form['instance_role'],
        'operation_mode' => $form['operation_mode'],
        'peer_base_url' => $form['peer_base_url'] !== '' ? $form['peer_base_url'] : null,
        'peer_turnier_id' => $form['peer_turnier_id'] !== '' ? $form['peer_turnier_id'] : null,
    ];

    $diffInput = $updates;
    if ($clearToken) {
        $diffInput['peer_api_token'] = null;
    } elseif ($tokenInput !== '') {
        $diffInput['peer_api_token'] = $tokenInput;
    }

    $pendingChanges = $instanceConfig->diff($diffInput);
    $hasPendingChanges = !empty($pendingChanges['after']);

    if ($action === 'save') {
        if (!in_array($form['instance_role'], InstanceConfiguration::roles(), true)) {
            $errors[] = t('instance.validation.invalid_role');
        }
        if (!in_array($form['operation_mode'], InstanceConfiguration::modes(), true)) {
            $errors[] = t('instance.validation.invalid_mode');
        }
        if ($form['peer_base_url'] !== '' && !filter_var($form['peer_base_url'], FILTER_VALIDATE_URL)) {
            $errors[] = t('instance.validation.peer_url_invalid');
        }
        if ($isTournamentMode && $isLocalInstance && $form['peer_base_url'] === '') {
            $errors[] = t('instance.validation.peer_base_required');
        }
        if ($form['peer_base_url'] !== '' && $form['peer_turnier_id'] === '') {
            $errors[] = t('instance.validation.peer_turnier_id_required');
        } elseif ($isTournamentMode && ($isLocalInstance || $isOnlineInstance) && $form['peer_turnier_id'] === '') {
            $errors[] = t('instance.validation.peer_turnier_id_required');
        }

        $requiresToken = false;
        if ($isTournamentMode && ($isLocalInstance || $isOnlineInstance)) {
            $requiresToken = true;
        } elseif ($form['peer_base_url'] !== '') {
            $requiresToken = (
                ($form['operation_mode'] === InstanceConfiguration::MODE_TOURNAMENT && $isLocalInstance) ||
                ($form['operation_mode'] === InstanceConfiguration::MODE_POST_TOURNAMENT && $isOnlineInstance)
            );
        }
        $effectiveToken = $clearToken ? null : ($tokenInput !== '' ? $tokenInput : ($hasPeerToken ? '__existing__' : null));
        if ($requiresToken && !$effectiveToken) {
            $errors[] = t('instance.validation.peer_token_required');
        }

        if ($previousMode !== $form['operation_mode'] && !$checklistComplete) {
            $errors[] = t('instance.validation.checklist_required');
        }

        if (!$errors) {
            $saveInput = $diffInput;
            if (($saveInput['peer_api_token'] ?? null) === null && !$clearToken) {
                unset($saveInput['peer_api_token']);
            }
            $changes = $instanceConfig->save($saveInput);
            if ($changes['after']) {
                audit_log('system_settings', 0, 'instance_update', $instanceConfig->redact($changes['before']), $instanceConfig->redact($changes['after']));
                instance_refresh_view();
                flash('success', t('instance.flash.updated'));
            } else {
                flash('info', t('instance.flash.no_changes'));
            }
            header('Location: instance.php');
            exit;
        }
    } elseif (in_array($action, ['test_connection', 'dry_run'], true)) {
        if ($hasPendingChanges) {
            flash('error', t('instance.validation.save_before_tests'));
            header('Location: instance.php');
            exit;
        }

        $baseUrl = $currentSettings['peer_base_url'] ?? '';
        $token = $currentSettings['peer_api_token'] ?? null;
        if ($baseUrl === '') {
            flash('error', t('instance.validation.peer_base_missing'));
            header('Location: instance.php');
            exit;
        }

        try {
            if ($action === 'test_connection') {
                $health = instance_http_get($baseUrl, '/health', $token);
                $instanceConfig->recordHealthResult(true, $health['status'] ?? 'OK', $health);
                instance_refresh_view();
                $statusLabel = $health['status'] ?? 'ok';
                flash('success', t('instance.flash.connection_success', ['status' => $statusLabel]));
            } else {
                $infoPath = '/mirror/info';
                if (!empty($currentSettings['peer_turnier_id'])) {
                    $infoPath .= '?turnier_id=' . urlencode((string) $currentSettings['peer_turnier_id']);
                }
                $remote = instance_http_get($baseUrl, $infoPath, $token);
                $remoteCounts = $remote['counts'] ?? [];
                $localCounts = $instanceConfig->collectLocalCounts();
                $local = $localCounts['counts'];
                $summary = [
                    'local' => $local,
                    'remote' => [
                        'entries' => (int) ($remoteCounts['entries'] ?? 0),
                        'classes' => (int) ($remoteCounts['classes'] ?? 0),
                        'results' => (int) ($remoteCounts['results'] ?? 0),
                    ],
                ];
                $summary['differences'] = [
                    'entries' => $summary['local']['entries'] - $summary['remote']['entries'],
                    'classes' => $summary['local']['classes'] - $summary['remote']['classes'],
                    'results' => $summary['local']['results'] - $summary['remote']['results'],
                ];
                $instanceConfig->recordDryRun($summary);
                instance_refresh_view();
                flash('success', t('instance.flash.dry_run_success', ['count' => $summary['differences']['entries']]));
            }
        } catch (Throwable $exception) {
            if ($action === 'test_connection') {
                $instanceConfig->recordHealthResult(false, $exception->getMessage());
                instance_refresh_view();
            }
            flash('error', t('instance.flash.peer_request_failed', ['message' => $exception->getMessage()]));
        }

        header('Location: instance.php');
        exit;
    }
}

$lastHealth = $instanceConfig->peerSummary();
$lastDryRun = $instanceConfig->lastDryRun();
$localSnapshot = $instanceConfig->collectLocalCounts();

render_page('instance.tpl', [
    'titleKey' => 'pages.instance.title',
    'page' => 'instance',
    'errors' => $errors,
    'form' => $form,
    'token' => csrf_token(),
    'hasPeerToken' => $hasPeerToken,
    'lastHealth' => $lastHealth,
    'lastDryRun' => $lastDryRun,
    'localSnapshot' => $localSnapshot,
    'hasPendingChanges' => $hasPendingChanges,
    'previousMode' => $previousMode,
]);

function instance_http_get(string $baseUrl, string $path, ?string $token = null): array
{
    $url = rtrim($baseUrl, '/') . $path;
    $headers = ['Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'header' => implode("\r\n", $headers),
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        $error = error_get_last();
        throw new RuntimeException($error['message'] ?? 'Keine Antwort vom Peer.');
    }
    $statusLine = $http_response_header[0] ?? '';
    if (!str_contains($statusLine, '200')) {
        throw new RuntimeException('Peer antwortet mit: ' . $statusLine);
    }
    $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new RuntimeException('Unerwartete Antwort vom Peer.');
    }
    return $decoded;
}
