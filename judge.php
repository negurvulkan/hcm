<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/audit.php';
require_once __DIR__ . '/app/helpers/scoring.php';

use App\CustomFields\CustomFieldManager;
use App\CustomFields\CustomFieldRepository;
use App\Scoring\ScoringEngine;

$user = auth_require('judge');
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
    render_page('judge.tpl', [
        'titleKey' => 'pages.judge.title',
        'page' => 'judge',
        'classes' => [],
        'selectedClass' => null,
        'start' => null,
        'scores' => [],
        'rule' => [],
        'result' => null,
    ]);
    exit;
}

$classId = (int) ($_GET['class_id'] ?? $classes[0]['id']);
$selectedClass = $classId ? db_first('SELECT * FROM classes WHERE id = :id', ['id' => $classId]) : null;
if (!$selectedClass || !event_accessible($user, (int) $selectedClass['event_id'])) {
    $classId = (int) $classes[0]['id'];
    $selectedClass = db_first('SELECT * FROM classes WHERE id = :id', ['id' => $classId]);
    if (!$selectedClass || !event_accessible($user, (int) $selectedClass['event_id'])) {
        flash('error', t('judge.validation.forbidden_event'));
        render_page('judge.tpl', [
            'titleKey' => 'pages.judge.title',
            'page' => 'judge',
            'classes' => [],
            'selectedClass' => null,
            'start' => null,
            'scores' => [],
            'rule' => [],
            'result' => null,
        ]);
        exit;
    }
}

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

