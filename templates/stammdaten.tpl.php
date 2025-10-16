<?php $content = function () use ($persons, $horses) { ?>
    <h1 class="h3 mb-3">Stammdaten (Demo)</h1>
    <p class="text-muted">Direkter Zugriff auf aktuelle Reiter- und Pferdedaten. CSV-Import und Dokumentenverwaltung folgen in Stufe 2.</p>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light fw-semibold">Reiter*innen</div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($persons as $person): ?>
                            <li class="mb-2">
                                <strong><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                <span class="text-muted small"><?= htmlspecialchars($person['role'], ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($person['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light fw-semibold">Pferde</div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($horses as $horse): ?>
                            <li class="mb-2">
                                <strong><?= htmlspecialchars($horse['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                <span class="text-muted small"><?= htmlspecialchars($horse['breed'], ENT_QUOTES, 'UTF-8') ?> – Besitzer: <?= htmlspecialchars($horse['owner'], ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php }; include __DIR__ . '/partial_layout.php';
