<?php
require __DIR__ . '/auth.php';

use App\Core\Csrf;
use App\Sponsors\SponsorRepository;

$user = auth_require('sponsors');
$pdo = app_pdo();
$repository = new SponsorRepository($pdo);

$statusOptions = array_merge(SponsorRepository::STATUSES, ['all']);
$typeOptions = SponsorRepository::TYPES;
$tierOptions = SponsorRepository::TIERS;
$valueTypeOptions = SponsorRepository::VALUE_TYPES;

$statusFilter = $_GET['status'] ?? 'active';
if (!in_array($statusFilter, $statusOptions, true)) {
    $statusFilter = 'active';
}
$eventFilter = isset($_GET['event']) ? (int) $_GET['event'] : null;
if ($eventFilter !== null && $eventFilter <= 0) {
    $eventFilter = null;
}

$redirectStatus = $statusFilter;
$redirectEvent = $eventFilter;

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editSponsor = $editId ? $repository->findSponsor($editId) : null;
$formSponsor = $editSponsor;

$events = db_all('SELECT id, title, start_date, end_date FROM events ORDER BY is_active DESC, start_date DESC, title ASC');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('sponsors.validation.csrf'));
        header('Location: sponsors.php');
        exit;
    }

    require_write_access('sponsors');

    $action = $_POST['action'] ?? 'create';
    $redirectStatus = $_POST['redirect_status'] ?? $statusFilter;
    if (!in_array($redirectStatus, $statusOptions, true)) {
        $redirectStatus = 'active';
    }
    $redirectEvent = isset($_POST['redirect_event']) ? (int) $_POST['redirect_event'] : $eventFilter;
    if ($redirectEvent !== null && $redirectEvent <= 0) {
        $redirectEvent = null;
    }

    if ($action === 'delete') {
        $sponsorId = (int) ($_POST['sponsor_id'] ?? 0);
        if ($sponsorId > 0) {
            $repository->deleteSponsor($sponsorId);
            flash('success', t('sponsors.flash.deleted'));
        }
        $redirectUrl = 'sponsors.php?status=' . rawurlencode($redirectStatus);
        if ($redirectEvent !== null) {
            $redirectUrl .= '&event=' . (int) $redirectEvent;
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    $sponsorId = (int) ($_POST['sponsor_id'] ?? 0);
    $payload = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'display_name' => trim((string) ($_POST['display_name'] ?? '')),
        'type' => trim((string) ($_POST['type'] ?? '')),
        'status' => trim((string) ($_POST['status'] ?? '')),
        'contact_person' => trim((string) ($_POST['contact_person'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'address' => trim((string) ($_POST['address'] ?? '')),
        'tier' => trim((string) ($_POST['tier'] ?? '')),
        'value' => trim((string) ($_POST['value'] ?? '')),
        'value_type' => trim((string) ($_POST['value_type'] ?? '')),
        'contract_start' => trim((string) ($_POST['contract_start'] ?? '')),
        'contract_end' => trim((string) ($_POST['contract_end'] ?? '')),
        'invoice_required' => isset($_POST['invoice_required']) && $_POST['invoice_required'] !== '0',
        'invoice_number' => trim((string) ($_POST['invoice_number'] ?? '')),
        'logo_path' => trim((string) ($_POST['logo_path'] ?? '')),
        'website' => trim((string) ($_POST['website'] ?? '')),
        'description_short' => trim((string) ($_POST['description_short'] ?? '')),
        'description_long' => trim((string) ($_POST['description_long'] ?? '')),
        'priority' => (string) ($_POST['priority'] ?? '0'),
        'color_primary' => trim((string) ($_POST['color_primary'] ?? '')),
        'tagline' => trim((string) ($_POST['tagline'] ?? '')),
        'show_on_website' => isset($_POST['show_on_website']) && $_POST['show_on_website'] !== '0',
        'show_on_signage' => isset($_POST['show_on_signage']) && $_POST['show_on_signage'] !== '0',
        'show_in_program' => isset($_POST['show_in_program']) && $_POST['show_in_program'] !== '0',
        'overlay_template' => trim((string) ($_POST['overlay_template'] ?? '')),
        'display_duration' => trim((string) ($_POST['display_duration'] ?? '')),
        'display_frequency' => trim((string) ($_POST['display_frequency'] ?? '')),
        'linked_event_id' => trim((string) ($_POST['linked_event_id'] ?? '')),
        'contract_file' => trim((string) ($_POST['contract_file'] ?? '')),
        'notes_internal' => trim((string) ($_POST['notes_internal'] ?? '')),
        'last_contacted' => trim((string) ($_POST['last_contacted'] ?? '')),
        'follow_up_date' => trim((string) ($_POST['follow_up_date'] ?? '')),
    ];

    $parseJson = static function (string $value, string $field, array &$errors): array {
        if ($value === '') {
            return [];
        }
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) {
                $errors[] = t('sponsors.validation.json', ['field' => $field]);
                return [];
            }
            return $decoded;
        } catch (Throwable) {
            $errors[] = t('sponsors.validation.json', ['field' => $field]);
            return [];
        }
    };

    $payload['logo_variants'] = $parseJson(trim((string) ($_POST['logo_variants'] ?? '')), t('sponsors.form.fields.logo_variants'), $errors);
    $payload['media_package'] = $parseJson(trim((string) ($_POST['media_package'] ?? '')), t('sponsors.form.fields.media_package'), $errors);
    $payload['documents'] = $parseJson(trim((string) ($_POST['documents'] ?? '')), t('sponsors.form.fields.documents'), $errors);
    $payload['sponsorship_history'] = $parseJson(trim((string) ($_POST['sponsorship_history'] ?? '')), t('sponsors.form.fields.sponsorship_history'), $errors);
    $payload['display_stats'] = $parseJson(trim((string) ($_POST['display_stats'] ?? '')), t('sponsors.form.fields.display_stats'), $errors);

    if ($payload['name'] === '' || $payload['contact_person'] === '' || $payload['email'] === '' || $payload['phone'] === '' || $payload['address'] === '') {
        $errors[] = t('sponsors.validation.required');
    }
    if ($payload['email'] !== '' && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('sponsors.validation.email');
    }
    if ($payload['website'] !== '' && !filter_var($payload['website'], FILTER_VALIDATE_URL)) {
        $errors[] = t('sponsors.validation.website');
    }
    if ($payload['value'] !== '' && !preg_match('/^-?\d+(?:[\.,]\d{1,2})?$/', $payload['value'])) {
        $errors[] = t('sponsors.validation.value');
    }
    if ($payload['color_primary'] !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $payload['color_primary'])) {
        $errors[] = t('sponsors.validation.color');
    }

    if ($payload['display_duration'] !== '' && !ctype_digit($payload['display_duration'])) {
        $errors[] = t('sponsors.validation.duration');
    }
    if ($payload['display_frequency'] !== '' && !ctype_digit($payload['display_frequency'])) {
        $errors[] = t('sponsors.validation.frequency');
    }
    if ($payload['linked_event_id'] !== '' && !ctype_digit($payload['linked_event_id'])) {
        $errors[] = t('sponsors.validation.event');
    }

    if ($payload['value'] === '') {
        $payload['value'] = null;
    }

    if ($errors) {
        foreach ($errors as $error) {
            flash('error', $error);
        }
        $formSponsor = array_merge($payload, [
            'id' => $sponsorId ?: null,
            'invoice_required' => $payload['invoice_required'],
            'show_on_website' => $payload['show_on_website'],
            'show_on_signage' => $payload['show_on_signage'],
            'show_in_program' => $payload['show_in_program'],
        ]);
    } else {
        if ($payload['linked_event_id'] === '') {
            $payload['linked_event_id'] = null;
        }
        if ($payload['display_duration'] === '') {
            $payload['display_duration'] = null;
        }
        if ($payload['display_frequency'] === '') {
            $payload['display_frequency'] = null;
        }

        if ($action === 'update' && $sponsorId > 0) {
            $repository->updateSponsor($sponsorId, $payload);
            flash('success', t('sponsors.flash.updated'));
            $redirectUrl = 'sponsors.php?status=' . rawurlencode($redirectStatus);
            if ($redirectEvent !== null) {
                $redirectUrl .= '&event=' . (int) $redirectEvent;
            }
            header('Location: ' . $redirectUrl . '&edit=' . $sponsorId);
            exit;
        }

        $newId = $repository->createSponsor($payload);
        flash('success', t('sponsors.flash.created'));
        $redirectUrl = 'sponsors.php?status=' . rawurlencode($redirectStatus);
        if ($redirectEvent !== null) {
            $redirectUrl .= '&event=' . (int) $redirectEvent;
        }
        header('Location: ' . $redirectUrl . '&edit=' . $newId);
        exit;
    }
}

$listStatus = $statusFilter === 'all' ? null : $statusFilter;
$sponsors = $repository->listSponsors($listStatus, $eventFilter);

render_page('sponsors.tpl', [
    'titleKey' => 'sponsors.title',
    'page' => 'sponsors',
    'sponsors' => $sponsors,
    'formSponsor' => $formSponsor,
    'isEditing' => $formSponsor !== null && !empty($formSponsor['id']),
    'statusOptions' => SponsorRepository::STATUSES,
    'filterStatuses' => $statusOptions,
    'statusFilter' => $statusFilter,
    'typeOptions' => $typeOptions,
    'tierOptions' => $tierOptions,
    'valueTypeOptions' => $valueTypeOptions,
    'events' => $events,
    'eventFilter' => $eventFilter,
    'redirectStatus' => $redirectStatus,
    'redirectEvent' => $redirectEvent,
]);