$starts = db_all('SELECT si.id, si.position, si.state, si.start_number_display, si.start_number_assignment_id, e.id AS entry_id, pr.id AS rider_id, pr.display_name AS rider, pr.email AS rider_email, pr.phone AS rider_phone, pr.date_of_birth AS rider_date_of_birth, pr.nationality AS rider_nationality, profile.club_id AS rider_club_id, c.name AS rider_club_name, h.id AS horse_id, h.name AS horse, h.life_number AS horse_life_number, h.microchip AS horse_microchip, h.sex AS horse_sex, h.birth_year AS horse_birth_year, h.documents_ok AS horse_documents_ok, h.notes AS horse_notes, owner.display_name AS horse_owner_name FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN parties pr ON pr.id = e.party_id LEFT JOIN person_profiles profile ON profile.party_id = pr.id LEFT JOIN clubs c ON c.id = profile.club_id JOIN horses h ON h.id = e.horse_id LEFT JOIN parties owner ON owner.id = h.owner_party_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
if (!$starts) {
    render_page('judge.tpl', [
        'titleKey' => 'pages.judge.title',
        'page' => 'judge',
        'classes' => $classes,
        'selectedClass' => $selectedClass,
        'start' => null,
        'scores' => [],
        'rule' => $selectedClass['rules_json'] ? json_decode($selectedClass['rules_json'], true, 512, JSON_THROW_ON_ERROR) : [],
        'result' => null,
        'starts' => [],
        'startStateCounts' => [],
        'extraScripts' => ['public/assets/js/entity-info.js', 'public/assets/js/judge.js'],
    ]);
    exit;
}

$riderIds = array_values(array_filter(array_map(static fn (array $item): int => (int) ($item['rider_id'] ?? 0), $starts), static fn (int $id): bool => $id > 0));
$horseIds = array_values(array_filter(array_map(static fn (array $item): int => (int) ($item['horse_id'] ?? 0), $starts), static fn (int $id): bool => $id > 0));
$riderCustomValues = $customFieldRepository->valuesForMany('person', $riderIds);
$horseCustomValues = $customFieldRepository->valuesForMany('horse', $horseIds);
foreach ($starts as &$candidate) {
    $riderId = (int) ($candidate['rider_id'] ?? 0);
    $horseId = (int) ($candidate['horse_id'] ?? 0);
    $candidate['rider_custom_fields'] = $riderCustomFieldManager->entityInfoFields($riderCustomValues[$riderId] ?? []);
    $candidate['horse_custom_fields'] = $horseCustomFieldManager->entityInfoFields($horseCustomValues[$horseId] ?? []);
}
unset($candidate);

$startStateCounts = [];
foreach ($starts as $candidate) {
    $stateKey = $candidate['state'] ?? 'scheduled';
    $startStateCounts[$stateKey] = ($startStateCounts[$stateKey] ?? 0) + 1;
}

$startId = (int) ($_GET['start_id'] ?? $starts[0]['id']);
$start = null;
foreach ($starts as $candidate) {
    if ((int) $candidate['id'] === $startId) {
        $start = $candidate;
        break;
    }
}
if (!$start) {
    $start = $starts[0];
    $startId = (int) $start['id'];
}

$rule = scoring_rule_for_class($selectedClass);
$result = db_first('SELECT * FROM results WHERE startlist_id = :id', ['id' => $startId]);
$scoresPayload = $result && $result['scores_json'] ? json_decode($result['scores_json'], true, 512, JSON_THROW_ON_ERROR) : null;
$existingInput = is_array($scoresPayload['input'] ?? null) ? $scoresPayload['input'] : ['fields' => [], 'judges' => []];
$existingEvaluation = $scoresPayload['evaluation'] ?? null;
$fieldsInput = is_array($existingInput['fields'] ?? null) ? $existingInput['fields'] : [];
$judgeEntries = [];
foreach ($existingInput['judges'] ?? [] as $entry) {
    if (is_array($entry) && !empty($entry['id'])) {
        $judgeEntries[$entry['id']] = $entry;
    }
}
$judgeKey = judge_identifier($user);
$currentJudgeComponents = $judgeEntries[$judgeKey]['components'] ?? [];
$otherJudges = array_filter($judgeEntries, static fn(string $key): bool => $key !== $judgeKey, ARRAY_FILTER_USE_KEY);
$perJudgeScores = [];
if (is_array($existingEvaluation['per_judge'] ?? null)) {
    foreach ($existingEvaluation['per_judge'] as $entry) {
        if (!empty($entry['id'])) {
            $perJudgeScores[$entry['id']] = $entry;
        }
    }
}

$fieldsInput = judge_normalize_field_values($rule['input']['fields'] ?? [], $fieldsInput);
$judgeComponents = judge_normalize_component_values($rule['input']['components'] ?? [], $currentJudgeComponents);

if ($start && $start['state'] !== 'running' && $start['state'] !== 'withdrawn') {
    $before = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $startId]);
    db_execute('UPDATE startlist_items SET state = :state, updated_at = :updated WHERE id = :id', [
        'state' => 'running',
        'updated' => (new \DateTimeImmutable())->format('c'),
        'id' => $startId,
    ]);
    audit_log('startlist_items', $startId, 'state_change', $before, ['state' => 'running']);
    if (($startNumberRule['allocation']['time'] ?? 'on_startlist') === 'on_gate') {
        assignStartNumber($startNumberContext, [
            'entry_id' => (int) $start['entry_id'],
            'startlist_id' => $startId,
        ]);
    }
    $assignmentIdRow = db_first('SELECT start_number_assignment_id FROM startlist_items WHERE id = :id', ['id' => $startId]);
    if (!empty($assignmentIdRow['start_number_assignment_id']) && ($startNumberRule['allocation']['lock_after'] ?? 'start_called') === 'start_called') {
        lockStartNumber((int) $assignmentIdRow['start_number_assignment_id'], 'start_called');
    }
    $upcoming = db_all('SELECT si.position, pr.display_name AS rider FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN parties pr ON pr.id = e.party_id WHERE si.class_id = :class AND si.state = "scheduled" ORDER BY si.planned_start ASC, si.position ASC LIMIT 5', ['class' => $classId]);
    db_execute('INSERT INTO notifications (type, payload, created_at) VALUES (:type, :payload, :created)', [
        'type' => 'next_starter',
        'payload' => json_encode([
            'current' => $start['rider'],
            'upcoming' => array_column($upcoming, 'rider'),
        ], JSON_THROW_ON_ERROR),
        'created' => (new \DateTimeImmutable())->format('c'),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('judge.validation.csrf_invalid'));
        header('Location: judge.php?class_id=' . $classId . '&start_id=' . $startId);
        exit;
    }

    require_write_access('judge');

    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete_result') {
        if ($result) {
            db_execute('DELETE FROM results WHERE id = :id', ['id' => $result['id']]);
            audit_log('results', (int) $result['id'], 'delete', $result, null);
            db_execute('UPDATE startlist_items SET state = :state, updated_at = :updated WHERE id = :id', [
                'state' => 'scheduled',
                'updated' => (new \DateTimeImmutable())->format('c'),
                'id' => $startId,
            ]);
            flash('success', t('judge.flash.result_deleted'));
        }
        header('Location: judge.php?class_id=' . $classId . '&start_id=' . $startId);
        exit;
    }

    $payload = $_POST['score'] ?? [];
    $parsedFields = judge_parse_fields($rule['input']['fields'] ?? [], $payload['fields'] ?? []);
    $parsedComponents = judge_parse_components($rule['input']['components'] ?? [], $payload['components'] ?? []);
    $fieldsData = $fieldsInput;
    foreach ($parsedFields as $key => $value) {
        $fieldsData[$key] = $value;
    }
    $judgeEntries[$judgeKey] = [
        'id' => $judgeKey,
        'user' => ['id' => $user['id'] ?? null, 'name' => $user['name'] ?? null],
        'components' => $parsedComponents,
        'submitted_at' => (new \DateTimeImmutable())->format('c'),
    ];
    $evaluationInput = [
        'fields' => $fieldsData,
        'judges' => array_values($judgeEntries),
    ];
    $engine = scoring_engine();
    $evaluation = $engine->evaluate($rule, $evaluationInput);
    $totals = $evaluation['totals'];
    $status = isset($_POST['sign']) ? 'signed' : 'submitted';
    $scoresPayload = [
        'input' => $evaluationInput,
        'evaluation' => $evaluation,
    ];
    $ruleSnapshot = $totals['rule_snapshot'] ?? $engine->snapshotRule($rule);
    $breakdown = [
        'per_judge' => $evaluation['per_judge'],
        'aggregate' => $evaluation['aggregate'],
        'totals' => [
            'penalties' => $totals['penalties'],
            'time' => $totals['time'],
        ],
    ];

    $store = [
        'scores' => json_encode($scoresPayload, JSON_THROW_ON_ERROR),
        'total' => $totals['total_rounded'] ?? $totals['total_raw'],
        'penalties' => $totals['penalties']['total'] ?? 0,
        'status' => $status,
        'signed_by' => $status === 'signed' ? ($user['name'] ?? null) : null,
        'signed_at' => $status === 'signed' ? (new \DateTimeImmutable())->format('c') : null,
        'signature' => $status === 'signed' ? hash('sha256', ($user['email'] ?? '') . $startId . time()) : null,
        'breakdown' => json_encode($breakdown, JSON_THROW_ON_ERROR),
        'rule_snapshot' => json_encode($ruleSnapshot, JSON_THROW_ON_ERROR),
        'engine_version' => $totals['engine_version'] ?? ScoringEngine::ENGINE_VERSION,
        'tiebreak_path' => json_encode([], JSON_THROW_ON_ERROR),
        'rank' => null,
        'eliminated' => !empty($totals['eliminated']) ? 1 : 0,
    ];

    if ($result) {
        $before = $result;
        db_execute('UPDATE results SET scores_json = :scores, total = :total, penalties = :penalties, status = :status, signed_by = :signed_by, signed_at = :signed_at, signature_hash = :signature, breakdown_json = :breakdown, rule_snapshot = :rule_snapshot, engine_version = :engine_version, tiebreak_path = :tiebreak_path, rank = :rank, eliminated = :eliminated WHERE id = :id', [
            'scores' => $store['scores'],
            'total' => $store['total'],
            'penalties' => $store['penalties'],
            'status' => $store['status'],
            'signed_by' => $store['signed_by'],
            'signed_at' => $store['signed_at'],
            'signature' => $store['signature'],
            'breakdown' => $store['breakdown'],
            'rule_snapshot' => $store['rule_snapshot'],
            'engine_version' => $store['engine_version'],
            'tiebreak_path' => $store['tiebreak_path'],
            'rank' => $store['rank'],
            'eliminated' => $store['eliminated'],
            'id' => $result['id'],
        ]);
        $result = db_first('SELECT * FROM results WHERE id = :id', ['id' => $result['id']]);
        audit_log('results', (int) $result['id'], 'update', $before, $result);
    } else {
        db_execute('INSERT INTO results (startlist_id, scores_json, total, penalties, status, signed_by, signed_at, signature_hash, created_at, breakdown_json, rule_snapshot, engine_version, tiebreak_path, rank, eliminated) VALUES (:startlist_id, :scores, :total, :penalties, :status, :signed_by, :signed_at, :signature, :created, :breakdown, :rule_snapshot, :engine_version, :tiebreak_path, :rank, :eliminated)', [
            'startlist_id' => $startId,
            'scores' => $store['scores'],
            'total' => $store['total'],
            'penalties' => $store['penalties'],
            'status' => $store['status'],
            'signed_by' => $store['signed_by'],
            'signed_at' => $store['signed_at'],
            'signature' => $store['signature'],
            'created' => (new \DateTimeImmutable())->format('c'),
            'breakdown' => $store['breakdown'],
            'rule_snapshot' => $store['rule_snapshot'],
            'engine_version' => $store['engine_version'],
            'tiebreak_path' => $store['tiebreak_path'],
            'rank' => $store['rank'],
            'eliminated' => $store['eliminated'],
        ]);
        $resultId = (int) app_pdo()->lastInsertId();
        $result = db_first('SELECT * FROM results WHERE id = :id', ['id' => $resultId]);
        audit_log('results', $resultId, 'create', null, $result);
    }

    db_execute('UPDATE startlist_items SET state = :state, updated_at = :updated WHERE id = :id', [
        'state' => $status === 'signed' ? 'completed' : 'running',
        'updated' => (new \DateTimeImmutable())->format('c'),
        'id' => $startId,
    ]);
    if ($status === 'signed') {
        $assignmentIdRow = db_first('SELECT start_number_assignment_id FROM startlist_items WHERE id = :id', ['id' => $startId]);
        if (!empty($assignmentIdRow['start_number_assignment_id'])) {
            lockStartNumber((int) $assignmentIdRow['start_number_assignment_id'], 'sign_off');
        }
    }

    scoring_recalculate_class($classId, $user, 'judge_submit');

    flash('success', t('judge.flash.result_saved'));
    header('Location: judge.php?class_id=' . $classId . '&start_id=' . $startId);
    exit;
}

