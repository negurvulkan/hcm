<?php
require __DIR__ . '/auth.php';

use App\Party\PartyRepository;

$user = auth_require('entries');
$isAdmin = auth_is_admin($user);
$activeEvent = event_active();
$partyRepository = new PartyRepository(app_pdo());
$persons = $partyRepository->personOptions();
$horses = db_all('SELECT id, name FROM horses ORDER BY name');
$classesSql = 'SELECT c.id, c.label, e.title FROM classes c JOIN events e ON e.id = c.event_id';
if (!$isAdmin) {
    if (!$activeEvent) {
        $classesList = [];
    } else {
        $classesList = db_all($classesSql . ' WHERE e.id = :event_id ORDER BY e.title, c.label', ['event_id' => (int) $activeEvent['id']]);
    }
} else {
    $classesList = db_all($classesSql . ' ORDER BY e.title, c.label');
}

if (!isset($_SESSION['entries_import'])) {
    $_SESSION['entries_import'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('entries.validation.csrf_invalid'));
        header('Location: entries.php');
        exit;
    }

    require_write_access('entries');

    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $personId = (int) ($_POST['person_id'] ?? 0);
        $horseId = (int) ($_POST['horse_id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        $status = in_array($_POST['status'] ?? 'open', ['open', 'paid'], true) ? $_POST['status'] : 'open';

        if (!$personId || !$horseId || !$classId) {
            flash('error', t('entries.validation.selection_required'));
        } else {
            $class = db_first('SELECT id, event_id FROM classes WHERE id = :id', ['id' => $classId]);
            if (!$class || !event_accessible($user, (int) $class['event_id'])) {
                flash('error', t('entries.validation.forbidden_event'));
            } else {
                db_execute(
                    'INSERT INTO entries (event_id, class_id, party_id, horse_id, status, fee_paid_at, created_at) VALUES (:event_id, :class_id, :party_id, :horse_id, :status, :paid_at, :created)',
                    [
                        'event_id' => (int) $class['event_id'],
                        'class_id' => $classId,
                        'party_id' => $personId,
                        'horse_id' => $horseId,
                        'status' => $status,
                        'paid_at' => $status === 'paid' ? (new \DateTimeImmutable())->format('c') : null,
                        'created' => (new \DateTimeImmutable())->format('c'),
                    ]
                );
                $entryId = (int) app_pdo()->lastInsertId();
                $context = [
                    'eventId' => (int) $class['event_id'],
                    'classId' => $classId,
                    'user' => $user,
                ];
                $rule = getStartNumberRule($context);
                if (($rule['allocation']['time'] ?? 'on_startlist') === 'on_entry') {
                    assignStartNumber($context, ['entry_id' => $entryId]);
                }
                flash('success', t('entries.flash.created'));
            }
        }

        header('Location: entries.php');
        exit;
    }

    if ($action === 'update') {
        $entryId = (int) ($_POST['entry_id'] ?? 0);
        $personId = (int) ($_POST['person_id'] ?? 0);
        $horseId = (int) ($_POST['horse_id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        $status = in_array($_POST['status'] ?? 'open', ['open', 'paid'], true) ? $_POST['status'] : 'open';

        if (!$entryId || !$personId || !$horseId || !$classId) {
            flash('error', t('entries.validation.fields_required'));
        } else {
            $existing = db_first('SELECT * FROM entries WHERE id = :id', ['id' => $entryId]);
            $class = db_first('SELECT id, event_id FROM classes WHERE id = :id', ['id' => $classId]);
            if (!$class || !event_accessible($user, (int) $class['event_id'])) {
                flash('error', t('entries.validation.class_unavailable'));
            } else {
                db_execute(
                    'UPDATE entries SET event_id = :event_id, class_id = :class_id, party_id = :party_id, horse_id = :horse_id, status = :status, fee_paid_at = :paid_at WHERE id = :id',
                    [
                        'event_id' => $class['event_id'],
                        'class_id' => $classId,
                        'party_id' => $personId,
                        'horse_id' => $horseId,
                        'status' => $status,
                        'paid_at' => $status === 'paid' ? (new \DateTimeImmutable())->format('c') : null,
                        'id' => $entryId,
                    ]
                );
                if ($existing && (int) $existing['class_id'] !== $classId && !empty($existing['start_number_assignment_id'])) {
                    releaseStartNumber([
                        'id' => (int) $existing['start_number_assignment_id'],
                        'entry_id' => $entryId,
                    ], 'reclass');
                }
                $context = [
                    'eventId' => (int) $class['event_id'],
                    'classId' => $classId,
                    'user' => $user,
                ];
                $rule = getStartNumberRule($context);
                if (($rule['allocation']['time'] ?? 'on_startlist') === 'on_entry') {
                    assignStartNumber($context, ['entry_id' => $entryId]);
                }
                flash('success', t('entries.flash.updated'));
            }
        }

        header('Location: entries.php');
        exit;
    }

    if ($action === 'delete') {
        $entryId = (int) ($_POST['entry_id'] ?? 0);
        if ($entryId) {
        $entry = db_first('SELECT event_id, start_number_assignment_id FROM entries WHERE id = :id', ['id' => $entryId]);
            if (!$entry || !event_accessible($user, (int) $entry['event_id'])) {
                flash('error', t('entries.validation.forbidden_event'));
            } else {
                if (!empty($entry['start_number_assignment_id'])) {
                    releaseStartNumber([
                        'id' => (int) $entry['start_number_assignment_id'],
                        'entry_id' => $entryId,
                    ], 'withdraw');
                }
                db_execute('DELETE FROM results WHERE startlist_id IN (SELECT id FROM startlist_items WHERE entry_id = :id)', ['id' => $entryId]);
                db_execute('DELETE FROM startlist_items WHERE entry_id = :id', ['id' => $entryId]);
                db_execute('DELETE FROM entries WHERE id = :id', ['id' => $entryId]);
                flash('success', t('entries.flash.deleted'));
            }
        }
        header('Location: entries.php');
        exit;
    }

    if ($action === 'update_status') {
        $entryId = (int) ($_POST['entry_id'] ?? 0);
        $status = in_array($_POST['status'] ?? 'open', ['open', 'paid'], true) ? $_POST['status'] : 'open';
        if ($entryId) {
            $entry = db_first('SELECT event_id FROM entries WHERE id = :id', ['id' => $entryId]);
            if (!$entry || !event_accessible($user, (int) $entry['event_id'])) {
                flash('error', t('entries.validation.forbidden_event'));
            } else {
                db_execute('UPDATE entries SET status = :status, fee_paid_at = :paid_at WHERE id = :id', [
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? (new \DateTimeImmutable())->format('c') : null,
                    'id' => $entryId,
                ]);
                flash('success', t('entries.flash.status_updated'));
            }
        }
        header('Location: entries.php');
        exit;
    }

    if ($action === 'bulk') {
        $bulkAction = (string) ($_POST['bulk_action'] ?? '');
        $entryIds = array_map('intval', $_POST['entry_ids'] ?? []);
        $entryIds = array_values(array_unique(array_filter($entryIds)));
        if (!$entryIds || !$bulkAction) {
            flash('error', t('entries.validation.bulk_selection_required'));
            header('Location: entries.php');
            exit;
        }

        $placeholders = implode(', ', array_fill(0, count($entryIds), '?'));
        $entriesData = $placeholders
            ? db_all('SELECT id, event_id, start_number_assignment_id FROM entries WHERE id IN (' . $placeholders . ')', $entryIds)
            : [];
        $permitted = [];
        foreach ($entriesData as $row) {
            if (event_accessible($user, (int) $row['event_id'])) {
                $permitted[(int) $row['id']] = $row;
            }
        }

        if (!$permitted) {
            flash('error', t('entries.validation.bulk_selection_required'));
            header('Location: entries.php');
            exit;
        }

        $processed = 0;
        if (in_array($bulkAction, ['mark_paid', 'mark_open'], true)) {
            $status = $bulkAction === 'mark_paid' ? 'paid' : 'open';
            foreach ($permitted as $entryId => $row) {
                db_execute('UPDATE entries SET status = :status, fee_paid_at = :paid_at WHERE id = :id', [
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? (new \DateTimeImmutable())->format('c') : null,
                    'id' => $entryId,
                ]);
                $processed++;
            }
            if ($processed > 0) {
                $flashKey = $status === 'paid' ? 'entries.flash.bulk_marked_paid' : 'entries.flash.bulk_marked_open';
                flash('success', tn($flashKey, $processed, ['count' => $processed]));
            }
        } elseif ($bulkAction === 'delete') {
            foreach ($permitted as $entryId => $row) {
                if (!empty($row['start_number_assignment_id'])) {
                    releaseStartNumber([
                        'id' => (int) $row['start_number_assignment_id'],
                        'entry_id' => $entryId,
                    ], 'withdraw');
                }
                db_execute('DELETE FROM results WHERE startlist_id IN (SELECT id FROM startlist_items WHERE entry_id = :id)', ['id' => $entryId]);
                db_execute('DELETE FROM startlist_items WHERE entry_id = :id', ['id' => $entryId]);
                db_execute('DELETE FROM entries WHERE id = :id', ['id' => $entryId]);
                $processed++;
            }
            if ($processed > 0) {
                flash('success', tn('entries.flash.bulk_deleted', $processed, ['count' => $processed]));
            }
        }

        if ($processed === 0) {
            flash('info', t('entries.flash.bulk_noop'));
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
            flash('error', t('entries.flash.csv_error'));
            header('Location: entries.php');
            exit;
        }
        $token = bin2hex(random_bytes(8));
        $_SESSION['entries_import'][$token] = $rows;
        flash('success', t('entries.flash.csv_loaded'));
        header('Location: entries.php?import=' . $token);
        exit;
    }

    if ($action === 'apply_import') {
        $token = $_POST['import_token'] ?? '';
        $mapping = $_POST['mapping'] ?? [];
        $rows = $_SESSION['entries_import'][$token] ?? null;
        if (!$rows) {
            flash('error', t('entries.validation.import_expired'));
            header('Location: entries.php');
            exit;
        }
        $header = array_shift($rows);
        $created = 0;
        $ruleCache = [];
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

            $person = db_first('SELECT id FROM parties WHERE party_type = "person" AND display_name = :name', ['name' => $personName]);
            $horse = db_first('SELECT id FROM horses WHERE name = :name', ['name' => $horseName]);
            $class = db_first('SELECT id, event_id FROM classes WHERE label = :label', ['label' => $classLabel]);

            if (!$person || !$horse || !$class || !event_accessible($user, (int) $class['event_id'])) {
                continue;
            }

            db_execute(
                'INSERT INTO entries (event_id, class_id, party_id, horse_id, status, fee_paid_at, created_at) VALUES (:event_id, :class_id, :party_id, :horse_id, :status, :paid_at, :created)',
                [
                    'event_id' => $class['event_id'],
                    'class_id' => $class['id'],
                    'party_id' => $person['id'],
                    'horse_id' => $horse['id'],
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? (new \DateTimeImmutable())->format('c') : null,
                    'created' => (new \DateTimeImmutable())->format('c'),
                ]
            );
            $entryId = (int) app_pdo()->lastInsertId();
            $cacheKey = (int) $class['id'];
            if (!isset($ruleCache[$cacheKey])) {
                $context = [
                    'eventId' => (int) $class['event_id'],
                    'classId' => (int) $class['id'],
                    'user' => $user,
                ];
                $ruleCache[$cacheKey] = [
                    'context' => $context,
                    'rule' => getStartNumberRule($context),
                ];
            }
            if ($entryId > 0 && ($ruleCache[$cacheKey]['rule']['allocation']['time'] ?? 'on_startlist') === 'on_entry') {
                assignStartNumber($ruleCache[$cacheKey]['context'], ['entry_id' => $entryId]);
            }
            $created++;
        }

        unset($_SESSION['entries_import'][$token]);
        flash('success', tn('entries.flash.import_success', $created, ['count' => $created]));
        header('Location: entries.php');
        exit;
    }
}

$entriesSql = 'SELECT e.id, e.status, e.party_id, e.horse_id, e.class_id, pr.display_name AS rider, h.name AS horse, c.label AS class_label, e.created_at FROM entries e JOIN parties pr ON pr.id = e.party_id JOIN horses h ON h.id = e.horse_id JOIN classes c ON c.id = e.class_id';
$entriesOrder = ' ORDER BY e.created_at DESC LIMIT 100';
if (!$isAdmin) {
    if (!$activeEvent) {
        $entries = [];
    } else {
        $entries = db_all($entriesSql . ' WHERE e.event_id = :event_id' . $entriesOrder, ['event_id' => (int) $activeEvent['id']]);
    }
} else {
    $entries = db_all($entriesSql . $entriesOrder);
}
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editEntry = null;
if ($editId) {
    foreach ($entries as $entry) {
        if ((int) $entry['id'] === $editId) {
            $editEntry = $entry;
            break;
        }
    }
}
$importToken = $_GET['import'] ?? '';
$importRows = $importToken && isset($_SESSION['entries_import'][$importToken]) ? $_SESSION['entries_import'][$importToken] : null;
$importHeader = $importRows ? $importRows[0] : [];
$importPreview = $importRows ? array_slice($importRows, 0, min(6, count($importRows))) : [];
$importPreviewRemaining = $importRows ? max(count($importRows) - count($importPreview), 0) : 0;

render_page('entries.tpl', [
    'titleKey' => 'pages.entries.title',
    'page' => 'entries',
    'persons' => $persons,
    'horses' => $horses,
    'classes' => $classesList,
    'entries' => $entries,
    'importToken' => $importToken,
    'importHeader' => $importHeader,
    'importPreview' => $importPreview,
    'importPreviewRemaining' => $importPreviewRemaining,
    'editEntry' => $editEntry,
    'extraScripts' => ['public/assets/js/entries.js'],
]);
