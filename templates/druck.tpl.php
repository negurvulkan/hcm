<?php $content = function () { ?>
    <h1 class="h3 mb-3">PDFs & Druck (Demo)</h1>
    <p class="text-muted">Die Dompdf-Integration folgt in Stufe 4. Für das MVP steht ein einfacher PDF-Platzhalter zur Verfügung.</p>
    <a href="index.php?page=pdf-demo" class="btn btn-primary">Demo-PDF herunterladen</a>
    <div class="alert alert-secondary mt-3">
        <strong>Leerer Durchlauf:</strong> Stammdaten prüfen → Prüfungen ansehen → Demo-PDF generieren. Damit ist der Grundfluss testbar.
    </div>
<?php }; include __DIR__ . '/partial_layout.php';
