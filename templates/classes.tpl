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
                        </div> 
                        </div>
                        <textarea id="class-scoring-rule" class="form-control font-monospace" data-rule-json name="rules_json" rows="14" spellcheck="false" placeholder='{"version":"1"}'><?= htmlspecialchars($editClass['rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text">JSON direkt bearbeiten oder über Presets starten. Gültige Regeln werden beim Speichern geprüft.</div>
                        <button class="btn btn-outline-secondary btn-sm mt-2" type="button" data-bs-toggle="modal" data-bs-target="#class-scoring-designer-modal">Scoring-Designer öffnen</button>
                    </div>
                    <div class="modal fade" id="class-scoring-designer-modal" tabindex="-1" aria-labelledby="class-scoring-designer-title" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="class-scoring-designer-title">Scoring-Designer</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="card border-secondary" data-scoring-designer data-target="#class-scoring-rule"
                                         data-default='<?= htmlspecialchars($scoringDesignerDefaultJson ?? "{}", ENT_QUOTES, 'UTF-8') ?>'
                                         data-presets='<?= htmlspecialchars($scoringDesignerPresetsJson ?? "{}", ENT_QUOTES, 'UTF-8') ?>'>
                                        <div class="card-body">
                                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                                                <div>
                                                    <h3 class="h6 mb-1">Bewertungslogik konfigurieren</h3>
                                                    <p class="text-muted mb-0">Passe Komponenten, Zeit und Tiebreaker ohne JSON-Eingabe an.</p>
                                                </div>
                                                <div class="btn-toolbar" role="toolbar">
                                                    <div class="btn-group btn-group-sm me-2" role="group">
                                                        <button class="btn btn-outline-secondary" type="button" data-action="reset-default">Zurücksetzen</button>
                                                    </div>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button class="btn btn-outline-primary" type="button" data-action="load-preset" data-preset="dressage">Dressur</button>
                                                        <button class="btn btn-outline-primary" type="button" data-action="load-preset" data-preset="jumping">Springen</button>
                                                        <button class="btn btn-outline-primary" type="button" data-action="load-preset" data-preset="western">Western</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-4">
                                                    <label class="form-label">Version</label>
                                                    <input type="text" class="form-control form-control-sm" data-scoring-path="version">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Regel-ID</label>
                                                    <input type="text" class="form-control form-control-sm" data-scoring-path="id">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Anzeigename</label>
                                                    <input type="text" class="form-control form-control-sm" data-scoring-path="label">
                                                </div>
                                            </div>
                                            <h4 class="h6">Richterkonfiguration</h4>
                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-4">
                                                    <label class="form-label">Richter (min)</label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="input.judges.min" data-type="integer" min="1">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Richter (max)</label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="input.judges.max" data-type="integer" min="1">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Aggregation</label>
                                                    <select class="form-select form-select-sm" data-scoring-path="input.judges.aggregation.method">
                                                        <option value="mean">Mittelwert</option>
                                                        <option value="sum">Summe</option>
                                                        <option value="median">Median</option>
                                                        <option value="best">Bester Wert</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Höchste Streicher</label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="input.judges.aggregation.drop_high" data-type="integer" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Niedrigste Streicher</label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="input.judges.aggregation.drop_low" data-type="integer" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Gewichte (Komma separiert)</label>
                                                    <input type="text" class="form-control form-control-sm" placeholder="1,1,1" data-scoring-path="input.judges.aggregation.weights" data-type="csv-number">
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h4 class="h6 mb-0">Zusatzfelder</h4>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-field">Feld hinzufügen</button>
                                            </div>
                                            <div class="vstack gap-3 mb-4" data-scoring-list="fields" data-empty-text="Keine Zusatzfelder definiert."></div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h4 class="h6 mb-0">Komponenten</h4>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-component">Komponente hinzufügen</button>
                                            </div>
                                            <div class="vstack gap-3 mb-4" data-scoring-list="components" data-empty-text="Keine Komponenten hinterlegt."></div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h4 class="h6 mb-0">Penalties</h4>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-penalty">Penalty hinzufügen</button>
                                            </div>
                                            <div class="vstack gap-3 mb-4" data-scoring-list="penalties" data-empty-text="Keine Penalties konfiguriert."></div>
                                            <h4 class="h6">Zeitbewertung</h4>
                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-4">
                                                    <label class="form-label">Zeitmodus</label>
                                                    <select class="form-select form-select-sm" data-scoring-path="time.mode">
                                                        <option value="none">Keine Zeitwertung</option>
                                                        <option value="faults_from_time">Fehler aus Zeit</option>
                                                        <option value="bonus_from_time">Bonus aus Zeit</option>
                                                        <option value="best_time">Bestzeit</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Erlaubte Zeit (Sek.)</label>
                                                    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="time.allowed_s" min="0" step="0.01">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Fehler/Sekunde</label>
                                                    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="time.fault_per_s" step="0.01">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Zeit-Cap (Sek.)</label>
                                                    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="time.cap_s" min="0" step="0.01">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Bonus/Sekunde</label>
                                                    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="time.bonus_per_s" step="0.01">
                                                </div>
                                            </div>
                                            <h4 class="h6">Formeln</h4>
                                            <div class="row g-3 mb-4">
                                                <div class="col-md-6">
                                                    <label class="form-label">Per-Judge-Formel</label>
                                                    <textarea class="form-control font-monospace form-control-sm" rows="2" data-scoring-path="per_judge_formula"></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Aggregationsformel</label>
                                                    <textarea class="form-control font-monospace form-control-sm" rows="2" data-scoring-path="aggregate_formula"></textarea>
                                                </div>
                                            </div>
                                            <h4 class="h6">Ranking</h4>
                                            <div class="row g-3 mb-3">
                                                <div class="col-sm-4">
                                                    <label class="form-label">Sortierreihenfolge</label>
                                                    <select class="form-select form-select-sm" data-scoring-path="ranking.order">
                                                        <option value="desc">Absteigend (höher ist besser)</option>
                                                        <option value="asc">Aufsteigend (niedriger ist besser)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h5 class="h6 mb-0">Tiebreaker-Kette</h5>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-tiebreak">Tiebreaker hinzufügen</button>
                                            </div>
                                            <div class="vstack gap-3 mb-4" data-scoring-list="tiebreakers" data-empty-text="Keine Tiebreaker gesetzt."></div>
                                            <h4 class="h6">Ausgabe</h4>
                                            <div class="row g-3">
                                                <div class="col-sm-4">
                                                    <label class="form-label">Rundung (Nachkommastellen)</label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="output.rounding" data-type="integer" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label">Einheit</label>
                                                    <input type="text" class="form-control form-control-sm" data-scoring-path="output.unit">
                                                </div>
                                                <div class="col-sm-4 d-flex align-items-center">
                                                    <div class="form-check mt-3 mt-sm-0">
                                                        <input class="form-check-input" type="checkbox" value="1" id="class-scoring-show-breakdown" data-scoring-path="output.show_breakdown" data-type="boolean">
                                                        <label class="form-check-label" for="class-scoring-show-breakdown">Breakdown anzeigen</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <small class="text-muted me-auto">Änderungen werden sofort in das JSON-Feld übernommen.</small>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Schließen</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="input-group input-group-sm" style="max-width: 200px;">
                            <span class="input-group-text">n</span>
                            <input type="number" class="form-control" name="simulation_count" value="<?= (int) ($simulationCount ?? 10) ?>" min="1" max="50">
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="submit" name="simulate_scoring" value="1" formnovalidate>Scoring-Simulation</button>
                        <?php if (!empty($scoringSimulationError)): ?>
                            <span class="text-danger small"><?= htmlspecialchars($scoringSimulationError, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($scoringSimulation)): ?>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm">
                                <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Total</th>
                                    <th>Penalties</th>
                                    <th>Zeit</th>
                                    <th>Elim</th>
                                    <th>Rank</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($scoringSimulation as $index => $sample): ?>
                                    <?php $result = $sample['result'] ?? []; ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars(number_format((float) ($result['total_rounded'] ?? $result['total_raw'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(number_format((float) ($result['penalties']['total'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(isset($result['time']['seconds']) ? number_format((float) $result['time']['seconds'], 2) : '–', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= !empty($result['eliminated']) ? '<span class="badge bg-danger">ja</span>' : '<span class="badge bg-success-subtle text-success">nein</span>' ?></td>
                                        <td><?= htmlspecialchars((string) ($result['rank'] ?? '–'), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="class-start-number-rules">Startnummern-Regel (JSON)</label>
                        <textarea id="class-start-number-rules" name="start_number_rules" class="form-control" rows="6" spellcheck="false" placeholder='{"mode":"classic"}'><?= htmlspecialchars($editClass['start_number_rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text">Leer lassen oder die Einstellungen über den Designer im Modal anpassen.</div>
                        <button class="btn btn-outline-secondary btn-sm mt-2" type="button" data-bs-toggle="modal" data-bs-target="#class-start-number-designer-modal">Startnummern-Designer öffnen</button>
                    </div>
                    <div class="modal fade" id="class-start-number-designer-modal" tabindex="-1" aria-labelledby="class-start-number-designer-title" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="class-start-number-designer-title">Startnummern-Designer</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="card border-secondary" data-start-number-designer data-target="#class-start-number-rules"
                                         data-rule="<?= htmlspecialchars($classRuleDesignerJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>"
                                         data-default="<?= htmlspecialchars($classRuleDefaultsJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>"
                                        <?php if (!empty($classRuleEventJson)): ?> data-event-rule="<?= htmlspecialchars($classRuleEventJson, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
                                        <div class="card-body">
                                            <div class="d-flex align-items-start justify-content-between mb-3">
                                                <div>
                                                    <h3 class="h6 mb-1">Konfiguration</h3>
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
                                </div>
                                <div class="modal-footer">
                                    <small class="text-muted me-auto">Änderungen werden sofort im JSON-Feld übernommen.</small>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Schließen</button>
                                </div>
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
