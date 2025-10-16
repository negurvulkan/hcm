<?php
require __DIR__ . '/auth.php';

use App\Setup\Installer;

$user = auth_require('classes');
$events = db_all('SELECT id, title FROM events ORDER BY title');
$presets = [
    'dressage' => Installer::dressagePreset(),
    'jumping' => Installer::jumpingPreset(),
    'western' => Installer::westernPreset(),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', 'CSRF ungültig.');
        header('Location: classes.php');
        exit;
    }

    $classId = (int) ($_POST['class_id'] ?? 0);
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $label = trim((string) ($_POST['label'] ?? ''));
    $arena = trim((string) ($_POST['arena'] ?? ''));
    $start = trim((string) ($_POST['start_time'] ?? ''));
    $end = trim((string) ($_POST['end_time'] ?? ''));
    $maxStarters = (int) ($_POST['max_starters'] ?? 0) ?: null;
    $judges = array_filter(array_map('trim', explode(',', (string) ($_POST['judges'] ?? ''))));
    $rulesRaw = (string) ($_POST['rules_json'] ?? '');
    $tiebreakers = array_filter(array_map('trim', explode(',', (string) ($_POST['tiebreakers'] ?? ''))));

    if (!$eventId || $label === '') {
        flash('error', 'Event und Bezeichnung angeben.');
        header('Location: classes.php');
        exit;
    }

    $rules = null;
    if ($rulesRaw !== '') {
        try {
            $rules = json_decode($rulesRaw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            flash('error', 'Regeln enthalten kein gültiges JSON.');
            header('Location: classes.php');
            exit;
        }
    }

    $data = [
        'event_id' => $eventId,
        'label' => $label,
        'arena' => $arena ?: null,
        'start_time' => $start ?: null,
        'end_time' => $end ?: null,
        'max_starters' => $maxStarters,
        'judge_assignments' => $judges ? json_encode(array_values($judges), JSON_THROW_ON_ERROR) : null,
        'rules_json' => $rules ? json_encode($rules, JSON_THROW_ON_ERROR) : null,
        'tiebreaker_json' => $tiebreakers ? json_encode(array_values($tiebreakers), JSON_THROW_ON_ERROR) : null,
    ];

    if ($classId > 0) {
        db_execute(
            'UPDATE classes SET event_id = :event_id, label = :label, arena = :arena, start_time = :start_time, end_time = :end_time, max_starters = :max_starters, judge_assignments = :judge_assignments, rules_json = :rules_json, tiebreaker_json = :tiebreaker_json WHERE id = :id',
            $data + ['id' => $classId]
        );
        flash('success', 'Prüfung aktualisiert.');
    } else {
        db_execute(
            'INSERT INTO classes (event_id, label, arena, start_time, end_time, max_starters, judge_assignments, rules_json, tiebreaker_json) VALUES (:event_id, :label, :arena, :start_time, :end_time, :max_starters, :judge_assignments, :rules_json, :tiebreaker_json)',
            $data
        );
        flash('success', 'Prüfung angelegt.');
    }

    header('Location: classes.php');
    exit;
}

$sql = 'SELECT c.*, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id ORDER BY e.start_date DESC, c.start_time ASC';
$classes = db_all($sql);
foreach ($classes as &$class) {
    $class['judges'] = $class['judge_assignments'] ? json_decode($class['judge_assignments'], true, 512, JSON_THROW_ON_ERROR) : [];
    $class['rules'] = $class['rules_json'] ? json_decode($class['rules_json'], true, 512, JSON_THROW_ON_ERROR) : [];
    $class['tiebreakers'] = $class['tiebreaker_json'] ? json_decode($class['tiebreaker_json'], true, 512, JSON_THROW_ON_ERROR) : [];
}
unset($class);

render_page('classes.tpl', [
    'title' => 'Prüfungen',
    'page' => 'classes',
    'events' => $events,
    'classes' => $classes,
    'presets' => $presets,
    'extraScripts' => ['public/assets/js/classes.js'],
]);
