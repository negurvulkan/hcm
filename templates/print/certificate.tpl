<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars(t('print.pdf.certificate.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: "Times New Roman", serif; text-align: center; padding: 5cm; }
        h1 { font-size: 42px; margin-bottom: 40px; }
        p { font-size: 18px; }
    </style>
</head>
<body>
<?php
/** @var array|null $winner */

$strings = [
    'heading' => t('print.pdf.certificate.heading'),
    'awarded' => t('print.pdf.certificate.body.awarded_to'),
    'with_horse' => t('print.pdf.certificate.body.with_horse'),
    'for_score' => t('print.pdf.certificate.body.for_score', [
        'points' => format_number($winner['total'] ?? 0, 2),
    ]),
    'signature' => t('print.pdf.certificate.body.signature'),
];
?>
<h1><?= htmlspecialchars($strings['heading'], ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($strings['awarded'], ENT_QUOTES, 'UTF-8') ?></p>
<p style="font-size:28px; font-weight:bold;"><?= htmlspecialchars($winner['rider'] ?? '__________', ENT_QUOTES, 'UTF-8') ?></p>
<p><?= htmlspecialchars($strings['with_horse'], ENT_QUOTES, 'UTF-8') ?></p>
<p style="font-size:24px;"><?= htmlspecialchars($winner['horse'] ?? '__________', ENT_QUOTES, 'UTF-8') ?></p>
<p><?= htmlspecialchars($strings['for_score'], ENT_QUOTES, 'UTF-8') ?></p>
<p style="margin-top:60px;">_________________________<br><?= htmlspecialchars($strings['signature'], ENT_QUOTES, 'UTF-8') ?></p>
</body>
</html>
