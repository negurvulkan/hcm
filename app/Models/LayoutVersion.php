<?php
declare(strict_types=1);

namespace App\Models;

use RuntimeException;

final class LayoutVersion
{
    public function __construct(
        public ?int $id,
        public int $layoutId,
        public int $version,
        public string $status,
        public ?string $comment,
        public array $data,
        public array $meta,
        public array $assets,
        public ?int $createdBy,
        public ?string $createdAt,
        public ?int $approvedBy,
        public ?string $approvedAt
    ) {
        if ($this->layoutId <= 0) {
            throw new RuntimeException('LayoutVersion requires a valid layout id.');
        }
        if ($this->version <= 0) {
            throw new RuntimeException('LayoutVersion version must be positive.');
        }
        if ($this->status === '') {
            throw new RuntimeException('LayoutVersion status must not be empty.');
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (int) ($row['layout_id'] ?? 0),
            (int) ($row['version'] ?? 1),
            (string) ($row['status'] ?? 'draft'),
            isset($row['comment']) && $row['comment'] !== '' ? (string) $row['comment'] : null,
            self::decodeJson($row['data_json'] ?? $row['data'] ?? []),
            self::decodeJson($row['meta_json'] ?? $row['meta'] ?? []),
            self::decodeJson($row['assets_json'] ?? $row['assets'] ?? []),
            isset($row['created_by']) ? (int) $row['created_by'] : null,
            isset($row['created_at']) ? (string) $row['created_at'] : null,
            isset($row['approved_by']) ? (int) $row['approved_by'] : null,
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
            'layout_id' => $this->layoutId,
            'version' => $this->version,
            'status' => $this->status,
            'comment' => $this->comment,
            'data' => $this->data,
            'meta' => $this->meta,
            'assets' => $this->assets,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'approved_by' => $this->approvedBy,
            'approved_at' => $this->approvedAt,
        ];
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
