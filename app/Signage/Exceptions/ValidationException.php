<?php

namespace App\Signage\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    private string $errorCode;

    public function __construct(string $errorCode, string $message)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
