<?php $content = function () use ($shifts) { ?>
    <h1 class="h3 mb-3">Helferkoordination (Demo)</h1>
    <p class="text-muted">Schichtplanung mit Konfliktprüfung wird in Stufe 5 erweitert. Aktuell sehen Sie den vorbereiteten Basisplan.</p>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Rolle</th>
                <th>Person</th>
                <th>Arena</th>
                <th>Von</th>
                <th>Bis</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($shifts as $shift): ?>
                <tr>
                    <td><?= htmlspecialchars($shift['role'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($shift['person_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($shift['arena'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($shift['start_time'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($shift['end_time'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php }; include __DIR__ . '/partial_layout.php';
