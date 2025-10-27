<?php

declare(strict_types=1);

namespace App\Modules\LayoutEditor\Models;

final class LayoutSnippet
{
    public function __construct(
        private int $id,
        private ?int $layoutId,
        private string $name,
        private ?string $category,
        private array $metadata,
        private array $snippet,
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
            isset($row['layout_id']) ? (int) $row['layout_id'] : null,
            (string) $row['name'],
            $row['category'] !== null ? (string) $row['category'] : null,
            is_array($row['metadata'] ?? null) ? $row['metadata'] : [],
            is_array($row['snippet'] ?? null) ? $row['snippet'] : [],
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

    public function layoutId(): ?int
    {
        return $this->layoutId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function category(): ?string
    {
        return $this->category;
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
    public function snippet(): array
    {
        return $this->snippet;
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
            'layout_id' => $this->layoutId(),
            'name' => $this->name(),
            'category' => $this->category(),
            'metadata' => $this->metadata(),
            'snippet' => $this->snippet(),
            'created_by' => $this->createdBy(),
            'updated_by' => $this->updatedBy(),
            'created_at' => $this->createdAt(),
            'updated_at' => $this->updatedAt(),
        ];
    }
}