render_page('judge.tpl', [
    'titleKey' => 'pages.judge.title',
    'page' => 'judge',
    'classes' => $classes,
    'selectedClass' => $selectedClass,
    'start' => $start,
    'starts' => $starts,
    'fieldsInput' => $fieldsInput,
    'judgeComponents' => $judgeComponents,
    'rule' => $rule,
    'result' => $result,
    'evaluation' => $existingEvaluation,
    'perJudgeScores' => $perJudgeScores,
    'otherJudges' => $otherJudges,
    'judgeKey' => $judgeKey,
    'startNumberRule' => $startNumberRule,
    'startStateCounts' => $startStateCounts,
    'extraScripts' => ['public/assets/js/entity-info.js', 'public/assets/js/judge.js'],
]);

function judge_identifier(array $user): string
{
    if (!empty($user['id'])) {
        return 'judge:' . (int) $user['id'];
    }
    $token = $user['email'] ?? $user['name'] ?? uniqid('judge', true);
    return 'judge:' . substr(hash('sha1', $token), 0, 12);
}

function judge_normalize_field_values(array $definitions, array $values): array
{
    $normalized = [];
    foreach ($definitions as $definition) {
        $id = $definition['id'] ?? null;
        if (!$id) {
            continue;
        }
        $type = $definition['type'] ?? 'number';
        $value = $values[$id] ?? ($definition['default'] ?? null);
        if ($type === 'set') {
            $normalized[$id] = is_array($value) ? array_values(array_unique($value)) : [];
        } elseif ($type === 'boolean') {
            $normalized[$id] = (bool) $value;
        } elseif ($type === 'number' || $type === 'time') {
            $normalized[$id] = $value !== null && $value !== '' ? (float) $value : null;
        } elseif ($type === 'text' || $type === 'textarea') {
            if ($value === null) {
                $normalized[$id] = null;
            } else {
                $stringValue = (string) $value;
                $normalized[$id] = $stringValue === '' ? null : $stringValue;
            }
        } else {
            $normalized[$id] = $value;
        }
    }
    return $normalized;
}

