<?php
/** @var array $user */
/** @var array $tiles */
/** @var array $todaySchedule */
/** @var array $currentStart */
/** @var array $nextStarters */
/** @var array $pageQuickActions */
/** @var array $liveActions */
?>
<?php $visibleRoles = []; foreach ($tiles as $roleKey => $items) { if ($user['role'] === 'admin' || $user['role'] === $roleKey) { $visibleRoles[] = $roleKey; } } ?>

<section class="mb-4">
    <?php if (!empty($pageQuickActions)): ?>
        <div class="card border-0 bg-transparent mb-3">
            <div class="card-body py-3 px-0">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small text-uppercase text-muted fw-semibold"><?= htmlspecialchars(t('dashboard.quick_actions.title'), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php foreach ($pageQuickActions as $action): ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars(t($action['label_key']), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 mb-0"><?= htmlspecialchars(t('dashboard.title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if (count($visibleRoles) > 1): ?>
            <div class="btn-group btn-group-sm" role="group" data-tile-filter-group>
                <button type="button" class="btn btn-outline-secondary active" data-tile-filter="all">
                    <?= htmlspecialchars(t('dashboard.filters.all'), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <?php foreach ($visibleRoles as $roleKey): ?>
                    <button type="button" class="btn btn-outline-secondary" data-tile-filter="<?= htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(t('dashboard.filters.role.' . $roleKey), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="row g-3" data-tile-grid>
        <?php foreach ($tiles as $role => $items): ?>
            <?php if ($user['role'] !== $role && $user['role'] !== 'admin') { continue; } ?>
            <?php foreach ($items as $item): ?>
                <div class="col-sm-6 col-xl-3" data-tile-role="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
                    <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                        <div class="card p-3 h-100">
                            <div class="small text-uppercase mb-2"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="display-6 fw-semibold <?= htmlspecialchars($item['value_class'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) $item['value'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="small text-muted"><?= htmlspecialchars($item['note'] ?? t('dashboard.tiles.default_note'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('dashboard.sections.today_schedule.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (!$todaySchedule): ?>
                    <p class=""><?= htmlspecialchars(t('dashboard.sections.today_schedule.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($todaySchedule as $slot): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($slot['label'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="small"><?= htmlspecialchars(format_time($slot['start_time'] ?? null), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(format_time($slot['end_time'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100" data-ticker>
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('dashboard.sections.live_status.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (!empty($liveActions)): ?>
                    <div class="d-flex flex-wrap gap-2 mb-3" data-live-actions>
                        <?php foreach ($liveActions as $action): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <p class="mb-1"><strong><?= htmlspecialchars(t('dashboard.sections.live_status.current'), ENT_QUOTES, 'UTF-8') ?>:</strong>
                    <?php if (!empty($currentStart['start_number_display'])): ?>
                        <span class="badge bg-primary text-light me-1"><?= htmlspecialchars(t('dashboard.sections.live_status.start_number', ['number' => $currentStart['start_number_display']]), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <span data-ticker-current><?= htmlspecialchars($currentStart['rider'] ?? t('dashboard.sections.live_status.none'), ENT_QUOTES, 'UTF-8') ?></span>
                </p>
                <p class="mb-1"><strong><?= htmlspecialchars(t('dashboard.sections.live_status.horse'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars($currentStart['horse'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-3"><strong><?= htmlspecialchars(t('dashboard.sections.live_status.class'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars($currentStart['class_label'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                <h3 class="h6 text-uppercase"><?= htmlspecialchars(t('dashboard.sections.live_status.upcoming'), ENT_QUOTES, 'UTF-8') ?></h3>
                <ul class="list-inline" data-ticker-upcoming>
                    <?php foreach ($nextStarters as $starter): ?>
                        <li class="list-inline-item badge bg-light text-dark me-2 mb-2">
                            <?= htmlspecialchars(t('dashboard.sections.live_status.position', ['position' => (int) $starter['position']]), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($starter['start_number_display'])): ?>
                                <span class="badge bg-primary text-light ms-1"><?= htmlspecialchars($starter['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            · <?= htmlspecialchars($starter['rider'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="small" data-ticker-shift><?= htmlspecialchars(t('dashboard.sections.live_status.no_shifts'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</section>
