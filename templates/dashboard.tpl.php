<?php $content = function () use ($stats, $installationHint) { ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Übersicht</h1>
            <p class="text-muted mb-0">Willkommen zurück! Alle Kernbereiche sind vorbereitet.</p>
        </div>
        <?php if ($installationHint): ?>
            <span class="badge bg-success">Neu installiert</span>
        <?php endif; ?>
    </div>
    <div class="row g-3">
        <?php foreach ($stats['counts'] as $label => $value): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small mb-1"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="display-6 fw-semibold"><?= (int) $value ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h5">Aktuelles Turnier</h2>
            <?php if ($stats['tournament']): ?>
                <dl class="row">
                    <dt class="col-sm-3">Name</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($stats['tournament']['name'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-3">Ort</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($stats['tournament']['location'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-3">Zeitraum</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($stats['tournament']['start_date'], ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($stats['tournament']['end_date'], ENT_QUOTES, 'UTF-8') ?></dd>
                </dl>
            <?php else: ?>
                <p class="text-muted mb-0">Noch kein Turnier angelegt.</p>
            <?php endif; ?>
        </div>
    </div>
<?php }; include __DIR__ . '/partial_layout.php';
