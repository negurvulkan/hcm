<?php
require __DIR__ . '/auth.php';

use App\Core\Csrf;
use App\Core\Rbac;
use App\Navigation\NavigationRepository;

if (!function_exists('navigation_generate_custom_item_key')) {
    function navigation_generate_custom_item_key(NavigationRepository $repository, string $role): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $key = 'custom_' . bin2hex(random_bytes(4));
            if (!$repository->itemExists($role, $key)) {
                return $key;
            }
        }

        throw new \RuntimeException('Unable to generate navigation key');
    }
}

$user = auth_require('instance');
$repository = new NavigationRepository();

$roles = Rbac::ROLES;
$selectedRole = $_POST['role'] ?? $_GET['role'] ?? 'admin';
if (!in_array($selectedRole, $roles, true)) {
    $selectedRole = 'admin';
}
$role = $selectedRole;

$availableLocales = available_locales();
$errors = [];
$action = $_POST['action'] ?? '';
$deleteRequest = $_POST['delete_group'] ?? null;
if ($deleteRequest !== null) {
    $_POST['group_id'] = $deleteRequest;
    $action = 'delete_group';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        $errors[] = t('navigation.validation.csrf');
    } else {
        require_write_access('instance');
        if ($action === 'create_group') {
            $payload = $_POST['new_group'] ?? [];
            $position = isset($payload['position']) ? (int) $payload['position'] : 0;
            $labelInput = $payload['label'] ?? [];
            $labels = [];
            foreach ($availableLocales as $locale) {
                $labels[$locale] = trim((string) ($labelInput[$locale] ?? ''));
            }
            $repository->createGroup($role, $labels, $position);
            flash('success', t('navigation.flash.group_created'));
        } elseif ($action === 'update_groups') {
            $groupsInput = $_POST['groups'] ?? [];
            foreach ($groupsInput as $groupId => $groupData) {
                $groupId = (int) $groupId;
                if (!$repository->groupBelongsToRole($groupId, $role)) {
                    $errors[] = t('navigation.validation.group_missing');
                    continue;
                }
                $position = isset($groupData['position']) ? (int) $groupData['position'] : 0;
                $labels = [];
                $labelInput = $groupData['label'] ?? [];
                foreach ($availableLocales as $locale) {
                    $labels[$locale] = trim((string) ($labelInput[$locale] ?? ''));
                }
                $repository->updateGroup($groupId, $role, $labels, $position);
            }
            if (!$errors) {
                flash('success', t('navigation.flash.groups_saved'));
            }
        } elseif ($action === 'delete_group') {
            $groupId = (int) ($_POST['group_id'] ?? 0);
            if ($groupId && $repository->groupBelongsToRole($groupId, $role)) {
                $repository->deleteGroup($groupId, $role);
                flash('success', t('navigation.flash.group_deleted'));
            } else {
                $errors[] = t('navigation.validation.group_missing');
            }
        } elseif ($action === 'create_custom_item') {
            $payload = $_POST['new_item'] ?? [];
            $groupId = isset($payload['group_id']) ? (int) $payload['group_id'] : 0;
            if ($groupId <= 0 || !$repository->groupBelongsToRole($groupId, $role)) {
                $errors[] = t('navigation.validation.group_missing');
            }

            $target = trim((string) ($payload['target'] ?? ''));
            if ($target === '') {
                $errors[] = t('navigation.validation.target_invalid', ['item' => t('navigation.labels.custom_item')]);
            } elseif (!preg_match('/^[A-Za-z0-9_\-\/]+\.php(?:#[A-Za-z0-9_\-]+)?$/', $target)) {
                $errors[] = t('navigation.validation.target_invalid', ['item' => t('navigation.labels.custom_item')]);
            }

            $variant = ($payload['variant'] ?? 'primary') === 'secondary' ? 'secondary' : 'primary';
            $position = isset($payload['position']) ? (int) $payload['position'] : 0;

            $labels = [];
            $labelInput = $payload['label'] ?? [];
            foreach ($availableLocales as $locale) {
                $value = trim((string) ($labelInput[$locale] ?? ''));
                if ($value !== '') {
                    $labels[$locale] = $value;
                }
            }

            if (!$labels) {
                $errors[] = t('navigation.validation.label_required');
            }

            if (!$errors) {
                try {
                    $itemKey = navigation_generate_custom_item_key($repository, $role);
                    $repository->createCustomItem($role, $itemKey, $labels, $target, $groupId, $variant, $position);
                    flash('success', t('navigation.flash.custom_created'));
                } catch (Throwable) {
                    $errors[] = t('navigation.validation.custom_key_failed');
                }
            }
        } elseif ($action === 'save_items') {
            $definitions = Rbac::menuDefinitions();
            $definitionsByKey = [];
            foreach ($definitions as $path => $definition) {
                $definitionsByKey[$definition['key']] = ['path' => $path, 'definition' => $definition];
            }
            $itemsInput = $_POST['items'] ?? [];
            $itemsToSave = [];
            foreach ($itemsInput as $itemKey => $itemData) {
                $itemKey = (string) $itemKey;
                $enabled = isset($itemData['enabled']) && (string) $itemData['enabled'] === '1';
                if (!$enabled) {
                    continue;
                }
                $isCustom = isset($itemData['is_custom']) && (string) $itemData['is_custom'] === '1';
                if ($isCustom) {
                    $existing = $currentItemsByKey[$itemKey] ?? null;
                    if ($existing === null || empty($existing['is_custom'])) {
                        continue;
                    }

                    $groupId = isset($itemData['group_id']) ? (int) $itemData['group_id'] : 0;
                    if ($groupId <= 0 || !$repository->groupBelongsToRole($groupId, $role)) {
                        $errors[] = t('navigation.validation.group_required', ['item' => t('navigation.labels.custom_item')]);
                        continue;
                    }

                    $target = trim((string) ($itemData['target'] ?? ''));
                    if ($target === '' || !preg_match('/^[A-Za-z0-9_\-\/]+\.php(?:#[A-Za-z0-9_\-]+)?$/', $target)) {
                        $errors[] = t('navigation.validation.target_invalid', ['item' => t('navigation.labels.custom_item')]);
                        continue;
                    }

                    $variant = $itemData['variant'] ?? 'primary';
                    $variant = $variant === 'secondary' ? 'secondary' : 'primary';
                    $position = isset($itemData['position']) ? (int) $itemData['position'] : 0;

                    $labels = [];
                    $labelInput = $itemData['label'] ?? [];
                    foreach ($availableLocales as $locale) {
                        $value = trim((string) ($labelInput[$locale] ?? ''));
                        if ($value !== '') {
                            $labels[$locale] = $value;
                        }
                    }

                    if (!$labels) {
                        $errors[] = t('navigation.validation.label_required');
                        continue;
                    }

                    $itemsToSave[] = [
                        'item_key' => $itemKey,
                        'target' => $target,
                        'group_id' => $groupId,
                        'variant' => $variant,
                        'position' => $position,
                        'is_custom' => true,
                        'labels' => $labels,
                    ];

                    continue;
                }

                if (!isset($definitionsByKey[$itemKey]) || !Rbac::allowed($role, $itemKey)) {
                    continue;
                }
                $groupId = isset($itemData['group_id']) ? (int) $itemData['group_id'] : 0;
                if ($groupId <= 0 || !$repository->groupBelongsToRole($groupId, $role)) {
                    $itemLabel = $definitionsByKey[$itemKey]['definition']['label_key'] ?? $itemKey;
                    $errors[] = t('navigation.validation.group_required', ['item' => t($itemLabel)]);
                    continue;
                }
                $target = trim((string) ($itemData['target'] ?? ''));
                if ($target === '') {
                    $target = $definitionsByKey[$itemKey]['path'];
                }

                if (!preg_match('/^[A-Za-z0-9_\-\/]+\.php(?:#[A-Za-z0-9_\-]+)?$/', $target)) {
                    $itemLabel = $definitionsByKey[$itemKey]['definition']['label_key'] ?? $itemKey;
                    $errors[] = t('navigation.validation.target_invalid', ['item' => t($itemLabel)]);
                    continue;
                }

                $variant = $itemData['variant'] ?? 'primary';
                $variant = $variant === 'secondary' ? 'secondary' : 'primary';
                $position = isset($itemData['position']) ? (int) $itemData['position'] : 0;

                $itemsToSave[] = [
                    'item_key' => $itemKey,
                    'target' => $target,
                    'group_id' => $groupId,
                    'variant' => $variant,
                    'position' => $position,
                    'is_custom' => false,
                    'labels' => [],
                ];
            }
            if (!$errors) {
                $repository->replaceItems($role, $itemsToSave);
                flash('success', t('navigation.flash.items_saved'));
            }
        } elseif ($action === 'reset_layout') {
            $layout = Rbac::defaultLayoutForRole($role);
            if (!empty($layout['groups']) && !empty($layout['items'])) {
                $repository->replaceLayout($role, $layout['groups'], $layout['items']);
                flash('success', t('navigation.flash.reset'));
            } else {
                $errors[] = t('navigation.validation.reset_failed');
            }
        }
    }

    if ($errors) {
        foreach ($errors as $message) {
            flash('error', $message);
        }
    }

    header('Location: navigation.php?role=' . urlencode($role));
    exit;
}

