<?php
/** @var array $classes */
/** @var array $selectedClass */
/** @var array $items */
/** @var array $shifts */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= htmlspecialchars(t('schedule.title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <form method="get" class="d-flex gap-2">
        <select name="class_id" class="form-select">
            <?php foreach ($classes as $class): ?>
                <option value="<?= (int) $class['id'] ?>" <?= (int) $selectedClass['id'] === (int) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['title'] . ' · ' . $class['label'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit"><?= htmlspecialchars(t('common.actions.switch'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5"><?= htmlspecialchars(t('schedule.shift.title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <form method="post" class="row g-3 align-items-end" data-shift-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="shift">
            <div class="col-sm-4">
                <label class="form-label"><?= htmlspecialchars(t('schedule.shift.minutes_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="number" name="minutes" class="form-control" required data-shift-input>
            </div>
            <div class="col-sm-4 col-lg-3">
                <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('schedule.shift.submit'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
            <div class="col-12 col-lg-5">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small text-muted text-uppercase fw-semibold"><?= htmlspecialchars(t('schedule.shift.presets_label'), ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="btn-group btn-group-sm" role="group">
                        <?php foreach ([-10, -5, +5, +10] as $preset): ?>
                            <button type="button" class="btn btn-outline-secondary" data-shift-preset="<?= $preset ?>">
                                <?= htmlspecialchars(sprintf('%+d', $preset), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </form>
        <p class="text-muted small mt-2"><?= htmlspecialchars(t('schedule.shift.note'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5"><?= htmlspecialchars(t('schedule.slots.title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                <tr>
                    <th><?= htmlspecialchars(t('schedule.slots.table.columns.position'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('schedule.slots.table.columns.start_number'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('schedule.slots.table.columns.rider'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('schedule.slots.table.columns.horse'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('schedule.slots.table.columns.planned_start'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th class="text-end"><?= htmlspecialchars(t('schedule.slots.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= (int) $item['position'] ?></td>
                        <td>
                            <?php if (!empty($item['start_number_display'])): ?>
                                <span class="badge bg-primary text-light"><?= htmlspecialchars($item['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <span class="text-muted"><?= htmlspecialchars(t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['rider'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($item['horse'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-slot-editor data-slot-initial="<?= htmlspecialchars($item['planned_start'] ? date('Y-m-d\TH:i', strtotime($item['planned_start'])) : '', ENT_QUOTES, 'UTF-8') ?>">
                            <?php $plannedDisplay = $item['planned_start'] ? date('H:i', strtotime($item['planned_start'])) : t('schedule.slots.table.no_time'); ?>
                            <div class="d-flex align-items-center gap-2" data-slot-view>
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($plannedDisplay, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-slot-edit><?= htmlspecialchars(t('schedule.slots.edit'), ENT_QUOTES, 'UTF-8') ?></button>
                            </div>
                            <form method="post" class="d-flex flex-wrap gap-2 d-none" data-slot-form>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_item">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <input type="datetime-local" name="planned_start" class="form-control form-control-sm" data-slot-input value="<?= htmlspecialchars($item['planned_start'] ? date('Y-m-d\TH:i', strtotime($item['planned_start'])) : '', ENT_QUOTES, 'UTF-8') ?>">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('common.actions.save'), ENT_QUOTES, 'UTF-8') ?></button>
                                    <button class="btn btn-outline-secondary" type="button" data-slot-cancel><?= htmlspecialchars(t('schedule.slots.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                                </div>
                            </form>
                        </td>
                        <td class="text-end">
                            <form method="post" class="d-inline" onsubmit="return confirm(<?= json_encode(t('schedule.slots.confirm_delete')) ?>)">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('common.actions.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h6"><?= htmlspecialchars(t('schedule.history.title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <ul class="list-unstyled mb-0">
            <?php foreach ($shifts as $shift): ?>
                <li><span class="badge bg-light text-dark me-2"><?= htmlspecialchars(t('schedule.history.minutes', ['value' => (int) $shift['shift_minutes']]), ENT_QUOTES, 'UTF-8') ?></span> <?= htmlspecialchars(date('d.m.Y H:i', strtotime($shift['created_at'])), ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
            <?php if (!$shifts): ?>
                <li class="text-muted"><?= htmlspecialchars(t('schedule.history.empty'), ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
        </ul>
    </div>
</div>
