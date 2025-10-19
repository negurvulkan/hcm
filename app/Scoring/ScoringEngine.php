<?php

namespace App\Scoring;

use RuntimeException;

class ScoringEngine
{
    public const ENGINE_VERSION = '1.0.0';
    private const EPSILON = 0.000001;

    public function evaluate(array $rule, array $input): array
    {
        $rule = RuleManager::mergeDefaults($rule);
        $validation = $this->validateInput($rule, $input);
        $warnings = [];
        if ($validation !== true) {
            $nonBlocking = array_values(array_diff($validation, ['Zu wenige Richter']));
            if ($nonBlocking) {
                throw new RuntimeException('Ungültige Eingabe: ' . implode(', ', $nonBlocking));
            }
            $warnings = $validation;
        }
        $perJudge = $this->evaluatePerJudge($input, $rule);
        $aggregate = $this->aggregateJudges($perJudge, $rule);
        $totals = $this->applyPenaltiesAndTime($aggregate, $input, $rule, $perJudge);
        if ($warnings) {
            $totals['warnings'] = $warnings;
        }
        $totals['rule_snapshot'] = $this->snapshotRule($rule);
        $totals['engine_version'] = self::ENGINE_VERSION;
        return [
            'per_judge' => $perJudge,
            'aggregate' => $aggregate,
            'totals' => $totals,
            'warnings' => $warnings,
        ];
    }

    public function evaluatePerJudge(array $input, array $rule): array
    {
        $weights = $this->componentWeights($rule);
        $fields = $input['fields'] ?? [];
        $judges = $input['judges'] ?? [];
        $componentDefinitions = $rule['input']['components'] ?? [];
        $result = [];
        foreach ($judges as $key => $judgeInput) {
            $rawValues = $this->mergeJudgeComponentPayload($judgeInput);
            $components = $this->extractJudgeValues($componentDefinitions, $rawValues);
            $context = [
                'components' => $components,
                'fields' => $fields,
                '__weights' => $weights,
            ];
            $formula = $rule['per_judge_formula'] ?? 'sum(components)';
            $score = Expression::evaluate($formula, $context);
            $result[] = [
                'id' => $judgeInput['id'] ?? (is_string($key) ? $key : 'judge_' . $key),
                'score' => (float) $score,
                'components' => $components,
            ];
        }
        return $result;
    }

    public function aggregateJudges(array $perJudge, array $rule): array
    {
        $aggregation = $rule['input']['judges']['aggregation'] ?? [];
        $dropHigh = (int) ($aggregation['drop_high'] ?? 0);
        $dropLow = (int) ($aggregation['drop_low'] ?? 0);
        $method = $aggregation['method'] ?? 'mean';
        $weights = $aggregation['weights'] ?? [];

        $sorted = $perJudge;
        usort($sorted, static fn($a, $b) => $a['score'] <=> $b['score']);
        $droppedIds = [];
        for ($i = 0; $i < $dropLow && $i < count($sorted); $i++) {
            $droppedIds[] = $sorted[$i]['id'];
        }
        for ($i = 0; $i < $dropHigh && $i < count($sorted); $i++) {
            $index = count($sorted) - 1 - $i;
            if ($index >= 0) {
                $droppedIds[] = $sorted[$index]['id'];
            }
        }
        $kept = [];
        foreach ($perJudge as $entry) {
            if (!in_array($entry['id'], $droppedIds, true)) {
                $kept[] = $entry;
            }
        }
        if (!$kept) {
            $kept = $perJudge;
            $droppedIds = [];
        }
        $scores = array_map(static fn($entry) => $entry['score'], $kept);
        switch ($method) {
            case 'median':
                sort($scores);
                $count = count($scores);
                if ($count === 0) {
                    $value = 0.0;
                } elseif ($count % 2 === 1) {
                    $value = (float) $scores[(int) floor($count / 2)];
                } else {
                    $value = ((float) $scores[$count / 2 - 1] + (float) $scores[$count / 2]) / 2;
                }
                break;
            case 'weighted_mean':
                $totalWeight = 0.0;
                $weightedSum = 0.0;
                foreach ($kept as $entry) {
                    $weight = (float) ($weights[$entry['id']] ?? 1.0);
                    $totalWeight += $weight;
                    $weightedSum += $entry['score'] * $weight;
                }
                $value = $totalWeight > 0 ? $weightedSum / $totalWeight : array_sum($scores) / max(count($scores), 1);
                break;
            case 'mean':
            default:
                $value = $scores ? array_sum($scores) / count($scores) : 0.0;
        }

        $componentTotals = [];
        foreach ($kept as $entry) {
            foreach ($this->combinedJudgeComponents($entry) as $componentId => $componentValue) {
                $componentTotals[$componentId][] = (float) $componentValue;
            }
        }
        $componentScores = [];
        foreach ($componentTotals as $componentId => $values) {
            $componentScores[$componentId] = [
                'score' => $values ? array_sum($values) / count($values) : 0.0,
                'judges' => $values,
            ];
        }

        return [
            'score' => (float) $value,
            'scores' => $scores,
            'method' => $method,
            'dropped' => array_values($droppedIds),
            'components' => $componentScores,
        ];
    }

