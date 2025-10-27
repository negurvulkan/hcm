<?php

declare(strict_types=1);

namespace App\Modules\LayoutEditor\Services;

use App\Modules\LayoutEditor\Exceptions\NotFoundException;
use App\Modules\LayoutEditor\Exceptions\ValidationException;
use App\Modules\LayoutEditor\Repositories\LayoutRepository;
use RuntimeException;

class LayoutService
{
    private LayoutRepository $repository;

    public function __construct(?LayoutRepository $repository = null)
    {
        $this->repository = $repository ?? new LayoutRepository();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listLayouts(array $filters = []): array
    {
        $layouts = $this->repository->list($filters);

        return array_map(static fn ($layout) => $layout->toArray(), $layouts);
    }

    public function getLayout(int $id): array
    {
        $layout = $this->repository->find($id);
        if (!$layout) {
            throw new NotFoundException('Layout wurde nicht gefunden.');
        }

        return $layout->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createLayout(array $data, int $userId): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new ValidationException('Name ist erforderlich.');
        }

        $structure = $this->normalizeArray($data['structure'] ?? []);
        $metadata = $this->normalizeArray($data['metadata'] ?? []);
        $description = $this->normalizeNullableString($data['description'] ?? null);
        $slug = $this->normalizeNullableString($data['slug'] ?? null);
        $status = $this->normalizeNullableString($data['status'] ?? null);
        $comment = $this->normalizeNullableString($data['comment'] ?? null);

        $layout = $this->repository->create($name, $structure, $metadata, $userId, $slug, $status, $description, $comment);

        return $layout->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateLayout(int $id, array $data, int $userId): array
    {
        $existing = $this->repository->find($id);
        if (!$existing) {
            throw new NotFoundException('Layout wurde nicht gefunden.');
        }

        $payload = [];
        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                throw new ValidationException('Name darf nicht leer sein.');
            }
            $payload['name'] = $name;
        }

        if (array_key_exists('slug', $data)) {
            $slug = $this->normalizeNullableString($data['slug']);
            $payload['slug'] = $slug ?? $existing->slug();
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $this->normalizeNullableString($data['description']);
        }

        if (array_key_exists('status', $data)) {
            $payload['status'] = $data['status'];
        }

        if (array_key_exists('structure', $data)) {
            $payload['structure'] = $this->normalizeArray($data['structure']);
        }

        if (array_key_exists('metadata', $data)) {
            $payload['metadata'] = $this->normalizeArray($data['metadata']);
        }

        $comment = $this->normalizeNullableString($data['comment'] ?? null);

        $layout = $this->repository->update($id, $payload, $userId, $comment);

        return $layout->toArray();
    }

    public function deleteLayout(int $id): void
    {
        $existing = $this->repository->find($id);
        if (!$existing) {
            throw new NotFoundException('Layout wurde nicht gefunden.');
        }

        $this->repository->delete($id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listVersions(int $layoutId): array
    {
        $layout = $this->repository->find($layoutId);
        if (!$layout) {
            throw new NotFoundException('Layout wurde nicht gefunden.');
        }

        $versions = $this->repository->listVersions($layoutId);

        return array_map(static fn ($version) => $version->toArray(), $versions);
    }

    public function restoreVersion(int $layoutId, int $versionId, int $userId, ?string $comment = null): array
    {
        $layout = $this->repository->find($layoutId);
        if (!$layout) {
            throw new NotFoundException('Layout wurde nicht gefunden.');
        }

        try {
            $restored = $this->repository->restoreVersion($layoutId, $versionId, $userId, $this->normalizeNullableString($comment));
        } catch (RuntimeException $exception) {
            throw new NotFoundException('Layout-Version wurde nicht gefunden.');
        }

        return $restored->toArray();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listSnippets(array $filters = []): array
    {
        if (array_key_exists('layout_id', $filters)) {
            $filters['layout_id'] = $filters['layout_id'] === null ? null : (int) $filters['layout_id'];
        }

        $snippets = $this->repository->listSnippets($filters);

        return array_map(static fn ($snippet) => $snippet->toArray(), $snippets);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createSnippet(array $data, int $userId): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new ValidationException('Snippet benötigt einen Namen.');
        }

        if (!array_key_exists('snippet', $data)) {
            throw new ValidationException('Snippet-Inhalt ist erforderlich.');
        }

        $payload = [
            'layout_id' => $data['layout_id'] ?? null,
            'name' => $name,
            'category' => $this->normalizeNullableString($data['category'] ?? null),
            'metadata' => $this->normalizeArray($data['metadata'] ?? []),
            'snippet' => $this->normalizeArray($data['snippet']),
        ];

        $snippet = $this->repository->createSnippet($payload, $userId);

        return $snippet->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateSnippet(int $id, array $data, int $userId): array
    {
        $snippet = $this->repository->findSnippet($id);
        if (!$snippet) {
            throw new NotFoundException('Snippet wurde nicht gefunden.');
        }

        $payload = [];
        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                throw new ValidationException('Snippet benötigt einen Namen.');
            }
            $payload['name'] = $name;
        }

        if (array_key_exists('layout_id', $data)) {
            $payload['layout_id'] = $data['layout_id'];
        }

        if (array_key_exists('category', $data)) {
            $payload['category'] = $this->normalizeNullableString($data['category']);
        }

        if (array_key_exists('metadata', $data)) {
            $payload['metadata'] = $this->normalizeArray($data['metadata']);
        }

        if (array_key_exists('snippet', $data)) {
            $payload['snippet'] = $this->normalizeArray($data['snippet']);
        }

        $updated = $this->repository->updateSnippet($id, $payload, $userId);

        return $updated->toArray();
    }

    public function deleteSnippet(int $id): void
    {
        $snippet = $this->repository->findSnippet($id);
        if (!$snippet) {
            throw new NotFoundException('Snippet wurde nicht gefunden.');
        }

        $this->repository->deleteSnippet($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeArray(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new ValidationException('Ungültiges Datenformat.');
        }

        return $value;
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
