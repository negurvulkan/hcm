<?php
require __DIR__ . '/auth.php';

$user = auth_require('export');
$isAdmin = auth_is_admin($user);
$activeEvent = event_active();

$type = $_GET['type'] ?? null;
$classId = (int) ($_GET['class_id'] ?? 0);

if ($type) {
    switch ($type) {
        case 'entries':
            $entriesSql = 'SELECT e.id, p.name AS rider, h.name AS horse, c.label AS class_label, e.status, e.start_number_raw, e.start_number_display, e.start_number_allocation_entity, e.start_number_rule_snapshot FROM entries e JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id JOIN classes c ON c.id = e.class_id';
            if (!$isAdmin) {
                if (!$activeEvent) {
                    $rows = [];
                } else {
                    $rows = db_all($entriesSql . ' WHERE e.event_id = :event_id ORDER BY e.created_at DESC', ['event_id' => (int) $activeEvent['id']]);
                }
            } else {
                $rows = db_all($entriesSql . ' ORDER BY e.created_at DESC');
            }
            outputCsv('entries.csv', ['ID', 'Reiter', 'Pferd', 'Prüfung', 'Status', 'Startnummer (Raw)', 'Startnummer (Display)', 'Allocation', 'Rule Snapshot'], $rows);
            break;
        case 'starters':
            if (!$classId) {
                flash('error', 'Klasse wählen.');
                header('Location: export.php');
                exit;
            }
            $class = db_first('SELECT event_id FROM classes WHERE id = :id', ['id' => $classId]);
            if (!$class || !event_accessible($user, (int) $class['event_id'])) {
                flash('error', 'Keine Berechtigung für dieses Turnier.');
                header('Location: export.php');
                exit;
            }
            $rows = db_all('SELECT si.position, p.name AS rider, h.name AS horse, si.start_number_raw, si.start_number_display, si.start_number_allocation_entity, si.start_number_rule_snapshot FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
            outputCsv('starters.csv', ['Pos', 'Reiter', 'Pferd', 'Startnummer (Raw)', 'Startnummer (Display)', 'Allocation', 'Rule Snapshot'], $rows);
            break;
        case 'results_json':
            if (!$classId) {
                flash('error', 'Klasse wählen.');
                header('Location: export.php');
                exit;
            }
            $class = db_first('SELECT event_id FROM classes WHERE id = :id', ['id' => $classId]);
            if (!$class || !event_accessible($user, (int) $class['event_id'])) {
                flash('error', 'Keine Berechtigung für dieses Turnier.');
                header('Location: export.php');
                exit;
            }
            $rows = db_all('SELECT r.total, r.rank, r.status, r.penalties, r.breakdown_json, r.rule_snapshot, r.engine_version, r.tiebreak_path, r.eliminated, p.name AS rider, h.name AS horse, si.start_number_raw, si.start_number_display, si.start_number_allocation_entity, si.start_number_rule_snapshot FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id', ['class_id' => $classId]);
            foreach ($rows as &$row) {
                $row['breakdown'] = $row['breakdown_json'] ? json_decode($row['breakdown_json'], true, 512, JSON_THROW_ON_ERROR) : null;
                $row['rule_snapshot'] = $row['rule_snapshot'] ? json_decode($row['rule_snapshot'], true, 512, JSON_THROW_ON_ERROR) : null;
                $row['tiebreak_path'] = $row['tiebreak_path'] ? json_decode($row['tiebreak_path'], true, 512, JSON_THROW_ON_ERROR) : [];
                unset($row['breakdown_json']);
            }
            unset($row);
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="results.json"');
            echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
    }
}

$classesSql = 'SELECT id, label FROM classes';
if (!$isAdmin) {
    if (!$activeEvent) {
        $classes = [];
    } else {
        $classes = db_all($classesSql . ' WHERE event_id = :event_id ORDER BY label', ['event_id' => (int) $activeEvent['id']]);
    }
} else {
    $classes = db_all($classesSql . ' ORDER BY label');
}

render_page('export.tpl', [
    'title' => 'Export',
    'page' => 'export',
    'classes' => $classes,
]);

function outputCsv(string $filename, array $header, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $header, ';');
    foreach ($rows as $row) {
        fputcsv($out, array_values($row), ';');
    }
    fclose($out);
    exit;
}