    public function applyPenaltiesAndTime(array $aggregate, array $input, array $rule, array $perJudge): array
    {
        $fields = $input['fields'] ?? [];
        $penaltyDefinitions = $rule['penalties'] ?? [];
        $penalties = [
            'total' => 0.0,
            'applied' => [],
        ];
        $eliminated = false;

        foreach ($penaltyDefinitions as $definition) {
            $context = $this->buildContext($aggregate, $perJudge, $fields, $penalties, null);
            $whenExpression = trim((string) ($definition['when'] ?? ''));
            $shouldApply = $whenExpression === '' ? true : (bool) Expression::evaluate($whenExpression, $context);
            if (!$shouldApply) {
                continue;
            }
            if (!empty($definition['eliminate'])) {
                $eliminated = true;
                $penalties['applied'][] = [
                    'id' => $definition['id'] ?? null,
                    'label' => $definition['label'] ?? 'Elimination',
                    'eliminate' => true,
                ];
                break;
            }
            $pointsExpression = trim((string) ($definition['points'] ?? '0'));
            $points = (float) Expression::evaluate($pointsExpression, $context);
            $penalties['total'] += $points;
            $penalties['applied'][] = [
                'id' => $definition['id'] ?? null,
                'label' => $definition['label'] ?? null,
                'points' => $points,
            ];
        }

        $timeRule = $rule['time'] ?? ['mode' => 'none'];
        $time = $this->evaluateTime($timeRule, $fields, $aggregate, $perJudge, $penalties);
        if (!empty($time['eliminate'])) {
            $eliminated = true;
        }

        $context = $this->buildContext($aggregate, $perJudge, $fields, $penalties, $time);
        $formula = $rule['aggregate_formula'] ?? 'aggregate.score';
        $totalScore = (float) Expression::evaluate($formula, $context);
        $rounded = round($totalScore, (int) ($rule['output']['rounding'] ?? 2));

        return [
            'aggregate' => $aggregate,
            'total_raw' => $totalScore,
            'total_rounded' => $rounded,
            'unit' => $rule['output']['unit'] ?? 'pts',
            'penalties' => $penalties,
            'time' => $time,
            'fields' => $fields,
            'eliminated' => $eliminated,
            'show_breakdown' => (bool) ($rule['output']['show_breakdown'] ?? true),
        ];
    }

    public function rankWithTiebreak(array $totals, array $rule): array
    {
        $order = strtolower($rule['ranking']['order'] ?? 'desc');
        $chain = $rule['ranking']['tiebreak_chain'] ?? [];
        $compare = function (array $a, array $b) use ($order, $chain): int {
            if (($a['eliminated'] ?? false) !== ($b['eliminated'] ?? false)) {
                return ($a['eliminated'] ?? false) ? 1 : -1;
            }
            $cmp = $this->compareFloat($a['total_raw'] ?? 0.0, $b['total_raw'] ?? 0.0, $order);
            if ($cmp !== 0) {
                return $cmp;
            }
            foreach ($chain as $criterion) {
                $cmp = $this->compareCriterion($criterion, $a, $b, $order);
                if ($cmp !== 0) {
                    return $cmp;
                }
            }
            return ($a['start_order'] ?? 0) <=> ($b['start_order'] ?? 0);
        };
        usort($totals, $compare);

        $ranked = [];
        $currentRank = 0;
        $lastEntry = null;
        foreach ($totals as $index => $entry) {
            $tie = $lastEntry && $compare($entry, $lastEntry) === 0;
            if (!$tie) {
                $currentRank = $index + 1;
            }
            $entry['rank'] = $currentRank;
            $entry['tiebreak_path'] = $this->buildTiebreakPath($entry, $chain, $order);
            $ranked[] = $entry;
            $lastEntry = $entry;
        }
        return $ranked;
    }

