<?php
namespace App\Sync;

use App\Services\InstanceConfiguration;
use DateTimeImmutable;
use PDO;
use PDOException;
use Throwable;

class SyncRepository
{
    private PDO $pdo;
    private InstanceConfiguration $config;

    /**
     * @var array<string, array<string, mixed>>
     */
    private const SCOPE_DEFINITIONS = [
        'persons' => [
            'alias_of' => 'parties',
        ],
        'parties' => [
            'table' => 'parties',
            'id_column' => 'id',
            'columns' => ['id', 'party_type', 'display_name', 'sort_name', 'email', 'phone', 'created_at', 'updated_at'],
            'version_column' => 'updated_at',
            'fallback_version_column' => 'created_at',
        ],
        'person_profiles' => [
            'table' => 'person_profiles',
            'id_column' => 'party_id',
            'columns' => ['party_id', 'club_id', 'preferred_locale', 'updated_at'],
            'version_column' => 'updated_at',
            'dependencies' => [
                ['column' => 'party_id', 'table' => 'parties', 'reference' => 'id'],
                ['column' => 'club_id', 'table' => 'clubs', 'reference' => 'id'],
            ],
        ],
        'party_roles' => [
            'table' => 'party_roles',
            'id_column' => 'id',
            'columns' => ['id', 'party_id', 'role', 'context', 'assigned_at', 'updated_at'],
            'version_column' => 'updated_at',
            'dependencies' => [
                ['column' => 'party_id', 'table' => 'parties', 'reference' => 'id'],
            ],
        ],
        'horses' => [
            'table' => 'horses',
            'id_column' => 'id',
            'columns' => ['id', 'name', 'owner_party_id', 'documents_ok', 'notes', 'updated_at'],
            'version_column' => 'updated_at',
            'dependencies' => [
                ['column' => 'owner_party_id', 'table' => 'parties', 'reference' => 'id'],
            ],
        ],
        'clubs' => [
            'table' => 'clubs',
            'id_column' => 'id',
            'columns' => ['id', 'name', 'short_name', 'updated_at'],
            'version_column' => 'updated_at',
        ],
        'events' => [
            'table' => 'events',
            'id_column' => 'id',
            'columns' => ['id', 'title', 'start_date', 'end_date', 'venues', 'is_active', 'scoring_rule_json', 'start_number_rules', 'updated_at'],
            'version_column' => 'updated_at',
        ],
        'classes' => [
            'table' => 'classes',
            'id_column' => 'id',
            'columns' => ['id', 'event_id', 'label', 'arena', 'start_time', 'end_time', 'max_starters', 'judge_assignments', 'rules_json', 'tiebreaker_json', 'scoring_rule_snapshot', 'updated_at'],
            'version_column' => 'updated_at',
            'dependencies' => [
                ['column' => 'event_id', 'table' => 'events', 'reference' => 'id'],
            ],
        ],
        'entries' => [
            'table' => 'entries',
            'id_column' => 'id',
            'columns' => ['id', 'event_id', 'class_id', 'party_id', 'horse_id', 'status', 'fee_paid_at', 'created_at', 'updated_at', 'start_number_display', 'start_number_raw', 'start_number_assignment_id', 'start_number_rule_snapshot', 'start_number_allocation_entity', 'start_number_locked_at'],
            'version_column' => 'updated_at',
            'fallback_version_column' => 'created_at',
            'dependencies' => [
                ['column' => 'event_id', 'table' => 'events', 'reference' => 'id'],
                ['column' => 'class_id', 'table' => 'classes', 'reference' => 'id'],
                ['column' => 'party_id', 'table' => 'parties', 'reference' => 'id'],
                ['column' => 'horse_id', 'table' => 'horses', 'reference' => 'id'],
            ],
        ],
        'starts' => [
            'table' => 'startlist_items',
            'id_column' => 'id',
            'columns' => ['id', 'class_id', 'entry_id', 'position', 'planned_start', 'state', 'note', 'created_at', 'updated_at', 'start_number_display', 'start_number_raw', 'start_number_assignment_id', 'start_number_rule_snapshot', 'start_number_allocation_entity', 'start_number_locked_at'],
            'version_column' => 'updated_at',
            'fallback_version_column' => 'created_at',
            'dependencies' => [
                ['column' => 'class_id', 'table' => 'classes', 'reference' => 'id'],
                ['column' => 'entry_id', 'table' => 'entries', 'reference' => 'id'],
            ],
        ],
        'schedules' => [
            'table' => 'schedule_shifts',
            'id_column' => 'id',
            'columns' => ['id', 'class_id', 'shift_minutes', 'created_at', 'updated_at'],
            'version_column' => 'updated_at',
            'fallback_version_column' => 'created_at',
            'dependencies' => [
                ['column' => 'class_id', 'table' => 'classes', 'reference' => 'id'],
            ],
        ],
        'scores' => [
            'alias_of' => 'results',
        ],
        'results' => [
            'table' => 'results',
            'id_column' => 'id',
            'columns' => ['id', 'startlist_id', 'scores_json', 'total', 'penalties', 'status', 'signed_by', 'signed_at', 'signature_hash', 'created_at', 'updated_at', 'breakdown_json', 'rule_snapshot', 'engine_version', 'tiebreak_path', 'rank', 'eliminated'],
            'version_column' => 'updated_at',
            'fallback_version_column' => 'created_at',
            'dependencies' => [
                ['column' => 'startlist_id', 'table' => 'startlist_items', 'reference' => 'id'],
            ],
        ],
        'announcements' => [
            'table' => 'notifications',
            'id_column' => 'id',
            'columns' => ['id', 'type', 'payload', 'created_at'],
            'version_column' => 'created_at',
        ],
        'helpers' => [
            'alias_of' => 'helper_shifts',
        ],
        'helper_shifts' => [
            'table' => 'helper_shifts',
            'id_column' => 'id',
            'columns' => ['id', 'role', 'station', 'party_id', 'start_time', 'end_time', 'token', 'checked_in_at', 'created_at', 'updated_at'],
            'version_column' => 'updated_at',
            'fallback_version_column' => 'created_at',
            'dependencies' => [
                ['column' => 'party_id', 'table' => 'parties', 'reference' => 'id'],
            ],
        ],
        'payments' => [
            'table' => null,
        ],
        'audit' => [
            'table' => 'audit_log',
            'id_column' => 'id',
            'columns' => ['id', 'entity', 'entity_id', 'action', 'user_id', 'before_state', 'after_state', 'created_at'],
            'version_column' => 'created_at',
        ],
    ];

