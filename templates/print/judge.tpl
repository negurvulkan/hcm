<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Richterbogen</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
    </style>
</head>
<body>
<h1>Richterbogen – <?= htmlspecialchars($class['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
<table>
    <thead>
    <tr>
        <th>Aufgabe</th>
        <th>Note</th>
        <th>Bemerkung</th>
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
        <tr><td>Zeit</td><td></td><td></td></tr>
        <tr><td>Fehlerpunkte</td><td></td><td></td></tr>
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
<p>Richter: ____________________________</p>
</body>
</html>
