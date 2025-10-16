<?php
require __DIR__ . '/auth.php';

$user = auth_require('clubs');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: clubs.php');
        exit;
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $short = strtoupper(trim((string) ($_POST['short_name'] ?? '')));

    if ($name === '' || $short === '') {
        flash('error', 'Name und Kürzel angeben.');
    } else {
        db_execute('INSERT INTO clubs (name, short_name) VALUES (:name, :short)', [
            'name' => $name,
            'short' => $short,
        ]);
        flash('success', 'Verein gespeichert.');
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
]);
