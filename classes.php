<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/app/helpers/start_number_rules.php';
require_once __DIR__ . '/app/helpers/scoring.php';

use App\Scoring\RuleManager;
use App\Setup\Installer;
use DateTimeImmutable;

if (!function_exists('classes_format_event_arena_option')) {
    function classes_format_event_arena_option(array $row): array
    {
        $label = trim((string) ($row['display_name'] ?? ''));
        if ($label === '') {
            $label = trim((string) ($row['arena_name'] ?? ''));
        }
        if ($label === '') {
            $label = t('classes.form.arena_picker.unassigned');
        }

        $type = (string) ($row['arena_type'] ?? 'outdoor');
        if (!in_array($type, ['indoor', 'outdoor'], true)) {
            $type = 'outdoor';
        }
        $typeLabel = t('classes.arena_badge.type.' . $type);

        $surface = trim((string) ($row['temp_surface'] ?? ''));
        if ($surface === '') {
            $surface = trim((string) ($row['arena_surface'] ?? ''));
        }

        $length = isset($row['length_m']) ? (int) $row['length_m'] : 0;
        $width = isset($row['width_m']) ? (int) $row['width_m'] : 0;
        $size = ($length > 0 && $width > 0)
            ? t('classes.arena_badge.size_format', ['width' => $width, 'length' => $length])
            : null;

        $features = [];
        if (!empty($row['covered'])) {
            $features[] = t('classes.arena_badge.feature.covered');
        }
        if (!empty($row['lighting'])) {
            $features[] = t('classes.arena_badge.feature.lighting');
        }
        if (!empty($row['drainage'])) {
            $features[] = t('classes.arena_badge.feature.drainage');
        }
        if (!empty($row['capacity'])) {
            $features[] = t('classes.arena_badge.feature.capacity', ['capacity' => (int) $row['capacity']]);
        }

        $location = trim((string) ($row['location_name'] ?? ''));
        $remarks = trim((string) ($row['remarks'] ?? ''));

        $badgeParts = array_filter(array_merge([$label], array_filter([$size, $surface]), $features));
        $summaryParts = array_filter(array_merge([$typeLabel], array_filter([$size, $surface]), $features));

        if ($location !== '') {
            $summaryParts[] = t('classes.arena_badge.feature.location', ['location' => $location]);
        }

        if ($remarks !== '') {
            $summaryParts[] = t('classes.arena_badge.feature.remarks', ['remarks' => $remarks]);
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'event_id' => (int) ($row['event_id'] ?? 0),
            'arena_id' => (int) ($row['arena_id'] ?? 0),
            'label' => $label,
            'type' => $type,
            'type_label' => $typeLabel,
            'surface' => $surface,
            'size' => $size,
            'features' => $features,
            'location' => $location !== '' ? $location : null,
            'remarks' => $remarks !== '' ? $remarks : null,
            'badge' => implode(' · ', $badgeParts),
            'summary' => implode(' · ', $summaryParts),
            'warmup_arena_id' => isset($row['warmup_arena_id']) ? (int) $row['warmup_arena_id'] : null,
            'blocked_times' => !empty($row['blocked_times']) ? (json_decode((string) $row['blocked_times'], true) ?: []) : [],
        ];
    }
}

if (!function_exists('classes_format_schedule_range')) {
    function classes_format_schedule_range(?string $start, ?string $end): ?string
    {
        if (!$start) {
            return null;
        }

        $startTimestamp = strtotime($start);
        if ($startTimestamp === false) {
            return $start;
        }
        $startFormatted = date('Y-m-d H:i', $startTimestamp);

        if (!$end) {
            return $startFormatted;
        }

        $endTimestamp = strtotime($end);
        if ($endTimestamp === false) {
            return $startFormatted;
        }

        $startDay = date('Y-m-d', $startTimestamp);
        $endDay = date('Y-m-d', $endTimestamp);
        $endFormatted = $startDay === $endDay ? date('H:i', $endTimestamp) : date('Y-m-d H:i', $endTimestamp);

        return $startFormatted . ' – ' . $endFormatted;
    }
}

