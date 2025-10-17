<?php
require __DIR__ . '/auth.php';

use App\Core\Rbac;
use App\Party\PartyRepository;

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
$repository = new PartyRepository(app_pdo());

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editPerson = null;
if ($editId) {
    $editPerson = $repository->findPerson($editId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('persons.validation.csrf_invalid'));
        header('Location: persons.php');
        exit;
    }

    require_write_access('persons');

    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $personId = (int) ($_POST['person_id'] ?? 0);
        if ($personId) {
            $person = $repository->findPerson($personId);
            if ($person && ($person['email'] ?? '') !== '') {
                db_execute('DELETE FROM users WHERE email = :email', ['email' => mb_strtolower($person['email'])]);
            }
            $repository->deletePerson($personId);
            flash('success', t('persons.flash.deleted'));
        }
        header('Location: persons.php');
        exit;
    }

    $personId = (int) ($_POST['person_id'] ?? 0);
    $currentPerson = null;
    if ($personId > 0) {
        $currentPerson = $repository->findPerson($personId);
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
        $errors[] = t('persons.validation.not_found');
    }

    if ($name === '') {
        $errors[] = t('persons.validation.name_required');
    }

    if (!$selectedRoles) {
        $errors[] = t('persons.validation.role_required');
    }

    $normalizedEmail = $email !== '' ? mb_strtolower($email) : '';
    $previousEmail = $currentPerson ? mb_strtolower((string) ($currentPerson['email'] ?? '')) : '';

    if ($setPassword) {
        if ($password === '' || $passwordConfirm === '') {
            $errors[] = t('persons.validation.password_required');
        }
        if ($password !== $passwordConfirm) {
            $errors[] = t('persons.validation.password_mismatch');
        }
        if (mb_strlen($password) < 8) {
            $errors[] = t('persons.validation.password_length');
        }
        if ($normalizedEmail === '') {
            $errors[] = t('persons.validation.email_required_for_password');
        }
    }

    if ($normalizedEmail !== '') {
        $userByEmail = db_first('SELECT id FROM users WHERE email = :email', ['email' => $normalizedEmail]);
        if ($userByEmail && ($action !== 'update' || $previousEmail !== $normalizedEmail)) {
            $errors[] = t('persons.validation.email_taken');
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
        $repository->updatePerson($personId, $name, $email ?: null, $phone ?: null, $clubId, $selectedRoles);
        persons_sync_user($currentPerson, $name, $email, $selectedRoles, $setPassword, $setPassword ? $password : null);
        flash('success', t('persons.flash.updated'));
    } else {
        $newId = $repository->createPerson($name, $email ?: null, $phone ?: null, $clubId, $selectedRoles);
        persons_sync_user(null, $name, $email, $selectedRoles, $setPassword, $setPassword ? $password : null);
        flash('success', t('persons.flash.created'));
    }

    header('Location: persons.php');
    exit;
}

$filterName = trim((string) ($_GET['q'] ?? ''));
$filterRole = trim((string) ($_GET['role'] ?? ''));

$persons = $repository->searchPersons($filterName !== '' ? $filterName : null, $filterRole !== '' ? $filterRole : null);

render_page('persons.tpl', [
    'title' => t('pages.persons.title'),
    'titleKey' => 'pages.persons.title',
    'page' => 'persons',
    'roles' => $roles,
    'persons' => $persons,
    'clubs' => $clubs,
    'filterName' => $filterName,
    'filterRole' => $filterRole,
    'editPerson' => $editPerson,
]);
