<?php
/** @var array $classes */
?>
<div class="card">
    <div class="card-body">
        <h1 class="h4 mb-3">Export</h1>
        <p class="text-muted">CSV- und JSON-Export für Meldestelle und Auswertung.</p>
        <div class="row g-3">
            <div class="col-md-4">
                <a class="btn btn-outline-secondary w-100" href="export.php?type=entries">CSV: Nennungen</a>
            </div>
            <div class="col-md-4">
                <form method="get" class="d-flex gap-2">
                    <input type="hidden" name="type" value="starters">
                    <select name="class_id" class="form-select">
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>"><?= htmlspecialchars($class['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="submit">CSV Startliste</button>
                </form>
            </div>
            <div class="col-md-4">
                <form method="get" class="d-flex gap-2">
                    <input type="hidden" name="type" value="results_json">
                    <select name="class_id" class="form-select">
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>"><?= htmlspecialchars($class['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="submit">JSON Ergebnisse</button>
                </form>
            </div>
        </div>
    </div>
</div>
