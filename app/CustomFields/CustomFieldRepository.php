<?php

declare(strict_types=1);

namespace App\CustomFields;

use DateTimeImmutable;
use JsonException;
use PDO;
use PDOException;
use RuntimeException;

class CustomFieldRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allDefinitions(?string $entity = null): array
    {
        $sql = 'SELECT d.*, COUNT(v.id) AS value_count FROM custom_field_definitions d '
            . 'LEFT JOIN custom_field_values v ON v.field_definition_id = d.id';
        $params = [];

        if ($entity !== null && $entity !== '') {
            $sql .= ' WHERE d.entity = :entity';
            $params['entity'] = $entity;
        }

        $sql .= ' GROUP BY d.id ORDER BY d.entity, d.field_key, d.version DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $definitions = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $definition = $this->formatDefinition($row);
            $definition['value_count'] = (int) ($row['value_count'] ?? 0);
            $definitions[] = $definition;
        }

        return $definitions;
    }

    /**
     * @param array{organization_id?: int|null, tournament_id?: int|null, profiles?: array<int, string>|string|null, include_inactive?: bool} $options
     * @return array<int, array<string, mixed>>
     */
    public function definitionsFor(string $entity, array $options = []): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM custom_field_definitions WHERE entity = :entity ORDER BY profile_key IS NULL DESC, profile_key, field_key, version DESC');
        $stmt->execute(['entity' => $entity]);

        $definitions = [];
        $profiles = $this->normalizeProfiles($options['profiles'] ?? null);
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $includeInactive = !empty($options['include_inactive']);

        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            if (!$this->matchesScope($row, $options)) {
                continue;
            }

            if (!$includeInactive && !$this->isWithinValidity($row, $now)) {
                continue;
            }

            if ($profiles !== null && !$this->matchesProfile($row, $profiles)) {
                continue;
            }

            $key = $this->definitionIndexKey($row);
            if (!isset($definitions[$key]) || (int) $row['version'] > (int) ($definitions[$key]['version'] ?? 0)) {
                $definitions[$key] = $this->formatDefinition($row);
            }
        }

        return array_values($definitions);
    }

    public function findDefinition(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT d.*, COUNT(v.id) AS value_count FROM custom_field_definitions d LEFT JOIN custom_field_values v ON v.field_definition_id = d.id WHERE d.id = :id GROUP BY d.id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $definition = $this->formatDefinition($row);
        $definition['value_count'] = (int) ($row['value_count'] ?? 0);

        return $definition;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createDefinition(array $data): int
    {
        $payload = $this->buildDefinitionPayload($data);
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $payload['created_at'] = $now;
        $payload['updated_at'] = $now;

        $columns = array_keys($payload);
        $placeholders = array_map(static fn ($column): string => ':' . $column, $columns);
        $sql = 'INSERT INTO custom_field_definitions (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($payload);
        } catch (PDOException|JsonException $exception) {
            throw new RuntimeException('Konnte Custom-Feld nicht anlegen: ' . $exception->getMessage(), 0, $exception);
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateDefinition(int $id, array $data): void
    {
        $current = $this->findDefinition($id);
        if (!$current) {
            throw new RuntimeException('Custom-Feld nicht gefunden.');
        }

        $payload = $this->buildDefinitionPayload($data);
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $payload['updated_at'] = $now;
        $payload['id'] = $id;

        $assignments = [];
        foreach ($payload as $column => $value) {
            if ($column === 'id') {
                continue;
            }
            $assignments[] = $column . ' = :' . $column;
        }
        $sql = 'UPDATE custom_field_definitions SET ' . implode(', ', $assignments) . ' WHERE id = :id';

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($payload);

            if ((int) $current['version'] !== (int) $payload['version']) {
                $updateValues = $this->pdo->prepare('UPDATE custom_field_values SET version = :version, updated_at = :updated WHERE field_definition_id = :id');
                $updateValues->execute([
                    'version' => (int) $payload['version'],
                    'updated' => $now,
                    'id' => $id,
                ]);
            }

            $this->pdo->commit();
        } catch (PDOException|JsonException $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Konnte Custom-Feld nicht aktualisieren: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public function deleteDefinition(int $id): void
    {
        $this->pdo->beginTransaction();

        try {
            $deleteValues = $this->pdo->prepare('DELETE FROM custom_field_values WHERE field_definition_id = :id');
            $deleteValues->execute(['id' => $id]);

            $deleteDefinition = $this->pdo->prepare('DELETE FROM custom_field_definitions WHERE id = :id');
            $deleteDefinition->execute(['id' => $id]);

            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Konnte Custom-Feld nicht löschen: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function valuesFor(string $entity, int $entityId): array
    {
        $sql = 'SELECT v.*, d.field_key, d.type FROM custom_field_values v JOIN custom_field_definitions d ON d.id = v.field_definition_id WHERE v.entity = :entity AND v.entity_id = :entity_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['entity' => $entity, 'entity_id' => $entityId]);

        $values = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $values[(string) $row['field_key']] = [
                'field_definition_id' => (int) $row['field_definition_id'],
                'version' => (int) $row['version'],
                'value' => $this->decodeValue($row['value'], (string) $row['type']),
                'updated_at' => $row['updated_at'],
            ];
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $values
     * @param array{organization_id?: int|null, tournament_id?: int|null, profiles?: array<int, string>|string|null} $options
     */
    public function saveValues(string $entity, int $entityId, array $values, array $options = []): void
    {
        if ($values === []) {
            return;
        }

        $definitions = $this->definitionsFor($entity, $options + ['include_inactive' => true]);
        $definitionMap = [];
        foreach ($definitions as $definition) {
            $definitionMap[$definition['key']] = $definition;
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->beginTransaction();

        try {
            $updateStmt = $this->pdo->prepare('UPDATE custom_field_values SET value = :value, version = :version, updated_at = :updated WHERE entity = :entity AND entity_id = :entity_id AND field_definition_id = :field_id');
            $insertStmt = $this->pdo->prepare('INSERT INTO custom_field_values (entity, entity_id, field_definition_id, value, version, created_at, updated_at) VALUES (:entity, :entity_id, :field_id, :value, :version, :created, :updated)');
            $deleteStmt = $this->pdo->prepare('DELETE FROM custom_field_values WHERE entity = :entity AND entity_id = :entity_id AND field_definition_id = :field_id');

            foreach ($values as $key => $value) {
                if (!isset($definitionMap[$key])) {
                    continue;
                }

                $definition = $definitionMap[$key];
                $encoded = $this->encodeValue($definition, $value);
                $fieldId = (int) $definition['id'];
                $params = [
                    'entity' => $entity,
                    'entity_id' => $entityId,
                    'field_id' => $fieldId,
                ];

                if ($this->shouldDeleteValue($value, $definition['type'])) {
                    $deleteStmt->execute($params);
                    continue;
                }

                $updateParams = $params + [
                    'value' => $encoded,
                    'version' => (int) $definition['version'],
                    'updated' => $now,
                ];
                $updateStmt->execute($updateParams);

                if ($updateStmt->rowCount() === 0) {
                    $insertParams = $updateParams + [
                        'created' => $now,
                    ];
                    $insertStmt->execute($insertParams);
                }
            }

            $this->pdo->commit();
        } catch (PDOException|JsonException $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Konnte Custom-Felder nicht speichern: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatDefinition(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'entity' => (string) $row['entity'],
            'key' => (string) $row['field_key'],
            'label' => $this->decodeJsonField($row['label_i18n']),
            'help' => $this->decodeJsonField($row['help_i18n'] ?? null),
            'type' => (string) $row['type'],
            'required' => (bool) $row['required'],
            'is_unique' => (bool) $row['is_unique'],
            'is_sensitive' => (bool) $row['is_sensitive'],
            'regex' => $row['regex_pattern'] ?: null,
            'min' => $row['min_value'] ?? null,
            'max' => $row['max_value'] ?? null,
            'enum_values' => $this->decodeJsonField($row['enum_values'] ?? null),
            'visibility' => (string) $row['visibility'],
            'scope' => (string) $row['scope'],
            'organization_id' => $row['organization_id'] !== null ? (int) $row['organization_id'] : null,
            'tournament_id' => $row['tournament_id'] !== null ? (int) $row['tournament_id'] : null,
            'required_when' => $this->decodeJsonField($row['required_when'] ?? null),
            'profile_key' => $row['profile_key'] ?: null,
            'version' => (int) $row['version'],
            'valid_from' => $row['valid_from'] ?: null,
            'valid_to' => $row['valid_to'] ?: null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildDefinitionPayload(array $data): array
    {
        $label = $data['label'] ?? [];
        $help = $data['help'] ?? [];
        $enumValues = $data['enum_values'] ?? null;
        $requiredWhen = $data['required_when'] ?? null;

        return [
            'entity' => (string) $data['entity'],
            'field_key' => (string) $data['field_key'],
            'label_i18n' => $this->encodeJsonField(is_array($label) ? $label : []),
            'help_i18n' => $this->encodeJsonField(is_array($help) ? $help : []),
            'type' => (string) $data['type'],
            'required' => !empty($data['required']) ? 1 : 0,
            'is_unique' => !empty($data['is_unique']) ? 1 : 0,
            'is_sensitive' => !empty($data['is_sensitive']) ? 1 : 0,
            'regex_pattern' => ($data['regex_pattern'] ?? '') !== '' ? (string) $data['regex_pattern'] : null,
            'min_value' => ($data['min_value'] ?? '') !== '' ? (string) $data['min_value'] : null,
            'max_value' => ($data['max_value'] ?? '') !== '' ? (string) $data['max_value'] : null,
            'enum_values' => $this->encodeJsonField(is_array($enumValues) ? $enumValues : null),
            'visibility' => (string) ($data['visibility'] ?? 'internal'),
            'scope' => (string) ($data['scope'] ?? 'global'),
            'organization_id' => isset($data['organization_id']) && $data['organization_id'] !== null && $data['organization_id'] !== '' ? (int) $data['organization_id'] : null,
            'tournament_id' => isset($data['tournament_id']) && $data['tournament_id'] !== null && $data['tournament_id'] !== '' ? (int) $data['tournament_id'] : null,
            'required_when' => $this->encodeJsonField(is_array($requiredWhen) ? $requiredWhen : null),
            'profile_key' => ($data['profile_key'] ?? '') !== '' ? (string) $data['profile_key'] : null,
            'version' => isset($data['version']) ? (int) $data['version'] : 1,
            'valid_from' => ($data['valid_from'] ?? '') !== '' ? (string) $data['valid_from'] : null,
            'valid_to' => ($data['valid_to'] ?? '') !== '' ? (string) $data['valid_to'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string>|null $profiles
     */
    private function matchesProfile(array $row, ?array $profiles): bool
    {
        $profileKey = $row['profile_key'] ?? null;
        if ($profileKey === null || $profileKey === '') {
            return true;
        }

        if ($profiles === null) {
            return false;
        }

        return in_array((string) $profileKey, $profiles, true);
    }

    /**
     * @param array<string, mixed> $row
     * @param array{organization_id?: int|null, tournament_id?: int|null} $options
     */
    private function matchesScope(array $row, array $options): bool
    {
        $scope = (string) ($row['scope'] ?? 'global');
        $organizationId = $options['organization_id'] ?? null;
        $tournamentId = $options['tournament_id'] ?? null;

        return match ($scope) {
            'global' => true,
            'organization' => $organizationId !== null && (int) $row['organization_id'] === (int) $organizationId,
            'tournament' => $tournamentId !== null && (int) $row['tournament_id'] === (int) $tournamentId,
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    private function definitionIndexKey(array $row): string
    {
        $parts = [
            (string) $row['field_key'],
            (string) ($row['scope'] ?? 'global'),
            (string) ($row['organization_id'] ?? ''),
            (string) ($row['tournament_id'] ?? ''),
            (string) ($row['profile_key'] ?? ''),
        ];

        return implode(':', $parts);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function isWithinValidity(array $row, string $now): bool
    {
        $validFrom = $row['valid_from'] ?? null;
        $validTo = $row['valid_to'] ?? null;

        if ($validFrom && $validFrom > $now) {
            return false;
        }
        if ($validTo && $validTo < $now) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function decodeValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) ((int) $value),
            'json' => $this->decodeJsonField($value),
            default => $value,
        };
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function encodeValue(array $definition, $value)
    {
        $type = (string) $definition['type'];

        return match ($type) {
            'int' => $value === null || $value === '' ? null : (string) ((int) $value),
            'float' => $value === null || $value === '' ? null : (string) ((float) $value),
            'bool' => $value === null ? null : ((filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false) ? '1' : '0'),
            'json' => $value === null ? null : json_encode($value, JSON_THROW_ON_ERROR),
            default => $value === null ? null : (string) $value,
        };
    }

    /**
     * @param mixed $value
     */
    private function shouldDeleteValue($value, string $type): bool
    {
        if ($value === null) {
            return true;
        }

        if ($type === 'bool') {
            return false;
        }

        if ($type === 'json') {
            return $value === [];
        }

        return $value === '';
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>|string|null
     */
    private function decodeJsonField($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return $value;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Ungültiges JSON in Custom-Felddefinition: ' . $exception->getMessage(), 0, $exception);
        }

        return $decoded;
    }

    /**
     * @param array<int, string>|null $value
     */
    private function encodeJsonField(?array $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (is_string($key) && $item !== null && $item !== '') {
                $normalized[$key] = $item;
                continue;
            }

            if (is_int($key)) {
                $item = is_string($item) ? trim($item) : $item;
                if ($item !== null && $item !== '') {
                    $normalized[] = $item;
                }
            }
        }

        if ($normalized === []) {
            return null;
        }

        return json_encode($normalized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<int, string>|string|null $profiles
     * @return array<int, string>|null
     */
    private function normalizeProfiles($profiles): ?array
    {
        if ($profiles === null || $profiles === []) {
            return null;
        }

        if (is_string($profiles)) {
            return [$profiles];
        }

        return array_values(array_filter(array_map(static fn ($profile): string => (string) $profile, $profiles), static fn ($value): bool => $value !== ''));
    }
}
