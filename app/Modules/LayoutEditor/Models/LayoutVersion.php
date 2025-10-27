<?php

declare(strict_types=1);

namespace App\Modules\LayoutEditor\Models;

final class LayoutVersion
{
    public function __construct(
        private int $id,
        private int $layoutId,
        private int $version,
        private ?string $comment,
        private array $metadata,
        private array $structure,
        private ?int $createdBy,
        private string $createdAt
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['layout_id'],
            (int) $row['version'],
            $row['comment'] !== null ? (string) $row['comment'] : null,
            is_array($row['metadata'] ?? null) ? $row['metadata'] : [],
            is_array($row['structure'] ?? null) ? $row['structure'] : [],
            isset($row['created_by']) ? (int) $row['created_by'] : null,
            (string) $row['created_at']
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function layoutId(): int
    {
        return $this->layoutId;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function comment(): ?string
    {
        return $this->comment;
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

    public function createdBy(): ?int
    {
        return $this->createdBy;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'layout_id' => $this->layoutId(),
            'version' => $this->version(),
            'comment' => $this->comment(),
            'metadata' => $this->metadata(),
            'structure' => $this->structure(),
            'created_by' => $this->createdBy(),
            'created_at' => $this->createdAt(),
        ];
    }
}
