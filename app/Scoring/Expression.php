<?php

namespace App\Scoring;

use RuntimeException;

class Expression
{
    public static function evaluate(string $expression, array $context): mixed
    {
        $parser = new ExpressionParser($expression);
        $ast = $parser->parse();
        $evaluator = new ExpressionEvaluator($context);
        return $evaluator->evaluate($ast);
    }
}

class ExpressionParser
{
    private string $source;
    private int $length;
    private int $position = 0;
    private array $tokens = [];
    private int $current = 0;

    public function __construct(string $source)
    {
        $this->source = $source;
        $this->length = strlen($source);
        $this->tokens = $this->tokenize();
    }

    public function parse(): array
    {
        $this->current = 0;
        $expr = $this->parseOr();
        $this->expect('EOF');
        return $expr;
    }

    private function tokenize(): array
    {
        $tokens = [];
        while ($this->position < $this->length) {
            $char = $this->source[$this->position];
            if (ctype_space($char)) {
                $this->position++;
                continue;
            }
            if ($char === '(' || $char === ')' || $char === ',' || $char === '.') {
                $tokens[] = ['type' => $char, 'value' => $char];
                $this->position++;
                continue;
            }
            if ($char === '-' || $char === '+' || $char === '*' || $char === '/' || $char === '%') {
                $tokens[] = ['type' => 'OP', 'value' => $char];
                $this->position++;
                continue;
            }
            if ($char === '!' || $char === '<' || $char === '>' || $char === '=' || $char === '&' || $char === '|') {
                $tokens[] = $this->tokenizeOperator();
                continue;
            }
            if ($char === '\"' || $char === "'") {
                $tokens[] = ['type' => 'STRING', 'value' => $this->readString($char)];
                continue;
            }
            if (ctype_digit($char) || ($char === '.' && $this->position + 1 < $this->length && ctype_digit($this->source[$this->position + 1]))) {
                $tokens[] = ['type' => 'NUMBER', 'value' => $this->readNumber()];
                continue;
            }
            if (ctype_alpha($char) || $char === '_') {
                $identifier = $this->readIdentifier();
                $type = in_array($identifier, ['true', 'false', 'null'], true) ? 'LITERAL' : 'IDENT';
                $tokens[] = ['type' => $type, 'value' => $identifier];
                continue;
            }
            throw new RuntimeException('Ungültiges Zeichen in Ausdruck: ' . $char);
        }
        $tokens[] = ['type' => 'EOF', 'value' => null];
        return $tokens;
    }

    private function tokenizeOperator(): array
    {
        $char = $this->source[$this->position];
        $next = $this->position + 1 < $this->length ? $this->source[$this->position + 1] : '';
        $twoChar = $char . $next;
        $multi = ['>=', '<=', '==', '!=', '&&', '||'];
        if (in_array($twoChar, $multi, true)) {
            $this->position += 2;
            return ['type' => 'OP', 'value' => $twoChar];
        }
        $this->position++;
        return ['type' => 'OP', 'value' => $char];
    }

    private function readString(string $quote): string
    {
        $this->position++;
        $buffer = '';
        while ($this->position < $this->length) {
            $char = $this->source[$this->position];
            if ($char === '\\') {
                $this->position++;
                if ($this->position >= $this->length) {
                    break;
                }
                $escape = $this->source[$this->position];
                $map = ['"' => '"', "'" => "'", 'n' => "\n", 'r' => "\r", 't' => "\t", '\\' => '\\'];
                $buffer .= $map[$escape] ?? $escape;
                $this->position++;
                continue;
            }
            if ($char === $quote) {
                $this->position++;
                return $buffer;
            }
            $buffer .= $char;
            $this->position++;
        }
        throw new RuntimeException('Unbeendeter String in Ausdruck');
    }

    private function readNumber(): float
    {
        $start = $this->position;
        $hasDot = false;
        while ($this->position < $this->length) {
            $char = $this->source[$this->position];
            if ($char === '.') {
                if ($hasDot) {
                    break;
                }
                $hasDot = true;
            } elseif (!ctype_digit($char)) {
                break;
            }
            $this->position++;
        }
        $number = substr($this->source, $start, $this->position - $start);
        return (float) $number;
    }

