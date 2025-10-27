<?php
declare(strict_types=1);

namespace App\Models;

use RuntimeException;

final class Layout
{
    public function __construct(
        public ?int $id,
        public string $uuid,
        public string $slug,
        public string $name,
        public ?string $description,
        public string $category,
        public string $status,
        public int $version,
        public array $data,
        public array $meta,
        public array $assets,
        public ?int $ownerId,
        public ?int $createdBy,
        public ?int $updatedBy,
        public ?int $approvedBy,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $publishedAt,
        public ?string $approvedAt
    ) {
        if ($this->uuid === '') {
            throw new RuntimeException('Layout uuid must not be empty.');
        }
        if ($this->slug === '') {
            throw new RuntimeException('Layout slug must not be empty.');
        }
        if ($this->name === '') {
            throw new RuntimeException('Layout name must not be empty.');
        }
        if ($this->category === '') {
            throw new RuntimeException('Layout category must not be empty.');
        }
        if ($this->status === '') {
            throw new RuntimeException('Layout status must not be empty.');
        }
        if ($this->version <= 0) {
            throw new RuntimeException('Layout version must be positive.');
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) ($row['uuid'] ?? ''),
            (string) ($row['slug'] ?? ''),
            (string) ($row['name'] ?? ''),
            isset($row['description']) && $row['description'] !== '' ? (string) $row['description'] : null,
            (string) ($row['category'] ?? 'general'),
            (string) ($row['status'] ?? 'draft'),
            isset($row['version']) ? max(1, (int) $row['version']) : 1,
            self::decodeJson($row['data_json'] ?? $row['data'] ?? []),
            self::decodeJson($row['meta_json'] ?? $row['meta'] ?? []),
            self::decodeJson($row['assets_json'] ?? $row['assets'] ?? []),
            isset($row['owner_id']) ? (int) $row['owner_id'] : null,
            isset($row['created_by']) ? (int) $row['created_by'] : null,
            isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            isset($row['approved_by']) ? (int) $row['approved_by'] : null,
            isset($row['created_at']) ? (string) $row['created_at'] : null,
            isset($row['updated_at']) ? (string) $row['updated_at'] : null,
            isset($row['published_at']) ? (string) $row['published_at'] : null,
            isset($row['approved_at']) ? (string) $row['approved_at'] : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'status' => $this->status,
            'version' => $this->version,
            'data' => $this->data,
            'meta' => $this->meta,
            'assets' => $this->assets,
            'owner_id' => $this->ownerId,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'approved_by' => $this->approvedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'published_at' => $this->publishedAt,
            'approved_at' => $this->approvedAt,
        ];
    }

    public function withVersion(int $version, array $data, array $meta, array $assets): self
    {
        return new self(
            $this->id,
            $this->uuid,
            $this->slug,
            $this->name,
            $this->description,
            $this->category,
            $this->status,
            $version,
            $data,
            $meta,
            $assets,
            $this->ownerId,
            $this->createdBy,
            $this->updatedBy,
            $this->approvedBy,
            $this->createdAt,
            $this->updatedAt,
            $this->publishedAt,
            $this->approvedAt
        );
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->approvedAt !== null;
    }

    /**
     * @return array<mixed>
     */
    private static function decodeJson(mixed $value): array
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
}
