<?php
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/auth.php';

use App\Sponsors\SponsorRepository;
use Throwable;

$activeEvent = event_active();
if ($activeEvent) {
    $params = ['event_id' => (int) $activeEvent['id']];
    $current = db_first('SELECT si.position, si.start_number_display, p.display_name AS rider, h.name AS horse, c.label FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN parties p ON p.id = e.party_id JOIN horses h ON h.id = e.horse_id JOIN classes c ON c.id = si.class_id WHERE si.state = "running" AND e.event_id = :event_id ORDER BY si.updated_at DESC LIMIT 1', $params);
    $next = db_all('SELECT si.position, si.start_number_display, p.display_name AS rider, h.name AS horse FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN parties p ON p.id = e.party_id JOIN horses h ON h.id = e.horse_id WHERE si.state = "scheduled" AND e.event_id = :event_id ORDER BY si.planned_start ASC, si.position ASC LIMIT 5', $params);
    $top = db_all('SELECT r.total, p.display_name AS rider, h.name AS horse FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id JOIN parties p ON p.id = e.party_id JOIN horses h ON h.id = e.horse_id WHERE r.status = "released" AND e.event_id = :event_id ORDER BY r.total DESC LIMIT 3', $params);
} else {
    $current = null;
    $next = [];
    $top = [];
}
$sponsorMessages = [];
$sponsorText = t('display.defaults.sponsor');
try {
    $sponsorRepository = new SponsorRepository(app_pdo());
    $eventId = $activeEvent['id'] ?? null;
    $messages = $sponsorRepository->tickerMessages($eventId ? (int) $eventId : null);
    if ($messages) {
        $sponsorMessages = $messages;
        $sponsorText = $messages[0];
    }
} catch (Throwable) {
    // Fallback handled below
}

if (!$sponsorMessages) {
    $legacySponsor = db_first('SELECT payload FROM notifications WHERE type = "sponsor" ORDER BY id DESC LIMIT 1');
    if ($legacySponsor) {
        try {
            $payload = json_decode($legacySponsor['payload'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
            $text = isset($payload['text']) && is_string($payload['text']) ? trim($payload['text']) : '';
            if ($text !== '') {
                $sponsorText = $text;
            }
        } catch (Throwable) {
            // ignore legacy decode errors
        }
    }
}

$view = app_view();
echo $view->render('display.tpl', [
    'title' => t('display.title'),
    'locale' => current_locale(),
    'current' => $current,
    'next' => $next,
    'top' => $top,
    'sponsor' => $sponsorText,
    'sponsorMessages' => $sponsorMessages,
]);