    private function readIdentifier(): string
    {
        $start = $this->position;
        while ($this->position < $this->length) {
            $char = $this->source[$this->position];
            if (!ctype_alnum($char) && $char !== '_') {
                break;
            }
            $this->position++;
        }
        return substr($this->source, $start, $this->position - $start);
    }

    private function parseOr(): array
    {
        $expr = $this->parseAnd();
        while ($this->match('OP', '||')) {
            $expr = ['type' => 'logical', 'operator' => '||', 'left' => $expr, 'right' => $this->parseAnd()];
        }
        return $expr;
    }

    private function parseAnd(): array
    {
        $expr = $this->parseEquality();
        while ($this->match('OP', '&&')) {
            $expr = ['type' => 'logical', 'operator' => '&&', 'left' => $expr, 'right' => $this->parseEquality()];
        }
        return $expr;
    }

    private function parseEquality(): array
    {
        $expr = $this->parseComparison();
        while ($this->check('OP', '==') || $this->check('OP', '!=')) {
            $operator = $this->advance()['value'];
            $expr = ['type' => 'binary', 'operator' => $operator, 'left' => $expr, 'right' => $this->parseComparison()];
        }
        return $expr;
    }

    private function parseComparison(): array
    {
        $expr = $this->parseTerm();
        while ($this->check('OP', '<') || $this->check('OP', '<=') || $this->check('OP', '>') || $this->check('OP', '>=')) {
            $operator = $this->advance()['value'];
            $expr = ['type' => 'binary', 'operator' => $operator, 'left' => $expr, 'right' => $this->parseTerm()];
        }
        return $expr;
    }

    private function parseTerm(): array
    {
        $expr = $this->parseFactor();
        while ($this->check('OP', '+') || $this->check('OP', '-')) {
            $operator = $this->advance()['value'];
            $expr = ['type' => 'binary', 'operator' => $operator, 'left' => $expr, 'right' => $this->parseFactor()];
        }
        return $expr;
    }

    private function parseFactor(): array
    {
        $expr = $this->parseUnary();
        while ($this->check('OP', '*') || $this->check('OP', '/') || $this->check('OP', '%')) {
            $operator = $this->advance()['value'];
            $expr = ['type' => 'binary', 'operator' => $operator, 'left' => $expr, 'right' => $this->parseUnary()];
        }
        return $expr;
    }

    private function parseUnary(): array
    {
        if ($this->check('OP', '!') || $this->check('OP', '-')) {
            $operator = $this->advance()['value'];
            return ['type' => 'unary', 'operator' => $operator, 'operand' => $this->parseUnary()];
        }
        return $this->parseCall();
    }

    private function parseCall(): array
    {
        $expr = $this->parsePrimary();
        while (true) {
            if ($this->match('.')) {
                $segment = $this->consume('IDENT', 'Erwartet Identifier nach Punkt');
                $expr = ['type' => 'property', 'object' => $expr, 'property' => $segment['value']];
                continue;
            }
            if ($this->match('(')) {
                $args = [];
                if (!$this->check(')')) {
                    do {
                        $args[] = $this->parseOr();
                    } while ($this->match(','));
                }
                $this->consume(')', 'Schließende Klammer erwartet');
                $expr = ['type' => 'call', 'callee' => $expr, 'arguments' => $args];
                continue;
            }
            break;
        }
        return $expr;
    }

    private function parsePrimary(): array
    {
        $token = $this->advance();
        switch ($token['type']) {
            case 'NUMBER':
                return ['type' => 'literal', 'value' => $token['value']];
            case 'STRING':
                return ['type' => 'literal', 'value' => $token['value']];
            case 'LITERAL':
                $map = ['true' => true, 'false' => false, 'null' => null];
                return ['type' => 'literal', 'value' => $map[$token['value']] ?? null];
            case 'IDENT':
                return ['type' => 'identifier', 'name' => $token['value']];
            case '(':
                $expr = $this->parseOr();
                $this->consume(')', 'Schließende Klammer erwartet');
                return $expr;
            default:
                throw new RuntimeException('Unerwartetes Token: ' . $token['type']);
        }
    }

