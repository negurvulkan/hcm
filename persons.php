<?php
require __DIR__ . '/auth.php';

use App\Core\Rbac;
use DateTimeImmutable;

$user = auth_require('persons');
$roles = Rbac::ROLES;
$clubs = db_all('SELECT id, name FROM clubs ORDER BY name');

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editPerson = null;
if ($editId) {
    $editPerson = db_first('SELECT * FROM persons WHERE id = :id', ['id' => $editId]);
    if ($editPerson) {
        $editPerson['role_list'] = $editPerson['roles'] ? json_decode($editPerson['roles'], true, 512, JSON_THROW_ON_ERROR) : [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: persons.php');
        exit;
    }

    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $personId = (int) ($_POST['person_id'] ?? 0);
        if ($personId) {
            db_execute('DELETE FROM persons WHERE id = :id', ['id' => $personId]);
            flash('success', 'Person gelöscht.');
        }
        header('Location: persons.php');
        exit;
    }

    $personId = (int) ($_POST['person_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $selectedRoles = array_values(array_filter((array) ($_POST['roles'] ?? [])));
    $clubId = (int) ($_POST['club_id'] ?? 0) ?: null;

    if ($name === '' || !$selectedRoles) {
        flash('error', 'Name und mindestens eine Rolle angeben.');
    } else {
        if ($action === 'update' && $personId > 0) {
            db_execute(
                'UPDATE persons SET name = :name, email = :email, phone = :phone, roles = :roles, club_id = :club_id WHERE id = :id',
                [
                    'name' => $name,
                    'email' => $email ?: null,
                    'phone' => $phone ?: null,
                    'roles' => json_encode(array_values($selectedRoles), JSON_THROW_ON_ERROR),
                    'club_id' => $clubId,
                    'id' => $personId,
                ]
            );
            flash('success', 'Person aktualisiert.');
        } else {
            db_execute(
                'INSERT INTO persons (name, email, phone, roles, club_id, created_at) VALUES (:name, :email, :phone, :roles, :club_id, :created_at)',
                [
                    'name' => $name,
                    'email' => $email ?: null,
                    'phone' => $phone ?: null,
                    'roles' => json_encode(array_values($selectedRoles), JSON_THROW_ON_ERROR),
                    'club_id' => $clubId,
                    'created_at' => (new DateTimeImmutable())->format('c'),
                ]
            );
            flash('success', 'Person angelegt.');
        }
    }

    header('Location: persons.php');
    exit;
}

$filterName = trim((string) ($_GET['q'] ?? ''));
$filterRole = trim((string) ($_GET['role'] ?? ''));

$sql = 'SELECT p.*, c.name AS club_name FROM persons p LEFT JOIN clubs c ON c.id = p.club_id WHERE 1=1';
$params = [];
if ($filterName !== '') {
    $sql .= ' AND p.name LIKE :name';
    $params['name'] = '%' . $filterName . '%';
}
if ($filterRole !== '') {
    $sql .= ' AND p.roles LIKE :role';
    $params['role'] = '%"' . $filterRole . '"%';
}
$sql .= ' ORDER BY p.name LIMIT 100';

$persons = db_all($sql, $params);
foreach ($persons as &$person) {
    $person['role_list'] = json_decode($person['roles'] ?? '[]', true, 512, JSON_THROW_ON_ERROR) ?: [];
}
unset($person);

render_page('persons.tpl', [
    'title' => 'Personen',
    'page' => 'persons',
    'roles' => $roles,
    'persons' => $persons,
    'clubs' => $clubs,
    'filterName' => $filterName,
    'filterRole' => $filterRole,
    'editPerson' => $editPerson,
]);
