<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/audit.php';

$user = auth_require('results');
$classes = db_all('SELECT c.id, c.label, e.title FROM classes c JOIN events e ON e.id = c.event_id ORDER BY e.title, c.label');
if (!$classes) {
    render_page('results.tpl', [
        'title' => 'Ergebnisse',
        'page' => 'results',
        'classes' => [],
        'selectedClass' => null,
        'results' => [],
        'audits' => [],
    ]);
    exit;
}

$classId = (int) ($_GET['class_id'] ?? $classes[0]['id']);
$selectedClass = db_first('SELECT * FROM classes WHERE id = :id', ['id' => $classId]);
if (!$selectedClass) {
    $selectedClass = $classes[0];
    $classId = (int) $selectedClass['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: results.php?class_id=' . $classId);
        exit;
    }
    $action = $_POST['action'] ?? '';
    $resultId = (int) ($_POST['result_id'] ?? 0);
    if ($resultId && in_array($action, ['release', 'revoke'], true)) {
        $result = db_first('SELECT * FROM results WHERE id = :id', ['id' => $resultId]);
        if ($result) {
            $before = $result;
            $status = $action === 'release' ? 'released' : 'submitted';
            db_execute('UPDATE results SET status = :status WHERE id = :id', [
                'status' => $status,
                'id' => $resultId,
            ]);
            $updated = db_first('SELECT * FROM results WHERE id = :id', ['id' => $resultId]);
            audit_log('results', $resultId, 'status_change', $before, $updated);
            if ($status === 'released') {
                db_execute('INSERT INTO notifications (type, payload, created_at) VALUES (:type, :payload, :created)', [
                    'type' => 'results_release',
                    'payload' => json_encode([
                        'class_id' => $classId,
                        'message' => 'Ergebnisse freigegeben für ' . ($selectedClass['label'] ?? ''),
                    ], JSON_THROW_ON_ERROR),
                    'created' => (new \DateTimeImmutable())->format('c'),
                ]);
            }
            flash('success', 'Status aktualisiert.');
        }
        header('Location: results.php?class_id=' . $classId);
        exit;
    }
}

$results = db_all('SELECT r.*, p.name AS rider, h.name AS horse FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY r.status DESC, r.total DESC', ['class_id' => $classId]);
$audits = db_all('SELECT * FROM audit_log WHERE entity = "results" AND entity_id IN (SELECT r.id FROM results r JOIN startlist_items si ON si.id = r.startlist_id WHERE si.class_id = :class_id) ORDER BY id DESC LIMIT 20', ['class_id' => $classId]);

render_page('results.tpl', [
    'title' => 'Ergebnisse',
    'page' => 'results',
    'classes' => $classes,
    'selectedClass' => $selectedClass,
    'results' => $results,
    'audits' => $audits,
]);