    public function snapshotRule(array $rule): array
    {
        $normalized = RuleManager::normalizeForSnapshot($rule);
        $json = json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        return [
            'id' => $rule['id'] ?? null,
            'version' => $rule['version'] ?? null,
            'hash' => hash('sha256', $json ?? ''),
            'json' => $json,
        ];
    }

    private function componentWeights(array $rule): array
    {
        $weights = [];
        foreach (($rule['input']['components'] ?? []) as $component) {
            if (!empty($component['id'])) {
                $weights[$component['id']] = (float) ($component['weight'] ?? 1.0);
            }
        }
        return $weights;
    }

    private function buildContext(array $aggregate, array $perJudge, array $fields, array $penalties, ?array $time): array
    {
        return [
            'aggregate' => ['score' => $aggregate['score'] ?? 0.0],
            'per_judge' => array_map(static fn($entry) => $entry['score'], $perJudge),
            'components' => $aggregate['components'] ?? [],
            'fields' => $fields,
            'penalties' => $penalties + ['total' => $penalties['total'] ?? 0.0],
            'time' => $time ?? ['mode' => 'none'],
            '__weights' => $this->componentWeightsFromAggregate($aggregate),
        ];
    }

    private function componentWeightsFromAggregate(array $aggregate): array
    {
        $weights = [];
        foreach ($aggregate['components'] ?? [] as $id => $component) {
            $weights[$id] = 1.0;
        }
        return $weights;
    }

    private function evaluateTime(array $config, array $fields, array $aggregate, array $perJudge, array $penalties): array
    {
        $mode = $config['mode'] ?? 'none';
        $seconds = isset($fields['time_s']) ? (float) $fields['time_s'] : null;
        $allowed = isset($config['allowed_s']) ? (float) $config['allowed_s'] : null;
        $faultPerSecond = isset($config['fault_per_s']) ? (float) $config['fault_per_s'] : 0.0;
        $cap = array_key_exists('cap_s', $config) ? $config['cap_s'] : null;
        $result = [
            'mode' => $mode,
            'seconds' => $seconds,
            'allowed' => $allowed,
            'faults' => 0.0,
            'bonus' => 0.0,
            'cap' => $cap,
        ];
        if ($mode === 'none' || $seconds === null || $allowed === null) {
            return $result;
        }
        $delta = $seconds - $allowed;
        if ($mode === 'faults_from_time') {
            $over = max(0.0, $delta);
            if ($cap !== null) {
                $over = min($over, (float) $cap);
            }
            $faults = $over * $faultPerSecond;
            $result['faults'] = $faults;
            $result['total'] = $faults + ($penalties['total'] ?? 0.0);
        } elseif ($mode === 'score_bonus') {
            $under = max(0.0, -$delta);
            $bonus = $under * $faultPerSecond;
            $result['bonus'] = $bonus;
        }
        return $result;
    }

    private function compareFloat(float $a, float $b, string $order): int
    {
        $diff = $a - $b;
        if (abs($diff) < self::EPSILON) {
            return 0;
        }
        if ($order === 'asc') {
            return $diff < 0 ? -1 : 1;
        }
        return $diff > 0 ? -1 : 1;
    }

    private function compareCriterion(string $criterion, array $a, array $b, string $order): int
    {
        if (str_starts_with($criterion, 'best_component:')) {
            $componentId = substr($criterion, strlen('best_component:'));
            $valueA = $a['aggregate']['components'][$componentId]['score'] ?? 0.0;
            $valueB = $b['aggregate']['components'][$componentId]['score'] ?? 0.0;
            return $this->compareFloat($valueA, $valueB, $order === 'asc' ? 'asc' : 'desc');
        }
        return match ($criterion) {
            'least_time' => $this->compareFloat($a['time']['seconds'] ?? INF, $b['time']['seconds'] ?? INF, 'asc'),
            'lowest_penalties' => $this->compareFloat($a['penalties']['total'] ?? 0.0, $b['penalties']['total'] ?? 0.0, 'asc'),
            'random_draw' => ($a['random_seed'] ?? 0) <=> ($b['random_seed'] ?? 0),
            'run_off' => 0,
            default => 0,
        };
    }

