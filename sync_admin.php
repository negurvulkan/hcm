<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/app/helpers/sync.php';

use App\Services\InstanceConfiguration;
use App\Sync\ChangeSet;
use App\Sync\Scopes;
use App\Sync\Since;
use App\Sync\SyncCursor;
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
        $errors[] = t('sync.flash.csrf_failed');
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
                sync_log_operation('outbound', 'diff', array_keys($counts), 'completed', t('sync.logs.ui_diff'), ['entities' => $counts]);
                $success = t('sync.flash.diff_success');
            } elseif ($action === 'pull' && $canPull) {
                sync_log_operation('outbound', 'pull', $scopes, 'queued', t('sync.logs.manual_pull'));
                $success = t('sync.flash.pull_queued');
            } elseif ($action === 'push' && $canPush) {
                $baseUrl = (string) ($instance->get('peer_base_url') ?? '');
                if ($baseUrl === '') {
                    $errors[] = t('sync.flash.peer_missing');
                } else {
                    enforceReadWritePolicy(new SyncRequest('push', 'POST', true, $scopes));

                    $since = new Since($cursor->value());
                    $scopeList = new Scopes($scopes);
                    $start = microtime(true);
                    $diffChangeSet = exportChanges($since, $scopeList);
                    $payload = sync_hydrate_change_set($diffChangeSet);

                    if (!$payload instanceof ChangeSet) {
                        sync_log_operation('outbound', 'push', $scopeList->toArray(), 'skipped', t('sync.logs.manual_push'), [
                            'accepted' => 0,
                            'rejected' => 0,
                        ]);
                        $success = t('sync.flash.push_nothing');
                    } else {
                        $response = null;
                        try {
                            $response = sync_http_post_json($baseUrl, '/sync/push', $payload->toArray(), (string) ($instance->get('peer_api_token') ?? ''));
                        } catch (Throwable $throwable) {
                            $errors[] = t('sync.flash.peer_request_failed', ['message' => $throwable->getMessage()]);
                        }

                        if ($response !== null) {
                            $reportData = is_array($response['report'] ?? null) ? $response['report'] : [];
                            $report = sync_import_report_from_array($reportData);
                            $duration = (int) ((microtime(true) - $start) * 1000);
                            $accepted = array_sum(array_map('count', $reportData['accepted'] ?? []));
                            $rejected = array_sum(array_map('count', $reportData['rejected'] ?? []));
                            $transactionId = isset($response['transaction_id']) ? (string) $response['transaction_id'] : '';

                            sync_log_operation('outbound', 'push', $payload->scopes(), $report->hasErrors() ? 'error' : 'completed', t('sync.logs.manual_push'), [
                                'accepted' => $accepted,
                                'rejected' => $rejected,
                            ], $duration, $transactionId !== '' ? $transactionId : null);

                            if (!$report->hasErrors()) {
                                $newCursor = sync_resolve_push_cursor($payload, $report);
                                if ($newCursor instanceof SyncCursor) {
                                    setSyncCursor($newCursor);
                                    $cursor = $newCursor;
                                }
                                $success = t('sync.flash.push_success', ['count' => $accepted]);
                            } else {
                                $errors[] = t('sync.flash.push_failed');
                            }

                            if ($transactionId !== '') {
                                try {
                                    sync_http_post_json($baseUrl, '/sync/ack', ['transaction_id' => $transactionId], (string) ($instance->get('peer_api_token') ?? ''));
                                } catch (Throwable $ackException) {
                                    $errors[] = t('sync.flash.push_ack_failed') . ' (' . $ackException->getMessage() . ')';
                                }
                            }
                        }
                    }
                }
            } else {
                $errors[] = t('sync.flash.action_unavailable');
            }
        } catch (SyncException $exception) {
            $errors[] = $exception->getMessage();
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
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
    'title' => t('pages.sync.title'),
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

function sync_http_post_json(string $baseUrl, string $path, array $payload, string $token = ''): array
{
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    $token = trim($token);
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
        $headers[] = 'X-API-Token: ' . $token;
    }

    $base = rtrim($baseUrl, '/');
    $attempts = [$base . $path];

    if (preg_match('~^/sync/([a-z0-9_-]+)$~i', $path, $matches)) {
        $attempts[] = $base . '/sync/index.php?action=' . rawurlencode($matches[1]);
    }

    $lastException = null;
    foreach ($attempts as $url) {
        try {
            return sync_http_post_json_request($url, $headers, $payload);
        } catch (RuntimeException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }
            $lastException = $exception;
        }
    }

    if ($lastException instanceof RuntimeException) {
        throw $lastException;
    }

    throw new RuntimeException(t('sync.flash.peer_request_failed', ['message' => 'connection failed']));
}

/**
 * @param array<int, string> $headers
 */
function sync_http_post_json_request(string $url, array $headers, array $payload): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'timeout' => 10,
            'header' => implode("\r\n", $headers),
            'content' => json_encode($payload, JSON_THROW_ON_ERROR),
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    $status = 0;
    if (preg_match('~\s(\d{3})\s~', $statusLine, $match)) {
        $status = (int) $match[1];
    }

    if ($response === false) {
        $error = error_get_last();
        $message = $error['message'] ?? ($statusLine !== '' ? $statusLine : 'connection failed');
        throw new RuntimeException(t('sync.flash.peer_request_failed', ['message' => $message]), $status);
    }

    if ($status !== 0 && $status !== 200) {
        throw new RuntimeException(t('sync.flash.peer_request_failed', ['message' => $statusLine ?: 'HTTP error']), $status);
    }

    $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new RuntimeException(t('sync.flash.peer_request_failed', ['message' => 'invalid response']), $status);
    }

    return $decoded;
}