    private function match(string $type, ?string $value = null): bool
    {
        if ($this->check($type, $value)) {
            $this->advance();
            return true;
        }
        return false;
    }

    private function consume(string $type, string $message): array
    {
        if ($this->check($type)) {
            return $this->advance();
        }
        throw new RuntimeException($message);
    }

    private function expect(string $type): void
    {
        if (!$this->check($type)) {
            throw new RuntimeException('Token ' . $type . ' erwartet');
        }
    }

    private function check(string $type, ?string $value = null): bool
    {
        $token = $this->tokens[$this->current] ?? null;
        if (!$token || $token['type'] !== $type) {
            return false;
        }
        if ($value !== null) {
            return $token['value'] === $value;
        }
        return true;
    }

    private function advance(): array
    {
        return $this->tokens[$this->current++] ?? ['type' => 'EOF', 'value' => null];
    }
}

class ExpressionEvaluator
{
    private array $context;

    public function __construct(array $context)
    {
        $this->context = $context;
    }

    public function evaluate(array $node): mixed
    {
        return match ($node['type']) {
            'literal' => $node['value'],
            'identifier' => $this->resolveIdentifier($node['name']),
            'property' => $this->resolveProperty($node['object'], $node['property']),
            'call' => $this->evaluateCall($node),
            'unary' => $this->evaluateUnary($node),
            'binary' => $this->evaluateBinary($node),
            'logical' => $this->evaluateLogical($node),
            default => throw new RuntimeException('Unbekannter Knotentyp ' . $node['type']),
        };
    }

    private function resolveIdentifier(string $name): mixed
    {
        return $this->getContextValue($name);
    }

    private function resolveProperty(array $objectNode, string $property): mixed
    {
        $object = $this->evaluate($objectNode);
        if ($property === 'contains' && (is_array($object) || $object instanceof \Traversable)) {
            return function ($needle) use ($object) {
                foreach ($object as $value) {
                    if (is_numeric($value) && is_numeric($needle)) {
                        if ((float) $value === (float) $needle) {
                            return true;
                        }
                    } elseif ($value === $needle) {
                        return true;
                    }
                }
                return false;
            };
        }
        if (is_array($object) && array_key_exists($property, $object)) {
            return $object[$property];
        }
        if (is_object($object) && isset($object->{$property})) {
            return $object->{$property};
        }
        if ($object instanceof ExpressionCallableProvider) {
            return $object->provide($property);
        }
        return null;
    }

    private function evaluateCall(array $node): mixed
    {
        $calleeNode = $node['callee'];
        $callee = $this->evaluate($calleeNode);
        if ($callee instanceof ExpressionCallableProvider) {
            return $callee->invoke($node['arguments'], $this);
        }
        $args = [];
        foreach ($node['arguments'] as $argument) {
            $args[] = $this->evaluate($argument);
        }
        if (is_callable($callee)) {
            return $callee(...$args);
        }
        $function = null;
        if (is_string($callee)) {
            $function = $callee;
        } elseif ($calleeNode['type'] === 'identifier') {
            $function = $calleeNode['name'];
        } elseif ($calleeNode['type'] === 'property' && $calleeNode['object']['type'] === 'identifier') {
            $object = $this->evaluate($calleeNode['object']);
            $method = $calleeNode['property'];
            if (is_callable([$object, $method])) {
                return $object->{$method}(...$args);
            }
        }
        if (!$function) {
            throw new RuntimeException('Unbekannte Funktion');
        }
        return $this->callFunction($function, $args);
    }

    private function evaluateUnary(array $node): mixed
    {
        $value = $this->evaluate($node['operand']);
        return match ($node['operator']) {
            '!' => !$this->truthy($value),
            '-' => -$this->toNumber($value),
            default => throw new RuntimeException('Unbekannter unärer Operator ' . $node['operator']),
        };
    }

