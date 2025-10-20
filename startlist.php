<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/audit.php';
require_once __DIR__ . '/app/helpers/startlist.php';

use App\CustomFields\CustomFieldManager;
use App\CustomFields\CustomFieldRepository;

$user = auth_require('startlist');
$isAdmin = auth_is_admin($user);
$activeEvent = event_active();
$classesSql = 'SELECT c.id, c.label, c.event_id, e.title, c.start_time FROM classes c JOIN events e ON e.id = c.event_id';
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
    render_page('startlist.tpl', [
        'title' => t('startlist.title'),
        'page' => 'startlist',
        'classes' => [],
        'selectedClass' => null,
        'startlist' => [],
        'conflicts' => [],
        'extraScripts' => ['public/assets/js/entity-info.js'],
    ]);
    exit;
}

$classId = (int) ($_GET['class_id'] ?? $classes[0]['id']);
$selectedClass = $classId ? db_first('SELECT c.*, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]) : null;
if (!$selectedClass || !event_accessible($user, (int) $selectedClass['event_id'])) {
    $classId = (int) $classes[0]['id'];
    $selectedClass = db_first('SELECT c.*, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]);
    if (!$selectedClass || !event_accessible($user, (int) $selectedClass['event_id'])) {
        flash('error', t('startlist.validation.forbidden_event'));
        render_page('startlist.tpl', [
            'title' => t('startlist.title'),
            'page' => 'startlist',
            'classes' => [],
            'selectedClass' => null,
            'startlist' => [],
            'conflicts' => [],
            'extraScripts' => ['public/assets/js/entity-info.js'],
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

$startNumberContext = [
    'eventId' => (int) $selectedClass['event_id'],
    'classId' => $classId,
    'date' => $selectedClass['start_time'] ? substr($selectedClass['start_time'], 0, 10) : null,
    'user' => $user,
];
$startNumberRule = getStartNumberRule($startNumberContext);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('startlist.validation.csrf_invalid'));
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    require_write_access('startlist');

    $action = $_POST['action'] ?? '';

    if ($action === 'update_number') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $rawInput = trim((string) ($_POST['start_number_raw'] ?? ''));

        if ($itemId <= 0) {
            flash('error', t('startlist.validation.item_not_found'));
            header('Location: startlist.php?class_id=' . $classId);
            exit;
        }

        $item = db_first('SELECT si.*, e.department FROM startlist_items si JOIN entries e ON e.id = si.entry_id WHERE si.id = :id AND si.class_id = :class_id', [
            'id' => $itemId,
            'class_id' => $classId,
        ]);

        if (!$item) {
            flash('error', t('startlist.validation.item_not_found'));
            header('Location: startlist.php?class_id=' . $classId);
            exit;
        }

        if (!empty($item['start_number_locked_at'])) {
            flash('error', t('startlist.flash.number_locked'));
            header('Location: startlist.php?class_id=' . $classId);
            exit;
        }

        if ($rawInput === '') {
            flash('error', t('startlist.validation.start_number_required'));
            header('Location: startlist.php?class_id=' . $classId);
            exit;
        }

        if (!preg_match('/^\d+$/', $rawInput)) {
            flash('error', t('startlist.validation.start_number_invalid'));
            header('Location: startlist.php?class_id=' . $classId);
            exit;
        }

        $rawNumber = (int) $rawInput;
        if ($rawNumber <= 0) {
            flash('error', t('startlist.validation.start_number_invalid'));
            header('Location: startlist.php?class_id=' . $classId);
            exit;
        }

        try {
            overrideStartNumber($startNumberContext, [
                'entry_id' => (int) $item['entry_id'],
                'startlist_id' => (int) $item['id'],
                'department' => $item['department'] ?? null,
                'assignment_id' => (int) ($item['start_number_assignment_id'] ?? 0),
            ], $rawNumber);
            flash('success', t('startlist.flash.number_updated'));
        } catch (\RuntimeException $exception) {
            $message = $exception->getMessage();
            flash('error', $message !== '' ? $message : t('startlist.flash.number_failed'));
        }

        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'generate') {
        $entries = db_all('SELECT e.id, e.department, pr.display_name AS rider, h.name AS horse, profile.club_id FROM entries e JOIN parties pr ON pr.id = e.party_id LEFT JOIN person_profiles profile ON profile.party_id = pr.id JOIN horses h ON h.id = e.horse_id WHERE e.class_id = :class_id AND e.status IN ("open", "paid")', ['class_id' => $classId]);
        if (!$entries) {
            flash('error', t('startlist.flash.no_entries'));
            header('Location: startlist.php?class_id=' . $classId);
            exit;
        }

        db_execute('DELETE FROM startlist_items WHERE class_id = :class_id', ['class_id' => $classId]);

        $departmentGroups = [];
        $processedDepartments = [];
        $unitSequence = [];
        $singles = [];
        foreach ($entries as &$entry) {
            $entry['department'] = isset($entry['department']) ? trim((string) $entry['department']) : '';
            if ($entry['department'] === '') {
                $singles[] = $entry;
                $unitSequence[] = ['type' => 'single'];
                continue;
            }
            $deptKey = startlist_normalize_department($entry['department']);
            if (!isset($departmentGroups[$deptKey])) {
                $departmentGroups[$deptKey] = [];
            }
            $departmentGroups[$deptKey][] = $entry;
            if (!isset($processedDepartments[$deptKey])) {
                $unitSequence[] = ['type' => 'department', 'key' => $deptKey];
                $processedDepartments[$deptKey] = true;
            }
        }
        unset($entry);

        $rotatedSingles = [];
        if ($singles) {
            $groupedSingles = [];
            foreach ($singles as $single) {
                $clubKey = (string) ($single['club_id'] ?? '0');
                $groupedSingles[$clubKey][] = $single;
            }
            ksort($groupedSingles);
            while ($groupedSingles) {
                foreach (array_keys($groupedSingles) as $club) {
                    $item = array_shift($groupedSingles[$club]);
                    if ($item) {
                        $rotatedSingles[] = $item;
                    }
                    if (!$groupedSingles[$club]) {
                        unset($groupedSingles[$club]);
                    }
                }
            }
        }

        $ordered = [];
        $singleIndex = 0;
        $departmentOrderCounters = [];
        foreach ($unitSequence as $unit) {
            if ($unit['type'] === 'department') {
                $key = $unit['key'];
                foreach ($departmentGroups[$key] as $groupEntry) {
                    $ordered[] = $groupEntry;
                }
                continue;
            }
            if (isset($rotatedSingles[$singleIndex])) {
                $ordered[] = $rotatedSingles[$singleIndex];
            }
            $singleIndex++;
        }

        $current = $selectedClass['start_time'] ? new \DateTimeImmutable($selectedClass['start_time']) : new \DateTimeImmutable('today 09:00');
        $lastDepartmentKey = null;
        $incrementCount = 0;
        foreach ($ordered as $position => $entry) {
            $departmentKey = startlist_normalize_department($entry['department'] ?? '');
            $departmentOrder = null;
            if ($departmentKey !== '') {
                if (!isset($departmentOrderCounters[$departmentKey])) {
                    $departmentOrderCounters[$departmentKey] = 1;
                } else {
                    $departmentOrderCounters[$departmentKey]++;
                }
                $departmentOrder = $departmentOrderCounters[$departmentKey];
            }
            if ($position > 0) {
                $shouldIncrement = true;
                if ($departmentKey !== '' && $departmentKey === $lastDepartmentKey) {
                    $shouldIncrement = false;
                }
                if ($shouldIncrement) {
                    $incrementCount++;
                    $current = $current->add(new \DateInterval('PT6M'));
                    if ($incrementCount % 5 === 0) {
                        $current = $current->add(new \DateInterval('PT5M'));
                    }
                }
            }
            db_execute(
                'INSERT INTO startlist_items (class_id, entry_id, position, planned_start, state, note, department_order, created_at, updated_at) VALUES (:class_id, :entry_id, :position, :planned_start, :state, :note, :department_order, :created, :updated)',
                [
                    'class_id' => $classId,
                    'entry_id' => $entry['id'],
                    'position' => $position + 1,
                    'planned_start' => $current->format('c'),
                    'state' => 'scheduled',
                    'note' => null,
                    'department_order' => $departmentOrder,
                    'created' => (new \DateTimeImmutable())->format('c'),
                    'updated' => (new \DateTimeImmutable())->format('c'),
                ]
            );
            $itemId = (int) $pdo->lastInsertId();
            if (in_array($startNumberRule['allocation']['time'] ?? 'on_startlist', ['on_entry', 'on_startlist'], true)) {
                assignStartNumber($startNumberContext, [
                    'entry_id' => (int) $entry['id'],
                    'startlist_id' => $itemId,
                    'department' => $entry['department'] !== '' ? $entry['department'] : null,
                ]);
            }
            audit_log('startlist_items', $itemId, 'generated', null, [
                'position' => $position + 1,
            ]);
            $lastDepartmentKey = $departmentKey !== '' ? $departmentKey : null;
        }

        flash('success', t('startlist.flash.generated'));
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'toggle_state') {
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
            $shouldWithdraw = false;
            foreach ($groupMembers as $member) {
                if (($member['state'] ?? 'scheduled') !== 'withdrawn') {
                    $shouldWithdraw = true;
                    break;
                }
            }
            $newState = $shouldWithdraw ? 'withdrawn' : 'scheduled';
            $timestamp = (new \DateTimeImmutable())->format('c');
            foreach ($groupMembers as $member) {
                $memberId = (int) $member['id'];
                db_execute('UPDATE startlist_items SET state = :state, updated_at = :updated WHERE id = :id', [
                    'state' => $newState,
                    'updated' => $timestamp,
                    'id' => $memberId,
                ]);
                audit_log('startlist_items', $memberId, 'state_change', $member, ['state' => $newState]);
                if ($newState === 'withdrawn' && !empty($member['start_number_assignment_id'])) {
                    releaseStartNumber([
                        'id' => (int) $member['start_number_assignment_id'],
                        'entry_id' => (int) $member['entry_id'],
                        'startlist_id' => $memberId,
                    ], 'scratch');
                }
                if ($newState === 'scheduled' && in_array($startNumberRule['allocation']['time'] ?? 'on_startlist', ['on_entry', 'on_startlist'], true)) {
                    assignStartNumber($startNumberContext, [
                        'entry_id' => (int) $member['entry_id'],
                        'startlist_id' => $memberId,
                        'department' => $member['department'] ?? null,
                    ]);
                }
            }
            flash('success', t('startlist.flash.status_updated'));
        }
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'update_time') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $time = trim((string) ($_POST['planned_start'] ?? ''));
        $note = trim((string) ($_POST['note'] ?? ''));
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
                db_execute('UPDATE startlist_items SET planned_start = :start, note = :note, updated_at = :updated WHERE id = :id', [
                    'start' => $time !== '' ? $time : null,
                    'note' => $note !== '' ? $note : null,
                    'updated' => $timestamp,
                    'id' => $memberId,
                ]);
                $after = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $memberId]);
                audit_log('startlist_items', $memberId, 'time_update', $member, $after);
            }
            flash('success', t('startlist.flash.time_updated'));
        }
        header('Location: startlist.php?class_id=' . $classId);
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
            flash('success', t('startlist.flash.item_removed'));
        }
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'move') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $direction = $_POST['direction'] ?? 'up';
        $item = db_first('SELECT si.*, e.department FROM startlist_items si JOIN entries e ON e.id = si.entry_id WHERE si.id = :id', ['id' => $itemId]);
        if ($item) {
            if ($isGroupClass) {
                $classItems = db_all('SELECT si.*, e.department FROM startlist_items si JOIN entries e ON e.id = si.entry_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
                $groups = startlist_group_entries($classItems);
                $currentIndex = null;
                foreach ($groups as $idx => $group) {
                    foreach ($group['members'] as $member) {
                        if ((int) $member['id'] === $itemId) {
                            $currentIndex = $idx;
                            break 2;
                        }
                    }
                }
                if ($currentIndex !== null) {
                    $swapIndex = null;
                    if ($direction === 'up' && $currentIndex > 0) {
                        $swapIndex = $currentIndex - 1;
                    } elseif ($direction === 'down' && $currentIndex < count($groups) - 1) {
                        $swapIndex = $currentIndex + 1;
                    }
                    if ($swapIndex !== null) {
                        $temp = $groups[$swapIndex];
                        $groups[$swapIndex] = $groups[$currentIndex];
                        $groups[$currentIndex] = $temp;
                        $updates = startlist_resequence_positions($groups);
                        if ($updates) {
                            $timestamp = (new \DateTimeImmutable())->format('c');
                            $positionMap = [];
                            foreach ($updates as $update) {
                                $positionMap[$update['id']] = $update['position'];
                                db_execute('UPDATE startlist_items SET position = :pos, updated_at = :updated WHERE id = :id', [
                                    'pos' => $update['position'],
                                    'updated' => $timestamp,
                                    'id' => $update['id'],
                                ]);
                            }
                            foreach ($groups as $group) {
                                foreach ($group['members'] as $member) {
                                    $memberId = (int) $member['id'];
                                    if (isset($positionMap[$memberId])) {
                                        audit_log('startlist_items', $memberId, 'reorder', $member, ['position' => $positionMap[$memberId]]);
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $newPosition = max(1, (int) $item['position'] + ($direction === 'up' ? -1 : 1));
                $swap = db_first('SELECT * FROM startlist_items WHERE class_id = :class_id AND position = :position', [
                    'class_id' => $classId,
                    'position' => $newPosition,
                ]);
                if ($swap) {
                    db_execute('UPDATE startlist_items SET position = :pos WHERE id = :id', [
                        'pos' => $item['position'],
                        'id' => $swap['id'],
                    ]);
                }
                db_execute('UPDATE startlist_items SET position = :pos, updated_at = :updated WHERE id = :id', [
                    'pos' => $newPosition,
                    'updated' => (new \DateTimeImmutable())->format('c'),
                    'id' => $itemId,
                ]);
                audit_log('startlist_items', $itemId, 'reorder', $item, ['position' => $newPosition]);
            }
        }
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }

    if ($action === 'reassign_number') {
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
            $assignments = [];
            $locked = false;
            foreach ($groupMembers as $member) {
                $assignment = null;
                if (!empty($member['start_number_assignment_id'])) {
                    $assignment = db_first('SELECT * FROM start_number_assignments WHERE id = :id', ['id' => (int) $member['start_number_assignment_id']]);
                    if ($assignment && !empty($assignment['locked_at'])) {
                        $locked = true;
                        break;
                    }
                }
                $assignments[(int) $member['id']] = $assignment;
            }
            if ($locked) {
                flash('error', t('startlist.flash.number_locked'));
            } else {
                $shouldAssign = in_array($startNumberRule['allocation']['time'] ?? 'on_startlist', ['on_entry', 'on_startlist'], true);
                foreach ($groupMembers as $member) {
                    $memberId = (int) $member['id'];
                    $assignment = $assignments[$memberId] ?? null;
                    if ($assignment) {
                        releaseStartNumber([
                            'id' => (int) $assignment['id'],
                            'entry_id' => (int) $member['entry_id'],
                            'startlist_id' => $memberId,
                        ], 'manual_reassign');
                    }
                    if ($shouldAssign) {
                        assignStartNumber($startNumberContext, [
                            'entry_id' => (int) $member['entry_id'],
                            'startlist_id' => $memberId,
                            'department' => $member['department'] ?? null,
                        ]);
                    }
                }
                if ($shouldAssign) {
                    flash('success', t('startlist.flash.number_reassigned'));
                } else {
                    flash('info', t('startlist.flash.number_gate'));
                }
            }
        }
        header('Location: startlist.php?class_id=' . $classId);
        exit;
    }
}

$startlist = db_all('SELECT si.*, e.status, COALESCE(cd.label, e.department) AS department, e.department_id, cd.position AS department_position, pr.id AS rider_id, pr.display_name AS rider, pr.email AS rider_email, pr.phone AS rider_phone, pr.date_of_birth AS rider_date_of_birth, pr.nationality AS rider_nationality, pr.status AS rider_status, profile.club_id AS rider_club_id, c.name AS rider_club_name, h.name AS horse, h.id AS horse_id, h.life_number AS horse_life_number, h.microchip AS horse_microchip, h.sex AS horse_sex, h.birth_year AS horse_birth_year, h.documents_ok AS horse_documents_ok, h.notes AS horse_notes, owner.display_name AS horse_owner_name FROM startlist_items si JOIN entries e ON e.id = si.entry_id LEFT JOIN class_departments cd ON cd.id = e.department_id JOIN parties pr ON pr.id = e.party_id LEFT JOIN person_profiles profile ON profile.party_id = pr.id LEFT JOIN clubs c ON c.id = profile.club_id JOIN horses h ON h.id = e.horse_id LEFT JOIN parties owner ON owner.id = h.owner_party_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);

$riderIds = array_values(array_filter(array_map(static fn (array $item): int => (int) ($item['rider_id'] ?? 0), $startlist), static fn (int $id): bool => $id > 0));
$horseIds = array_values(array_filter(array_map(static fn (array $item): int => (int) ($item['horse_id'] ?? 0), $startlist), static fn (int $id): bool => $id > 0));
$riderCustomValues = $customFieldRepository->valuesForMany('person', $riderIds);
$horseCustomValues = $customFieldRepository->valuesForMany('horse', $horseIds);
foreach ($startlist as &$item) {
    $riderId = (int) ($item['rider_id'] ?? 0);
    $horseId = (int) ($item['horse_id'] ?? 0);
    $item['rider_custom_fields'] = $riderCustomFieldManager->entityInfoFields($riderCustomValues[$riderId] ?? []);
    $item['horse_custom_fields'] = $horseCustomFieldManager->entityInfoFields($horseCustomValues[$horseId] ?? []);
}
unset($item);

$groupedStartlist = $isGroupClass ? startlist_group_entries($startlist) : null;

$conflicts = [];
if (!$isGroupClass) {
    foreach ($startlist as $index => $item) {
        if (!isset($startlist[$index - 1])) {
            continue;
        }
        $previous = $startlist[$index - 1];
        if ($previous['horse_id'] === $item['horse_id'] && abs($item['position'] - $previous['position']) < 3) {
            $conflicts[] = [$previous, $item];
        }
    }
}

$hasDepartments = false;
if ($isGroupClass) {
    foreach ($groupedStartlist ?? [] as $group) {
        if (!empty($group['department'])) {
            $hasDepartments = true;
            break;
        }
    }
} else {
    foreach ($startlist as $item) {
        if (!empty($item['department']) && trim((string) $item['department']) !== '') {
            $hasDepartments = true;
            break;
        }
    }
}

$departmentBoard = null;
if ($isGroupClass) {
    $classDepartments = db_all('SELECT id, label, position FROM class_departments WHERE class_id = :class_id ORDER BY position', ['class_id' => $classId]);
    $membersByDepartment = [];
    $membersByDepartment[0] = [];
    foreach ($startlist as $item) {
        $deptId = (int) ($item['department_id'] ?? 0);
        if ($deptId <= 0) {
            $membersByDepartment[0][] = $item;
            continue;
        }
        if (!isset($membersByDepartment[$deptId])) {
            $membersByDepartment[$deptId] = [];
        }
        $membersByDepartment[$deptId][] = $item;
    }
    foreach ($membersByDepartment as &$members) {
        usort($members, static function (array $left, array $right): int {
            $leftOrder = $left['department_order'] ?? null;
            $rightOrder = $right['department_order'] ?? null;
            if ($leftOrder !== null && $rightOrder !== null && $leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }
            if ($leftOrder !== null && $rightOrder === null) {
                return -1;
            }
            if ($leftOrder === null && $rightOrder !== null) {
                return 1;
            }
            return ((int) ($left['position'] ?? 0)) <=> ((int) ($right['position'] ?? 0));
        });
    }
    unset($members);
    $departmentBoard = [
        'departments' => [],
        'unassigned' => $membersByDepartment[0] ?? [],
    ];
    foreach ($classDepartments as $departmentRow) {
        $deptId = (int) ($departmentRow['id'] ?? 0);
        $departmentBoard['departments'][] = [
            'id' => $deptId,
            'label' => $departmentRow['label'],
            'position' => (int) ($departmentRow['position'] ?? 0),
            'members' => $membersByDepartment[$deptId] ?? [],
        ];
    }
    foreach ($membersByDepartment as $deptId => $members) {
        if ($deptId === 0) {
            continue;
        }
        $exists = false;
        foreach ($departmentBoard['departments'] as $departmentEntry) {
            if ((int) $departmentEntry['id'] === $deptId) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $departmentBoard['departments'][] = [
                'id' => $deptId,
                'label' => $members[0]['department'] ?? '',
                'position' => count($departmentBoard['departments']) + 1,
                'members' => $members,
                'missing' => true,
            ];
        }
    }
}

$extraScripts = [
    'public/assets/js/entity-info.js',
    'public/assets/js/startlist-reorder.js',
    'public/assets/js/startlist-numbers.js',
];
if ($isGroupClass) {
    $extraScripts[] = 'public/assets/js/startlist-departments.js';
}

render_page('startlist.tpl', [
    'title' => t('startlist.title'),
    'page' => 'startlist',
    'classes' => $classes,
    'selectedClass' => $selectedClass,
    'startlist' => $startlist,
    'groupedStartlist' => $groupedStartlist,
    'conflicts' => $conflicts,
    'startNumberRule' => $startNumberRule,
    'hasDepartments' => $hasDepartments,
    'isGroupClass' => $isGroupClass,
    'extraScripts' => $extraScripts,
    'departmentBoard' => $departmentBoard,
]);
