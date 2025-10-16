<?php
namespace App\Core;

use RuntimeException;

class SmartyView
{
    private string $templatePath;
    private array $shared = [];

    public function __construct(string $templatePath)
    {
        $this->templatePath = rtrim($templatePath, '/');
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function render(string $template, array $data = []): string
    {
        $path = $this->templatePath . '/' . $template;
        if (!is_file($path)) {
            throw new RuntimeException('Template nicht gefunden: ' . $template);
        }

        $variables = array_merge($this->shared, $data);
        extract($variables, EXTR_SKIP);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
