<?php
require __DIR__ . '/auth.php';

use App\CustomFields\CustomFieldRepository;

$user = auth_require('custom_fields');
$pdo = app_pdo();
$repository = new CustomFieldRepository($pdo);

$availableLocales = available_locales();
$defaultLocale = $availableLocales[0] ?? current_locale();

$entityOptions = [
    'person' => t('custom_fields.entities.person'),
    'horse' => t('custom_fields.entities.horse'),
    'entry' => t('custom_fields.entities.entry'),
];

$typeOptions = [
    'text' => t('custom_fields.types.text'),
    'textarea' => t('custom_fields.types.textarea'),
    'int' => t('custom_fields.types.int'),
    'float' => t('custom_fields.types.float'),
    'bool' => t('custom_fields.types.bool'),
    'json' => t('custom_fields.types.json'),
];

$scopeOptions = [
    'global' => t('custom_fields.scope.global'),
    'organization' => t('custom_fields.scope.organization'),
    'tournament' => t('custom_fields.scope.tournament'),
];

$visibilityOptions = [
    'internal' => t('custom_fields.visibility.internal'),
    'public' => t('custom_fields.visibility.public'),
    'private' => t('custom_fields.visibility.private'),
];

$organizations = db_all('SELECT p.id, p.display_name FROM parties p WHERE p.party_type = :type ORDER BY p.display_name', ['type' => 'organization']);
$tournaments = db_all('SELECT id, title, start_date FROM events ORDER BY COALESCE(start_date, \'9999-12-31\') DESC, title');

