<?php
/** @var array $classes */
/** @var array $selectedClass */
/** @var array $items */
/** @var array $shifts */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Zeitplan</h1>
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
        <h2 class="h5">Live-Verschiebung</h2>
        <form method="post" class="row g-3 align-items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="shift">
            <div class="col-sm-4">
                <label class="form-label">Minuten (+/-)</label>
                <input type="number" name="minutes" class="form-control" required>
            </div>
            <div class="col-sm-4">
                <button class="btn btn-accent" type="submit">Verschieben &amp; Broadcast</button>
            </div>
        </form>
        <p class="text-muted small mt-2">Änderungen werden an Dashboard &amp; Anzeige gesendet.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Slots</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Startnr.</th>
                    <th>Reiter</th>
                    <th>Pferd</th>
                    <th>Geplanter Start</th>
                    <th class="text-end">Aktionen</th>
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
                                <span class="text-muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['rider'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($item['horse'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form method="post" class="d-flex gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_item">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <input type="datetime-local" name="planned_start" class="form-control form-control-sm" value="<?= htmlspecialchars($item['planned_start'] ? date('Y-m-d\TH:i', strtotime($item['planned_start'])) : '', ENT_QUOTES, 'UTF-8') ?>">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">Speichern</button>
                            </form>
                        </td>
                        <td class="text-end">
                            <form method="post" class="d-inline" onsubmit="return confirm('Slot wirklich entfernen?')">
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

<div class="card">
    <div class="card-body">
        <h2 class="h6">Verlauf</h2>
        <ul class="list-unstyled mb-0">
            <?php foreach ($shifts as $shift): ?>
                <li><span class="badge bg-light text-dark me-2"><?= (int) $shift['shift_minutes'] ?> min</span> <?= htmlspecialchars(date('d.m.Y H:i', strtotime($shift['created_at'])), ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
            <?php if (!$shifts): ?>
                <li class="text-muted">Keine Verschiebungen.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
