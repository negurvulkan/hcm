<?php
/** @var array $classes */
/** @var array $selectedClass */
/** @var array $startlist */
/** @var array $conflicts */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= htmlspecialchars(t('startlist.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
    <form method="get" class="d-flex gap-2">
        <select name="class_id" class="form-select">
            <?php foreach ($classes as $class): ?>
                <option value="<?= (int) $class['id'] ?>" <?= (int) $selectedClass['id'] === (int) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['title'] . ' · ' . $class['label'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit"><?= htmlspecialchars(t('common.actions.switch'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>

<div class="mb-3">
    <form method="post" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="generate">
        <input type="hidden" name="class_id" value="<?= (int) $selectedClass['id'] ?>">
        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('startlist.actions.generate'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>

<?php if ($conflicts): ?>
    <div class="alert alert-warning">
        <strong><?= htmlspecialchars(t('startlist.conflicts.title'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars(t('startlist.conflicts.description'), ENT_QUOTES, 'UTF-8') ?>
        <ul class="mb-0">
            <?php foreach ($conflicts as [$first, $second]): ?>
                <li><?= htmlspecialchars(t('startlist.conflicts.item', [
                    'first_horse' => $first['horse'],
                    'first_position' => $first['position'],
                    'second_horse' => $second['horse'],
                    'second_position' => $second['position'],
                ]), ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                <tr>
                    <th><?= htmlspecialchars(t('startlist.table.columns.position'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('startlist.table.columns.start_number'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('startlist.table.columns.rider'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('startlist.table.columns.horse'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('startlist.table.columns.planned_start_note'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('startlist.table.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('startlist.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($startlist as $item): ?>
                    <tr class="<?= $item['state'] === 'withdrawn' ? 'table-secondary' : '' ?>">
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
                        <td>
                            <form method="post" class="d-flex flex-column gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_time">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <div class="d-flex gap-2">
                                    <input type="datetime-local" class="form-control form-control-sm" name="planned_start" value="<?= htmlspecialchars($item['planned_start'] ? date('Y-m-d\TH:i', strtotime($item['planned_start'])) : '', ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit"><?= htmlspecialchars(t('common.actions.save'), ENT_QUOTES, 'UTF-8') ?></button>
                                </div>
                                <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="<?= htmlspecialchars(t('startlist.table.note_placeholder'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </form>
                        </td>
                        <td>
                            <span class="badge <?= $item['state'] === 'withdrawn' ? 'bg-danger' : 'bg-success' ?>">
                                <?= htmlspecialchars(t('startlist.status.' . ($item['state'] ?? 'scheduled')), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="move">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="direction" value="up">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">▲</button>
                            </form>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="move">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="direction" value="down">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">▼</button>
                            </form>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_state">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm <?= $item['state'] === 'withdrawn' ? 'btn-outline-success' : 'btn-outline-warning' ?>" type="submit">
                                    <?= htmlspecialchars($item['state'] === 'withdrawn' ? t('startlist.actions.reactivate') : t('startlist.actions.withdraw'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </form>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reassign_number">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit" <?= !empty($item['start_number_locked_at']) ? 'disabled' : '' ?>><?= htmlspecialchars(t('startlist.actions.reassign_number'), ENT_QUOTES, 'UTF-8') ?></button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm(<?= json_encode(t('startlist.confirm.delete')) ?>)">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('common.actions.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$startlist): ?>
                    <tr>
                        <td colspan="7" class="text-muted"><?= htmlspecialchars(t('startlist.table.empty'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
