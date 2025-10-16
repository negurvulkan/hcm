<?php
require __DIR__ . '/auth.php';

$user = auth_require('events');
$isAdmin = auth_is_admin($user);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editEvent = $editId ? db_first('SELECT * FROM events WHERE id = :id', ['id' => $editId]) : null;
if ($editEvent && $editEvent['venues']) {
    $editEvent['venues_list'] = json_decode($editEvent['venues'], true, 512, JSON_THROW_ON_ERROR) ?: [];
}
if ($editEvent && !empty($editEvent['start_number_rules'])) {
    $editEvent['start_number_rules_text'] = $editEvent['start_number_rules'];
}

$simulation = [];
$simulationError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: events.php');
        exit;
    }

    $action = $_POST['action'] ?? ($_POST['default_action'] ?? 'create');

    if ($action === 'simulate_rules') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $venuesInput = (string) ($_POST['venues'] ?? '');
        $rulesInput = trim((string) ($_POST['start_number_rules'] ?? ''));
        $editEvent = [
            'id' => $eventId,
            'title' => $title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'venues_list' => array_filter(array_map('trim', explode(',', $venuesInput))),
            'start_number_rules_text' => $rulesInput,
        ];
        if ($rulesInput === '') {
            $simulationError = 'Regel-JSON angeben.';
        } else {
            try {
                $rulesDecoded = json_decode($rulesInput, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($rulesDecoded)) {
                    $simulationError = 'Regel-JSON muss ein Objekt sein.';
                } else {
                    $simulation = events_simulate_numbers($rulesDecoded, 20);
                }
            } catch (\JsonException $e) {
                $simulationError = 'Regel-JSON ungültig: ' . $e->getMessage();
            }
        }
    }

    if (in_array($action, ['set_active', 'deactivate'], true)) {
        if (!$isAdmin) {
            flash('error', 'Nur Administratoren können Turniere aktivieren oder deaktivieren.');
            header('Location: events.php');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId) {
            if ($action === 'set_active') {
                db_execute('UPDATE events SET is_active = 0 WHERE is_active = 1');
                db_execute('UPDATE events SET is_active = 1 WHERE id = :id', ['id' => $eventId]);
                event_active(true);
                flash('success', 'Aktives Turnier aktualisiert.');
            } else {
                db_execute('UPDATE events SET is_active = 0 WHERE id = :id', ['id' => $eventId]);
                event_active(true);
                flash('success', 'Turnier deaktiviert.');
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
                flash('error', 'Turnier besitzt Prüfungen und kann nicht gelöscht werden.');
            } else {
                db_execute('DELETE FROM events WHERE id = :id', ['id' => $eventId]);
                event_active(true);
                flash('success', 'Turnier gelöscht.');
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
        $venues = array_filter(array_map('trim', explode(',', (string) ($_POST['venues'] ?? ''))));
        $rulesInput = trim((string) ($_POST['start_number_rules'] ?? ''));
        $rulesEncoded = null;
        if ($rulesInput !== '') {
            try {
                $decodedRules = json_decode($rulesInput, true, 512, JSON_THROW_ON_ERROR);
                $rulesEncoded = json_encode($decodedRules, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            } catch (\JsonException $e) {
                flash('error', 'Regel-JSON ungültig: ' . $e->getMessage());
                header('Location: events.php' . ($eventId ? '?edit=' . $eventId : ''));
                exit;
            }
        }

        if ($title === '') {
            flash('error', 'Titel erforderlich.');
        } else {
            $payload = [
                'title' => $title,
                'start' => $start ?: null,
                'end' => $end ?: null,
                'venues' => $venues ? json_encode(array_values($venues), JSON_THROW_ON_ERROR) : null,
                'rules' => $rulesEncoded,
            ];

            if ($action === 'update' && $eventId > 0) {
                db_execute(
                    'UPDATE events SET title = :title, start_date = :start, end_date = :end, venues = :venues, start_number_rules = :rules WHERE id = :id',
                    $payload + ['id' => $eventId]
                );
                flash('success', 'Turnier aktualisiert.');
            } else {
                db_execute(
                    'INSERT INTO events (title, start_date, end_date, venues, start_number_rules) VALUES (:title, :start, :end, :venues, :rules)',
                    $payload
                );
                flash('success', 'Turnier angelegt.');
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
foreach ($events as &$event) {
    $event['venues_list'] = $event['venues'] ? json_decode($event['venues'], true, 512, JSON_THROW_ON_ERROR) : [];
}
unset($event);

render_page('events.tpl', [
    'title' => 'Turniere',
    'page' => 'events',
    'events' => $events,
    'editEvent' => $editEvent,
    'isAdmin' => $isAdmin,
    'simulation' => $simulation,
    'simulationError' => $simulationError,
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
