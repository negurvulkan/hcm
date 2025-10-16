<?php
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/auth.php';

$current = db_first('SELECT si.position, p.name AS rider, h.name AS horse, c.label FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id JOIN classes c ON c.id = si.class_id WHERE si.state = "running" ORDER BY si.updated_at DESC LIMIT 1');
$next = db_all('SELECT si.position, p.name AS rider, h.name AS horse FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE si.state = "scheduled" ORDER BY si.planned_start ASC, si.position ASC LIMIT 5');
$top = db_all('SELECT r.total, p.name AS rider, h.name AS horse FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id JOIN persons p ON p.id = e.person_id JOIN horses h ON h.id = e.horse_id WHERE r.status = "released" ORDER BY r.total DESC LIMIT 3');
$sponsor = db_first('SELECT payload FROM notifications WHERE type = "sponsor" ORDER BY id DESC LIMIT 1');

$view = app_view();
echo $view->render('display.tpl', [
    'current' => $current,
    'next' => $next,
    'top' => $top,
    'sponsor' => $sponsor ? json_decode($sponsor['payload'], true, 512, JSON_THROW_ON_ERROR)['text'] ?? '' : 'Sponsor: Lokaler Partner',
]);
