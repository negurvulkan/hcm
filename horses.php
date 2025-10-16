<?php
require __DIR__ . '/auth.php';

$user = auth_require('horses');
$owners = db_all('SELECT id, name FROM persons ORDER BY name');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: horses.php');
        exit;
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $ownerId = (int) ($_POST['owner_id'] ?? 0) ?: null;
    $documentsOk = isset($_POST['documents_ok']) ? 1 : 0;
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($name === '') {
        flash('error', 'Name erforderlich.');
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
        flash('success', 'Pferd gespeichert.');
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
    'title' => 'Pferde',
    'page' => 'horses',
    'horses' => $horses,
    'owners' => $owners,
    'filterName' => $filterName,
    'filterOwner' => $filterOwner,
]);
