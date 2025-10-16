<?php
/** @var array $persons */
/** @var array $shifts */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Schicht anlegen</h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Rolle</label>
                        <input type="text" name="role" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Station</label>
                        <input type="text" name="station" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Person</label>
                        <select name="person_id" class="form-select">
                            <option value="">Noch offen</option>
                            <?php foreach ($persons as $person): ?>
                                <option value="<?= (int) $person['id'] ?>"><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label">Beginn</label>
                            <div data-datetime-picker>
                                <input type="datetime-local" name="start_time" class="form-control">
                            </div>
                        </div>
                        <div class="col">
                            <label class="form-label">Ende</label>
                            <div data-datetime-picker>
                                <input type="datetime-local" name="end_time" class="form-control">
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-accent w-100 mt-3" type="submit">Speichern</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">Schichtplan</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Rolle</th>
                            <th>Person</th>
                            <th>Zeitraum</th>
                            <th>Token</th>
                            <th>Check-in</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($shifts as $shift): ?>
                            <tr>
                                <td><?= htmlspecialchars($shift['role'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($shift['person'] ?? 'Offen', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($shift['start_time'] ?? '–', ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($shift['end_time'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><code><?= htmlspecialchars($shift['token'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td>
                                    <?php if ($shift['checked_in_at']): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars(date('H:i', strtotime($shift['checked_in_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="checkin">
                                            <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">Check-in</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$shifts): ?>
                            <tr><td colspan="5" class="text-muted">Noch keine Schichten.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
