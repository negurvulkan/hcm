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
        $errors[] = 'Sicherheitsprüfung fehlgeschlagen.';
    }

    $form['instance_role'] = strtoupper((string) ($_POST['instance_role'] ?? $form['instance_role']));
    $form['operation_mode'] = strtoupper((string) ($_POST['operation_mode'] ?? $form['operation_mode']));
    $form['peer_base_url'] = trim((string) ($_POST['peer_base_url'] ?? $form['peer_base_url']));
    $form['peer_turnier_id'] = trim((string) ($_POST['peer_turnier_id'] ?? $form['peer_turnier_id']));
    $tokenInput = trim((string) ($_POST['peer_api_token'] ?? ''));
    $clearToken = isset($_POST['peer_api_token_clear']);
    $checklistComplete = ($_POST['checklist_complete'] ?? '') === '1';

    if ($clearToken) {
        $hasPeerToken = false;
    } elseif ($tokenInput !== '') {
        $hasPeerToken = true;
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
            $errors[] = 'Ungültige Instanz-Rolle ausgewählt.';
        }
        if (!in_array($form['operation_mode'], InstanceConfiguration::modes(), true)) {
            $errors[] = 'Ungültiger Betriebsmodus ausgewählt.';
        }
        if ($form['peer_base_url'] !== '' && !filter_var($form['peer_base_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Peer-URL ist nicht gültig.';
        }
        if ($form['peer_base_url'] !== '' && $form['peer_turnier_id'] === '') {
            $errors[] = 'Peer Turnier-ID angeben.';
        }

        $requiresToken = ($form['peer_base_url'] !== '') && (
            ($form['operation_mode'] === InstanceConfiguration::MODE_TOURNAMENT && $form['instance_role'] === InstanceConfiguration::ROLE_LOCAL) ||
            ($form['operation_mode'] === InstanceConfiguration::MODE_POST_TOURNAMENT && $form['instance_role'] === InstanceConfiguration::ROLE_ONLINE)
        );
        $effectiveToken = $clearToken ? null : ($tokenInput !== '' ? $tokenInput : ($hasPeerToken ? '__existing__' : null));
        if ($requiresToken && !$effectiveToken) {
            $errors[] = 'Für diesen Modus wird ein API-Token benötigt.';
        }

        if ($previousMode !== $form['operation_mode'] && !$checklistComplete) {
            $errors[] = 'Bitte die Checkliste bestätigen, um den Modus zu wechseln.';
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
                flash('success', 'Konfiguration aktualisiert.');
            } else {
                flash('info', 'Keine Änderungen erkannt.');
            }
            header('Location: instance.php');
            exit;
        }
    } elseif (in_array($action, ['test_connection', 'dry_run'], true)) {
        if ($hasPendingChanges) {
            flash('error', 'Bitte zuerst speichern, bevor die Tests ausgeführt werden.');
            header('Location: instance.php');
            exit;
        }

        $baseUrl = $currentSettings['peer_base_url'] ?? '';
        $token = $currentSettings['peer_api_token'] ?? null;
        if ($baseUrl === '') {
            flash('error', 'Peer-Basisadresse ist nicht konfiguriert.');
            header('Location: instance.php');
            exit;
        }

        try {
            if ($action === 'test_connection') {
                $health = instance_http_get($baseUrl, '/health', $token);
                $instanceConfig->recordHealthResult(true, $health['status'] ?? 'OK', $health);
                instance_refresh_view();
                flash('success', 'Verbindung erfolgreich: ' . ($health['status'] ?? 'ok'));
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
                flash('success', 'Dry-Run durchgeführt. Differenzen Einträge: ' . $summary['differences']['entries']);
            }
        } catch (Throwable $exception) {
            if ($action === 'test_connection') {
                $instanceConfig->recordHealthResult(false, $exception->getMessage());
                instance_refresh_view();
            }
            flash('error', 'Peer-Anfrage fehlgeschlagen: ' . $exception->getMessage());
        }

        header('Location: instance.php');
        exit;
    }
}

$lastHealth = $instanceConfig->peerSummary();
$lastDryRun = $instanceConfig->lastDryRun();
$localSnapshot = $instanceConfig->collectLocalCounts();

render_page('instance.tpl', [
    'title' => 'Instanz & Modus',
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
