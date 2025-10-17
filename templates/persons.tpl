<?php
/** @var array $roles */
/** @var array $persons */
/** @var array $clubs */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars($editPerson ? t('persons.form.edit_title') : t('persons.form.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editPerson ? 'update' : 'create' ?>">
                    <input type="hidden" name="person_id" value="<?= $editPerson ? (int) $editPerson['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('persons.form.name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editPerson['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('persons.form.email'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editPerson['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('persons.form.phone'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editPerson['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('persons.form.password'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="password" name="password" class="form-control" <?= $editPerson ? '' : 'autocomplete="new-password"' ?>>
                        <small class="text-muted"><?= htmlspecialchars($editPerson ? t('persons.form.password_hint_update') : t('persons.form.password_hint_create'), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('persons.form.password_confirm'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="password" name="password_confirm" class="form-control" <?= $editPerson ? '' : 'autocomplete="new-password"' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('persons.form.roles'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="roles[]" class="form-select" multiple size="<?= min(6, count($roles)) ?>">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= $editPerson && in_array($role, $editPerson['role_list'] ?? [], true) ? 'selected' : '' ?>><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted"><?= htmlspecialchars(t('persons.form.roles_hint'), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('persons.form.club'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="club_id" class="form-select">
                            <option value="">–</option>
                            <?php foreach ($clubs as $club): ?>
                                <option value="<?= (int) $club['id'] ?>" <?= $editPerson && (int) ($editPerson['club_id'] ?? 0) === (int) $club['id'] ? 'selected' : '' ?>><?= htmlspecialchars($club['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('persons.form.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editPerson): ?>
                            <a href="persons.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('persons.form.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('persons.list.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form class="row g-2 align-items-end mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars(t('persons.filters.name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($filterName ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?= htmlspecialchars(t('persons.filters.role'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="role" class="form-select">
                            <option value=""><?= htmlspecialchars(t('persons.filters.all_roles'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= ($filterRole ?? '') === $role ? 'selected' : '' ?>><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" type="submit"><?= htmlspecialchars(t('persons.filters.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('persons.table.name'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('persons.table.contact'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('persons.table.roles'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('persons.table.club'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th class="text-end"><?= htmlspecialchars(t('persons.table.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($persons as $person): ?>
                            <tr>
                                <td><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div><?= htmlspecialchars($person['email'] ?? '–', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($person['phone'] ?? '–', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <?php foreach ($person['role_list'] as $role): ?>
                                        <span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td><?= htmlspecialchars($person['club_name'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="persons.php?edit=<?= (int) $person['id'] ?>"><?= htmlspecialchars(t('persons.table.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('persons.table.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="person_id" value="<?= (int) $person['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(t('persons.table.delete'), ENT_QUOTES, 'UTF-8') ?></button>
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
