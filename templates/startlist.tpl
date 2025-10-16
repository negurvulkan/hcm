<?php
/** @var array $classes */
/** @var array $selectedClass */
/** @var array $startlist */
/** @var array $conflicts */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Startliste</h1>
    <form method="get" class="d-flex gap-2">
        <select name="class_id" class="form-select">
            <?php foreach ($classes as $class): ?>
                <option value="<?= (int) $class['id'] ?>" <?= (int) $selectedClass['id'] === (int) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['title'] . ' · ' . $class['label'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Wechseln</button>
    </form>
</div>

<div class="mb-3">
    <form method="post" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="generate">
        <input type="hidden" name="class_id" value="<?= (int) $selectedClass['id'] ?>">
        <button class="btn btn-accent" type="submit">Startliste generieren</button>
    </form>
</div>

<?php if ($conflicts): ?>
    <div class="alert alert-warning">
        <strong>Hinweis:</strong> Folgende Pferde starten sehr dicht hintereinander:
        <ul class="mb-0">
            <?php foreach ($conflicts as [$first, $second]): ?>
                <li><?= htmlspecialchars($first['horse'], ENT_QUOTES, 'UTF-8') ?> (Nr. <?= (int) $first['position'] ?>) &amp; <?= htmlspecialchars($second['horse'], ENT_QUOTES, 'UTF-8') ?> (Nr. <?= (int) $second['position'] ?>)</li>
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
                    <th>#</th>
                    <th>Startnr.</th>
                    <th>Reiter</th>
                    <th>Pferd</th>
                    <th>Geplanter Start &amp; Notiz</th>
                    <th>Status</th>
                    <th>Aktionen</th>
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
                                <span class="text-muted">–</span>
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
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Speichern</button>
                                </div>
                                <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Notiz"><?= htmlspecialchars($item['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </form>
                        </td>
                        <td>
                            <span class="badge <?= $item['state'] === 'withdrawn' ? 'bg-danger' : 'bg-success' ?>">
                                <?= htmlspecialchars($item['state'], ENT_QUOTES, 'UTF-8') ?>
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
                                    <?= $item['state'] === 'withdrawn' ? 'Reaktivieren' : 'Abmelden' ?>
                                </button>
                            </form>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reassign_number">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit" <?= !empty($item['start_number_locked_at']) ? 'disabled' : '' ?>>Neu zuweisen</button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Start endgültig entfernen?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Löschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
