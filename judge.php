<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/audit.php';

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
        'title' => 'Richten',
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
        flash('error', 'Keine Berechtigung für dieses Turnier.');
        render_page('judge.tpl', [
            'title' => 'Richten',
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

$starts = db_all('SELECT si.id, si.position, si.state, p.name AS rider, h.name AS horse FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
if (!$starts) {
    render_page('judge.tpl', [
        'title' => 'Richten',
        'page' => 'judge',
        'classes' => $classes,
        'selectedClass' => $selectedClass,
        'start' => null,
        'scores' => [],
        'rule' => $selectedClass['rules_json'] ? json_decode($selectedClass['rules_json'], true, 512, JSON_THROW_ON_ERROR) : [],
        'result' => null,
        'starts' => [],
    ]);
    exit;
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

$rule = $selectedClass['rules_json'] ? json_decode($selectedClass['rules_json'], true, 512, JSON_THROW_ON_ERROR) : ['type' => 'dressage'];
$result = db_first('SELECT * FROM results WHERE startlist_id = :id', ['id' => $startId]);
$scores = $result ? ($result['scores_json'] ? json_decode($result['scores_json'], true, 512, JSON_THROW_ON_ERROR) : []) : [];

if ($start && $start['state'] !== 'running' && $start['state'] !== 'withdrawn') {
    $before = db_first('SELECT * FROM startlist_items WHERE id = :id', ['id' => $startId]);
    db_execute('UPDATE startlist_items SET state = :state, updated_at = :updated WHERE id = :id', [
        'state' => 'running',
        'updated' => (new \DateTimeImmutable())->format('c'),
        'id' => $startId,
    ]);
    audit_log('startlist_items', $startId, 'state_change', $before, ['state' => 'running']);
    $upcoming = db_all('SELECT si.position, p.name AS rider FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id WHERE si.class_id = :class AND si.state = "scheduled" ORDER BY si.planned_start ASC, si.position ASC LIMIT 5', ['class' => $classId]);
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
        flash('error', 'CSRF ungültig.');
        header('Location: judge.php?class_id=' . $classId . '&start_id=' . $startId);
        exit;
    }

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
            flash('success', 'Wertung entfernt.');
        }
        header('Location: judge.php?class_id=' . $classId . '&start_id=' . $startId);
        exit;
    }

    $payload = $_POST['score'] ?? [];
    $scores = buildScores($rule, $payload);
    $total = calculateTotal($rule, $scores, $_POST);
    $penaltyValue = null;
    if (($rule['type'] ?? '') === 'jumping') {
        $penaltyValue = (float) ($_POST['time_penalties'] ?? 0);
    } elseif (($rule['type'] ?? '') === 'western') {
        $penaltyValue = array_sum($scores['penalties'] ?? []);
    }
    $status = isset($_POST['sign']) ? 'signed' : 'submitted';

    if ($result) {
        $before = $result;
        db_execute('UPDATE results SET scores_json = :scores, total = :total, penalties = :penalties, status = :status, signed_by = :signed_by, signed_at = :signed_at, signature_hash = :signature WHERE id = :id', [
            'scores' => json_encode($scores, JSON_THROW_ON_ERROR),
            'total' => $total,
            'penalties' => $penaltyValue,
            'status' => $status,
            'signed_by' => $status === 'signed' ? $user['name'] : null,
            'signed_at' => $status === 'signed' ? (new \DateTimeImmutable())->format('c') : null,
            'signature' => $status === 'signed' ? hash('sha256', $user['email'] . $startId . time()) : null,
            'id' => $result['id'],
        ]);
        $result = db_first('SELECT * FROM results WHERE id = :id', ['id' => $result['id']]);
        audit_log('results', (int) $result['id'], 'update', $before, $result);
    } else {
        db_execute('INSERT INTO results (startlist_id, scores_json, total, penalties, status, signed_by, signed_at, signature_hash, created_at) VALUES (:startlist_id, :scores, :total, :penalties, :status, :signed_by, :signed_at, :signature, :created)', [
            'startlist_id' => $startId,
            'scores' => json_encode($scores, JSON_THROW_ON_ERROR),
            'total' => $total,
            'penalties' => $penaltyValue,
            'status' => $status,
            'signed_by' => $status === 'signed' ? $user['name'] : null,
            'signed_at' => $status === 'signed' ? (new \DateTimeImmutable())->format('c') : null,
            'signature' => $status === 'signed' ? hash('sha256', $user['email'] . $startId . time()) : null,
            'created' => (new \DateTimeImmutable())->format('c'),
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

    flash('success', 'Wertung gespeichert.');
    header('Location: judge.php?class_id=' . $classId . '&start_id=' . $startId);
    exit;
}

render_page('judge.tpl', [
    'title' => 'Richten',
    'page' => 'judge',
    'classes' => $classes,
    'selectedClass' => $selectedClass,
    'start' => $start,
    'starts' => $starts,
    'scores' => $scores,
    'rule' => $rule,
    'result' => $result,
]);

function buildScores(array $rule, array $payload): array
{
    switch ($rule['type'] ?? 'dressage') {
        case 'jumping':
            return [
                'time' => (float) ($payload['time'] ?? 0),
                'faults' => (int) ($payload['faults'] ?? 0),
            ];
        case 'western':
            $maneuvers = [];
            foreach ($rule['maneuvers'] ?? [] as $index => $maneuver) {
                $maneuvers[$index] = (float) ($payload['maneuvers'][$index] ?? 0);
            }
            $penalties = array_map('floatval', $payload['penalties'] ?? []);
            return [
                'maneuvers' => $maneuvers,
                'penalties' => $penalties,
            ];
        default:
            $movements = [];
            foreach ($rule['movements'] ?? [] as $index => $movement) {
                $movements[$index] = (float) ($payload['movements'][$index] ?? 0);
            }
            return ['movements' => $movements];
    }
}

function calculateTotal(array $rule, array $scores, array $payload): float
{
    switch ($rule['type'] ?? 'dressage') {
        case 'jumping':
            $total = ($scores['faults'] ?? 0) + (float) ($payload['time_penalties'] ?? 0);
            return $total;
        case 'western':
            $maneuverSum = array_sum($scores['maneuvers'] ?? []);
            $penalties = array_sum($scores['penalties'] ?? []);
            return $maneuverSum - $penalties;
        default:
            $values = $scores['movements'] ?? [];
            if (!$values) {
                return 0.0;
            }
            $sum = array_sum($values);
            return $sum / count($values);
    }
}
