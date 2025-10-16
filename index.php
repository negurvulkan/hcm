<?php
require __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Rbac;
use App\Services\PdfService;

$configFile = __DIR__ . '/config/app.php';
if (!is_file($configFile)) {
    header('Location: setup.php');
    exit;
}

$pdo = App::get('pdo');
$view = App::get('view');
$auth = new Auth($pdo);

$page = $_GET['page'] ?? 'dashboard';

if ($page === 'logout') {
    $auth->logout();
    header('Location: index.php');
    exit;
}

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_token'] ?? null;
    if (!Csrf::check($token)) {
        $error = 'Sicherheitsprüfung fehlgeschlagen.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if (!$auth->attempt($email, $password)) {
            $error = 'Login fehlgeschlagen. Bitte Zugangsdaten prüfen.';
        } else {
            header('Location: index.php');
            exit;
        }
    }
}

if (!$auth->check()) {
    echo $view->render('login.tpl.php', [
        'csrfToken' => Csrf::token(),
        'error' => $error ?? null,
        'installed' => isset($_GET['installed']),
    ]);
    exit;
}

$user = $auth->user();
$menu = Rbac::menuFor($user['role']);

switch ($page) {
    case 'stammdaten':
        echo $view->render('stammdaten.tpl.php', [
            'title' => 'Stammdaten',
            'user' => $user,
            'menu' => $menu,
            'persons' => fetchRows($pdo, 'SELECT name, role, email FROM persons ORDER BY name LIMIT 10'),
            'horses' => fetchRows($pdo, 'SELECT name, breed, owner FROM horses ORDER BY name LIMIT 10'),
        ]);
        break;
    case 'pruefungen':
        echo $view->render('pruefungen.tpl.php', [
            'title' => 'Prüfungen & Arenen',
            'user' => $user,
            'menu' => $menu,
            'arenen' => fetchRows($pdo, 'SELECT arenas.name AS arena, tournaments.name AS tournament FROM arenas JOIN tournaments ON tournaments.id = arenas.tournament_id'),
        ]);
        break;
    case 'nennungen':
        echo $view->render('nennungen.tpl.php', [
            'title' => 'Nennungen (Demo)',
            'user' => $user,
            'menu' => $menu,
        ]);
        break;
    case 'zeitplan':
        echo $view->render('zeitplan.tpl.php', [
            'title' => 'Zeitplan (Demo)',
            'user' => $user,
            'menu' => $menu,
            'slots' => demoSchedule($pdo),
        ]);
        break;
    case 'helfer':
        echo $view->render('helfer.tpl.php', [
            'title' => 'Helferkoordination (Demo)',
            'user' => $user,
            'menu' => $menu,
            'shifts' => fetchRows($pdo, 'SELECT shifts.role, shifts.person_name, shifts.start_time, shifts.end_time, arenas.name AS arena FROM shifts LEFT JOIN arenas ON arenas.id = shifts.arena_id ORDER BY start_time'),
        ]);
        break;
    case 'moderation':
        echo $view->render('moderation.tpl.php', [
            'title' => 'Moderation (Demo)',
            'user' => $user,
            'menu' => $menu,
            'highlights' => demoHighlights($pdo),
        ]);
        break;
    case 'druck':
        echo $view->render('druck.tpl.php', [
            'title' => 'PDFs & Druck',
            'user' => $user,
            'menu' => $menu,
        ]);
        break;
    case 'pdf-demo':
        (new PdfService())->downloadSimplePdf('Demo-Protokoll', [
            'Turnier: Sommerturnier 2024',
            'Arena: Arena Nord',
            'Workflow: Stammdaten → Prüfungen → Druck',
            'Hinweis: Dompdf-Integration folgt in späterer Stufe.',
        ]);
        exit;
    default:
        echo $view->render('dashboard.tpl.php', [
            'title' => 'Dashboard',
            'user' => $user,
            'menu' => $menu,
            'stats' => dashboardStats($pdo),
            'installationHint' => isset($_GET['installed']),
        ]);
        break;
}

function fetchRows(\PDO $pdo, string $query): array
{
    $stmt = $pdo->query($query);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function dashboardStats(\PDO $pdo): array
{
    $counts = [];
    foreach ([
        'users' => 'SELECT COUNT(*) FROM users',
        'persons' => 'SELECT COUNT(*) FROM persons',
        'horses' => 'SELECT COUNT(*) FROM horses',
        'arenas' => 'SELECT COUNT(*) FROM arenas',
    ] as $key => $sql) {
        $counts[$key] = (int) $pdo->query($sql)->fetchColumn();
    }

    $tournament = $pdo->query('SELECT name, location, start_date, end_date FROM tournaments LIMIT 1')->fetch(PDO::FETCH_ASSOC);

    return [
        'counts' => $counts,
        'tournament' => $tournament,
    ];
}

function demoSchedule(\PDO $pdo): array
{
    $arenas = fetchRows($pdo, 'SELECT arenas.name AS arena FROM arenas');
    $schedule = [];
    $start = new \DateTimeImmutable('2024-08-10 08:00');
    foreach ($arenas as $index => $arena) {
        $schedule[] = [
            'arena' => $arena['arena'],
            'start' => $start->modify('+' . ($index * 60) . ' minutes')->format('H:i'),
            'end' => $start->modify('+' . ($index * 60 + 120) . ' minutes')->format('H:i'),
            'note' => 'Basis-Zeitfenster für Demo.'
        ];
    }
    return $schedule;
}

function demoHighlights(\PDO $pdo): array
{
    $topRiders = array_slice(fetchRows($pdo, 'SELECT name FROM persons WHERE role IN ("Reiter", "Reiterin") ORDER BY name'), 0, 3);
    return [
        'currentStarter' => $topRiders[0]['name'] ?? 'Noch kein Start',
        'nextRiders' => array_column(array_slice($topRiders, 1), 'name'),
        'sponsor' => 'Sponsor: Pferdehof Sonnental',
    ];
}
