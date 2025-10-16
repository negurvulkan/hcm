<?php
/** @var array $persons */
/** @var array $horses */
/** @var array $classes */
/** @var array $entries */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= $editEntry ? 'Nennung bearbeiten' : 'Manuelle Nennung' ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editEntry ? 'update' : 'create' ?>">
                    <input type="hidden" name="entry_id" value="<?= $editEntry ? (int) $editEntry['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Reiter</label>
                        <select name="person_id" class="form-select" required>
                            <option value="">Wählen…</option>
                            <?php foreach ($persons as $person): ?>
                                <option value="<?= (int) $person['id'] ?>" <?= $editEntry && (int) $editEntry['person_id'] === (int) $person['id'] ? 'selected' : '' ?>><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pferd</label>
                        <select name="horse_id" class="form-select" required>
                            <option value="">Wählen…</option>
                            <?php foreach ($horses as $horse): ?>
                                <option value="<?= (int) $horse['id'] ?>" <?= $editEntry && (int) $editEntry['horse_id'] === (int) $horse['id'] ? 'selected' : '' ?>><?= htmlspecialchars($horse['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prüfung</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">Wählen…</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= (int) $class['id'] ?>" <?= $editEntry && (int) $editEntry['class_id'] === (int) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['title'] . ' · ' . $class['label'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="open" <?= !$editEntry || $editEntry['status'] === 'open' ? 'selected' : '' ?>>offen</option>
                            <option value="paid" <?= $editEntry && $editEntry['status'] === 'paid' ? 'selected' : '' ?>>bezahlt</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit">Speichern</button>
                        <?php if ($editEntry): ?>
                            <a href="entries.php" class="btn btn-outline-secondary">Abbrechen</a>
                        <?php endif; ?>
                    </div>
                </form>
                <hr>
                <h3 class="h6">CSV-Import</h3>
                <form method="post" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="preview_import">
                    <input type="file" name="csv" class="form-control" accept=".csv" required>
                    <button type="submit" class="btn btn-outline-secondary">CSV laden</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Nennungen</h2>
                    <?php if ($importHeader): ?>
                        <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#importModal">Mapping abschließen</button>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Reiter</th>
                            <th>Pferd</th>
                            <th>Prüfung</th>
                            <th>Status</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?= htmlspecialchars($entry['rider'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($entry['horse'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($entry['class_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge <?= $entry['status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                        <?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="entries.php?edit=<?= (int) $entry['id'] ?>">Bearbeiten</a>
                                        <form method="post" onsubmit="return confirm('Nennung und ggf. Startlisten-Einträge löschen?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="entry_id" value="<?= (int) $entry['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Löschen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($importHeader): ?>
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5">CSV-Mapping</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="apply_import">
                <input type="hidden" name="import_token" value="<?= htmlspecialchars($importToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <?php $columns = array_values($importHeader); ?>
                    <div class="mb-3">
                        <label class="form-label">Reiter-Spalte</label>
                        <select name="mapping[person]" class="form-select" required>
                            <option value="">Wählen…</option>
                            <?php foreach ($columns as $index => $column): ?>
                                <option value="<?= $index ?>"><?= htmlspecialchars($column, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pferd-Spalte</label>
                        <select name="mapping[horse]" class="form-select" required>
                            <option value="">Wählen…</option>
                            <?php foreach ($columns as $index => $column): ?>
                                <option value="<?= $index ?>"><?= htmlspecialchars($column, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prüfung-Spalte</label>
                        <select name="mapping[class]" class="form-select" required>
                            <option value="">Wählen…</option>
                            <?php foreach ($columns as $index => $column): ?>
                                <option value="<?= $index ?>"><?= htmlspecialchars($column, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status-Spalte</label>
                        <select name="mapping[status]" class="form-select">
                            <option value="">(optional)</option>
                            <?php foreach ($columns as $index => $column): ?>
                                <option value="<?= $index ?>"><?= htmlspecialchars($column, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-muted small">Hinweis: Personen, Pferde und Prüfungen müssen bereits existieren.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-accent">Import starten</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
