<?php
require __DIR__ . '/auth.php';

use App\Core\Csrf;
use App\Core\Rbac;
use App\Navigation\NavigationRepository;

$user = auth_require('instance');
$pdo = app_pdo();
$repository = new NavigationRepository($pdo);

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
                if (!isset($definitionsByKey[$itemKey]) || !Rbac::allowed($role, $itemKey)) {
                    continue;
                }
                $groupId = isset($itemData['group_id']) ? (int) $itemData['group_id'] : 0;
                if ($groupId <= 0 || !$repository->groupBelongsToRole($groupId, $role)) {
                    $itemLabel = $definitionsByKey[$itemKey]['definition']['label_key'] ?? $itemKey;
                    $errors[] = t('navigation.validation.group_required', ['item' => t($itemLabel)]);
                    continue;
                }
                $variant = $itemData['variant'] ?? 'primary';
                $variant = $variant === 'secondary' ? 'secondary' : 'primary';
                $position = isset($itemData['position']) ? (int) $itemData['position'] : 0;

                $itemsToSave[] = [
                    'item_key' => $itemKey,
                    'target' => $definitionsByKey[$itemKey]['path'],
                    'group_id' => $groupId,
                    'variant' => $variant,
                    'position' => $position,
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
        'enabled' => $active !== null,
    ];
}

usort($menuItems, static function (array $left, array $right): int {
    return strcmp($left['label_key'], $right['label_key']);
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
