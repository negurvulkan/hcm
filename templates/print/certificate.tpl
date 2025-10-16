<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Urkunde</title>
    <style>
        body { font-family: "Times New Roman", serif; text-align: center; padding: 5cm; }
        h1 { font-size: 42px; margin-bottom: 40px; }
        p { font-size: 18px; }
    </style>
</head>
<body>
<h1>Urkunde</h1>
<p>Diese Urkunde wird verliehen an</p>
<p style="font-size:28px; font-weight:bold;"><?= htmlspecialchars($winner['rider'] ?? '__________', ENT_QUOTES, 'UTF-8') ?></p>
<p>mit dem Pferd</p>
<p style="font-size:24px;"><?= htmlspecialchars($winner['horse'] ?? '__________', ENT_QUOTES, 'UTF-8') ?></p>
<p>für den Sieg mit <?= htmlspecialchars(number_format((float) ($winner['total'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?> Punkten.</p>
<p style="margin-top:60px;">_________________________<br>Turnierleitung</p>
</body>
</html>
