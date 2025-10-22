
<?php
/** @var array $roles */
/** @var array $stations */
/** @var array $events */
/** @var array $persons */
/** @var array $shifts */
/** @var array $filters */
/** @var array|null $editRole */
/** @var array|null $editShift */
/** @var array $statusOptions */
?>
<div class="row g-4">
    <div class="col-xxl-3">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars($editRole ? t('helpers.roles.edit_title') : t('helpers.roles.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post" class="d-grid gap-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editRole ? 'update_role' : 'create_role' ?>">
                    <input type="hidden" name="role_id" value="<?= $editRole ? (int) $editRole['id'] : '' ?>">
                    <div>
                        <label class="form-label" for="role-name"><?= htmlspecialchars(t('helpers.roles.fields.name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" class="form-control" id="role-name" name="name" required value="<?= htmlspecialchars($editRole['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div>
                        <label class="form-label" for="role-key"><?= htmlspecialchars(t('helpers.roles.fields.key'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" class="form-control" id="role-key" name="role_key" value="<?= htmlspecialchars($editRole['role_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-text"><?= htmlspecialchars(t('helpers.roles.key_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div>
                        <label class="form-label" for="role-color"><?= htmlspecialchars(t('helpers.roles.fields.color'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="color" class="form-control form-control-color" id="role-color" name="color" value="<?= htmlspecialchars($editRole['color'] ?? '#6c757d', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="role-active" name="active" value="1" <?= $editRole ? ((int) $editRole['active'] === 1 ? 'checked' : '') : 'checked' ?>>
                        <label class="form-check-label" for="role-active"><?= htmlspecialchars(t('helpers.roles.fields.active'), ENT_QUOTES, 'UTF-8') ?></label>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('helpers.roles.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editRole): ?>
                            <a href="helpers.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('helpers.roles.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('helpers.roles.list_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (!$roles): ?>
                    <p class="text-muted mb-0"><?= htmlspecialchars(t('helpers.roles.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($roles as $role): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge me-2" style="background-color: <?= htmlspecialchars($role['color'] ?? '#6c757d', ENT_QUOTES, 'UTF-8') ?>">&nbsp;</span>
                                    <span class="fw-semibold"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!(int) $role['active']): ?>
                                        <span class="badge bg-secondary ms-2"><?= htmlspecialchars(t('helpers.roles.inactive'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="btn-group">
                                    <a href="helpers.php?edit_role=<?= (int) $role['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(t('helpers.roles.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                    <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('helpers.roles.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_role">
                                        <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(t('helpers.roles.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xxl-9">
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars($editShift ? t('helpers.shifts.edit_title') : t('helpers.shifts.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editShift ? 'update_shift' : 'create_shift' ?>">
                    <input type="hidden" name="shift_id" value="<?= $editShift ? (int) $editShift['id'] : '' ?>">
                    <div class="col-md-4">
                        <label class="form-label" for="shift-role"><?= htmlspecialchars(t('helpers.shifts.fields.role'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="shift-role" name="role_id" required>
                            <option value=""><?= htmlspecialchars(t('helpers.shifts.placeholders.role'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int) $role['id'] ?>" <?= $editShift && (int) $editShift['role_id'] === (int) $role['id'] ? 'selected' : '' ?>><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="shift-station"><?= htmlspecialchars(t('helpers.shifts.fields.station'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="shift-station" name="station_id">
                            <option value=""><?= htmlspecialchars(t('helpers.shifts.placeholders.station'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($stations as $station): ?>
                                <option value="<?= (int) $station['id'] ?>" <?= $editShift && (int) ($editShift['station_id'] ?? 0) === (int) $station['id'] ? 'selected' : '' ?>><?= htmlspecialchars($station['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="shift-event"><?= htmlspecialchars(t('helpers.shifts.fields.event'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="shift-event" name="event_id">
                            <option value=""><?= htmlspecialchars(t('helpers.shifts.placeholders.event'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= (int) $event['id'] ?>" <?= $editShift && (int) ($editShift['event_id'] ?? 0) === (int) $event['id'] ? 'selected' : '' ?>><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="shift-person"><?= htmlspecialchars(t('helpers.shifts.fields.person'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="shift-person" name="person_id">
                            <option value=""><?= htmlspecialchars(t('helpers.shifts.placeholders.person'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($persons as $person): ?>
                                <option value="<?= (int) $person['id'] ?>" <?= $editShift && (int) ($editShift['person_id'] ?? 0) === (int) $person['id'] ? 'selected' : '' ?>><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="shift-starts"><?= htmlspecialchars(t('helpers.shifts.fields.starts_at'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="datetime-local" class="form-control" id="shift-starts" name="starts_at" value="<?= htmlspecialchars($editShift['starts_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="shift-ends"><?= htmlspecialchars(t('helpers.shifts.fields.ends_at'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="datetime-local" class="form-control" id="shift-ends" name="ends_at" value="<?= htmlspecialchars($editShift['ends_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="shift-status"><?= htmlspecialchars(t('helpers.shifts.fields.status'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="shift-status" name="status">
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $editShift && ($editShift['status'] ?? 'open') === $value ? 'selected' : (!$editShift && $value === 'open' ? 'selected' : '') ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="shift-notes"><?= htmlspecialchars(t('helpers.shifts.fields.notes'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea class="form-control" id="shift-notes" name="notes" rows="2"><?= htmlspecialchars($editShift['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('helpers.shifts.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editShift): ?>
                            <a href="helpers.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('helpers.shifts.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <form class="row g-2 align-items-end mb-3" method="get">
                    <div class="col-md-3">
                        <label class="form-label" for="filter-status"><?= htmlspecialchars(t('helpers.shifts.filters.status'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="filter-status" name="status">
                            <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>><?= htmlspecialchars(t('helpers.shifts.filters.status_all'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="filter-role"><?= htmlspecialchars(t('helpers.shifts.filters.role'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="filter-role" name="role_id">
                            <option value="0"><?= htmlspecialchars(t('helpers.shifts.placeholders.role'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int) $role['id'] ?>" <?= (int) $filters['role_id'] === (int) $role['id'] ? 'selected' : '' ?>><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="filter-station"><?= htmlspecialchars(t('helpers.shifts.filters.station'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="filter-station" name="station_id">
                            <option value="0"><?= htmlspecialchars(t('helpers.shifts.placeholders.station'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($stations as $station): ?>
                                <option value="<?= (int) $station['id'] ?>" <?= (int) $filters['station_id'] === (int) $station['id'] ? 'selected' : '' ?>><?= htmlspecialchars($station['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="filter-person"><?= htmlspecialchars(t('helpers.shifts.filters.person'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="filter-person" name="person_id">
                            <option value="0"><?= htmlspecialchars(t('helpers.shifts.placeholders.person'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($persons as $person): ?>
                                <option value="<?= (int) $person['id'] ?>" <?= (int) $filters['person_id'] === (int) $person['id'] ? 'selected' : '' ?>><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="filter-day"><?= htmlspecialchars(t('helpers.shifts.filters.day'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="date" class="form-control" id="filter-day" name="day" value="<?= htmlspecialchars($filters['day'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-2 ms-md-auto">
                        <button class="btn btn-outline-secondary w-100" type="submit"><?= htmlspecialchars(t('helpers.shifts.filters.apply'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('helpers.shifts.table.columns.role'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('helpers.shifts.table.columns.station'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('helpers.shifts.table.columns.person'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('helpers.shifts.table.columns.period'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('helpers.shifts.table.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th class="text-end"><?= htmlspecialchars(t('helpers.shifts.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($shifts as $shift): ?>
                            <?php $status = $shift['status'] ?? 'open'; ?>
                            <tr>
                                <td>
                                    <span class="badge rounded-pill" style="background-color: <?= htmlspecialchars($shift['role_color'] ?? '#6c757d', ENT_QUOTES, 'UTF-8') ?>">&nbsp;</span>
                                    <span class="ms-2 fw-semibold"><?= htmlspecialchars($shift['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td><?= htmlspecialchars($shift['station_name'] ?? t('helpers.shifts.placeholders.station'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($shift['person_name'] ?? t('helpers.shifts.placeholders.person'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($shift['starts_at'] ? date('Y-m-d H:i', strtotime($shift['starts_at'])) : '–', ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($shift['ends_at'] ? date('H:i', strtotime($shift['ends_at'])) : '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($statusOptions[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <?php if ($status === 'open'): ?>
                                            <form method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                                <input type="hidden" name="target_status" value="assigned">
                                                <button type="submit" class="btn btn-sm btn-outline-success"><?= htmlspecialchars(t('helpers.status_actions.assign'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </form>
                                        <?php elseif ($status === 'assigned'): ?>
                                            <form method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                                <input type="hidden" name="target_status" value="open">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(t('helpers.status_actions.reopen'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </form>
                                            <form method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                                <input type="hidden" name="target_status" value="active">
                                                <button type="submit" class="btn btn-sm btn-outline-success"><?= htmlspecialchars(t('helpers.status_actions.start'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </form>
                                        <?php elseif ($status === 'active'): ?>
                                            <form method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                                <input type="hidden" name="target_status" value="assigned">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(t('helpers.status_actions.pause'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </form>
                                            <form method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                                <input type="hidden" name="target_status" value="done">
                                                <button type="submit" class="btn btn-sm btn-outline-success"><?= htmlspecialchars(t('helpers.status_actions.finish'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </form>
                                        <?php elseif ($status === 'done'): ?>
                                            <form method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                                <input type="hidden" name="target_status" value="active">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(t('helpers.status_actions.reopen_active'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="helpers.php?edit_shift=<?= (int) $shift['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(t('helpers.shifts.table.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="duplicate_shift">
                                            <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-info"><?= htmlspecialchars(t('helpers.shifts.table.duplicate'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('helpers.shifts.table.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_shift">
                                            <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(t('helpers.shifts.table.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$shifts): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4"><?= htmlspecialchars(t('helpers.shifts.table.empty'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
