<?php

declare(strict_types=1);

namespace App\Modules\LayoutEditor\Exceptions;

class NotFoundException extends LayoutEditorException
{
    public function __construct(string $message, string $errorCode = 'NOT_FOUND', int $status = 404)
    {
        parent::__construct($message, $errorCode, $status);
    }
}
