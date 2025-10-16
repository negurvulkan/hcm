<?php
namespace App\Sync;

use RuntimeException;
use Throwable;

class SyncException extends RuntimeException
{
    private string $errorCode;
    private int $status;

    public function __construct(string $errorCode, string $message, int $status = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
        $this->errorCode = $errorCode;
        $this->status = $status > 0 ? $status : 400;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array{status: string, code: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'status' => 'error',
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
        ];
    }
}
