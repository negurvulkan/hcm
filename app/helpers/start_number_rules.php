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
