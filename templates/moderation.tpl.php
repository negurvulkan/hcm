<?php $content = function () use ($highlights) { ?>
    <h1 class="h3 mb-3">Moderation (Demo)</h1>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 text-center p-4">
                <div class="text-muted text-uppercase small">Jetzt im Ring</div>
                <div class="display-6 fw-semibold mb-3"><?= htmlspecialchars($highlights['currentStarter'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-muted">Nächste Reiter*innen:</div>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($highlights['nextRiders'] as $next): ?>
                        <li><?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 p-4 bg-light">
                <div class="text-muted text-uppercase small">Sponsor</div>
                <div class="h4 mb-0"><?= htmlspecialchars($highlights['sponsor'], ENT_QUOTES, 'UTF-8') ?></div>
                <p class="text-muted mt-3">Rotation weiterer Banner folgt in Stufe 3. Inhalte werden zentral gepflegt.</p>
            </div>
        </div>
    </div>
<?php }; include __DIR__ . '/partial_layout.php';
