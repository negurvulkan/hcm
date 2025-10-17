<?php
/** @var array $clubs */
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars($editClub ? t('clubs.form.edit_title') : t('clubs.form.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editClub ? 'update' : 'create' ?>">
                    <input type="hidden" name="club_id" value="<?= $editClub ? (int) $editClub['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('clubs.form.name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editClub['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('clubs.form.short_name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="short_name" class="form-control" maxlength="10" value="<?= htmlspecialchars($editClub['short_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('clubs.form.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editClub): ?>
                            <a class="btn btn-outline-secondary" href="clubs.php"><?= htmlspecialchars(t('clubs.form.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('clubs.list.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form class="row g-2 align-items-end mb-3">
                    <div class="col-md-10">
                        <label class="form-label"><?= htmlspecialchars(t('clubs.filters.search'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($filter ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" type="submit"><?= htmlspecialchars(t('clubs.filters.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('clubs.table.name'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('clubs.table.short_name'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th class="text-end"><?= htmlspecialchars(t('clubs.table.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clubs as $club): ?>
                            <tr>
                                <td><?= htmlspecialchars($club['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($club['short_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="clubs.php?edit=<?= (int) $club['id'] ?>"><?= htmlspecialchars(t('clubs.table.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('clubs.table.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="club_id" value="<?= (int) $club['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(t('clubs.table.delete'), ENT_QUOTES, 'UTF-8') ?></button>
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
