<?php
/** @var array|null $current */
/** @var array $next */
/** @var array $top */
/** @var string $sponsor */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Live-Anzeige</title>
    <link rel="stylesheet" href="public/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/styles.css">
    <style>
        body { background: #0b0d14; color: #f5f7ff; font-size: 1.2rem; }
        .ticker { background: rgba(255, 255, 255, 0.08); padding: 1rem; border-radius: 1rem; }
        .card { background: rgba(255, 255, 255, 0.06); border: none; }
        .card h2 { color: #a0c1ff; }
    </style>
</head>
<body>
<div class="container py-4" data-ticker>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h4">Aktueller Starter</h2>
                    <p class="display-5" data-ticker-current>
                        <?php if (!empty($current['start_number_display'])): ?>
                            <span class="badge bg-primary text-light me-2">Startnr. <?= htmlspecialchars($current['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($current['rider'] ?? 'Noch kein Start', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p class="h4">Pferd: <?= htmlspecialchars($current['horse'] ?? '–', ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-muted">Prüfung: <?= htmlspecialchars($current['label'] ?? '–', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h4">Nächste Starter</h2>
                    <ul class="list-unstyled" data-ticker-upcoming>
                        <?php foreach ($next as $entry): ?>
                            <li class="py-1">
                                Nr. <?= (int) $entry['position'] ?>
                                <?php if (!empty($entry['start_number_display'])): ?>
                                    <span class="badge bg-primary text-light ms-1"><?= htmlspecialchars($entry['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                – <?= htmlspecialchars($entry['rider'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($entry['horse'], ENT_QUOTES, 'UTF-8') ?>)
                            </li>
                        <?php endforeach; ?>
                        <?php if (!$next): ?>
                            <li class="text-muted">Keine weiteren Starter.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h4">Zwischenstand Top 3</h2>
                    <ol class="mb-0">
                        <?php foreach ($top as $result): ?>
                            <li class="py-1"><?= htmlspecialchars($result['rider'], ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(number_format((float) $result['total'], 2), ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                        <?php if (!$top): ?>
                            <li class="text-muted">Noch keine Ergebnisse.</li>
                        <?php endif; ?>
                    </ol>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ticker h-100">
                <div class="text-uppercase text-muted small">Ticker</div>
                <div class="fs-3" data-ticker-shift>Keine aktuellen Verschiebungen.</div>
                <div class="mt-3" data-ticker-result><?= htmlspecialchars($sponsor, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>
<script src="public/assets/vendor/jquery.min.js"></script>
<script src="public/assets/js/helpers.js"></script>
<script src="public/assets/js/ticker.js"></script>
</body>
</html>
