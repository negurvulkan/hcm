<?php
/** @var array $classes */
/** @var array $selectedClass */
/** @var array $results */
/** @var array $audits */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= htmlspecialchars(t('results.title'), ENT_QUOTES, 'UTF-8') ?></h1>
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
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                <tr>
                    <th><?= htmlspecialchars(t('results.table.columns.rank'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('results.table.columns.start_number'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('results.table.columns.rider'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('results.table.columns.horse'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('results.table.columns.total'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('results.table.columns.penalties'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('results.table.columns.time'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('results.table.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(t('results.table.columns.rule'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th class="text-end"><?= htmlspecialchars(t('results.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $result): ?>
                    <?php $breakdown = $result['breakdown'] ?? []; $totals = $breakdown['totals'] ?? []; $timeInfo = $totals['time'] ?? []; ?>
                    <tr<?= !empty($result['eliminated']) ? ' class="table-danger"' : '' ?> >
                        <td><?= $result['rank'] ? (int) $result['rank'] : '–' ?></td>
                        <td><span class="badge bg-primary text-light"><?= htmlspecialchars($result['start_number_display'] ?? $result['start_number_raw'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars($result['rider'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($result['horse'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars(number_format((float) $result['total'], 2), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($result['eliminated'])): ?><span class="badge bg-danger ms-2"><?= htmlspecialchars(t('results.table.badges.eliminated'), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                            <?php if (!empty($result['tiebreak_path'])): ?>
                                <div class="text-muted small"><?= htmlspecialchars(implode(' → ', $result['tiebreak_path']), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(number_format((float) ($totals['penalties']['total'] ?? $result['penalties'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= isset($timeInfo['seconds']) ? htmlspecialchars(number_format((float) $timeInfo['seconds'], 2), ENT_QUOTES, 'UTF-8') : htmlspecialchars(t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= $result['status'] === 'released' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= htmlspecialchars(t('results.status.' . $result['status']), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td class="small text-muted">
                            <?php if (!empty($result['rule_snapshot']['hash'])): ?>
                                <?= htmlspecialchars(t('results.table.rule_hash', ['hash' => substr($result['rule_snapshot']['hash'], 0, 8)]), ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                <?= htmlspecialchars(t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="result_id" value="<?= (int) $result['id'] ?>">
                                <input type="hidden" name="action" value="<?= $result['status'] === 'released' ? 'revoke' : 'release' ?>">
                                <button class="btn btn-sm <?= $result['status'] === 'released' ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit">
                                    <?= htmlspecialchars($result['status'] === 'released' ? t('results.table.actions.revoke_release') : t('results.table.actions.release'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm(<?= json_encode(t('results.table.confirm_delete')) ?>)">
                                <?= csrf_field() ?>
                                <input type="hidden" name="result_id" value="<?= (int) $result['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('common.actions.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$results): ?>
                    <tr><td colspan="10" class="text-muted"><?= htmlspecialchars(t('results.table.empty'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h6"><?= htmlspecialchars(t('results.audits.title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <ul class="list-unstyled mb-0">
            <?php foreach ($audits as $entry): ?>
                <li class="mb-1">
                    <span class="badge bg-light text-dark me-2">#<?= (int) $entry['entity_id'] ?></span>
                    <?= htmlspecialchars($entry['action'], ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(date('d.m.Y H:i', strtotime($entry['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                </li>
            <?php endforeach; ?>
            <?php if (!$audits): ?>
                <li class="text-muted"><?= htmlspecialchars(t('results.audits.empty'), ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
        </ul>
    </div>
</div>
