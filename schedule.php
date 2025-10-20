<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/audit.php';
require_once __DIR__ . '/app/helpers/startlist.php';

use App\CustomFields\CustomFieldManager;
use App\CustomFields\CustomFieldRepository;

$user = auth_require('schedule');
$isAdmin = auth_is_admin($user);
$activeEvent = event_active();
$classesSql = 'SELECT c.id, c.label, c.event_id, e.title FROM classes c JOIN events e ON e.id = c.event_id';
if (!$isAdmin) {
    if (!$activeEvent) {
        $classes = [];
    } else {
        $classes = db_all($classesSql . ' WHERE e.id = :event_id ORDER BY e.title, c.label', ['event_id' => (int) $activeEvent['id']]);
    }
} else {
    $classes = db_all($classesSql . ' ORDER BY e.title, c.label');
}
if (!$classes) {
    render_page('schedule.tpl', [
        'title' => t('schedule.title'),
        'page' => 'schedule',
        'classes' => [],
        'selectedClass' => null,
        'items' => [],
        'shifts' => [],
        'extraScripts' => ['public/assets/js/entity-info.js', 'public/assets/js/schedule.js'],
    ]);
    exit;
}

$classId = (int) ($_GET['class_id'] ?? $classes[0]['id']);
$selectedClass = $classId ? db_first('SELECT c.*, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]) : null;
if (!$selectedClass || !event_accessible($user, (int) $selectedClass['event_id'])) {
    $classId = (int) $classes[0]['id'];
    $selectedClass = db_first('SELECT c.*, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]);
    if (!$selectedClass || !event_accessible($user, (int) $selectedClass['event_id'])) {
        flash('error', t('schedule.validation.forbidden_event'));
        render_page('schedule.tpl', [
            'title' => t('schedule.title'),
            'page' => 'schedule',
            'classes' => [],
            'selectedClass' => null,
            'items' => [],
            'shifts' => [],
            'extraScripts' => ['public/assets/js/entity-info.js', 'public/assets/js/schedule.js'],
        ]);
        exit;
    }
}

$isGroupClass = !empty($selectedClass['is_group']);

$pdo = app_pdo();
$customFieldRepository = new CustomFieldRepository($pdo);
$primaryOrganizationId = instance_primary_organization_id();
$customFieldContext = [];
if (!empty($selectedClass['event_id'])) {
    $customFieldContext['tournament_id'] = (int) $selectedClass['event_id'];
}
if ($primaryOrganizationId !== null) {
    $customFieldContext['organization_id'] = $primaryOrganizationId;
}
$riderCustomFieldManager = new CustomFieldManager($customFieldRepository, 'person', $customFieldContext);
$horseCustomFieldManager = new CustomFieldManager($customFieldRepository, 'horse', $customFieldContext);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('schedule.validation.csrf_invalid'));
        header('Location: schedule.php?class_id=' . $classId);
        exit;
    }

    require_write_access('schedule');

    $action = $_POST['action'] ?? '';
    if ($action === 'shift') {
        $minutes = (int) ($_POST['minutes'] ?? 0);
        if ($minutes !== 0) {
            $items = db_all('SELECT id, planned_start FROM startlist_items WHERE class_id = :class_id ORDER BY position', ['class_id' => $classId]);
            foreach ($items as $item) {
                if (!$item['planned_start']) {
                    continue;
                }
                $before = $item;
                $start = new \DateTimeImmutable($item['planned_start']);
                $interval = new \DateInterval('PT' . abs($minutes) . 'M');
                if ($minutes < 0) {
                    $interval->invert = 1;
                }
                $new = $start->add($interval);
                db_execute('UPDATE startlist_items SET planned_start = :start, updated_at = :updated WHERE id = :id', [
                    'start' => $new->format('c'),
                    'updated' => (new \DateTimeImmutable())->format('c'),
                    'id' => $item['id'],
                ]);
                audit_log('startlist_items', (int) $item['id'], 'time_shift', $before, ['planned_start' => $new->format('c')]);
            }
            db_execute('INSERT INTO schedule_shifts (class_id, shift_minutes, created_at) VALUES (:class_id, :shift, :created)', [
                'class_id' => $classId,
                'shift' => $minutes,
                'created' => (new \DateTimeImmutable())->format('c'),
            ]);
            db_execute('INSERT INTO notifications (type, payload, created_at) VALUES (:type, :payload, :created)', [
                'type' => 'schedule_shift',
                'payload' => json_encode([
                    'class_id' => $classId,
                    'message' => t('schedule.notifications.shift', [
                        'minutes' => ($minutes > 0 ? '+' : '') . $minutes,
                        'class' => $selectedClass['label'] ?? '',
                    ]),
                ], JSON_THROW_ON_ERROR),
                'created' => (new \DateTimeImmutable())->format('c'),
            ]);
            flash('success', t('schedule.flash.shifted'));
        }
        header('Location: schedule.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'update_item') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $time = trim((string) ($_POST['planned_start'] ?? ''));
        $item = db_first('SELECT si.*, e.department FROM startlist_items si JOIN entries e ON e.id = si.entry_id WHERE si.id = :id', ['id' => $itemId]);
        if ($item) {
            $groupMembers = [$item];
            if ($isGroupClass) {
                $classItems = db_all('SELECT si.*, e.department FROM startlist_items si JOIN entries e ON e.id = si.entry_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
                $groups = startlist_group_entries($classItems);
                $group = startlist_find_group_for_item($groups, $itemId);
                if ($group) {
                    $groupMembers = $group['members'];
                }
            }
            $timestamp = (new \DateTimeImmutable())->format('c');
            foreach ($groupMembers as $member) {
                $memberId = (int) $member['id'];
                db_execute('UPDATE startlist_items SET planned_start = :start, updated_at = :updated WHERE id = :id', [
                    'start' => $time !== '' ? $time : null,
                    'updated' => $timestamp,
                    'id' => $memberId,
                ]);
                $after = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $memberId]);
                audit_log('startlist_items', $memberId, 'time_update', $member, $after);
            }
            flash('success', t('schedule.flash.slot_updated'));
        }
        header('Location: schedule.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'delete_item') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = db_first('SELECT si.*, e.department FROM startlist_items si JOIN entries e ON e.id = si.entry_id WHERE si.id = :id', ['id' => $itemId]);
        if ($item) {
            $groupMembers = [$item];
            if ($isGroupClass) {
                $classItems = db_all('SELECT si.*, e.department FROM startlist_items si JOIN entries e ON e.id = si.entry_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
                $groups = startlist_group_entries($classItems);
                $group = startlist_find_group_for_item($groups, $itemId);
                if ($group) {
                    $groupMembers = $group['members'];
                }
            }
            foreach ($groupMembers as $member) {
                $memberId = (int) $member['id'];
                if (!empty($member['start_number_assignment_id'])) {
                    releaseStartNumber([
                        'id' => (int) $member['start_number_assignment_id'],
                        'entry_id' => (int) $member['entry_id'],
                        'startlist_id' => $memberId,
                    ], 'withdraw');
                }
                db_execute('DELETE FROM results WHERE startlist_id = :id', ['id' => $memberId]);
                db_execute('DELETE FROM startlist_items WHERE id = :id', ['id' => $memberId]);
                audit_log('startlist_items', $memberId, 'delete', $member, null);
            }
            flash('success', t('schedule.flash.slot_deleted'));
        }
        header('Location: schedule.php?class_id=' . $classId);
        exit;
    }
}

$items = db_all('SELECT si.id, si.position, si.state, si.planned_start, si.start_number_display, si.start_number_locked_at, e.department, pr.id AS rider_id, pr.display_name AS rider, pr.email AS rider_email, pr.phone AS rider_phone, pr.date_of_birth AS rider_date_of_birth, pr.nationality AS rider_nationality, profile.club_id AS rider_club_id, c.name AS rider_club_name, h.id AS horse_id, h.name AS horse, h.life_number AS horse_life_number, h.microchip AS horse_microchip, h.sex AS horse_sex, h.birth_year AS horse_birth_year, h.documents_ok AS horse_documents_ok, h.notes AS horse_notes, owner.display_name AS horse_owner_name FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN parties pr ON pr.id = e.party_id LEFT JOIN person_profiles profile ON profile.party_id = pr.id LEFT JOIN clubs c ON c.id = profile.club_id JOIN horses h ON h.id = e.horse_id LEFT JOIN parties owner ON owner.id = h.owner_party_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
$riderIds = array_values(array_filter(array_map(static fn (array $item): int => (int) ($item['rider_id'] ?? 0), $items), static fn (int $id): bool => $id > 0));
$horseIds = array_values(array_filter(array_map(static fn (array $item): int => (int) ($item['horse_id'] ?? 0), $items), static fn (int $id): bool => $id > 0));
$riderCustomValues = $customFieldRepository->valuesForMany('person', $riderIds);
$horseCustomValues = $customFieldRepository->valuesForMany('horse', $horseIds);
foreach ($items as &$item) {
    $riderId = (int) ($item['rider_id'] ?? 0);
    $horseId = (int) ($item['horse_id'] ?? 0);
    $item['rider_custom_fields'] = $riderCustomFieldManager->entityInfoFields($riderCustomValues[$riderId] ?? []);
    $item['horse_custom_fields'] = $horseCustomFieldManager->entityInfoFields($horseCustomValues[$horseId] ?? []);
}
unset($item);
$groupedItems = $isGroupClass ? startlist_group_entries($items) : null;
$shifts = db_all('SELECT shift_minutes, created_at FROM schedule_shifts WHERE class_id = :class_id ORDER BY id DESC LIMIT 10', ['class_id' => $classId]);

render_page('schedule.tpl', [
    'title' => t('schedule.title'),
    'page' => 'schedule',
    'classes' => $classes,
    'selectedClass' => $selectedClass,
    'items' => $items,
    'groupedItems' => $groupedItems,
    'isGroupClass' => $isGroupClass,
    'shifts' => $shifts,
    'extraScripts' => ['public/assets/js/entity-info.js', 'public/assets/js/schedule.js'],
]);
