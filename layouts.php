<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

use App\Core\Csrf;
use App\Layouts\LayoutController;
use App\Layouts\LayoutRepository;
use App\Models\Layout;
use App\Models\LayoutVersion;

$user = auth_require('layouts');
$pdo = app_pdo();
$repository = new LayoutRepository($pdo);
$controller = new LayoutController($repository);

if (($_GET['action'] ?? '') === 'export') {
    $layoutId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $version = isset($_GET['version']) ? (int) $_GET['version'] : null;
    if ($layoutId > 0) {
        try {
            $path = $controller->export($layoutId, $version);
            $filename = 'layout-' . $layoutId . ($version ? '-v' . $version : '') . '.zip';
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            @unlink($path);
            exit;
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
            header('Location: layouts.php');
            exit;
        }
    }
    flash('error', t('layouts.flash.export_failed'));
    header('Location: layouts.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('layouts.flash.csrf'));
        header('Location: layouts.php');
        exit;
    }

    require_write_access('layouts');

    if (($user['role'] ?? '') === 'announcer') {
        flash('error', t('layouts.flash.permission_denied'));
        header('Location: layouts.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $redirect = 'layouts.php';

    try {
        switch ($action) {
            case 'create':
                $controller->create($_POST, $user);
                flash('success', t('layouts.flash.created'));
                break;
            case 'update_meta':
                $layoutId = (int) ($_POST['layout_id'] ?? 0);
                if ($layoutId <= 0) {
                    throw new RuntimeException('Layout-ID fehlt.');
                }
                $controller->updateMetadata($layoutId, $_POST, $user);
                flash('success', t('layouts.flash.updated'));
                $redirect .= '?edit=' . $layoutId;
                break;
            case 'create_version':
                $layoutId = (int) ($_POST['layout_id'] ?? 0);
                if ($layoutId <= 0) {
                    throw new RuntimeException('Layout-ID fehlt.');
                }
                $controller->createVersion($layoutId, $_POST, $user);
                flash('success', t('layouts.flash.version_created'));
                $redirect .= '?edit=' . $layoutId;
                break;
            case 'approve':
                $layoutId = (int) ($_POST['layout_id'] ?? 0);
                $version = (int) ($_POST['version'] ?? 0);
                if ($layoutId <= 0 || $version <= 0) {
                    throw new RuntimeException('Layout-Version unvollständig.');
                }
                $controller->approve($layoutId, $version, $user, $_POST['comment'] ?? null);
                flash('success', t('layouts.flash.approved'));
                $redirect .= '?edit=' . $layoutId;
                break;
            case 'duplicate':
                $layoutId = (int) ($_POST['layout_id'] ?? 0);
                if ($layoutId <= 0) {
                    throw new RuntimeException('Layout-ID fehlt.');
                }
                $duplicate = $controller->duplicate($layoutId, $user, $_POST['name'] ?? null);
                flash('success', t('layouts.flash.duplicated'));
                $redirect .= '?edit=' . ($duplicate->id ?? 0);
                break;
            case 'delete':
                $layoutId = (int) ($_POST['layout_id'] ?? 0);
                if ($layoutId <= 0) {
                    throw new RuntimeException('Layout-ID fehlt.');
                }
                $controller->delete($layoutId);
                flash('success', t('layouts.flash.deleted'));
                break;
            case 'import':
                if (!isset($_FILES['package']) || !is_uploaded_file($_FILES['package']['tmp_name'])) {
                    throw new RuntimeException('Keine Importdatei hochgeladen.');
                }
                $layout = $controller->import($_FILES['package']['tmp_name'], $user);
                flash('success', t('layouts.flash.imported'));
                $redirect .= '?edit=' . ($layout->id ?? 0);
                break;
            default:
                throw new RuntimeException('Unbekannte Aktion.');
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    header('Location: ' . $redirect);
    exit;
}

$filters = [
    'search' => $_GET['search'] ?? '',
    'category' => $_GET['category'] ?? 'all',
    'status' => $_GET['status'] ?? 'all',
    'owner_id' => isset($_GET['owner_id']) ? (int) $_GET['owner_id'] : null,
];

$result = $controller->index($filters);
$layouts = [];
$versions = [];
foreach ($result['layouts'] as $layout) {
    if (!$layout instanceof Layout) {
        continue;
    }
    $layouts[] = $layout->toArray();
    $layoutId = $layout->id ?? 0;
    if ($layoutId > 0) {
        $versions[$layoutId] = array_map(static fn (LayoutVersion $version): array => $version->toArray(), $repository->getVersions($layoutId));
    }
}

$users = db_all('SELECT id, name, email FROM users ORDER BY name ASC');
$ownerOptions = array_map(static function (array $row): array {
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'] ?: $row['email'],
        'email' => $row['email'],
    ];
}, $users);

render_page('layout-list.tpl', [
    'titleKey' => 'layouts.title',
    'page' => 'layouts',
    'filters' => $result['filters'],
    'layouts' => $layouts,
    'versions' => $versions,
    'categories' => $result['categories'],
    'statuses' => $result['statuses'],
    'ownerOptions' => $ownerOptions,
    'activeLayoutId' => isset($_GET['edit']) ? (int) $_GET['edit'] : null,
]);
