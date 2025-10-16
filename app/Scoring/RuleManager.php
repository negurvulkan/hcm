<?php

namespace App\Scoring;

use RuntimeException;

class RuleManager
{
    public static function presets(): array
    {
        return [
            'dressage' => self::dressagePreset(),
            'jumping' => self::jumpingPreset(),
            'western' => self::westernPreset(),
        ];
    }

    public static function mergeDefaults(array $rule): array
    {
        $defaults = [
            'version' => '1',
            'id' => 'generic.score.v1',
            'label' => 'Generic Scoring',
            'input' => [
                'judges' => [
                    'min' => 1,
                    'max' => 5,
                    'aggregation' => [
                        'method' => 'mean',
                        'drop_high' => 0,
                        'drop_low' => 0,
                        'weights' => [],
                    ],
                ],
                'fields' => [
                    ['id' => 'penalties', 'label' => 'Penalties', 'type' => 'set', 'options' => [1, 2, 5]],
                ],
                'components' => [],
            ],
            'penalties' => [],
            'time' => [
                'mode' => 'none',
                'allowed_s' => null,
                'fault_per_s' => 0,
                'cap_s' => null,
            ],
            'per_judge_formula' => 'sum(components)',
            'aggregate_formula' => 'aggregate.score - penalties.total + coalesce(time.bonus, 0) - coalesce(time.faults, 0)',
            'ranking' => [
                'order' => 'desc',
                'tiebreak_chain' => [],
            ],
            'output' => [
                'rounding' => 2,
                'unit' => 'pts',
                'show_breakdown' => true,
            ],
        ];
        return self::recursiveMerge($defaults, $rule);
    }

    public static function normalizeForSnapshot(array $rule): array
    {
        $rule = self::mergeDefaults($rule);
        return self::sortRecursive($rule);
    }

    public static function validate(array $rule): void
    {
        $rule = self::mergeDefaults($rule);
        if (!isset($rule['input']['components']) || !is_array($rule['input']['components'])) {
            throw new RuntimeException('components fehlt');
        }
        foreach ($rule['input']['components'] as $component) {
            if (!isset($component['id'])) {
                throw new RuntimeException('Komponente ohne ID');
            }
        }
    }

    public static function dressagePreset(): array
    {
        return [
            'version' => '1',
            'id' => 'dressage.generic.v1',
            'label' => 'Dressur v1',
            'input' => [
                'judges' => [
                    'min' => 1,
                    'max' => 3,
                    'aggregation' => [
                        'method' => 'mean',
                        'drop_high' => 0,
                        'drop_low' => 0,
                    ],
                ],
                'fields' => [],
                'components' => [
                    ['id' => 'C1', 'label' => 'Trabverstärkungen', 'min' => 0, 'max' => 10, 'step' => 0.5, 'weight' => 1],
                    ['id' => 'C2', 'label' => 'Galoppvolten', 'min' => 0, 'max' => 10, 'step' => 0.5, 'weight' => 1],
                    ['id' => 'C3', 'label' => 'Übergänge', 'min' => 0, 'max' => 10, 'step' => 0.5, 'weight' => 1],
                    ['id' => 'IMP', 'label' => 'Impression', 'min' => 0, 'max' => 10, 'step' => 0.5, 'weight' => 0.5],
                ],
            ],
            'penalties' => [],
            'time' => ['mode' => 'none'],
            'per_judge_formula' => 'weighted(components)',
            'aggregate_formula' => 'aggregate.score - penalties.total',
            'ranking' => [
                'order' => 'desc',
                'tiebreak_chain' => ['best_component:C1', 'best_component:C2', 'random_draw'],
            ],
            'output' => [
                'rounding' => 2,
                'unit' => 'Punkte',
                'show_breakdown' => true,
            ],
        ];
    }

    public static function jumpingPreset(): array
    {
        return [
            'version' => '1',
            'id' => 'jumping.generic.v1',
            'label' => 'Springen v1',
            'input' => [
                'judges' => [
                    'min' => 1,
                    'max' => 1,
                    'aggregation' => [
                        'method' => 'mean',
                    ],
                ],
                'fields' => [
                    ['id' => 'time_s', 'label' => 'Zeit (Sek.)', 'type' => 'number', 'min' => 0],
                    ['id' => 'faults', 'label' => 'Fehlerpunkte', 'type' => 'number', 'min' => 0],
                ],
                'components' => [],
            ],
            'penalties' => [
                ['id' => 'faults', 'when' => 'fields.faults > 0', 'points' => 'fields.faults', 'label' => 'Hindernisfehler'],
            ],
            'time' => [
                'mode' => 'faults_from_time',
                'allowed_s' => 75,
                'fault_per_s' => 1,
            ],
            'per_judge_formula' => '0',
            'aggregate_formula' => 'penalties.total + time.faults',
            'ranking' => [
                'order' => 'asc',
                'tiebreak_chain' => ['least_time', 'random_draw'],
            ],
            'output' => [
                'rounding' => 2,
                'unit' => 'Fehler',
            ],
        ];
    }

    public static function westernPreset(): array
    {
        return [
            'version' => '1',
            'id' => 'western.reining.v1',
            'label' => 'Western / Reining v1',
            'input' => [
                'judges' => [
                    'min' => 3,
                    'max' => 5,
                    'aggregation' => [
                        'method' => 'mean',
                        'drop_high' => 1,
                        'drop_low' => 1,
                    ],
                ],
                'fields' => [],
                'components' => [
                    ['id' => 'M1', 'label' => 'Stop', 'min' => -1.5, 'max' => 1.5, 'step' => 0.5],
                    ['id' => 'M2', 'label' => 'Spin', 'min' => -1.5, 'max' => 1.5, 'step' => 0.5],
                    ['id' => 'M3', 'label' => 'Lead Change', 'min' => -1.5, 'max' => 1.5, 'step' => 0.5],
                ],
            ],
            'penalties' => [
                ['id' => 'p1', 'when' => 'fields.penalties.contains(1)', 'points' => '1', 'label' => 'Kleine Fehler'],
                ['id' => 'p2', 'when' => 'fields.penalties.contains(2)', 'points' => '2', 'label' => 'Mittlere Fehler'],
                ['id' => 'p5', 'when' => 'fields.penalties.contains(5)', 'points' => '5', 'label' => 'Grobe Fehler'],
            ],
            'time' => ['mode' => 'none'],
            'per_judge_formula' => 'sum(components) + 70',
            'aggregate_formula' => 'aggregate.score - penalties.total',
            'ranking' => [
                'order' => 'desc',
                'tiebreak_chain' => ['best_component:M1', 'lowest_penalties', 'random_draw'],
            ],
            'output' => [
                'rounding' => 1,
                'unit' => 'Score',
            ],
        ];
    }

    private static function recursiveMerge(array $defaults, array $rule): array
    {
        foreach ($rule as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])) {
                $defaults[$key] = self::recursiveMerge($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }
        return $defaults;
    }

    private static function sortRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sortRecursive($value);
            }
        }
        if (self::isAssoc($data)) {
            ksort($data);
        }
        return $data;
    }

    private static function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
