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
<h1>Ergebnisliste</h1>
<table>
    <thead>
    <tr>
        <th>Platz</th>
        <th>Reiter</th>
        <th>Pferd</th>
        <th>Punkte</th>
    </tr>
    </thead>
    <tbody>
    <?php $rank = 1; foreach ($items as $item): ?>
        <tr>
            <td><?= $rank++ ?></td>
            <td><?= htmlspecialchars($item['rider'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['horse'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(number_format((float) $item['total'], 2), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
