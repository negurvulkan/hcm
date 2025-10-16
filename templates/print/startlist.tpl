<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Startliste</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 6px; }
    </style>
</head>
<body>
<h1>Startliste</h1>
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Startnr.</th>
        <th>Reiter</th>
        <th>Pferd</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr>
            <td><?= (int) $item['position'] ?></td>
            <td><?= htmlspecialchars($item['start_number_display'] ?? $item['start_number_raw'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['rider'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['horse'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
