<?php
declare(strict_types=1);

require __DIR__ . '/../app/helpers/uuid.php';
require __DIR__ . '/../app/Models/Layout.php';
require __DIR__ . '/../app/Models/LayoutVersion.php';
require __DIR__ . '/../app/Layouts/LayoutRepository.php';
require __DIR__ . '/../app/Layouts/LayoutController.php';

use App\Layouts\LayoutController;
use App\Layouts\LayoutRepository;

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

$migration = require __DIR__ . '/../app/Setup/Migrations/20241215000000__layouts_module.php';
($migration['up'])($pdo, 'sqlite');

$storagePath = sys_get_temp_dir() . '/layout_repo_test_' . uniqid();
@mkdir($storagePath, 0775, true);

function assertSame(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(($message ? $message . ': ' : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}

function assertTrue(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new RuntimeException($message ?: 'Assertion failed');
    }
}

$repository = new LayoutRepository($pdo, $storagePath);
$controller = new LayoutController($repository);

$layout = $repository->createLayout(
    'Certificate template',
    'certificate',
    ['canvas' => ['width' => 1024, 'height' => 724]],
    ['tags' => ['print']],
    [],
    1,
    99,
    'draft',
    'Default award certificate'
);

assertSame('Certificate template', $layout->name, 'Layout name should persist.');
assertSame('certificate', $layout->category, 'Category should be stored.');
assertSame(1, $layout->version, 'Initial version should be 1.');

$repository->updateMetadata($layout->id ?? 0, [
    'name' => 'Certificate V2',
    'status' => 'in_review',
    'owner_id' => 5,
], 101);

$updated = $repository->getLayout($layout->id ?? 0);
assertSame('Certificate V2', $updated->name, 'Updated layout should carry new name.');
assertSame('in_review', $updated->status, 'Status should update.');
assertSame(5, $updated->ownerId, 'Owner id should change.');

$version = $controller->createVersion($updated->id ?? 0, [
    'status' => 'in_review',
    'data' => json_encode(['pages' => [['id' => 'page-1']]], JSON_THROW_ON_ERROR),
    'meta' => json_encode(['notes' => 'Awaiting sign-off'], JSON_THROW_ON_ERROR),
    'assets' => json_encode([['filename' => 'logo.png']]),
    'comment' => 'Initial review',
    'version' => 2,
], ['id' => 101]);

assertSame(2, $version->version, 'Version should increment to requested number.');
assertSame('in_review', $version->status, 'Version status should match input.');

$approved = $repository->approveVersion($updated->id ?? 0, 2, 200, 'Approved for print');
assertSame('approved', $approved->status, 'Layout status should switch to approved.');
assertTrue($approved->approvedAt !== null, 'Approved timestamp should be set.');

$zipPath = $repository->exportLayout($approved->id ?? 0);
assertTrue(is_file($zipPath), 'Export should create a ZIP package.');

$zip = new ZipArchive();
assertTrue($zip->open($zipPath) === true, 'ZIP archive should open.');
$manifest = $zip->getFromName('layout.json');
assertTrue(is_string($manifest), 'Manifest should exist.');
$decoded = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
assertSame('Certificate V2', $decoded['layout']['name'] ?? null, 'Exported manifest should contain layout name.');
$zip->close();

$imported = $repository->importLayout($zipPath, 300);
assertTrue(($imported->id ?? 0) !== ($approved->id ?? 0), 'Import should create a new layout.');
assertSame('Certificate V2', $imported->name, 'Imported layout should keep original name.');

$duplicate = $repository->duplicateLayout($approved->id ?? 0, 400, 'Certificate Copy');
assertSame('Certificate Copy', $duplicate->name, 'Duplicate should allow naming override.');
assertSame('draft', $duplicate->status, 'Duplicate should start as draft.');

$controller->createVersion($duplicate->id ?? 0, [
    'status' => 'draft',
    'data' => json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR),
], ['id' => 400]);

$versions = $repository->getVersions($duplicate->id ?? 0);
assertTrue(count($versions) >= 1, 'Duplicate should have at least one version.');

@unlink($zipPath);

echo "Layout repository tests passed\n";
