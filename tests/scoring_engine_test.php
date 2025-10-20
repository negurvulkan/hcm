<?php
declare(strict_types=1);

use App\Scoring\RuleManager;
use App\Scoring\ScoringEngine;

require __DIR__ . '/../app/Scoring/Expression.php';
require __DIR__ . '/../app/Scoring/RuleManager.php';
require __DIR__ . '/../app/Scoring/ScoringEngine.php';
require __DIR__ . '/../app/helpers/scoring.php';

function assertSame(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(($message ? $message . ': ' : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}

function assertTrue(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new RuntimeException($message ?: 'Assertion failed');
    }
}

$engine = new ScoringEngine();

$rule = RuleManager::dressagePreset();
$input = [
    'fields' => [],
    'judges' => [
        ['id' => 'a', 'components' => ['C1' => 7.5, 'C2' => 8.0, 'C3' => 7.0, 'IMP' => 8.5]],
        ['id' => 'b', 'components' => ['C1' => 7.0, 'C2' => 7.5, 'C3' => 7.0, 'IMP' => 8.0]],
    ],
];
$evaluation = $engine->evaluate($rule, $input);
assertSame(2, count($evaluation['per_judge']), 'Two judges expected');
assertTrue(abs($evaluation['per_judge'][0]['score'] - 7.642857) < 0.001, 'Weighted sum judge A');
assertTrue(abs($evaluation['aggregate']['score'] - 7.464285) < 0.001, 'Average aggregate');
assertTrue(!isset($evaluation['aggregate']['lessons']), 'Legacy lessons breakdown removed');

$western = RuleManager::westernPreset();
$westernInput = [
    'fields' => ['penalties' => [1]],
    'judges' => [
        ['id' => 'j1', 'components' => ['M1' => 1.0, 'M2' => 0.5, 'M3' => 0.0]],
        ['id' => 'j2', 'components' => ['M1' => 0.5, 'M2' => 0.0, 'M3' => -0.5]],
        ['id' => 'j3', 'components' => ['M1' => -1.0, 'M2' => -0.5, 'M3' => -1.0]],
    ],
];
$westernEval = $engine->evaluate($western, $westernInput);
assertTrue(abs($westernEval['aggregate']['score'] - 70.0) < 0.001, 'Drop high/low mean');
assertSame(1.0, $westernEval['totals']['penalties']['total'], 'Penalty applied');

$legacyRule = RuleManager::mergeDefaults([
    'id' => 'legacy.lessons',
    'input' => [
        'judges' => ['min' => 1, 'max' => 1],
        'components' => [
            ['id' => 'A', 'label' => 'A'],
        ],
        'lessons' => [
            ['id' => 'L1', 'label' => 'Legacy', 'min' => 0, 'max' => 10],
        ],
    ],
    'per_judge_formula' => 'sum(components)',
    'aggregate_formula' => 'aggregate.score',
]);
assertTrue(!isset($legacyRule['input']['lessons']), 'Legacy lessons removed from merged rule');
$legacyEval = $engine->evaluate($legacyRule, [
    'fields' => [],
    'judges' => [
        ['id' => 'legacy', 'components' => ['A' => 5.0], 'lessons' => ['L1' => 7.0]],
    ],
]);
assertTrue(abs(($legacyEval['per_judge'][0]['components']['L1'] ?? 0) - 7.0) < 0.001, 'Legacy lesson merged into components');
assertTrue(abs(($legacyEval['per_judge'][0]['components']['A'] ?? 0) - 5.0) < 0.001, 'Component preserved alongside legacy lesson');
assertTrue(isset($legacyEval['aggregate']['components']['L1']), 'Legacy lesson aggregated as component');
assertTrue(!isset($legacyEval['aggregate']['lessons']), 'Aggregate no longer exposes lessons');

$ruleWithElim = RuleManager::mergeDefaults([
    'id' => 'test.elim',
    'input' => [
        'judges' => ['min' => 1, 'max' => 1],
        'components' => [['id' => 'X', 'label' => 'Score', 'min' => 0, 'max' => 10]],
    ],
    'penalties' => [
        ['id' => 'elim', 'when' => 'components.X < 5', 'eliminate' => true],
    ],
    'per_judge_formula' => 'components.X',
    'aggregate_formula' => 'aggregate.score',
]);
$elimEval = $engine->evaluate($ruleWithElim, ['fields' => [], 'judges' => [['id' => 'solo', 'components' => ['X' => 4.0]]]]);
assertTrue($elimEval['totals']['eliminated'] === true, 'Elimination flag expected');

$timeRule = RuleManager::mergeDefaults([
    'id' => 'test.time',
    'input' => [
        'judges' => ['min' => 1, 'max' => 1],
        'components' => [['id' => 'base', 'label' => 'Base']]
    ],
    'time' => [
        'mode' => 'faults_from_time',
        'allowed_s' => 60,
        'fault_per_s' => 0.25,
    ],
    'per_judge_formula' => 'components.base',
    'aggregate_formula' => 'aggregate.score + time.faults',
]);
$timeEval = $engine->evaluate($timeRule, ['fields' => ['time_s' => 70], 'judges' => [['id' => 't', 'components' => ['base' => 4]]]]);
assertTrue(abs($timeEval['totals']['total_raw'] - 6.5) < 0.001, 'Time faults should add');
assertTrue(abs($timeEval['totals']['time']['faults'] - 2.5) < 0.001, 'Time faults value');

$rankRule = RuleManager::mergeDefaults([
    'id' => 'test.rank',
    'ranking' => ['order' => 'asc', 'tiebreak_chain' => ['least_time', 'lowest_penalties']],
    'input' => [
        'judges' => ['min' => 1, 'max' => 1],
        'components' => [['id' => 'base', 'label' => 'Base']],
        'fields' => [['id' => 'time_s', 'type' => 'number']],
    ],
    'per_judge_formula' => 'components.base',
    'aggregate_formula' => 'aggregate.score',
]);
$totals = [
    ['total_raw' => 4.0, 'time' => ['seconds' => 65], 'penalties' => ['total' => 2], 'random_seed' => 1, 'aggregate' => ['components' => []]],
    ['total_raw' => 4.0, 'time' => ['seconds' => 63], 'penalties' => ['total' => 4], 'random_seed' => 2, 'aggregate' => ['components' => []]],
    ['total_raw' => 5.0, 'time' => ['seconds' => 70], 'penalties' => ['total' => 1], 'random_seed' => 3, 'aggregate' => ['components' => []]],
];
$ranked = $engine->rankWithTiebreak($totals, $rankRule);
assertSame(1, $ranked[0]['rank']);
assertSame(2, $ranked[1]['rank']);
assertSame(3, $ranked[2]['rank']);

$newSchemaRule = RuleManager::mergeDefaults([
    'id' => 'dressage.a.fn.v1',
    'label' => 'FN A-Dressur',
    'mode' => 'scale',
    'judges' => [
        'min' => 1,
        'max' => 3,
        'positions' => ['C', 'E', 'M'],
        'aggregationMethod' => 'mean',
        'weights' => ['C' => 1.0, 'E' => 1.0, 'M' => 1.0],
    ],
    'scoring' => [
        'components' => [
            ['id' => 'L1', 'label' => 'Einreiten', 'scoreType' => 'scale', 'min' => 0, 'max' => 10, 'step' => 0.5, 'weight' => 1],
            ['id' => 'C3', 'label' => 'Sitz & Einwirkung', 'scoreType' => 'scale', 'min' => 0, 'max' => 10, 'step' => 0.5, 'weight' => 2],
        ],
        'penalties' => [
            ['id' => 'ERR1', 'label' => 'Aufgabenfehler', 'type' => 'deduction', 'value' => 2],
            ['id' => 'ELIM', 'label' => 'Eliminierung', 'type' => 'elimination', 'eliminate' => true],
        ],
        'time' => ['mode' => 'none'],
        'perJudgeFormula' => 'weighted(components)',
        'aggregateFormula' => 'aggregate.score - penalties.total',
        'rounding' => ['decimals' => 2, 'unit' => '%', 'normalizeToPercent' => true],
        'tiebreakers' => [
            ['type' => 'highestComponent', 'componentId' => 'C3'],
            ['type' => 'random'],
        ],
    ],
]);
assertSame('best_component:C3', $newSchemaRule['ranking']['tiebreak_chain'][0]);
assertSame('random_draw', $newSchemaRule['ranking']['tiebreak_chain'][1]);

$newSchemaInput = [
    'judges' => [
        ['id' => 'C', 'components' => ['L1' => 7.0, 'C3' => 8.0]],
        ['id' => 'E', 'components' => ['L1' => 7.5, 'C3' => 7.5]],
    ],
    'penalties' => ['ERR1'],
];
$newSchemaEval = $engine->evaluate($newSchemaRule, $newSchemaInput);
assertTrue(abs($newSchemaEval['per_judge'][0]['score'] - 7.6666) < 0.01, 'Per judge weighted average');
assertSame(2.0, $newSchemaEval['totals']['penalties']['total'], 'Manual penalty deducted');
assertSame('ERR1', $newSchemaEval['totals']['penalties']['applied'][0]['id']);
assertTrue(isset($newSchemaEval['totals']['normalization_target']) && abs($newSchemaEval['totals']['normalization_target'] - 10.0) < 0.001, 'Normalization target for percent');
assertTrue(abs($newSchemaEval['totals']['total_rounded'] - 55.83) < 0.05, 'Normalized percentage total');
assertSame('%', $newSchemaEval['totals']['unit']);

$departmentRule = RuleManager::mergeDefaults([
    'grouping' => [
        'department' => [
            'enabled' => true,
            'aggregation' => 'mean',
            'rounding' => 1,
            'label' => 'Abteilung',
            'min_members' => 1,
        ],
    ],
]);
$departmentResults = scoring_department_results([
    ['id' => 1, 'department' => 'A1', 'total' => 70.0, 'rank' => 1, 'eliminated' => 0, 'rider' => 'Anna', 'horse' => 'Alpha', 'start_number_display' => '1'],
    ['id' => 2, 'department' => 'A1', 'total' => 68.0, 'rank' => 2, 'eliminated' => 0, 'rider' => 'Ben', 'horse' => 'Beta', 'start_number_display' => '2'],
    ['id' => 3, 'department' => 'B1', 'total' => 60.0, 'rank' => 3, 'eliminated' => 0, 'rider' => 'Cara', 'horse' => 'Gamma', 'start_number_display' => '3'],
], $departmentRule);
assertTrue($departmentResults['enabled'] === true, 'Department results should be enabled');
assertSame(2, count($departmentResults['entries']), 'Two squads aggregated');
assertSame(1, $departmentResults['entries'][0]['rank'], 'First squad rank');
assertTrue($departmentResults['entries'][0]['score'] > $departmentResults['entries'][1]['score'], 'Higher squad score first');

$snapshot = $engine->snapshotRule($rule);
assertTrue(!empty($snapshot['hash']), 'Snapshot hash missing');
assertTrue(str_contains($snapshot['json'], 'dressage'), 'Snapshot json should contain rule id');

echo "Scoring engine tests passed\n";
