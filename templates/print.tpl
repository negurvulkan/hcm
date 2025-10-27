<?php
/** @var array $classes */
?>
<div class="card">
    <div class="card-body">
        <h1 class="h4 mb-3"><?= htmlspecialchars(t('print.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted"><?= htmlspecialchars(t('print.description'), ENT_QUOTES, 'UTF-8') ?></p>
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label"><?= htmlspecialchars(t('print.form.class_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <select name="class_id" class="form-select">
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id'] ?>"><?= htmlspecialchars($class['label'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label"><?= htmlspecialchars(t('print.form.document_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <select name="download" class="form-select">
                    <option value="startlist"><?= htmlspecialchars(t('print.documents.startlist'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="judge"><?= htmlspecialchars(t('print.documents.judge'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="results"><?= htmlspecialchars(t('print.documents.results'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="certificate"><?= htmlspecialchars(t('print.documents.certificate'), ENT_QUOTES, 'UTF-8') ?></option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-accent w-100" type="submit"><?= htmlspecialchars(t('print.form.submit'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="print-paper"><?= htmlspecialchars(t('print.form.paper_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <select name="paper" id="print-paper" class="form-select">
                    <option value="a4"<?= ($paper ?? 'a4') === 'a4' ? ' selected' : '' ?>>A4</option>
                    <option value="a5"<?= ($paper ?? 'a4') === 'a5' ? ' selected' : '' ?>>A5</option>
                    <option value="letter"<?= ($paper ?? 'a4') === 'letter' ? ' selected' : '' ?>>Letter</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="print-orientation"><?= htmlspecialchars(t('print.form.orientation_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <select name="orientation" id="print-orientation" class="form-select">
                    <option value="portrait"<?= ($orientation ?? 'portrait') === 'portrait' ? ' selected' : '' ?>><?= htmlspecialchars(t('print.form.orientation_portrait'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="landscape"<?= ($orientation ?? 'portrait') === 'landscape' ? ' selected' : '' ?>><?= htmlspecialchars(t('print.form.orientation_landscape'), ENT_QUOTES, 'UTF-8') ?></option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="print-bleed"><?= htmlspecialchars(t('print.form.bleed_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="number" class="form-control" id="print-bleed" name="bleed" min="0" step="0.5" value="<?= htmlspecialchars(number_format((float) ($bleed ?? 0), 1, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-text small text-muted"><?= htmlspecialchars(t('print.form.bleed_hint'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </form>
    </div>
</div>
