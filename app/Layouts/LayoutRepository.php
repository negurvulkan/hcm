<?php
declare(strict_types=1);

namespace App\Layouts;

use App\Models\Layout;
use App\Models\LayoutVersion;
use DateTimeImmutable;
use PDO;
use PDOException;
use RuntimeException;
use ZipArchive;

use function app_uuid;
use function is_dir;
use function mkdir;

class LayoutRepository
{
    public const STATUSES = ['draft', 'in_review', 'approved', 'archived'];

    public const CATEGORIES = ['general', 'certificate', 'signage', 'print', 'screen', 'web'];

    private string $storagePath;

    public function __construct(private PDO $pdo, ?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?: dirname(__DIR__, 2) . '/storage/layout_assets';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<int, Layout>
     */
    public function listLayouts(array $filters = []): array
    {
        $sql = 'SELECT * FROM layouts';
        $conditions = [];
        $params = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $conditions[] = '(name LIKE :search OR description LIKE :search OR slug LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $category = $this->normalizeCategory($filters['category'] ?? '', 'all');
        if ($category !== 'all' && $category !== '') {
            $conditions[] = 'category = :category';
            $params['category'] = $category;
        }

        $status = $this->normalizeStatus($filters['status'] ?? '', 'all');
        if ($status !== 'all' && $status !== '') {
            $conditions[] = 'status = :status';
            $params['status'] = $status;
        }

        if (!empty($filters['owner_id'])) {
            $conditions[] = 'owner_id = :owner_id';
            $params['owner_id'] = (int) $filters['owner_id'];
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY updated_at DESC, name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn (array $row): Layout => Layout::fromArray($row), $rows);
    }

    public function getLayout(int $id): ?Layout
    {
        $stmt = $this->pdo->prepare('SELECT * FROM layouts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Layout::fromArray($row) : null;
    }

    public function getLayoutBySlug(string $slug): ?Layout
    {
        $stmt = $this->pdo->prepare('SELECT * FROM layouts WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Layout::fromArray($row) : null;
    }

    /**
     * @return array<int, LayoutVersion>
     */
    public function getVersions(int $layoutId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM layout_versions WHERE layout_id = :layout ORDER BY version DESC');
        $stmt->execute(['layout' => $layoutId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn (array $row): LayoutVersion => LayoutVersion::fromArray($row), $rows);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     * @param array<int, array<string, mixed>> $assets
     */
    public function createLayout(
        string $name,
        string $category,
        array $data,
        array $meta,
        array $assets,
        ?int $ownerId,
        int $userId,
        string $status = 'draft',
        ?string $description = null,
        bool $bootstrapVersion = true,
        int $initialVersion = 1
    ): Layout {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Layout benötigt einen Namen.');
        }

        $category = $this->normalizeCategory($category, 'general');
        if ($category === 'all' || $category === '') {
            $category = 'general';
        }

        $status = $this->normalizeStatus($status, 'draft');
        if ($status === 'all' || $status === '') {
            $status = 'draft';
        }

        $slug = $this->ensureUniqueSlug($this->slugify($name));
        $uuid = app_uuid();
        $now = (new DateTimeImmutable('now'))->format('c');

        $resolvedAssets = $this->normalizeAssets($assets, $uuid, true);

        $versionNumber = $bootstrapVersion ? max(1, $initialVersion) : max(0, $initialVersion);

        $payload = [
            'uuid' => $uuid,
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'status' => $status,
            'version' => $versionNumber,
            'data_json' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'assets_json' => json_encode($resolvedAssets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'owner_id' => $ownerId,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
        ];

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('INSERT INTO layouts (uuid, slug, name, description, category, status, version, data_json, meta_json, assets_json, owner_id, created_by, updated_by, created_at, updated_at, approved_by, approved_at, published_at) VALUES (:uuid, :slug, :name, :description, :category, :status, :version, :data_json, :meta_json, :assets_json, :owner_id, :created_by, :updated_by, :created_at, :updated_at, :approved_by, :approved_at, :published_at)');
            $stmt->execute($payload);
            $id = (int) $this->pdo->lastInsertId();
            if ($bootstrapVersion) {
                $this->insertVersion($id, $versionNumber, $status, $data, $meta, $resolvedAssets, $userId, $now, null);
            }
            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Layout konnte nicht erstellt werden: ' . $exception->getMessage(), 0, $exception);
        }

        return $this->getLayout($id) ?? throw new RuntimeException('Layout konnte nach Erstellung nicht geladen werden.');
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     * @param array<int, array<string, mixed>> $assets
     */
    public function createVersion(int $layoutId, array $data, array $meta, array $assets, int $userId, string $status = 'in_review', ?string $comment = null, ?int $targetVersion = null): LayoutVersion
    {
        $layout = $this->getLayout($layoutId);
        if (!$layout) {
            throw new RuntimeException('Layout nicht gefunden.');
        }

        $status = $this->normalizeStatus($status, 'in_review');
        if ($status === 'all' || $status === '') {
            $status = 'in_review';
        }

        if ($targetVersion !== null && $targetVersion <= 0) {
            throw new RuntimeException('Ungültige Versionsnummer.');
        }

        $nextVersion = $targetVersion ?? ($layout->version + 1);
        if ($targetVersion !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM layout_versions WHERE layout_id = :layout AND version = :version');
            $stmt->execute(['layout' => $layoutId, 'version' => $targetVersion]);
            if ((int) $stmt->fetchColumn() > 0) {
                $nextVersion = $layout->version + 1;
            }
        }

        $now = (new DateTimeImmutable('now'))->format('c');
        $resolvedAssets = $this->normalizeAssets($assets, $layout->uuid, true);

        try {
            $this->pdo->beginTransaction();

            $this->insertVersion($layoutId, $nextVersion, $status, $data, $meta, $resolvedAssets, $userId, $now, $comment);

            $stmt = $this->pdo->prepare('UPDATE layouts SET version = :version, status = :status, data_json = :data_json, meta_json = :meta_json, assets_json = :assets_json, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id');
            $stmt->execute([
                'version' => $nextVersion,
                'status' => $status,
                'data_json' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'assets_json' => json_encode($resolvedAssets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_by' => $userId,
                'updated_at' => $now,
                'id' => $layoutId,
            ]);

            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Layout-Version konnte nicht erstellt werden: ' . $exception->getMessage(), 0, $exception);
        }

        $versions = $this->getVersions($layoutId);
        return $versions[0] ?? throw new RuntimeException('Neue Version konnte nicht geladen werden.');
    }

    public function approveVersion(int $layoutId, int $version, int $userId, ?string $comment = null): Layout
    {
        $layout = $this->getLayout($layoutId);
        if (!$layout) {
            throw new RuntimeException('Layout nicht gefunden.');
        }

        $stmt = $this->pdo->prepare('SELECT * FROM layout_versions WHERE layout_id = :layout AND version = :version');
        $stmt->execute(['layout' => $layoutId, 'version' => $version]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('Version nicht gefunden.');
        }
        $versionRow = LayoutVersion::fromArray($row);

        $now = (new DateTimeImmutable('now'))->format('c');

        try {
            $this->pdo->beginTransaction();
            $updateVersion = $this->pdo->prepare('UPDATE layout_versions SET status = :status, approved_at = :approved_at, approved_by = :approved_by, comment = COALESCE(comment, :comment) WHERE id = :id');
            $updateVersion->execute([
                'status' => 'approved',
                'approved_at' => $now,
                'approved_by' => $userId,
                'comment' => $comment,
                'id' => $row['id'],
            ]);

            $updateLayout = $this->pdo->prepare('UPDATE layouts SET status = :status, version = :version, data_json = :data_json, meta_json = :meta_json, assets_json = :assets_json, approved_by = :approved_by, approved_at = :approved_at, published_at = :published_at, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id');
            $updateLayout->execute([
                'status' => 'approved',
                'version' => $version,
                'data_json' => json_encode($versionRow->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'meta_json' => json_encode($versionRow->meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'assets_json' => json_encode($versionRow->assets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'approved_by' => $userId,
                'approved_at' => $now,
                'published_at' => $now,
                'updated_at' => $now,
                'updated_by' => $userId,
                'id' => $layoutId,
            ]);

            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Version konnte nicht freigegeben werden: ' . $exception->getMessage(), 0, $exception);
        }

        return $this->getLayout($layoutId) ?? throw new RuntimeException('Layout nach Freigabe nicht verfügbar.');
    }

    public function updateMetadata(int $layoutId, array $attributes, int $userId): Layout
    {
        $layout = $this->getLayout($layoutId);
        if (!$layout) {
            throw new RuntimeException('Layout nicht gefunden.');
        }

        $name = trim((string) ($attributes['name'] ?? $layout->name));
        if ($name === '') {
            $name = $layout->name;
        }
        $slug = $layout->slug;
        if ($name !== $layout->name) {
            $slug = $this->ensureUniqueSlug($this->slugify($name), $layoutId);
        }
        $category = $this->normalizeCategory($attributes['category'] ?? $layout->category, $layout->category);
        $status = $this->normalizeStatus($attributes['status'] ?? $layout->status, $layout->status);
        $owner = $attributes['owner_id'] ?? $layout->ownerId;
        $ownerId = $owner !== null ? (int) $owner : null;
        $description = isset($attributes['description']) ? trim((string) $attributes['description']) : $layout->description;
        $now = (new DateTimeImmutable('now'))->format('c');

        $stmt = $this->pdo->prepare('UPDATE layouts SET name = :name, slug = :slug, description = :description, category = :category, status = :status, owner_id = :owner_id, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'category' => $category,
            'status' => $status,
            'owner_id' => $ownerId,
            'updated_by' => $userId,
            'updated_at' => $now,
            'id' => $layoutId,
        ]);

        return $this->getLayout($layoutId) ?? throw new RuntimeException('Layout nach Update nicht verfügbar.');
    }

    public function duplicateLayout(int $layoutId, int $userId, ?string $name = null): Layout
    {
        $layout = $this->getLayout($layoutId);
        if (!$layout) {
            throw new RuntimeException('Layout nicht gefunden.');
        }

        $newName = $name ? trim($name) : $layout->name . ' Kopie';
        if ($newName === '') {
            $newName = $layout->name . ' Kopie';
        }

        $versions = $this->getVersions($layoutId);
        $latestVersion = $versions[0] ?? new LayoutVersion(null, $layoutId, $layout->version, $layout->status, null, $layout->data, $layout->meta, $layout->assets, $layout->createdBy, $layout->createdAt, $layout->approvedBy, $layout->approvedAt);

        $newLayout = $this->createLayout(
            $newName,
            $layout->category,
            $latestVersion->data,
            $latestVersion->meta,
            $this->copyAssetsMetadata($layout->uuid, $latestVersion->assets),
            $layout->ownerId,
            $userId,
            'draft',
            $layout->description
        );

        return $this->getLayout($newLayout->id ?? 0) ?? $newLayout;
    }

    public function deleteLayout(int $layoutId): void
    {
        $layout = $this->getLayout($layoutId);
        if (!$layout) {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM layout_versions WHERE layout_id = :id')->execute(['id' => $layoutId]);
            $this->pdo->prepare('DELETE FROM layouts WHERE id = :id')->execute(['id' => $layoutId]);
            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Layout konnte nicht gelöscht werden: ' . $exception->getMessage(), 0, $exception);
        }

        $dir = $this->assetDirectory($layout->uuid);
        if (is_dir($dir)) {
            $files = scandir($dir) ?: [];
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                @unlink($dir . '/' . $file);
            }
            @rmdir($dir);
        }
    }

    public function exportLayout(int $layoutId, ?int $version = null): string
    {
        $layout = $this->getLayout($layoutId);
        if (!$layout) {
            throw new RuntimeException('Layout nicht gefunden.');
        }

        $payload = $layout->toArray();
        if ($version !== null && $version !== $layout->version) {
            $versions = $this->getVersions($layoutId);
            foreach ($versions as $candidate) {
                if ($candidate->version === $version) {
                    $payload['data'] = $candidate->data;
                    $payload['meta'] = $candidate->meta;
                    $payload['assets'] = $candidate->assets;
                    $payload['version'] = $candidate->version;
                    $payload['status'] = $candidate->status;
                    break;
                }
            }
        }

        $export = [
            'schema' => 'hcm-layout-package',
            'schema_version' => 1,
            'exported_at' => (new DateTimeImmutable('now'))->format('c'),
            'layout' => $payload,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'layout_pkg_');
        if ($tmp === false) {
            throw new RuntimeException('Konnte temporäre Datei nicht erstellen.');
        }
        $zipPath = $tmp . '.zip';
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Konnte ZIP-Archiv nicht öffnen.');
        }
        $zip->addFromString('layout.json', json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        foreach ($payload['assets'] as $asset) {
            if (!is_array($asset)) {
                continue;
            }
            $filename = isset($asset['filename']) ? (string) $asset['filename'] : null;
            if (!$filename) {
                $filename = isset($asset['path']) ? basename((string) $asset['path']) : null;
            }
            if (!$filename) {
                continue;
            }
            $path = $this->assetPath($layout->uuid, $filename);
            if (is_file($path)) {
                $zip->addFile($path, 'assets/' . $filename);
            }
        }

        $zip->close();

        return $zipPath;
    }

    public function importLayout(string $zipPath, int $userId): Layout
    {
        if (!is_file($zipPath)) {
            throw new RuntimeException('Import-Datei nicht gefunden.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('ZIP-Archiv konnte nicht geöffnet werden.');
        }

        $json = $zip->getFromName('layout.json');
        if ($json === false) {
            $zip->close();
            throw new RuntimeException('Layout-Definition nicht gefunden.');
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            $zip->close();
            throw new RuntimeException('Layout-Definition ungültig: ' . $exception->getMessage(), 0, $exception);
        }

        $layoutData = $decoded['layout'] ?? null;
        if (!is_array($layoutData)) {
            $zip->close();
            throw new RuntimeException('Layoutdaten fehlen.');
        }

        $name = trim((string) ($layoutData['name'] ?? '')); 
        if ($name === '') {
            $zip->close();
            throw new RuntimeException('Layout benötigt einen Namen.');
        }

        $category = (string) ($layoutData['category'] ?? 'general');
        $status = (string) ($layoutData['status'] ?? 'draft');
        $data = isset($layoutData['data']) && is_array($layoutData['data']) ? $layoutData['data'] : [];
        $meta = isset($layoutData['meta']) && is_array($layoutData['meta']) ? $layoutData['meta'] : [];
        $assets = isset($layoutData['assets']) && is_array($layoutData['assets']) ? $layoutData['assets'] : [];
        $description = isset($layoutData['description']) && $layoutData['description'] !== '' ? (string) $layoutData['description'] : null;

        $layout = $this->createLayout($name, $category, $data, $meta, [], null, $userId, $status, $description, false, 0);

        $storedAssets = $this->extractAssetsFromZip($zip, $layout->uuid, $assets);

        $this->createVersion(
            $layout->id ?? throw new RuntimeException('Layout-ID fehlt.'),
            $data,
            $meta,
            $storedAssets,
            $userId,
            $status,
            'Imported from package',
            isset($layoutData['version']) ? max(1, (int) $layoutData['version']) : null
        );

        $zip->close();

        return $this->getLayout($layout->id ?? 0) ?? $layout;
    }

    /**
     * @param array<int, array<string, mixed>> $assets
     *
     * @return array<int, array<string, mixed>>
     */
    private function copyAssetsMetadata(string $sourceUuid, array $assets): array
    {
        $resolved = [];
        $sourceDir = $this->assetDirectory($sourceUuid);
        foreach ($assets as $asset) {
            if (!is_array($asset)) {
                continue;
            }
            $filename = isset($asset['filename']) ? (string) $asset['filename'] : (isset($asset['path']) ? basename((string) $asset['path']) : null);
            if (!$filename) {
                continue;
            }
            $path = $sourceDir . '/' . $filename;
            if (!is_file($path)) {
                continue;
            }
            $resolved[] = [
                'filename' => $filename,
                'original_name' => isset($asset['original_name']) ? (string) $asset['original_name'] : $filename,
                'mime_type' => isset($asset['mime_type']) ? (string) $asset['mime_type'] : null,
                'size' => isset($asset['size']) ? (int) $asset['size'] : filesize($path),
                'path' => $sourceUuid . '/' . $filename,
            ];
        }
        return $resolved;
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug;
        $counter = 1;
        while (true) {
            $params = ['slug' => $slug];
            $sql = 'SELECT id FROM layouts WHERE slug = :slug';
            if ($ignoreId !== null) {
                $sql .= ' AND id != :id';
                $params['id'] = $ignoreId;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            if (!$stmt->fetch()) {
                return $slug;
            }
            $slug = $base . '-' . (++$counter);
        }
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? 'layout';
        $value = trim($value, '-');

        return $value === '' ? 'layout' : $value;
    }

    private function normalizeStatus(mixed $status, string $fallback = 'all'): string
    {
        if (!is_string($status)) {
            return $fallback;
        }
        $status = trim($status);
        if ($status === '') {
            return $fallback;
        }
        if ($status !== 'all' && !in_array($status, self::STATUSES, true)) {
            return $fallback;
        }
        return $status;
    }

    private function normalizeCategory(mixed $category, string $fallback = 'all'): string
    {
        if (!is_string($category)) {
            return $fallback;
        }
        $category = trim($category);
        if ($category === '') {
            return $fallback;
        }
        if ($category !== 'all' && !in_array($category, self::CATEGORIES, true)) {
            return $fallback;
        }
        return $category;
    }

    /**
     * @param array<int, array<string, mixed>> $assets
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAssets(array $assets, string $uuid, bool $copy, ?string $sourceUuid = null): array
    {
        $resolved = [];
        $targetDir = $this->assetDirectory($uuid);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        foreach ($assets as $asset) {
            if (!is_array($asset)) {
                continue;
            }
            $filename = isset($asset['filename']) ? (string) $asset['filename'] : (isset($asset['path']) ? basename((string) $asset['path']) : null);
            if (!$filename) {
                continue;
            }
            $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
            if (!$filename) {
                continue;
            }

            $sourcePath = null;
            if ($copy) {
                if ($sourceUuid !== null) {
                    $candidate = $this->assetPath($sourceUuid, $filename);
                    if (is_file($candidate)) {
                        $sourcePath = $candidate;
                    }
                }
                if (!$sourcePath && isset($asset['path'])) {
                    $path = (string) $asset['path'];
                    if (str_starts_with($path, '/')) {
                        $sourcePath = $path;
                    } elseif ($sourceUuid !== null && str_starts_with($path, $sourceUuid . '/')) {
                        $sourcePath = $this->assetPath($sourceUuid, basename($path));
                    } elseif (str_contains($path, '/')) {
                        $parts = explode('/', $path);
                        $maybeUuid = array_shift($parts);
                        if ($maybeUuid && $maybeUuid !== $uuid) {
                            $candidate = $this->assetPath($maybeUuid, implode('/', $parts));
                            if (is_file($candidate)) {
                                $sourcePath = $candidate;
                            }
                        }
                    }
                }
            }

            $targetPath = $targetDir . '/' . $filename;
            if ($copy && $sourcePath && is_file($sourcePath)) {
                copy($sourcePath, $targetPath);
            }

            $resolved[] = [
                'filename' => $filename,
                'original_name' => isset($asset['original_name']) ? (string) $asset['original_name'] : $filename,
                'mime_type' => isset($asset['mime_type']) ? (string) $asset['mime_type'] : null,
                'size' => isset($asset['size']) ? (int) $asset['size'] : (is_file($targetPath) ? filesize($targetPath) : null),
                'path' => $uuid . '/' . $filename,
            ];
        }

        return $resolved;
    }

    private function assetDirectory(string $uuid): string
    {
        return rtrim($this->storagePath, '/') . '/' . $uuid;
    }

    private function assetPath(string $uuid, string $filename): string
    {
        return $this->assetDirectory($uuid) . '/' . $filename;
    }

    /**
     * @param array<int, array<string, mixed>> $assets
     */
    private function insertVersion(int $layoutId, int $version, string $status, array $data, array $meta, array $assets, int $userId, string $createdAt, ?string $comment): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO layout_versions (layout_id, version, status, comment, data_json, meta_json, assets_json, created_by, created_at, approved_by, approved_at) VALUES (:layout_id, :version, :status, :comment, :data_json, :meta_json, :assets_json, :created_by, :created_at, NULL, NULL)');
        $stmt->execute([
            'layout_id' => $layoutId,
            'version' => $version,
            'status' => $status,
            'comment' => $comment,
            'data_json' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'assets_json' => json_encode($assets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_by' => $userId,
            'created_at' => $createdAt,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $assetsMeta
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractAssetsFromZip(ZipArchive $zip, string $uuid, array $assetsMeta): array
    {
        $targetDir = $this->assetDirectory($uuid);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $resolved = [];
        foreach ($assetsMeta as $asset) {
            if (!is_array($asset)) {
                continue;
            }
            $filename = isset($asset['filename']) ? (string) $asset['filename'] : (isset($asset['path']) ? basename((string) $asset['path']) : null);
            if (!$filename) {
                continue;
            }
            $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
            if ($filename === '') {
                continue;
            }
            $stream = $zip->getStream('assets/' . $filename);
            if (!$stream) {
                continue;
            }
            $targetPath = $targetDir . '/' . $filename;
            $handle = fopen($targetPath, 'wb');
            if ($handle === false) {
                fclose($stream);
                continue;
            }
            while (!feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    break;
                }
                fwrite($handle, $chunk);
            }
            fclose($stream);
            fclose($handle);
            $resolved[] = [
                'filename' => $filename,
                'original_name' => isset($asset['original_name']) ? (string) $asset['original_name'] : $filename,
                'mime_type' => isset($asset['mime_type']) ? (string) $asset['mime_type'] : null,
                'size' => filesize($targetPath) ?: null,
                'path' => $uuid . '/' . $filename,
            ];
        }

        return $resolved;
    }
}
