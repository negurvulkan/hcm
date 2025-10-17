<?php
/** @var array $persons */
/** @var array $shifts */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars($editShift ? t('helpers.form.edit_title') : t('helpers.form.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editShift ? 'update' : 'create' ?>">
                    <input type="hidden" name="shift_id" value="<?= $editShift ? (int) $editShift['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('helpers.form.role'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="role" class="form-control" value="<?= htmlspecialchars($editShift['role'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('helpers.form.station'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="station" class="form-control" value="<?= htmlspecialchars($editShift['station'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('helpers.form.person'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="person_id" class="form-select">
                            <option value=""><?= htmlspecialchars(t('helpers.form.person_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($persons as $person): ?>
                                <option value="<?= (int) $person['id'] ?>" <?= $editShift && (int) ($editShift['person_id'] ?? 0) === (int) $person['id'] ? 'selected' : '' ?>><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label"><?= htmlspecialchars(t('helpers.form.start'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div data-datetime-picker>
                                <input type="datetime-local" name="start_time" class="form-control" value="<?= htmlspecialchars(isset($editShift['start_time']) && $editShift['start_time'] ? date('Y-m-d\TH:i', strtotime($editShift['start_time'])) : '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="col">
                            <label class="form-label"><?= htmlspecialchars(t('helpers.form.end'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div data-datetime-picker>
                                <input type="datetime-local" name="end_time" class="form-control" value="<?= htmlspecialchars(isset($editShift['end_time']) && $editShift['end_time'] ? date('Y-m-d\TH:i', strtotime($editShift['end_time'])) : '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('helpers.form.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editShift): ?>
                            <a href="helpers.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('helpers.form.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('helpers.table.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('helpers.table.columns.role'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('helpers.table.columns.person'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('helpers.table.columns.period'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('helpers.table.columns.token'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('helpers.table.columns.check_in'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th class="text-end"><?= htmlspecialchars(t('helpers.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($shifts as $shift): ?>
                            <tr>
                                <td><?= htmlspecialchars($shift['role'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($shift['person'] ?? t('helpers.table.person_open'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($shift['start_time'] ?? t('helpers.table.time_placeholder'), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($shift['end_time'] ?? t('helpers.table.time_placeholder'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><code><?= htmlspecialchars($shift['token'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td>
                                    <?php if ($shift['checked_in_at']): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars(date('H:i', strtotime($shift['checked_in_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="checkin">
                                            <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit"><?= htmlspecialchars(t('helpers.table.check_in_button'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="helpers.php?edit=<?= (int) $shift['id'] ?>"><?= htmlspecialchars(t('helpers.table.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('helpers.table.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('helpers.table.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$shifts): ?>
                            <tr><td colspan="6" class="text-muted"><?= htmlspecialchars(t('helpers.table.none'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
