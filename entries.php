<?php
require __DIR__ . '/auth.php';

$user = auth_require('entries');
$persons = db_all('SELECT id, name FROM persons ORDER BY name');
$horses = db_all('SELECT id, name FROM horses ORDER BY name');
$classesList = db_all('SELECT c.id, c.label, e.title FROM classes c JOIN events e ON e.id = c.event_id ORDER BY e.title, c.label');

if (!isset($_SESSION['entries_import'])) {
    $_SESSION['entries_import'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: entries.php');
        exit;
    }

    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $personId = (int) ($_POST['person_id'] ?? 0);
        $horseId = (int) ($_POST['horse_id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        $status = in_array($_POST['status'] ?? 'open', ['open', 'paid'], true) ? $_POST['status'] : 'open';

        if (!$personId || !$horseId || !$classId) {
            flash('error', 'Bitte Reiter, Pferd und Prüfung wählen.');
        } else {
            db_execute(
                'INSERT INTO entries (event_id, class_id, person_id, horse_id, status, fee_paid_at, created_at) VALUES ((SELECT event_id FROM classes WHERE id = :class_id), :class_id, :person_id, :horse_id, :status, :paid_at, :created)',
                [
                    'class_id' => $classId,
                    'person_id' => $personId,
                    'horse_id' => $horseId,
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? (new \DateTimeImmutable())->format('c') : null,
                    'created' => (new \DateTimeImmutable())->format('c'),
                ]
            );
            flash('success', 'Nennung gespeichert.');
        }

        header('Location: entries.php');
        exit;
    }

    if ($action === 'update_status') {
        $entryId = (int) ($_POST['entry_id'] ?? 0);
        $status = in_array($_POST['status'] ?? 'open', ['open', 'paid'], true) ? $_POST['status'] : 'open';
        if ($entryId) {
            db_execute('UPDATE entries SET status = :status, fee_paid_at = :paid_at WHERE id = :id', [
                'status' => $status,
                'paid_at' => $status === 'paid' ? (new \DateTimeImmutable())->format('c') : null,
                'id' => $entryId,
            ]);
            flash('success', 'Status aktualisiert.');
        }
        header('Location: entries.php');
        exit;
    }

    if ($action === 'preview_import' && isset($_FILES['csv']) && is_uploaded_file($_FILES['csv']['tmp_name'])) {
        $rows = [];
        if (($handle = fopen($_FILES['csv']['tmp_name'], 'rb')) !== false) {
            while (($data = fgetcsv($handle, 2000, ';')) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        if (!$rows) {
            flash('error', 'CSV konnte nicht gelesen werden.');
            header('Location: entries.php');
            exit;
        }
        $token = bin2hex(random_bytes(8));
        $_SESSION['entries_import'][$token] = $rows;
        flash('success', 'CSV geladen – Spalten zuordnen.');
        header('Location: entries.php?import=' . $token);
        exit;
    }

    if ($action === 'apply_import') {
        $token = $_POST['import_token'] ?? '';
        $mapping = $_POST['mapping'] ?? [];
        $rows = $_SESSION['entries_import'][$token] ?? null;
        if (!$rows) {
            flash('error', 'Import-Sitzung abgelaufen.');
            header('Location: entries.php');
            exit;
        }
        $header = array_shift($rows);
        $created = 0;
        foreach ($rows as $row) {
            $data = array_combine(range(0, count($row) - 1), $row);
            $personName = $data[(int) ($mapping['person'] ?? -1)] ?? null;
            $horseName = $data[(int) ($mapping['horse'] ?? -1)] ?? null;
            $classLabel = $data[(int) ($mapping['class'] ?? -1)] ?? null;
            $status = strtolower(trim((string) ($data[(int) ($mapping['status'] ?? -1)] ?? 'open')));
            $status = in_array($status, ['paid', 'open'], true) ? $status : 'open';

            if (!$personName || !$horseName || !$classLabel) {
                continue;
            }

            $person = db_first('SELECT id FROM persons WHERE name = :name', ['name' => $personName]);
            $horse = db_first('SELECT id FROM horses WHERE name = :name', ['name' => $horseName]);
            $class = db_first('SELECT id, event_id FROM classes WHERE label = :label', ['label' => $classLabel]);

            if (!$person || !$horse || !$class) {
                continue;
            }

            db_execute(
                'INSERT INTO entries (event_id, class_id, person_id, horse_id, status, fee_paid_at, created_at) VALUES (:event_id, :class_id, :person_id, :horse_id, :status, :paid_at, :created)',
                [
                    'event_id' => $class['event_id'],
                    'class_id' => $class['id'],
                    'person_id' => $person['id'],
                    'horse_id' => $horse['id'],
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? (new \DateTimeImmutable())->format('c') : null,
                    'created' => (new \DateTimeImmutable())->format('c'),
                ]
            );
            $created++;
        }

        unset($_SESSION['entries_import'][$token]);
        flash('success', $created . ' Nennungen importiert.');
        header('Location: entries.php');
        exit;
    }
}

$entries = db_all('SELECT e.id, e.status, p.name AS rider, h.name AS horse, c.label AS class_label, e.created_at FROM entries e JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id JOIN classes c ON c.id = e.class_id ORDER BY e.created_at DESC LIMIT 100');
$importToken = $_GET['import'] ?? '';
$importRows = $importToken && isset($_SESSION['entries_import'][$importToken]) ? $_SESSION['entries_import'][$importToken] : null;
$importHeader = $importRows ? $importRows[0] : [];

render_page('entries.tpl', [
    'title' => 'Nennungen',
    'page' => 'entries',
    'persons' => $persons,
    'horses' => $horses,
    'classes' => $classesList,
    'entries' => $entries,
    'importToken' => $importToken,
    'importHeader' => $importHeader,
]);
