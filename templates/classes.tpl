<?php
/** @var array $events */
/** @var array $classes */
/** @var array $presets */
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= $editClass ? 'Prüfung bearbeiten' : 'Prüfung anlegen' ?></h2>
                <form method="post" data-class-form data-presets='<?= json_encode($presets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editClass ? 'update' : 'create' ?>'>
                    <input type="hidden" name="class_id" value="<?= $editClass ? (int) $editClass['id'] : '' ?>'>
                    <div class="mb-3">
                        <label class="form-label">Turnier</label>
                        <select name="event_id" class="form-select" required>
                            <option value="">Wählen…</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= (int) $event['id'] ?>" <?= $editClass && (int) $editClass['event_id'] === (int) $event['id'] ? 'selected' : '' ?>><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bezeichnung</label>
                        <input type="text" name="label" class="form-control" value="<?= htmlspecialchars($editClass['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Platz</label>
                        <input type="text" name="arena" class="form-control" value="<?= htmlspecialchars($editClass['arena'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label">Startzeit</label>
                            <input type="datetime-local" name="start_time" class="form-control" value="<?= htmlspecialchars($editClass['start_formatted'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Ende</label>
                            <input type="datetime-local" name="end_time" class="form-control" value="<?= htmlspecialchars($editClass['end_formatted'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="mt-3 mb-3">
                        <label class="form-label">Max. Starter</label>
                        <input type="number" name="max_starters" class="form-control" min="1" value="<?= htmlspecialchars($editClass['max_starters'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Richter (Komma getrennt)</label>
                        <input type="text" name="judges" class="form-control" placeholder="Anna Richter, Max Mustermann" value="<?= htmlspecialchars($editClass['judges'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3" data-rule-editor>
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label mb-0">Regeln</label>
                            <button class="btn btn-sm btn-outline-secondary d-none" data-rule-toggle type="button">JSON bearbeiten</button>
                        </div>
                        <div class="form-text mb-2">Konfiguriere die Bewertungslogik je Disziplin oder bearbeite das JSON direkt.</div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button class="btn btn-sm btn-outline-secondary" data-preset="dressage" type="button">Dressur-Vorlage</button>
                            <button class="btn btn-sm btn-outline-secondary" data-preset="jumping" type="button">Springen-Vorlage</button>
                            <button class="btn btn-sm btn-outline-secondary" data-preset="western" type="button">Western-Vorlage</button>
                        </div>
                        <div class="alert alert-warning d-none" role="alert" data-rule-error></div>
                        <div class="border rounded p-3 bg-light-subtle d-none" data-rule-builder>
                            <div class="row g-3 align-items-end mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Disziplin</label>
                                    <select class="form-select form-select-sm" data-rule-type>
                                        <option value="dressage">Dressur</option>
                                        <option value="jumping">Springen</option>
                                        <option value="western">Western</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted small mb-0">Die Eingaben werden automatisch als Regel-JSON gespeichert.</p>
                                </div>
                            </div>
                            <div data-rule-panel="dressage" class="rule-panel">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h3 class="h6 mb-0">Lektionen</h3>
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-movement">Bewegung hinzufügen</button>
                                    </div>
                                    <div class="d-grid gap-2" data-dressage-movements></div>
                                    <p class="text-muted small mb-0 d-none" data-dressage-empty>Füge mindestens eine Lektion hinzu.</p>
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-4">
                                        <label class="form-label">Schrittweite</label>
                                        <input type="number" class="form-control form-control-sm" min="0" step="0.1" data-dressage-step>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label">Aggregation</label>
                                        <select class="form-select form-select-sm" data-dressage-aggregate>
                                            <option value="average">Durchschnitt</option>
                                            <option value="sum">Summe</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" value="1" id="rule-drop-high-low" data-dressage-drop>
                                            <label class="form-check-label" for="rule-drop-high-low">Beste/Schlechteste streichen</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div data-rule-panel="jumping" class="rule-panel d-none">
                                <div class="row g-3">
                                    <div class="col-sm-4">
                                        <div class="form-check mt-sm-4">
                                            <input class="form-check-input" type="checkbox" value="1" id="rule-jump-faults" data-jumping-faults>
                                            <label class="form-check-label" for="rule-jump-faults">Fehlerpunkte erfassen</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label">Erlaubte Zeit (Sekunden)</label>
                                        <input type="number" class="form-control form-control-sm" min="0" data-jumping-time>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label">Zeitfehler pro Sekunde</label>
                                        <input type="number" class="form-control form-control-sm" step="0.01" data-jumping-penalty>
                                    </div>
                                </div>
                            </div>
                            <div data-rule-panel="western" class="rule-panel d-none">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h3 class="h6 mb-0">Manöver</h3>
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-maneuver">Manöver hinzufügen</button>
                                    </div>
                                    <div class="d-grid gap-2" data-western-maneuvers></div>
                                    <p class="text-muted small mb-0 d-none" data-western-empty>Füge mindestens ein Manöver hinzu.</p>
                                </div>
                                <div>
                                    <label class="form-label">Strafpunkte</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="number" class="form-control" step="0.5" data-western-penalty-input placeholder="z. B. 1">
                                        <button class="btn btn-outline-secondary" type="button" data-action="add-penalty">Hinzufügen</button>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2" data-western-penalties></div>
                                </div>
                            </div>
                        </div>
                        <textarea name="rules_json" class="form-control" rows="6" spellcheck="false" data-rule-json placeholder='{"type":"dressage"}'><?= htmlspecialchars($editClass['rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Startnummern-Regel (JSON)</label>
                        <textarea name="start_number_rules" class="form-control" rows="6" spellcheck="false" placeholder='{"mode":"classic"}'><?= htmlspecialchars($editClass['start_number_rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <button class="btn btn-sm btn-outline-primary" type="submit" name="simulate" value="1" formnovalidate>Simulation (n=10)</button>
                            <?php if (!empty($classSimulationError)): ?>
                                <span class="text-danger small"><?= htmlspecialchars($classSimulationError, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($classSimulation)): ?>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Rohwert</th>
                                        <th>Anzeige</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($classSimulation as $index => $preview): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars((string) $preview['raw'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($preview['display'], ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tiebreaker-Kette (Komma getrennt)</label>
                        <input type="text" name="tiebreakers" class="form-control" placeholder="beste Teilnote, Zeit, Los" value="<?= htmlspecialchars($editClass['tiebreakers_list'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit">Speichern</button>
                        <?php if ($editClass): ?>
                            <a href="classes.php" class="btn btn-outline-secondary">Abbrechen</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">Prüfungen</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Turnier</th>
                            <th>Bezeichnung</th>
                            <th>Platz / Zeitraum</th>
                            <th>Richter</th>
                            <th>Tiebreaker</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><?= htmlspecialchars($class['event_title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($class['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div><?= htmlspecialchars($class['arena'] ?? '–', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($class['start_time'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <?php foreach ($class['judges'] as $judge): ?>
                                        <span class="badge bg-light text-dark me-1 mb-1"><?= htmlspecialchars($judge, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php foreach ($class['tiebreakers'] as $item): ?>
                                        <span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="classes.php?edit=<?= (int) $class['id'] ?>">Bearbeiten</a>
                                        <form method="post" onsubmit="return confirm('Prüfung inklusive abhängiger Daten löschen?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Löschen</button>
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