function judge_normalize_component_values(array $components, array $values): array
{
    $normalized = [];
    foreach ($components as $component) {
        $id = $component['id'] ?? null;
        if (!$id) {
            continue;
        }
        $normalized[$id] = $values[$id] ?? null;
    }
    return $normalized;
}

function judge_parse_fields(array $definitions, array $payload): array
{
    $parsed = [];
    foreach ($definitions as $definition) {
        $id = $definition['id'] ?? null;
        if (!$id) {
            continue;
        }
        $type = $definition['type'] ?? 'number';
        $raw = $payload[$id] ?? null;
        if ($type === 'set') {
            $parsed[$id] = is_array($raw) ? array_values(array_unique(array_filter($raw, static fn($item) => $item !== '' && $item !== null))) : [];
        } elseif ($type === 'boolean') {
            $parsed[$id] = !empty($raw);
        } elseif ($type === 'number' || $type === 'time') {
            $parsed[$id] = $raw === '' || $raw === null ? null : (float) $raw;
        } elseif ($type === 'text' || $type === 'textarea') {
            if ($raw === null) {
                $parsed[$id] = null;
            } else {
                $stringValue = (string) $raw;
                $parsed[$id] = $stringValue === '' ? null : $stringValue;
            }
        } else {
            $parsed[$id] = $raw;
        }
    }
    return $parsed;
}

function judge_parse_components(array $definitions, array $payload): array
{
    $parsed = [];
    foreach ($definitions as $definition) {
        $id = $definition['id'] ?? null;
        if (!$id) {
            continue;
        }
        $value = $payload[$id] ?? null;
        $parsed[$id] = $value === '' || $value === null ? null : (float) $value;
    }
    return $parsed;
}
