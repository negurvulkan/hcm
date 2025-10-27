<?php

declare(strict_types=1);

namespace App\Modules\LayoutEditor\Models;

use DateTimeImmutable;

final class Layout
{
    public function __construct(
        private int $id,
        private string $slug,
        private string $name,
        private ?string $description,
        private string $status,
        private array $metadata,
        private array $structure,
        private int $version,
        private ?int $currentVersionId,
        private ?int $publishedVersionId,
        private ?string $publishedAt,
        private ?string $archivedAt,
        private bool $isLocked,
        private ?int $lockedBy,
        private ?string $lockedAt,
        private ?int $createdBy,
        private ?int $updatedBy,
        private string $createdAt,
        private string $updatedAt
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['slug'],
            (string) $row['name'],
            $row['description'] !== null ? (string) $row['description'] : null,
            (string) ($row['status'] ?? 'draft'),
            is_array($row['metadata'] ?? null) ? $row['metadata'] : [],
            is_array($row['structure'] ?? null) ? $row['structure'] : [],
            (int) ($row['version'] ?? 1),
            isset($row['current_version_id']) ? (int) $row['current_version_id'] : null,
            isset($row['published_version_id']) ? (int) $row['published_version_id'] : null,
            self::normalizeTimestamp($row['published_at'] ?? null),
            self::normalizeTimestamp($row['archived_at'] ?? null),
            (bool) ((int) ($row['is_locked'] ?? 0)),
            isset($row['locked_by']) ? (int) $row['locked_by'] : null,
            self::normalizeTimestamp($row['locked_at'] ?? null),
            isset($row['created_by']) ? (int) $row['created_by'] : null,
            isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            (string) $row['created_at'],
            (string) $row['updated_at']
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function status(): string
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return array<string, mixed>
     */
    public function structure(): array
    {
        return $this->structure;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function currentVersionId(): ?int
    {
        return $this->currentVersionId;
    }

    public function publishedVersionId(): ?int
    {
        return $this->publishedVersionId;
    }

    public function publishedAt(): ?string
    {
        return $this->publishedAt;
    }

    public function archivedAt(): ?string
    {
        return $this->archivedAt;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function lockedBy(): ?int
    {
        return $this->lockedBy;
    }

    public function lockedAt(): ?string
    {
        return $this->lockedAt;
    }

    public function createdBy(): ?int
    {
        return $this->createdBy;
    }

    public function updatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }

    public function updatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'slug' => $this->slug(),
            'name' => $this->name(),
            'description' => $this->description(),
            'status' => $this->status(),
            'metadata' => $this->metadata(),
            'structure' => $this->structure(),
            'version' => $this->version(),
            'current_version_id' => $this->currentVersionId(),
            'published_version_id' => $this->publishedVersionId(),
            'published_at' => $this->publishedAt(),
            'archived_at' => $this->archivedAt(),
            'is_locked' => $this->isLocked(),
            'locked_by' => $this->lockedBy(),
            'locked_at' => $this->lockedAt(),
            'created_by' => $this->createdBy(),
            'updated_by' => $this->updatedBy(),
            'created_at' => $this->createdAt(),
            'updated_at' => $this->updatedAt(),
        ];
    }

    private static function normalizeTimestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value->format('c');
        }

        return (string) $value;
    }
}
