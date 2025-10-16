(function () {
    'use strict';

    function parseJsonSafe(value, fallback) {
        if (!value) {
            return fallback;
        }
        try {
            return JSON.parse(value);
        } catch (error) {
            console.warn('start-number-designer: JSON parse failed', error);
            return fallback;
        }
    }

    function cloneDefaults(defaults) {
        return JSON.parse(JSON.stringify(defaults));
    }

    function deepMerge(target, source) {
        if (!source || typeof source !== 'object') {
            return target;
        }
        Object.keys(source).forEach(function (key) {
            var value = source[key];
            if (Array.isArray(value)) {
                target[key] = value.slice();
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

    function escapeHtml(value) {
        var safe = value === undefined || value === null ? '' : value;
        return String(safe).replace(/["&'<>]/g, function (char) {
            return ({'"': '&quot;', '&': '&amp;', "'": '&#39;', '<': '&lt;', '>': '&gt;'}[char]);
        });
    }

    function escapeValue(value) {
        if (value === undefined || value === null || value === '') {
            return '';
        }
        return String(value);
    }

    function buildOptions(values, selected, customLabels) {
        var baseLabels = {
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
        var labels = customLabels ? Object.assign({}, baseLabels, customLabels) : baseLabels;
        return values.map(function (value) {
            var label = Object.prototype.hasOwnProperty.call(labels, value) ? labels[value] : value;
            var isSelected = value === selected ? ' selected' : '';
            return '<option value="' + value + '"' + isSelected + '>' + label + '</option>';
        }).join('');
    }

    function initDesigner(container) {
        var targetSelector = container.getAttribute('data-target');
        var textarea = targetSelector ? document.querySelector(targetSelector) : container.querySelector('textarea');
        if (!textarea) {
            return;
        }

        var defaults = parseJsonSafe(container.dataset.default, {});
        var overrideContainer = container.querySelector('[data-override-list]');
        var fieldSelectors = '[data-designer-field]';
        var presetData = parseJsonSafe(container.dataset.presets, {});
        var presets = Object.assign({
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
                overrides: []
            },
            western: {
                mode: 'western',
                scope: 'class',
                sequence: {
                    start: 100,
                    step: 5,
                    range: [100, 499],
                    reset: 'per_class'
                },
                format: {
                    prefix: 'W',
                    width: 2,
                    suffix: '',
                    separator: ''
                },
                allocation: {
                    entity: 'pair',
                    time: 'on_startlist',
                    reuse: 'after_scratch',
                    lock_after: 'sign_off'
                },
                constraints: {
                    unique_per: 'class',
                    blocklists: [],
                    club_spacing: 0,
                    horse_cooldown_min: 30
                },
                overrides: []
            }
        }, presetData || {});
        var eventRule = container.hasAttribute('data-event-rule') ? parseJsonSafe(container.dataset.eventRule, null) : null;
        var state = mergeWithDefaults(parseJsonSafe(container.dataset.rule, defaults));

        function mergeWithDefaults(custom) {
            return deepMerge(cloneDefaults(defaults), custom || {});
        }

        function getPath(object, path, fallback) {
            if (!object) {
                return fallback;
            }
            var parts = path.split('.');
            var cursor = object;
            for (var i = 0; i < parts.length; i += 1) {
                if (cursor && Object.prototype.hasOwnProperty.call(cursor, parts[i])) {
                    cursor = cursor[parts[i]];
                } else {
                    return fallback;
                }
            }
            return cursor === undefined ? fallback : cursor;
        }

        function renderOverrides(overrides) {
            if (!overrideContainer) {
                return;
            }
            overrideContainer.innerHTML = '';
            overrides.forEach(function (item, index) {
                var wrapper = document.createElement('div');
                wrapper.className = 'rule-override border rounded p-3';
                wrapper.dataset.index = String(index);
                wrapper.innerHTML = createOverrideTemplate(item, index);
                overrideContainer.appendChild(wrapper);
            });
            if (overrides.length === 0) {
                var empty = document.createElement('div');
                empty.className = 'text-muted small';
                empty.textContent = 'Keine Overrides definiert.';
                overrideContainer.appendChild(empty);
            }
        }

        function getRange(sequence, index) {
            if (!sequence || !Array.isArray(sequence.range)) {
                return '';
            }
            var value = sequence.range[index];
            return value === undefined || value === null ? '' : value;
        }

        function createOverrideTemplate(item, index) {
            var conditions = item && item.if ? item.if : {};
            var sequence = item && item.sequence ? item.sequence : {};
            var format = item && item.format ? item.format : {};
            var allocation = item && item.allocation ? item.allocation : {};
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
                '<div class="col-sm-4"><label class="form-label">Reset</label><select class="form-select" data-override-field="sequence.reset">' + buildOptions(['never', 'per_class', 'per_day'], sequence.reset || '') + '</select></div>' +
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

        function applyStateToForm() {
            var current = state || mergeWithDefaults({});
            container.querySelectorAll(fieldSelectors).forEach(function (element) {
                var key = element.getAttribute('data-designer-field');
                switch (key) {
                    case 'mode':
                        element.value = current.mode || 'classic';
                        break;
                    case 'scope':
                        element.value = current.scope || 'tournament';
                        break;
                    case 'sequence.start':
                        element.value = getPath(current, 'sequence.start', '');
                        break;
                    case 'sequence.step':
                        element.value = getPath(current, 'sequence.step', '');
                        break;
                    case 'sequence.reset':
                        element.value = getPath(current, 'sequence.reset', 'never');
                        break;
                    case 'sequence.range_min':
                        var rangeMin = getPath(current, 'sequence.range', []);
                        element.value = Array.isArray(rangeMin) && rangeMin.length ? (rangeMin[0] === null ? '' : rangeMin[0]) : '';
                        break;
                    case 'sequence.range_max':
                        var rangeMax = getPath(current, 'sequence.range', []);
                        element.value = Array.isArray(rangeMax) && rangeMax.length > 1 ? (rangeMax[1] === null ? '' : rangeMax[1]) : '';
                        break;
                    case 'format.prefix':
                        element.value = getPath(current, 'format.prefix', '');
                        break;
                    case 'format.width':
                        element.value = getPath(current, 'format.width', '');
                        break;
                    case 'format.suffix':
                        element.value = getPath(current, 'format.suffix', '');
                        break;
                    case 'format.separator':
                        element.value = getPath(current, 'format.separator', '');
                        break;
                    case 'allocation.entity':
                        element.value = getPath(current, 'allocation.entity', 'start');
                        break;
                    case 'allocation.time':
                        element.value = getPath(current, 'allocation.time', 'on_startlist');
                        break;
                    case 'allocation.reuse':
                        element.value = getPath(current, 'allocation.reuse', 'never');
                        break;
                    case 'allocation.lock_after':
                        element.value = getPath(current, 'allocation.lock_after', 'sign_off');
                        break;
                    case 'constraints.unique_per':
                        element.value = getPath(current, 'constraints.unique_per', 'tournament');
                        break;
                    case 'constraints.blocklists':
                        element.value = (getPath(current, 'constraints.blocklists', []) || []).join(', ');
                        break;
                    case 'constraints.club_spacing':
                        element.value = getPath(current, 'constraints.club_spacing', '');
                        break;
                    case 'constraints.horse_cooldown_min':
                        element.value = getPath(current, 'constraints.horse_cooldown_min', '');
                        break;
                }
            });
            renderOverrides(current.overrides || []);
            syncTextarea();
        }

        function readOverride(wrapper) {
            var result = {};
            var ifConditions = {};
            wrapper.querySelectorAll('[data-override-field]').forEach(function (field) {
                var key = field.getAttribute('data-override-field');
                var value = field.value;
                if (key.indexOf('if.') === 0) {
                    var conditionKey = key.slice(3);
                    if (value !== '') {
                        ifConditions[conditionKey] = value;
                    }
                    return;
                }
                if (value === '') {
                    return;
                }
                var parts = key.split('.');
                var cursor = result;
                for (var i = 0; i < parts.length - 1; i += 1) {
                    if (!cursor[parts[i]] || typeof cursor[parts[i]] !== 'object') {
                        cursor[parts[i]] = {};
                    }
                    cursor = cursor[parts[i]];
                }
                cursor[parts[parts.length - 1]] = value;
            });
            if (Object.keys(ifConditions).length > 0) {
                result.if = ifConditions;
            }
            return Object.keys(result).length > 0 ? result : null;
        }

        function collectStateFromForm() {
            var next = mergeWithDefaults({});
            container.querySelectorAll(fieldSelectors).forEach(function (field) {
                var key = field.getAttribute('data-designer-field');
                var value = field.value;
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
                        next.sequence.step = value === '' ? defaults.sequence.step : Number(value);
                        break;
                    case 'sequence.reset':
                        next.sequence.reset = value || defaults.sequence.reset;
                        break;
                    case 'sequence.range_min':
                    case 'sequence.range_max':
                        if (!Array.isArray(next.sequence.range) || next.sequence.range === null) {
                            next.sequence.range = [null, null];
                        }
                        var idx = key.endsWith('min') ? 0 : 1;
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

            var overrides = [];
            if (overrideContainer) {
                overrideContainer.querySelectorAll('.rule-override').forEach(function (wrapper) {
                    var parsed = readOverride(wrapper);
                    if (parsed) {
                        if (parsed.sequence) {
                            if (parsed.sequence.range) {
                                var min = parsed.sequence.range[0];
                                var max = parsed.sequence.range[1];
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
            }
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

        container.addEventListener('input', function (event) {
            var target = event.target;
            if (target.matches(fieldSelectors) || target.hasAttribute('data-override-field')) {
                collectStateFromForm();
            }
        });

        container.addEventListener('change', function (event) {
            var target = event.target;
            if (target.matches('select[data-override-field="sequence.reset"]')) {
                collectStateFromForm();
            }
        });

        container.addEventListener('click', function (event) {
            var target = event.target;
            if (target.dataset.action === 'add-override') {
                event.preventDefault();
                var overrides = state.overrides ? state.overrides.slice() : [];
                overrides.push({ if: { class_tag: '' } });
                state.overrides = overrides;
                renderOverrides(overrides);
                collectStateFromForm();
            }
            if (target.dataset.action === 'remove-override') {
                event.preventDefault();
                var wrapper = target.closest('.rule-override');
                if (!wrapper || !overrideContainer) {
                    return;
                }
                var index = Array.prototype.indexOf.call(overrideContainer.querySelectorAll('.rule-override'), wrapper);
                if (index >= 0) {
                    state.overrides.splice(index, 1);
                    renderOverrides(state.overrides);
                    collectStateFromForm();
                }
            }
            if (target.dataset.action === 'load-json') {
                event.preventDefault();
                var parsed = parseJsonSafe(textarea.value, null);
                if (parsed) {
                    state = mergeWithDefaults(parsed);
                    applyStateToForm();
                } else {
                    alert('JSON konnte nicht gelesen werden.');
                }
            }
            if (target.dataset.action === 'reset-defaults') {
                event.preventDefault();
                state = mergeWithDefaults({});
                applyStateToForm();
            }
            if (target.dataset.action === 'load-preset') {
                event.preventDefault();
                var presetKey = target.getAttribute('data-preset');
                if (presetKey && presets[presetKey]) {
                    state = mergeWithDefaults(presets[presetKey]);
                    applyStateToForm();
                }
            }
            if (target.dataset.action === 'load-event-rule') {
                event.preventDefault();
                if (eventRule) {
                    state = mergeWithDefaults(eventRule);
                    applyStateToForm();
                } else {
                    alert('Keine Turnierregel verfügbar.');
                }
            }
        });

        applyStateToForm();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-start-number-designer]').forEach(function (element) {
            initDesigner(element);
        });
    });
})();
