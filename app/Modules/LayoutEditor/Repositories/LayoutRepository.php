<?php

declare(strict_types=1);

namespace App\Modules\LayoutEditor\Repositories;

use App\Core\App;
use App\Modules\LayoutEditor\Models\Layout;
use App\Modules\LayoutEditor\Models\LayoutSnippet;
use App\Modules\LayoutEditor\Models\LayoutVersion;
use DateTimeImmutable;
use JsonException;
use PDO;
use RuntimeException;
use Throwable;

class LayoutRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
            return;
        }

        if (function_exists('app_pdo')) {
            $candidate = app_pdo();
            if ($candidate instanceof PDO) {
                $this->pdo = $candidate;
                return;
            }
        }

        $candidate = App::get('pdo');
        if (!$candidate instanceof PDO) {
            throw new RuntimeException('Keine Datenbankverbindung für Layout-Editor verfügbar.');
        }

        $this->pdo = $candidate;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, Layout>
     */
    public function list(array $filters = []): array
    {
        $sql = 'SELECT * FROM layouts';
        $params = [];
        $conditions = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(name LIKE :search OR slug LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY updated_at DESC, name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $layouts = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $layouts[] = $this->hydrateLayout($row);
        }

        return $layouts;
    }

    public function find(int $id): ?Layout
    {
        $stmt = $this->pdo->prepare('SELECT * FROM layouts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateLayout($row) : null;
    }

    public function findBySlug(string $slug): ?Layout
    {
        $stmt = $this->pdo->prepare('SELECT * FROM layouts WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateLayout($row) : null;
    }

    /**
     * @param array<string, mixed> $structure
     * @param array<string, mixed> $metadata
     */
    public function create(string $name, array $structure, array $metadata, int $userId, ?string $slug = null, ?string $status = null, ?string $description = null, ?string $comment = null): Layout
    {
        $baseSlug = $slug !== null && $slug !== '' ? $slug : $name;
        $slugValue = $this->ensureUniqueSlug($this->slugify($baseSlug));
        $statusValue = $this->sanitizeStatus($status);

        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $metadataJson = $this->encodeJson($metadata);
        $structureJson = $this->encodeJson($structure);

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO layouts (slug, name, description, status, metadata_json, structure_json, version, current_version_id, published_version_id, published_at, archived_at, is_locked, locked_by, locked_at, created_by, updated_by, created_at, updated_at)
                 VALUES (:slug, :name, :description, :status, :metadata_json, :structure_json, 1, NULL, NULL, :published_at, :archived_at, 0, NULL, NULL, :created_by, :updated_by, :created_at, :updated_at)'
            );
            $stmt->execute([
                'slug' => $slugValue,
                'name' => $name,
                'description' => $description,
                'status' => $statusValue,
                'metadata_json' => $metadataJson,
                'structure_json' => $structureJson,
                'published_at' => $statusValue === 'published' ? $now : null,
                'archived_at' => $statusValue === 'archived' ? $now : null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $layoutId = (int) $this->pdo->lastInsertId();
            $versionId = $this->insertVersion($layoutId, 1, $structure, $metadata, $userId, $comment);

            $update = $this->pdo->prepare('UPDATE layouts SET current_version_id = :version_id, published_version_id = CASE WHEN status = "published" THEN :version_id ELSE published_version_id END WHERE id = :id');
            $update->execute([
                'version_id' => $versionId,
                'id' => $layoutId,
            ]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Layout konnte nicht erstellt werden: ' . $exception->getMessage(), 0, $exception);
        }

        $layout = $this->find($layoutId);
        if (!$layout) {
            throw new RuntimeException('Layout konnte nach dem Speichern nicht geladen werden.');
        }

        return $layout;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, int $userId, ?string $comment = null): Layout
    {
        $layout = $this->find($id);
        if (!$layout) {
            throw new RuntimeException('Layout nicht gefunden.');
        }

        $name = trim((string) ($data['name'] ?? $layout->name()));
        if ($name === '') {
            $name = $layout->name();
        }

        $slugInput = (string) ($data['slug'] ?? $layout->slug());
        $slugValue = $this->ensureUniqueSlug($this->slugify($slugInput), $id);
        $statusValue = $this->sanitizeStatus($data['status'] ?? $layout->status());
        $description = array_key_exists('description', $data) ? ($data['description'] !== null ? (string) $data['description'] : null) : $layout->description();
        $structure = is_array($data['structure'] ?? null) ? $data['structure'] : $layout->structure();
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : $layout->metadata();

        $version = $layout->version() + 1;
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE layouts SET slug = :slug, name = :name, description = :description, status = :status, metadata_json = :metadata_json, structure_json = :structure_json, version = :version, updated_by = :updated_by, updated_at = :updated_at, archived_at = :archived_at WHERE id = :id'
            );
            $stmt->execute([
                'slug' => $slugValue,
                'name' => $name,
                'description' => $description,
                'status' => $statusValue,
                'metadata_json' => $this->encodeJson($metadata),
                'structure_json' => $this->encodeJson($structure),
                'version' => $version,
                'updated_by' => $userId,
                'updated_at' => $now,
                'archived_at' => $statusValue === 'archived' ? $now : null,
                'id' => $id,
            ]);

            $versionId = $this->insertVersion($id, $version, $structure, $metadata, $userId, $comment);

            $update = $this->pdo->prepare(
                'UPDATE layouts SET current_version_id = :current_version_id, published_version_id = CASE WHEN :status = "published" THEN :published_version_id ELSE published_version_id END, published_at = CASE WHEN :status = "published" THEN :published_at ELSE NULL END WHERE id = :id'
            );
            $update->execute([
                'current_version_id' => $versionId,
                'status' => $statusValue,
                'published_version_id' => $versionId,
                'published_at' => $statusValue === 'published' ? $now : null,
                'id' => $id,
            ]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Layout konnte nicht aktualisiert werden: ' . $exception->getMessage(), 0, $exception);
        }

        $updated = $this->find($id);
        if (!$updated) {
            throw new RuntimeException('Layout konnte nach dem Aktualisieren nicht geladen werden.');
        }

        return $updated;
    }

    public function delete(int $id): void
    {
        $this->pdo->beginTransaction();

        try {
            $deleteVersions = $this->pdo->prepare('DELETE FROM layout_versions WHERE layout_id = :id');
            $deleteVersions->execute(['id' => $id]);

            $deleteSnippets = $this->pdo->prepare('DELETE FROM layout_snippets WHERE layout_id = :id');
            $deleteSnippets->execute(['id' => $id]);

            $deleteLayout = $this->pdo->prepare('DELETE FROM layouts WHERE id = :id');
            $deleteLayout->execute(['id' => $id]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Layout konnte nicht gelöscht werden: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return array<int, LayoutVersion>
     */
    public function listVersions(int $layoutId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM layout_versions WHERE layout_id = :layout_id ORDER BY version DESC');
        $stmt->execute(['layout_id' => $layoutId]);

        $versions = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $versions[] = $this->hydrateVersion($row);
        }

        return $versions;
    }

    public function findVersion(int $layoutId, int $versionId): ?LayoutVersion
    {
        $stmt = $this->pdo->prepare('SELECT * FROM layout_versions WHERE layout_id = :layout_id AND id = :id');
        $stmt->execute(['layout_id' => $layoutId, 'id' => $versionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateVersion($row) : null;
    }

    public function restoreVersion(int $layoutId, int $versionId, int $userId, ?string $comment = null): Layout
    {
        $version = $this->findVersion($layoutId, $versionId);
        if (!$version) {
            throw new RuntimeException('Version nicht gefunden.');
        }

        return $this->update(
            $layoutId,
            [
                'structure' => $version->structure(),
                'metadata' => $version->metadata(),
            ],
            $userId,
            $comment ?? ('Wiederhergestellt aus Version ' . $version->version())
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, LayoutSnippet>
     */
    public function listSnippets(array $filters = []): array
    {
        $sql = 'SELECT * FROM layout_snippets';
        $conditions = [];
        $params = [];

        if (array_key_exists('layout_id', $filters) && $filters['layout_id'] !== null) {
            $conditions[] = 'layout_id = :layout_id';
            $params['layout_id'] = (int) $filters['layout_id'];
        }

        if (!empty($filters['category'])) {
            $conditions[] = 'category = :category';
            $params['category'] = $filters['category'];
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY category IS NULL, category, name';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $snippets = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $snippets[] = $this->hydrateSnippet($row);
        }

        return $snippets;
    }

    public function findSnippet(int $id): ?LayoutSnippet
    {
        $stmt = $this->pdo->prepare('SELECT * FROM layout_snippets WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateSnippet($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createSnippet(array $data, int $userId): LayoutSnippet
    {
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $layoutId = $this->normalizeNullableInt($data['layout_id'] ?? null);
        $category = $this->normalizeNullableString($data['category'] ?? null);

        $stmt = $this->pdo->prepare(
            'INSERT INTO layout_snippets (layout_id, name, category, metadata_json, snippet_json, created_by, updated_by, created_at, updated_at)
             VALUES (:layout_id, :name, :category, :metadata_json, :snippet_json, :created_by, :updated_by, :created_at, :updated_at)'
        );
        $stmt->execute([
            'layout_id' => $layoutId,
            'name' => $data['name'],
            'category' => $category,
            'metadata_json' => $this->encodeJson(is_array($data['metadata'] ?? null) ? $data['metadata'] : []),
            'snippet_json' => $this->encodeJson(is_array($data['snippet'] ?? null) ? $data['snippet'] : []),
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $snippet = $this->findSnippet($id);
        if (!$snippet) {
            throw new RuntimeException('Snippet konnte nach dem Speichern nicht geladen werden.');
        }

        return $snippet;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateSnippet(int $id, array $data, int $userId): LayoutSnippet
    {
        $snippet = $this->findSnippet($id);
        if (!$snippet) {
            throw new RuntimeException('Snippet nicht gefunden.');
        }

        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $layoutId = array_key_exists('layout_id', $data)
            ? $this->normalizeNullableInt($data['layout_id'])
            : $snippet->layoutId();
        $category = array_key_exists('category', $data)
            ? $this->normalizeNullableString($data['category'])
            : $snippet->category();

        $stmt = $this->pdo->prepare(
            'UPDATE layout_snippets SET layout_id = :layout_id, name = :name, category = :category, metadata_json = :metadata_json, snippet_json = :snippet_json, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute([
            'layout_id' => $layoutId,
            'name' => $data['name'] ?? $snippet->name(),
            'category' => $category,
            'metadata_json' => $this->encodeJson(is_array($data['metadata'] ?? null) ? $data['metadata'] : $snippet->metadata()),
            'snippet_json' => $this->encodeJson(is_array($data['snippet'] ?? null) ? $data['snippet'] : $snippet->snippet()),
            'updated_by' => $userId,
            'updated_at' => $now,
            'id' => $id,
        ]);

        $updated = $this->findSnippet($id);
        if (!$updated) {
            throw new RuntimeException('Snippet konnte nach dem Aktualisieren nicht geladen werden.');
        }

        return $updated;
    }

    public function deleteSnippet(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM layout_snippets WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function insertVersion(int $layoutId, int $version, array $structure, array $metadata, int $userId, ?string $comment): int
    {
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO layout_versions (layout_id, version, comment, metadata_json, structure_json, created_by, created_at)
             VALUES (:layout_id, :version, :comment, :metadata_json, :structure_json, :created_by, :created_at)'
        );
        $stmt->execute([
            'layout_id' => $layoutId,
            'version' => $version,
            'comment' => $comment,
            'metadata_json' => $this->encodeJson($metadata),
            'structure_json' => $this->encodeJson($structure),
            'created_by' => $userId,
            'created_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateLayout(array $row): Layout
    {
        $row['metadata'] = $this->decodeJson($row['metadata_json'] ?? null);
        $row['structure'] = $this->decodeJson($row['structure_json'] ?? null);

        return Layout::fromDatabase($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateVersion(array $row): LayoutVersion
    {
        $row['metadata'] = $this->decodeJson($row['metadata_json'] ?? null);
        $row['structure'] = $this->decodeJson($row['structure_json'] ?? null);

        return LayoutVersion::fromDatabase($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateSnippet(array $row): LayoutSnippet
    {
        $row['metadata'] = $this->decodeJson($row['metadata_json'] ?? null);
        $row['snippet'] = $this->decodeJson($row['snippet_json'] ?? null);

        return LayoutSnippet::fromDatabase($row);
    }

    private function decodeJson(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $value
     */
    private function encodeJson(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('JSON konnte nicht kodiert werden: ' . $exception->getMessage(), 0, $exception);
        }
    }

    private function slugify(string $value): string
    {
        $slug = strtolower($value);
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'layout-' . bin2hex(random_bytes(4));
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $candidate = $slug;
        $suffix = 1;

        while ($this->slugExists($candidate, $ignoreId)) {
            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT 1 FROM layouts WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    private function sanitizeStatus(?string $status): string
    {
        $allowed = ['draft', 'published', 'archived'];
        $status = strtolower((string) ($status ?? 'draft'));

        return in_array($status, $allowed, true) ? $status : 'draft';
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
