<?php
declare(strict_types=1);

namespace App\Layouts;

use App\Models\Layout;
use App\Models\LayoutVersion;
use RuntimeException;

class LayoutController
{
    public function __construct(private LayoutRepository $repository)
    {
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function index(array $filters = []): array
    {
        $normalized = [
            'search' => isset($filters['search']) ? trim((string) $filters['search']) : '',
            'category' => isset($filters['category']) ? (string) $filters['category'] : 'all',
            'status' => isset($filters['status']) ? (string) $filters['status'] : 'all',
            'owner_id' => isset($filters['owner_id']) ? (int) $filters['owner_id'] : null,
        ];

        $layouts = $this->repository->listLayouts($normalized);
        $categories = array_merge(['all'], LayoutRepository::CATEGORIES);
        $statuses = array_merge(['all'], LayoutRepository::STATUSES);

        return [
            'filters' => $normalized,
            'layouts' => $layouts,
            'categories' => $categories,
            'statuses' => $statuses,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $user
     */
    public function create(array $payload, array $user): Layout
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Name des Layouts fehlt.');
        }
        $category = (string) ($payload['category'] ?? 'general');
        $status = (string) ($payload['status'] ?? 'draft');
        $description = isset($payload['description']) ? trim((string) $payload['description']) : null;
        $ownerId = isset($payload['owner_id']) && $payload['owner_id'] !== '' ? (int) $payload['owner_id'] : null;
        $data = $this->ensureArray($payload['data'] ?? []);
        $meta = $this->ensureArray($payload['meta'] ?? []);
        $assets = $this->ensureArrayList($payload['assets'] ?? []);

        return $this->repository->createLayout(
            $name,
            $category,
            $data,
            $meta,
            $assets,
            $ownerId,
            (int) ($user['id'] ?? 0),
            $status,
            $description
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $user
     */
    public function updateMetadata(int $layoutId, array $payload, array $user): Layout
    {
        return $this->repository->updateMetadata($layoutId, $payload, (int) ($user['id'] ?? 0));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $user
     */
    public function createVersion(int $layoutId, array $payload, array $user): LayoutVersion
    {
        $data = $this->ensureArray($payload['data'] ?? []);
        $meta = $this->ensureArray($payload['meta'] ?? []);
        $assets = $this->ensureArrayList($payload['assets'] ?? []);
        $status = (string) ($payload['status'] ?? 'in_review');
        $comment = isset($payload['comment']) && $payload['comment'] !== '' ? (string) $payload['comment'] : null;
        $targetVersion = isset($payload['version']) && $payload['version'] !== '' ? (int) $payload['version'] : null;

        return $this->repository->createVersion(
            $layoutId,
            $data,
            $meta,
            $assets,
            (int) ($user['id'] ?? 0),
            $status,
            $comment,
            $targetVersion
        );
    }

    public function approve(int $layoutId, int $version, array $user, ?string $comment = null): Layout
    {
        return $this->repository->approveVersion($layoutId, $version, (int) ($user['id'] ?? 0), $comment);
    }

    public function duplicate(int $layoutId, array $user, ?string $name = null): Layout
    {
        return $this->repository->duplicateLayout($layoutId, (int) ($user['id'] ?? 0), $name);
    }

    public function delete(int $layoutId): void
    {
        $this->repository->deleteLayout($layoutId);
    }

    public function export(int $layoutId, ?int $version = null): string
    {
        return $this->repository->exportLayout($layoutId, $version);
    }

    public function import(string $path, array $user): Layout
    {
        return $this->repository->importLayout($path, (int) ($user['id'] ?? 0));
    }

    /**
     * @param mixed $value
     *
     * @return array<string, mixed>
     */
    private function ensureArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value === null || $value === '') {
            return [];
        }
        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                return is_array($decoded) ? $decoded : [];
            } catch (\Throwable) {
                return [];
            }
        }
        return [];
    }

    /**
     * @param mixed $value
     *
     * @return array<int, array<string, mixed>>
     */
    private function ensureArrayList(mixed $value): array
    {
        $array = $this->ensureArray($value);
        return array_values(array_filter(
            array_map(static fn ($item): ?array => is_array($item) ? $item : null, $array),
            static fn (?array $item): bool => $item !== null
        ));
    }
}
