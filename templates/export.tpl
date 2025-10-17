<?php
/** @var array $classes */
?>
<div class="card">
    <div class="card-body">
        <h1 class="h4 mb-3"><?= htmlspecialchars(t('export.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted"><?= htmlspecialchars(t('export.description'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="row g-3">
            <div class="col-md-4">
                <a class="btn btn-outline-secondary w-100" href="export.php?type=entries"><?= htmlspecialchars(t('export.buttons.entries_csv'), ENT_QUOTES, 'UTF-8') ?></a>
            </div>
            <div class="col-md-4">
                <form method="get" class="d-flex gap-2">
                    <input type="hidden" name="type" value="starters">
                    <select name="class_id" class="form-select" aria-label="<?= htmlspecialchars(t('export.form.class_select'), ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>"><?= htmlspecialchars($class['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="submit"><?= htmlspecialchars(t('export.buttons.starters_csv'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            </div>
            <div class="col-md-4">
                <form method="get" class="d-flex gap-2">
                    <input type="hidden" name="type" value="results_json">
                    <select name="class_id" class="form-select" aria-label="<?= htmlspecialchars(t('export.form.class_select'), ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>"><?= htmlspecialchars($class['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="submit"><?= htmlspecialchars(t('export.buttons.results_json'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
