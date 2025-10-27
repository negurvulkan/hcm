<?php
declare(strict_types=1);

namespace App\LayoutEditor;

use App\LayoutEditor\Exceptions\TemplateSyntaxException;

class TemplateEngine
{
    /**
     * @throws TemplateSyntaxException
     */
    public function render(string $template, array $data): string
    {
        $ast = $this->parse($template);
        return $this->renderNodes($ast, [['value' => $data, 'meta' => ['index' => null, 'key' => null]]]);
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws TemplateSyntaxException
     */
    public function parse(string $template): array
    {
        $length = strlen($template);
        $position = 0;
        $nodes = [];
        $containers = [&$nodes];
        $sectionStack = [];

        while ($position < $length) {
            $open = strpos($template, '{{', $position);
            if ($open === false) {
                $remaining = substr($template, $position);
                if ($remaining !== '') {
                    $containers[array_key_last($containers)][] = [
                        'type' => 'text',
                        'content' => $remaining,
                    ];
                }
                break;
            }

            if ($open > $position) {
                $text = substr($template, $position, $open - $position);
                if ($text !== '') {
                    $containers[array_key_last($containers)][] = [
                        'type' => 'text',
                        'content' => $text,
                    ];
                }
            }

            $close = strpos($template, '}}', $open + 2);
            if ($close === false) {
                [$line, $column] = $this->positionFromOffset($template, $open);
                throw new TemplateSyntaxException('Unvollständiger Ausdruck – schließende Klammern fehlen.', $open, $line, $column);
            }

            $rawContent = substr($template, $open + 2, $close - $open - 2);
            $content = trim($rawContent);
            [$line, $column] = $this->positionFromOffset($template, $open);
            $position = $close + 2;

            if ($content === '') {
                continue;
            }

            if ($content[0] === '!') {
                // comment, ignore
                continue;
            }

            if ($content[0] === '#') {
                $sectionContent = trim(substr($content, 1));
                if ($sectionContent === '') {
                    throw new TemplateSyntaxException('Sektionsbezeichner fehlt.', $open, $line, $column);
                }

                if (str_starts_with($sectionContent, 'if ')) {
                    $expression = trim(substr($sectionContent, 3));
                    if ($expression === '') {
                        throw new TemplateSyntaxException('IF-Bedingung darf nicht leer sein.', $open, $line, $column);
                    }
                    $node = [
                        'type' => 'section',
                        'sectionType' => 'if',
                        'expression' => $expression,
                        'children' => [],
                        'else' => [],
                        'line' => $line,
                        'column' => $column,
                    ];
                } elseif (str_starts_with($sectionContent, 'each ')) {
                    $expression = trim(substr($sectionContent, 5));
                    if ($expression === '') {
                        throw new TemplateSyntaxException('EACH-Ausdruck darf nicht leer sein.', $open, $line, $column);
                    }
                    $node = [
                        'type' => 'section',
                        'sectionType' => 'each',
                        'expression' => $expression,
                        'children' => [],
                        'else' => [],
                        'line' => $line,
                        'column' => $column,
                    ];
                } else {
                    throw new TemplateSyntaxException('Unbekannte Sektion: ' . $sectionContent, $open, $line, $column);
                }

                $containerIndex = array_key_last($containers);
                $containers[$containerIndex][] = $node;
                $newIndex = array_key_last($containers[$containerIndex]);
                $sectionStack[] = &$containers[$containerIndex][$newIndex];
                $containers[] = &$containers[$containerIndex][$newIndex]['children'];
                continue;
            }

            if ($content[0] === '/') {
                $name = trim(substr($content, 1));
                if (!$sectionStack) {
                    throw new TemplateSyntaxException('Unerwartetes Schließen einer Sektion.', $open, $line, $column);
                }
                $current = &$sectionStack[array_key_last($sectionStack)];
                $expected = $current['sectionType'];
                if ($name !== '' && $name !== $expected) {
                    throw new TemplateSyntaxException("Sektion '/{$name}' schließt '#{$expected}' nicht.", $open, $line, $column);
                }
                array_pop($sectionStack);
                array_pop($containers);
                continue;
            }

            if ($content === 'else') {
                if (!$sectionStack) {
                    throw new TemplateSyntaxException('ELSE ohne zugehörige Sektion.', $open, $line, $column);
                }
                $current = &$sectionStack[array_key_last($sectionStack)];
                if (!empty($current['hasElse'])) {
                    throw new TemplateSyntaxException('Sektion enthält bereits einen ELSE-Block.', $open, $line, $column);
                }
                $current['hasElse'] = true;
                $containers[array_key_last($containers)] = &$current['else'];
                continue;
            }

            $path = $content;
            if ($path === '') {
                throw new TemplateSyntaxException('Platzhalter darf nicht leer sein.', $open, $line, $column);
            }
            $containers[array_key_last($containers)][] = [
                'type' => 'variable',
                'path' => $path,
                'line' => $line,
                'column' => $column,
            ];
        }

        if ($sectionStack) {
            $openSection = $sectionStack[array_key_last($sectionStack)];
            throw new TemplateSyntaxException(
                "Sektion '#{$openSection['sectionType']}' wurde nicht geschlossen.",
                $length,
                $openSection['line'] ?? 0,
                $openSection['column'] ?? 0
            );
        }

        return $nodes;
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<int, array{value: mixed, meta: array{index: int|null, key: string|int|null}}>$contexts
     */
    private function renderNodes(array $nodes, array $contexts): string
    {
        $output = '';
        foreach ($nodes as $node) {
            switch ($node['type']) {
                case 'text':
                    $output .= $node['content'];
                    break;
                case 'variable':
                    $value = $this->resolvePath($node['path'], $contexts);
                    $output .= $this->escape($value);
                    break;
                case 'section':
                    if ($node['sectionType'] === 'if') {
                        $value = $this->resolvePath($node['expression'], $contexts);
                        if ($this->isTruthy($value)) {
                            $output .= $this->renderNodes($node['children'], $contexts);
                        } else {
                            $output .= $this->renderNodes($node['else'], $contexts);
                        }
                    } elseif ($node['sectionType'] === 'each') {
                        $value = $this->resolvePath($node['expression'], $contexts);
                        if (is_array($value) || $value instanceof \Traversable) {
                            $index = 0;
                            foreach ($value as $key => $item) {
                                $childContexts = $contexts;
                                $childContexts[] = ['value' => $item, 'meta' => ['index' => $index, 'key' => $key]];
                                $output .= $this->renderNodes($node['children'], $childContexts);
                                $index++;
                            }
                            if ($index === 0) {
                                $output .= $this->renderNodes($node['else'], $contexts);
                            }
                        } else {
                            $output .= $this->renderNodes($node['else'], $contexts);
                        }
                    }
                    break;
            }
        }

        return $output;
    }

    /**
     * @param array<int, array{value: mixed, meta: array{index: int|null, key: string|int|null}}>$contexts
     */
    private function resolvePath(string $path, array $contexts): mixed
    {
        $trimmed = trim($path);
        $contextIndex = count($contexts) - 1;
        while (str_starts_with($trimmed, '../')) {
            $trimmed = substr($trimmed, 3);
            $contextIndex--;
            if ($contextIndex < 0) {
                return null;
            }
        }

        if ($trimmed === '' || $trimmed === 'this') {
            return $contexts[$contextIndex]['value'];
        }

        $segments = array_values(array_filter(explode('.', $trimmed), static fn ($segment) => $segment !== ''));
        $value = $contexts[$contextIndex]['value'];

        foreach ($segments as $segment) {
            if ($segment === 'this') {
                continue;
            }
            if ($segment === '@index') {
                $value = $contexts[$contextIndex]['meta']['index'];
                continue;
            }
            if ($segment === '@key') {
                $value = $contexts[$contextIndex]['meta']['key'];
                continue;
            }
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }
            if (is_object($value) && isset($value->{$segment})) {
                $value = $value->{$segment};
                continue;
            }
            return null;
        }

        return $value;
    }

    private function escape(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value)) {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if ($value instanceof \Stringable) {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if (is_array($value)) {
            return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function isTruthy(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if ($value === false) {
            return false;
        }
        if (is_string($value)) {
            return trim($value) !== '';
        }
        if (is_numeric($value)) {
            return true;
        }
        if (is_array($value)) {
            return count($value) > 0;
        }
        if ($value instanceof \Countable) {
            return $value->count() > 0;
        }
        return (bool) $value;
    }

    /**
     * @return array{int,int}
     */
    private function positionFromOffset(string $template, int $offset): array
    {
        $before = substr($template, 0, $offset);
        $line = substr_count($before, "\n") + 1;
        $lastNewline = strrpos($before, "\n");
        $column = $lastNewline === false ? $offset + 1 : $offset - $lastNewline;
        return [$line, $column];
    }
}
