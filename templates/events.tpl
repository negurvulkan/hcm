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
                        <label class="form-label" for="start-number-rules-input">Startnummern-Regeln (JSON)</label>
                        <textarea id="start-number-rules-input" name="start_number_rules" class="form-control" rows="10" spellcheck="false" placeholder="{ &quot;mode&quot;: &quot;classic&quot;, ... }"><?= htmlspecialchars($editEvent['start_number_rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text">Leer lassen oder den Designer im Modal verwenden.</div>
                        <button class="btn btn-outline-secondary btn-sm mt-2" type="button" data-bs-toggle="modal" data-bs-target="#event-start-number-designer-modal">Startnummern-Designer öffnen</button>
                    </div>
                    <div class="modal fade" id="event-start-number-designer-modal" tabindex="-1" aria-labelledby="event-start-number-designer-title" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="event-start-number-designer-title">Startnummern-Designer</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="card border-secondary" data-start-number-designer data-target="#start-number-rules-input"
                                         data-rule="<?= htmlspecialchars($ruleDesignerJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>"
                                         data-default="<?= htmlspecialchars($ruleDesignerDefaultsJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start justify-content-between mb-3">
                                                <div>
                                                    <h3 class="h6 mb-1">Konfiguration</h3>
                                                    <p class="text-muted mb-0">Werte anpassen, das JSON wird automatisch im Formular aktualisiert.</p>
                                                </div>
                                                <div class="btn-toolbar" role="toolbar">
                                                    <div class="btn-group btn-group-sm me-2" role="group">
                                                        <button class="btn btn-outline-secondary" type="button" data-action="load-json">JSON in Designer laden</button>
                                                        <button class="btn btn-outline-secondary" type="button" data-action="reset-defaults">Zurücksetzen</button>
                                                    </div>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button class="btn btn-outline-primary" type="button" data-action="load-preset" data-preset="classic">Classic-Vorlage</button>
                                                        <button class="btn btn-outline-primary" type="button" data-action="load-preset" data-preset="western">Western-Vorlage</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-sm-6">
                                                    <label class="form-label">Modus</label>
                                                    <select class="form-select" data-designer-field="mode">
                                                        <option value="classic">Classic</option>
                                                        <option value="western">Western</option>
                                                        <option value="custom">Custom</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label">Scope</label>
                                                    <select class="form-select" data-designer-field="scope">
                                                        <option value="tournament">Turnier</option>
                                                        <option value="class">Klasse</option>
                                                        <option value="arena">Arena</option>
                                                        <option value="day">Tag</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <hr>
                                            <h4 class="h6">Sequenz</h4>
                                            <div class="row g-3">
                                                <div class="col-sm-4">
                                                    <label class="form-label">Start</label>
                                                    <input type="number" class="form-control" data-designer-field="sequence.start" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Schrittweite</label>
                                                    <input type="number" class="form-control" data-designer-field="sequence.step" min="1">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Reset</label>
                                                    <select class="form-select" data-designer-field="sequence.reset">
                                                        <option value="never">Nie</option>
                                                        <option value="per_class">Pro Klasse</option>
                                                        <option value="per_day">Pro Tag</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label">Bereich von</label>
                                                    <input type="number" class="form-control" data-designer-field="sequence.range_min" min="0">
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label">Bereich bis</label>
                                                    <input type="number" class="form-control" data-designer-field="sequence.range_max" min="0">
                                                </div>
                                            </div>
                                            <hr>
                                            <h4 class="h6">Format</h4>
                                            <div class="row g-3">
                                                <div class="col-sm-3">
                                                    <label class="form-label">Prefix</label>
                                                    <input type="text" class="form-control" data-designer-field="format.prefix" maxlength="10">
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label">Ziffernbreite</label>
                                                    <input type="number" class="form-control" data-designer-field="format.width" min="0">
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label">Suffix</label>
                                                    <input type="text" class="form-control" data-designer-field="format.suffix" maxlength="10">
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label">Separator</label>
                                                    <input type="text" class="form-control" data-designer-field="format.separator" maxlength="5">
                                                </div>
                                            </div>
                                            <hr>
                                            <h4 class="h6">Zuteilung</h4>
                                            <div class="row g-3">
                                                <div class="col-sm-3">
                                                    <label class="form-label">Entität</label>
                                                    <select class="form-select" data-designer-field="allocation.entity">
                                                        <option value="start">Start</option>
                                                        <option value="pair">Reiter/Pferd</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label">Zeitpunkt</label>
                                                    <select class="form-select" data-designer-field="allocation.time">
                                                        <option value="on_entry">Bei Nennung</option>
                                                        <option value="on_startlist">Startliste</option>
                                                        <option value="on_gate">Am Gate</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label">Wiederverwendung</label>
                                                    <select class="form-select" data-designer-field="allocation.reuse">
                                                        <option value="never">Nie</option>
                                                        <option value="after_scratch">Nach Abmeldung</option>
                                                        <option value="session">Session</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label">Sperre nach</label>
                                                    <select class="form-select" data-designer-field="allocation.lock_after">
                                                        <option value="sign_off">Freigabe</option>
                                                        <option value="start_called">Aufruf</option>
                                                        <option value="never">Nie</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <hr>
                                            <h4 class="h6">Einschränkungen</h4>
                                            <div class="row g-3">
                                                <div class="col-sm-6">
                                                    <label class="form-label">Eindeutigkeit</label>
                                                    <select class="form-select" data-designer-field="constraints.unique_per">
                                                        <option value="tournament">Turnier</option>
                                                        <option value="class">Klasse</option>
                                                        <option value="day">Tag</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label">Blockierte Nummern</label>
                                                    <input type="text" class="form-control" data-designer-field="constraints.blocklists" placeholder="13, 666">
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label">Vereinsabstand</label>
                                                    <input type="number" class="form-control" data-designer-field="constraints.club_spacing" min="0">
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label">Horse Cooldown (Min.)</label>
                                                    <input type="number" class="form-control" data-designer-field="constraints.horse_cooldown_min" min="0">
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h4 class="h6 mb-0">Overrides</h4>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-override">Override hinzufügen</button>
                                            </div>
                                            <div class="vstack gap-3" data-override-list></div>
                                            <div class="alert alert-secondary mt-3 mb-0 small">
                                                Bedingungen pro Override (z. B. Klasse, Division, Arena, Datum) definieren. Nur ausgefüllte Felder werden übernommen.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <small class="text-muted me-auto">Änderungen werden sofort im JSON-Feld übernommen.</small>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Schließen</button>
                                </div>
                            </div>
                        </div>
                    </div>
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
