<?php $content = function () use ($arenen) { ?>
    <h1 class="h3 mb-3">Prüfungen & Arenen (Demo)</h1>
    <p class="text-muted">Deklarative Bewertungsregeln folgen in Stufe 2. Hier sehen Sie die verfügbaren Arenen.</p>
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Arena</th>
                        <th>Turnier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($arenen as $arena): ?>
                        <tr>
                            <td><?= htmlspecialchars($arena['arena'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($arena['tournament'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php }; include __DIR__ . '/partial_layout.php';
