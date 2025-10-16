<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Ergebnisliste</title>
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

function print_number(mixed $value, int $decimals = 2): string
{
    return number_format((float) $value, $decimals, ',', '.');
}

?>
<h1>Ergebnisliste<?= isset($class['label']) ? ': ' . htmlspecialchars($class['label'], ENT_QUOTES, 'UTF-8') : '' ?></h1>
<?php if (!empty($class['event_title'])): ?>
    <p><strong>Turnier:</strong> <?= htmlspecialchars($class['event_title'], ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<table>
    <thead>
    <tr>
        <th>Platz</th>
        <th>Startnr.</th>
        <th>Reiter</th>
        <th>Pferd</th>
        <th>Gesamt</th>
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
        $unit = $item['unit'] ?? ($aggregate['unit'] ?? null);
        $rank = $item['rank'] ?? ($index + 1);
        ?>
        <tr>
            <td><?= htmlspecialchars((string) $rank, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['start_number_display'] ?? $item['start_number_raw'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['rider'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['horse'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <?= htmlspecialchars(print_number($item['total'] ?? 0), ENT_QUOTES, 'UTF-8') ?><?= $unit ? ' ' . htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') : '' ?>
                <?php if (!empty($item['eliminated'])): ?>
                    <strong style="margin-left: 6px; color: #c00;">ELIM</strong>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td colspan="5">
                <?php $applied = $penalties['applied'] ?? []; ?>
                <div><strong>Penalties:</strong>
                    <?php if ($applied): ?>
                        <?= htmlspecialchars(print_number($penalties['total'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                        <?php
                        $penaltyTexts = [];
                        foreach ($applied as $penalty) {
                            if (!empty($penalty['eliminate'])) {
                                $penaltyTexts[] = ($penalty['label'] ?? 'Elimination');
                            } elseif (isset($penalty['points'])) {
                                $penaltyTexts[] = ($penalty['label'] ?? 'Penalty') . ' (' . print_number($penalty['points'], 2) . ')';
                            }
                        }
                        if ($penaltyTexts): ?>
                            – <?= htmlspecialchars(implode(', ', $penaltyTexts), ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    <?php else: ?>
                        keine
                    <?php endif; ?>
                </div>
                <div><strong>Zeit:</strong>
                    <?php if (isset($timeInfo['seconds'])): ?>
                        <?= htmlspecialchars(print_number($timeInfo['seconds'], 2), ENT_QUOTES, 'UTF-8') ?> s<?php if (isset($timeInfo['allowed'])): ?> (Soll: <?= htmlspecialchars(print_number($timeInfo['allowed'], 2), ENT_QUOTES, 'UTF-8') ?> s)<?php endif; ?><?php if (!empty($timeInfo['faults'])): ?>, Faults: <?= htmlspecialchars(print_number($timeInfo['faults'], 2), ENT_QUOTES, 'UTF-8') ?><?php endif; ?><?php if (!empty($timeInfo['bonus'])): ?>, Bonus: <?= htmlspecialchars(print_number($timeInfo['bonus'], 2), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                    <?php else: ?>
                        –
                    <?php endif; ?>
                </div>
                <?php if ($components): ?>
                    <div><strong>Komponenten:</strong>
                        <?php
                        $componentTexts = [];
                        foreach ($components as $componentId => $componentData) {
                            $componentTexts[] = $componentId . ': ' . print_number($componentData['score'] ?? 0, 2);
                        }
                        ?>
                        <?= htmlspecialchars(implode(', ', $componentTexts), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($item['tiebreak_path'])): ?>
                    <div><strong>Tiebreak:</strong> <?= htmlspecialchars(implode(' → ', $item['tiebreak_path']), ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div><strong>Regel:</strong>
                    <?php if (!empty($item['rule_snapshot']['hash'])): ?>
                        <?= htmlspecialchars(substr($item['rule_snapshot']['hash'], 0, 12), ENT_QUOTES, 'UTF-8') ?><?php if (!empty($item['rule_snapshot']['id'])): ?> (<?= htmlspecialchars($item['rule_snapshot']['id'], ENT_QUOTES, 'UTF-8') ?><?= !empty($item['rule_snapshot']['version']) ? ' v' . htmlspecialchars($item['rule_snapshot']['version'], ENT_QUOTES, 'UTF-8') : '' ?>)<?php endif; ?>
                    <?php else: ?>
                        –
                    <?php endif; ?>
                    <?php if (!empty($item['engine_version'])): ?> · Engine <?= htmlspecialchars($item['engine_version'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$items): ?>
        <tr><td colspan="5">Keine Ergebnisse vorhanden.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>
