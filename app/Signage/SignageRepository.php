<?php

namespace App\Signage;

use App\Core\App;
use App\Signage\Exceptions\ValidationException;
use DateTimeImmutable;
use PDO;
use RuntimeException;

class SignageRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
            return;
        }

        if (function_exists('app_pdo')) {
            $candidate = \app_pdo();
            if ($candidate instanceof PDO) {
                $this->pdo = $candidate;
                return;
            }
        }

        $candidate = App::get('pdo');
        if (!$candidate instanceof PDO) {
            throw new RuntimeException('Keine Datenbankverbindung für Digital Signage verfügbar.');
        }

        $this->pdo = $candidate;
    }

    public function listLayouts(?int $eventId = null): array
    {
        $sql = 'SELECT l.*, (
                    SELECT COUNT(*) FROM signage_displays d WHERE d.assigned_layout_id = l.id
                ) AS assigned_displays,
                (
                    SELECT COUNT(*) FROM signage_playlists p WHERE p.layout_id = l.id
                ) AS playlist_usage
                FROM signage_layouts l';
        $params = [];
        if ($eventId !== null) {
            $sql .= ' WHERE l.event_id = :event_id OR l.event_id IS NULL';
            $params['event_id'] = $eventId;
        }
        $sql .= ' ORDER BY l.updated_at DESC, l.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $row) => $this->hydrateLayoutRow($row), $rows);
    }

    public function getLayout(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM signage_layouts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateLayoutRow($row, true) : null;
    }

    public function getLayoutBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM signage_layouts WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateLayoutRow($row, true) : null;
    }

    public function createLayout(string $name, ?int $eventId, int $userId, array $options = []): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Layout benötigt einen Namen.');
        }

        $now = (new DateTimeImmutable('now'))->format('c');
        $slug = $this->ensureUniqueSlug($this->slugify($name));
        $blueprint = LayoutDefaults::blueprint($name);
        if (!empty($options['blueprint']) && is_array($options['blueprint'])) {
            $blueprint = array_merge($blueprint, $options['blueprint']);
        }

        $stmt = $this->pdo->prepare('INSERT INTO signage_layouts (event_id, name, slug, description, status, theme_json, canvas_width, canvas_height, layers_json, timeline_json, data_sources_json, options_json, version, created_by, updated_by, created_at, updated_at)
            VALUES (:event_id, :name, :slug, :description, :status, :theme_json, :canvas_width, :canvas_height, :layers_json, :timeline_json, :data_sources_json, :options_json, :version, :created_by, :updated_by, :created_at, :updated_at)');
        $stmt->execute([
            'event_id' => $eventId,
            'name' => $name,
            'slug' => $slug,
            'description' => $options['description'] ?? null,
            'status' => 'draft',
            'theme_json' => json_encode($blueprint['options']['theme'] ?? [], JSON_THROW_ON_ERROR),
            'canvas_width' => (int) ($blueprint['canvas']['width'] ?? 1920),
            'canvas_height' => (int) ($blueprint['canvas']['height'] ?? 1080),
            'layers_json' => json_encode($blueprint['elements'] ?? [], JSON_THROW_ON_ERROR),
            'timeline_json' => json_encode($blueprint['timeline'] ?? [], JSON_THROW_ON_ERROR),
            'data_sources_json' => json_encode($blueprint['dataSources'] ?? [], JSON_THROW_ON_ERROR),
            'options_json' => json_encode($blueprint['options'] ?? [], JSON_THROW_ON_ERROR),
            'version' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $this->pdo->lastInsertId();

        $this->storeRevision($id, 1, $userId, $blueprint, $options['comment'] ?? null);

        return $this->getLayout($id) ?? [];
    }

    public function updateLayout(int $id, array $payload, int $userId, ?string $comment = null): array
    {
        $layout = $this->getLayout($id);
        if (!$layout) {
            throw new RuntimeException('Layout nicht gefunden.');
        }

        $name = trim((string) ($payload['name'] ?? $layout['name'] ?? ''));
        if ($name === '') {
            $name = $layout['name'];
        }

        $status = $payload['status'] ?? $layout['status'] ?? 'draft';
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }

        $version = (int) ($layout['version'] ?? 1) + 1;
        $now = (new DateTimeImmutable('now'))->format('c');

        $data = [
            'canvas' => $payload['canvas'] ?? $layout['canvas'] ?? [],
            'elements' => $payload['elements'] ?? $layout['elements'] ?? [],
            'timeline' => $payload['timeline'] ?? $layout['timeline'] ?? [],
            'dataSources' => $payload['dataSources'] ?? $layout['dataSources'] ?? [],
            'options' => $payload['options'] ?? $layout['options'] ?? [],
        ];

        $stmt = $this->pdo->prepare('UPDATE signage_layouts SET
            name = :name,
            description = :description,
            status = :status_value,
            theme_json = :theme_json,
            canvas_width = :canvas_width,
            canvas_height = :canvas_height,
            layers_json = :layers_json,
            timeline_json = :timeline_json,
            data_sources_json = :data_sources_json,
            options_json = :options_json,
            version = :version,
            updated_by = :updated_by,
            updated_at = :updated_at,
            published_at = CASE WHEN :status_value = "published" THEN COALESCE(published_at, :updated_at) ELSE published_at END
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $payload['description'] ?? $layout['description'] ?? null,
            'status_value' => $status,
            'theme_json' => json_encode($data['options']['theme'] ?? [], JSON_THROW_ON_ERROR),
            'canvas_width' => (int) ($data['canvas']['width'] ?? $layout['canvas']['width'] ?? 1920),
            'canvas_height' => (int) ($data['canvas']['height'] ?? $layout['canvas']['height'] ?? 1080),
            'layers_json' => json_encode(array_values($data['elements']), JSON_THROW_ON_ERROR),
            'timeline_json' => json_encode(array_values($data['timeline']), JSON_THROW_ON_ERROR),
            'data_sources_json' => json_encode(array_values($data['dataSources']), JSON_THROW_ON_ERROR),
            'options_json' => json_encode($data['options'], JSON_THROW_ON_ERROR),
            'version' => $version,
            'updated_by' => $userId,
            'updated_at' => $now,
        ]);

        $this->storeRevision($id, $version, $userId, $data, $comment);

        return $this->getLayout($id) ?? [];
    }

    public function publishLayout(int $id, int $userId): array
    {
        $layout = $this->getLayout($id);
        if (!$layout) {
            throw new RuntimeException('Layout nicht gefunden.');
        }

        $now = (new DateTimeImmutable('now'))->format('c');
        $stmt = $this->pdo->prepare('UPDATE signage_layouts SET status = "published", published_at = :published_at, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'published_at' => $now,
            'updated_by' => $userId,
            'updated_at' => $now,
        ]);

        return $this->getLayout($id) ?? [];
    }

    public function deleteLayout(int $id): void
    {
        $this->pdo->prepare('DELETE FROM signage_layout_revisions WHERE layout_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('UPDATE signage_displays SET assigned_layout_id = NULL WHERE assigned_layout_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('DELETE FROM signage_playlist_items WHERE layout_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('UPDATE signage_playlists SET layout_id = NULL WHERE layout_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('DELETE FROM signage_layouts WHERE id = :id')->execute(['id' => $id]);
    }

    public function duplicateLayout(int $id, int $userId, ?string $newName = null): array
    {
        $layout = $this->getLayout($id);
        if (!$layout) {
            throw new RuntimeException('Layout nicht gefunden.');
        }

        $name = $newName ? trim($newName) : ($layout['name'] . ' Copy');
        $slug = $this->ensureUniqueSlug($this->slugify($name));
        $now = (new DateTimeImmutable('now'))->format('c');

        $stmt = $this->pdo->prepare('INSERT INTO signage_layouts (event_id, name, slug, description, status, theme_json, canvas_width, canvas_height, layers_json, timeline_json, data_sources_json, options_json, version, created_by, updated_by, created_at, updated_at)
            VALUES (:event_id, :name, :slug, :description, :status, :theme_json, :canvas_width, :canvas_height, :layers_json, :timeline_json, :data_sources_json, :options_json, :version, :created_by, :updated_by, :created_at, :updated_at)');
        $stmt->execute([
            'event_id' => $layout['event_id'] ?? null,
            'name' => $name,
            'slug' => $slug,
            'description' => $layout['description'] ?? null,
            'status' => 'draft',
            'theme_json' => json_encode($layout['options']['theme'] ?? [], JSON_THROW_ON_ERROR),
            'canvas_width' => (int) ($layout['canvas']['width'] ?? 1920),
            'canvas_height' => (int) ($layout['canvas']['height'] ?? 1080),
            'layers_json' => json_encode($layout['elements'] ?? [], JSON_THROW_ON_ERROR),
            'timeline_json' => json_encode($layout['timeline'] ?? [], JSON_THROW_ON_ERROR),
            'data_sources_json' => json_encode($layout['dataSources'] ?? [], JSON_THROW_ON_ERROR),
            'options_json' => json_encode($layout['options'] ?? [], JSON_THROW_ON_ERROR),
            'version' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $newId = (int) $this->pdo->lastInsertId();

        $this->storeRevision($newId, 1, $userId, [
            'canvas' => $layout['canvas'] ?? [],
            'elements' => $layout['elements'] ?? [],
            'timeline' => $layout['timeline'] ?? [],
            'dataSources' => $layout['dataSources'] ?? [],
            'options' => $layout['options'] ?? [],
        ], 'Kopie erstellt');

        return $this->getLayout($newId) ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDisplays(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM signage_displays ORDER BY display_group ASC, name ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            $row['settings'] = $this->decode($row['settings_json'] ?? '{}', []);
            unset($row['settings_json']);
            return $row;
        }, $rows);
    }

    public function getDisplayByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM signage_displays WHERE access_token = :token');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['settings'] = $this->decode($row['settings_json'] ?? '{}', []);
        unset($row['settings_json']);

        return $row;
    }

    public function registerDisplay(string $name, string $group, array $payload = []): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Display benötigt einen Namen.');
        }

        $token = $this->generateToken();
        $now = (new DateTimeImmutable('now'))->format('c');
        $stmt = $this->pdo->prepare('INSERT INTO signage_displays (name, display_group, location, description, access_token, assigned_layout_id, assigned_playlist_id, last_seen_at, heartbeat_interval, hardware_info, settings_json, created_at, updated_at)
            VALUES (:name, :display_group, :location, :description, :access_token, :layout_id, :playlist_id, :last_seen_at, :heartbeat_interval, :hardware_info, :settings_json, :created_at, :updated_at)');
        $stmt->execute([
            'name' => $name,
            'display_group' => $group !== '' ? $group : 'default',
            'location' => $payload['location'] ?? null,
            'description' => $payload['description'] ?? null,
            'access_token' => $token,
            'layout_id' => $payload['layout_id'] ?? null,
            'playlist_id' => $payload['playlist_id'] ?? null,
            'last_seen_at' => null,
            'heartbeat_interval' => (int) ($payload['heartbeat_interval'] ?? 60),
            'hardware_info' => $payload['hardware_info'] ?? null,
            'settings_json' => json_encode($payload['settings'] ?? [], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $this->pdo->lastInsertId();

        return $this->getDisplay($id);
    }

    public function updateDisplay(int $id, array $payload): array
    {
        $display = $this->getDisplay($id);
        if (!$display) {
            throw new RuntimeException('Display nicht gefunden.');
        }
        $now = (new DateTimeImmutable('now'))->format('c');
        $stmt = $this->pdo->prepare('UPDATE signage_displays SET name = :name, display_group = :display_group, location = :location, description = :description, assigned_layout_id = :layout_id, assigned_playlist_id = :playlist_id, heartbeat_interval = :heartbeat_interval, settings_json = :settings_json, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => trim((string) ($payload['name'] ?? $display['name'] ?? 'Display')) ?: $display['name'],
            'display_group' => trim((string) ($payload['display_group'] ?? $display['display_group'] ?? 'default')) ?: 'default',
            'location' => $payload['location'] ?? $display['location'] ?? null,
            'description' => $payload['description'] ?? $display['description'] ?? null,
            'layout_id' => $payload['layout_id'] ?? $display['assigned_layout_id'] ?? null,
            'playlist_id' => $payload['playlist_id'] ?? $display['assigned_playlist_id'] ?? null,
            'heartbeat_interval' => (int) ($payload['heartbeat_interval'] ?? $display['heartbeat_interval'] ?? 60),
            'settings_json' => json_encode($payload['settings'] ?? $display['settings'] ?? [], JSON_THROW_ON_ERROR),
            'updated_at' => $now,
        ]);

        return $this->getDisplay($id);
    }

    public function deleteDisplay(int $id): void
    {
        $this->pdo->prepare('DELETE FROM signage_displays WHERE id = :id')->execute(['id' => $id]);
    }

    public function touchDisplay(int $id, ?string $hardwareInfo = null): void
    {
        $stmt = $this->pdo->prepare('UPDATE signage_displays SET last_seen_at = :last_seen, hardware_info = COALESCE(:hardware_info, hardware_info), updated_at = :updated_at WHERE id = :id');
        $now = (new DateTimeImmutable('now'))->format('c');
        $stmt->execute([
            'id' => $id,
            'last_seen' => $now,
            'hardware_info' => $hardwareInfo,
            'updated_at' => $now,
        ]);
    }

    public function getDisplay(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM signage_displays WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['settings'] = $this->decode($row['settings_json'] ?? '{}', []);
        unset($row['settings_json']);

        return $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPlaylists(?string $group = null): array
    {
        $sql = 'SELECT * FROM signage_playlists';
        $params = [];
        if ($group !== null) {
            $sql .= ' WHERE display_group = :group';
            $params['group'] = $group;
        }
        $sql .= ' ORDER BY priority DESC, CASE WHEN starts_at IS NULL THEN 0 ELSE 1 END ASC, starts_at ASC, title ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            $row['items'] = $this->playlistItems((int) $row['id']);
            return $row;
        }, $rows);
    }

    public function getPlaylist(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM signage_playlists WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['items'] = $this->playlistItems($id);

        return $row;
    }

    public function savePlaylist(?int $id, array $data): array
    {
        $payload = [
            'title' => trim((string) ($data['title'] ?? 'Playlist')),
            'display_group' => trim((string) ($data['display_group'] ?? 'default')) ?: 'default',
            'layout_id' => isset($data['layout_id']) ? (int) $data['layout_id'] : null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'rotation_seconds' => max(5, (int) ($data['rotation_seconds'] ?? 30)),
            'priority' => (int) ($data['priority'] ?? 0),
            'is_enabled' => (int) (!empty($data['is_enabled'])),
            'items' => $data['items'] ?? [],
        ];

        $normalizedItems = [];
        $layoutIds = [];
        if ($payload['layout_id']) {
            $layoutIds[] = (int) $payload['layout_id'];
        }
        if (is_array($payload['items'])) {
            foreach ($payload['items'] as $item) {
                $itemLayoutId = isset($item['layout_id']) ? (int) $item['layout_id'] : 0;
                if ($itemLayoutId <= 0) {
                    $itemLayoutId = $payload['layout_id'] ?? 0;
                }
                if ($itemLayoutId <= 0) {
                    throw new ValidationException('PLAYLIST_ITEM_LAYOUT_MISSING', 'Playlist-Element benötigt ein Layout.');
                }
                $layoutIds[] = $itemLayoutId;
                $normalizedItems[] = [
                    'layout_id' => $itemLayoutId,
                    'label' => isset($item['label']) ? trim((string) $item['label']) : null,
                    'duration_seconds' => max(5, (int) ($item['duration_seconds'] ?? $payload['rotation_seconds'])),
                    'options' => is_array($item['options'] ?? null) ? $item['options'] : [],
                ];
            }
        }

        $layoutIds = array_values(array_unique(array_filter($layoutIds, static fn (int $value): bool => $value > 0)));
        if ($layoutIds) {
            $placeholders = implode(',', array_fill(0, count($layoutIds), '?'));
            $stmt = $this->pdo->prepare('SELECT id FROM signage_layouts WHERE id IN (' . $placeholders . ')');
            $stmt->execute($layoutIds);
            $existing = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            sort($existing);
            $expected = $layoutIds;
            sort($expected);
            $missing = array_values(array_diff($expected, $existing));
            if ($missing) {
                throw new ValidationException('PLAYLIST_LAYOUT_INVALID', 'Ungültige Layout-Referenzen: ' . implode(', ', $missing));
            }
        }

        $now = (new DateTimeImmutable('now'))->format('c');
        if ($id === null) {
            $stmt = $this->pdo->prepare('INSERT INTO signage_playlists (layout_id, title, display_group, starts_at, ends_at, rotation_seconds, priority, is_enabled, created_at, updated_at)'
                . ' VALUES (:layout_id, :title, :display_group, :starts_at, :ends_at, :rotation_seconds, :priority, :is_enabled, :created_at, :updated_at)');
            $stmt->execute([
                'layout_id' => $payload['layout_id'],
                'title' => $payload['title'],
                'display_group' => $payload['display_group'],
                'starts_at' => $payload['starts_at'],
                'ends_at' => $payload['ends_at'],
                'rotation_seconds' => $payload['rotation_seconds'],
                'priority' => $payload['priority'],
                'is_enabled' => $payload['is_enabled'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $id = (int) $this->pdo->lastInsertId();
        } else {
            $stmt = $this->pdo->prepare('UPDATE signage_playlists SET layout_id = :layout_id, title = :title, display_group = :display_group, starts_at = :starts_at, ends_at = :ends_at, rotation_seconds = :rotation_seconds, priority = :priority, is_enabled = :is_enabled, updated_at = :updated_at WHERE id = :id');
            $stmt->execute([
                'id' => $id,
                'layout_id' => $payload['layout_id'],
                'title' => $payload['title'],
                'display_group' => $payload['display_group'],
                'starts_at' => $payload['starts_at'],
                'ends_at' => $payload['ends_at'],
                'rotation_seconds' => $payload['rotation_seconds'],
                'priority' => $payload['priority'],
                'is_enabled' => $payload['is_enabled'],
                'updated_at' => $now,
            ]);
            $this->pdo->prepare('DELETE FROM signage_playlist_items WHERE playlist_id = :id')->execute(['id' => $id]);
        }

        if ($normalizedItems) {
            $insert = $this->pdo->prepare('INSERT INTO signage_playlist_items (playlist_id, layout_id, label, duration_seconds, position, options_json) VALUES (:playlist_id, :layout_id, :label, :duration_seconds, :position, :options_json)');
            $position = 0;
            foreach ($normalizedItems as $item) {
                $insert->execute([
                    'playlist_id' => $id,
                    'layout_id' => $item['layout_id'],
                    'label' => $item['label'] ?? null,
                    'duration_seconds' => $item['duration_seconds'],
                    'position' => $position++,
                    'options_json' => json_encode($item['options'], JSON_THROW_ON_ERROR),
                ]);
            }
        }

        return $this->getPlaylist($id) ?? [];
    }

    public function deletePlaylist(int $id): void
    {
        $this->pdo->prepare('DELETE FROM signage_playlist_items WHERE playlist_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('UPDATE signage_displays SET assigned_playlist_id = NULL WHERE assigned_playlist_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('DELETE FROM signage_playlists WHERE id = :id')->execute(['id' => $id]);
    }

    public function resolveDisplayState(string $token): array
    {
        $display = $this->getDisplayByToken($token);
        if (!$display) {
            return [
                'status' => 'error',
                'code' => 'DISPLAY_NOT_REGISTERED',
                'message' => 'Display nicht registriert.',
            ];
        }

        $this->touchDisplay((int) $display['id']);
        $now = new DateTimeImmutable('now');
        $playlist = null;
        if (!empty($display['assigned_playlist_id'])) {
            $candidate = $this->getPlaylist((int) $display['assigned_playlist_id']);
            if ($candidate && $this->playlistIsActive($candidate, $now)) {
                $playlist = $candidate;
            }
        }

        if ($playlist === null) {
            $playlist = $this->findActivePlaylistForGroup($display['display_group'] ?? 'default', $now);
        }

        $layouts = [];
        $activeLayout = null;

        if ($playlist) {
            foreach ($playlist['items'] as $item) {
                $layoutId = (int) ($item['layout_id'] ?? 0);
                if ($layoutId <= 0) {
                    continue;
                }
                if (!isset($layouts[$layoutId])) {
                    $layout = $this->getLayout($layoutId);
                    if ($layout) {
                        $layouts[$layoutId] = $layout;
                    }
                }
            }
            if (!$layouts && !empty($playlist['layout_id'])) {
                $layout = $this->getLayout((int) $playlist['layout_id']);
                if ($layout) {
                    $layouts[(int) $layout['id']] = $layout;
                }
            }
        }

        if (!$playlist) {
            if (!empty($display['assigned_layout_id'])) {
                $activeLayout = $this->getLayout((int) $display['assigned_layout_id']);
            }
            if (!$activeLayout) {
                $activeLayout = $this->getFallbackLayout();
            }
        }

        $resolvedLayouts = array_values($layouts);
        if ($activeLayout && !$layouts) {
            $resolvedLayouts = [$activeLayout];
        }

        $payload = [
            'status' => 'ok',
            'display' => [
                'id' => (int) $display['id'],
                'name' => $display['name'],
                'group' => $display['display_group'],
                'location' => $display['location'],
                'heartbeat' => (int) ($display['heartbeat_interval'] ?? 60),
                'last_seen_at' => $display['last_seen_at'],
            ],
            'generated_at' => $now->format('c'),
            'playlist' => $playlist,
            'layouts' => $resolvedLayouts,
            'active_layout' => $activeLayout,
            'data' => $this->buildDataPayload($activeLayout ?: ($resolvedLayouts[0] ?? null)),
            'sync_token' => sha1(($activeLayout['updated_at'] ?? '') . ($playlist['updated_at'] ?? '') . ($display['updated_at'] ?? '')),
            'cache_ttl' => max(60, (int) ($display['heartbeat_interval'] ?? 60) * 3),
        ];

        return $payload;
    }

    private function playlistItems(int $playlistId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM signage_playlist_items WHERE playlist_id = :id ORDER BY position ASC');
        $stmt->execute(['id' => $playlistId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['options'] = $this->decode($row['options_json'] ?? '{}', []);
            unset($row['options_json']);
        }
        unset($row);

        return $rows;
    }

    private function playlistIsActive(array $playlist, DateTimeImmutable $now): bool
    {
        if (empty($playlist['is_enabled'])) {
            return false;
        }
        if (!empty($playlist['starts_at']) && $now < new DateTimeImmutable($playlist['starts_at'])) {
            return false;
        }
        if (!empty($playlist['ends_at']) && $now > new DateTimeImmutable($playlist['ends_at'])) {
            return false;
        }

        return true;
    }

    private function findActivePlaylistForGroup(string $group, DateTimeImmutable $now): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM signage_playlists WHERE display_group = :group AND is_enabled = 1 ORDER BY priority DESC, CASE WHEN starts_at IS NULL THEN 0 ELSE 1 END ASC, starts_at ASC, updated_at DESC');
        $stmt->execute(['group' => $group]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if ($this->playlistIsActive($row, $now)) {
                $row['items'] = $this->playlistItems((int) $row['id']);
                return $row;
            }
        }

        return null;
    }

    private function buildDataPayload(?array $layout): array
    {
        $activeEvent = \event_active();
        $eventId = $activeEvent['id'] ?? null;

        if ($layout && !empty($layout['event_id'])) {
            $stmt = $this->pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $layout['event_id']]);
            $forcedEvent = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($forcedEvent) {
                $activeEvent = $forcedEvent;
                $eventId = $forcedEvent['id'];
            }
        }

        $eventData = $activeEvent ? [
            'id' => (int) $activeEvent['id'],
            'title' => $activeEvent['title'] ?? '',
            'start_date' => $activeEvent['start_date'] ?? null,
            'end_date' => $activeEvent['end_date'] ?? null,
            'venues' => $this->decode($activeEvent['venues'] ?? '[]', []),
            'branding' => [
                'logo' => \instance_config()->get('branding_logo'),
                'primary_color' => \instance_config()->get('branding_primary'),
            ],
        ] : null;

        $live = $this->fetchLiveData($eventId);
        $schedule = $this->fetchScheduleData($eventId);
        $sponsors = $this->fetchSponsorMessages();
        $clock = [
            'time' => (new DateTimeImmutable('now'))->format('H:i'),
            'iso' => (new DateTimeImmutable('now'))->format('c'),
        ];

        return [
            'event' => $eventData,
            'live' => $live,
            'schedule' => $schedule,
            'sponsors' => $sponsors,
            'clock' => $clock,
        ];
    }

    private function fetchLiveData(?int $eventId): array
    {
        $params = [];
        $where = '';
        if ($eventId !== null) {
            $params['event_id'] = $eventId;
            $where = ' AND e.event_id = :event_id';
        }

        $current = \db_first('SELECT si.position, si.start_number_display, p.display_name AS rider, h.name AS horse, c.label AS class_label
            FROM startlist_items si
            JOIN entries e ON e.id = si.entry_id
            JOIN parties p ON p.id = e.party_id
            JOIN horses h ON h.id = e.horse_id
            JOIN classes c ON c.id = si.class_id
            WHERE si.state = "running"' . $where . '
            ORDER BY si.updated_at DESC LIMIT 1', $params);

        $next = \db_all('SELECT si.position, si.start_number_display, p.display_name AS rider, h.name AS horse
            FROM startlist_items si
            JOIN entries e ON e.id = si.entry_id
            JOIN parties p ON p.id = e.party_id
            JOIN horses h ON h.id = e.horse_id
            WHERE si.state = "scheduled"' . $where . '
            ORDER BY si.planned_start ASC, si.position ASC LIMIT 5', $params);

        $top = \db_all('SELECT r.total, p.display_name AS rider, h.name AS horse
            FROM results r
            JOIN startlist_items si ON si.id = r.startlist_id
            JOIN entries e ON e.id = si.entry_id
            JOIN parties p ON p.id = e.party_id
            JOIN horses h ON h.id = e.horse_id
            WHERE r.status = "released"' . $where . '
            ORDER BY r.total DESC LIMIT 10', $params);

        $position = 1;
        foreach ($top as &$row) {
            $row['position'] = $position++;
        }
        unset($row);

        return [
            'current' => $current,
            'next' => $next,
            'top' => $top,
        ];
    }

    private function fetchScheduleData(?int $eventId): array
    {
        $params = [];
        $where = '';
        if ($eventId !== null) {
            $params['event_id'] = $eventId;
            $where = ' AND c.event_id = :event_id';
        }

        $upcoming = \db_all('SELECT c.label, c.arena, c.start_time, c.end_time
            FROM classes c
            WHERE c.start_time IS NOT NULL' . $where . '
            ORDER BY c.start_time ASC LIMIT 8', $params);

        return [
            'upcoming' => $upcoming,
        ];
    }

    private function fetchSponsorMessages(): array
    {
        $latest = \db_first('SELECT payload FROM notifications WHERE type = "sponsor" ORDER BY id DESC LIMIT 1');
        if (!$latest) {
            return [
                'messages' => [\t('display.defaults.sponsor')],
            ];
        }
        $payload = $this->decode($latest['payload'] ?? '{}', []);
        $text = $payload['text'] ?? null;
        $messages = [];
        if (is_string($text) && trim($text) !== '') {
            $messages[] = trim($text);
        }
        foreach (($payload['messages'] ?? []) as $message) {
            if (is_string($message) && trim($message) !== '') {
                $messages[] = trim($message);
            }
        }
        if (!$messages) {
            $messages[] = \t('display.defaults.sponsor');
        }

        return ['messages' => $messages];
    }

    private function storeRevision(int $layoutId, int $version, int $userId, array $data, ?string $comment = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO signage_layout_revisions (layout_id, version, comment, layers_json, timeline_json, data_sources_json, options_json, theme_json, created_by, created_at)
            VALUES (:layout_id, :version, :comment, :layers_json, :timeline_json, :data_sources_json, :options_json, :theme_json, :created_by, :created_at)');
        $now = (new DateTimeImmutable('now'))->format('c');
        $stmt->execute([
            'layout_id' => $layoutId,
            'version' => $version,
            'comment' => $comment,
            'layers_json' => json_encode($data['elements'] ?? $data['layers'] ?? [], JSON_THROW_ON_ERROR),
            'timeline_json' => json_encode($data['timeline'] ?? [], JSON_THROW_ON_ERROR),
            'data_sources_json' => json_encode($data['dataSources'] ?? [], JSON_THROW_ON_ERROR),
            'options_json' => json_encode($data['options'] ?? [], JSON_THROW_ON_ERROR),
            'theme_json' => json_encode($data['options']['theme'] ?? [], JSON_THROW_ON_ERROR),
            'created_by' => $userId,
            'created_at' => $now,
        ]);

        $revisionId = (int) $this->pdo->lastInsertId();
        $update = $this->pdo->prepare('UPDATE signage_layouts SET current_revision_id = :revision WHERE id = :layout_id');
        $update->execute([
            'revision' => $revisionId,
            'layout_id' => $layoutId,
        ]);
    }

    private function hydrateLayoutRow(array $row, bool $includeJson = false): array
    {
        $row['canvas'] = [
            'width' => (int) ($row['canvas_width'] ?? 1920),
            'height' => (int) ($row['canvas_height'] ?? 1080),
        ];
        if ($includeJson) {
            $row['elements'] = $this->decode($row['layers_json'] ?? '[]', []);
            $row['timeline'] = $this->decode($row['timeline_json'] ?? '[]', []);
            $row['dataSources'] = $this->decode($row['data_sources_json'] ?? '[]', []);
            $row['options'] = $this->decode($row['options_json'] ?? '{}', []);
        }
        unset($row['layers_json'], $row['timeline_json'], $row['data_sources_json'], $row['options_json'], $row['theme_json']);

        return $row;
    }

    private function decode(?string $json, $default)
    {
        if ($json === null || $json === '') {
            return $default;
        }
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'layout-' . substr(bin2hex(random_bytes(4)), 0, 8);
    }

    private function ensureUniqueSlug(string $slug): string
    {
        $base = $slug;
        $counter = 1;
        while (true) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM signage_layouts WHERE slug = :slug');
            $stmt->execute(['slug' => $slug]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $counter;
            $counter++;
        }
    }

    private function generateToken(): string
    {
        do {
            $token = bin2hex(random_bytes(24));
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM signage_displays WHERE access_token = :token');
            $stmt->execute(['token' => $token]);
        } while ((int) $stmt->fetchColumn() > 0);

        return $token;
    }

    private function getFallbackLayout(): ?array
    {
        $stmt = $this->pdo->query('SELECT id FROM signage_layouts ORDER BY updated_at DESC LIMIT 1');
        $id = (int) $stmt->fetchColumn();
        if ($id) {
            return $this->getLayout($id);
        }

        return null;
    }
}
