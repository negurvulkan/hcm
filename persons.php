<?php
require __DIR__ . '/auth.php';

use App\Core\Rbac;
use App\CustomFields\CustomFieldManager;
use App\CustomFields\CustomFieldRepository;
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
    function persons_sync_user(?array $currentPerson, string $displayName, string $email, array $roles, bool $setPassword, ?string $password): void
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
                'name' => $displayName,
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
                    'name' => $displayName,
                    'email' => $normalizedEmail,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $primaryRole,
                    'created_at' => (new \DateTimeImmutable())->format('c'),
                ]
            );
        }
    }
}

if (!function_exists('persons_redirect_with_tab')) {
    function persons_redirect_with_tab(string $url, ?string $tab): void
    {
        if ($tab !== null && in_array($tab, ['staff', 'participants'], true)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'tab=' . rawurlencode($tab);
        }

        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('persons_profile_key')) {
    function persons_profile_key(string $tab): string
    {
        return $tab === 'staff' ? 'staff' : 'participant';
    }
}

$user = auth_require('persons');
$roles = Rbac::ROLES;
$staffRoles = array_values(array_filter($roles, static fn ($role) => $role !== 'participant'));
$personStatuses = ['active', 'blocked', 'archived'];
$clubs = db_all('SELECT id, name FROM clubs ORDER BY name');
$pdo = app_pdo();
$repository = new PartyRepository($pdo);
$customFieldRepository = new CustomFieldRepository($pdo);
$activeEventId = event_active_id();
$primaryOrganizationId = instance_primary_organization_id();

$activeTab = $_GET['tab'] ?? 'staff';
if (!in_array($activeTab, ['staff', 'participants'], true)) {
    $activeTab = 'staff';
}

$customFieldContext = [
    'profiles' => persons_profile_key($activeTab),
    'tournament_id' => $activeEventId,
];
if ($primaryOrganizationId !== null) {
    $customFieldContext['organization_id'] = $primaryOrganizationId;
}
$customFieldManager = new CustomFieldManager($customFieldRepository, 'person', $customFieldContext);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editPerson = null;
if ($editId) {
    $editPerson = $repository->findPerson($editId);
}
$editCustomFieldValues = $editId ? $customFieldRepository->valuesFor('person', $editId) : [];
$personCustomFieldForm = $customFieldManager->formFields($editCustomFieldValues);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('persons.validation.csrf_invalid'));
        $tab = $_POST['tab'] ?? null;
        $tab = in_array($tab, ['staff', 'participants'], true) ? $tab : null;
        persons_redirect_with_tab('persons.php', $tab);
    }

    require_write_access('persons');

    $action = $_POST['action'] ?? 'create';
    $redirectTab = $_POST['tab'] ?? null;
    $redirectTab = in_array($redirectTab, ['staff', 'participants'], true) ? $redirectTab : null;

    $customFieldManagerPost = null;
    $customFieldValues = [];
    if (in_array($action, ['create', 'update'], true)) {
        $profileKey = persons_profile_key($redirectTab ?? $activeTab);
        $postContext = [
            'profiles' => $profileKey,
            'tournament_id' => $activeEventId,
        ];
        if ($primaryOrganizationId !== null) {
            $postContext['organization_id'] = $primaryOrganizationId;
        }
        $customFieldManagerPost = new CustomFieldManager($customFieldRepository, 'person', $postContext);
        $customFieldResult = $customFieldManagerPost->validate((array) ($_POST['custom_fields'] ?? []));
        $customFieldValues = $customFieldResult['values'];
        if ($customFieldResult['errors']) {
            $errors = array_merge($errors, $customFieldResult['errors']);
        }
    }

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
        persons_redirect_with_tab('persons.php', $redirectTab);
    }

    $personId = (int) ($_POST['person_id'] ?? 0);
    $currentPerson = null;
    if ($personId > 0) {
        $currentPerson = $repository->findPerson($personId);
    }

    $givenName = trim((string) ($_POST['given_name'] ?? ''));
    $familyName = trim((string) ($_POST['family_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $selectedRoles = array_values(array_filter((array) ($_POST['roles'] ?? [])));
    $selectedRoles = array_values(array_intersect($selectedRoles, Rbac::ROLES));
    $clubId = (int) ($_POST['club_id'] ?? 0) ?: null;
    $dateOfBirth = trim((string) ($_POST['date_of_birth'] ?? ''));
    $nationality = trim((string) ($_POST['nationality'] ?? ''));
    $status = in_array($_POST['status'] ?? 'active', $personStatuses, true) ? ($_POST['status'] ?? 'active') : 'active';
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $setPassword = $password !== '' || $passwordConfirm !== '';

    $errors = [];

    if ($action === 'update' && $personId > 0 && !$currentPerson) {
        $errors[] = t('persons.validation.not_found');
    }

    if ($givenName === '') {
        $errors[] = t('persons.validation.given_name_required');
    }

    if ($familyName === '') {
        $errors[] = t('persons.validation.family_name_required');
    }

    if ($email === '' && $phone === '') {
        $errors[] = t('persons.validation.contact_required');
    }

    if (!$selectedRoles) {
        $errors[] = t('persons.validation.role_required');
    }

    if ($dateOfBirth !== '') {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $dateOfBirth);
        if (!$date || $date->format('Y-m-d') !== $dateOfBirth) {
            $errors[] = t('persons.validation.date_of_birth_invalid');
        }
    } else {
        $dateOfBirth = null;
    }

    if ($nationality !== '' && !preg_match('/^[A-Za-z]{2,3}$/', $nationality)) {
        $errors[] = t('persons.validation.nationality_invalid');
    } elseif ($nationality === '') {
        $nationality = null;
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
        persons_redirect_with_tab($redirect, $redirectTab);
    }

    if ($action === 'update' && $personId > 0) {
        $repository->updatePerson(
            $personId,
            $givenName,
            $familyName,
            $email ?: null,
            $phone ?: null,
            $clubId,
            $selectedRoles,
            $dateOfBirth,
            $nationality,
            $status,
            $currentPerson['uuid'] ?? null
        );
        $displayName = trim($givenName . ' ' . $familyName);
        persons_sync_user($currentPerson, $displayName, $email, $selectedRoles, $setPassword, $setPassword ? $password : null);
        if ($customFieldManagerPost) {
            $customFieldRepository->saveValues('person', $personId, $customFieldValues, $customFieldManagerPost->context());
        }
        flash('success', t('persons.flash.updated'));
    } else {
        $newId = $repository->createPerson(
            $givenName,
            $familyName,
            $email ?: null,
            $phone ?: null,
            $clubId,
            $selectedRoles,
            $dateOfBirth,
            $nationality,
            $status
        );
        $displayName = trim($givenName . ' ' . $familyName);
        persons_sync_user(null, $displayName, $email, $selectedRoles, $setPassword, $setPassword ? $password : null);
        if ($customFieldManagerPost) {
            $customFieldRepository->saveValues('person', $newId, $customFieldValues, $customFieldManagerPost->context());
        }
        flash('success', t('persons.flash.created'));
    }

    persons_redirect_with_tab('persons.php', $redirectTab);
}

$filterName = trim((string) ($_GET['q'] ?? ''));
$filterRole = trim((string) ($_GET['role'] ?? ''));
$filterStatusRaw = trim((string) ($_GET['status'] ?? ''));
$filterStatus = in_array($filterStatusRaw, $personStatuses, true) ? $filterStatusRaw : '';

$persons = $repository->searchPersons(
    $filterName !== '' ? $filterName : null,
    $filterRole !== '' ? $filterRole : null,
    $filterStatus !== '' ? $filterStatus : null
);

$personIds = array_map(static fn (array $row): int => (int) $row['id'], $persons);
$personCustomFieldValues = $customFieldRepository->valuesForMany('person', $personIds);
foreach ($persons as &$person) {
    $id = (int) $person['id'];
    $person['custom_fields'] = $customFieldManager->formatListValues($personCustomFieldValues[$id] ?? []);
}
unset($person);

$personCustomFieldColumns = $customFieldManager->listColumns();

$staffPersons = [];
$participantPersons = [];
foreach ($persons as $person) {
    $roleList = $person['role_list'] ?? [];
    $hasStaffRole = !empty(array_intersect($staffRoles, $roleList));
    $hasParticipantRole = in_array('participant', $roleList, true);

    if ($hasStaffRole) {
        $staffPersons[] = $person;
    }

    if ($hasParticipantRole || !$hasStaffRole) {
        $participantPersons[] = $person;
    }
}

render_page('persons.tpl', [
    'title' => t('pages.persons.title'),
    'titleKey' => 'pages.persons.title',
    'page' => 'persons',
    'roles' => $roles,
    'persons' => $persons,
    'staffRoles' => $staffRoles,
    'staffPersons' => $staffPersons,
    'participantPersons' => $participantPersons,
    'clubs' => $clubs,
    'filterName' => $filterName,
    'filterRole' => $filterRole,
    'filterStatus' => $filterStatus,
    'activeTab' => $activeTab,
    'editPerson' => $editPerson,
    'personStatuses' => $personStatuses,
    'personCustomFieldForm' => $personCustomFieldForm,
    'personCustomFieldColumns' => $personCustomFieldColumns,
]);