    private function buildTiebreakPath(array $entry, array $chain, string $order): array
    {
        $path = [];
        foreach ($chain as $criterion) {
            if (str_starts_with($criterion, 'best_component:')) {
                $componentId = substr($criterion, strlen('best_component:'));
                $value = $entry['aggregate']['components'][$componentId]['score'] ?? null;
                $path[] = $criterion . '=' . ($value !== null ? round($value, 3) : 'n/a');
                continue;
            }
            switch ($criterion) {
                case 'least_time':
                    $value = $entry['time']['seconds'] ?? null;
                    $path[] = 'least_time=' . ($value !== null ? round($value, 3) : 'n/a');
                    break;
                case 'lowest_penalties':
                    $path[] = 'lowest_penalties=' . round((float) ($entry['penalties']['total'] ?? 0), 3);
                    break;
                case 'random_draw':
                    $path[] = 'random_draw=' . ($entry['random_seed'] ?? 'n/a');
                    break;
                case 'run_off':
                    $path[] = 'run_off';
                    break;
            }
        }
        if (!$chain) {
            $path[] = 'total(' . $order . ')';
        }
        return $path;
    }

    private function validateInput(array $rule, array $input): array|bool
    {
        $errors = [];
        $minJudges = (int) ($rule['input']['judges']['min'] ?? 1);
        $maxJudges = (int) ($rule['input']['judges']['max'] ?? 1);
        $judgeCount = count($input['judges'] ?? []);
        if ($judgeCount < $minJudges) {
            $errors[] = 'Zu wenige Richter';
        }
        if ($maxJudges > 0 && $judgeCount > $maxJudges) {
            $errors[] = 'Zu viele Richter';
        }
        $components = [];
        foreach ($rule['input']['components'] ?? [] as $component) {
            if (!empty($component['id'])) {
                $components[$component['id']] = $component;
            }
        }
        foreach (($input['judges'] ?? []) as $judge) {
            $payload = $this->mergeJudgeComponentPayload($judge);
            foreach ($components as $componentId => $definition) {
                $value = $payload[$componentId] ?? null;
                if ($value === null && !empty($definition['required'])) {
                    $errors[] = 'Komponente ' . $componentId . ' fehlt';
                    continue;
                }
                if ($value !== null) {
                    $floatValue = (float) $value;
                    if (isset($definition['min']) && $floatValue < (float) $definition['min']) {
                        $errors[] = 'Komponente ' . $componentId . ' unter Minimum';
                    }
                    if (isset($definition['max']) && $floatValue > (float) $definition['max']) {
                        $errors[] = 'Komponente ' . $componentId . ' über Maximum';
                    }
                }
            }
        }
        return $errors ?: true;
    }

    private function extractJudgeValues(array $definitions, array $values): array
    {
        $normalized = [];
        foreach ($definitions as $definition) {
            $id = $definition['id'] ?? null;
            if (!$id) {
                continue;
            }
            $raw = $values[$id] ?? null;
            if ($raw === '' || $raw === null) {
                $normalized[$id] = null;
                continue;
            }
            $normalized[$id] = (float) $raw;
        }
        return $normalized;
    }

    private function mergeJudgeComponentPayload(array $judgeInput): array
    {
        $components = is_array($judgeInput['components'] ?? null) ? $judgeInput['components'] : [];
        $lessons = is_array($judgeInput['lessons'] ?? null) ? $judgeInput['lessons'] : [];

        foreach ($lessons as $id => $value) {
            if (!array_key_exists($id, $components)) {
                $components[$id] = $value;
            }
        }

        return $components;
    }

    private function combinedJudgeComponents(array $entry): array
    {
        $components = is_array($entry['components'] ?? null) ? $entry['components'] : [];
        $lessons = is_array($entry['lessons'] ?? null) ? $entry['lessons'] : [];

        foreach ($lessons as $id => $value) {
            if (!array_key_exists($id, $components)) {
                $components[$id] = $value;
            }
        }

        return $components;
    }
}
