<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/app/helpers/start_number_rules.php';
require_once __DIR__ . '/app/helpers/scoring.php';
require_once __DIR__ . '/app/helpers/arenas.php';

use App\Scoring\RuleManager;

$user = auth_require('events');
$isAdmin = auth_is_admin($user);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editEvent = $editId ? db_first('SELECT * FROM events WHERE id = :id', ['id' => $editId]) : null;
if ($editEvent && !empty($editEvent['start_number_rules'])) {
    $editEvent['start_number_rules_text'] = $editEvent['start_number_rules'];
}
if ($editEvent && !empty($editEvent['scoring_rule_json'])) {
    $editEvent['scoring_rule_text'] = json_encode(scoring_rule_decode($editEvent['scoring_rule_json']) ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$scoringPresets = RuleManager::presets();
$scoringDesignerDefault = RuleManager::mergeDefaults([]);
$scoringDesignerPresets = [];
foreach ($scoringPresets as $key => $preset) {
    $scoringDesignerPresets[$key] = RuleManager::mergeDefaults($preset);
}
$scoringPresetOptions = scoring_rule_preset_options();
$startNumberPresetRules = start_number_rule_preset_rules();
$startNumberPresetOptions = start_number_rule_preset_options();

$ruleDefaults = start_number_rule_defaults();
$designerRule = $ruleDefaults;
if ($editEvent && !empty($editEvent['start_number_rules'])) {
    try {
        $decoded = json_decode($editEvent['start_number_rules'], true, 512, JSON_THROW_ON_ERROR);
        if (is_array($decoded)) {
            $designerRule = start_number_rule_merge_defaults($decoded);
        }
    } catch (\JsonException $e) {
        $designerRule = $ruleDefaults;
    }
}

$simulation = [];
$simulationError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('events.validation.csrf_invalid'));
        header('Location: events.php');
        exit;
    }

    require_write_access('events');

    $action = $_POST['action'] ?? ($_POST['default_action'] ?? 'create');

    if ($action === 'simulate_rules') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $rulesInput = trim((string) ($_POST['start_number_rules'] ?? ''));
        $editEvent = [
            'id' => $eventId,
            'title' => $title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'arena_assignments' => [],
            'start_number_rules_text' => $rulesInput,
        ];
        if ($rulesInput === '') {
            $simulationError = t('events.validation.rule_json_required');
        } else {
            try {
                $rulesDecoded = json_decode($rulesInput, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($rulesDecoded)) {
                    $simulationError = t('events.validation.rule_json_object');
                } else {
                    $simulation = events_simulate_numbers($rulesDecoded, 20);
                    $designerRule = start_number_rule_merge_defaults($rulesDecoded);
                }
            } catch (\JsonException $e) {
                $simulationError = t('events.validation.rule_json_invalid', ['message' => $e->getMessage()]);
            }
        }
    }

    if (in_array($action, ['set_active', 'deactivate'], true)) {
        if (!$isAdmin) {
            flash('error', t('events.validation.admin_only_activation'));
            header('Location: events.php');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId) {
            if ($action === 'set_active') {
                db_execute('UPDATE events SET is_active = 0 WHERE is_active = 1');
                db_execute('UPDATE events SET is_active = 1 WHERE id = :id', ['id' => $eventId]);
                event_active(true);
                flash('success', t('events.flash.activated'));
            } else {
                db_execute('UPDATE events SET is_active = 0 WHERE id = :id', ['id' => $eventId]);
                event_active(true);
                flash('success', t('events.flash.deactivated'));
            }
        }
        header('Location: events.php');
        exit;
    }

    if ($action === 'delete') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId) {
            $hasClasses = db_first('SELECT COUNT(*) AS cnt FROM classes WHERE event_id = :id', ['id' => $eventId]);
            if ($hasClasses && (int) $hasClasses['cnt'] > 0) {
                flash('error', t('events.validation.has_classes'));
            } else {
                db_execute('DELETE FROM events WHERE id = :id', ['id' => $eventId]);
                event_active(true);
                flash('success', t('events.flash.deleted'));
            }
        }
        header('Location: events.php');
        exit;
    }

    if ($action !== 'simulate_rules') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $start = trim((string) ($_POST['start_date'] ?? ''));
        $end = trim((string) ($_POST['end_date'] ?? ''));
        $rulesInput = trim((string) ($_POST['start_number_rules'] ?? ''));
        $rulesEncoded = null;
        $scoringRuleInput = trim((string) ($_POST['scoring_rule_json'] ?? ''));
        $scoringRuleEncoded = null;
        if ($rulesInput !== '') {
            try {
                $decodedRules = json_decode($rulesInput, true, 512, JSON_THROW_ON_ERROR);
                $rulesEncoded = json_encode($decodedRules, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
                $designerRule = start_number_rule_merge_defaults($decodedRules);
            } catch (\JsonException $e) {
                flash('error', t('events.validation.rule_json_invalid', ['message' => $e->getMessage()]));
                header('Location: events.php' . ($eventId ? '?edit=' . $eventId : ''));
                exit;
            }
        }
        if ($scoringRuleInput !== '') {
            try {
                $decodedScoring = json_decode($scoringRuleInput, true, 512, JSON_THROW_ON_ERROR);
                RuleManager::validate($decodedScoring);
                $scoringRuleEncoded = json_encode(RuleManager::mergeDefaults($decodedScoring), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } catch (\Throwable $e) {
                flash('error', t('events.validation.scoring_invalid', ['message' => $e->getMessage()]));
                header('Location: events.php' . ($eventId ? '?edit=' . $eventId : ''));
                exit;
            }
        }

        if ($title === '') {
            flash('error', t('events.validation.title_required'));
        } else {
            $existingVenues = null;
            if ($action === 'update' && $eventId > 0) {
                $existingRow = db_first('SELECT venues FROM events WHERE id = :id', ['id' => $eventId]);
                $existingVenues = $existingRow['venues'] ?? null;
            }
            $payload = [
                'title' => $title,
                'start' => $start ?: null,
                'end' => $end ?: null,
                'venues' => $existingVenues,
                'rules' => $rulesEncoded,
                'scoring_rule' => $scoringRuleEncoded,
            ];

            if ($action === 'update' && $eventId > 0) {
                db_execute(
                    'UPDATE events SET title = :title, start_date = :start, end_date = :end, venues = :venues, start_number_rules = :rules, scoring_rule_json = :scoring_rule WHERE id = :id',
                    $payload + ['id' => $eventId]
                );
                flash('success', t('events.flash.updated'));
            } else {
                db_execute(
                    'INSERT INTO events (title, start_date, end_date, venues, start_number_rules, scoring_rule_json) VALUES (:title, :start, :end, :venues, :rules, :scoring_rule)',
                    $payload
                );
                flash('success', t('events.flash.created'));
            }
        }

        header('Location: events.php');
        exit;
    }
}