$user = auth_require('classes');
$isAdmin = auth_is_admin($user);
$classSupportsGroupMode = db_has_column('classes', 'is_group');
$activeEvent = event_active();

$eventsQuery = 'SELECT id, title, is_active FROM events';
if (!$isAdmin) {
    $eventsQuery .= ' WHERE is_active = 1';
}
$eventsQuery .= ' ORDER BY title';
$events = db_all($eventsQuery);
$presets = scoring_rule_presets();
$scoringDesignerDefault = RuleManager::mergeDefaults([]);
$scoringDesignerPresets = [];
foreach ($presets as $key => $preset) {
    $scoringDesignerPresets[$key] = RuleManager::mergeDefaults($preset);
}
$scoringPresetOptions = scoring_rule_preset_options();
$startNumberPresetRules = start_number_rule_preset_rules();
$startNumberPresetOptions = start_number_rule_preset_options();

$accessibleEventIds = array_map(static fn(array $event): int => (int) $event['id'], $events);
$arenaOptionsByEvent = [];
$arenaLookup = [];
$arenaPickerDataJson = '{}';

if ($accessibleEventIds) {
    $placeholders = implode(', ', array_fill(0, count($accessibleEventIds), '?'));
    $eventArenaRows = db_all(
        'SELECT ea.*, a.name AS arena_name, a.type AS arena_type, a.surface AS arena_surface, a.length_m, a.width_m, '
        . 'a.covered, a.lighting, a.drainage, a.capacity, l.name AS location_name '
        . 'FROM event_arenas ea '
        . 'JOIN arenas a ON a.id = ea.arena_id '
        . 'LEFT JOIN locations l ON l.id = a.location_id '
        . 'WHERE ea.event_id IN (' . $placeholders . ') '
        . 'ORDER BY ea.event_id, COALESCE(ea.display_name, a.name)',
        $accessibleEventIds
    );

    foreach ($eventArenaRows as $row) {
        $option = classes_format_event_arena_option($row);
        $eventId = (string) $option['event_id'];
        if (!isset($arenaOptionsByEvent[$eventId])) {
            $arenaOptionsByEvent[$eventId] = [];
        }
        $arenaOptionsByEvent[$eventId][] = $option;
        $arenaLookup[(int) $option['id']] = $option;
    }

    foreach ($arenaOptionsByEvent as $eventId => &$options) {
        usort($options, static fn(array $a, array $b): int => strcmp($a['label'], $b['label']));
    }
    unset($options);

    $arenaPickerPayload = ['events' => $arenaOptionsByEvent];
    try {
        $arenaPickerDataJson = json_encode($arenaPickerPayload, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        $arenaPickerDataJson = '{}';
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editClass = null;
if ($editId) {
    $editClass = db_first('SELECT * FROM classes WHERE id = :id', ['id' => $editId]);
    if ($editClass && !event_accessible($user, (int) $editClass['event_id'])) {
        flash('error', t('classes.validation.forbidden_event'));
        header('Location: classes.php');
        exit;
    }
    if ($editClass) {
        $editClass['event_arena_id'] = isset($editClass['event_arena_id']) ? (int) $editClass['event_arena_id'] : 0;
        $editClass['judges'] = $editClass['judge_assignments'] ? implode(', ', json_decode($editClass['judge_assignments'], true, 512, JSON_THROW_ON_ERROR) ?: []) : '';
        $editClass['rules_text'] = $editClass['rules_json'] ? json_encode(json_decode($editClass['rules_json'], true, 512, JSON_THROW_ON_ERROR), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';
        $editClass['tiebreakers_list'] = $editClass['tiebreaker_json'] ? implode(', ', json_decode($editClass['tiebreaker_json'], true, 512, JSON_THROW_ON_ERROR) ?: []) : '';
        $editClass['start_formatted'] = $editClass['start_time'] ? date('Y-m-d\TH:i', strtotime($editClass['start_time'])) : '';
        $editClass['end_formatted'] = $editClass['end_time'] ? date('Y-m-d\TH:i', strtotime($editClass['end_time'])) : '';
        $editClass['is_group'] = $classSupportsGroupMode ? !empty($editClass['is_group']) : false;
        if (!empty($editClass['start_number_rules'])) {
            $editClass['start_number_rules_text'] = $editClass['start_number_rules'];
        }
        if (!empty($editClass['event_arena_id']) && isset($arenaLookup[$editClass['event_arena_id']])) {
            $editClass['arena_option'] = $arenaLookup[$editClass['event_arena_id']];
        } elseif (!empty($editClass['arena'])) {
            $editClass['legacy_arena'] = $editClass['arena'];
        }
    }
}

$classSimulation = [];
$classSimulationError = null;
$scoringSimulation = [];
$scoringSimulationError = null;
$simulationCount = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('classes.validation.csrf_invalid'));
        header('Location: classes.php');
        exit;
    }

    require_write_access('classes');

    $action = $_POST['action'] ?? 'create';
    if (isset($_POST['simulate'])) {
        $action = 'simulate_start_numbers';
    }
    if (isset($_POST['simulate_scoring'])) {
        $action = 'simulate_scoring';
    }

    if ($action === 'simulate_start_numbers') {
        $classId = (int) ($_POST['class_id'] ?? 0);
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $label = trim((string) ($_POST['label'] ?? ''));
        $eventArenaId = (int) ($_POST['event_arena_id'] ?? 0);
        $arenaLabel = '';
        if ($eventArenaId > 0) {
            $eventArenaRow = db_first(
                'SELECT ea.display_name, ea.event_id, a.name AS arena_name FROM event_arenas ea JOIN arenas a ON a.id = ea.arena_id WHERE ea.id = :id',
                ['id' => $eventArenaId]
            );
            if ($eventArenaRow) {
                $arenaLabel = trim((string) ($eventArenaRow['display_name'] ?? ''));
                if ($arenaLabel === '') {
                    $arenaLabel = trim((string) ($eventArenaRow['arena_name'] ?? ''));
                }
            }
        }
        $start = trim((string) ($_POST['start_time'] ?? ''));
        $end = trim((string) ($_POST['end_time'] ?? ''));
        $maxStarters = trim((string) ($_POST['max_starters'] ?? ''));
        $judgesInput = trim((string) ($_POST['judges'] ?? ''));
        $rulesInput = (string) ($_POST['rules_json'] ?? '');
        $tiebreakersInput = trim((string) ($_POST['tiebreakers'] ?? ''));
        $ruleJson = trim((string) ($_POST['start_number_rules'] ?? ''));

        $editClass = [
            'id' => $classId ?: null,
            'event_id' => $eventId,
            'label' => $label,
            'arena' => $arenaLabel,
            'start_formatted' => $start,
            'end_formatted' => $end,
            'max_starters' => $maxStarters !== '' ? (int) $maxStarters : null,
            'judges' => $judgesInput,
            'rules_text' => $rulesInput,
            'tiebreakers_list' => $tiebreakersInput,
            'start_number_rules_text' => $ruleJson,
            'event_arena_id' => $eventArenaId,
        ];

        if ($eventArenaId > 0 && isset($arenaLookup[$eventArenaId])) {
            $editClass['arena_option'] = $arenaLookup[$eventArenaId];
        }

        if (!$eventId || !event_accessible($user, $eventId)) {
            $classSimulationError = t('classes.validation.select_event');
        } elseif ($ruleJson === '') {
            $classSimulationError = t('classes.validation.rule_json_required');
        } else {
            try {
                $decodedRule = json_decode($ruleJson, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($decodedRule)) {
                    $classSimulationError = t('classes.validation.rule_json_object');
                } else {
                    $context = [
                        'eventId' => $eventId,
                        'user' => $user,
                        'ruleOverride' => $decodedRule,
                        'class' => [
                            'id' => $classId ?: 0,
                            'event_id' => $eventId,
                            'start_number_rules' => $ruleJson,
                        ],
                    ];
                    if ($classId > 0) {
                        $context['classId'] = $classId;
                    }
                    if ($start !== '') {
                        $context['date'] = substr($start, 0, 10);
                    }
                    $classSimulation = simulateStartNumbers($context, 10);
                }
            } catch (\JsonException $e) {
                $classSimulationError = t('classes.validation.rule_json_invalid', ['message' => $e->getMessage()]);
            }
        }
    } elseif ($action === 'simulate_scoring') {
        $classId = (int) ($_POST['class_id'] ?? 0);
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $rulesRaw = (string) ($_POST['rules_json'] ?? '');
        $simulationCount = max(1, min(50, (int) ($_POST['simulation_count'] ?? 10)));
        $count = $simulationCount;
        $resolvedRule = null;
        if ($rulesRaw !== '') {
            try {
                $decoded = json_decode($rulesRaw, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $resolvedRule = $decoded;
                } else {
                    $scoringSimulationError = t('classes.validation.rule_json_invalid_simple');
                }
            } catch (\JsonException $e) {
                $scoringSimulationError = t('classes.validation.rule_json_invalid', ['message' => $e->getMessage()]);
            }
        }
        if (!$resolvedRule && $classId) {
            $existing = db_first('SELECT event_id, rules_json FROM classes WHERE id = :id', ['id' => $classId]);
            if ($existing) {
                $resolvedRule = scoring_rule_decode($existing['rules_json'] ?? '') ?? ($existing['event_id'] ? scoring_rule_for_event((int) $existing['event_id']) : null);
            }
        }
        if (!$resolvedRule && $eventId) {
            $resolvedRule = scoring_rule_for_event($eventId);
        }
        if (!$resolvedRule) {
            $resolvedRule = Installer::dressagePreset();
        }
        if (!$scoringSimulationError) {
            try {
                $scoringSimulation = scoring_simulate($resolvedRule, $count);
            } catch (\Throwable $e) {
                $scoringSimulationError = t('classes.validation.simulation_failed', ['message' => $e->getMessage()]);
            }
        }
        if ($classId && !$editClass) {
            $editClass = db_first('SELECT * FROM classes WHERE id = :id', ['id' => $classId]);
        }
    } else {
        if ($action === 'delete') {
            $classId = (int) ($_POST['class_id'] ?? 0);
            if ($classId) {
                $class = db_first('SELECT event_id FROM classes WHERE id = :id', ['id' => $classId]);
                if (!$class || !event_accessible($user, (int) $class['event_id'])) {
                    flash('error', t('classes.validation.no_permission_event'));
                    header('Location: classes.php');
                    exit;
                }
                db_execute('DELETE FROM results WHERE startlist_id IN (SELECT id FROM startlist_items WHERE class_id = :id)', ['id' => $classId]);
                db_execute('DELETE FROM startlist_items WHERE class_id = :id', ['id' => $classId]);
                db_execute('DELETE FROM entries WHERE class_id = :id', ['id' => $classId]);
                db_execute('DELETE FROM schedule_shifts WHERE class_id = :id', ['id' => $classId]);
                db_execute('DELETE FROM classes WHERE id = :id', ['id' => $classId]);
                flash('success', t('classes.flash.deleted'));
            }
            header('Location: classes.php');
            exit;
        }

        $classId = (int) ($_POST['class_id'] ?? 0);
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $label = trim((string) ($_POST['label'] ?? ''));
        $eventArenaId = (int) ($_POST['event_arena_id'] ?? 0);
        $quickArenaName = trim((string) ($_POST['arena_quick_name'] ?? ''));
        $quickArenaType = (string) ($_POST['arena_quick_type'] ?? 'outdoor');
        $quickArenaSurface = trim((string) ($_POST['arena_quick_surface'] ?? ''));
        $quickArenaLength = (int) ($_POST['arena_quick_length'] ?? 0);
        $quickArenaWidth = (int) ($_POST['arena_quick_width'] ?? 0);
        $quickArenaLocation = trim((string) ($_POST['arena_quick_location'] ?? ''));
        $resolvedEventArena = null;
        $start = trim((string) ($_POST['start_time'] ?? ''));
        $end = trim((string) ($_POST['end_time'] ?? ''));
        $maxStarters = (int) ($_POST['max_starters'] ?? 0) ?: null;
        $judges = array_filter(array_map('trim', explode(',', (string) ($_POST['judges'] ?? ''))));
        $rulesRaw = (string) ($_POST['rules_json'] ?? '');
        $tiebreakers = array_filter(array_map('trim', explode(',', (string) ($_POST['tiebreakers'] ?? ''))));
        $startNumberRuleRaw = trim((string) ($_POST['start_number_rules'] ?? ''));

        if (!$eventId || $label === '') {
            flash('error', t('classes.validation.event_label_required'));
            header('Location: classes.php');
            exit;
        }

        if (!event_accessible($user, $eventId)) {
            flash('error', t('classes.validation.no_permission_event'));
            header('Location: classes.php');
            exit;
        }

        if ($end !== '' && $start !== '') {
            $startTimestamp = strtotime($start);
            $endTimestamp = strtotime($end);
            if ($startTimestamp !== false && $endTimestamp !== false && $endTimestamp <= $startTimestamp) {
                flash('error', t('classes.validation.time_range_invalid'));
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }
        }

        if ($eventArenaId > 0) {
            $resolvedEventArena = db_first(
                'SELECT ea.*, a.name AS arena_name, a.type AS arena_type, a.surface AS arena_surface, a.length_m, a.width_m, a.covered, a.lighting, a.drainage, a.capacity, l.name AS location_name '
                . 'FROM event_arenas ea '
                . 'JOIN arenas a ON a.id = ea.arena_id '
                . 'LEFT JOIN locations l ON l.id = a.location_id '
                . 'WHERE ea.id = :id',
                ['id' => $eventArenaId]
            );

            if (!$resolvedEventArena) {
                flash('error', t('classes.validation.arena_missing'));
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }

            if ((int) $resolvedEventArena['event_id'] !== $eventId) {
                flash('error', t('classes.validation.arena_event_mismatch'));
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }
        } elseif ($quickArenaName !== '') {
            if (!$eventId) {
                flash('error', t('classes.validation.arena_event_required'));
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }

            $now = (new DateTimeImmutable())->format('c');
            $arenaType = $quickArenaType === 'indoor' ? 'indoor' : 'outdoor';
            $lengthValue = $quickArenaLength > 0 ? $quickArenaLength : null;
            $widthValue = $quickArenaWidth > 0 ? $quickArenaWidth : null;

            $locationId = null;
            if ($quickArenaLocation !== '') {
                $locationRow = db_first('SELECT id FROM locations WHERE LOWER(name) = :name LIMIT 1', ['name' => mb_strtolower($quickArenaLocation, 'UTF-8')]);
                if ($locationRow) {
                    $locationId = (int) $locationRow['id'];
                } else {
                    db_execute(
                        'INSERT INTO locations (name, address, geo_lat, geo_lng, notes, created_at, updated_at) '
                        . 'VALUES (:name, NULL, NULL, NULL, NULL, :created, :created)',
                        [
                            'name' => $quickArenaLocation,
                            'created' => $now,
                        ]
                    );
                    $locationId = (int) app_pdo()->lastInsertId();
                }
            }

            db_execute(
                'INSERT INTO arenas (location_id, name, type, surface, length_m, width_m, covered, lighting, drainage, capacity, notes, created_at, updated_at) '
                . 'VALUES (:location_id, :name, :type, :surface, :length_m, :width_m, 0, 0, 0, NULL, NULL, :created, :created)',
                [
                    'location_id' => $locationId,
                    'name' => $quickArenaName,
                    'type' => $arenaType,
                    'surface' => $quickArenaSurface !== '' ? $quickArenaSurface : null,
                    'length_m' => $lengthValue,
                    'width_m' => $widthValue,
                    'created' => $now,
                ]
            );

            $newArenaId = (int) app_pdo()->lastInsertId();

            db_execute(
                'INSERT INTO event_arenas (event_id, arena_id, display_name, remarks, temp_surface, blocked_times, warmup_arena_id, created_at, updated_at) '
                . 'VALUES (:event_id, :arena_id, :display_name, NULL, :temp_surface, :blocked_times, NULL, :created, :created)',
                [
                    'event_id' => $eventId,
                    'arena_id' => $newArenaId,
                    'display_name' => $quickArenaName,
                    'temp_surface' => $quickArenaSurface !== '' ? $quickArenaSurface : null,
                    'blocked_times' => json_encode([], JSON_THROW_ON_ERROR),
                    'created' => $now,
                ]
            );

            $eventArenaId = (int) app_pdo()->lastInsertId();
            $resolvedEventArena = [
                'id' => $eventArenaId,
                'event_id' => $eventId,
                'arena_id' => $newArenaId,
                'display_name' => $quickArenaName,
                'arena_name' => $quickArenaName,
                'arena_type' => $arenaType,
                'arena_surface' => $quickArenaSurface,
                'temp_surface' => $quickArenaSurface,
                'length_m' => $lengthValue,
                'width_m' => $widthValue,
                'covered' => 0,
                'lighting' => 0,
                'drainage' => 0,
                'capacity' => null,
                'location_name' => $quickArenaLocation !== '' ? $quickArenaLocation : null,
                'remarks' => null,
                'blocked_times' => json_encode([], JSON_THROW_ON_ERROR),
            ];
        }

        $resolvedArenaLabel = null;
        $baseArenaIdForAvailability = null;
        if ($resolvedEventArena) {
            $resolvedArenaLabel = trim((string) ($resolvedEventArena['display_name'] ?? ''));
            if ($resolvedArenaLabel === '') {
                $resolvedArenaLabel = trim((string) ($resolvedEventArena['arena_name'] ?? ''));
            }
            $baseArenaIdForAvailability = isset($resolvedEventArena['arena_id']) ? (int) $resolvedEventArena['arena_id'] : null;
        }

        if ($resolvedEventArena && $start !== '' && $end !== '' && $baseArenaIdForAvailability) {
            $conflictParams = [
                'arena' => (int) $eventArenaId,
                'start' => $start,
                'end' => $end,
            ];
            $conflictSql = 'SELECT id, label, start_time, end_time FROM classes WHERE event_arena_id = :arena AND start_time IS NOT NULL AND end_time IS NOT NULL AND start_time < :end AND end_time > :start';
            if ($classId > 0) {
                $conflictSql .= ' AND id != :current';
                $conflictParams['current'] = $classId;
            }
            $conflict = db_first($conflictSql, $conflictParams);
            if ($conflict) {
                flash('error', t('classes.validation.arena_conflict', ['label' => $conflict['label'] ?? '']));
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }

            $blocked = db_first(
                'SELECT id, reason FROM arena_availability WHERE arena_id = :arena_id AND status = "blocked" AND start_time < :end AND end_time > :start',
                [
                    'arena_id' => $baseArenaIdForAvailability,
                    'start' => $start,
                    'end' => $end,
                ]
            );
            if ($blocked) {
                flash('error', t('classes.validation.arena_blocked', ['reason' => $blocked['reason'] ?? '']));
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }
        }

        if ($resolvedArenaLabel === null && $action === 'update' && $classId > 0) {
            $currentClass = db_first('SELECT arena FROM classes WHERE id = :id', ['id' => $classId]);
            if ($currentClass && isset($currentClass['arena'])) {
                $resolvedArenaLabel = $currentClass['arena'];
            }
        }

        $rules = null;
        if ($rulesRaw !== '') {
            try {
                $decodedRule = json_decode($rulesRaw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                flash('error', t('classes.validation.scoring_invalid', ['message' => $e->getMessage()]));
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }
            if (!is_array($decodedRule)) {
                flash('error', t('classes.validation.scoring_object'));
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }
            try {
                RuleManager::validate($decodedRule);
            } catch (\Throwable $e) {
                flash('error', t('classes.validation.scoring_invalid', ['message' => $e->getMessage()]));
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }
            $rules = RuleManager::mergeDefaults($decodedRule);
        }

        $eventRule = $eventId ? scoring_rule_for_event($eventId) : null;
        $resolvedRule = $rules ?? $eventRule ?? RuleManager::dressagePreset();
        try {
            RuleManager::validate($resolvedRule);
            $resolvedRule = RuleManager::mergeDefaults($resolvedRule);
            $rulesSnapshotJson = json_encode($resolvedRule, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            flash('error', t('classes.validation.scoring_rule_invalid', ['message' => $e->getMessage()]));
            header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
            exit;
        }

        $startNumberRule = null;
        if ($startNumberRuleRaw !== '') {
            try {
                $decodedStartRule = json_decode($startNumberRuleRaw, true, 512, JSON_THROW_ON_ERROR);
                $startNumberRule = json_encode($decodedStartRule, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } catch (\JsonException $e) {
                flash('error', t('classes.validation.start_rule_invalid', ['message' => $e->getMessage()]));
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }
        }

        $isGroup = $classSupportsGroupMode && isset($_POST['is_group']) && (string) $_POST['is_group'] === '1' ? 1 : 0;

        $data = [
            'event_id' => $eventId,
            'label' => $label,
            'arena' => $resolvedArenaLabel ?: null,
            'start_time' => $start ?: null,
            'end_time' => $end ?: null,
            'max_starters' => $maxStarters,
            'judge_assignments' => $judges ? json_encode(array_values($judges), JSON_THROW_ON_ERROR) : null,
            'rules_json' => $rules ? json_encode($rules, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null,
            'tiebreaker_json' => $tiebreakers ? json_encode(array_values($tiebreakers), JSON_THROW_ON_ERROR) : null,
            'start_number_rules' => $startNumberRule,
            'scoring_rule_snapshot' => $rulesSnapshotJson,
            'event_arena_id' => $eventArenaId ?: null,
        ];

        if ($classSupportsGroupMode) {
            $data['is_group'] = $isGroup;
        }

        $dataColumns = array_keys($data);
        $updateAssignments = array_map(static fn(string $column) => $column . ' = :' . $column, $dataColumns);

        if ($action === 'update' && $classId > 0) {
            $updateSql = 'UPDATE classes SET ' . implode(', ', $updateAssignments) . ' WHERE id = :id';
            db_execute($updateSql, $data + ['id' => $classId]);
            flash('success', t('classes.flash.updated'));
        } else {
            $insertColumns = implode(', ', $dataColumns);
            $insertPlaceholders = implode(', ', array_map(static fn(string $column) => ':' . $column, $dataColumns));
            $insertSql = 'INSERT INTO classes (' . $insertColumns . ') VALUES (' . $insertPlaceholders . ')';
            db_execute($insertSql, $data);
            flash('success', t('classes.flash.created'));
        }

        header('Location: classes.php');
        exit;
    }
}

$sql = 'SELECT c.*, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id';
$order = ' ORDER BY e.start_date DESC, c.start_time ASC';
if (!$isAdmin) {
    if (!$activeEvent) {
        $classes = [];
    } else {
        $classes = db_all($sql . ' WHERE e.id = :event_id' . $order, ['event_id' => (int) $activeEvent['id']]);
    }
} else {
    $classes = db_all($sql . $order);
}
foreach ($classes as &$class) {
    $class['judges'] = $class['judge_assignments'] ? json_decode($class['judge_assignments'], true, 512, JSON_THROW_ON_ERROR) : [];
    $class['rules'] = $class['rules_json'] ? json_decode($class['rules_json'], true, 512, JSON_THROW_ON_ERROR) : [];
    $class['tiebreakers'] = $class['tiebreaker_json'] ? json_decode($class['tiebreaker_json'], true, 512, JSON_THROW_ON_ERROR) : [];
    $class['is_group'] = $classSupportsGroupMode ? !empty($class['is_group']) : false;
    $class['event_arena_id'] = isset($class['event_arena_id']) ? (int) $class['event_arena_id'] : 0;
    if (!empty($class['event_arena_id']) && isset($arenaLookup[$class['event_arena_id']])) {
        $class['arena_option'] = $arenaLookup[$class['event_arena_id']];
        $class['arena_display'] = $class['arena_option']['label'];
        $class['arena_summary'] = $class['arena_option']['summary'];
    } elseif (!empty($class['arena'])) {
        $class['arena_display'] = $class['arena'];
        $class['arena_summary'] = null;
    } else {
        $class['arena_display'] = null;
        $class['arena_summary'] = null;
    }
    $class['schedule_display'] = classes_format_schedule_range($class['start_time'] ?? null, $class['end_time'] ?? null);
}
unset($class);

$classRuleDefaults = start_number_rule_defaults();
$classRuleDesigner = $classRuleDefaults;
$classRuleEvent = null;
$designerEventId = null;
if ($editClass) {
    $designerEventId = (int) ($editClass['event_id'] ?? 0);
    if ($designerEventId > 0) {
        $eventRuleRow = db_first('SELECT start_number_rules FROM events WHERE id = :id', ['id' => $designerEventId]);
        if (!empty($eventRuleRow['start_number_rules'])) {
            try {
                $decodedEventRule = json_decode($eventRuleRow['start_number_rules'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decodedEventRule)) {
                    $classRuleEvent = start_number_rule_merge_defaults($decodedEventRule);
                }
            } catch (\JsonException $e) {
                $classRuleEvent = null;
            }
        }
    }
    $rawOverride = $editClass['start_number_rules_text'] ?? ($editClass['start_number_rules'] ?? '');
    if ($rawOverride !== '') {
        try {
            $decodedOverride = json_decode($rawOverride, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decodedOverride)) {
                $classRuleDesigner = start_number_rule_merge_defaults($decodedOverride);
            }
        } catch (\JsonException $e) {
            $classRuleDesigner = $classRuleDefaults;
        }
    } elseif ($classRuleEvent) {
        $classRuleDesigner = $classRuleEvent;
    }
}

if ($editClass) {
    $editClass['event_arena_id'] = isset($editClass['event_arena_id']) ? (int) $editClass['event_arena_id'] : 0;
    if (!empty($editClass['event_arena_id']) && isset($arenaLookup[$editClass['event_arena_id']])) {
        $editClass['arena_option'] = $arenaLookup[$editClass['event_arena_id']];
    } elseif (!empty($editClass['arena']) && empty($editClass['legacy_arena'])) {
        $editClass['legacy_arena'] = $editClass['arena'];
    }
}

render_page('classes.tpl', [
    'title' => t('classes.title'),
    'page' => 'classes',
    'events' => $events,
    'classes' => $classes,
    'presets' => $presets,
    'editClass' => $editClass,
    'supportsGroupMode' => $classSupportsGroupMode,
    'classSimulation' => $classSimulation,
    'classSimulationError' => $classSimulationError,
    'scoringSimulation' => $scoringSimulation,
    'scoringSimulationError' => $scoringSimulationError,
    'simulationCount' => $simulationCount,
    'arenaPickerDataJson' => $arenaPickerDataJson,
    'arenaOptionsByEvent' => $arenaOptionsByEvent,
    'classRuleDesignerJson' => start_number_rule_safe_json($classRuleDesigner),
    'classRuleDefaultsJson' => start_number_rule_safe_json($classRuleDefaults),
    'classRuleEventJson' => $classRuleEvent ? start_number_rule_safe_json($classRuleEvent) : null,
    'scoringDesignerDefaultJson' => scoring_rule_safe_json($scoringDesignerDefault),
    'scoringDesignerPresetsJson' => scoring_rule_safe_json($scoringDesignerPresets),
    'scoringPresetOptions' => $scoringPresetOptions,
    'startNumberPresetOptions' => $startNumberPresetOptions,
    'startNumberDesignerPresetsJson' => start_number_rule_safe_json($startNumberPresetRules),
    'extraScripts' => ['public/assets/js/scoring-designer.js', 'public/assets/js/start-number-designer.js', 'public/assets/js/classes.js'],
]);
