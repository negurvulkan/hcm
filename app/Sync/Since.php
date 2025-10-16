<?php
namespace App\Sync;

use DateTimeImmutable;
use Exception;

class Since
{
    private SyncCursor $cursor;

    public function __construct(?string $value = null)
    {
        $this->cursor = new SyncCursor($value);
    }

    public function value(): string
    {
        return $this->cursor->value();
    }

    public function epoch(): int
    {
        return $this->cursor->epoch();
    }

    public function toCursor(): SyncCursor
    {
        return $this->cursor;
    }

    public static function fromMixed(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof SyncCursor) {
            return new self($value->value());
        }

        if (is_string($value) || $value === null) {
            return new self($value);
        }

        if (is_array($value) && isset($value['value'])) {
            return new self((string) $value['value']);
        }

        throw new SyncException('INVALID_CURSOR', 'Ungültiger Cursor übergeben.');
    }

    public function isBeforeNow(): bool
    {
        try {
            $now = new DateTimeImmutable();
            return $this->epoch() <= (int) $now->format('U');
        } catch (Exception) {
            return true;
        }
    }
}
