<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/app/helpers/start_number_rules.php';
require_once __DIR__ . '/app/helpers/scoring.php';

use App\Scoring\RuleManager;
use App\Setup\Installer;

$user = auth_require('classes');
$isAdmin = auth_is_admin($user);
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

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editClass = null;
if ($editId) {
    $editClass = db_first('SELECT * FROM classes WHERE id = :id', ['id' => $editId]);
    if ($editClass && !event_accessible($user, (int) $editClass['event_id'])) {
        flash('error', 'Kein Zugriff auf dieses Turnier.');
        header('Location: classes.php');
        exit;
    }
    if ($editClass) {
        $editClass['judges'] = $editClass['judge_assignments'] ? implode(', ', json_decode($editClass['judge_assignments'], true, 512, JSON_THROW_ON_ERROR) ?: []) : '';
        $editClass['rules_text'] = $editClass['rules_json'] ? json_encode(json_decode($editClass['rules_json'], true, 512, JSON_THROW_ON_ERROR), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';
        $editClass['tiebreakers_list'] = $editClass['tiebreaker_json'] ? implode(', ', json_decode($editClass['tiebreaker_json'], true, 512, JSON_THROW_ON_ERROR) ?: []) : '';
        $editClass['start_formatted'] = $editClass['start_time'] ? date('Y-m-d\TH:i', strtotime($editClass['start_time'])) : '';
        $editClass['end_formatted'] = $editClass['end_time'] ? date('Y-m-d\TH:i', strtotime($editClass['end_time'])) : '';
        if (!empty($editClass['start_number_rules'])) {
            $editClass['start_number_rules_text'] = $editClass['start_number_rules'];
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
        flash('error', 'CSRF ungültig.');
        header('Location: classes.php');
        exit;
    }

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
        $arena = trim((string) ($_POST['arena'] ?? ''));
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
            'arena' => $arena,
            'start_formatted' => $start,
            'end_formatted' => $end,
            'max_starters' => $maxStarters !== '' ? (int) $maxStarters : null,
            'judges' => $judgesInput,
            'rules_text' => $rulesInput,
            'tiebreakers_list' => $tiebreakersInput,
            'start_number_rules_text' => $ruleJson,
        ];

        if (!$eventId || !event_accessible($user, $eventId)) {
            $classSimulationError = 'Turnier auswählen.';
        } elseif ($ruleJson === '') {
            $classSimulationError = 'Regel-JSON angeben.';
        } else {
            try {
                $decodedRule = json_decode($ruleJson, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($decodedRule)) {
                    $classSimulationError = 'Regel-JSON muss ein Objekt sein.';
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
                $classSimulationError = 'Regel-JSON ungültig: ' . $e->getMessage();
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
                    $scoringSimulationError = 'Regel-JSON ungültig.';
                }
            } catch (\JsonException $e) {
                $scoringSimulationError = 'Regel-JSON ungültig: ' . $e->getMessage();
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
                $scoringSimulationError = 'Simulation fehlgeschlagen: ' . $e->getMessage();
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
                    flash('error', 'Keine Berechtigung für dieses Turnier.');
                    header('Location: classes.php');
                    exit;
                }
                db_execute('DELETE FROM results WHERE startlist_id IN (SELECT id FROM startlist_items WHERE class_id = :id)', ['id' => $classId]);
                db_execute('DELETE FROM startlist_items WHERE class_id = :id', ['id' => $classId]);
                db_execute('DELETE FROM entries WHERE class_id = :id', ['id' => $classId]);
                db_execute('DELETE FROM schedule_shifts WHERE class_id = :id', ['id' => $classId]);
                db_execute('DELETE FROM classes WHERE id = :id', ['id' => $classId]);
                flash('success', 'Prüfung gelöscht.');
            }
            header('Location: classes.php');
            exit;
        }

        $classId = (int) ($_POST['class_id'] ?? 0);
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $label = trim((string) ($_POST['label'] ?? ''));
        $arena = trim((string) ($_POST['arena'] ?? ''));
        $start = trim((string) ($_POST['start_time'] ?? ''));
        $end = trim((string) ($_POST['end_time'] ?? ''));
        $maxStarters = (int) ($_POST['max_starters'] ?? 0) ?: null;
        $judges = array_filter(array_map('trim', explode(',', (string) ($_POST['judges'] ?? ''))));
        $rulesRaw = (string) ($_POST['rules_json'] ?? '');
        $tiebreakers = array_filter(array_map('trim', explode(',', (string) ($_POST['tiebreakers'] ?? ''))));
        $startNumberRuleRaw = trim((string) ($_POST['start_number_rules'] ?? ''));

        if (!$eventId || $label === '') {
            flash('error', 'Event und Bezeichnung angeben.');
            header('Location: classes.php');
            exit;
        }

        if (!event_accessible($user, $eventId)) {
            flash('error', 'Keine Berechtigung für dieses Turnier.');
            header('Location: classes.php');
            exit;
        }

        $rules = null;
        if ($rulesRaw !== '') {
            try {
                $decodedRule = json_decode($rulesRaw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                flash('error', 'Scoring-Regeln ungültig: ' . $e->getMessage());
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }
            if (!is_array($decodedRule)) {
                flash('error', 'Scoring-Regeln müssen ein JSON-Objekt sein.');
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }
            try {
                RuleManager::validate($decodedRule);
            } catch (\Throwable $e) {
                flash('error', 'Scoring-Regeln ungültig: ' . $e->getMessage());
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
            flash('error', 'Scoring-Regel ungültig: ' . $e->getMessage());
            header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
            exit;
        }

        $startNumberRule = null;
        if ($startNumberRuleRaw !== '') {
            try {
                $decodedStartRule = json_decode($startNumberRuleRaw, true, 512, JSON_THROW_ON_ERROR);
                $startNumberRule = json_encode($decodedStartRule, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } catch (\JsonException $e) {
                flash('error', 'Startnummern-Regel ungültig: ' . $e->getMessage());
                header('Location: classes.php' . ($classId ? '?edit=' . $classId : ''));
                exit;
            }
        }

        $data = [
            'event_id' => $eventId,
            'label' => $label,
            'arena' => $arena ?: null,
            'start_time' => $start ?: null,
            'end_time' => $end ?: null,
            'max_starters' => $maxStarters,
            'judge_assignments' => $judges ? json_encode(array_values($judges), JSON_THROW_ON_ERROR) : null,
            'rules_json' => $rules ? json_encode($rules, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null,
            'tiebreaker_json' => $tiebreakers ? json_encode(array_values($tiebreakers), JSON_THROW_ON_ERROR) : null,
            'start_number_rules' => $startNumberRule,
            'scoring_rule_snapshot' => $rulesSnapshotJson,
        ];

        if ($action === 'update' && $classId > 0) {
            db_execute(
                'UPDATE classes SET event_id = :event_id, label = :label, arena = :arena, start_time = :start_time, end_time = :end_time, max_starters = :max_starters, judge_assignments = :judge_assignments, rules_json = :rules_json, tiebreaker_json = :tiebreaker_json, start_number_rules = :start_number_rules, scoring_rule_snapshot = :scoring_rule_snapshot WHERE id = :id',
                $data + ['id' => $classId]
            );
            flash('success', 'Prüfung aktualisiert.');
        } else {
            db_execute(
                'INSERT INTO classes (event_id, label, arena, start_time, end_time, max_starters, judge_assignments, rules_json, tiebreaker_json, start_number_rules, scoring_rule_snapshot) VALUES (:event_id, :label, :arena, :start_time, :end_time, :max_starters, :judge_assignments, :rules_json, :tiebreaker_json, :start_number_rules, :scoring_rule_snapshot)',
                $data
            );
            flash('success', 'Prüfung angelegt.');
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

render_page('classes.tpl', [
    'title' => 'Prüfungen',
    'page' => 'classes',
    'events' => $events,
    'classes' => $classes,
    'presets' => $presets,
    'editClass' => $editClass,
    'classSimulation' => $classSimulation,
    'classSimulationError' => $classSimulationError,
    'scoringSimulation' => $scoringSimulation,
    'scoringSimulationError' => $scoringSimulationError,
    'simulationCount' => $simulationCount,
    'classRuleDesignerJson' => start_number_rule_safe_json($classRuleDesigner),
    'classRuleDefaultsJson' => start_number_rule_safe_json($classRuleDefaults),
    'classRuleEventJson' => $classRuleEvent ? start_number_rule_safe_json($classRuleEvent) : null,
    'scoringDesignerDefaultJson' => scoring_rule_safe_json($scoringDesignerDefault),
    'scoringDesignerPresetsJson' => scoring_rule_safe_json($scoringDesignerPresets),
    'extraScripts' => ['public/assets/js/scoring-designer.js', 'public/assets/js/start-number-designer.js', 'public/assets/js/classes.js'],
]);
