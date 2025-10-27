<?php
declare(strict_types=1);

namespace App\LayoutEditor\Exceptions;

use RuntimeException;

class TemplateSyntaxException extends RuntimeException
{
    private int $offset;
    private int $templateLine;
    private int $templateColumn;

    public function __construct(string $message, int $offset, int $line, int $column)
    {
        parent::__construct($message);
        $this->offset = $offset;
        $this->templateLine = $line;
        $this->templateColumn = $column;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function templateLine(): int
    {
        return $this->templateLine;
    }

    public function templateColumn(): int
    {
        return $this->templateColumn;
    }
}
