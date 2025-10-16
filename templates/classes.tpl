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
                        <label class="form-label" for="class-start-number-rules">Startnummern-Regel (JSON)</label>
                        <textarea id="class-start-number-rules" name="start_number_rules" class="form-control" rows="6" spellcheck="false" placeholder='{"mode":"classic"}'><?= htmlspecialchars($editClass['start_number_rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text">Leer lassen oder die Einstellungen im Designer darunter verwenden.</div>
                    </div>
                    <div class="card border-secondary mb-3" data-start-number-designer data-target="#class-start-number-rules"
                         data-rule="<?= htmlspecialchars($classRuleDesignerJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>"
                         data-default="<?= htmlspecialchars($classRuleDefaultsJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>"
                        <?php if (!empty($classRuleEventJson)): ?> data-event-rule="<?= htmlspecialchars($classRuleEventJson, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div>
                                    <h3 class="h6 mb-1">Startnummern-Designer</h3>
                                    <p class="text-muted mb-0">Passe Sequenz, Format und Overrides bequem per UI an.</p>
                                </div>
                                <div class="btn-toolbar" role="toolbar">
                                    <div class="btn-group btn-group-sm me-2" role="group">
                                        <button class="btn btn-outline-secondary" type="button" data-action="load-json">JSON laden</button>
                                        <button class="btn btn-outline-secondary" type="button" data-action="reset-defaults">Zurücksetzen</button>
                                    </div>
                                    <div class="btn-group btn-group-sm me-2" role="group">
                                        <button class="btn btn-outline-primary" type="button" data-action="load-preset" data-preset="classic">Classic</button>
                                        <button class="btn btn-outline-primary" type="button" data-action="load-preset" data-preset="western">Western</button>
                                    </div>
                                    <?php if (!empty($classRuleEventJson)): ?>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-secondary" type="button" data-action="load-event-rule">Turnierregel übernehmen</button>
                                        </div>
                                    <?php endif; ?>
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
                                Definiere Bedingungen pro Override (z. B. Klasse, Division, Arena, Datum). Nur ausgefüllte Felder werden berücksichtigt.
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <button class="btn btn-sm btn-outline-primary" type="submit" name="simulate" value="1" formnovalidate>Simulation (n=10)</button>
                        <?php if (!empty($classSimulationError)): ?>
                            <span class="text-danger small"><?= htmlspecialchars($classSimulationError, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($classSimulation)): ?>
                        <div class="table-responsive mb-3">
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
