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
                    <input type="hidden" name="default_action" value="<?= $editEvent ? 'update' : 'create' ?>">
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
                    <div class="mb-3">
                        <label class="form-label">Startnummern-Regeln (JSON)</label>
                        <textarea name="start_number_rules" class="form-control" rows="10" placeholder="{ &quot;mode&quot;: &quot;classic&quot;, ... }"><?= htmlspecialchars($editEvent['start_number_rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text">Leer lassen, um Standard zu verwenden.</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit">Speichern</button>
                        <button class="btn btn-outline-primary" type="submit" name="action" value="simulate_rules" formnovalidate>Simulation (n=20)</button>
                        <?php if ($editEvent): ?>
                            <a href="events.php" class="btn btn-outline-secondary">Abbrechen</a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if (!empty($simulationError)): ?>
                    <div class="alert alert-danger mt-3"><?= htmlspecialchars($simulationError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (!empty($simulation)): ?>
                    <div class="card mt-3">
                        <div class="card-body">
                            <h3 class="h6">Simulation</h3>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($simulation as $entry): ?>
                                    <li><span class="badge bg-primary text-light me-2"><?= htmlspecialchars($entry['display'], ENT_QUOTES, 'UTF-8') ?></span><span class="text-muted">(Raw: <?= (int) $entry['raw'] ?>)</span></li>
                                <?php endforeach; ?>
                                <?php if (!$simulation): ?>
                                    <li class="text-muted">Keine Werte verfügbar.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
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
                            <th>Status</th>
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
                                <td>
                                    <?php if ((int) ($event['is_active'] ?? 0) === 1): ?>
                                        <span class="badge bg-success">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <?php if (!empty($isAdmin)): ?>
                                            <?php if ((int) ($event['is_active'] ?? 0) === 1): ?>
                                                <form method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">Deaktivieren</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="set_active">
                                                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Aktiv setzen</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
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
