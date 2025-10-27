<?php

declare(strict_types=1);

namespace App\Modules\LayoutEditor\Exceptions;

class ValidationException extends LayoutEditorException
{
    public function __construct(string $message, string $errorCode = 'VALIDATION_ERROR', int $status = 422)
    {
        parent::__construct($message, $errorCode, $status);
    }
}
