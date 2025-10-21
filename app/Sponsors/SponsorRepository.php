<?php

namespace App\Sponsors;

use DateTimeImmutable;
use PDO;
use RuntimeException;

use function is_array;
use function json_decode;
use function json_encode;
use function trim;

class SponsorRepository
{
    public const TYPES = [
        'company',
        'individual',
        'club',
        'institution',
        'media_partner',
        'other',
    ];

    public const STATUSES = ['active', 'inactive', 'archived'];

    public const TIERS = ['platinum', 'gold', 'silver', 'bronze', 'partner', 'supporter'];

    public const VALUE_TYPES = ['cash', 'in_kind', 'service'];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listSponsors(?string $status = null, ?int $eventId = null): array
    {
        $sql = 'SELECT * FROM sponsors WHERE 1=1';
        $params = [];
        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        if ($eventId !== null) {
            $sql .= ' AND (linked_event_id IS NULL OR linked_event_id = :event)';
            $params['event'] = $eventId;
        }
        $sql .= ' ORDER BY priority DESC, ' . $this->tierOrderExpression() . ', COALESCE(display_name, name) ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    public function findSponsor(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sponsors WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function createSponsor(array $data): int
    {
        $payload = $this->normalize($data);
        $now = (new DateTimeImmutable('now'))->format('c');
        $payload['created_at'] = $now;
        $payload['updated_at'] = $now;

        $columns = array_keys($payload);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

        $sql = 'INSERT INTO sponsors (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt->execute($payload)) {
            throw new RuntimeException('Sponsor konnte nicht angelegt werden.');
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function updateSponsor(int $id, array $data): void
    {
        $payload = $this->normalize($data);
        $payload['updated_at'] = (new DateTimeImmutable('now'))->format('c');
        $payload['id'] = $id;

        $set = [];
        foreach ($payload as $column => $value) {
            if ($column === 'id') {
                continue;
            }
            $set[] = $column . ' = :' . $column;
        }

        if (!$set) {
            return;
        }

        $sql = 'UPDATE sponsors SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt->execute($payload)) {
            throw new RuntimeException('Sponsor konnte nicht aktualisiert werden.');
        }
    }

    public function deleteSponsor(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM sponsors WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function signageEntries(?int $eventId = null): array
    {
        $sql = 'SELECT * FROM sponsors WHERE status = "active" AND show_on_signage = 1';
        $params = [];
        if ($eventId !== null) {
            $sql .= ' AND (linked_event_id IS NULL OR linked_event_id = :event)';
            $params['event'] = $eventId;
        }
        $sql .= ' ORDER BY priority DESC, ' . $this->tierOrderExpression() . ', COALESCE(display_name, name) ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $hydrated = array_map(fn (array $row) => $this->hydrate($row), $rows);

        foreach ($hydrated as &$row) {
            $row['ticker_text'] = $this->buildTickerText($row);
        }
        unset($row);

        return $hydrated;
    }

    public function tickerMessages(?int $eventId = null): array
    {
        $entries = $this->signageEntries($eventId);
        if (!$entries) {
            return [];
        }

        return array_values(array_filter(array_map(static function (array $entry): ?string {
            $text = trim((string) ($entry['ticker_text'] ?? ''));
            return $text === '' ? null : $text;
        }, $entries)));
    }

    private function normalize(array $data): array
    {
        $payload = [
            'name' => trim((string) ($data['name'] ?? '')),
            'display_name' => $this->nullString($data['display_name'] ?? null),
            'type' => $this->ensureEnum(trim((string) ($data['type'] ?? 'company')), self::TYPES, 'company'),
            'status' => $this->ensureEnum(trim((string) ($data['status'] ?? 'active')), self::STATUSES, 'active'),
            'contact_person' => trim((string) ($data['contact_person'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'address' => trim((string) ($data['address'] ?? '')),
            'tier' => $this->ensureEnum(trim((string) ($data['tier'] ?? 'partner')), self::TIERS, 'partner'),
            'value' => $this->normalizeValue($data['value'] ?? null),
            'value_type' => $this->ensureEnum(trim((string) ($data['value_type'] ?? 'cash')), self::VALUE_TYPES, 'cash'),
            'contract_start' => $this->nullString($data['contract_start'] ?? null),
            'contract_end' => $this->nullString($data['contract_end'] ?? null),
            'invoice_required' => !empty($data['invoice_required']) ? 1 : 0,
            'invoice_number' => $this->nullString($data['invoice_number'] ?? null),
            'logo_path' => $this->nullString($data['logo_path'] ?? null),
            'website' => $this->nullString($data['website'] ?? null),
            'description_short' => $this->nullString($data['description_short'] ?? null),
            'description_long' => $this->nullString($data['description_long'] ?? null),
            'priority' => isset($data['priority']) ? (int) $data['priority'] : 0,
            'color_primary' => $this->nullString($data['color_primary'] ?? null),
            'tagline' => $this->nullString($data['tagline'] ?? null),
            'show_on_website' => !empty($data['show_on_website']) ? 1 : 0,
            'show_on_signage' => !empty($data['show_on_signage']) ? 1 : 0,
            'show_in_program' => !empty($data['show_in_program']) ? 1 : 0,
            'overlay_template' => $this->nullString($data['overlay_template'] ?? null),
            'display_duration' => isset($data['display_duration']) && $data['display_duration'] !== '' ? (int) $data['display_duration'] : null,
            'display_frequency' => isset($data['display_frequency']) && $data['display_frequency'] !== '' ? (int) $data['display_frequency'] : null,
            'linked_event_id' => isset($data['linked_event_id']) && $data['linked_event_id'] !== '' ? (int) $data['linked_event_id'] : null,
            'contract_file' => $this->nullString($data['contract_file'] ?? null),
            'logo_variants' => $this->encodeJson($data['logo_variants'] ?? []),
            'media_package' => $this->encodeJson($data['media_package'] ?? []),
            'notes_internal' => $this->nullString($data['notes_internal'] ?? null),
            'documents' => $this->encodeJson($data['documents'] ?? []),
            'sponsorship_history' => $this->encodeJson($data['sponsorship_history'] ?? []),
            'display_stats' => $this->encodeJson($data['display_stats'] ?? []),
            'last_contacted' => $this->nullString($data['last_contacted'] ?? null),
            'follow_up_date' => $this->nullString($data['follow_up_date'] ?? null),
        ];

        return $payload;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['priority'] = (int) ($row['priority'] ?? 0);
        $row['invoice_required'] = !empty($row['invoice_required']);
        $row['show_on_website'] = !empty($row['show_on_website']);
        $row['show_on_signage'] = !empty($row['show_on_signage']);
        $row['show_in_program'] = !empty($row['show_in_program']);
        $row['display_duration'] = isset($row['display_duration']) ? (int) $row['display_duration'] : null;
        $row['display_frequency'] = isset($row['display_frequency']) ? (int) $row['display_frequency'] : null;
        $row['linked_event_id'] = isset($row['linked_event_id']) ? (int) $row['linked_event_id'] : null;
        $row['logo_variants'] = $this->decodeJson($row['logo_variants'] ?? '[]', []);
        $row['media_package'] = $this->decodeJson($row['media_package'] ?? '[]', []);
        $row['documents'] = $this->decodeJson($row['documents'] ?? '[]', []);
        $row['sponsorship_history'] = $this->decodeJson($row['sponsorship_history'] ?? '[]', []);
        $row['display_stats'] = $this->decodeJson($row['display_stats'] ?? '{}', []);

        return $row;
    }

    private function tierOrderExpression(): string
    {
        return "CASE tier "
            . "WHEN 'platinum' THEN 0 "
            . "WHEN 'gold' THEN 1 "
            . "WHEN 'silver' THEN 2 "
            . "WHEN 'bronze' THEN 3 "
            . "WHEN 'partner' THEN 4 "
            . "WHEN 'supporter' THEN 5 "
            . 'ELSE 9 END';
    }

    private function ensureEnum(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function nullString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = (string) $value;
        $normalized = str_replace(',', '.', $value);
        if (!is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function encodeJson(mixed $value): string
    {
        if (!is_array($value)) {
            $value = [];
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function decodeJson(string $json, array $default): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : $default;
    }

    private function buildTickerText(array $sponsor): string
    {
        $parts = [];
        $tier = $sponsor['tier'] ?? null;
        if (is_string($tier) && $tier !== '') {
            $tierLabel = $this->translate('sponsors.tiers.' . $tier);
            if ($tierLabel !== '') {
                $parts[] = $tierLabel;
            }
        }
        $displayName = trim((string) ($sponsor['display_name'] ?? ''));
        if ($displayName === '') {
            $displayName = trim((string) ($sponsor['name'] ?? ''));
        }
        if ($displayName !== '') {
            $parts[] = $displayName;
        }
        $tagline = trim((string) ($sponsor['tagline'] ?? ''));
        if ($tagline === '') {
            $tagline = trim((string) ($sponsor['description_short'] ?? ''));
        }
        if ($tagline !== '') {
            $parts[] = $tagline;
        }

        return implode(' · ', array_filter($parts, static fn (string $part): bool => $part !== ''));
    }

    private function translate(string $key): string
    {
        if (function_exists('t')) {
            $translated = \t($key);
            if (is_string($translated) && $translated !== $key) {
                return $translated;
            }
        }

        $suffix = substr($key, strrpos($key, '.') + 1);
        return ucfirst(str_replace('_', ' ', $suffix));
    }
}
