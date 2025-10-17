<?php
require __DIR__ . '/auth.php';

$user = auth_require('print');
$isAdmin = auth_is_admin($user);
$activeEvent = event_active();

if (isset($_GET['download'])) {
    if (!class_exists('Dompdf\\Dompdf') && is_file(__DIR__ . '/vendor/autoload.php')) {
        require __DIR__ . '/vendor/autoload.php';
    }
    $type = $_GET['download'];
    $classId = (int) ($_GET['class_id'] ?? 0);
    $view = app_view();
    $html = '';
    $filename = $type . '.pdf';

    $class = null;
    if (in_array($type, ['startlist', 'judge', 'results', 'certificate'], true)) {
        if ($classId <= 0) {
            flash('error', t('print.validation.class_required'));
            header('Location: print.php');
            exit;
        }
        $class = db_first('SELECT c.*, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]);
        if (!$class || !event_accessible($user, (int) $class['event_id'])) {
            flash('error', t('print.validation.forbidden_event'));
            header('Location: print.php');
            exit;
        }
    }

    switch ($type) {
        case 'startlist':
            $data = db_all('SELECT si.position, si.start_number_display, si.start_number_raw, pr.display_name AS rider, h.name AS horse FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN parties pr ON pr.id = e.party_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
            $html = $view->render('print/startlist.tpl', ['items' => $data]);
            $filename = t('print.files.startlist');
            break;
        case 'judge':
            $rule = $class['rules_json'] ? json_decode($class['rules_json'], true, 512, JSON_THROW_ON_ERROR) : [];
            $html = $view->render('print/judge.tpl', ['class' => $class, 'rule' => $rule]);
            $filename = t('print.files.judge');
            break;
        case 'results':
            $rows = db_all('SELECT r.id, r.total, r.rank, r.penalties, r.breakdown_json, r.rule_snapshot, r.engine_version, r.tiebreak_path, r.eliminated, r.status, pr.display_name AS rider, h.name AS horse, si.start_number_display, si.start_number_raw FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id JOIN parties pr ON pr.id = e.party_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY r.rank IS NULL, r.rank ASC, r.total DESC', ['class_id' => $classId]);
            foreach ($rows as &$row) {
                try {
                    $row['breakdown'] = $row['breakdown_json'] ? json_decode($row['breakdown_json'], true, 512, JSON_THROW_ON_ERROR) : [];
                } catch (\JsonException $e) {
                    $row['breakdown'] = ['error' => $e->getMessage()];
                }
                try {
                    $row['rule_snapshot'] = $row['rule_snapshot'] ? json_decode($row['rule_snapshot'], true, 512, JSON_THROW_ON_ERROR) : null;
                } catch (\JsonException $e) {
                    $row['rule_snapshot'] = ['error' => $e->getMessage()];
                }
                try {
                    $row['tiebreak_path'] = $row['tiebreak_path'] ? json_decode($row['tiebreak_path'], true, 512, JSON_THROW_ON_ERROR) : [];
                } catch (\JsonException $e) {
                    $row['tiebreak_path'] = [];
                }
                $row['unit'] = null;
                if (!empty($row['rule_snapshot']['json'])) {
                    try {
                        $decodedRule = json_decode($row['rule_snapshot']['json'], true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($decodedRule)) {
                            $row['rule_details'] = $decodedRule;
                            $row['unit'] = $decodedRule['output']['unit'] ?? null;
                        }
                    } catch (\JsonException $e) {
                        $row['rule_details_error'] = $e->getMessage();
                    }
                }
            }
            unset($row);
            $html = $view->render('print/results.tpl', [
                'items' => $rows,
                'class' => $class,
            ]);
            $filename = t('print.files.results');
            break;
        case 'certificate':
            $data = db_first('SELECT pr.display_name AS rider, h.name AS horse, r.total FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id JOIN parties pr ON pr.id = e.party_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY r.total DESC LIMIT 1', ['class_id' => $classId]);
            $html = $view->render('print/certificate.tpl', ['winner' => $data]);
            $filename = t('print.files.certificate');
            break;
        default:
            flash('error', t('print.validation.unknown_type'));
            header('Location: print.php');
            exit;
    }

    if (!class_exists('Dompdf\\Dompdf')) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<p>' . htmlspecialchars(t('print.errors.dompdf_missing'), ENT_QUOTES, 'UTF-8') . '</p>';
        echo $html;
        exit;
    }

    $dompdf = new Dompdf\Dompdf(['isRemoteEnabled' => true]);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4');
    $dompdf->render();
    $dompdf->stream($filename);
    exit;
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

render_page('print.tpl', [
    'title' => t('print.title'),
    'page' => 'print',
    'classes' => $classes,
]);
