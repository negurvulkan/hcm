<?php

function start_number_rule_defaults(): array
{
    return [
        'mode' => 'classic',
        'scope' => 'tournament',
        'sequence' => [
            'start' => 1,
            'step' => 1,
            'range' => null,
            'reset' => 'never',
        ],
        'format' => [
            'prefix' => '',
            'width' => 0,
            'suffix' => '',
            'separator' => '',
        ],
        'allocation' => [
            'entity' => 'start',
            'time' => 'on_startlist',
            'reuse' => 'never',
            'lock_after' => 'sign_off',
        ],
        'constraints' => [
            'unique_per' => 'tournament',
            'blocklists' => [],
            'club_spacing' => 0,
            'horse_cooldown_min' => 0,
        ],
        'overrides' => [],
    ];
}

function start_number_rule_merge_defaults(array $rule): array
{
    return array_replace_recursive(start_number_rule_defaults(), $rule);
}

function start_number_rule_safe_json(array $data): string
{
    try {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (\JsonException $e) {
        return '{}';
    }
}

function start_number_rule_presets(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $directory = dirname(__DIR__, 2) . '/storage/presets/start_numbers';
    $cache = [];

    if (is_dir($directory)) {
        $files = glob($directory . '/*.json') ?: [];
        sort($files);
        foreach ($files as $file) {
            $key = basename($file, '.json');
            $contents = file_get_contents($file);
            if ($contents === false) {
                continue;
            }
            try {
                $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                continue;
            }
            if (!is_array($decoded)) {
                continue;
            }
            $ruleData = $decoded['rule'] ?? $decoded;
            if (!is_array($ruleData)) {
                continue;
            }
            $label = null;
            if (isset($decoded['label']) && is_string($decoded['label'])) {
                $label = $decoded['label'];
            } elseif (isset($decoded['meta']['label']) && is_string($decoded['meta']['label'])) {
                $label = $decoded['meta']['label'];
            } elseif (isset($ruleData['label']) && is_string($ruleData['label'])) {
                $label = $ruleData['label'];
            } else {
                $label = start_number_rule_format_label($key);
            }
            $cache[$key] = [
                'label' => $label,
                'rule' => start_number_rule_merge_defaults($ruleData),
            ];
        }
    }

    if ($cache === []) {
        $cache = start_number_rule_fallback_presets();
    }

    return $cache;
}

function start_number_rule_preset_rules(): array
{
    $presets = start_number_rule_presets();
    $rules = [];

    foreach ($presets as $key => $preset) {
        $rules[$key] = $preset['rule'];
    }

    return $rules;
}

function start_number_rule_preset_options(): array
{
    $presets = start_number_rule_presets();
    $options = [];

    foreach ($presets as $key => $preset) {
        $options[] = [
            'key' => $key,
            'label' => $preset['label'] ?? start_number_rule_format_label($key),
        ];
    }

    return $options;
}

function start_number_rule_fallback_presets(): array
{
    return [
        'classic' => [
            'label' => 'Classic (Turnier)',
            'rule' => start_number_rule_merge_defaults([
                'mode' => 'classic',
                'scope' => 'tournament',
                'sequence' => [
                    'start' => 1,
                    'step' => 1,
                    'range' => [1, 450],
                    'reset' => 'per_day',
                ],
                'format' => [
                    'prefix' => '',
                    'width' => 3,
                    'suffix' => '',
                    'separator' => '',
                ],
                'allocation' => [
                    'entity' => 'start',
                    'time' => 'on_startlist',
                    'reuse' => 'after_scratch',
                    'lock_after' => 'start_called',
                ],
                'constraints' => [
                    'unique_per' => 'tournament',
                    'blocklists' => ['13'],
                    'club_spacing' => 1,
                    'horse_cooldown_min' => 0,
                ],
                'overrides' => [],
            ]),
        ],
        'western' => [
            'label' => 'Western (Klassenbasiert)',
            'rule' => start_number_rule_merge_defaults([
                'mode' => 'western',
                'scope' => 'class',
                'sequence' => [
                    'start' => 100,
                    'step' => 5,
                    'range' => [100, 499],
                    'reset' => 'per_class',
                ],
                'format' => [
                    'prefix' => 'W',
                    'width' => 2,
                    'suffix' => '',
                    'separator' => '',
                ],
                'allocation' => [
                    'entity' => 'pair',
                    'time' => 'on_startlist',
                    'reuse' => 'after_scratch',
                    'lock_after' => 'sign_off',
                ],
                'constraints' => [
                    'unique_per' => 'class',
                    'blocklists' => [],
                    'club_spacing' => 0,
                    'horse_cooldown_min' => 30,
                ],
                'overrides' => [],
            ]),
        ],
    ];
}

function start_number_rule_format_label(string $key): string
{
    $key = str_replace(['_', '-'], ' ', $key);

    return ucwords($key);
}
