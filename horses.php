<?php
require __DIR__ . '/auth.php';

$user = auth_require('horses');
$owners = db_all('SELECT id, name FROM persons ORDER BY name');

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editHorse = $editId ? db_first('SELECT * FROM horses WHERE id = :id', ['id' => $editId]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('horses.validation.csrf_invalid'));
        header('Location: horses.php');
        exit;
    }

    require_write_access('horses');

    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $horseId = (int) ($_POST['horse_id'] ?? 0);
        if ($horseId) {
            db_execute('DELETE FROM horses WHERE id = :id', ['id' => $horseId]);
            flash('success', t('horses.flash.deleted'));
        }
        header('Location: horses.php');
        exit;
    }

    $horseId = (int) ($_POST['horse_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $ownerId = (int) ($_POST['owner_id'] ?? 0) ?: null;
    $documentsOk = isset($_POST['documents_ok']) ? 1 : 0;
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($name === '') {
        flash('error', t('horses.validation.name_required'));
    } else {
        if ($action === 'update' && $horseId > 0) {
            db_execute(
                'UPDATE horses SET name = :name, owner_id = :owner, documents_ok = :ok, notes = :notes WHERE id = :id',
                [
                    'name' => $name,
                    'owner' => $ownerId,
                    'ok' => $documentsOk,
                    'notes' => $notes ?: null,
                    'id' => $horseId,
                ]
            );
            flash('success', t('horses.flash.updated'));
        } else {
            db_execute(
                'INSERT INTO horses (name, owner_id, documents_ok, notes) VALUES (:name, :owner, :ok, :notes)',
                [
                    'name' => $name,
                    'owner' => $ownerId,
                    'ok' => $documentsOk,
                    'notes' => $notes ?: null,
                ]
            );
            flash('success', t('horses.flash.created'));
        }
    }

    header('Location: horses.php');
    exit;
}

$filterName = trim((string) ($_GET['q'] ?? ''));
$filterOwner = (int) ($_GET['owner'] ?? 0);

$sql = 'SELECT h.*, p.name AS owner_name FROM horses h LEFT JOIN persons p ON p.id = h.owner_id WHERE 1=1';
$params = [];
if ($filterName !== '') {
    $sql .= ' AND h.name LIKE :name';
    $params['name'] = '%' . $filterName . '%';
}
if ($filterOwner) {
    $sql .= ' AND h.owner_id = :owner';
    $params['owner'] = $filterOwner;
}
$sql .= ' ORDER BY h.name LIMIT 100';

$horses = db_all($sql, $params);

render_page('horses.tpl', [
    'titleKey' => 'pages.horses.title',
    'page' => 'horses',
    'horses' => $horses,
    'owners' => $owners,
    'filterName' => $filterName,
    'filterOwner' => $filterOwner,
    'editHorse' => $editHorse,
]);
