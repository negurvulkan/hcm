<?php

namespace App\Signage;

use App\Signage\Exceptions\NotFoundException;
use App\Signage\Exceptions\ValidationException;

class SignageApiHandler
{
    private SignageRepository $repository;

    public function __construct(SignageRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function perform(string $action, array $payload, array $context = []): array
    {
        $userId = (int) ($context['user_id'] ?? 0);

        switch ($action) {
            case 'create_layout':
                $name = (string) ($payload['name'] ?? 'Neues Layout');
                $eventId = $payload['event_id'] ?? null;
                $layout = $this->repository->createLayout($name, $eventId, $userId, $payload);

                return ['layout' => $layout];

            case 'update_layout':
                $layoutId = (int) ($payload['id'] ?? 0);
                if ($layoutId <= 0 || !$this->repository->getLayout($layoutId)) {
                    throw new NotFoundException('LAYOUT_NOT_FOUND', 'Layout nicht gefunden.');
                }
                $layout = $this->repository->updateLayout($layoutId, $payload, $userId, $payload['comment'] ?? null);

                return ['layout' => $layout];

            case 'publish_layout':
                $layoutId = (int) ($payload['id'] ?? 0);
                if ($layoutId <= 0 || !$this->repository->getLayout($layoutId)) {
                    throw new NotFoundException('LAYOUT_NOT_FOUND', 'Layout nicht gefunden.');
                }
                $layout = $this->repository->publishLayout($layoutId, $userId);

                return ['layout' => $layout];

            case 'duplicate_layout':
                $layoutId = (int) ($payload['id'] ?? 0);
                if ($layoutId <= 0 || !$this->repository->getLayout($layoutId)) {
                    throw new NotFoundException('LAYOUT_NOT_FOUND', 'Layout nicht gefunden.');
                }
                $layout = $this->repository->duplicateLayout($layoutId, $userId, $payload['name'] ?? null);

                return ['layout' => $layout];

            case 'delete_layout':
                $layoutId = (int) ($payload['id'] ?? 0);
                if ($layoutId <= 0 || !$this->repository->getLayout($layoutId)) {
                    throw new NotFoundException('LAYOUT_NOT_FOUND', 'Layout nicht gefunden.');
                }
                $this->repository->deleteLayout($layoutId);

                return [];

            case 'register_display':
                $display = $this->repository->registerDisplay(
                    (string) ($payload['name'] ?? 'Display'),
                    (string) ($payload['display_group'] ?? 'default'),
                    $payload
                );

                return ['display' => $display];

            case 'update_display':
                $displayId = (int) ($payload['id'] ?? 0);
                if ($displayId <= 0 || !$this->repository->getDisplay($displayId)) {
                    throw new NotFoundException('DISPLAY_NOT_FOUND', 'Display nicht gefunden.');
                }
                $display = $this->repository->updateDisplay($displayId, $payload);

                return ['display' => $display];

            case 'delete_display':
                $displayId = (int) ($payload['id'] ?? 0);
                if ($displayId <= 0 || !$this->repository->getDisplay($displayId)) {
                    throw new NotFoundException('DISPLAY_NOT_FOUND', 'Display nicht gefunden.');
                }
                $this->repository->deleteDisplay($displayId);

                return [];

            case 'save_playlist':
                $playlistId = isset($payload['id']) ? (int) $payload['id'] : null;
                if ($playlistId !== null && !$this->repository->getPlaylist($playlistId)) {
                    throw new NotFoundException('PLAYLIST_NOT_FOUND', 'Playlist nicht gefunden.');
                }
                $playlist = $this->repository->savePlaylist($playlistId, $payload);

                return ['playlist' => $playlist];

            case 'delete_playlist':
                $playlistId = (int) ($payload['id'] ?? 0);
                if ($playlistId <= 0 || !$this->repository->getPlaylist($playlistId)) {
                    throw new NotFoundException('PLAYLIST_NOT_FOUND', 'Playlist nicht gefunden.');
                }
                $this->repository->deletePlaylist($playlistId);

                return [];

            default:
                throw new ValidationException('ACTION_UNKNOWN', 'Unbekannte Aktion: ' . $action);
        }
    }
}
