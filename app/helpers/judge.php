<?php

declare(strict_types=1);

function judge_identifier(array $user): string
{
    if (!empty($user['id'])) {
        return 'judge:' . (int) $user['id'];
    }
    $token = $user['email'] ?? $user['name'] ?? uniqid('judge', true);
    return 'judge:' . substr(hash('sha1', $token), 0, 12);
}

function judge_normalize_field_values(array $definitions, array $values): array
{
    $normalized = [];
    foreach ($definitions as $definition) {
        $id = $definition['id'] ?? null;
        if (!$id) {
            continue;
        }
        $type = $definition['type'] ?? 'number';
        $value = $values[$id] ?? ($definition['default'] ?? null);
        if ($type === 'set') {
            $normalized[$id] = is_array($value) ? array_values(array_unique($value)) : [];
        } elseif ($type === 'boolean') {
            $normalized[$id] = (bool) $value;
        } elseif ($type === 'number' || $type === 'time') {
            $normalized[$id] = judge_parse_number($value);
        } elseif ($type === 'text' || $type === 'textarea') {
            if ($value === null) {
                $normalized[$id] = null;
            } else {
                $stringValue = (string) $value;
                $normalized[$id] = $stringValue === '' ? null : $stringValue;
            }
        } else {
            $normalized[$id] = $value;
        }
    }
    return $normalized;
}

function judge_normalize_component_values(array $components, array $values): array
{
    $normalized = [];
    foreach ($components as $component) {
        $id = $component['id'] ?? null;
        if (!$id) {
            continue;
        }
        $normalized[$id] = judge_parse_number($values[$id] ?? null);
    }
    return $normalized;
}

function judge_parse_fields(array $definitions, array $payload): array
{
    $parsed = [];
    foreach ($definitions as $definition) {
        $id = $definition['id'] ?? null;
        if (!$id) {
            continue;
        }
        $type = $definition['type'] ?? 'number';
        $raw = $payload[$id] ?? null;
        if ($type === 'set') {
            $parsed[$id] = is_array($raw)
                ? array_values(array_unique(array_filter($raw, static fn($item) => $item !== '' && $item !== null)))
                : [];
        } elseif ($type === 'boolean') {
            $parsed[$id] = !empty($raw);
        } elseif ($type === 'number' || $type === 'time') {
            $parsed[$id] = judge_parse_number($raw);
        } elseif ($type === 'text' || $type === 'textarea') {
            if ($raw === null) {
                $parsed[$id] = null;
            } else {
                $stringValue = (string) $raw;
                $parsed[$id] = $stringValue === '' ? null : $stringValue;
            }
        } else {
            $parsed[$id] = $raw;
        }
    }
    return $parsed;
}

function judge_parse_components(array $definitions, array $payload): array
{
    $parsed = [];
    foreach ($definitions as $definition) {
        $id = $definition['id'] ?? null;
        if (!$id) {
            continue;
        }
        $parsed[$id] = judge_parse_number($payload[$id] ?? null);
    }
    return $parsed;
}

function judge_parse_number(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    if (is_float($value) || is_int($value)) {
        return (float) $value;
    }
    if (is_bool($value)) {
        return $value ? 1.0 : 0.0;
    }
    if (is_string($value)) {
        $normalized = str_replace("\u{00A0}", '', trim($value));
        if ($normalized === '') {
            return null;
        }
        $commaPos = strrpos($normalized, ',');
        $dotPos = strrpos($normalized, '.');
        if ($commaPos !== false && $dotPos !== false) {
            if ($commaPos > $dotPos) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($commaPos !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }
        $normalized = preg_replace('/\s+/u', '', $normalized);
        if ($normalized === '' || !is_numeric($normalized)) {
            return (float) $normalized;
        }
        return (float) $normalized;
    }
    if (is_numeric($value)) {
        return (float) $value;
    }
    return null;
}
