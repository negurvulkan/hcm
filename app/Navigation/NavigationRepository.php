<?php

declare(strict_types=1);

namespace App\Navigation;

use PDO;
use RuntimeException;

class NavigationRepository
{
    private string $storagePath;

    public function __construct(PDO|string|null $storage = null)
    {
        if ($storage instanceof PDO) {
            $this->storagePath = $this->defaultStoragePath();
            return;
        }

        if (is_string($storage) && $storage !== '') {
            $this->storagePath = $storage;
            return;
        }

        $this->storagePath = $this->defaultStoragePath();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function groupsForRole(string $role): array
    {
        $data = $this->load();
        $groups = $data['roles'][$role]['groups'] ?? [];

        $normalized = [];
        foreach ($groups as $group) {
            $normalized[] = [
                'id' => (int) ($group['id'] ?? 0),
                'role' => $role,
                'label_key' => isset($group['label_key']) ? (is_string($group['label_key']) ? $group['label_key'] : null) : null,
                'label_translations' => $this->sanitizeLabels($group['label_translations'] ?? []),
                'position' => (int) ($group['position'] ?? 0),
            ];
        }

        $this->sortGroups($normalized);

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function itemsForRole(string $role): array
    {
        $data = $this->load();
        $items = $data['roles'][$role]['items'] ?? [];

        $normalized = [];
        foreach ($items as $item) {
            $normalized[] = [
                'id' => (int) ($item['id'] ?? 0),
                'role' => $role,
                'item_key' => (string) ($item['item_key'] ?? ''),
                'target' => (string) ($item['target'] ?? ''),
                'group_id' => isset($item['group_id']) ? (int) $item['group_id'] : null,
                'variant' => $item['variant'] === 'secondary' ? 'secondary' : 'primary',
                'position' => (int) ($item['position'] ?? 0),
                'is_custom' => (bool) ($item['is_custom'] ?? false),
                'label_translations' => $this->sanitizeLabels($item['label_translations'] ?? []),
            ];
        }

        $this->sortItems($normalized);

        return $normalized;
    }

    public function itemExists(string $role, string $itemKey): bool
    {
        $data = $this->load();
        $items = $data['roles'][$role]['items'] ?? [];

        foreach ($items as $item) {
            if (($item['item_key'] ?? null) === $itemKey) {
                return true;
            }
        }

        return false;
    }

    public function createCustomItem(string $role, string $itemKey, array $labels, string $target, int $groupId, string $variant, int $position): int
    {
        $data = $this->load();
        $this->ensureRole($data, $role);

        $id = $this->nextItemId($data);
        $data['roles'][$role]['items'][] = [
            'id' => $id,
            'item_key' => $itemKey,
            'target' => (string) $target,
            'group_id' => (int) $groupId,
            'variant' => $variant === 'secondary' ? 'secondary' : 'primary',
            'position' => $position,
            'is_custom' => true,
            'label_translations' => $this->sanitizeLabels($labels),
        ];

        $this->sortItems($data['roles'][$role]['items']);
        $this->persist($data);

        return $id;
    }

    public function createGroup(string $role, array $labels, int $position, ?string $labelKey = null): int
    {
        $data = $this->load();
        $this->ensureRole($data, $role);

        $id = $this->nextGroupId($data);
        $data['roles'][$role]['groups'][] = [
            'id' => $id,
            'label_key' => $labelKey,
            'label_translations' => $this->sanitizeLabels($labels),
            'position' => $position,
        ];

        $this->sortGroups($data['roles'][$role]['groups']);
        $this->persist($data);

        return $id;
    }

    public function updateGroup(int $id, string $role, array $labels, int $position): void
    {
        $data = $this->load();
        $this->ensureRole($data, $role);

        foreach ($data['roles'][$role]['groups'] as &$group) {
            if ((int) ($group['id'] ?? 0) === $id) {
                $group['label_translations'] = $this->sanitizeLabels($labels);
                $group['position'] = $position;
                break;
            }
        }
        unset($group);

        $this->sortGroups($data['roles'][$role]['groups']);
        $this->persist($data);
    }

    public function deleteGroup(int $id, string $role): void
    {
        $data = $this->load();
        $this->ensureRole($data, $role);

        $data['roles'][$role]['groups'] = array_values(array_filter(
            $data['roles'][$role]['groups'],
            static fn (array $group): bool => (int) ($group['id'] ?? 0) !== $id
        ));

        $data['roles'][$role]['items'] = array_values(array_filter(
            $data['roles'][$role]['items'],
            static fn (array $item): bool => (int) ($item['group_id'] ?? 0) !== $id
        ));

        $this->sortGroups($data['roles'][$role]['groups']);
        $this->sortItems($data['roles'][$role]['items']);
        $this->persist($data);
    }

    /**
     * @param array<int, array{item_key:string,target:string,group_id:int,variant:string,position:int,is_custom:bool,labels:array<string,string>}> $items
     */
    public function replaceItems(string $role, array $items): void
    {
        $data = $this->load();
        $this->ensureRole($data, $role);

        $existing = [];
        foreach ($data['roles'][$role]['items'] as $item) {
            $existing[$item['item_key']] = $item;
        }

        $updated = [];
        foreach ($items as $item) {
            $itemKey = $item['item_key'];
            $current = $existing[$itemKey] ?? null;
            $id = isset($current['id']) ? (int) $current['id'] : $this->nextItemId($data);

            $updated[] = [
                'id' => $id,
                'item_key' => $itemKey,
                'target' => (string) $item['target'],
                'group_id' => isset($item['group_id']) ? (int) $item['group_id'] : null,
                'variant' => $item['variant'] === 'secondary' ? 'secondary' : 'primary',
                'position' => $item['position'],
                'is_custom' => (bool) $item['is_custom'],
                'label_translations' => $this->sanitizeLabels($item['labels'] ?? []),
            ];
        }

        $data['roles'][$role]['items'] = $updated;
        $this->sortItems($data['roles'][$role]['items']);
        $this->persist($data);
    }

    /**
     * @param array<int, array{key:string,label_key:?string,labels:array<string,string>,position:int}> $groups
     * @param array<int, array{item_key:string,target:string,group_key:string,variant:string,position:int}> $items
     */
    public function replaceLayout(string $role, array $groups, array $items): void
    {
        $data = $this->load();
        $this->ensureRole($data, $role);

        $data['roles'][$role]['groups'] = [];
        $data['roles'][$role]['items'] = [];

        $groupIds = [];
        foreach ($groups as $group) {
            $id = $this->nextGroupId($data);
            $data['roles'][$role]['groups'][] = [
                'id' => $id,
                'label_key' => $group['label_key'] ?? null,
                'label_translations' => $this->sanitizeLabels($group['labels'] ?? []),
                'position' => (int) ($group['position'] ?? 0),
            ];
            $groupIds[$group['key']] = $id;
        }

        foreach ($items as $item) {
            $groupKey = $item['group_key'];
            if (!isset($groupIds[$groupKey])) {
                continue;
            }

            $data['roles'][$role]['items'][] = [
                'id' => $this->nextItemId($data),
                'item_key' => (string) $item['item_key'],
                'target' => (string) $item['target'],
                'group_id' => $groupIds[$groupKey],
                'variant' => $item['variant'] === 'secondary' ? 'secondary' : 'primary',
                'position' => (int) ($item['position'] ?? 0),
                'is_custom' => false,
                'label_translations' => [],
            ];
        }

        $this->sortGroups($data['roles'][$role]['groups']);
        $this->sortItems($data['roles'][$role]['items']);
        $this->persist($data);
    }

    public function groupBelongsToRole(int $groupId, string $role): bool
    {
        $data = $this->load();
        $groups = $data['roles'][$role]['groups'] ?? [];

        foreach ($groups as $group) {
            if ((int) ($group['id'] ?? 0) === $groupId) {
                return true;
            }
        }

        return false;
    }

    private function defaultStoragePath(): string
    {
        return dirname(__DIR__, 2) . '/storage/navigation/navigation.json';
    }

    private function load(): array
    {
        if (!is_file($this->storagePath)) {
            return [
                'next_group_id' => 1,
                'next_item_id' => 1,
                'roles' => [],
            ];
        }

        $contents = file_get_contents($this->storagePath);
        if ($contents === false || $contents === '') {
            return [
                'next_group_id' => 1,
                'next_item_id' => 1,
                'roles' => [],
            ];
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid navigation storage');
        }

        $decoded['next_group_id'] = isset($decoded['next_group_id']) ? (int) $decoded['next_group_id'] : 1;
        if ($decoded['next_group_id'] < 1) {
            $decoded['next_group_id'] = 1;
        }

        $decoded['next_item_id'] = isset($decoded['next_item_id']) ? (int) $decoded['next_item_id'] : 1;
        if ($decoded['next_item_id'] < 1) {
            $decoded['next_item_id'] = 1;
        }

        if (!isset($decoded['roles']) || !is_array($decoded['roles'])) {
            $decoded['roles'] = [];
        }

        return $decoded;
    }

    private function persist(array $data): void
    {
        $directory = dirname($this->storagePath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create navigation storage directory');
        }

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new RuntimeException('Unable to encode navigation data');
        }

        $tempFile = tempnam($directory, 'nav_');
        if ($tempFile === false) {
            throw new RuntimeException('Unable to create temporary navigation storage');
        }

        $bytes = file_put_contents($tempFile, $encoded);
        if ($bytes === false) {
            @unlink($tempFile);
            throw new RuntimeException('Unable to write navigation storage');
        }

        if (!@rename($tempFile, $this->storagePath)) {
            @unlink($tempFile);
            throw new RuntimeException('Unable to persist navigation storage');
        }
    }

    private function ensureRole(array &$data, string $role): void
    {
        if (!isset($data['roles']) || !is_array($data['roles'])) {
            $data['roles'] = [];
        }

        if (!isset($data['roles'][$role])) {
            $data['roles'][$role] = [
                'groups' => [],
                'items' => [],
            ];
        }

        if (!is_array($data['roles'][$role]['groups'] ?? null)) {
            $data['roles'][$role]['groups'] = [];
        }

        if (!is_array($data['roles'][$role]['items'] ?? null)) {
            $data['roles'][$role]['items'] = [];
        }
    }

    private function nextGroupId(array &$data): int
    {
        $next = (int) ($data['next_group_id'] ?? 1);
        $data['next_group_id'] = $next + 1;

        return $next;
    }

    private function nextItemId(array &$data): int
    {
        $next = (int) ($data['next_item_id'] ?? 1);
        $data['next_item_id'] = $next + 1;

        return $next;
    }

    /**
     * @param array<string, mixed> $labels
     * @return array<string, string>
     */
    private function sanitizeLabels(array $labels): array
    {
        $sanitized = [];
        foreach ($labels as $locale => $value) {
            if (!is_string($locale) || !is_string($value)) {
                continue;
            }

            $trimmed = trim($value);
            if ($trimmed === '') {
                continue;
            }

            $sanitized[$locale] = $trimmed;
        }

        return $sanitized;
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     */
    private function sortGroups(array &$groups): void
    {
        usort($groups, static function (array $left, array $right): int {
            $positionComparison = ((int) ($left['position'] ?? 0)) <=> ((int) ($right['position'] ?? 0));
            if ($positionComparison !== 0) {
                return $positionComparison;
            }

            return ((int) ($left['id'] ?? 0)) <=> ((int) ($right['id'] ?? 0));
        });
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function sortItems(array &$items): void
    {
        usort($items, static function (array $left, array $right): int {
            $positionComparison = ((int) ($left['position'] ?? 0)) <=> ((int) ($right['position'] ?? 0));
            if ($positionComparison !== 0) {
                return $positionComparison;
            }

            return ((int) ($left['id'] ?? 0)) <=> ((int) ($right['id'] ?? 0));
        });
    }
}
