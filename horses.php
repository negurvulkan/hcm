<?php
require __DIR__ . '/auth.php';

use App\CustomFields\CustomFieldManager;
use App\CustomFields\CustomFieldRepository;
use App\Party\PartyRepository;

$user = auth_require('horses');
$pdo = app_pdo();
$partyRepository = new PartyRepository($pdo);
$owners = $partyRepository->personOptions();
$horseSexes = ['unknown', 'mare', 'gelding', 'stallion'];
$customFieldRepository = new CustomFieldRepository($pdo);
$activeEventId = event_active_id();
$primaryOrganizationId = instance_primary_organization_id();
$customFieldContext = [
    'tournament_id' => $activeEventId,
];
if ($primaryOrganizationId !== null) {
    $customFieldContext['organization_id'] = $primaryOrganizationId;
}
$customFieldManager = new CustomFieldManager($customFieldRepository, 'horse', $customFieldContext);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editHorse = $editId ? db_first('SELECT * FROM horses WHERE id = :id', ['id' => $editId]) : null;
$editCustomFields = $editId ? $customFieldRepository->valuesFor('horse', $editId) : [];
$horseCustomFieldForm = $customFieldManager->formFields($editCustomFields);

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
    $lifeNumber = trim((string) ($_POST['life_number'] ?? ''));
    $microchip = trim((string) ($_POST['microchip'] ?? ''));
    $sex = in_array($_POST['sex'] ?? 'unknown', $horseSexes, true) ? ($_POST['sex'] ?? 'unknown') : 'unknown';
    $birthYearRaw = trim((string) ($_POST['birth_year'] ?? ''));
    $birthYear = null;
    $customFieldResult = $customFieldManager->validate((array) ($_POST['custom_fields'] ?? []));

    $errors = [];

    if ($name === '') {
        $errors[] = t('horses.validation.name_required');
    }

    if ($lifeNumber === '' && $microchip === '') {
        $errors[] = t('horses.validation.identification_required');
    }

    if ($birthYearRaw !== '') {
        if (!preg_match('/^\d{4}$/', $birthYearRaw)) {
            $errors[] = t('horses.validation.birth_year_invalid');
        } else {
            $birthYear = (int) $birthYearRaw;
            $currentYear = (int) (new DateTimeImmutable())->format('Y');
            if ($birthYear < 1900 || $birthYear > $currentYear) {
                $errors[] = t('horses.validation.birth_year_invalid');
            }
        }
    }

    if ($customFieldResult['errors']) {
        $errors = array_merge($errors, $customFieldResult['errors']);
    }

    if ($errors) {
        foreach ($errors as $message) {
            flash('error', $message);
        }
    } else {
        if ($action === 'update' && $horseId > 0) {
            db_execute(
                'UPDATE horses SET uuid = COALESCE(uuid, :uuid), name = :name, owner_party_id = :owner, documents_ok = :ok, notes = :notes, life_number = :life, microchip = :chip, sex = :sex, birth_year = :birth WHERE id = :id',
                [
                    'uuid' => $editHorse['uuid'] ?? app_uuid(),
                    'name' => $name,
                    'owner' => $ownerId,
                    'ok' => $documentsOk,
                    'notes' => $notes ?: null,
                    'life' => $lifeNumber !== '' ? $lifeNumber : null,
                    'chip' => $microchip !== '' ? $microchip : null,
                    'sex' => $sex,
                    'birth' => $birthYear,
                    'id' => $horseId,
                ]
            );
            $customFieldRepository->saveValues('horse', $horseId, $customFieldResult['values'], $customFieldManager->context());
            flash('success', t('horses.flash.updated'));
        } else {
            db_execute(
                'INSERT INTO horses (uuid, name, owner_party_id, documents_ok, notes, life_number, microchip, sex, birth_year) VALUES (:uuid, :name, :owner, :ok, :notes, :life, :chip, :sex, :birth)',
                [
                    'uuid' => app_uuid(),
                    'name' => $name,
                    'owner' => $ownerId,
                    'ok' => $documentsOk,
                    'notes' => $notes ?: null,
                    'life' => $lifeNumber !== '' ? $lifeNumber : null,
                    'chip' => $microchip !== '' ? $microchip : null,
                    'sex' => $sex,
                    'birth' => $birthYear,
                ]
            );
            $newId = (int) $pdo->lastInsertId();
            if ($newId > 0) {
                $customFieldRepository->saveValues('horse', $newId, $customFieldResult['values'], $customFieldManager->context());
            }
            flash('success', t('horses.flash.created'));
        }
    }

    header('Location: horses.php');
    exit;
}

$filterName = trim((string) ($_GET['q'] ?? ''));
$filterOwner = (int) ($_GET['owner'] ?? 0);

$sql = 'SELECT h.*, pr.display_name AS owner_name FROM horses h LEFT JOIN parties pr ON pr.id = h.owner_party_id WHERE 1=1';
$params = [];
if ($filterName !== '') {
    $sql .= ' AND h.name LIKE :name';
    $params['name'] = '%' . $filterName . '%';
}
if ($filterOwner) {
    $sql .= ' AND h.owner_party_id = :owner';
    $params['owner'] = $filterOwner;
}
$sql .= ' ORDER BY h.name LIMIT 100';

$horses = db_all($sql, $params);
$horseIds = array_map(static fn (array $row): int => (int) $row['id'], $horses);
$horseCustomValues = $customFieldRepository->valuesForMany('horse', $horseIds);
foreach ($horses as &$horse) {
    $id = (int) $horse['id'];
    $horse['custom_fields'] = $customFieldManager->formatListValues($horseCustomValues[$id] ?? []);
}
unset($horse);
$horseCustomFieldColumns = $customFieldManager->listColumns();

render_page('horses.tpl', [
    'titleKey' => 'pages.horses.title',
    'page' => 'horses',
    'horses' => $horses,
    'owners' => $owners,
    'filterName' => $filterName,
    'filterOwner' => $filterOwner,
    'editHorse' => $editHorse,
    'horseSexes' => $horseSexes,
    'horseCustomFieldForm' => $horseCustomFieldForm,
    'horseCustomFieldColumns' => $horseCustomFieldColumns,
]);
