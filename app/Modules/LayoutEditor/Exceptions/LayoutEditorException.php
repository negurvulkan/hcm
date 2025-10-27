<?php

declare(strict_types=1);

namespace App\Modules\LayoutEditor\Exceptions;

use RuntimeException;
use Throwable;

class LayoutEditorException extends RuntimeException
{
    private string $errorCode;

    private int $status;

    public function __construct(string $message, string $errorCode = 'LAYOUT_EDITOR_ERROR', int $status = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->status = $status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }
}
