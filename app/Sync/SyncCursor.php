<?php
namespace App\Sync;

use DateTimeImmutable;
use Exception;

class SyncCursor
{
    private string $value;
    private int $epoch;

    public function __construct(?string $value = null)
    {
        if ($value === null || trim($value) === '') {
            $value = '1970-01-01T00:00:00+00:00';
        }

        $this->value = $this->normalize($value);
        $this->epoch = (int) (new DateTimeImmutable($this->value))->format('U');
    }

    public static function fromArray(array $payload): self
    {
        $value = $payload['value'] ?? null;
        return new self($value);
    }

    /**
     * @return array{value: string, epoch: int}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'epoch' => $this->epoch];
    }

    public function value(): string
    {
        return $this->value;
    }

    public function epoch(): int
    {
        return $this->epoch;
    }

    private function normalize(string $value): string
    {
        try {
            return (new DateTimeImmutable($value))->format('c');
        } catch (Exception $exception) {
            throw new SyncException('INVALID_CURSOR', 'Ungültiger Cursor: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
