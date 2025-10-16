<?php
namespace App\Sync;

use App\Core\App;
use App\Services\InstanceConfiguration;

class SyncService
{
    private SyncRepository $repository;
    private InstanceConfiguration $config;

    public function __construct(SyncRepository $repository, InstanceConfiguration $config)
    {
        $this->repository = $repository;
        $this->config = $config;
    }

    public static function make(): self
    {
        $pdo = App::get('pdo');
        $config = App::get('instance');
        if (!$pdo || !$config instanceof InstanceConfiguration) {
            throw new SyncException('SERVICE_UNAVAILABLE', 'Synchronisation nicht initialisiert.', 503);
        }

        $repository = new SyncRepository($pdo, $config);
        return new self($repository, $config);
    }

    public function getCursor(): SyncCursor
    {
        return $this->repository->getCursor();
    }

    public function setCursor(SyncCursor $cursor): void
    {
        $this->repository->setCursor($cursor);
    }

    public function exportChanges(Since $since, Scopes $scopes): ChangeSet
    {
        return $this->repository->exportChanges($since, $scopes);
    }

    public function importChanges(ChangeSet $changeSet): ImportReport
    {
        $report = new ImportReport();
        $this->repository->import($changeSet, $report);
        return $report;
    }

    public function validateDelta(ChangeSet $changeSet): ValidationReport
    {
        return $this->repository->validate($changeSet);
    }

    public function enforcePolicy(SyncRequest $request): void
    {
        $mode = $this->config->get('operation_mode');
        $role = $this->config->get('instance_role');

        if ($this->config->get('instance_role') === InstanceConfiguration::ROLE_MIRROR && $request->isWrite()) {
            throw new SyncException('READ_ONLY_MODE', 'Mirror-Instanzen sind read-only.', 423);
        }

        if ($request->isWrite()) {
            if (!$this->config->canWrite()) {
                throw new SyncException('READ_ONLY_MODE', 'Diese Instanz ist derzeit schreibgeschützt.', 423);
            }

            if ($mode === InstanceConfiguration::MODE_POST_TOURNAMENT && $role === InstanceConfiguration::ROLE_LOCAL) {
                throw new SyncException('READ_ONLY_MODE', 'Lokale Instanz im Archiv-Modus ist schreibgeschützt.', 423);
            }
        }

        if ($mode === InstanceConfiguration::MODE_PRE_TOURNAMENT && $role === InstanceConfiguration::ROLE_LOCAL) {
            throw new SyncException('READ_ONLY_MODE', 'Lokale Instanz ist vor dem Turnier inaktiv.', 423);
        }
    }

    /**
     * @param array<string, array{id?: string, ids?: array<int, string|int>, limit?: int}> $scopeMap
     */
    public function pull(array $scopeMap): ChangeSet
    {
        return $this->repository->pull($scopeMap);
    }

    /**
     * @return array<string, int>
     */
    public function entityCounts(?array $scopes = null): array
    {
        return $this->repository->entityCounts($scopes);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentLogs(int $limit = 20): array
    {
        return $this->repository->recentLogs($limit);
    }

    public function logOperation(string $direction, string $operation, array $scopes, string $status, string $message, array $counts = [], ?int $durationMs = null, ?string $transactionId = null): void
    {
        $this->repository->logOperation($direction, $operation, $scopes, $status, $message, $counts, $durationMs, $transactionId);
    }

    public function createTransaction(string $direction, string $operation, array $scopes, array $summary): string
    {
        return $this->repository->createTransaction($direction, $operation, $scopes, $summary);
    }

    public function acknowledge(string $transactionId): bool
    {
        return $this->repository->acknowledge($transactionId);
    }
}
