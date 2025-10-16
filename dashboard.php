<?php
require __DIR__ . '/auth.php';

$user = auth_require('dashboard');
$isAdmin = auth_is_admin($user);
$activeEvent = event_active();

$pdo = app_pdo();

$helperCheckins = (int) $pdo->query("SELECT COUNT(*) FROM helper_shifts WHERE checked_in_at IS NOT NULL")->fetchColumn();
$instanceConfig = instance_config();
$instanceMeta = instance_view_context();
$peerInfo = $instanceMeta['peer'] ?? [];
$lastDryRun = $instanceConfig->lastDryRun();
$localCounts = $instanceConfig->collectLocalCounts();
$localEntriesCount = $localCounts['counts']['entries'] ?? 0;
$remoteEntriesCount = $lastDryRun['remote']['entries'] ?? 0;
$lastSyncNote = t('dashboard.tiles.sync.note', [
    'local' => $localEntriesCount,
    'remote' => $remoteEntriesCount,
]);
$adminTiles = [
    [
        'title' => t('dashboard.tiles.peer_connection.title'),
        'value' => $peerInfo['status'] === 'ok'
            ? t('dashboard.tiles.peer_connection.connected')
            : ($peerInfo['status'] === 'error'
                ? t('dashboard.tiles.peer_connection.error')
                : t('dashboard.tiles.peer_connection.pending')),
        'value_class' => $peerInfo['status'] === 'ok' ? 'text-success' : ($peerInfo['status'] === 'error' ? 'text-danger' : 'text-muted'),
        'note' => $peerInfo['formatted_checked_at'] ?? ($peerInfo['message'] ?? t('dashboard.tiles.peer_connection.note_missing')),
        'href' => 'instance.php',
    ],
    [
        'title' => t('dashboard.tiles.write_state.title'),
        'value' => $instanceMeta['read_only'] ? t('layout.read_only') : t('layout.writeable'),
        'value_class' => $instanceMeta['read_only'] ? 'text-warning' : 'text-success',
        'note' => $instanceMeta['mode_label'] ?? '-',
        'href' => 'instance.php',
    ],
    [
        'title' => t('dashboard.tiles.sync.title'),
        'value' => $lastDryRun['formatted_timestamp'] ?? t('dashboard.tiles.sync.empty'),
        'value_class' => !empty($lastDryRun['timestamp']) ? 'text-primary' : 'text-muted',
        'note' => $lastSyncNote,
        'href' => 'instance.php',
    ],
];

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
    'admin' => $adminTiles,
    'office' => [
        ['title' => t('dashboard.tiles.office.open_entries'), 'value' => $openEntries, 'href' => 'entries.php'],
        ['title' => t('dashboard.tiles.office.paid_entries'), 'value' => $paidEntries, 'href' => 'entries.php'],
    ],
    'steward' => [
        ['title' => t('dashboard.tiles.steward.today_schedule'), 'value' => count($todaySchedule), 'href' => 'schedule.php'],
        ['title' => t('dashboard.tiles.steward.live_position'), 'value' => $currentStart['position'] ?? '-', 'href' => 'startlist.php'],
    ],
    'helpers' => [
        ['title' => t('dashboard.tiles.helpers.checkins'), 'value' => $helperCheckins, 'href' => 'helpers.php'],
    ],
    'judge' => [
        ['title' => t('dashboard.tiles.judge.queue'), 'value' => count($nextStarters), 'href' => 'judge.php'],
    ],
];

render_page('dashboard.tpl', [
    'titleKey' => 'dashboard.title',
    'page' => 'dashboard',
    'user' => $user,
    'tiles' => $tiles,
    'todaySchedule' => $todaySchedule,
    'currentStart' => $currentStart,
    'nextStarters' => $nextStarters,
]);
