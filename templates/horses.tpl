<?php
/** @var array $horses */
/** @var array $owners */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars($editHorse ? t('horses.form.edit_title') : t('horses.form.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editHorse ? 'update' : 'create' ?>">
                    <input type="hidden" name="horse_id" value="<?= $editHorse ? (int) $editHorse['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('horses.form.name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editHorse['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('horses.form.owner'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="owner_id" class="form-select">
                            <option value="">–</option>
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?= (int) $owner['id'] ?>" <?= $editHorse && (int) ($editHorse['owner_id'] ?? 0) === (int) $owner['id'] ? 'selected' : '' ?>><?= htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="documents_ok" name="documents_ok" <?= $editHorse && (int) ($editHorse['documents_ok'] ?? 0) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="documents_ok"><?= htmlspecialchars(t('horses.form.documents_ok'), ENT_QUOTES, 'UTF-8') ?></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('horses.form.notes'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($editHorse['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('horses.form.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editHorse): ?>
                            <a href="horses.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('horses.form.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('horses.list.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form class="row g-2 align-items-end mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars(t('horses.filters.name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($filterName ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?= htmlspecialchars(t('horses.filters.owner'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="owner" class="form-select">
                            <option value=""><?= htmlspecialchars(t('horses.filters.all_owners'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?= (int) $owner['id'] ?>" <?= ((int)($filterOwner ?? 0)) === (int) $owner['id'] ? 'selected' : '' ?>><?= htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" type="submit"><?= htmlspecialchars(t('horses.filters.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('horses.table.name'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('horses.table.owner'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('horses.table.status'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('horses.table.notes'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th class="text-end"><?= htmlspecialchars(t('horses.table.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($horses as $horse): ?>
                            <tr>
                                <td><?= htmlspecialchars($horse['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($horse['owner_name'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ((int) ($horse['documents_ok'] ?? 0) === 1): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars(t('horses.table.status_documents_ok'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><?= htmlspecialchars(t('horses.table.status_pending'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($horse['notes'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="horses.php?edit=<?= (int) $horse['id'] ?>"><?= htmlspecialchars(t('horses.table.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('horses.table.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="horse_id" value="<?= (int) $horse['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('horses.table.delete'), ENT_QUOTES, 'UTF-8') ?></button>
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
