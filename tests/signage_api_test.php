<?php
declare(strict_types=1);

use App\Signage\SignageApiHandler;
use App\Signage\SignageRepository;
use App\Signage\Exceptions\NotFoundException;
use App\Signage\Exceptions\ValidationException;

require __DIR__ . '/../app/Signage/LayoutDefaults.php';
require __DIR__ . '/../app/Signage/Exceptions/NotFoundException.php';
require __DIR__ . '/../app/Signage/Exceptions/ValidationException.php';
require __DIR__ . '/../app/Signage/SignageRepository.php';
require __DIR__ . '/../app/Signage/SignageApiHandler.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec('CREATE TABLE signage_layouts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id INTEGER NULL,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT NULL,
    status TEXT NOT NULL DEFAULT "draft",
    theme_json TEXT NULL,
    canvas_width INTEGER NOT NULL DEFAULT 1920,
    canvas_height INTEGER NOT NULL DEFAULT 1080,
    layers_json TEXT NOT NULL DEFAULT "[]",
    timeline_json TEXT NOT NULL DEFAULT "[]",
    data_sources_json TEXT NOT NULL DEFAULT "[]",
    options_json TEXT NOT NULL DEFAULT "{}",
    version INTEGER NOT NULL DEFAULT 1,
    current_revision_id INTEGER NULL,
    published_at TEXT NULL,
    created_by INTEGER NULL,
    updated_by INTEGER NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)');
$pdo->exec('CREATE TABLE signage_layout_revisions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    layout_id INTEGER NOT NULL,
    version INTEGER NOT NULL,
    comment TEXT NULL,
    layers_json TEXT NOT NULL,
    timeline_json TEXT NOT NULL,
    data_sources_json TEXT NOT NULL,
    options_json TEXT NOT NULL,
    theme_json TEXT NULL,
    created_by INTEGER NULL,
    created_at TEXT NOT NULL
)');
$pdo->exec('CREATE TABLE signage_playlists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    layout_id INTEGER NULL,
    title TEXT NOT NULL,
    display_group TEXT NOT NULL,
    starts_at TEXT NULL,
    ends_at TEXT NULL,
    rotation_seconds INTEGER NOT NULL DEFAULT 30,
    priority INTEGER NOT NULL DEFAULT 0,
    is_enabled INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)');
$pdo->exec('CREATE TABLE signage_playlist_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    playlist_id INTEGER NOT NULL,
    layout_id INTEGER NOT NULL,
    label TEXT NULL,
    duration_seconds INTEGER NOT NULL DEFAULT 30,
    position INTEGER NOT NULL DEFAULT 0,
    options_json TEXT NOT NULL DEFAULT "{}"
)');
$pdo->exec('CREATE TABLE signage_displays (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    display_group TEXT NOT NULL,
    location TEXT NULL,
    description TEXT NULL,
    access_token TEXT NOT NULL,
    assigned_layout_id INTEGER NULL,
    assigned_playlist_id INTEGER NULL,
    last_seen_at TEXT NULL,
    heartbeat_interval INTEGER NOT NULL DEFAULT 60,
    hardware_info TEXT NULL,
    settings_json TEXT NOT NULL DEFAULT "{}",
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)');

$GLOBALS['__signage_pdo'] = $pdo;

function app_pdo(): PDO
{
    return $GLOBALS['__signage_pdo'];
}

function db_first(string $sql, array $params = []): ?array
{
    $stmt = app_pdo()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function db_all(string $sql, array $params = []): array
{
    $stmt = app_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function t(string $key): string
{
    return $key;
}

class FakeInstanceConfig
{
    public function get(string $key)
    {
        return null;
    }
}

function instance_config(): FakeInstanceConfig
{
    static $instance;
    if (!$instance) {
        $instance = new FakeInstanceConfig();
    }
    return $instance;
}

function event_active(): ?array
{
    return null;
}

function event_active_id(): ?int
{
    return null;
}

function assertTrue(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new RuntimeException($message ?: 'Assertion failed');
    }
}

function assertSame(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(($message ? $message . ': ' : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}

function expectException(string $class, callable $callable, ?string $contains = null): void
{
    try {
        $callable();
    } catch (Throwable $throwable) {
        if (!$throwable instanceof $class) {
            throw new RuntimeException('Unexpected exception ' . get_class($throwable) . ': ' . $throwable->getMessage());
        }
        if ($contains && !str_contains($throwable->getMessage(), $contains)) {
            throw new RuntimeException('Exception message did not contain expected text.');
        }
        return;
    }
    throw new RuntimeException('Expected exception of type ' . $class . ' was not thrown.');
}

$repository = new SignageRepository($pdo);
$handler = new SignageApiHandler($repository);
$context = ['user_id' => 99];

$layoutResponse = $handler->perform('create_layout', ['name' => 'Main Board'], $context);
$layout = $layoutResponse['layout'] ?? null;
assertTrue(is_array($layout) && isset($layout['id']), 'Layout should be created.');

expectException(NotFoundException::class, function () use ($handler, $context) {
    $handler->perform('update_layout', ['id' => 999], $context);
});

$playlistResponse = $handler->perform('save_playlist', [
    'title' => 'Primary Loop',
    'display_group' => 'default',
    'items' => [
        ['layout_id' => $layout['id'], 'duration_seconds' => 25, 'label' => 'Live'],
    ],
], $context);
$playlist = $playlistResponse['playlist'] ?? null;
assertTrue(is_array($playlist) && isset($playlist['id']), 'Playlist should be created.');
assertSame(1, count($playlist['items'] ?? []), 'Playlist should contain one item.');
assertSame(25, $playlist['items'][0]['duration_seconds'], 'Duration should respect payload.');

expectException(ValidationException::class, function () use ($handler, $context, $playlist) {
    $handler->perform('save_playlist', [
        'id' => $playlist['id'],
        'title' => 'Broken Playlist',
        'items' => [
            ['layout_id' => 9999, 'duration_seconds' => 10],
        ],
    ], $context);
}, 'Ungültige Layout-Referenzen');

expectException(NotFoundException::class, function () use ($handler, $context) {
    $handler->perform('delete_display', ['id' => 1234], $context);
});

expectException(ValidationException::class, function () use ($handler, $context) {
    $handler->perform('unknown_action', [], $context);
}, 'Unbekannte Aktion');

echo "Signage API tests passed\n";
