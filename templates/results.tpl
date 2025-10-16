<?php
/** @var array $classes */
/** @var array $selectedClass */
/** @var array $results */
/** @var array $audits */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Ergebnisse</h1>
    <form method="get" class="d-flex gap-2">
        <select name="class_id" class="form-select">
            <?php foreach ($classes as $class): ?>
                <option value="<?= (int) $class['id'] ?>" <?= (int) $selectedClass['id'] === (int) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['title'] . ' · ' . $class['label'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Wechseln</button>
    </form>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                <tr>
                    <th>Rang</th>
                    <th>Startnr.</th>
                    <th>Reiter</th>
                    <th>Pferd</th>
                    <th>Gesamt</th>
                    <th>Penalties</th>
                    <th>Zeit</th>
                    <th>Status</th>
                    <th>Regel</th>
                    <th class="text-end"></th>
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
                            <?php if (!empty($result['eliminated'])): ?><span class="badge bg-danger ms-2">elim.</span><?php endif; ?>
                            <?php if (!empty($result['tiebreak_path'])): ?>
                                <div class="text-muted small"><?= htmlspecialchars(implode(' → ', $result['tiebreak_path']), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(number_format((float) ($totals['penalties']['total'] ?? $result['penalties'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= isset($timeInfo['seconds']) ? htmlspecialchars(number_format((float) $timeInfo['seconds'], 2), ENT_QUOTES, 'UTF-8') : '–' ?></td>
                        <td>
                            <span class="badge <?= $result['status'] === 'released' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= htmlspecialchars($result['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td class="small text-muted">
                            <?php if (!empty($result['rule_snapshot']['hash'])): ?>
                                Hash: <?= htmlspecialchars(substr($result['rule_snapshot']['hash'], 0, 8), ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                –
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="result_id" value="<?= (int) $result['id'] ?>">
                                <input type="hidden" name="action" value="<?= $result['status'] === 'released' ? 'revoke' : 'release' ?>">
                                <button class="btn btn-sm <?= $result['status'] === 'released' ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit">
                                    <?= $result['status'] === 'released' ? 'Freigabe zurückziehen' : 'Freigeben' ?>
                                </button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Ergebnis löschen?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="result_id" value="<?= (int) $result['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Löschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$results): ?>
                    <tr><td colspan="10" class="text-muted">Keine Ergebnisse erfasst.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h6">Änderungslog</h2>
        <ul class="list-unstyled mb-0">
            <?php foreach ($audits as $entry): ?>
                <li class="mb-1">
                    <span class="badge bg-light text-dark me-2">#<?= (int) $entry['entity_id'] ?></span>
                    <?= htmlspecialchars($entry['action'], ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(date('d.m.Y H:i', strtotime($entry['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                </li>
            <?php endforeach; ?>
            <?php if (!$audits): ?>
                <li class="text-muted">Keine Änderungen protokolliert.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
