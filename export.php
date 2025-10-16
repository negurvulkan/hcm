<?php
require __DIR__ . '/auth.php';

auth_require('export');

$type = $_GET['type'] ?? null;
$classId = (int) ($_GET['class_id'] ?? 0);

if ($type) {
    switch ($type) {
        case 'entries':
            $rows = db_all('SELECT e.id, p.name AS rider, h.name AS horse, c.label AS class_label, e.status FROM entries e JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id JOIN classes c ON c.id = e.class_id ORDER BY e.created_at DESC');
            outputCsv('entries.csv', ['ID', 'Reiter', 'Pferd', 'Prüfung', 'Status'], $rows);
            break;
        case 'starters':
            if (!$classId) {
                flash('error', 'Klasse wählen.');
                header('Location: export.php');
                exit;
            }
            $rows = db_all('SELECT si.position, p.name AS rider, h.name AS horse FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
            outputCsv('starters.csv', ['Pos', 'Reiter', 'Pferd'], $rows);
            break;
        case 'results_json':
            if (!$classId) {
                flash('error', 'Klasse wählen.');
                header('Location: export.php');
                exit;
            }
            $rows = db_all('SELECT r.total, r.status, p.name AS rider, h.name AS horse FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id', ['class_id' => $classId]);
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="results.json"');
            echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
    }
}

$classes = db_all('SELECT id, label FROM classes ORDER BY label');

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
