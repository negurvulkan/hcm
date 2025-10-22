<?php
namespace App\Core;

use RuntimeException;

class SmartyView
{
    private string $templatePath;
    private array $shared = [];
    private array $stacks = [];

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

    public function push(string $stack, callable|string $content): void
    {
        $value = $this->evaluateStackContent($content);
        if (!isset($this->stacks[$stack])) {
            $this->stacks[$stack] = [];
        }
        $this->stacks[$stack][] = $value;
    }

    public function stack(string $stack, string $glue = "\n"): string
    {
        if (!isset($this->stacks[$stack])) {
            return '';
        }

        return implode($glue, $this->stacks[$stack]);
    }

    public function flushStacks(): void
    {
        $this->stacks = [];
    }

    private function evaluateStackContent(callable|string $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        ob_start();
        $result = $content();
        $output = ob_get_clean();

        if ($result !== null) {
            $output .= (string) $result;
        }

        return $output;
    }
}
