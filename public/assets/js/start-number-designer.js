(function () {
    'use strict';

    function translate(key, params, fallback) {
        if (window.I18n && typeof window.I18n.t === 'function') {
            return window.I18n.t(key, params);
        }
        if (typeof fallback === 'function') {
            return fallback(params || {});
        }
        if (typeof fallback === 'string') {
            if (params && fallback.indexOf('{') !== -1) {
                return Object.keys(params).reduce(function (carry, paramKey) {
                    var pattern = new RegExp('\\{' + paramKey + '\\}', 'g');
                    return carry.replace(pattern, String(params[paramKey]));
                }, fallback);
            }
            return fallback;
        }
        return key;
    }

    var defaultOptionLabels = {
        '': { key: 'start_numbers.designer.labels.none_change', fallback: 'No change' },
        'never': { key: 'start_numbers.designer.options.reset.never', fallback: 'Never' },
        'per_class': { key: 'start_numbers.designer.options.reset.per_class', fallback: 'Per class' },
        'per_day': { key: 'start_numbers.designer.options.reset.per_day', fallback: 'Per day' },
        'start': { key: 'start_numbers.designer.options.entity.start', fallback: 'Start' },
        'pair': { key: 'start_numbers.designer.options.entity.pair', fallback: 'Rider/horse' },
        'department': { key: 'start_numbers.designer.options.entity.department', fallback: 'Squad / section' },
        'on_entry': { key: 'start_numbers.designer.options.time.on_entry', fallback: 'On entry' },
        'on_startlist': { key: 'start_numbers.designer.options.time.on_startlist', fallback: 'Start list' },
        'on_gate': { key: 'start_numbers.designer.options.time.on_gate', fallback: 'At the gate' },
        'after_scratch': { key: 'start_numbers.designer.options.reuse.after_scratch', fallback: 'After scratch' },
        'session': { key: 'start_numbers.designer.options.reuse.session', fallback: 'Session' },
        'sign_off': { key: 'start_numbers.designer.options.lock_after.sign_off', fallback: 'Sign-off' },
        'start_called': { key: 'start_numbers.designer.options.lock_after.start_called', fallback: 'Call-up' }
    };

    function resolveOptionLabel(value, overrides) {
        if (overrides && Object.prototype.hasOwnProperty.call(overrides, value)) {
            var override = overrides[value];
            if (typeof override === 'string') {
                return override;
            }
            if (override && typeof override === 'object') {
                return translate(override.key, override.params || null, override.fallback);
            }
        }
        if (Object.prototype.hasOwnProperty.call(defaultOptionLabels, value)) {
            var meta = defaultOptionLabels[value];
            return translate(meta.key, meta.params || null, meta.fallback);
        }
        return String(value);
    }

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
        return values.map(function (value) {
            var label = resolveOptionLabel(value, customLabels);
            var isSelected = value === selected ? ' selected' : '';
            return '<option value="' + value + '"' + isSelected + '>' + escapeHtml(label) + '</option>';
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
        var presets = (presetData && typeof presetData === 'object') ? presetData : {};
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
                empty.textContent = translate('start_numbers.designer.overrides.empty', null, 'No overrides defined.');
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
            var heading = translate('start_numbers.designer.overrides.heading', { index: index + 1 }, 'Override #{index}');
            var removeLabel = translate('start_numbers.designer.overrides.remove', null, 'Remove');
            var classTagLabel = translate('start_numbers.designer.overrides.fields.class_tag', null, 'Class tag');
            var divisionLabel = translate('start_numbers.designer.overrides.fields.division', null, 'Division');
            var arenaLabel = translate('start_numbers.designer.overrides.fields.arena', null, 'Arena');
            var dateLabel = translate('start_numbers.designer.overrides.fields.date', null, 'Date');
            var sequenceTitle = translate('start_numbers.designer.sections.sequence.title', null, 'Sequence');
            var sequenceStartLabel = translate('start_numbers.designer.sections.sequence.start', null, 'Start');
            var sequenceStepLabel = translate('start_numbers.designer.sections.sequence.step', null, 'Step size');
            var sequenceResetLabel = translate('start_numbers.designer.sections.sequence.reset', null, 'Reset');
            var rangeFromLabel = translate('start_numbers.designer.sections.sequence.range_from', null, 'Range from');
            var rangeToLabel = translate('start_numbers.designer.sections.sequence.range_to', null, 'Range to');
            var formatTitle = translate('start_numbers.designer.sections.format.title', null, 'Format');
            var formatPrefixLabel = translate('start_numbers.designer.sections.format.prefix', null, 'Prefix');
            var formatWidthLabel = translate('start_numbers.designer.sections.format.width', null, 'Width');
            var formatSuffixLabel = translate('start_numbers.designer.sections.format.suffix', null, 'Suffix');
            var formatSeparatorLabel = translate('start_numbers.designer.sections.format.separator', null, 'Separator');
            var allocationTitle = translate('start_numbers.designer.sections.allocation.title', null, 'Allocation');
            var allocationEntityLabel = translate('start_numbers.designer.sections.allocation.entity', null, 'Entity');
            var allocationTimeLabel = translate('start_numbers.designer.sections.allocation.time', null, 'Timing');
            var allocationReuseLabel = translate('start_numbers.designer.sections.allocation.reuse', null, 'Reuse');
            var allocationLockAfterLabel = translate('start_numbers.designer.sections.allocation.lock_after', null, 'Lock after');
            return [
                '<div class="d-flex justify-content-between align-items-start mb-3">',
                '<h5 class="h6 mb-0">' + escapeHtml(heading) + '</h5>',
                '<button type="button" class="btn btn-sm btn-outline-danger" data-action="remove-override">' + escapeHtml(removeLabel) + '</button>',
                '</div>',
                '<div class="row g-3 mb-3">',
                '<div class="col-sm-6"><label class="form-label">' + escapeHtml(classTagLabel) + '</label><input type="text" class="form-control" data-override-field="if.class_tag" value="' + escapeHtml(conditions.class_tag || '') + '"></div>',
                '<div class="col-sm-6"><label class="form-label">' + escapeHtml(divisionLabel) + '</label><input type="text" class="form-control" data-override-field="if.division" value="' + escapeHtml(conditions.division || '') + '"></div>',
                '<div class="col-sm-6"><label class="form-label">' + escapeHtml(arenaLabel) + '</label><input type="text" class="form-control" data-override-field="if.arena" value="' + escapeHtml(conditions.arena || '') + '"></div>',
                '<div class="col-sm-6"><label class="form-label">' + escapeHtml(dateLabel) + '</label><input type="date" class="form-control" data-override-field="if.date" value="' + escapeHtml(conditions.date || '') + '"></div>',
                '</div>',
                '<h6 class="h6">' + escapeHtml(sequenceTitle) + '</h6>',
                '<div class="row g-3 mb-3">',
                '<div class="col-sm-4"><label class="form-label">' + escapeHtml(sequenceStartLabel) + '</label><input type="number" class="form-control" data-override-field="sequence.start" value="' + escapeValue(sequence.start) + '"></div>',
                '<div class="col-sm-4"><label class="form-label">' + escapeHtml(sequenceStepLabel) + '</label><input type="number" class="form-control" data-override-field="sequence.step" value="' + escapeValue(sequence.step) + '"></div>',
                '<div class="col-sm-4"><label class="form-label">' + escapeHtml(sequenceResetLabel) + '</label><select class="form-select" data-override-field="sequence.reset">' + buildOptions(['never', 'per_class', 'per_day'], sequence.reset || '') + '</select></div>',
                '<div class="col-sm-6"><label class="form-label">' + escapeHtml(rangeFromLabel) + '</label><input type="number" class="form-control" data-override-field="sequence.range_min" value="' + escapeValue(getRange(sequence, 0)) + '"></div>',
                '<div class="col-sm-6"><label class="form-label">' + escapeHtml(rangeToLabel) + '</label><input type="number" class="form-control" data-override-field="sequence.range_max" value="' + escapeValue(getRange(sequence, 1)) + '"></div>',
                '</div>',
                '<h6 class="h6">' + escapeHtml(formatTitle) + '</h6>',
                '<div class="row g-3">',
                '<div class="col-sm-3"><label class="form-label">' + escapeHtml(formatPrefixLabel) + '</label><input type="text" class="form-control" data-override-field="format.prefix" value="' + escapeHtml(format.prefix || '') + '"></div>',
                '<div class="col-sm-3"><label class="form-label">' + escapeHtml(formatWidthLabel) + '</label><input type="number" class="form-control" data-override-field="format.width" value="' + escapeValue(format.width) + '"></div>',
                '<div class="col-sm-3"><label class="form-label">' + escapeHtml(formatSuffixLabel) + '</label><input type="text" class="form-control" data-override-field="format.suffix" value="' + escapeHtml(format.suffix || '') + '"></div>',
                '<div class="col-sm-3"><label class="form-label">' + escapeHtml(formatSeparatorLabel) + '</label><input type="text" class="form-control" data-override-field="format.separator" value="' + escapeHtml(format.separator || '') + '"></div>',
                '</div>',
                '<h6 class="h6 mt-3">' + escapeHtml(allocationTitle) + '</h6>',
                '<div class="row g-3">',
                '<div class="col-sm-3"><label class="form-label">' + escapeHtml(allocationEntityLabel) + '</label><select class="form-select" data-override-field="allocation.entity">' + buildOptions(['', 'start', 'pair', 'department'], allocation.entity || '') + '</select></div>',
                '<div class="col-sm-3"><label class="form-label">' + escapeHtml(allocationTimeLabel) + '</label><select class="form-select" data-override-field="allocation.time">' + buildOptions(['', 'on_entry', 'on_startlist', 'on_gate'], allocation.time || '') + '</select></div>',
                '<div class="col-sm-3"><label class="form-label">' + escapeHtml(allocationReuseLabel) + '</label><select class="form-select" data-override-field="allocation.reuse">' + buildOptions(['', 'never', 'after_scratch', 'session'], allocation.reuse || '') + '</select></div>',
                '<div class="col-sm-3"><label class="form-label">' + escapeHtml(allocationLockAfterLabel) + '</label><select class="form-select" data-override-field="allocation.lock_after">' + buildOptions(['', 'sign_off', 'start_called', 'never'], allocation.lock_after || '') + '</select></div>',
                '</div>'
            ].join('');
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
            if (target && target.hasAttribute('data-preset-select')) {
                var presetKey = target.value;
                if (presetKey && presets[presetKey]) {
                    state = mergeWithDefaults(presets[presetKey]);
                    applyStateToForm();
                }
                target.value = '';
                return;
            }
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
                    alert(translate('start_numbers.designer.messages.json_error', null, 'Could not read JSON.'));
                }
            }
            if (target.dataset.action === 'reset-defaults') {
                event.preventDefault();
                state = mergeWithDefaults({});
                applyStateToForm();
            }
            if (target.dataset.action === 'load-event-rule') {
                event.preventDefault();
                if (eventRule) {
                    state = mergeWithDefaults(eventRule);
                    applyStateToForm();
                } else {
                    alert(translate('start_numbers.designer.messages.no_event_rule', null, 'No event rule available.'));
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
