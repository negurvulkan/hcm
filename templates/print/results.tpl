<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars(t('print.pdf.results.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
    </style>
</head>
<body>
<?php
/** @var array $items */
/** @var array|null $class */

$heading = !empty($class['label'])
    ? t('print.pdf.results.heading_with_class', ['class' => $class['label']])
    : t('print.pdf.results.heading');
$eventLabel = t('print.pdf.results.event_label');
$strings = [
    'position' => t('print.pdf.results.table.rank'),
    'start_number' => t('print.pdf.results.table.start_number'),
    'rider' => t('print.pdf.results.table.rider'),
    'horse' => t('print.pdf.results.table.horse'),
    'total' => t('print.pdf.results.table.total'),
    'eliminated' => t('print.pdf.results.badges.eliminated'),
    'penalties' => t('print.pdf.results.sections.penalties'),
    'penalties_none' => t('print.pdf.results.sections.penalties_none'),
    'penalty_default' => t('print.pdf.results.penalties.default_label'),
    'penalty_elimination' => t('print.pdf.results.penalties.elimination_label'),
    'time' => t('print.pdf.results.sections.time'),
    'time_target' => t('print.pdf.results.time_details.target'),
    'time_faults' => t('print.pdf.results.time_details.faults'),
    'time_bonus' => t('print.pdf.results.time_details.bonus'),
    'components' => t('print.pdf.results.sections.components'),
    'tiebreak' => t('print.pdf.results.sections.tiebreak'),
    'rule' => t('print.pdf.results.sections.rule'),
    'rule_engine' => t('print.pdf.results.rule_engine_prefix'),
    'empty' => t('print.pdf.results.empty'),
];
?>
<h1><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
<?php if (!empty($class['event_title'])): ?>
    <p><strong><?= htmlspecialchars($eventLabel, ENT_QUOTES, 'UTF-8') ?></strong> <?= htmlspecialchars($class['event_title'], ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<table>
    <thead>
    <tr>
        <th><?= htmlspecialchars($strings['position'], ENT_QUOTES, 'UTF-8') ?></th>
        <th><?= htmlspecialchars($strings['start_number'], ENT_QUOTES, 'UTF-8') ?></th>
        <th><?= htmlspecialchars($strings['rider'], ENT_QUOTES, 'UTF-8') ?></th>
        <th><?= htmlspecialchars($strings['horse'], ENT_QUOTES, 'UTF-8') ?></th>
        <th><?= htmlspecialchars($strings['total'], ENT_QUOTES, 'UTF-8') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $index => $item): ?>
        <?php
        $breakdown = $item['breakdown'] ?? [];
        $totals = $breakdown['totals'] ?? [];
        $penalties = $breakdown['penalties'] ?? ($totals['penalties'] ?? []);
        $timeInfo = $breakdown['time'] ?? ($totals['time'] ?? []);
        $aggregate = $breakdown['aggregate'] ?? ($totals['aggregate'] ?? []);
        $components = $aggregate['components'] ?? [];
        $componentDefinitions = [];
        if (!empty($item['rule_details']['input']['components']) && is_array($item['rule_details']['input']['components'])) {
            foreach ($item['rule_details']['input']['components'] as $definition) {
                if (empty($definition['id'])) {
                    continue;
                }
                $componentDefinitions[$definition['id']] = [
                    'label' => is_string($definition['label'] ?? null) && $definition['label'] !== ''
                        ? $definition['label']
                        : $definition['id'],
                    'weight' => isset($definition['weight']) ? (float) $definition['weight'] : null,
                ];
            }
        }
        $unit = $item['unit'] ?? ($aggregate['unit'] ?? null);
        $rank = $item['rank'] ?? ($index + 1);
        ?>
        <tr>
            <td><?= htmlspecialchars((string) $rank, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['start_number_display'] ?? $item['start_number_raw'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['rider'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['horse'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <?= htmlspecialchars(format_number($item['total'] ?? 0, 2), ENT_QUOTES, 'UTF-8') ?><?= $unit ? ' ' . htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') : '' ?>
                <?php if (!empty($item['eliminated'])): ?>
                    <strong style="margin-left: 6px; color: #c00;"><?= htmlspecialchars($strings['eliminated'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td colspan="5">
                <?php $applied = $penalties['applied'] ?? []; ?>
                <div><strong><?= htmlspecialchars($strings['penalties'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if ($applied): ?>
                        <?= htmlspecialchars(format_number($penalties['total'] ?? 0, 2), ENT_QUOTES, 'UTF-8') ?>
                        <?php
                        $penaltyTexts = [];
                        foreach ($applied as $penalty) {
                            if (!empty($penalty['eliminate'])) {
                                $penaltyTexts[] = ($penalty['label'] ?? $strings['penalty_elimination']);
                            } elseif (isset($penalty['points'])) {
                                $penaltyTexts[] = ($penalty['label'] ?? $strings['penalty_default']) . ' (' . format_number($penalty['points'], 2) . ')';
                            }
                        }
                        if ($penaltyTexts): ?>
                            – <?= htmlspecialchars(implode(', ', $penaltyTexts), ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= htmlspecialchars($strings['penalties_none'], ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </div>
                <div><strong><?= htmlspecialchars($strings['time'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if (isset($timeInfo['seconds'])): ?>
                        <?= htmlspecialchars(format_number($timeInfo['seconds'], 2), ENT_QUOTES, 'UTF-8') ?> s<?php if (isset($timeInfo['allowed'])): ?> (<?= htmlspecialchars($strings['time_target'], ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars(format_number($timeInfo['allowed'], 2), ENT_QUOTES, 'UTF-8') ?> s)<?php endif; ?><?php if (!empty($timeInfo['faults'])): ?>, <?= htmlspecialchars($strings['time_faults'], ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars(format_number($timeInfo['faults'], 2), ENT_QUOTES, 'UTF-8') ?><?php endif; ?><?php if (!empty($timeInfo['bonus'])): ?>, <?= htmlspecialchars($strings['time_bonus'], ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars(format_number($timeInfo['bonus'], 2), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                    <?php else: ?>
                        –
                    <?php endif; ?>
                </div>
                <?php if ($components): ?>
                    <div><strong><?= htmlspecialchars($strings['components'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php
                        $componentTexts = [];
                        foreach ($components as $componentId => $componentData) {
                            $definition = $componentDefinitions[$componentId] ?? [];
                            $label = $definition['label'] ?? ($componentData['label'] ?? $componentId);
                            $weight = $definition['weight'] ?? null;
                            $score = format_number($componentData['score'] ?? 0, 2);
                            $entry = $label;
                            if ($weight !== null && abs($weight - 1.0) > 0.0001) {
                                $entry .= ' (' . format_number($weight, 2) . '×)';
                            }
                            $entry .= ': ' . $score;
                            $judgeScores = [];
                            if (!empty($componentData['judges']) && is_array($componentData['judges'])) {
                                foreach ($componentData['judges'] as $judgeScore) {
                                    $formattedJudgeScore = format_number($judgeScore, 2);
                                    if ($formattedJudgeScore === '') {
                                        continue;
                                    }
                                    $judgeScores[] = $formattedJudgeScore;
                                }
                            }
                            if ($judgeScores) {
                                $entry .= ' [' . implode(' / ', $judgeScores) . ']';
                            }
                            $componentTexts[] = $entry;
                        }
                        ?>
                        <?= htmlspecialchars(implode(', ', $componentTexts), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($item['tiebreak_path'])): ?>
                    <div><strong><?= htmlspecialchars($strings['tiebreak'], ENT_QUOTES, 'UTF-8') ?></strong> <?= htmlspecialchars(implode(' → ', $item['tiebreak_path']), ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div><strong><?= htmlspecialchars($strings['rule'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if (!empty($item['rule_snapshot']['hash'])): ?>
                        <?= htmlspecialchars(substr($item['rule_snapshot']['hash'], 0, 12), ENT_QUOTES, 'UTF-8') ?><?php if (!empty($item['rule_snapshot']['id'])): ?> (<?= htmlspecialchars($item['rule_snapshot']['id'], ENT_QUOTES, 'UTF-8') ?><?= !empty($item['rule_snapshot']['version']) ? ' v' . htmlspecialchars($item['rule_snapshot']['version'], ENT_QUOTES, 'UTF-8') : '' ?>)<?php endif; ?>
                    <?php else: ?>
                        –
                    <?php endif; ?>
                    <?php if (!empty($item['engine_version'])): ?> · <?= htmlspecialchars($strings['rule_engine'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($item['engine_version'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$items): ?>
        <tr><td colspan="5"><?= htmlspecialchars($strings['empty'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>
