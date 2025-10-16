<?php
/** @var array $entries */
?>
<div class="card">
    <div class="card-body">
        <h1 class="h4 mb-3">Audit-Trail</h1>
        <ul class="list-group list-group-flush">
            <?php foreach ($entries as $entry): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong><?= htmlspecialchars($entry['entity'], ENT_QUOTES, 'UTF-8') ?> #<?= (int) $entry['entity_id'] ?></strong>
                            <div class="text-muted small">Aktion: <?= htmlspecialchars($entry['action'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="text-muted small"><?= htmlspecialchars($entry['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </li>
            <?php endforeach; ?>
            <?php if (!$entries): ?>
                <li class="list-group-item text-muted">Noch keine Einträge.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
