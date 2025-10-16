<?php
/** @var array $classes */
/** @var array $selectedClass */
/** @var array|null $start */
/** @var array $starts */
/** @var array $rule */
/** @var array $scores */
/** @var array|null $result */
?>
<div class="alert alert-info d-flex justify-content-between align-items-center">
    <div><strong><?= htmlspecialchars($selectedClass['label'] ?? 'Prüfung', ENT_QUOTES, 'UTF-8') ?></strong> · <?= htmlspecialchars($start['rider'] ?? 'Kein Start', ENT_QUOTES, 'UTF-8') ?></div>
    <div class="text-muted small">Offline? <span class="badge bg-light text-dark">Form puffert lokal</span></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <form method="get" class="d-flex gap-2">
            <select name="class_id" class="form-select">
                <?php foreach ($classes as $class): ?>
                    <option value="<?= (int) $class['id'] ?>" <?= (int) $selectedClass['id'] === (int) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['title'] . ' · ' . $class['label'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-secondary" type="submit">Wechseln</button>
        </form>
    </div>
    <div class="col-md-8 text-end">
        <?php foreach ($starts as $candidate): ?>
            <a href="judge.php?class_id=<?= (int) $selectedClass['id'] ?>&start_id=<?= (int) $candidate['id'] ?>" class="btn btn-sm <?= (int) $candidate['id'] === (int) $start['id'] ? 'btn-accent' : 'btn-outline-secondary' ?>">Nr. <?= (int) $candidate['position'] ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$start): ?>
    <p class="text-muted">Keine Startliste verfügbar.</p>
<?php else: ?>
<form method="post" class="card" data-autosave>
    <div class="card-body">
        <?= csrf_field() ?>
        <input type="hidden" name="class_id" value="<?= (int) $selectedClass['id'] ?>">
        <input type="hidden" name="start_id" value="<?= (int) $start['id'] ?>">
        <h2 class="h5 mb-3">Wertung für <?= htmlspecialchars($start['rider'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($start['horse'], ENT_QUOTES, 'UTF-8') ?></h2>
        <?php switch ($rule['type'] ?? 'dressage'): case 'jumping': ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Rittzeit (Sekunden)</label>
                    <input type="number" step="0.01" name="score[time]" class="form-control" value="<?= htmlspecialchars($scores['time'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fehlerpunkte</label>
                    <input type="number" name="score[faults]" class="form-control" value="<?= htmlspecialchars($scores['faults'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Zeitfehler</label>
                    <input type="number" name="time_penalties" class="form-control" value="<?= htmlspecialchars($result['penalties'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
        <?php break; case 'western': ?>
            <div class="row g-3">
                <?php foreach ($rule['maneuvers'] ?? [] as $index => $maneuver): ?>
                    <div class="col-md-4">
                        <label class="form-label"><?= htmlspecialchars($maneuver['label'] ?? 'Maneuver ' . ($index + 1), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="number" step="0.5" min="<?= (float) ($maneuver['range'][0] ?? -1.5) ?>" max="<?= (float) ($maneuver['range'][1] ?? 1.5) ?>" name="score[maneuvers][<?= $index ?>]" class="form-control" value="<?= htmlspecialchars($scores['maneuvers'][$index] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-3">
                <label class="form-label">Penalties</label>
                <div class="d-flex gap-3 flex-wrap">
                    <?php foreach ($rule['penalties'] ?? [] as $penalty): ?>
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="score[penalties][]" value="<?= (float) $penalty ?>" <?= in_array((float) $penalty, $scores['penalties'] ?? [], true) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= htmlspecialchars($penalty . ' Punkte', ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php break; default: ?>
            <div class="row g-3">
                <?php foreach ($rule['movements'] ?? [] as $index => $movement): ?>
                    <div class="col-md-4">
                        <label class="form-label"><?= htmlspecialchars($movement['label'] ?? 'Aufgabe ' . ($index + 1), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="score[movements][<?= $index ?>]" class="form-select">
                            <?php for ($i = 0; $i <= 10; $i += 0.5): ?>
                                <option value="<?= $i ?>" <?= ((float) ($scores['movements'][$index] ?? 0) === (float) $i) ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endswitch; ?>
        <div class="form-check form-switch mt-4">
            <input class="form-check-input" type="checkbox" id="sign" name="sign" <?= ($result['status'] ?? '') === 'signed' ? 'checked' : '' ?>>
            <label class="form-check-label" for="sign">Elektronisch signieren</label>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="text-muted small">Zwischenspeichern möglich – Abschicken speichert endgültig.</div>
        <div class="d-flex gap-2">
            <?php if ($result): ?>
                <button class="btn btn-outline-danger" type="submit" name="action" value="delete_result" formnovalidate onclick="return confirm('Wertung wirklich löschen?')">Löschen</button>
            <?php endif; ?>
            <button class="btn btn-accent" type="submit" name="action" value="save">Wertung speichern</button>
        </div>
    </div>
</form>
<?php endif; ?>

<script>
    document.querySelectorAll('[data-autosave]').forEach(function (form) {
        AppHelpers.markDirty($(form));
    });
</script>
