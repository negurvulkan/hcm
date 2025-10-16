<?php
require_once __DIR__ . '/auth.php';

if (!function_exists('audit_log')) {
    function audit_log(string $entity, int $entityId, string $action, ?array $before = null, ?array $after = null): void
    {
        $user = auth_user();
        db_execute(
            'INSERT INTO audit_log (entity, entity_id, action, user_id, before_state, after_state, created_at) VALUES (:entity, :entity_id, :action, :user_id, :before_state, :after_state, :created_at)',
            [
                'entity' => $entity,
                'entity_id' => $entityId,
                'action' => $action,
                'user_id' => $user['id'] ?? null,
                'before_state' => $before ? json_encode($before, JSON_THROW_ON_ERROR) : null,
                'after_state' => $after ? json_encode($after, JSON_THROW_ON_ERROR) : null,
                'created_at' => (new \DateTimeImmutable())->format('c'),
            ]
        );
    }
}

if (!function_exists('audit_latest')) {
    function audit_latest(string $entity, int $entityId): ?array
    {
        $row = db_first('SELECT * FROM audit_log WHERE entity = :entity AND entity_id = :entity_id ORDER BY id DESC LIMIT 1', [
            'entity' => $entity,
            'entity_id' => $entityId,
        ]);
        if (!$row) {
            return null;
        }
        $row['before_state'] = $row['before_state'] ? json_decode($row['before_state'], true, 512, JSON_THROW_ON_ERROR) : null;
        $row['after_state'] = $row['after_state'] ? json_decode($row['after_state'], true, 512, JSON_THROW_ON_ERROR) : null;
        return $row;
    }
}

if (!function_exists('audit_undo')) {
    function audit_undo(int $logId): bool
    {
        $log = db_first('SELECT * FROM audit_log WHERE id = :id', ['id' => $logId]);
        if (!$log || empty($log['before_state'])) {
            return false;
        }
        $before = json_decode($log['before_state'], true, 512, JSON_THROW_ON_ERROR);
        switch ($log['entity']) {
            case 'startlist_items':
                db_execute(
                    'UPDATE startlist_items SET position = :position, planned_start = :planned_start, state = :state, note = :note, updated_at = :updated WHERE id = :id',
                    [
                        'position' => $before['position'] ?? null,
                        'planned_start' => $before['planned_start'] ?? null,
                        'state' => $before['state'] ?? 'scheduled',
                        'note' => $before['note'] ?? null,
                        'updated' => (new \DateTimeImmutable())->format('c'),
                        'id' => $log['entity_id'],
                    ]
                );
                break;
            case 'results':
                db_execute(
                    'UPDATE results SET scores_json = :scores, total = :total, penalties = :penalties, status = :status, signed_by = :signed_by, signed_at = :signed_at, signature_hash = :signature, breakdown_json = :breakdown, rule_snapshot = :rule_snapshot, engine_version = :engine_version, tiebreak_path = :tiebreak_path, rank = :rank, eliminated = :eliminated WHERE id = :id',
                    [
                        'scores' => isset($before['scores_json']) ? json_encode($before['scores_json'], JSON_THROW_ON_ERROR) : ($before['scores'] ?? null),
                        'total' => $before['total'] ?? null,
                        'penalties' => $before['penalties'] ?? null,
                        'status' => $before['status'] ?? 'draft',
                        'signed_by' => $before['signed_by'] ?? null,
                        'signed_at' => $before['signed_at'] ?? null,
                        'signature' => $before['signature_hash'] ?? null,
                        'breakdown' => $before['breakdown_json'] ?? ($before['breakdown'] ?? null),
                        'rule_snapshot' => $before['rule_snapshot'] ?? null,
                        'engine_version' => $before['engine_version'] ?? null,
                        'tiebreak_path' => $before['tiebreak_path'] ?? null,
                        'rank' => $before['rank'] ?? null,
                        'eliminated' => $before['eliminated'] ?? 0,
                        'id' => $log['entity_id'],
                    ]
                );
                break;
            default:
                return false;
        }

        audit_log($log['entity'], (int) $log['entity_id'], 'undo', json_decode($log['after_state'] ?? 'null', true) ?: null, $before);
        return true;
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $user = auth_require('audit');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'CSRF ungültig']);
            exit;
        }
        $logId = (int) ($_POST['log_id'] ?? 0);
        $success = $logId > 0 && audit_undo($logId);
        echo json_encode(['success' => $success]);
        exit;
    }

    $entries = db_all('SELECT * FROM audit_log ORDER BY id DESC LIMIT 50');
    render_page('audit.tpl', [
        'title' => 'Audit-Trail',
        'page' => 'audit',
        'entries' => $entries,
    ]);
}
