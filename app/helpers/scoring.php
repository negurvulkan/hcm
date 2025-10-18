<?php

use App\Scoring\RuleManager;
use App\Scoring\ScoringEngine;

function scoring_engine(): ScoringEngine
{
    static $engine = null;
    if ($engine === null) {
        $engine = new ScoringEngine();
    }
    return $engine;
}

function scoring_rule_presets(): array
{
    return RuleManager::presets();
}

function scoring_rule_safe_json(array $data): string
{
    try {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (\JsonException $e) {
        return '{}';
    }
}

function scoring_rule_decode(?string $json): ?array
{
    if (!$json) {
        return null;
    }
    try {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : null;
    } catch (\JsonException $e) {
        return null;
    }
}

function scoring_rule_for_event(int $eventId): ?array
{
    $row = db_first('SELECT scoring_rule_json FROM events WHERE id = :id', ['id' => $eventId]);
    if (!$row || empty($row['scoring_rule_json'])) {
        return null;
    }
    return scoring_rule_decode($row['scoring_rule_json']);
}

function scoring_rule_for_class(array $class): array
{
    $rule = null;
    if (!empty($class['rules_json'])) {
        $rule = scoring_rule_decode($class['rules_json']);
    }
    if (!$rule && !empty($class['event_id'])) {
        $rule = scoring_rule_for_event((int) $class['event_id']);
    }
    if (!$rule) {
        $rule = RuleManager::dressagePreset();
    }
    return RuleManager::mergeDefaults($rule);
}

function scoring_simulate(array $rule, int $count = 10): array
{
    $rule = RuleManager::mergeDefaults($rule);
    $engine = scoring_engine();
    $samples = [];
    for ($i = 0; $i < $count; $i++) {
        $input = scoring_fake_input($rule);
        $evaluation = $engine->evaluate($rule, $input);
        $samples[] = [
            'input' => $input,
            'result' => $evaluation['totals'],
        ];
    }
    $totals = array_map(static fn($sample) => $sample['result'], $samples);
    $ranked = $engine->rankWithTiebreak($totals, $rule);
    foreach ($ranked as $index => $entry) {
        $samples[$index]['result'] = $entry;
    }
    return $samples;
}

function scoring_fake_input(array $rule): array
{
    $judgesMin = (int) ($rule['input']['judges']['min'] ?? 1);
    $judgesMax = (int) ($rule['input']['judges']['max'] ?? $judgesMin);
    $judgeCount = max($judgesMin, $judgesMax ? random_int($judgesMin, max($judgesMin, $judgesMax)) : $judgesMin);
    $input = ['judges' => [], 'fields' => []];
    $components = $rule['input']['components'] ?? [];
    for ($j = 0; $j < $judgeCount; $j++) {
        $componentValues = [];
        foreach ($components as $component) {
            $min = isset($component['min']) ? (float) $component['min'] : 0.0;
            $max = isset($component['max']) ? (float) $component['max'] : 10.0;
            $step = isset($component['step']) ? (float) $component['step'] : 0.5;
            $steps = (int) (($max - $min) / max($step, 0.1));
            $value = $min + $step * random_int(0, max(0, $steps));
            $componentValues[$component['id']] = round($value, 3);
        }
        $input['judges'][] = [
            'id' => 'judge_' . ($j + 1),
            'components' => $componentValues,
        ];
    }
    foreach ($rule['input']['fields'] ?? [] as $field) {
        $input['fields'][$field['id']] = scoring_fake_field_value($field);
    }
    return $input;
}

function scoring_fake_field_value(array $field): mixed
{
    return match ($field['type'] ?? 'number') {
        'number' => (float) random_int(0, 100),
        'time' => (float) random_int(30, 120),
        'set' => [],
        'boolean' => (bool) random_int(0, 1),
        'text' => 'Text ' . random_int(1, 99),
        'textarea' => "Notiz " . random_int(1, 99) . "\nWeitere Zeile",
        default => null,
    };
}

function scoring_recalculate_class(int $classId, array $user = null, string $reason = 'auto'): array
{
    $class = db_first('SELECT * FROM classes WHERE id = :id', ['id' => $classId]);
    if (!$class) {
        return [];
    }
    $rule = scoring_rule_for_class($class);
    $engine = scoring_engine();
    $rows = db_all('SELECT r.*, si.position AS start_position FROM results r JOIN startlist_items si ON si.id = r.startlist_id WHERE si.class_id = :class', ['class' => $classId]);
    $evaluations = [];
    $errors = [];
    foreach ($rows as $row) {
        $scores = $row['scores_json'] ? json_decode($row['scores_json'], true, 512, JSON_THROW_ON_ERROR) : null;
        if (!is_array($scores) || empty($scores['input'])) {
            continue;
        }
        try {
            $evaluation = $engine->evaluate($rule, $scores['input']);
        } catch (\Throwable $e) {
            $errors[] = [
                'result_id' => (int) $row['id'],
                'message' => $e->getMessage(),
            ];
            error_log('scoring_recalculate_class failed for result #' . (int) $row['id'] . ': ' . $e->getMessage());
            audit_log('results', (int) $row['id'], 'recalculate_error', null, [
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
            continue;
        }
        $totals = $evaluation['totals'];
        $totals['result_id'] = (int) $row['id'];
        $totals['start_order'] = (int) ($row['start_position'] ?? 0);
        $totals['random_seed'] = (int) $row['id'];
        $evaluations[] = $totals;
        $scores['evaluation'] = $evaluation;
        db_execute(
            'UPDATE results SET scores_json = :scores WHERE id = :id',
            [
                'scores' => json_encode($scores, JSON_THROW_ON_ERROR),
                'id' => (int) $row['id'],
            ]
        );
    }
    if (!$evaluations) {
        if ($errors) {
            error_log('scoring_recalculate_class aborted: all evaluations failed for class ' . $classId);
        }
        return $errors;
    }
    $ranked = $engine->rankWithTiebreak($evaluations, $rule);
    foreach ($ranked as $entry) {
        $update = [
            'total' => $entry['total_rounded'] ?? $entry['total_raw'],
            'penalties' => $entry['penalties']['total'] ?? 0,
            'breakdown' => json_encode([
                'penalties' => $entry['penalties'],
                'time' => $entry['time'],
                'aggregate' => $entry['aggregate'],
                'rule_snapshot' => $entry['rule_snapshot'] ?? null,
            ], JSON_THROW_ON_ERROR),
            'rule_snapshot' => json_encode($entry['rule_snapshot'] ?? null, JSON_THROW_ON_ERROR),
            'engine_version' => $entry['engine_version'] ?? ScoringEngine::ENGINE_VERSION,
            'tiebreak_path' => json_encode($entry['tiebreak_path'] ?? [], JSON_THROW_ON_ERROR),
            'rank' => $entry['rank'] ?? null,
            'eliminated' => !empty($entry['eliminated']) ? 1 : 0,
        ];
        db_execute(
            'UPDATE results SET total = :total, penalties = :penalties, breakdown_json = :breakdown, rule_snapshot = :rule_snapshot, engine_version = :engine_version, tiebreak_path = :tiebreak_path, rank = :rank, eliminated = :eliminated WHERE id = :id',
            $update + ['id' => $entry['result_id']]
        );
        audit_log('results', (int) $entry['result_id'], 'recalculate', null, [
            'reason' => $reason,
            'user' => $user['id'] ?? null,
        ]);
    }
    if ($errors) {
        error_log('scoring_recalculate_class completed with ' . count($errors) . ' error(s) for class ' . $classId);
    }
    return $errors;
}
