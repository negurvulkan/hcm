<?php
require __DIR__ . '/auth.php';

use App\Core\Rbac;

if (!function_exists('persons_primary_role')) {
    function persons_primary_role(array $roles): string
    {
        foreach (Rbac::ROLES as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return $roles[0] ?? 'participant';
    }
}

if (!function_exists('persons_sync_user')) {
    function persons_sync_user(?array $currentPerson, string $name, string $email, array $roles, bool $setPassword, ?string $password): void
    {
        $normalizedEmail = $email !== '' ? mb_strtolower($email) : '';

        if ($normalizedEmail === '') {
            if ($currentPerson) {
                $oldEmail = trim((string) ($currentPerson['email'] ?? ''));
                if ($oldEmail !== '') {
                    db_execute('DELETE FROM users WHERE email = :email', ['email' => mb_strtolower($oldEmail)]);
                }
            }

            return;
        }

        $existingUser = db_first('SELECT * FROM users WHERE email = :email', ['email' => $normalizedEmail]);
        if (!$existingUser && $currentPerson) {
            $oldEmail = trim((string) ($currentPerson['email'] ?? ''));
            $normalizedOld = $oldEmail !== '' ? mb_strtolower($oldEmail) : '';
            if ($normalizedOld && $normalizedOld !== $normalizedEmail) {
                $existingUser = db_first('SELECT * FROM users WHERE email = :email', ['email' => $normalizedOld]);
            }
        }

        $primaryRole = persons_primary_role($roles);

        if ($existingUser) {
            $params = [
                'id' => (int) $existingUser['id'],
                'name' => $name,
                'email' => $normalizedEmail,
                'role' => $primaryRole,
            ];
            $sql = 'UPDATE users SET name = :name, email = :email, role = :role';
            if ($setPassword && $password !== null) {
                $params['password'] = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ', password = :password';
            }
            $sql .= ' WHERE id = :id';
            db_execute($sql, $params);
            return;
        }

        if ($setPassword && $password !== null) {
            db_execute(
                'INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, :created_at)',
                [
                    'name' => $name,
                    'email' => $normalizedEmail,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $primaryRole,
                    'created_at' => (new \DateTimeImmutable())->format('c'),
                ]
            );
        }
    }
}

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

    require_write_access('persons');

    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $personId = (int) ($_POST['person_id'] ?? 0);
        if ($personId) {
            $person = db_first('SELECT email FROM persons WHERE id = :id', ['id' => $personId]);
            if ($person && ($person['email'] ?? '') !== '') {
                db_execute('DELETE FROM users WHERE email = :email', ['email' => mb_strtolower($person['email'])]);
            }
            db_execute('DELETE FROM persons WHERE id = :id', ['id' => $personId]);
            flash('success', 'Person gelöscht.');
        }
        header('Location: persons.php');
        exit;
    }

    $personId = (int) ($_POST['person_id'] ?? 0);
    $currentPerson = null;
    if ($personId > 0) {
        $currentPerson = db_first('SELECT * FROM persons WHERE id = :id', ['id' => $personId]);
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $selectedRoles = array_values(array_filter((array) ($_POST['roles'] ?? [])));
    $selectedRoles = array_values(array_intersect($selectedRoles, Rbac::ROLES));
    $clubId = (int) ($_POST['club_id'] ?? 0) ?: null;
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $setPassword = $password !== '' || $passwordConfirm !== '';

    $errors = [];

    if ($action === 'update' && $personId > 0 && !$currentPerson) {
        $errors[] = 'Person nicht gefunden.';
    }

    if ($name === '') {
        $errors[] = 'Name angeben.';
    }

    if (!$selectedRoles) {
        $errors[] = 'Mindestens eine Rolle auswählen.';
    }

    $normalizedEmail = $email !== '' ? mb_strtolower($email) : '';
    $previousEmail = $currentPerson ? mb_strtolower((string) ($currentPerson['email'] ?? '')) : '';

    if ($setPassword) {
        if ($password === '' || $passwordConfirm === '') {
            $errors[] = 'Passwort und Bestätigung angeben.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwörter stimmen nicht überein.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Passwort muss mindestens 8 Zeichen lang sein.';
        }
        if ($normalizedEmail === '') {
            $errors[] = 'E-Mail benötigt, um ein Passwort zu setzen.';
        }
    }

    if ($normalizedEmail !== '') {
        $userByEmail = db_first('SELECT id FROM users WHERE email = :email', ['email' => $normalizedEmail]);
        if ($userByEmail && ($action !== 'update' || $previousEmail !== $normalizedEmail)) {
            $errors[] = 'E-Mail wird bereits von einem anderen Benutzer verwendet.';
        }
    }

    if ($errors) {
        foreach ($errors as $message) {
            flash('error', $message);
        }
        $redirect = 'persons.php';
        if ($action === 'update' && $personId > 0) {
            $redirect .= '?edit=' . $personId;
        }
        header('Location: ' . $redirect);
        exit;
    }

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
        persons_sync_user($currentPerson, $name, $email, $selectedRoles, $setPassword, $setPassword ? $password : null);
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
                'created_at' => (new \DateTimeImmutable())->format('c'),
            ]
        );
        persons_sync_user(null, $name, $email, $selectedRoles, $setPassword, $setPassword ? $password : null);
        flash('success', 'Person angelegt.');
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