$definitions = $repository->allDefinitions();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editDefinition = $editId ? $repository->findDefinition($editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('custom_fields.validation.csrf_invalid'));
        header('Location: custom_fields.php');
        exit;
    }

    require_write_access('custom_fields');

    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $definitionId = (int) ($_POST['definition_id'] ?? 0);
        if ($definitionId) {
            try {
                $repository->deleteDefinition($definitionId);
                flash('success', t('custom_fields.flash.deleted'));
            } catch (RuntimeException $exception) {
                flash('error', $exception->getMessage());
            }
        }
        header('Location: custom_fields.php');
        exit;
    }

    $definitionId = (int) ($_POST['definition_id'] ?? 0);
    $entity = (string) ($_POST['entity'] ?? '');
    $fieldKey = trim((string) ($_POST['field_key'] ?? ''));
    $type = (string) ($_POST['type'] ?? '');
    $scope = (string) ($_POST['scope'] ?? 'global');
    $visibility = (string) ($_POST['visibility'] ?? 'internal');
    $profileKey = trim((string) ($_POST['profile_key'] ?? ''));
    $version = (int) ($_POST['version'] ?? 1);
    $regexPattern = trim((string) ($_POST['regex_pattern'] ?? ''));
    $minValue = trim((string) ($_POST['min_value'] ?? ''));
    $maxValue = trim((string) ($_POST['max_value'] ?? ''));
    $validFromInput = trim((string) ($_POST['valid_from'] ?? ''));
    $validToInput = trim((string) ($_POST['valid_to'] ?? ''));

    $labels = [];
    foreach ($availableLocales as $locale) {
        $labels[$locale] = trim((string) ($_POST['label'][$locale] ?? ''));
    }
    $helps = [];
    foreach ($availableLocales as $locale) {
        $helps[$locale] = trim((string) ($_POST['help'][$locale] ?? ''));
    }

    $enumValuesInput = trim((string) ($_POST['enum_values'] ?? ''));
    $enumValues = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $enumValuesInput) ?: []), static fn ($value) => $value !== ''));

    $organizationId = null;
    if ($scope === 'organization') {
        $organizationId = (int) ($_POST['organization_id'] ?? 0) ?: null;
    }
    $tournamentId = null;
    if ($scope === 'tournament') {
        $tournamentId = (int) ($_POST['tournament_id'] ?? 0) ?: null;
    }

    $validFrom = custom_fields_normalize_datetime($validFromInput);
    $validTo = custom_fields_normalize_datetime($validToInput);

    $errors = [];
    if (!isset($entityOptions[$entity])) {
        $errors[] = t('custom_fields.validation.entity_required');
    }
    if ($fieldKey === '' || !preg_match('/^[A-Za-z0-9_.-]+$/', $fieldKey)) {
        $errors[] = t('custom_fields.validation.field_key');
    }
    if (!isset($typeOptions[$type])) {
        $errors[] = t('custom_fields.validation.type_required');
    }
    if (!isset($scopeOptions[$scope])) {
        $errors[] = t('custom_fields.validation.scope_required');
    }
    if (!isset($visibilityOptions[$visibility])) {
        $errors[] = t('custom_fields.validation.visibility_required');
    }
    if ($scope === 'organization' && !$organizationId) {
        $errors[] = t('custom_fields.validation.organization_required');
    }
    if ($scope === 'tournament' && !$tournamentId) {
        $errors[] = t('custom_fields.validation.tournament_required');
    }

    $labelDefault = trim($labels[$defaultLocale] ?? '');
    if ($labelDefault === '') {
        $errors[] = t('custom_fields.validation.label_required', ['locale' => strtoupper($defaultLocale)]);
    }

    if ($validFromInput !== '' && $validFrom === null) {
        $errors[] = t('custom_fields.validation.valid_from');
    }

    if ($validToInput !== '' && $validTo === null) {
        $errors[] = t('custom_fields.validation.valid_to');
    }

    if ($regexPattern !== '') {
        set_error_handler(static function () {
        });
        $isValidRegex = @preg_match($regexPattern, '') !== false;
        restore_error_handler();
        if (!$isValidRegex) {
            $errors[] = t('custom_fields.validation.regex_invalid');
        }
    }

    if ($version < 1) {
        $version = 1;
    }

    if ($errors) {
        foreach ($errors as $message) {
            flash('error', $message);
        }
        $redirect = 'custom_fields.php';
        if ($action === 'update' && $definitionId > 0) {
            $redirect .= '?edit=' . $definitionId;
        }
        header('Location: ' . $redirect);
        exit;
    }

    $payload = [
        'entity' => $entity,
        'field_key' => $fieldKey,
        'label' => array_filter($labels, static fn ($value) => $value !== ''),
        'help' => array_filter($helps, static fn ($value) => $value !== ''),
        'type' => $type,
        'required' => isset($_POST['required']),
        'is_unique' => isset($_POST['is_unique']),
        'is_sensitive' => isset($_POST['is_sensitive']),
        'regex_pattern' => $regexPattern,
        'min_value' => $minValue,
        'max_value' => $maxValue,
        'enum_values' => $enumValues,
        'visibility' => $visibility,
        'scope' => $scope,
        'organization_id' => $organizationId,
        'tournament_id' => $tournamentId,
        'profile_key' => $profileKey,
        'version' => $version,
        'valid_from' => $validFrom,
        'valid_to' => $validTo,
    ];

    try {
        if ($action === 'update' && $definitionId > 0) {
            $repository->updateDefinition($definitionId, $payload);
            flash('success', t('custom_fields.flash.updated'));
            header('Location: custom_fields.php');
            exit;
        }

        $repository->createDefinition($payload);
        flash('success', t('custom_fields.flash.created'));
        header('Location: custom_fields.php');
        exit;
    } catch (RuntimeException $exception) {
        flash('error', $exception->getMessage());
        $redirect = 'custom_fields.php';
        if ($action === 'update' && $definitionId > 0) {
            $redirect .= '?edit=' . $definitionId;
        }
        header('Location: ' . $redirect);
        exit;
    }
}

render_page('custom_fields.tpl', [
    'titleKey' => 'custom_fields.title',
    'page' => 'custom_fields',
    'definitions' => $definitions,
    'editDefinition' => $editDefinition,
    'entityOptions' => $entityOptions,
    'typeOptions' => $typeOptions,
    'scopeOptions' => $scopeOptions,
    'visibilityOptions' => $visibilityOptions,
    'organizations' => $organizations,
    'tournaments' => $tournaments,
    'locales' => $availableLocales,
    'defaultLocale' => $defaultLocale,
]);

function custom_fields_normalize_datetime(string $value): ?string
{
    if ($value === '') {
        return null;
    }

    $formats = ['Y-m-d\TH:i', 'Y-m-d\TH:i:s'];
    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value);
        if ($date instanceof DateTimeImmutable) {
            return $date->format('Y-m-d H:i:s');
        }
    }

    return null;
}
