<?php
require __DIR__ . '/auth.php';

$user = auth_require('dashboard');
$isAdmin = auth_is_admin($user);
$activeEvent = event_active();

$pdo = app_pdo();

$helperCheckins = (int) $pdo->query("SELECT COUNT(*) FROM helper_shifts WHERE checked_in_at IS NOT NULL")->fetchColumn();

$today = (new DateTimeImmutable('today'))->format('Y-m-d');

if ($isAdmin) {
    $openEntries = (int) $pdo->query("SELECT COUNT(*) FROM entries WHERE status = 'open'")->fetchColumn();
    $paidEntries = (int) $pdo->query("SELECT COUNT(*) FROM entries WHERE status = 'paid'")->fetchColumn();
    $todaySchedule = db_all('SELECT c.label, c.start_time, c.end_time FROM classes c WHERE DATE(c.start_time) = :today ORDER BY c.start_time LIMIT 4', ['today' => $today]);
    $currentStart = db_first("SELECT si.position, si.start_number_display, p.name AS rider, h.name AS horse, c.label AS class_label FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id JOIN classes c ON c.id = si.class_id WHERE si.state = 'running' ORDER BY si.updated_at DESC LIMIT 1");
    $nextStarters = db_all("SELECT si.position, si.start_number_display, p.name AS rider FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id WHERE si.state = 'scheduled' ORDER BY si.planned_start ASC, si.position ASC LIMIT 5");
} else {
    if (!$activeEvent) {
        $openEntries = 0;
        $paidEntries = 0;
        $todaySchedule = [];
        $currentStart = null;
        $nextStarters = [];
    } else {
        $eventId = (int) $activeEvent['id'];
        $countParams = ['event_id' => $eventId];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM entries WHERE status = 'open' AND event_id = :event_id");
        $stmt->execute($countParams);
        $openEntries = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM entries WHERE status = 'paid' AND event_id = :event_id");
        $stmt->execute($countParams);
        $paidEntries = (int) $stmt->fetchColumn();

        $todaySchedule = db_all('SELECT c.label, c.start_time, c.end_time FROM classes c WHERE DATE(c.start_time) = :today AND c.event_id = :event_id ORDER BY c.start_time LIMIT 4', ['today' => $today, 'event_id' => $eventId]);
        $currentStart = db_first("SELECT si.position, si.start_number_display, p.name AS rider, h.name AS horse, c.label AS class_label FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id JOIN classes c ON c.id = si.class_id WHERE si.state = 'running' AND e.event_id = :event_id ORDER BY si.updated_at DESC LIMIT 1", $countParams);
        $nextStarters = db_all("SELECT si.position, si.start_number_display, p.name AS rider FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id WHERE si.state = 'scheduled' AND e.event_id = :event_id ORDER BY si.planned_start ASC, si.position ASC LIMIT 5", $countParams);
    }
}

$tiles = [
    'office' => [
        ['title' => 'Offene Nennungen', 'value' => $openEntries, 'href' => 'entries.php'],
        ['title' => 'Bezahlte Nennungen', 'value' => $paidEntries, 'href' => 'entries.php'],
    ],
    'steward' => [
        ['title' => 'Heutiger Ablauf', 'value' => count($todaySchedule), 'href' => 'schedule.php'],
        ['title' => 'Live-Starter', 'value' => $currentStart['position'] ?? '-', 'href' => 'startlist.php'],
    ],
    'helpers' => [
        ['title' => 'Check-ins', 'value' => $helperCheckins, 'href' => 'helpers.php'],
    ],
    'judge' => [
        ['title' => 'Starters in Queue', 'value' => count($nextStarters), 'href' => 'judge.php'],
    ],
];

render_page('dashboard.tpl', [
    'title' => 'Dashboard',
    'page' => 'dashboard',
    'user' => $user,
    'tiles' => $tiles,
    'todaySchedule' => $todaySchedule,
    'currentStart' => $currentStart,
    'nextStarters' => $nextStarters,
]);