$eventsQuery = 'SELECT * FROM events';
$params = [];
if (!$isAdmin) {
    $eventsQuery .= ' WHERE is_active = 1';
}
$eventsQuery .= ' ORDER BY start_date DESC, id DESC';
$events = db_all($eventsQuery, $params);
$eventIds = array_filter(array_map(static fn ($row) => (int) ($row['id'] ?? 0), $events));
if ($editEvent && isset($editEvent['id'])) {
    $eventIds[] = (int) $editEvent['id'];
}
$eventIds = array_values(array_unique(array_filter($eventIds)));
$eventAssignments = $eventIds ? arenas_event_assignments(app_pdo(), $eventIds) : [];
foreach ($events as &$event) {
    $eventId = (int) ($event['id'] ?? 0);
    $event['arena_assignments'] = $eventAssignments[$eventId] ?? [];
}
unset($event);
if ($editEvent) {
    $editId = (int) ($editEvent['id'] ?? 0);
    $editEvent['arena_assignments'] = $eventAssignments[$editId] ?? [];
}

render_page('events.tpl', [
    'title' => t('events.title'),
    'page' => 'events',
    'events' => $events,
    'editEvent' => $editEvent,
    'isAdmin' => $isAdmin,
    'simulation' => $simulation,
    'simulationError' => $simulationError,
    'ruleDesignerJson' => start_number_rule_safe_json($designerRule),
    'ruleDesignerDefaultsJson' => start_number_rule_safe_json($ruleDefaults),
    'scoringDesignerDefaultJson' => scoring_rule_safe_json($scoringDesignerDefault),
    'scoringDesignerPresetsJson' => scoring_rule_safe_json($scoringDesignerPresets),
    'scoringPresetOptions' => $scoringPresetOptions,
    'startNumberPresetOptions' => $startNumberPresetOptions,
    'startNumberDesignerPresetsJson' => start_number_rule_safe_json($startNumberPresetRules),
    'extraScripts' => ['public/assets/js/scoring-designer.js', 'public/assets/js/start-number-designer.js'],
]);

function events_simulate_numbers(array $rule, int $count): array
{
    $defaults = [
        'sequence' => ['start' => 1, 'step' => 1, 'range' => null],
        'format' => ['prefix' => '', 'width' => 0, 'suffix' => '', 'separator' => ''],
        'constraints' => ['blocklists' => []],
    ];
    $rule = array_replace_recursive($defaults, $rule);
    $start = (int) ($rule['sequence']['start'] ?? 1);
    $step = (int) ($rule['sequence']['step'] ?? 1) ?: 1;
    $range = $rule['sequence']['range'];
    $blocklist = array_map('strval', $rule['constraints']['blocklists'] ?? []);
    $numbers = [];
    $current = $start;
    while (count($numbers) < $count) {
        if ($range && $current > (int) $range[1]) {
            break;
        }
        if ($blocklist && in_array((string) $current, $blocklist, true)) {
            $current += $step;
            continue;
        }
        $numbers[] = [
            'raw' => $current,
            'display' => events_format_number($current, $rule['format']),
        ];
        $current += $step;
    }
    return $numbers;
}

function events_format_number(int $number, array $format): string
{
    $width = (int) ($format['width'] ?? 0);
    $body = $width > 0 ? str_pad((string) $number, $width, '0', STR_PAD_LEFT) : (string) $number;
    $prefix = (string) ($format['prefix'] ?? '');
    $suffix = (string) ($format['suffix'] ?? '');
    $separator = (string) ($format['separator'] ?? '');
    $parts = [];
    if ($prefix !== '') {
        $parts[] = $prefix;
    }
    $parts[] = $body;
    if ($suffix !== '') {
        $parts[] = $suffix;
    }
    return $separator === '' ? implode('', $parts) : implode($separator, $parts);
}

