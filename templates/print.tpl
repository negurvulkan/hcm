<?php
/** @var array $classes */
?>
<div class="card">
    <div class="card-body">
        <h1 class="h4 mb-3">Druck &amp; PDFs</h1>
        <p class="text-muted">Wähle eine Prüfung und lade das gewünschte Dokument herunter. Dompdf muss lokal vorhanden sein.</p>
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Prüfung</label>
                <select name="class_id" class="form-select">
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id'] ?>"><?= htmlspecialchars($class['label'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Dokument</label>
                <select name="download" class="form-select">
                    <option value="startlist">Startliste (A4)</option>
                    <option value="judge">Richterbogen</option>
                    <option value="results">Ergebnisliste</option>
                    <option value="certificate">Urkunde</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-accent w-100" type="submit">Download</button>
            </div>
        </form>
    </div>
</div>
