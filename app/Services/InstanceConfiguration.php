<?php
namespace App\Services;

use DateTimeImmutable;
use PDO;
use PDOException;

class InstanceConfiguration
{
    public const VERSION = '2.0.0';

    public const ROLE_ONLINE = 'ONLINE';
    public const ROLE_LOCAL = 'LOCAL';
    public const ROLE_MIRROR = 'MIRROR';

    public const MODE_PRE_TOURNAMENT = 'PRE_TOURNAMENT';
    public const MODE_TOURNAMENT = 'TOURNAMENT';
    public const MODE_POST_TOURNAMENT = 'POST_TOURNAMENT';

    private const DEFAULTS = [
        'instance_role' => self::ROLE_ONLINE,
        'operation_mode' => self::MODE_PRE_TOURNAMENT,
        'peer_base_url' => null,
        'peer_api_token' => null,
        'peer_turnier_id' => null,
        'peer_last_health_status' => 'unknown',
        'peer_last_health_message' => null,
        'peer_last_health_checked_at' => null,
        'peer_last_dry_run_at' => null,
        'peer_last_dry_run_summary' => null,
        'sync_last_completed_at' => null,
    ];

    private PDO $pdo;

    /**
     * @var array<string, mixed>
     */
    private array $settings = [];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $viewCache = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->settings = self::DEFAULTS;
        $this->load();
    }

    public static function roles(): array
    {
        return [self::ROLE_ONLINE, self::ROLE_LOCAL, self::ROLE_MIRROR];
    }

    public static function modes(): array
    {
        return [self::MODE_PRE_TOURNAMENT, self::MODE_TOURNAMENT, self::MODE_POST_TOURNAMENT];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->settings;
    }

    public function hasPeerToken(): bool
    {
        $value = $this->settings['peer_api_token'] ?? null;
        return $value !== null && $value !== '';
    }

    public function canWrite(): bool
    {
        $role = (string) $this->settings['instance_role'];
        $mode = (string) $this->settings['operation_mode'];

        if ($role === self::ROLE_MIRROR) {
            return false;
        }

        return match ($mode) {
            self::MODE_PRE_TOURNAMENT => $role === self::ROLE_ONLINE,
            self::MODE_TOURNAMENT => $role === self::ROLE_LOCAL,
            self::MODE_POST_TOURNAMENT => $role === self::ROLE_ONLINE,
            default => $role === self::ROLE_ONLINE,
        };
    }

    public function readOnlyMessage(?string $context = null): string
    {
        $role = (string) $this->settings['instance_role'];
        $mode = (string) $this->settings['operation_mode'];

        if ($role === self::ROLE_MIRROR) {
            return 'Diese Instanz läuft als Mirror und ist vollständig schreibgeschützt.';
        }

        if ($mode === self::MODE_TOURNAMENT && $role === self::ROLE_ONLINE) {
            return 'Im Turnierbetrieb führt die lokale Instanz die Schreiboperationen aus. Diese Online-Instanz ist derzeit read-only.';
        }

        if ($mode === self::MODE_PRE_TOURNAMENT && $role === self::ROLE_LOCAL) {
            return 'Vor dem Turnier ist die lokale Instanz gesperrt. Änderungen erfolgen über die Online-Instanz.';
        }

        if ($mode === self::MODE_POST_TOURNAMENT && $role === self::ROLE_LOCAL) {
            return 'Nach dem Turnier ist die lokale Instanz archiviert und nur noch lesbar.';
        }

        return 'Schreiboperationen sind derzeit nicht erlaubt.';
    }

    /**
     * @param array<string, mixed> $values
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public function diff(array $values): array
    {
        return $this->computeChanges($values);
    }

    /**
     * @param array<string, mixed> $values
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public function save(array $values): array
    {
        $changes = $this->computeChanges($values);
        if (!$changes['after']) {
            return $changes;
        }

        $now = (new DateTimeImmutable())->format('c');
        foreach ($changes['after'] as $key => $value) {
            $this->persist($key, $value, $now);
            $this->settings[$key] = $value;
        }

        $this->viewCache = null;
        return $changes;
    }

    public function recordHealthResult(bool $ok, string $message = '', ?array $payload = null): void
    {
        $status = $ok ? 'ok' : 'error';
        $updates = [
            'peer_last_health_status' => $status,
            'peer_last_health_message' => $message ?: ($payload['status'] ?? ($ok ? 'OK' : 'Fehler')),
            'peer_last_health_checked_at' => (new DateTimeImmutable())->format('c'),
        ];

        $this->save($updates);
    }

    public function recordDryRun(array $summary): void
    {
        $updates = [
            'peer_last_dry_run_at' => (new DateTimeImmutable())->format('c'),
            'peer_last_dry_run_summary' => json_encode($summary, JSON_THROW_ON_ERROR),
        ];
        $this->save($updates);
    }

    /**
     * @return array{role: string, role_label: string, mode: string, mode_label: string, read_only: bool, read_only_message: ?string, peer: array<string, mixed>, status_text: string}
     */
    public function viewContext(): array
    {
        if ($this->viewCache !== null) {
            return $this->viewCache;
        }

        $role = (string) $this->settings['instance_role'];
        $mode = (string) $this->settings['operation_mode'];
        $readOnly = !$this->canWrite();
        $roleLabel = $this->roleLabel($role);
        $modeLabel = $this->modeLabel($mode);
        $peer = $this->peerSummary();

        $this->viewCache = [
            'role' => $role,
            'role_label' => $roleLabel,
            'mode' => $mode,
            'mode_label' => $modeLabel,
            'read_only' => $readOnly,
            'read_only_message' => $readOnly ? $this->readOnlyMessage() : null,
            'peer' => $peer,
            'status_text' => $roleLabel . ' · ' . $modeLabel,
        ];

        return $this->viewCache;
    }

    /**
     * @return array{status: string, label: string, class: string, checked_at: ?string, formatted_checked_at: ?string, message: ?string, configured: bool}
     */
    public function peerSummary(): array
    {
        $status = (string) ($this->settings['peer_last_health_status'] ?? 'unknown');
        $message = $this->settings['peer_last_health_message'] ?? null;
        $checkedAt = $this->settings['peer_last_health_checked_at'] ?? null;
        $configured = ($this->settings['peer_base_url'] ?? null) !== null && ($this->settings['peer_base_url'] ?? '') !== '';

        $label = match ($status) {
            'ok' => 'Peer verbunden',
            'error' => 'Peer Fehler',
            default => 'Peer nicht geprüft',
        };

        $class = match ($status) {
            'ok' => 'text-success',
            'error' => 'text-danger',
            default => 'text-muted',
        };

        $formatted = null;
        if ($checkedAt) {
            try {
                $formatted = (new DateTimeImmutable($checkedAt))->format('d.m.Y H:i');
            } catch (\Exception) {
                $formatted = $checkedAt;
            }
        }

        return [
            'status' => $status,
            'label' => $label,
            'class' => $class,
            'checked_at' => $checkedAt,
            'formatted_checked_at' => $formatted,
            'message' => $message,
            'configured' => $configured,
        ];
    }

    /**
     * @return array{timestamp: ?string, formatted_timestamp: ?string, local: array<string, int>, remote: array<string, int>, differences: array<string, int>}
     */
    public function lastDryRun(): array
    {
        $timestamp = $this->settings['peer_last_dry_run_at'] ?? null;
        $summaryRaw = $this->settings['peer_last_dry_run_summary'] ?? null;
        $local = ['entries' => 0, 'classes' => 0, 'results' => 0];
        $remote = $local;
        $differences = $local;

        if ($summaryRaw) {
            $decoded = json_decode((string) $summaryRaw, true, 512, JSON_THROW_ON_ERROR);
            $local = $decoded['local'] ?? $local;
            $remote = $decoded['remote'] ?? $remote;
            $differences = $decoded['differences'] ?? $differences;
        }

        $formatted = null;
        if ($timestamp) {
            try {
                $formatted = (new DateTimeImmutable($timestamp))->format('d.m.Y H:i');
            } catch (\Exception) {
                $formatted = $timestamp;
            }
        }

        return [
            'timestamp' => $timestamp,
            'formatted_timestamp' => $formatted,
            'local' => $local,
            'remote' => $remote,
            'differences' => $differences,
        ];
    }

    /**
     * @return array{event: ?array, counts: array<string, int>}
     */
    public function collectLocalCounts(): array
    {
        $event = $this->activeEvent();
        $counts = [
            'entries' => 0,
            'classes' => 0,
            'results' => 0,
        ];

        if (!$event) {
            return ['event' => null, 'counts' => $counts];
        }

        $eventId = (int) $event['id'];

        $counts['entries'] = $this->countByEvent('entries', 'event_id', $eventId);
        $counts['classes'] = $this->countByEvent('classes', 'event_id', $eventId);
        $counts['results'] = $this->countResults($eventId);

        return ['event' => $event, 'counts' => $counts];
    }

    public function roleLabel(string $role): string
    {
        return match ($role) {
            self::ROLE_ONLINE => 'Online-Instanz',
            self::ROLE_LOCAL => 'Lokale Turnier-Instanz',
            self::ROLE_MIRROR => 'Mirror/Slave',
            default => 'Unbekannte Instanz',
        };
    }

    public function modeLabel(string $mode): string
    {
        return match ($mode) {
            self::MODE_PRE_TOURNAMENT => 'Prä-Turnier',
            self::MODE_TOURNAMENT => 'Turnierbetrieb',
            self::MODE_POST_TOURNAMENT => 'Post-Turnier',
            default => 'Unbekannter Modus',
        };
    }

    /**
     * @param array<string, mixed> $values
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    private function computeChanges(array $values): array
    {
        $before = [];
        $after = [];

        foreach ($values as $key => $value) {
            if (!array_key_exists($key, $this->settings)) {
                continue;
            }
            $normalized = $this->normalize($key, $value);
            if ($normalized === '__skip__') {
                continue;
            }
            $current = $this->settings[$key] ?? null;
            if ($current === $normalized) {
                continue;
            }
            $before[$key] = $current;
            $after[$key] = $normalized;
        }

        return ['before' => $before, 'after' => $after];
    }

    private function normalize(string $key, mixed $value): mixed
    {
        if ($key === 'peer_api_token' && $value === '__keep__') {
            return '__skip__';
        }

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($key === 'instance_role') {
            $value = strtoupper((string) $value);
            return in_array($value, self::roles(), true) ? $value : self::ROLE_ONLINE;
        }

        if ($key === 'operation_mode') {
            $value = strtoupper((string) $value);
            return in_array($value, self::modes(), true) ? $value : self::MODE_PRE_TOURNAMENT;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        if ($value === '') {
            return null;
        }

        return (string) $value;
    }

    private function persist(string $key, mixed $value, string $timestamp): void
    {
        try {
            $update = $this->pdo->prepare('UPDATE system_settings SET value = :value, updated_at = :updated WHERE setting_key = :key');
            $update->execute([
                'value' => $value,
                'updated' => $timestamp,
                'key' => $key,
            ]);

            if ($update->rowCount() === 0) {
                $insert = $this->pdo->prepare('INSERT INTO system_settings (setting_key, value, updated_at) VALUES (:key, :value, :updated)');
                $insert->execute([
                    'key' => $key,
                    'value' => $value,
                    'updated' => $timestamp,
                ]);
            }
        } catch (PDOException) {
            // Ignorieren wenn Tabelle (noch) nicht existiert.
        }
    }

    private function load(): void
    {
        try {
            $stmt = $this->pdo->query('SELECT setting_key, value FROM system_settings');
        } catch (PDOException) {
            return;
        }

        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as $row) {
            $this->settings[$row['setting_key']] = $row['value'];
        }
    }

    private function activeEvent(): ?array
    {
        try {
            $stmt = $this->pdo->query('SELECT * FROM events WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        } catch (PDOException) {
            return null;
        }

        return $row ?: null;
    }

    private function countByEvent(string $table, string $column, int $eventId): int
    {
        try {
            $stmt = $this->pdo->prepare(sprintf('SELECT COUNT(*) FROM %s WHERE %s = :event_id', $table, $column));
            $stmt->execute(['event_id' => $eventId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException) {
            return 0;
        }
    }

    private function countResults(int $eventId): int
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM results r
                    JOIN startlist_items si ON si.id = r.startlist_id
                    JOIN entries e ON e.id = si.entry_id
                 WHERE e.event_id = :event_id'
            );
            $stmt->execute(['event_id' => $eventId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException) {
            return 0;
        }
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public function redact(array $values): array
    {
        $redacted = $values;
        if (array_key_exists('peer_api_token', $redacted) && $redacted['peer_api_token']) {
            $redacted['peer_api_token'] = '***';
        }
        return $redacted;
    }
}

