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
                        <textarea id="start-number-rules-input" name="start_number_rules" class="form-control" rows="10" placeholder="{ &quot;mode&quot;: &quot;classic&quot;, ... }"><?= htmlspecialchars($editEvent['start_number_rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text">Leer lassen, um Standard zu verwenden oder den Designer verwenden.</div>
                    </div>
                    <div class="card border-secondary mb-3" id="rule-designer"
                         data-rule="<?= htmlspecialchars($ruleDesignerJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>"
                         data-default="<?= htmlspecialchars($ruleDesignerDefaultsJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div>
                                    <h3 class="h6 mb-1">Startnummern-Designer</h3>
                                    <p class="text-muted mb-0">Werte anpassen, JSON wird automatisch aktualisiert.</p>
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
                                        <option value="session">Sitzung</option>
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
                            <div id="rule-override-list" class="vstack gap-3"></div>
                            <div class="alert alert-secondary mt-3 mb-0 small">
                                Bedingungen pro Override (z. B. Klasse, Division, Arena, Datum) definieren. Nur ausgefüllte Felder werden übernommen.
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const designer = document.getElementById('rule-designer');
    const textarea = document.getElementById('start-number-rules-input');
    if (!designer || !textarea) {
        return;
    }

    const defaults = parseJsonSafe(designer.dataset.default, {});
    let state = parseJsonSafe(designer.dataset.rule, defaults);
    const presets = {
        classic: {
            mode: 'classic',
            scope: 'tournament',
            sequence: {
                start: 1,
                step: 1,
                range: [1, 450],
                reset: 'per_day'
            },
            format: {
                prefix: '',
                width: 3,
                suffix: '',
                separator: ''
            },
            allocation: {
                entity: 'start',
                time: 'on_startlist',
                reuse: 'after_scratch',
                lock_after: 'start_called'
            },
            constraints: {
                unique_per: 'tournament',
                blocklists: ['13'],
                club_spacing: 1,
                horse_cooldown_min: 0
            },
            overrides: [
                {
                    if: { division: 'HC' },
                    sequence: {
                        start: 900,
                        step: 1,
                        range: [900, 999],
                        reset: 'per_class'
                    }
                },
                {
                    if: { date: '2024-08-18' },
                    sequence: {
                        start: 200,
                        range: [200, 450]
                    },
                    format: {
                        prefix: 'SUN-'
                    }
                }
            ]
        },
        western: {
            mode: 'western',
            scope: 'arena',
            sequence: {
                start: 50,
                step: 5,
                range: [50, 500],
                reset: 'per_class'
            },
            format: {
                prefix: 'W',
                width: 2,
                suffix: '',
                separator: '-'
            },
            allocation: {
                entity: 'pair',
                time: 'on_entry',
                reuse: 'session',
                lock_after: 'sign_off'
            },
            constraints: {
                unique_per: 'arena',
                blocklists: ['100'],
                club_spacing: 0,
                horse_cooldown_min: 30
            },
            overrides: [
                {
                    if: { arena: 'Trail' },
                    sequence: {
                        start: 300,
                        step: 5,
                        range: [300, 450]
                    },
                    format: {
                        prefix: 'TR'
                    }
                },
                {
                    if: { division: 'Youth' },
                    sequence: {
                        start: 800,
                        step: 2,
                        range: [800, 899]
                    },
                    allocation: {
                        reuse: 'after_scratch'
                    }
                }
            ]
        }
    };
    state = eventsMergeWithDefaults(state);

    const overrideContainer = designer.querySelector('#rule-override-list');
    const fieldSelectors = '[data-designer-field]';

    function parseJsonSafe(value, fallback) {
        if (!value) {
            return fallback;
        }
        try {
            return JSON.parse(value);
        } catch (err) {
            console.warn('JSON parse failed', err);
            return fallback;
        }
    }

    function cloneDefaults() {
        return JSON.parse(JSON.stringify(defaults));
    }

    function getPath(object, path, fallback) {
        if (!object) {
            return fallback;
        }
        const parts = path.split('.');
        let cursor = object;
        for (let i = 0; i < parts.length; i += 1) {
            if (cursor && Object.prototype.hasOwnProperty.call(cursor, parts[i])) {
                cursor = cursor[parts[i]];
            } else {
                return fallback;
            }
        }
        return cursor === undefined ? fallback : cursor;
    }

    function applyStateToForm() {
        const current = state || cloneDefaults();
        designer.querySelectorAll(fieldSelectors).forEach(function (el) {
            const key = el.getAttribute('data-designer-field');
            switch (key) {
                case 'mode':
                    el.value = current.mode || 'classic';
                    break;
                case 'scope':
                    el.value = current.scope || 'tournament';
                    break;
                case 'sequence.start':
                    el.value = getPath(current, 'sequence.start', '');
                    break;
                case 'sequence.step':
                    el.value = getPath(current, 'sequence.step', '');
                    break;
                case 'sequence.reset':
                    el.value = getPath(current, 'sequence.reset', 'never');
                    break;
                case 'sequence.range_min': {
                    const rangeSource = getPath(current, 'sequence.range', []);
                    const range = Array.isArray(rangeSource) ? rangeSource : [];
                    el.value = range.length ? (range[0] === null ? '' : range[0]) : '';
                    break;
                }
                case 'sequence.range_max': {
                    const rangeSource = getPath(current, 'sequence.range', []);
                    const range = Array.isArray(rangeSource) ? rangeSource : [];
                    el.value = range.length > 1 ? (range[1] === null ? '' : range[1]) : '';
                    break;
                }
                case 'format.prefix':
                    el.value = getPath(current, 'format.prefix', '');
                    break;
                case 'format.width':
                    el.value = getPath(current, 'format.width', '');
                    break;
                case 'format.suffix':
                    el.value = getPath(current, 'format.suffix', '');
                    break;
                case 'format.separator':
                    el.value = getPath(current, 'format.separator', '');
                    break;
                case 'allocation.entity':
                    el.value = getPath(current, 'allocation.entity', 'start');
                    break;
                case 'allocation.time':
                    el.value = getPath(current, 'allocation.time', 'on_startlist');
                    break;
                case 'allocation.reuse':
                    el.value = getPath(current, 'allocation.reuse', 'never');
                    break;
                case 'allocation.lock_after':
                    el.value = getPath(current, 'allocation.lock_after', 'sign_off');
                    break;
                case 'constraints.unique_per':
                    el.value = getPath(current, 'constraints.unique_per', 'tournament');
                    break;
                case 'constraints.blocklists':
                    el.value = (getPath(current, 'constraints.blocklists', []) || []).join(', ');
                    break;
                case 'constraints.club_spacing':
                    el.value = getPath(current, 'constraints.club_spacing', '');
                    break;
                case 'constraints.horse_cooldown_min':
                    el.value = getPath(current, 'constraints.horse_cooldown_min', '');
                    break;
            }
        });
        renderOverrides(current.overrides || []);
        syncTextarea();
    }

    function renderOverrides(overrides) {
        overrideContainer.innerHTML = '';
        overrides.forEach(function (item, index) {
            const wrapper = document.createElement('div');
            wrapper.className = 'rule-override border rounded p-3';
            wrapper.dataset.index = String(index);
            wrapper.innerHTML = createOverrideTemplate(item, index);
            overrideContainer.appendChild(wrapper);
        });
        if (overrides.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'text-muted small';
            empty.textContent = 'Keine Overrides definiert.';
            overrideContainer.appendChild(empty);
        }
    }

    function createOverrideTemplate(item, index) {
        const conditions = item && item.if ? item.if : {};
        const sequence = item && item.sequence ? item.sequence : {};
        const format = item && item.format ? item.format : {};
        const allocation = item && item.allocation ? item.allocation : {};
        return '<div class="d-flex justify-content-between align-items-start mb-3">' +
            '<h5 class="h6 mb-0">Override #' + (index + 1) + '</h5>' +
            '<button type="button" class="btn btn-sm btn-outline-danger" data-action="remove-override">Entfernen</button>' +
            '</div>' +
            '<div class="row g-3 mb-3">' +
            '<div class="col-sm-6"><label class="form-label">Klassen-Tag</label><input type="text" class="form-control" data-override-field="if.class_tag" value="' + escapeHtml(conditions.class_tag || '') + '"></div>' +
            '<div class="col-sm-6"><label class="form-label">Division</label><input type="text" class="form-control" data-override-field="if.division" value="' + escapeHtml(conditions.division || '') + '"></div>' +
            '<div class="col-sm-6"><label class="form-label">Arena</label><input type="text" class="form-control" data-override-field="if.arena" value="' + escapeHtml(conditions.arena || '') + '"></div>' +
            '<div class="col-sm-6"><label class="form-label">Datum</label><input type="date" class="form-control" data-override-field="if.date" value="' + escapeHtml(conditions.date || '') + '"></div>' +
            '</div>' +
            '<h6 class="h6">Sequenz</h6>' +
            '<div class="row g-3 mb-3">' +
            '<div class="col-sm-4"><label class="form-label">Start</label><input type="number" class="form-control" data-override-field="sequence.start" value="' + escapeValue(sequence.start) + '"></div>' +
            '<div class="col-sm-4"><label class="form-label">Schrittweite</label><input type="number" class="form-control" data-override-field="sequence.step" value="' + escapeValue(sequence.step) + '"></div>' +
            '<div class="col-sm-4"><label class="form-label">Reset</label>' +
            '<select class="form-select" data-override-field="sequence.reset">' +
            buildOptions(['never', 'per_class', 'per_day'], sequence.reset || '') +
            '</select></div>' +
            '<div class="col-sm-6"><label class="form-label">Bereich von</label><input type="number" class="form-control" data-override-field="sequence.range_min" value="' + escapeValue(getRange(sequence, 0)) + '"></div>' +
            '<div class="col-sm-6"><label class="form-label">Bereich bis</label><input type="number" class="form-control" data-override-field="sequence.range_max" value="' + escapeValue(getRange(sequence, 1)) + '"></div>' +
            '</div>' +
            '<h6 class="h6">Format</h6>' +
            '<div class="row g-3">' +
            '<div class="col-sm-3"><label class="form-label">Prefix</label><input type="text" class="form-control" data-override-field="format.prefix" value="' + escapeHtml(format.prefix || '') + '"></div>' +
            '<div class="col-sm-3"><label class="form-label">Breite</label><input type="number" class="form-control" data-override-field="format.width" value="' + escapeValue(format.width) + '"></div>' +
            '<div class="col-sm-3"><label class="form-label">Suffix</label><input type="text" class="form-control" data-override-field="format.suffix" value="' + escapeHtml(format.suffix || '') + '"></div>' +
            '<div class="col-sm-3"><label class="form-label">Separator</label><input type="text" class="form-control" data-override-field="format.separator" value="' + escapeHtml(format.separator || '') + '"></div>' +
            '</div>' +
            '<h6 class="h6 mt-3">Zuteilung</h6>' +
            '<div class="row g-3">' +
            '<div class="col-sm-3"><label class="form-label">Entität</label><select class="form-select" data-override-field="allocation.entity">' + buildOptions(['', 'start', 'pair'], allocation.entity || '', { '': 'Keine Änderung', start: 'Start', pair: 'Reiter/Pferd' }) + '</select></div>' +
            '<div class="col-sm-3"><label class="form-label">Zeitpunkt</label><select class="form-select" data-override-field="allocation.time">' + buildOptions(['', 'on_entry', 'on_startlist', 'on_gate'], allocation.time || '', { '': 'Keine Änderung', on_entry: 'Bei Nennung', on_startlist: 'Startliste', on_gate: 'Am Gate' }) + '</select></div>' +
            '<div class="col-sm-3"><label class="form-label">Reuse</label><select class="form-select" data-override-field="allocation.reuse">' + buildOptions(['', 'never', 'after_scratch', 'session'], allocation.reuse || '', { '': 'Keine Änderung', never: 'Nie', after_scratch: 'Nach Abmeldung', session: 'Session' }) + '</select></div>' +
            '<div class="col-sm-3"><label class="form-label">Sperre nach</label><select class="form-select" data-override-field="allocation.lock_after">' + buildOptions(['', 'sign_off', 'start_called', 'never'], allocation.lock_after || '', { '': 'Keine Änderung', sign_off: 'Freigabe', start_called: 'Aufruf', never: 'Nie' }) + '</select></div>' +
            '</div>';
    }

    function escapeHtml(value) {
        const safe = value === undefined || value === null ? '' : value;
        return String(safe).replace(/["&'<>]/g, function (c) {
            return ({'"': '&quot;', '&': '&amp;', "'": '&#39;', '<': '&lt;', '>': '&gt;'}[c]);
        });
    }

    function escapeValue(value) {
        if (value === undefined || value === null || value === '') {
            return '';
        }
        return String(value);
    }

    function getRange(sequence, index) {
        if (!sequence || !Array.isArray(sequence.range)) {
            return '';
        }
        const val = sequence.range[index];
        return val === undefined || val === null ? '' : val;
    }

    function buildOptions(values, selected, customLabels) {
        const baseLabels = {
            '': 'Keine Änderung',
            'never': 'Nie',
            'per_class': 'Pro Klasse',
            'per_day': 'Pro Tag',
            'start': 'Start',
            'pair': 'Reiter/Pferd',
            'on_entry': 'Bei Nennung',
            'on_startlist': 'Startliste',
            'on_gate': 'Am Gate',
            'after_scratch': 'Nach Abmeldung',
            'session': 'Session',
            'sign_off': 'Freigabe',
            'start_called': 'Aufruf'
        };
        const labelMap = customLabels ? Object.assign({}, baseLabels, customLabels) : baseLabels;
        return values.map(function (val) {
            const label = Object.prototype.hasOwnProperty.call(labelMap, val) ? labelMap[val] : val;
            const isSelected = val === selected ? ' selected' : '';
            return '<option value="' + val + '"' + isSelected + '>' + label + '</option>';
        }).join('');
    }

    function readOverride(wrapper) {
        const result = {};
        const ifConditions = {};
        wrapper.querySelectorAll('[data-override-field]').forEach(function (input) {
            const path = input.getAttribute('data-override-field');
            const value = input.value;
            if (path.startsWith('if.')) {
                const key = path.split('.')[1];
                if (value !== '') {
                    ifConditions[key] = value;
                }
                return;
            }
                if (path.startsWith('sequence.')) {
                    if (!result.sequence) {
                        result.sequence = {};
                    }
                    const key = path.split('.')[1];
                    if (key === 'range_min' || key === 'range_max') {
                        const existing = Array.isArray(result.sequence.range) ? result.sequence.range : [];
                        const range = [existing[0] === undefined ? null : existing[0], existing[1] === undefined ? null : existing[1]];
                        const idx = key === 'range_min' ? 0 : 1;
                        range[idx] = value !== '' ? Number(value) : null;
                        if (!result.sequence.range) {
                            result.sequence.range = range;
                        } else {
                            result.sequence.range[idx] = range[idx];
                        }
                    } else if (value !== '') {
                        if (key === 'reset') {
                            result.sequence.reset = value;
                        } else {
                            result.sequence[key] = Number(value);
                        }
                    }
                    return;
                }
            if (path.startsWith('format.')) {
                if (!result.format) {
                    result.format = {};
                }
                const key = path.split('.')[1];
                if (value !== '') {
                    result.format[key] = key === 'width' ? Number(value) : value;
                }
                return;
            }
            if (path.startsWith('allocation.')) {
                if (!result.allocation) {
                    result.allocation = {};
                }
                const key = path.split('.')[1];
                if (value !== '') {
                    result.allocation[key] = value;
                }
            }
        });

        if (Object.keys(ifConditions).length === 0) {
            return null;
        }

        result.if = ifConditions;

        if (result.sequence && result.sequence.range) {
            const range = result.sequence.range;
            if (range[0] === null && range[1] === null) {
                delete result.sequence.range;
            } else {
                result.sequence.range = range.map(function (val) {
                    return val === null ? null : Number(val);
                });
            }
        }

        return result;
    }

    function collectStateFromForm() {
        const next = cloneDefaults();
        designer.querySelectorAll(fieldSelectors).forEach(function (el) {
            const key = el.getAttribute('data-designer-field');
            const value = el.value;
            switch (key) {
                case 'mode':
                    next.mode = value || defaults.mode;
                    break;
                case 'scope':
                    next.scope = value || defaults.scope;
                    break;
                case 'sequence.start':
                    next.sequence.start = value === '' ? defaults.sequence.start : Number(value);
                    break;
                case 'sequence.step':
                    next.sequence.step = value === '' ? defaults.sequence.step : Math.max(1, Number(value));
                    break;
                case 'sequence.reset':
                    next.sequence.reset = value || defaults.sequence.reset;
                    break;
                case 'sequence.range_min':
                case 'sequence.range_max':
                    if (!Array.isArray(next.sequence.range) || next.sequence.range === null) {
                        next.sequence.range = [null, null];
                    }
                    const idx = key.endsWith('min') ? 0 : 1;
                    next.sequence.range[idx] = value === '' ? null : Number(value);
                    break;
                case 'format.prefix':
                    next.format.prefix = value;
                    break;
                case 'format.width':
                    next.format.width = value === '' ? defaults.format.width : Number(value);
                    break;
                case 'format.suffix':
                    next.format.suffix = value;
                    break;
                case 'format.separator':
                    next.format.separator = value;
                    break;
                case 'allocation.entity':
                    next.allocation.entity = value || defaults.allocation.entity;
                    break;
                case 'allocation.time':
                    next.allocation.time = value || defaults.allocation.time;
                    break;
                case 'allocation.reuse':
                    next.allocation.reuse = value || defaults.allocation.reuse;
                    break;
                case 'allocation.lock_after':
                    next.allocation.lock_after = value || defaults.allocation.lock_after;
                    break;
                case 'constraints.unique_per':
                    next.constraints.unique_per = value || defaults.constraints.unique_per;
                    break;
                case 'constraints.blocklists':
                    next.constraints.blocklists = value === '' ? [] : value.split(',').map(function (item) {
                        return item.trim();
                    }).filter(Boolean);
                    break;
                case 'constraints.club_spacing':
                    next.constraints.club_spacing = value === '' ? defaults.constraints.club_spacing : Number(value);
                    break;
                case 'constraints.horse_cooldown_min':
                    next.constraints.horse_cooldown_min = value === '' ? defaults.constraints.horse_cooldown_min : Number(value);
                    break;
            }
        });

        const overrides = [];
        designer.querySelectorAll('.rule-override').forEach(function (wrapper) {
            const parsed = readOverride(wrapper);
            if (parsed) {
                if (parsed.sequence) {
                    if (parsed.sequence.range) {
                        const [min, max] = parsed.sequence.range;
                        if (min === null && max === null) {
                            delete parsed.sequence.range;
                        } else {
                            parsed.sequence.range = parsed.sequence.range.map(function (val) {
                                return val === null ? null : Number(val);
                            });
                        }
                    }
                    if (Object.keys(parsed.sequence).length === 0) {
                        delete parsed.sequence;
                    }
                }
                if (parsed.format && Object.keys(parsed.format).length === 0) {
                    delete parsed.format;
                }
                if (parsed.allocation && Object.keys(parsed.allocation).length === 0) {
                    delete parsed.allocation;
                }
                overrides.push(parsed);
            }
        });
        next.overrides = overrides;

        if (!next.sequence.range || (next.sequence.range[0] === null && next.sequence.range[1] === null)) {
            next.sequence.range = null;
        }

        state = next;
        syncTextarea();
    }

    function syncTextarea() {
        textarea.value = JSON.stringify(state, null, 2);
    }

    designer.addEventListener('input', function (event) {
        const target = event.target;
        if (target.matches(fieldSelectors) || target.hasAttribute('data-override-field')) {
            collectStateFromForm();
        }
    });

    designer.addEventListener('change', function (event) {
        const target = event.target;
        if (target.matches('select[data-override-field="sequence.reset"]')) {
            collectStateFromForm();
        }
    });

    designer.addEventListener('click', function (event) {
        const target = event.target;
        if (target.dataset.action === 'add-override') {
            event.preventDefault();
            const overrides = state.overrides ? state.overrides.slice() : [];
            overrides.push({ if: { class_tag: '' } });
            state.overrides = overrides;
            renderOverrides(overrides);
            collectStateFromForm();
        }
        if (target.dataset.action === 'remove-override') {
            event.preventDefault();
            const wrapper = target.closest('.rule-override');
            if (!wrapper) {
                return;
            }
            const index = Array.prototype.indexOf.call(overrideContainer.querySelectorAll('.rule-override'), wrapper);
            if (index >= 0) {
                state.overrides.splice(index, 1);
                renderOverrides(state.overrides);
                collectStateFromForm();
            }
        }
        if (target.dataset.action === 'load-json') {
            event.preventDefault();
            const parsed = parseJsonSafe(textarea.value, null);
            if (parsed) {
                state = eventsMergeWithDefaults(parsed);
                applyStateToForm();
            } else {
                alert('JSON konnte nicht gelesen werden.');
            }
        }
        if (target.dataset.action === 'reset-defaults') {
            event.preventDefault();
            state = cloneDefaults();
            applyStateToForm();
        }
        if (target.dataset.action === 'load-preset') {
            event.preventDefault();
            const presetKey = target.getAttribute('data-preset');
            if (presetKey && presets[presetKey]) {
                state = eventsMergeWithDefaults(presets[presetKey]);
                applyStateToForm();
            }
        }
    });

    function eventsMergeWithDefaults(custom) {
        const base = cloneDefaults();
        return deepMerge(base, custom || {});
    }

    function deepMerge(target, source) {
        if (!source || typeof source !== 'object') {
            return target;
        }
        Object.keys(source).forEach(function (key) {
            const value = source[key];
            if (Array.isArray(value)) {
                target[key] = Array.isArray(value) ? value.slice() : value;
            } else if (value && typeof value === 'object') {
                if (!target[key] || typeof target[key] !== 'object') {
                    target[key] = {};
                }
                deepMerge(target[key], value);
            } else {
                target[key] = value;
            }
        });
        return target;
    }

    applyStateToForm();
});
</script>