    private const SCOPE_ORDER = [
        'clubs',
        'parties',
        'person_profiles',
        'party_roles',
        'horses',
        'events',
        'classes',
        'entries',
        'starts',
        'schedules',
        'results',
        'scores',
        'helpers',
        'helper_shifts',
        'announcements',
        'payments',
        'audit',
    ];

    public function __construct(PDO $pdo, InstanceConfiguration $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function getCursor(): SyncCursor
    {
        $stmt = $this->pdo->prepare('SELECT value FROM system_settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute(['key' => 'sync_cursor']);
        $value = $stmt->fetchColumn();

        if ($value === false || $value === null || $value === '') {
            return new SyncCursor();
        }

        $decoded = json_decode((string) $value, true);
        if (is_array($decoded) && isset($decoded['value'])) {
            return SyncCursor::fromArray($decoded);
        }

        return new SyncCursor((string) $value);
    }

    public function setCursor(SyncCursor $cursor): void
    {
        $payload = json_encode($cursor->toArray(), JSON_THROW_ON_ERROR);
        $timestamp = (new DateTimeImmutable())->format('c');
        $update = $this->pdo->prepare('UPDATE system_settings SET value = :value, updated_at = :updated WHERE setting_key = :key');
        $update->execute([
            'value' => $payload,
            'updated' => $timestamp,
            'key' => 'sync_cursor',
        ]);

        if ($update->rowCount() === 0) {
            $insert = $this->pdo->prepare('INSERT INTO system_settings (setting_key, value, updated_at) VALUES (:key, :value, :updated)');
            $insert->execute([
                'key' => 'sync_cursor',
                'value' => $payload,
                'updated' => $timestamp,
            ]);
        }

        $lastStmt = $this->pdo->prepare('UPDATE system_settings SET value = :value, updated_at = :updated WHERE setting_key = :last');
        $lastStmt->execute([
            'value' => $cursor->value(),
            'updated' => $timestamp,
            'last' => 'sync_last_completed_at',
        ]);

        if ($lastStmt->rowCount() === 0) {
            $insertLast = $this->pdo->prepare('INSERT INTO system_settings (setting_key, value, updated_at) VALUES (:key, :value, :updated)');
            $insertLast->execute([
                'key' => 'sync_last_completed_at',
                'value' => $cursor->value(),
                'updated' => $timestamp,
            ]);
        }
    }

    public function exportChanges(Since $since, Scopes $scopes): ChangeSet
    {
        $scopeNames = $scopes->toArray();
        $this->ensureBaseline($scopeNames);

        $placeholders = implode(',', array_fill(0, count($scopeNames), '?'));
        $sql = sprintf('SELECT scope, entity_id, version, checksum FROM sync_state WHERE scope IN (%s) AND version_epoch > :epoch ORDER BY version_epoch ASC, entity_id ASC', $placeholders);
        $stmt = $this->pdo->prepare($sql);
        $params = $scopeNames;
        $params[':epoch'] = $since->epoch();
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $changeSet = new ChangeSet([], $this->config->get('instance_role'), $since->value());
        foreach ($rows as $row) {
            $changeSet->add($row['scope'], [
                'id' => $row['entity_id'],
                'version' => $row['version'],
                'data' => null,
                'meta' => ['checksum' => $row['checksum']],
            ]);
        }

        return $changeSet;
    }

    public function pull(array $scopeMap): ChangeSet
    {
        $entries = [];
        foreach ($scopeMap as $scope => $definition) {
            $ids = $definition['ids'] ?? $definition;
            if (!is_array($ids)) {
                continue;
            }
            $rows = $this->loadRecords((string) $scope, $ids);
            if ($rows) {
                $entries[$scope] = $rows;
            }
        }

        return new ChangeSet($entries, $this->config->get('instance_role'));
    }

    public function validate(ChangeSet $changeSet): ValidationReport
    {
        $report = new ValidationReport();
        foreach ($changeSet->all() as $scope => $records) {
            $definition = $this->definitionFor($scope);
            if ($definition === null || ($definition['table'] ?? null) === null) {
                foreach ($records as $record) {
                    $report->addIssue($scope, $record['id'], 'INVALID_SCOPE', \t('sync.api.errors.scope_not_supported'));
                }
                continue;
            }
            foreach ($records as $record) {
                if (!is_array($record['data'])) {
                    $report->addIssue($scope, $record['id'], 'SCHEMA_VALIDATION_FAILED', \t('sync.api.errors.record_missing_data'));
                    continue;
                }

                $allowed = $definition['columns'] ?? [];
                foreach ($record['data'] as $key => $_value) {
                    if ($key === $definition['id_column']) {
                        continue;
                    }
                    if ($allowed && !in_array($key, $allowed, true)) {
                        $report->addIssue($scope, $record['id'], 'SCHEMA_VALIDATION_FAILED', \t('sync.api.errors.field_not_allowed', ['field' => $key]));
                    }
                }
            }
        }

        return $report;
    }

    public function import(ChangeSet $changeSet, ImportReport $report): void
    {
        $scopes = $this->orderScopes($changeSet->scopes());
        foreach ($scopes as $scope) {
            $definition = $this->definitionFor($scope);
            if ($definition === null || ($definition['table'] ?? null) === null) {
                foreach ($changeSet->forScope($scope) as $record) {
                    $report->addRejected($scope, $record['id'], 'INVALID_SCOPE', \t('sync.api.errors.scope_not_synchronised'));
                }
                continue;
            }

            foreach ($changeSet->forScope($scope) as $record) {
                $data = $record['data'];
                if (!is_array($data)) {
                    $report->addRejected($scope, $record['id'], 'SCHEMA_VALIDATION_FAILED', \t('sync.api.errors.record_missing_data'));
                    continue;
                }

                try {
                    $this->pdo->beginTransaction();
                    $message = $this->applyRecord($scope, $definition, $record['id'], $record['version'], $data, $changeSet->origin());
                    $this->pdo->commit();
                    $report->addAccepted($scope, $record['id'], $message);
                } catch (SyncException $exception) {
                    $this->pdo->rollBack();
                    $report->addRejected($scope, $record['id'], $exception->getErrorCode(), $exception->getMessage());
                } catch (Throwable $throwable) {
                    $this->pdo->rollBack();
                    $report->addError('INTERNAL_ERROR', $throwable->getMessage());
                }
            }
        }
    }

    /**
     * @return array<string, int>
     */
    public function entityCounts(?array $scopes = null): array
    {
        $targets = $scopes ?? array_keys(self::SCOPE_DEFINITIONS);
        $result = [];
        foreach ($targets as $scope) {
            $definition = $this->definitionFor($scope);
            $table = $definition['table'] ?? null;
            if ($table === null) {
                $result[$scope] = 0;
                continue;
            }
            try {
                $stmt = $this->pdo->query(sprintf('SELECT COUNT(*) FROM %s', $table));
                $result[$scope] = (int) ($stmt ? $stmt->fetchColumn() : 0);
            } catch (PDOException) {
                $result[$scope] = 0;
            }
        }
        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentLogs(int $limit = 20): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT id, direction, operation, scopes, counts, status, message, duration_ms, created_at, actor, transaction_id FROM sync_logs ORDER BY id DESC LIMIT :limit');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }

        foreach ($rows as &$row) {
            $row['scopes'] = $this->decodeJson($row['scopes']);
            $row['counts'] = $this->decodeJson($row['counts']);
        }
        unset($row);

        return $rows;
    }

    public function logOperation(string $direction, string $operation, array $scopes, string $status, string $message, array $counts = [], ?int $durationMs = null, ?string $transactionId = null): void
    {
        $now = (new DateTimeImmutable())->format('c');
        $stmt = $this->pdo->prepare('INSERT INTO sync_logs (direction, operation, scopes, counts, status, message, duration_ms, created_at, actor, transaction_id) VALUES (:direction, :operation, :scopes, :counts, :status, :message, :duration, :created, :actor, :transaction)');
        $stmt->execute([
            'direction' => $direction,
            'operation' => $operation,
            'scopes' => json_encode(array_values($scopes), JSON_THROW_ON_ERROR),
            'counts' => json_encode($counts, JSON_THROW_ON_ERROR),
            'status' => $status,
            'message' => $message,
            'duration' => $durationMs,
            'created' => $now,
            'actor' => $this->config->get('peer_base_url') ?: 'peer',
            'transaction' => $transactionId,
        ]);
    }

    public function createTransaction(string $direction, string $operation, array $scopes, array $summary): string
    {
        $id = bin2hex(random_bytes(16));
        $stmt = $this->pdo->prepare('INSERT INTO sync_transactions (id, direction, operation, scopes, status, summary, created_at) VALUES (:id, :direction, :operation, :scopes, :status, :summary, :created)');
        $stmt->execute([
            'id' => $id,
            'direction' => $direction,
            'operation' => $operation,
            'scopes' => json_encode(array_values($scopes), JSON_THROW_ON_ERROR),
            'status' => 'pending',
            'summary' => json_encode($summary, JSON_THROW_ON_ERROR),
            'created' => (new DateTimeImmutable())->format('c'),
        ]);

        return $id;
    }

    public function acknowledge(string $transactionId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE sync_transactions SET status = :status, acknowledged_at = :ack WHERE id = :id AND status != :status');
        $stmt->execute([
            'status' => 'acknowledged',
            'ack' => (new DateTimeImmutable())->format('c'),
            'id' => $transactionId,
        ]);

        return $stmt->rowCount() > 0;
    }

    private function applyRecord(string $scope, array $definition, string $id, string $version, array $data, string $origin): string
    {
        $realScope = $definition['alias_of'] ?? $scope;
        if (isset($definition['alias_of'])) {
            $definition = $this->definitionFor($definition['alias_of']);
            if ($definition === null) {
                throw new SyncException('INVALID_SCOPE', \t('sync.api.errors.alias_unknown_scope'));
            }
        }

        $table = $definition['table'];
        $idColumn = $definition['id_column'];
        $allowed = $definition['columns'] ?? [];
        $normalized = $this->filterData($allowed, $data);
        $normalized[$idColumn] = $this->castIdentifier($id);

        if ($this->hasColumn($definition, 'updated_at')) {
            $normalized['updated_at'] = $version;
        }

        $existingState = $this->fetchState($realScope, $id);
        $incomingCursor = new SyncCursor($version);
        $checksum = $this->checksum($normalized);

        if ($existingState !== null) {
            $existingEpoch = (int) $existingState['version_epoch'];
            if ($incomingCursor->epoch() < $existingEpoch) {
                if (!$this->shouldAcceptOlder($origin, $incomingCursor->epoch(), $existingEpoch)) {
                    throw new SyncException('CONFLICT_POLICY_VIOLATION', \t('sync.api.errors.change_outdated'));
                }
            }

            if ($incomingCursor->epoch() === $existingEpoch && $existingState['checksum'] === $checksum) {
                return 'noop';
            }
        }

        $existingRow = $this->loadRow($definition, $id);
        $merged = $this->mergeWithExisting($normalized, $existingRow);
        $this->assertDependencies($realScope, $definition, $merged);
        $this->persistEntity($definition, $merged, $existingRow !== null);
        $this->upsertState($realScope, $id, $incomingCursor->value(), $checksum, ['origin' => $origin]);

        return $existingState ? 'updated' : 'inserted';
    }

    private function mergeWithExisting(array $data, ?array $existing): array
    {
        if ($existing === null) {
            return $data;
        }

        foreach ($data as $key => $value) {
            $existing[$key] = $value;
        }

        return $existing;
    }

    private function assertDependencies(string $scope, array $definition, array $data): void
    {
        $dependencies = $definition['dependencies'] ?? [];
        foreach ($dependencies as $dependency) {
            $column = $dependency['column'];
            if (!array_key_exists($column, $data) || $data[$column] === null || $data[$column] === '') {
                continue;
            }
            if (!$this->existsInTable($dependency['table'], $dependency['reference'], $data[$column])) {
                throw new SyncException('FOREIGN_KEY_MISSING', \t('sync.api.errors.dependency_missing', ['column' => $column, 'value' => $data[$column]]));
            }
        }
    }

    private function persistEntity(array $definition, array $data, bool $exists): void
    {
        $table = $definition['table'];
        $idColumn = $definition['id_column'];

        $columns = array_keys($data);
        $updates = array_filter($columns, static fn ($column) => $column !== $idColumn);

        if ($exists) {
            if ($updates === []) {
                return;
            }

            $updateSql = sprintf('UPDATE %s SET %s WHERE %s = :%s', $table, implode(', ', array_map(static fn ($column) => sprintf('%s = :%s', $column, $column), $updates)), $idColumn, $idColumn);
            $stmt = $this->pdo->prepare($updateSql);
            $stmt->execute($this->prepareParams($data));

            return;
        }

        $insertSql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), implode(', ', array_map(static fn ($column) => ':' . $column, $columns)));
        $insert = $this->pdo->prepare($insertSql);
        $insert->execute($this->prepareParams($data));
    }

