<?php
/** @var array $events */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= $editEvent ? 'Turnier bearbeiten' : 'Turnier anlegen' ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editEvent ? 'update' : 'create' ?>">
                    <input type="hidden" name="event_id" value="<?= $editEvent ? (int) $editEvent['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Titel</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editEvent['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label">Start</label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($editEvent['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Ende</label>
                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($editEvent['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Orte/Plätze (durch Komma getrennt)</label>
                        <input type="text" name="venues" class="form-control" placeholder="Hauptplatz, Abreitehalle" value="<?= htmlspecialchars(isset($editEvent['venues_list']) ? implode(', ', $editEvent['venues_list']) : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit">Speichern</button>
                        <?php if ($editEvent): ?>
                            <a href="events.php" class="btn btn-outline-secondary">Abbrechen</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">Turniere</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Titel</th>
                            <th>Zeitraum</th>
                            <th>Orte</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($event['start_date'] ?? '–', ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($event['end_date'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php foreach ($event['venues_list'] as $venue): ?>
                                        <span class="badge bg-light text-dark me-1 mb-1"><?= htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="events.php?edit=<?= (int) $event['id'] ?>">Bearbeiten</a>
                                        <form method="post" onsubmit="return confirm('Turnier wirklich löschen? Dies kann nicht rückgängig gemacht werden.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Löschen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