$groups = $repository->groupsForRole($role);

$currentItems = $repository->itemsForRole($role);
$currentItemsByKey = [];
foreach ($currentItems as $item) {
    $currentItemsByKey[$item['item_key']] = $item;
}

$menuDefinitions = Rbac::menuDefinitions();
$menuItems = [];
foreach ($menuDefinitions as $path => $definition) {
    $key = $definition['key'];
    if (!Rbac::allowed($role, $key)) {
        continue;
    }
    $active = $currentItemsByKey[$key] ?? null;
    $menuItems[] = [
        'key' => $key,
        'path' => $path,
        'label_key' => $definition['label_key'] ?? $key,
        'tooltip_key' => $definition['tooltip_key'] ?? null,
        'variant' => $active['variant'] ?? ($definition['variant'] ?? 'primary'),
        'position' => $active['position'] ?? ($definition['priority'] ?? 50),
        'group_id' => $active['group_id'] ?? null,
        'target' => $active['target'] ?? $path,
        'enabled' => $active !== null,
        'is_custom' => false,
        'label_translations' => $active['label_translations'] ?? [],
    ];
}

foreach ($currentItems as $item) {
    if (empty($item['is_custom'])) {
        continue;
    }

    $translations = $item['label_translations'] ?? [];
    $fallbackLabel = reset($translations);
    if (!is_string($fallbackLabel)) {
        $fallbackLabel = $item['target'];
    }

    $menuItems[] = [
        'key' => $item['item_key'],
        'path' => $item['target'],
        'label_key' => null,
        'tooltip_key' => null,
        'variant' => $item['variant'] ?? 'primary',
        'position' => $item['position'] ?? 50,
        'group_id' => $item['group_id'] ?? null,
        'target' => $item['target'],
        'enabled' => true,
        'is_custom' => true,
        'label_translations' => is_array($translations) ? $translations : [],
        'fallback_label' => is_string($fallbackLabel) ? $fallbackLabel : $item['target'],
    ];
}

usort($menuItems, static function (array $left, array $right): int {
    $leftLabel = $left['label_key'] !== null ? t($left['label_key']) : ($left['fallback_label'] ?? $left['key']);
    $rightLabel = $right['label_key'] !== null ? t($right['label_key']) : ($right['fallback_label'] ?? $right['key']);

    return strcmp((string) $leftLabel, (string) $rightLabel);
});

render_page('navigation.tpl', [
    'titleKey' => 'navigation.title',
    'page' => 'navigation',
    'role' => $role,
    'roles' => $roles,
    'groups' => $groups,
    'menuItems' => $menuItems,
    'locales' => $availableLocales,
    'token' => csrf_token(),
]);
