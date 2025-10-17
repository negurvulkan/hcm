<?php
/** @var array|null $current */
/** @var array $next */
/** @var array $top */
/** @var string $sponsor */
/** @var string $title */
/** @var string $locale */
$pageTitle = $title ?? t('display.title');
$pageLocale = $locale ?? current_locale();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($pageLocale, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
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
                    <h2 class="h4"><?= htmlspecialchars(t('display.headings.current'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="display-5" data-ticker-current>
                        <?php if (!empty($current['start_number_display'])): ?>
                            <span class="badge bg-primary text-light me-2"><?= htmlspecialchars(t('display.labels.start_number_prefix'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($current['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($current['rider'] ?? t('display.labels.no_current'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p class="h4"><?= htmlspecialchars(t('display.labels.horse_prefix'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($current['horse'] ?? t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-muted"><?= htmlspecialchars(t('display.labels.class_prefix'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($current['label'] ?? t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h4"><?= htmlspecialchars(t('display.headings.upcoming'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <ul class="list-unstyled" data-ticker-upcoming>
                        <?php foreach ($next as $entry): ?>
                            <li class="py-1">
                                <?= htmlspecialchars(t('display.labels.position_prefix'), ENT_QUOTES, 'UTF-8') ?> <?= (int) $entry['position'] ?>
                                <?php if (!empty($entry['start_number_display'])): ?>
                                    <span class="badge bg-primary text-light ms-1"><?= htmlspecialchars($entry['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                – <?= htmlspecialchars($entry['rider'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($entry['horse'], ENT_QUOTES, 'UTF-8') ?>)
                            </li>
                        <?php endforeach; ?>
                        <?php if (!$next): ?>
                            <li class="text-muted"><?= htmlspecialchars(t('display.empty.next'), ENT_QUOTES, 'UTF-8') ?></li>
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
                    <h2 class="h4"><?= htmlspecialchars(t('display.headings.top'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <ol class="mb-0">
                        <?php foreach ($top as $result): ?>
                            <li class="py-1"><?= htmlspecialchars($result['rider'], ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(number_format((float) $result['total'], 2), ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                        <?php if (!$top): ?>
                            <li class="text-muted"><?= htmlspecialchars(t('display.empty.results'), ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endif; ?>
                    </ol>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ticker h-100">
                <div class="text-uppercase text-muted small"><?= htmlspecialchars(t('display.headings.ticker'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="fs-3" data-ticker-shift><?= htmlspecialchars(t('display.empty.shift'), ENT_QUOTES, 'UTF-8') ?></div>
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
