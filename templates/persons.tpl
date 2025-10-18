<?php
/** @var array $roles */
/** @var array $persons */
/** @var array $clubs */
/** @var array $staffRoles */
/** @var array $staffPersons */
/** @var array $participantPersons */
/** @var string $activeTab */
/** @var array $personStatuses */

$activePersons = $activeTab === 'staff' ? $staffPersons : $participantPersons;
$availableRoles = $activeTab === 'staff' ? $staffRoles : [];
$emptyMessageKey = $activeTab === 'staff' ? 'persons.list.empty_staff' : 'persons.list.empty_participants';

$buildTabLink = static function (string $tab) use ($filterName, $filterRole, $filterStatus, $activeTab): string {
    $params = ['tab' => $tab];
    if (($filterName ?? '') !== '') {
        $params['q'] = $filterName;
    }
    if ($tab === $activeTab && ($filterRole ?? '') !== '') {
        $params['role'] = $filterRole;
    }
    if ($tab === $activeTab && ($filterStatus ?? '') !== '') {
        $params['status'] = $filterStatus;
    }

    return 'persons.php?' . http_build_query($params);
};
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
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('persons.form.given_name'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="given_name" class="form-control" value="<?= htmlspecialchars($editPerson['given_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('persons.form.family_name'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="family_name" class="form-control" value="<?= htmlspecialchars($editPerson['family_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('persons.form.email'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editPerson['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <small class="text-muted"><?= htmlspecialchars(t('persons.form.contact_hint'), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('persons.form.phone'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editPerson['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('persons.form.date_of_birth'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($editPerson['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('persons.form.nationality'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="nationality" maxlength="3" class="form-control" value="<?= htmlspecialchars($editPerson['nationality'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <small class="text-muted"><?= htmlspecialchars(t('persons.form.nationality_hint'), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
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
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('persons.form.status'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="status" class="form-select">
                            <?php foreach ($personStatuses as $status): ?>
                                <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $editPerson ? (($editPerson['status'] ?? 'active') === $status ? 'selected' : '') : ($status === 'active' ? 'selected' : '') ?>><?= htmlspecialchars(t('persons.status.' . $status), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php
                    $customFieldFormData = $personCustomFieldForm;
                    $customFieldShowEmpty = false;
                    require __DIR__ . '/partials/custom_fields_form.tpl';
                    ?>
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
                <ul class="nav nav-pills mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'staff' ? 'active' : '' ?>" href="<?= htmlspecialchars($buildTabLink('staff'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('persons.tabs.staff'), ENT_QUOTES, 'UTF-8') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'participants' ? 'active' : '' ?>" href="<?= htmlspecialchars($buildTabLink('participants'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('persons.tabs.participants'), ENT_QUOTES, 'UTF-8') ?></a>
                    </li>
                </ul>
                <form class="row g-2 align-items-end mb-3">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="col-12 col-md-<?= $activeTab === 'staff' ? '5' : '6' ?>">
                        <label class="form-label"><?= htmlspecialchars(t('persons.filters.name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($filterName ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <?php if ($activeTab === 'staff'): ?>
                        <div class="col-12 col-md-3">
                            <label class="form-label"><?= htmlspecialchars(t('persons.filters.role'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="role" class="form-select">
                                <option value=""><?= htmlspecialchars(t('persons.filters.all_roles'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php foreach ($availableRoles as $role): ?>
                                    <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= ($filterRole ?? '') === $role ? 'selected' : '' ?>><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label"><?= htmlspecialchars(t('persons.filters.status'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="status" class="form-select">
                                <option value=""><?= htmlspecialchars(t('persons.filters.all_statuses'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php foreach ($personStatuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= ($filterStatus ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars(t('persons.status.' . $status), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-1">
                            <button class="btn btn-outline-secondary w-100" type="submit"><?= htmlspecialchars(t('persons.filters.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    <?php else: ?>
                        <div class="col-12 col-md-4">
                            <label class="form-label"><?= htmlspecialchars(t('persons.filters.status'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="status" class="form-select">
                                <option value=""><?= htmlspecialchars(t('persons.filters.all_statuses'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php foreach ($personStatuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= ($filterStatus ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars(t('persons.status.' . $status), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <button class="btn btn-outline-secondary w-100" type="submit"><?= htmlspecialchars(t('persons.filters.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    <?php endif; ?>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('persons.table.name'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('persons.table.contact'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('persons.table.roles'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('persons.table.club'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('persons.table.status'), ENT_QUOTES, 'UTF-8') ?></th>
                            <?php foreach ($personCustomFieldColumns as $column): ?>
                                <th><?= htmlspecialchars($column['label'], ENT_QUOTES, 'UTF-8') ?></th>
                            <?php endforeach; ?>
                            <th class="text-end"><?= htmlspecialchars(t('persons.table.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($activePersons as $person): ?>
                            <tr>
                                <td>
                                    <div><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($person['date_of_birth']) || !empty($person['nationality'])): ?>
                                        <div class="text-muted small">
                                            <?php if (!empty($person['date_of_birth'])): ?>
                                                <span><?= htmlspecialchars($person['date_of_birth'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($person['nationality'])): ?>
                                                <span class="ms-2 text-uppercase"><?= htmlspecialchars($person['nationality'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
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
                                <td>
                                    <?php
                                    $statusKey = $person['status'] ?? 'active';
                                    $statusClass = match ($statusKey) {
                                        'active' => 'bg-success',
                                        'blocked' => 'bg-warning text-dark',
                                        'archived' => 'bg-secondary',
                                        default => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('persons.status.' . $statusKey), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <?php foreach ($personCustomFieldColumns as $column): ?>
                                    <td><?= htmlspecialchars($person['custom_fields'][$column['key']] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <?php endforeach; ?>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <?php
                                        $editParams = ['edit' => (int) $person['id'], 'tab' => $activeTab];
                                        if (($filterName ?? '') !== '') {
                                            $editParams['q'] = $filterName;
                                        }
                                        if (($filterRole ?? '') !== '' && $activeTab === 'staff') {
                                            $editParams['role'] = $filterRole;
                                        }
                                        if (($filterStatus ?? '') !== '') {
                                            $editParams['status'] = $filterStatus;
                                        }
                                        ?>
                                        <a class="btn btn-sm btn-outline-secondary" href="persons.php?<?= htmlspecialchars(http_build_query($editParams), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('persons.table.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('persons.table.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="person_id" value="<?= (int) $person['id'] ?>">
                                            <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(t('persons.table.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$activePersons): ?>
                            <tr>
                                <td colspan="<?= 6 + count($personCustomFieldColumns) ?>" class="text-center text-muted py-4"><?= htmlspecialchars(t($emptyMessageKey), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
