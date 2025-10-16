<?php
declare(strict_types=1);

use App\StartNumber\StartNumberService;

require __DIR__ . '/../app/StartNumber/StartNumberService.php';
require __DIR__ . '/../start_numbers.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

$GLOBALS['__pdo'] = $pdo;
$GLOBALS['__audit'] = [];

function db_execute(string $sql, array $params = []): bool
{
    $stmt = $GLOBALS['__pdo']->prepare($sql);
    return $stmt->execute($params);
}

function db_first(string $sql, array $params = []): ?array
{
    $stmt = $GLOBALS['__pdo']->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function db_all(string $sql, array $params = []): array
{
    $stmt = $GLOBALS['__pdo']->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function audit_log(string $entity, int $entityId, string $action, $before, $after): void
{
    $GLOBALS['__audit'][] = [$entity, $entityId, $action, $before, $after];
}

function app_pdo(): PDO
{
    return $GLOBALS['__pdo'];
}

function assertSame($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}

function assertTrue(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new RuntimeException($message ?: 'Assertion failed.');
    }
}

function expectException(callable $callback, string $contains): void
{
    try {
        $callback();
    } catch (Throwable $throwable) {
        if (str_contains($throwable->getMessage(), $contains)) {
            return;
        }
        throw new RuntimeException('Unexpected exception message: ' . $throwable->getMessage());
    }
    throw new RuntimeException('Expected exception was not thrown.');
}

$pdo->exec('CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, start_number_rules TEXT)');
$pdo->exec('CREATE TABLE classes (id INTEGER PRIMARY KEY AUTOINCREMENT, event_id INTEGER, label TEXT, arena TEXT, start_time TEXT, division TEXT, tags TEXT)');
$pdo->exec('CREATE TABLE persons (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, club_id INTEGER)');
$pdo->exec('CREATE TABLE horses (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
$pdo->exec('CREATE TABLE entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id INTEGER,
    class_id INTEGER,
    person_id INTEGER,
    horse_id INTEGER,
    status TEXT,
    start_number_assignment_id INTEGER,
    start_number_raw INTEGER,
    start_number_display TEXT,
    start_number_rule_snapshot TEXT,
    start_number_allocation_entity TEXT,
    start_number_locked_at TEXT,
    created_at TEXT
)');
$pdo->exec('CREATE TABLE startlist_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER,
    entry_id INTEGER,
    position INTEGER,
    state TEXT,
    planned_start TEXT,
    start_number_assignment_id INTEGER,
    start_number_raw INTEGER,
    start_number_display TEXT,
    start_number_rule_snapshot TEXT,
    start_number_allocation_entity TEXT,
    start_number_locked_at TEXT,
    note TEXT,
    created_at TEXT,
    updated_at TEXT
)');
$pdo->exec('CREATE TABLE start_number_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id INTEGER NOT NULL,
    class_id INTEGER,
    arena TEXT,
    day TEXT,
    scope_key TEXT NOT NULL,
    rule_scope TEXT NOT NULL,
    rule_snapshot TEXT NOT NULL,
    allocation_entity TEXT NOT NULL,
    allocation_time TEXT NOT NULL,
    subject_type TEXT NOT NULL,
    subject_key TEXT NOT NULL,
    subject_payload TEXT,
    rider_id INTEGER,
    horse_id INTEGER,
    club_id INTEGER,
    start_number_raw INTEGER NOT NULL,
    start_number_display TEXT NOT NULL,
    status TEXT NOT NULL,
    locked_at TEXT,
    released_at TEXT,
    release_reason TEXT,
    created_by TEXT,
    created_at TEXT NOT NULL
)');

// Seed shared data
$pdo->exec("INSERT INTO persons (id, name, club_id) VALUES (1, 'Rider One', 10), (2, 'Rider Two', 20)");
$pdo->exec("INSERT INTO horses (id, name) VALUES (1, 'Horse A'), (2, 'Horse B')");

$classicRule = [
    'mode' => 'classic',
    'scope' => 'class',
    'sequence' => ['start' => 1, 'step' => 1, 'range' => [1, 3], 'reset' => 'per_class'],
    'format' => ['prefix' => '', 'width' => 2, 'suffix' => '', 'separator' => ''],
    'allocation' => ['entity' => 'start', 'time' => 'on_startlist', 'reuse' => 'never', 'lock_after' => 'start_called'],
    'constraints' => ['unique_per' => 'tournament', 'blocklists' => ['2'], 'club_spacing' => 0, 'horse_cooldown_min' => 0],
    'overrides' => [
        ['if' => ['division' => 'Youth'], 'sequence' => ['start' => 50, 'range' => [50, 60]], 'format' => ['prefix' => 'Y-', 'width' => 3]]
    ],
];

db_execute('INSERT INTO events (id, title, start_number_rules) VALUES (1, "Classic", :rules)', [
    'rules' => json_encode($classicRule, JSON_THROW_ON_ERROR),
]);
$pdo->exec("INSERT INTO classes (id, event_id, label, arena, start_time, division) VALUES (1, 1, 'Class A', 'Arena 1', '2024-08-17T08:00:00', 'Open')");
$pdo->exec("INSERT INTO classes (id, event_id, label, arena, start_time, division) VALUES (2, 1, 'Class B', 'Arena 2', '2024-08-18T09:00:00', 'Youth')");

$now = (new DateTimeImmutable())->format('c');
db_execute('INSERT INTO entries (id, event_id, class_id, person_id, horse_id, status, created_at) VALUES (1, 1, 1, 1, 1, "open", :created)', ['created' => $now]);
db_execute('INSERT INTO entries (id, event_id, class_id, person_id, horse_id, status, created_at) VALUES (2, 1, 1, 2, 2, "open", :created)', ['created' => $now]);
db_execute('INSERT INTO entries (id, event_id, class_id, person_id, horse_id, status, created_at) VALUES (3, 1, 2, 1, 1, "open", :created)', ['created' => $now]);

$contextA = ['eventId' => 1, 'classId' => 1, 'user' => ['name' => 'Test']];
assignStartNumber($contextA, ['entry_id' => 1]);
assignStartNumber($contextA, ['entry_id' => 2]);

$entry1 = db_first('SELECT start_number_display FROM entries WHERE id = 1');
$entry2 = db_first('SELECT start_number_display, start_number_raw FROM entries WHERE id = 2');
assertSame('01', $entry1['start_number_display'], 'First classic start number mismatch.');
assertSame('03', $entry2['start_number_display'], 'Blocklist should skip 02.');
assertSame(3, (int) $entry2['start_number_raw']);

expectException(function () use ($contextA) {
    db_execute('INSERT INTO entries (id, event_id, class_id, person_id, horse_id, status, created_at) VALUES (4, 1, 1, 1, 2, "open", :created)', ['created' => (new DateTimeImmutable())->format('c')]);
    assignStartNumber($contextA, ['entry_id' => 4]);
}, 'Startnummernbereich erschöpft');

$contextB = ['eventId' => 1, 'classId' => 2, 'user' => ['name' => 'Test']];
assignStartNumber($contextB, ['entry_id' => 3]);
$entry3 = db_first('SELECT start_number_display FROM entries WHERE id = 3');
assertSame('Y-050', $entry3['start_number_display'], 'Override for Youth division failed.');

$westernRule = [
    'mode' => 'western',
    'scope' => 'tournament',
    'sequence' => ['start' => 100, 'step' => 1, 'range' => [100, 110], 'reset' => 'never'],
    'format' => ['prefix' => 'W', 'width' => 3, 'suffix' => '', 'separator' => '-'],
    'allocation' => ['entity' => 'pair', 'time' => 'on_entry', 'reuse' => 'session', 'lock_after' => 'sign_off'],
    'constraints' => ['unique_per' => 'tournament', 'blocklists' => [], 'club_spacing' => 0, 'horse_cooldown_min' => 0],
    'overrides' => [
        ['if' => ['arena' => 'Trail'], 'sequence' => ['start' => 300, 'range' => [300, 310]], 'format' => ['prefix' => 'TR', 'width' => 2]]
    ],
];

db_execute('INSERT INTO events (id, title, start_number_rules) VALUES (2, "Western", :rules)', [
    'rules' => json_encode($westernRule, JSON_THROW_ON_ERROR),
]);
$pdo->exec("INSERT INTO classes (id, event_id, label, arena, start_time, division) VALUES (3, 2, 'Trail Youth', 'Trail', '2024-08-17T10:00:00', 'Youth')");
$pdo->exec("INSERT INTO classes (id, event_id, label, arena, start_time, division) VALUES (4, 2, 'Pleasure Open', 'Arena 3', '2024-08-18T11:00:00', 'Open')");

db_execute('INSERT INTO entries (id, event_id, class_id, person_id, horse_id, status, created_at) VALUES (5, 2, 3, 1, 1, "open", :created)', ['created' => $now]);
db_execute('INSERT INTO entries (id, event_id, class_id, person_id, horse_id, status, created_at) VALUES (6, 2, 4, 1, 1, "open", :created)', ['created' => $now]);

$contextTrail = ['eventId' => 2, 'classId' => 3, 'user' => ['name' => 'Test']];
$contextPleasure = ['eventId' => 2, 'classId' => 4, 'user' => ['name' => 'Test']];

assignStartNumber($contextTrail, ['entry_id' => 5]);
assignStartNumber($contextPleasure, ['entry_id' => 6]);

$entry5 = db_first('SELECT start_number_display, start_number_assignment_id FROM entries WHERE id = 5');
$entry6 = db_first('SELECT start_number_display, start_number_assignment_id FROM entries WHERE id = 6');
assertSame($entry5['start_number_display'], $entry6['start_number_display'], 'Pair allocation must reuse start number.');
assertTrue((int) $entry5['start_number_assignment_id'] === (int) $entry6['start_number_assignment_id'], 'Pair entries should share assignment.');

$assignmentId = (int) $entry5['start_number_assignment_id'];
releaseStartNumber(['id' => $assignmentId, 'entry_id' => 5], 'withdraw');
$entry6After = db_first('SELECT start_number_display, start_number_assignment_id FROM entries WHERE id = 6');
assertSame($entry6['start_number_display'], $entry6After['start_number_display'], 'Remaining pair should keep number after partial release.');
assertTrue((int) $entry6After['start_number_assignment_id'] === $assignmentId, 'Assignment should stay active for remaining pair.');

releaseStartNumber(['id' => $assignmentId, 'entry_id' => 6], 'withdraw');
$assignmentRow = db_first('SELECT status FROM start_number_assignments WHERE id = :id', ['id' => $assignmentId]);
assertSame('released', $assignmentRow['status'], 'Assignment should be released once no bindings remain.');

$formatted = formatStartNumber(['id' => $assignmentId], ['eventId' => 2]);
assertSame($entry6['start_number_display'], $formatted, 'Format helper should return stored display value.');

echo "Start number engine tests passed\n";
