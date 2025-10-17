<?php
/** @var array $persons */
/** @var array $horses */
/** @var array $classes */
/** @var array $entries */
/** @var array|null $importHeader */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars($editEntry ? t('entries.form.edit_title') : t('entries.form.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editEntry ? 'update' : 'create' ?>">
                    <input type="hidden" name="entry_id" value="<?= $editEntry ? (int) $editEntry['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('entries.form.person'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="person_id" class="form-select" required>
                            <option value=""><?= htmlspecialchars(t('entries.form.select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($persons as $person): ?>
                                <option value="<?= (int) $person['id'] ?>" <?= $editEntry && (int) $editEntry['party_id'] === (int) $person['id'] ? 'selected' : '' ?>><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('entries.form.horse'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="horse_id" class="form-select" required>
                            <option value=""><?= htmlspecialchars(t('entries.form.select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($horses as $horse): ?>
                                <option value="<?= (int) $horse['id'] ?>" <?= $editEntry && (int) $editEntry['horse_id'] === (int) $horse['id'] ? 'selected' : '' ?>><?= htmlspecialchars($horse['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('entries.form.class'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="class_id" class="form-select" required>
                            <option value=""><?= htmlspecialchars(t('entries.form.select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= (int) $class['id'] ?>" <?= $editEntry && (int) $editEntry['class_id'] === (int) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['title'] . ' · ' . $class['label'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('entries.form.status'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="status" class="form-select">
                            <option value="open" <?= !$editEntry || $editEntry['status'] === 'open' ? 'selected' : '' ?>><?= htmlspecialchars(t('entries.status.open'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="paid" <?= $editEntry && $editEntry['status'] === 'paid' ? 'selected' : '' ?>><?= htmlspecialchars(t('entries.status.paid'), ENT_QUOTES, 'UTF-8') ?></option>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('entries.form.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editEntry): ?>
                            <a href="entries.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('entries.form.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
                <hr>
                <h3 class="h6"><?= htmlspecialchars(t('entries.form.import_heading'), ENT_QUOTES, 'UTF-8') ?></h3>
                <form method="post" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="preview_import">
                    <input type="file" name="csv" class="form-control" accept=".csv" required>
                    <button type="submit" class="btn btn-outline-secondary"><?= htmlspecialchars(t('entries.form.import_button'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0"><?= htmlspecialchars(t('entries.table.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <?php if ($importHeader): ?>
                        <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#importModal"><?= htmlspecialchars(t('entries.table.mapping_button'), ENT_QUOTES, 'UTF-8') ?></button>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('entries.table.columns.rider'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('entries.table.columns.horse'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('entries.table.columns.class'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('entries.table.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th class="text-end"><?= htmlspecialchars(t('entries.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
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
                                        <?= htmlspecialchars(t('entries.status.' . $entry['status']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="entries.php?edit=<?= (int) $entry['id'] ?>"><?= htmlspecialchars(t('entries.table.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('entries.table.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="entry_id" value="<?= (int) $entry['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(t('entries.table.delete'), ENT_QUOTES, 'UTF-8') ?></button>
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
                <h2 class="modal-title h5"><?= htmlspecialchars(t('entries.import.modal_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="apply_import">
                <input type="hidden" name="import_token" value="<?= htmlspecialchars($importToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <?php $columns = array_values($importHeader); ?>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('entries.import.person_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="mapping[person]" class="form-select" required>
                            <option value=""><?= htmlspecialchars(t('entries.form.select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($columns as $index => $column): ?>
                                <option value="<?= $index ?>"><?= htmlspecialchars($column, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('entries.import.horse_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="mapping[horse]" class="form-select" required>
                            <option value=""><?= htmlspecialchars(t('entries.form.select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($columns as $index => $column): ?>
                                <option value="<?= $index ?>"><?= htmlspecialchars($column, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('entries.import.class_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="mapping[class]" class="form-select" required>
                            <option value=""><?= htmlspecialchars(t('entries.form.select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($columns as $index => $column): ?>
                                <option value="<?= $index ?>"><?= htmlspecialchars($column, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('entries.import.status_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="mapping[status]" class="form-select">
                            <option value=""><?= htmlspecialchars(t('entries.import.status_optional'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($columns as $index => $column): ?>
                                <option value="<?= $index ?>"><?= htmlspecialchars($column, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-muted small"><?= htmlspecialchars(t('entries.import.note'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('entries.import.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="submit" class="btn btn-accent"><?= htmlspecialchars(t('entries.import.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
