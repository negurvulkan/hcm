<?php

declare(strict_types=1);

namespace App\Modules\LayoutEditor\Controller;

use App\Modules\LayoutEditor\Exceptions\ValidationException;
use App\Modules\LayoutEditor\Services\LayoutService;

class LayoutEditorController
{
    private LayoutService $service;

    public function __construct(?LayoutService $service = null)
    {
        $this->service = $service ?? new LayoutService();
    }

    /**
     * @param array{query?: array<string, mixed>} $context
     * @return array<string, mixed>
     */
    public function index(array $context): array
    {
        $filters = [];
        $query = $context['query'] ?? [];
        if (!empty($query['status'])) {
            $filters['status'] = (string) $query['status'];
        }
        if (!empty($query['search'])) {
            $filters['search'] = (string) $query['search'];
        }

        return [
            'layouts' => $this->service->listLayouts($filters),
        ];
    }

    /**
     * @param array{params?: array<string, mixed>} $context
     */
    public function show(array $context): array
    {
        $id = $this->requireId($context);

        return [
            'layout' => $this->service->getLayout($id),
        ];
    }

    /**
     * @param array{payload?: array<string, mixed>, user?: array<string, mixed>} $context
     */
    public function store(array $context): array
    {
        $userId = $this->requireUserId($context);
        $payload = $context['payload'] ?? [];

        $layout = $this->service->createLayout($payload, $userId);

        return [
            'layout' => $layout,
        ];
    }

    /**
     * @param array{params?: array<string, mixed>, payload?: array<string, mixed>, user?: array<string, mixed>} $context
     */
    public function update(array $context): array
    {
        $id = $this->requireId($context);
        $userId = $this->requireUserId($context);
        $payload = $context['payload'] ?? [];

        $layout = $this->service->updateLayout($id, $payload, $userId);

        return [
            'layout' => $layout,
        ];
    }

    /**
     * @param array{params?: array<string, mixed>} $context
     */
    public function destroy(array $context): array
    {
        $id = $this->requireId($context);
        $this->service->deleteLayout($id);

        return [
            'deleted' => true,
        ];
    }

    /**
     * @param array{params?: array<string, mixed>} $context
     */
    public function versions(array $context): array
    {
        $id = $this->requireId($context);

        return [
            'versions' => $this->service->listVersions($id),
        ];
    }

    /**
     * @param array{params?: array<string, mixed>, payload?: array<string, mixed>, user?: array<string, mixed>} $context
     */
    public function restore(array $context): array
    {
        $layoutId = $this->requireId($context);
        $userId = $this->requireUserId($context);
        $payload = $context['payload'] ?? [];
        $versionId = isset($payload['version_id']) ? (int) $payload['version_id'] : 0;
        if ($versionId <= 0) {
            throw new ValidationException('Version-ID wird benötigt.', 'VERSION_ID_REQUIRED');
        }

        $comment = $payload['comment'] ?? null;
        $layout = $this->service->restoreVersion($layoutId, $versionId, $userId, is_string($comment) ? $comment : null);

        return [
            'layout' => $layout,
        ];
    }

    /**
     * @param array{query?: array<string, mixed>} $context
     */
    public function listSnippets(array $context): array
    {
        $filters = [];
        $query = $context['query'] ?? [];
        if (array_key_exists('layout_id', $query)) {
            $filters['layout_id'] = $query['layout_id'];
        }
        if (!empty($query['category'])) {
            $filters['category'] = (string) $query['category'];
        }

        return [
            'snippets' => $this->service->listSnippets($filters),
        ];
    }

    /**
     * @param array{payload?: array<string, mixed>, user?: array<string, mixed>} $context
     */
    public function createSnippet(array $context): array
    {
        $userId = $this->requireUserId($context);
        $payload = $context['payload'] ?? [];

        $snippet = $this->service->createSnippet($payload, $userId);

        return [
            'snippet' => $snippet,
        ];
    }

    /**
     * @param array{params?: array<string, mixed>, payload?: array<string, mixed>, user?: array<string, mixed>} $context
     */
    public function updateSnippet(array $context): array
    {
        $id = $this->requireSnippetId($context);
        $userId = $this->requireUserId($context);
        $payload = $context['payload'] ?? [];

        $snippet = $this->service->updateSnippet($id, $payload, $userId);

        return [
            'snippet' => $snippet,
        ];
    }

    /**
     * @param array{params?: array<string, mixed>} $context
     */
    public function deleteSnippet(array $context): array
    {
        $id = $this->requireSnippetId($context);
        $this->service->deleteSnippet($id);

        return [
            'deleted' => true,
        ];
    }

    /**
     * @param array{params?: array<string, mixed>} $context
     */
    private function requireId(array $context): int
    {
        $params = $context['params'] ?? [];
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        if ($id <= 0) {
            throw new ValidationException('Layout-ID wird benötigt.', 'LAYOUT_ID_REQUIRED');
        }

        return $id;
    }

    /**
     * @param array{params?: array<string, mixed>} $context
     */
    private function requireSnippetId(array $context): int
    {
        $params = $context['params'] ?? [];
        $id = isset($params['snippet']) ? (int) $params['snippet'] : 0;
        if ($id <= 0) {
            throw new ValidationException('Snippet-ID wird benötigt.', 'SNIPPET_ID_REQUIRED');
        }

        return $id;
    }

    /**
     * @param array{user?: array<string, mixed>} $context
     */
    private function requireUserId(array $context): int
    {
        $user = $context['user'] ?? [];
        $userId = isset($user['id']) ? (int) $user['id'] : 0;
        if ($userId <= 0) {
            throw new ValidationException('Ungültiger Benutzerkontext.', 'USER_CONTEXT_INVALID');
        }

        return $userId;
    }
}
