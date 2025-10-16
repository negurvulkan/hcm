<?php $content = function () use ($slots) { ?>
    <h1 class="h3 mb-3">Zeitplan (Demo)</h1>
    <p class="text-muted">Der Generator berücksichtigt künftig Erholzeiten und Pausen. Hier sehen Sie das automatisch erzeugte Grundgerüst.</p>
    <div class="row g-3">
        <?php foreach ($slots as $slot): ?>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-2"><?= htmlspecialchars($slot['arena'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="mb-1"><strong><?= htmlspecialchars($slot['start'], ENT_QUOTES, 'UTF-8') ?></strong> – <?= htmlspecialchars($slot['end'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-muted small mb-0"><?= htmlspecialchars($slot['note'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php }; include __DIR__ . '/partial_layout.php';
