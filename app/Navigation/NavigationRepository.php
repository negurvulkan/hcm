<?php

declare(strict_types=1);

namespace App\Navigation;

use DateTimeImmutable;
use PDO;
use Throwable;

class NavigationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function groupsForRole(string $role): array
    {
        $stmt = $this->pdo->prepare('SELECT id, role, label_key, label_i18n, position FROM navigation_groups WHERE role = :role ORDER BY position ASC, id ASC');
        $stmt->execute(['role' => $role]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static function (array $row): array {
            $translations = [];
            $raw = $row['label_i18n'] ?? null;
            if (is_string($raw) && $raw !== '') {
                try {
                    /** @var array<string, string> $decoded */
                    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    foreach ($decoded as $locale => $value) {
                        if (is_string($value) && $value !== '') {
                            $translations[$locale] = $value;
                        }
                    }
                } catch (\JsonException) {
                    $translations = [];
                }
            }

            return [
                'id' => (int) $row['id'],
                'role' => (string) $row['role'],
                'label_key' => $row['label_key'] !== null ? (string) $row['label_key'] : null,
                'label_translations' => $translations,
                'position' => (int) $row['position'],
            ];
        }, $rows ?: []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function itemsForRole(string $role): array
    {
        $stmt = $this->pdo->prepare('SELECT id, role, item_key, target, group_id, variant, position, is_custom, label_i18n FROM navigation_items WHERE role = :role ORDER BY position ASC, id ASC');
        $stmt->execute(['role' => $role]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static function (array $row): array {
            $translations = [];
            $raw = $row['label_i18n'] ?? null;
            if (is_string($raw) && $raw !== '') {
                try {
                    /** @var array<string, string> $decoded */
                    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    foreach ($decoded as $locale => $value) {
                        if (is_string($value) && $value !== '') {
                            $translations[$locale] = $value;
                        }
                    }
                } catch (\JsonException) {
                    $translations = [];
                }
            }

            return [
                'id' => (int) $row['id'],
                'role' => (string) $row['role'],
                'item_key' => (string) $row['item_key'],
                'target' => (string) $row['target'],
                'group_id' => $row['group_id'] !== null ? (int) $row['group_id'] : null,
                'variant' => $row['variant'] !== null && $row['variant'] !== '' ? (string) $row['variant'] : 'primary',
                'position' => (int) $row['position'],
                'is_custom' => (bool) $row['is_custom'],
                'label_translations' => $translations,
            ];
        }, $rows ?: []);
    }

    public function itemExists(string $role, string $itemKey): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM navigation_items WHERE role = :role AND item_key = :item_key');
        $stmt->execute([
            'role' => $role,
            'item_key' => $itemKey,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function createCustomItem(string $role, string $itemKey, array $labels, string $target, int $groupId, string $variant, int $position): int
    {
        $now = (new DateTimeImmutable())->format('c');
        $labelTranslations = array_filter($labels, static fn ($value) => is_string($value) && $value !== '');
        $json = $labelTranslations ? json_encode($labelTranslations, JSON_THROW_ON_ERROR) : null;

        $stmt = $this->pdo->prepare('INSERT INTO navigation_items (role, item_key, target, group_id, variant, position, is_custom, label_i18n, created_at, updated_at) VALUES (:role, :item_key, :target, :group_id, :variant, :position, 1, :label_i18n, :created_at, :updated_at)');
        $stmt->execute([
            'role' => $role,
            'item_key' => $itemKey,
            'target' => $target,
            'group_id' => $groupId,
            'variant' => $variant,
            'position' => $position,
            'label_i18n' => $json,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createGroup(string $role, array $labels, int $position, ?string $labelKey = null): int
    {
        $now = (new DateTimeImmutable())->format('c');
        $labelTranslations = array_filter($labels, static fn ($value) => is_string($value) && $value !== '');
        $json = $labelTranslations ? json_encode($labelTranslations, JSON_THROW_ON_ERROR) : null;

        $stmt = $this->pdo->prepare('INSERT INTO navigation_groups (role, label_key, label_i18n, position, created_at, updated_at) VALUES (:role, :label_key, :label_i18n, :position, :created_at, :updated_at)');
        $stmt->execute([
            'role' => $role,
            'label_key' => $labelKey,
            'label_i18n' => $json,
            'position' => $position,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateGroup(int $id, string $role, array $labels, int $position): void
    {
        $now = (new DateTimeImmutable())->format('c');
        $labelTranslations = array_filter($labels, static fn ($value) => is_string($value) && $value !== '');
        $json = $labelTranslations ? json_encode($labelTranslations, JSON_THROW_ON_ERROR) : null;

        $stmt = $this->pdo->prepare('UPDATE navigation_groups SET label_i18n = :label_i18n, position = :position, updated_at = :updated_at WHERE id = :id AND role = :role');
        $stmt->execute([
            'label_i18n' => $json,
            'position' => $position,
            'updated_at' => $now,
            'id' => $id,
            'role' => $role,
        ]);
    }

    public function deleteGroup(int $id, string $role): void
    {
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }
        try {
            $deleteItems = $this->pdo->prepare('DELETE FROM navigation_items WHERE role = :role AND group_id = :group_id');
            $deleteItems->execute([
                'role' => $role,
                'group_id' => $id,
            ]);

            $deleteGroup = $this->pdo->prepare('DELETE FROM navigation_groups WHERE id = :id AND role = :role');
            $deleteGroup->execute([
                'id' => $id,
                'role' => $role,
            ]);

            if ($startedTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($startedTransaction) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * @param array<int, array{item_key:string,target:string,group_id:int,variant:string,position:int,is_custom:bool,labels:array<string,string>}> $items
     */
    public function replaceItems(string $role, array $items): void
    {
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }
        try {
            $select = $this->pdo->prepare('SELECT id, item_key FROM navigation_items WHERE role = :role');
            $select->execute(['role' => $role]);
            $existing = [];
            foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $existing[(string) $row['item_key']] = (int) $row['id'];
            }

            $now = (new DateTimeImmutable())->format('c');
            $upserted = [];

            $update = $this->pdo->prepare('UPDATE navigation_items SET target = :target, group_id = :group_id, variant = :variant, position = :position, is_custom = :is_custom, label_i18n = :label_i18n, updated_at = :updated_at WHERE id = :id AND role = :role');
            $insert = $this->pdo->prepare('INSERT INTO navigation_items (role, item_key, target, group_id, variant, position, is_custom, label_i18n, created_at, updated_at) VALUES (:role, :item_key, :target, :group_id, :variant, :position, :is_custom, :label_i18n, :created_at, :updated_at)');

            foreach ($items as $item) {
                $itemKey = $item['item_key'];
                $upserted[] = $itemKey;

                $labelTranslations = array_filter($item['labels'] ?? [], static fn ($value) => is_string($value) && $value !== '');
                $json = $labelTranslations ? json_encode($labelTranslations, JSON_THROW_ON_ERROR) : null;

                if (isset($existing[$itemKey])) {
                    $update->execute([
                        'target' => $item['target'],
                        'group_id' => $item['group_id'],
                        'variant' => $item['variant'],
                        'position' => $item['position'],
                        'is_custom' => $item['is_custom'] ? 1 : 0,
                        'label_i18n' => $json,
                        'updated_at' => $now,
                        'id' => $existing[$itemKey],
                        'role' => $role,
                    ]);
                } else {
                    $insert->execute([
                        'role' => $role,
                        'item_key' => $itemKey,
                        'target' => $item['target'],
                        'group_id' => $item['group_id'],
                        'variant' => $item['variant'],
                        'position' => $item['position'],
                        'is_custom' => $item['is_custom'] ? 1 : 0,
                        'label_i18n' => $json,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $delete = $this->pdo->prepare('DELETE FROM navigation_items WHERE role = :role AND item_key = :item_key');
            foreach ($existing as $itemKey => $id) {
                if (!in_array($itemKey, $upserted, true)) {
                    $delete->execute([
                        'role' => $role,
                        'item_key' => $itemKey,
                    ]);
                }
            }

            if ($startedTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($startedTransaction) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * @param array<int, array{key:string,label_key:?string,labels:array<string,string>,position:int}> $groups
     * @param array<int, array{item_key:string,target:string,group_key:string,variant:string,position:int}> $items
     */
    public function replaceLayout(string $role, array $groups, array $items): void
    {
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }
        try {
            $this->pdo->prepare('DELETE FROM navigation_items WHERE role = :role')->execute(['role' => $role]);
            $this->pdo->prepare('DELETE FROM navigation_groups WHERE role = :role')->execute(['role' => $role]);

            $now = (new DateTimeImmutable())->format('c');
            $groupInsert = $this->pdo->prepare('INSERT INTO navigation_groups (role, label_key, label_i18n, position, created_at, updated_at) VALUES (:role, :label_key, :label_i18n, :position, :created_at, :updated_at)');
            $itemInsert = $this->pdo->prepare('INSERT INTO navigation_items (role, item_key, target, group_id, variant, position, is_custom, label_i18n, created_at, updated_at) VALUES (:role, :item_key, :target, :group_id, :variant, :position, :is_custom, :label_i18n, :created_at, :updated_at)');

            $groupIds = [];
            foreach ($groups as $group) {
                $labelTranslations = array_filter($group['labels'], static fn ($value) => is_string($value) && $value !== '');
                $json = $labelTranslations ? json_encode($labelTranslations, JSON_THROW_ON_ERROR) : null;
                $groupInsert->execute([
                    'role' => $role,
                    'label_key' => $group['label_key'] ?? null,
                    'label_i18n' => $json,
                    'position' => $group['position'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $groupIds[$group['key']] = (int) $this->pdo->lastInsertId();
            }

            foreach ($items as $item) {
                $groupKey = $item['group_key'];
                $groupId = $groupIds[$groupKey] ?? null;
                if ($groupId === null) {
                    continue;
                }
                $itemInsert->execute([
                    'role' => $role,
                    'item_key' => $item['item_key'],
                    'target' => $item['target'],
                    'group_id' => $groupId,
                    'variant' => $item['variant'],
                    'position' => $item['position'],
                    'is_custom' => 0,
                    'label_i18n' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($startedTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($startedTransaction) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function groupBelongsToRole(int $groupId, string $role): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM navigation_groups WHERE id = :id AND role = :role');
        $stmt->execute([
            'id' => $groupId,
            'role' => $role,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
