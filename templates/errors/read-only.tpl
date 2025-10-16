<?php
/** @var string|null $message */
?>
<div class="py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 640px;">
        <div class="card-body p-4">
            <h1 class="h4 mb-3">Schreibschutz aktiv</h1>
            <p class="mb-4">Diese Instanz befindet sich in einem schreibgeschützten Modus. Änderungen sind aktuell nicht zulässig.</p>
            <?php if (!empty($message)): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <p class="small text-muted mb-0">Passe den Betriebsmodus bei Bedarf unter „Instanz &amp; Modus“ an.</p>
        </div>
        <div class="card-footer bg-light d-flex justify-content-between align-items-center">
            <a href="dashboard.php" class="btn btn-outline-primary btn-sm">Zurück zum Dashboard</a>
            <a href="instance.php" class="btn btn-primary btn-sm">Instanz &amp; Modus öffnen</a>
        </div>
    </div>
</div>