    private function evaluateBinary(array $node): mixed
    {
        $left = $this->evaluate($node['left']);
        $right = $this->evaluate($node['right']);
        return match ($node['operator']) {
            '+' => $this->toNumber($left) + $this->toNumber($right),
            '-' => $this->toNumber($left) - $this->toNumber($right),
            '*' => $this->toNumber($left) * $this->toNumber($right),
            '/' => $this->safeDivide($this->toNumber($left), $this->toNumber($right)),
            '%' => $this->safeModulo($this->toNumber($left), $this->toNumber($right)),
            '==' => $left == $right,
            '!=' => $left != $right,
            '<' => $this->toNumber($left) < $this->toNumber($right),
            '<=' => $this->toNumber($left) <= $this->toNumber($right),
            '>' => $this->toNumber($left) > $this->toNumber($right),
            '>=' => $this->toNumber($left) >= $this->toNumber($right),
            default => throw new RuntimeException('Unbekannter Operator ' . $node['operator']),
        };
    }

    private function evaluateLogical(array $node): bool
    {
        if ($node['operator'] === '&&') {
            return $this->truthy($this->evaluate($node['left'])) && $this->truthy($this->evaluate($node['right']));
        }
        if ($node['operator'] === '||') {
            return $this->truthy($this->evaluate($node['left'])) || $this->truthy($this->evaluate($node['right']));
        }
        throw new RuntimeException('Unbekannter Logik-Operator');
    }

    private function callFunction(string $name, array $args): mixed
    {
        $name = strtolower($name);
        return match ($name) {
            'sum' => array_sum(array_map([$this, 'toNumber'], $args)),
            'mean' => ($count = count($args)) ? array_sum(array_map([$this, 'toNumber'], $args)) / $count : 0,
            'min' => min(array_map([$this, 'toNumber'], $args)),
            'max' => max(array_map([$this, 'toNumber'], $args)),
            'if' => $this->truthy($args[0] ?? false) ? ($args[1] ?? null) : ($args[2] ?? null),
            'clamp' => max($this->toNumber($args[1] ?? 0), min($this->toNumber($args[2] ?? 0), $this->toNumber($args[0] ?? 0))),
            'round' => round($this->toNumber($args[0] ?? 0), (int) ($args[1] ?? 0)),
            'coalesce' => $this->coalesce($args),
            'weighted' => $this->weighted($args[0] ?? []),
            'contains' => $this->contains($args[0] ?? [], $args[1] ?? null),
            default => $this->callContextFunction($name, $args),
        };
    }

    private function callContextFunction(string $name, array $args): mixed
    {
        $functions = $this->context['__functions'] ?? [];
        if (!isset($functions[$name]) || !is_callable($functions[$name])) {
            throw new RuntimeException('Unbekannte Funktion ' . $name);
        }
        return $functions[$name](...$args);
    }

    private function weighted(mixed $value): float
    {
        if (!is_array($value)) {
            return 0.0;
        }
        $weights = $this->context['__weights'] ?? [];
        $sum = 0.0;
        $total = 0.0;
        foreach ($value as $key => $component) {
            $weight = $weights[$key] ?? 1.0;
            $sum += $this->toNumber($component) * $weight;
            $total += $weight;
        }
        return $total > 0 ? $sum / $total : 0.0;
    }

    private function contains(mixed $haystack, mixed $needle): bool
    {
        if (is_array($haystack) || $haystack instanceof \Traversable) {
            foreach ($haystack as $value) {
                if ($value === $needle) {
                    return true;
                }
            }
        }
        return false;
    }

    private function coalesce(array $values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    private function safeDivide(float $numerator, float $denominator): float
    {
        if (abs($denominator) < 1e-12) {
            return 0.0;
        }
        return $numerator / $denominator;
    }

    private function safeModulo(float $value, float $mod): float
    {
        if (abs($mod) < 1e-12) {
            return 0.0;
        }
        return fmod($value, $mod);
    }

    private function truthy(mixed $value): bool
    {
        return (bool) $value;
    }

    private function toNumber(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }
        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }
        return 0.0;
    }

    private function getContextValue(string $path): mixed
    {
        $segments = explode('.', $path);
        $value = $this->context;
        foreach ($segments as $segment) {
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
}

interface ExpressionCallableProvider
{
    public function provide(string $name): callable;

    public function invoke(array $arguments, ExpressionEvaluator $evaluator): mixed;
}