    private function prepareParams(array $data): array
    {
        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }
        return $params;
    }

    private function existsInTable(string $table, string $column, mixed $value): bool
    {
        $stmt = $this->pdo->prepare(sprintf('SELECT 1 FROM %s WHERE %s = :value LIMIT 1', $table, $column));
        $stmt->execute(['value' => $value]);
        return (bool) $stmt->fetchColumn();
    }

    private function loadRow(array $definition, string $id): ?array
    {
        $table = $definition['table'];
        $idColumn = $definition['id_column'];
        $stmt = $this->pdo->prepare(sprintf('SELECT * FROM %s WHERE %s = :id LIMIT 1', $table, $idColumn));
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function filterData(array $allowed, array $data): array
    {
        if ($allowed === []) {
            return $data;
        }
        $filtered = [];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $filtered[$column] = $data[$column];
            }
        }
        return $filtered;
    }

    /**
     * @return array<int, array{id: string, version: string, data: array, meta: array<string, mixed>}>
     */
    private function loadRecords(string $scope, array $ids): array
    {
        $definition = $this->definitionFor($scope);
        if ($definition === null || ($definition['table'] ?? null) === null) {
            return [];
        }

        if (isset($definition['alias_of'])) {
            $scope = $definition['alias_of'];
            $definition = $this->definitionFor($scope);
            if ($definition === null) {
                return [];
            }
        }

        $table = $definition['table'];
        $idColumn = $definition['id_column'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = sprintf('SELECT * FROM %s WHERE %s IN (%s)', $table, $idColumn, $placeholders);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($ids));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $id = (string) $row[$idColumn];
            $state = $this->fetchState($scope, $id);
            $version = $state['version'] ?? $this->determineVersion($definition, $row);
            $result[] = [
                'id' => $id,
                'version' => $version,
                'data' => $row,
                'meta' => ['checksum' => $state['checksum'] ?? $this->checksum($row)],
            ];
        }

        return $result;
    }

    private function fetchState(string $scope, string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sync_state WHERE scope = :scope AND entity_id = :id LIMIT 1');
        $stmt->execute([
            'scope' => $scope,
            'id' => $id,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function upsertState(string $scope, string $id, string $version, ?string $checksum, array $meta = []): void
    {
        $cursor = new SyncCursor($version);
        $update = $this->pdo->prepare('UPDATE sync_state SET version = :version, version_epoch = :epoch, checksum = :checksum, payload_meta = :meta, updated_at = :updated WHERE scope = :scope AND entity_id = :id');
        $update->execute([
            'version' => $cursor->value(),
            'epoch' => $cursor->epoch(),
            'checksum' => $checksum,
            'meta' => $meta ? json_encode($meta, JSON_THROW_ON_ERROR) : null,
            'updated' => $cursor->value(),
            'scope' => $scope,
            'id' => $id,
        ]);

        if ($update->rowCount() === 0) {
            $insert = $this->pdo->prepare('INSERT INTO sync_state (scope, entity_id, version, version_epoch, checksum, payload_meta, updated_at) VALUES (:scope, :id, :version, :epoch, :checksum, :meta, :updated)');
            $insert->execute([
                'scope' => $scope,
                'id' => $id,
                'version' => $cursor->value(),
                'epoch' => $cursor->epoch(),
                'checksum' => $checksum,
                'meta' => $meta ? json_encode($meta, JSON_THROW_ON_ERROR) : null,
                'updated' => $cursor->value(),
            ]);
        }
    }

    private function ensureBaseline(array $scopes): void
    {
        foreach ($scopes as $scope) {
            $definition = $this->definitionFor($scope);
            if ($definition === null || ($definition['table'] ?? null) === null) {
                continue;
            }

            if (isset($definition['alias_of'])) {
                $scope = $definition['alias_of'];
                $definition = $this->definitionFor($scope);
                if ($definition === null) {
                    continue;
                }
            }

            $table = $definition['table'];
            $idColumn = $definition['id_column'];
            try {
                $stmt = $this->pdo->query(sprintf('SELECT %s, %s FROM %s', $idColumn, $definition['version_column'] ?? $idColumn, $table));
                if (!$stmt) {
                    continue;
                }
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $id = (string) $row[$idColumn];
                    if ($this->fetchState($scope, $id) !== null) {
                        continue;
                    }
                    $version = $this->determineVersion($definition, $row);
                    $this->upsertState($scope, $id, $version, null);
                }
            } catch (PDOException) {
                continue;
            }
        }
    }

    private function determineVersion(array $definition, array $row): string
    {
        $versionField = $definition['version_column'] ?? null;
        $fallbackField = $definition['fallback_version_column'] ?? null;
        $candidate = null;
        if ($versionField && !empty($row[$versionField])) {
            $candidate = (string) $row[$versionField];
        } elseif ($fallbackField && !empty($row[$fallbackField])) {
            $candidate = (string) $row[$fallbackField];
        }

        try {
            return (new DateTimeImmutable($candidate ?? 'now'))->format('c');
        } catch (Throwable) {
            return (new DateTimeImmutable())->format('c');
        }
    }

    private function checksum(array $data): string
    {
        ksort($data);
        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function decodeJson(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return $decoded ?? [];
    }

    private function hasColumn(array $definition, string $column): bool
    {
        return in_array($column, $definition['columns'] ?? [], true);
    }

    private function castIdentifier(string $id): mixed
    {
        if (ctype_digit($id)) {
            return (int) $id;
        }
        return $id;
    }

    private function shouldAcceptOlder(string $origin, int $incomingEpoch, int $existingEpoch): bool
    {
        $mode = $this->config->get('operation_mode');
        $role = $this->config->get('instance_role');
        $origin = strtoupper($origin);

        if ($incomingEpoch >= $existingEpoch) {
            return true;
        }

        if ($mode === InstanceConfiguration::MODE_TOURNAMENT) {
            if ($role === InstanceConfiguration::ROLE_ONLINE && $origin === InstanceConfiguration::ROLE_LOCAL) {
                return true;
            }
            if ($role === InstanceConfiguration::ROLE_LOCAL && $origin === InstanceConfiguration::ROLE_ONLINE) {
                return false;
            }
        }

        if ($mode === InstanceConfiguration::MODE_PRE_TOURNAMENT) {
            return $role === InstanceConfiguration::ROLE_ONLINE && $origin === InstanceConfiguration::ROLE_ONLINE;
        }

        if ($mode === InstanceConfiguration::MODE_POST_TOURNAMENT) {
            if ($role === InstanceConfiguration::ROLE_ONLINE && $origin === InstanceConfiguration::ROLE_LOCAL) {
                return false;
            }
        }

        return $incomingEpoch >= $existingEpoch;
    }

    private function definitionFor(string $scope): ?array
    {
        return self::SCOPE_DEFINITIONS[$scope] ?? null;
    }

    /**
     * @return string[]
     */
    private function orderScopes(array $scopes): array
    {
        $ordered = [];
        foreach (self::SCOPE_ORDER as $candidate) {
            if (in_array($candidate, $scopes, true)) {
                $ordered[] = $candidate;
            }
        }
        foreach ($scopes as $scope) {
            if (!in_array($scope, $ordered, true)) {
                $ordered[] = $scope;
            }
        }
        return $ordered;
    }
}
