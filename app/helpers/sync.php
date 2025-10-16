<?php
use App\Core\App;
use App\Services\InstanceConfiguration;
use App\Sync\ChangeSet;
use App\Sync\ImportReport;
use App\Sync\Scopes;
use App\Sync\Since;
use App\Sync\SyncCursor;
use App\Sync\SyncException;
use App\Sync\SyncRequest;
use App\Sync\SyncService;
use App\Sync\ValidationReport;

if (!function_exists('sync_service')) {
    function sync_service(): SyncService
    {
        static $service = null;
        if ($service instanceof SyncService) {
            return $service;
        }

        $instance = App::get('instance');
        if (!$instance instanceof InstanceConfiguration) {
            throw new SyncException('SERVICE_UNAVAILABLE', 'Instanzkonfiguration fehlt.', 503);
        }

        $service = SyncService::make();
        return $service;
    }
}

if (!function_exists('getSyncCursor')) {
    function getSyncCursor(): SyncCursor
    {
        return sync_service()->getCursor();
    }
}

if (!function_exists('setSyncCursor')) {
    function setSyncCursor(SyncCursor $cursor): void
    {
        sync_service()->setCursor($cursor);
    }
}

if (!function_exists('exportChanges')) {
    function exportChanges(Since $since, Scopes $scopes): ChangeSet
    {
        return sync_service()->exportChanges($since, $scopes);
    }
}

if (!function_exists('importChanges')) {
    function importChanges(ChangeSet $delta): ImportReport
    {
        return sync_service()->importChanges($delta);
    }
}

if (!function_exists('validateDelta')) {
    function validateDelta(ChangeSet $delta): ValidationReport
    {
        return sync_service()->validateDelta($delta);
    }
}

if (!function_exists('enforceReadWritePolicy')) {
    function enforceReadWritePolicy(SyncRequest $request): void
    {
        sync_service()->enforcePolicy($request);
    }
}

if (!function_exists('sync_available_scopes')) {
    /**
     * @return string[]
     */
    function sync_available_scopes(): array
    {
        return Scopes::ALL;
    }
}

if (!function_exists('sync_recent_logs')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function sync_recent_logs(int $limit = 20): array
    {
        return sync_service()->recentLogs($limit);
    }
}

if (!function_exists('sync_entity_counts')) {
    /**
     * @return array<string, int>
     */
    function sync_entity_counts(?array $scopes = null): array
    {
        return sync_service()->entityCounts($scopes);
    }
}

if (!function_exists('sync_pull_entities')) {
    /**
     * @param array<string, array{id?: string, ids?: array<int, string|int>, limit?: int}> $scopeMap
     */
    function sync_pull_entities(array $scopeMap): ChangeSet
    {
        return sync_service()->pull($scopeMap);
    }
}

if (!function_exists('sync_log_operation')) {
    function sync_log_operation(string $direction, string $operation, array $scopes, string $status, string $message, array $counts = [], ?int $durationMs = null, ?string $transactionId = null): void
    {
        sync_service()->logOperation($direction, $operation, $scopes, $status, $message, $counts, $durationMs, $transactionId);
    }
}

if (!function_exists('sync_create_transaction')) {
    function sync_create_transaction(string $direction, string $operation, array $scopes, array $summary): string
    {
        return sync_service()->createTransaction($direction, $operation, $scopes, $summary);
    }
}

if (!function_exists('sync_acknowledge')) {
    function sync_acknowledge(string $transactionId): bool
    {
        return sync_service()->acknowledge($transactionId);
    }
}
