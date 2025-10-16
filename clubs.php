<?php
require __DIR__ . '/auth.php';

$user = auth_require('clubs');

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editClub = $editId ? db_first('SELECT * FROM clubs WHERE id = :id', ['id' => $editId]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: clubs.php');
        exit;
    }

    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $clubId = (int) ($_POST['club_id'] ?? 0);
        if ($clubId) {
            db_execute('DELETE FROM clubs WHERE id = :id', ['id' => $clubId]);
            flash('success', 'Verein gelöscht.');
        }
        header('Location: clubs.php');
        exit;
    }

    $clubId = (int) ($_POST['club_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $short = strtoupper(trim((string) ($_POST['short_name'] ?? '')));

    if ($name === '' || $short === '') {
        flash('error', 'Name und Kürzel angeben.');
    } else {
        if ($action === 'update' && $clubId > 0) {
            db_execute('UPDATE clubs SET name = :name, short_name = :short WHERE id = :id', [
                'name' => $name,
                'short' => $short,
                'id' => $clubId,
            ]);
            flash('success', 'Verein aktualisiert.');
        } else {
            db_execute('INSERT INTO clubs (name, short_name) VALUES (:name, :short)', [
                'name' => $name,
                'short' => $short,
            ]);
            flash('success', 'Verein gespeichert.');
        }
    }

    header('Location: clubs.php');
    exit;
}

$filter = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT * FROM clubs WHERE 1=1';
$params = [];
if ($filter !== '') {
    $sql .= ' AND (name LIKE :filter OR short_name LIKE :filter)';
    $params['filter'] = '%' . $filter . '%';
}
$sql .= ' ORDER BY name';
$clubs = db_all($sql, $params);

render_page('clubs.tpl', [
    'title' => 'Vereine',
    'page' => 'clubs',
    'clubs' => $clubs,
    'filter' => $filter,
    'editClub' => $editClub,
]);
