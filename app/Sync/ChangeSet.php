<?php
namespace App\Sync;

class ChangeSet
{
    /**
     * @var array<string, array<int, array{id: string, version: string, data: array|null, meta: array<string, mixed>}>>
     */
    private array $entries = [];

    private string $origin;
    private ?string $cursor;

    public function __construct(array $entries = [], string $origin = 'UNKNOWN', ?string $cursor = null)
    {
        $this->origin = strtoupper($origin);
        $this->cursor = $cursor;
        foreach ($entries as $scope => $records) {
            foreach ($records as $record) {
                $this->add($scope, $record);
            }
        }
    }

    /**
     * @param array{id: mixed, version: mixed, data?: mixed, meta?: mixed} $record
     */
    public function add(string $scope, array $record): void
    {
        $scope = strtolower($scope);
        $id = (string) ($record['id'] ?? '');
        $version = (string) ($record['version'] ?? '');
        $data = $record['data'] ?? null;
        $meta = $record['meta'] ?? [];

        if ($id === '' || $version === '') {
            throw new SyncException('SCHEMA_VALIDATION_FAILED', 'Änderungsdatensatz ohne ID oder Version.');
        }

        if ($data !== null && !is_array($data)) {
            throw new SyncException('SCHEMA_VALIDATION_FAILED', 'Payload muss ein Objekt sein.');
        }

        if (!is_array($meta)) {
            $meta = [];
        }

        $this->entries[$scope][] = [
            'id' => $id,
            'version' => $version,
            'data' => $data,
            'meta' => $meta,
        ];
    }

    /**
     * @return array<string, array<int, array{id: string, version: string, data: array|null, meta: array<string, mixed>}>>
     */
    public function all(): array
    {
        return $this->entries;
    }

    /**
     * @return array<int, array{id: string, version: string, data: array|null, meta: array<string, mixed>}>
     */
    public function forScope(string $scope): array
    {
        return $this->entries[strtolower($scope)] ?? [];
    }

    /**
     * @return string[]
     */
    public function scopes(): array
    {
        return array_keys($this->entries);
    }

    public function origin(): string
    {
        return $this->origin;
    }

    public function cursor(): ?string
    {
        return $this->cursor;
    }

    public static function fromPayload(array $payload): self
    {
        $origin = strtoupper((string) ($payload['origin'] ?? 'UNKNOWN'));
        $cursor = isset($payload['cursor']) ? (string) $payload['cursor'] : null;
        $entities = $payload['entities'] ?? [];
        if (!is_array($entities)) {
            throw new SyncException('SCHEMA_VALIDATION_FAILED', 'Ungültige Entities-Struktur.');
        }

        $set = new self([], $origin, $cursor);
        foreach ($entities as $scope => $records) {
            if (!is_array($records)) {
                throw new SyncException('SCHEMA_VALIDATION_FAILED', 'Entities müssen Arrays sein.');
            }
            foreach ($records as $record) {
                if (!is_array($record)) {
                    throw new SyncException('SCHEMA_VALIDATION_FAILED', 'Datensatz muss Objekt sein.');
                }
                $set->add((string) $scope, $record);
            }
        }

        return $set;
    }

    /**
     * @return array{origin: string, cursor: ?string, entities: array<string, array<int, array{id: string, version: string, data: array|null, meta: array<string, mixed>}>>}
     */
    public function toArray(): array
    {
        return [
            'origin' => $this->origin,
            'cursor' => $this->cursor,
            'entities' => $this->entries,
        ];
    }
}
