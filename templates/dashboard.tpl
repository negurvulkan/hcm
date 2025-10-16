<?php
/** @var array $user */
/** @var array $tiles */
/** @var array $todaySchedule */
/** @var array $currentStart */
/** @var array $nextStarters */
?>
<section class="mb-4">
    <div class="row g-3">
        <?php foreach ($tiles as $role => $items): ?>
            <?php if ($user['role'] !== $role && $user['role'] !== 'admin') { continue; } ?>
            <?php foreach ($items as $item): ?>
                <div class="col-sm-6 col-xl-3">
                    <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                        <div class="card p-3 h-100">
                            <div class="small text-uppercase mb-2"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="display-6 fw-semibold"><?= htmlspecialchars((string) $item['value'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small">Zur Übersicht</div>
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
                <h2 class="h5 mb-3">Heutiger Zeitplan</h2>
                <?php if (!$todaySchedule): ?>
                    <p class="">Heute sind keine Starts geplant.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($todaySchedule as $slot): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($slot['label'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="small"><?= htmlspecialchars(date('H:i', strtotime($slot['start_time'] ?? '')), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(date('H:i', strtotime($slot['end_time'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
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
                <h2 class="h5 mb-3">Live-Status</h2>
                <p class="mb-1"><strong>Aktuell:</strong>
                    <?php if (!empty($currentStart['start_number_display'])): ?>
                        <span class="badge bg-primary text-light me-1">Startnr. <?= htmlspecialchars($currentStart['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <span data-ticker-current><?= htmlspecialchars($currentStart['rider'] ?? 'Noch kein Start', ENT_QUOTES, 'UTF-8') ?></span>
                </p>
                <p class="mb-1"><strong>Pferd:</strong> <?= htmlspecialchars($currentStart['horse'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-3"><strong>Prüfung:</strong> <?= htmlspecialchars($currentStart['class_label'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                <h3 class="h6 text-uppercase">Nächste Starter</h3>
                <ul class="list-inline" data-ticker-upcoming>
                    <?php foreach ($nextStarters as $starter): ?>
                        <li class="list-inline-item badge bg-light text-dark me-2 mb-2">
                            Nr. <?= (int) $starter['position'] ?>
                            <?php if (!empty($starter['start_number_display'])): ?>
                                <span class="badge bg-primary text-light ms-1"><?= htmlspecialchars($starter['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            · <?= htmlspecialchars($starter['rider'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="small" data-ticker-shift>Keine Verschiebungen.</div>
            </div>
        </div>
    </div>
</section>
