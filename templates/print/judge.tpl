<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars(t('print.pdf.judge.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
    </style>
</head>
<body>
<h1><?= htmlspecialchars(t('print.pdf.judge.heading', ['class' => $class['label'] ?? '']), ENT_QUOTES, 'UTF-8') ?></h1>
<table>
    <thead>
    <tr>
        <th><?= htmlspecialchars(t('print.pdf.judge.table.movement'), ENT_QUOTES, 'UTF-8') ?></th>
        <th><?= htmlspecialchars(t('print.pdf.judge.table.score'), ENT_QUOTES, 'UTF-8') ?></th>
        <th><?= htmlspecialchars(t('print.pdf.judge.table.comment'), ENT_QUOTES, 'UTF-8') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php if (($rule['type'] ?? 'dressage') === 'dressage'): ?>
        <?php foreach ($rule['movements'] ?? [] as $movement): ?>
            <tr>
                <td><?= htmlspecialchars($movement['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td></td>
                <td></td>
            </tr>
        <?php endforeach; ?>
    <?php elseif (($rule['type'] ?? '') === 'jumping'): ?>
        <tr><td><?= htmlspecialchars(t('print.pdf.judge.jumping.time'), ENT_QUOTES, 'UTF-8') ?></td><td></td><td></td></tr>
        <tr><td><?= htmlspecialchars(t('print.pdf.judge.jumping.penalties'), ENT_QUOTES, 'UTF-8') ?></td><td></td><td></td></tr>
    <?php else: ?>
        <?php foreach ($rule['maneuvers'] ?? [] as $maneuver): ?>
            <tr>
                <td><?= htmlspecialchars($maneuver['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td></td>
                <td></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
<p><?= htmlspecialchars(t('print.pdf.judge.footer'), ENT_QUOTES, 'UTF-8') ?></p>
</body>
</html>
