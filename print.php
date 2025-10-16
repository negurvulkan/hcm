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
            flash('error', 'Prüfung auswählen.');
            header('Location: print.php');
            exit;
        }
        $class = db_first('SELECT * FROM classes WHERE id = :id', ['id' => $classId]);
        if (!$class || !event_accessible($user, (int) $class['event_id'])) {
            flash('error', 'Keine Berechtigung für dieses Turnier.');
            header('Location: print.php');
            exit;
        }
    }

    switch ($type) {
        case 'startlist':
            $data = db_all('SELECT si.position, si.start_number_display, si.start_number_raw, p.name AS rider, h.name AS horse FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
            $html = $view->render('print/startlist.tpl', ['items' => $data]);
            $filename = 'startliste.pdf';
            break;
        case 'judge':
            $rule = $class['rules_json'] ? json_decode($class['rules_json'], true, 512, JSON_THROW_ON_ERROR) : [];
            $html = $view->render('print/judge.tpl', ['class' => $class, 'rule' => $rule]);
            $filename = 'richterbogen.pdf';
            break;
        case 'results':
            $data = db_all('SELECT r.total, p.name AS rider, h.name AS horse, si.start_number_display, si.start_number_raw FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY r.total DESC', ['class_id' => $classId]);
            $html = $view->render('print/results.tpl', ['items' => $data]);
            $filename = 'ergebnisliste.pdf';
            break;
        case 'certificate':
            $data = db_first('SELECT p.name AS rider, h.name AS horse, r.total FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY r.total DESC LIMIT 1', ['class_id' => $classId]);
            $html = $view->render('print/certificate.tpl', ['winner' => $data]);
            $filename = 'urkunde.pdf';
            break;
        default:
            flash('error', 'Unbekannter Typ.');
            header('Location: print.php');
            exit;
    }

    if (!class_exists('Dompdf\\Dompdf')) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<p>Dompdf ist nicht verfügbar. Bitte lokal installieren.</p>';
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
    'title' => 'Druck',
    'page' => 'print',
    'classes' => $classes,
]);
