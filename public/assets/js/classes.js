(function (window) {
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

    function escapeHtml(value) {
        var safe = value === undefined || value === null ? '' : value;
        return String(safe).replace(/["&'<>]/g, function (char) {
            return ({ '"': '&quot;', '&': '&amp;', "'": '&#39;', '<': '&lt;', '>': '&gt;' })[char];
        });
    }

    function deepClone(value) {
        if (value === null || typeof value !== 'object') {
            return value;
        }
        if (Array.isArray(value)) {
            return value.map(function (item) {
                return deepClone(item);
            });
        }
        var clone = {};
        Object.keys(value).forEach(function (key) {
            clone[key] = deepClone(value[key]);
        });
        return clone;
    }

    function mergeObjects(target, source) {
        if (!source || typeof source !== 'object') {
            return target;
        }
        Object.keys(source).forEach(function (key) {
            var value = source[key];
            if (Array.isArray(value)) {
                target[key] = value.map(function (item) {
                    return deepClone(item);
                });
            } else if (value && typeof value === 'object') {
                if (!target[key] || typeof target[key] !== 'object') {
                    target[key] = {};
                }
                mergeObjects(target[key], value);
            } else {
                target[key] = value;
            }
        });
        return target;
    }

    function toNumberOrNull(value) {
        var number = parseFloat(value);
        if (!isNaN(number) && isFinite(number)) {
            return number;
        }
        return null;
    }

    function safeParseJson(raw, fallback) {
        if (!raw || typeof raw !== 'string') {
            return fallback;
        }
        try {
            return JSON.parse(raw);
        } catch (error) {
            return fallback;
        }
    }

    function createElement(html) {
        var template = window.document.createElement('template');
        template.innerHTML = html.trim();
        return template.content.firstElementChild;
    }

    function ArenaPicker(form, data) {
        this.form = form;
        this.data = data && typeof data === 'object' ? data : {};
        if (!this.data.events) {
            this.data.events = {};
        }
        this.eventSelect = form.querySelector('select[name="event_id"]');
        this.select = form.querySelector('[data-arena-select]');
        this.summary = form.querySelector('[data-arena-summary]');
        this.quickToggle = form.querySelector('[data-arena-quick-toggle]');
        this.quickForm = form.querySelector('[data-arena-quick-form]');
        this.quickInputs = this.quickForm ? this.quickForm.querySelectorAll('input, select, textarea') : null;
        this.emptyText = this.summary ? this.summary.getAttribute('data-empty') || '' : '';
        this.currentEventId = null;
    }

    ArenaPicker.prototype.getEventId = function () {
        if (!this.eventSelect) {
            return '';
        }
        return String(this.eventSelect.value || '');
    };

    ArenaPicker.prototype.getOptionsForEvent = function (eventId) {
        var key = String(eventId || '');
        return (this.data.events && this.data.events[key]) ? this.data.events[key] : [];
    };

    ArenaPicker.prototype.getSelectedOption = function () {
        if (!this.select) {
            return null;
        }
        var value = String(this.select.value || '');
        if (!value) {
            return null;
        }
        var options = this.getOptionsForEvent(this.getEventId());
        for (var i = 0; i < options.length; i += 1) {
            if (String(options[i].id) === value) {
                return options[i];
            }
        }
        return null;
    };

    ArenaPicker.prototype.resetQuickForm = function () {
        if (!this.quickInputs) {
            return;
        }
        Array.prototype.forEach.call(this.quickInputs, function (element) {
            if (element.tagName === 'SELECT') {
                if (element.name === 'arena_quick_type') {
                    element.value = 'outdoor';
                } else if (element.options.length > 0) {
                    element.selectedIndex = 0;
                }
            } else {
                element.value = '';
            }
        });
    };

    ArenaPicker.prototype.renderOptions = function () {
        if (!this.select) {
            return;
        }
        var placeholderLabel = '';
        var existingPlaceholder = this.select.querySelector('option[value=""]');
        if (existingPlaceholder) {
            placeholderLabel = existingPlaceholder.textContent;
        }

        while (this.select.firstChild) {
            this.select.removeChild(this.select.firstChild);
        }

        var placeholder = window.document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = placeholderLabel || translate('classes.form.arena_picker.placeholder', null, 'Select arena…');
        this.select.appendChild(placeholder);

        var selectedValue = this.select.getAttribute('data-selected') || '';
        var options = this.getOptionsForEvent(this.getEventId());
        var hasSelection = false;

        for (var i = 0; i < options.length; i += 1) {
            var option = options[i];
            var node = window.document.createElement('option');
            node.value = String(option.id);
            node.textContent = option.label;
            if (selectedValue && String(option.id) === String(selectedValue)) {
                node.selected = true;
                hasSelection = true;
            }
            this.select.appendChild(node);
        }

        if (!hasSelection) {
            this.select.value = '';
        }

        this.select.removeAttribute('data-selected');
        this.updateSummary();
    };

    ArenaPicker.prototype.updateSummary = function () {
        if (!this.summary) {
            return;
        }
        var option = this.getSelectedOption();
        if (!option) {
            this.summary.textContent = this.emptyText || '';
            return;
        }
        var lines = [];
        if (option.summary) {
            lines.push(option.summary);
        }
        if (option.location) {
            lines.push(translate('classes.form.arena_picker.location_summary', { location: option.location }, 'Location: {location}'));
        }
        if (option.remarks) {
            lines.push(translate('classes.form.arena_picker.remarks_summary', { remarks: option.remarks }, 'Notes: {remarks}'));
        }
        this.summary.textContent = lines.length ? lines.join(' · ') : (this.emptyText || '');
    };

    ArenaPicker.prototype.toggleQuickForm = function () {
        if (!this.quickForm) {
            return;
        }
        var isHidden = this.quickForm.classList.contains('d-none');
        if (isHidden) {
            this.quickForm.classList.remove('d-none');
            var focusable = this.quickForm.querySelector('input, select, textarea');
            if (focusable) {
                focusable.focus();
            }
        } else {
            this.quickForm.classList.add('d-none');
            this.resetQuickForm();
        }
    };

    ArenaPicker.prototype.handleEventChange = function () {
        var eventId = this.getEventId();
        this.renderOptions();
        if (!eventId) {
            if (this.quickToggle) {
                this.quickToggle.disabled = true;
            }
            if (this.quickForm && !this.quickForm.classList.contains('d-none')) {
                this.quickForm.classList.add('d-none');
            }
            this.resetQuickForm();
        } else if (this.quickToggle) {
            this.quickToggle.disabled = false;
        }
    };

    ArenaPicker.prototype.init = function () {
        if (!this.select) {
            return;
        }

        this.renderOptions();
        if (this.eventSelect) {
            this.currentEventId = this.getEventId();
            this.eventSelect.addEventListener('change', this.handleEventChange.bind(this));
        }

        this.select.addEventListener('change', this.updateSummary.bind(this));

        if (this.quickToggle) {
            this.quickToggle.addEventListener('click', this.toggleQuickForm.bind(this));
            if (!this.getEventId()) {
                this.quickToggle.disabled = true;
            }
        }
    };

    function RuleEditor(form, presets) {
        this.form = form;
        this.editor = form.querySelector('[data-rule-editor]');
        this.textarea = this.editor ? this.editor.querySelector('[data-rule-json]') : null;
        this.builder = this.editor ? this.editor.querySelector('[data-rule-builder]') : null;
        this.toggleButton = this.editor ? this.editor.querySelector('[data-rule-toggle]') : null;
        this.errorBox = this.editor ? this.editor.querySelector('[data-rule-error]') : null;
        this.presets = presets || {};
        this.supportedTypes = ['dressage', 'jumping', 'western'];
        this.state = null;
        this.manualMode = true;
        this.errorMessage = null;
    }

    RuleEditor.prototype.init = function () {
        if (!this.editor || !this.textarea) {
            return;
        }

        this.bindEvents();
        this.bootstrapState();
    };

    RuleEditor.prototype.bindEvents = function () {
        var self = this;

        if (this.toggleButton) {
            this.toggleButton.classList.remove('d-none');
            this.toggleButton.addEventListener('click', function (event) {
                event.preventDefault();
                self.toggleMode();
            });
        }

        this.editor.addEventListener('click', function (event) {
            var actionButton = event.target.closest('[data-action]');
            if (!actionButton || !self.builder || !self.builder.contains(actionButton)) {
                return;
            }

            var action = actionButton.getAttribute('data-action');
            event.preventDefault();

            if (action === 'add-movement') {
                self.addMovement();
                return;
            }
            if (action === 'remove-movement') {
                var movementRow = actionButton.closest('[data-index]');
                if (movementRow) {
                    var movementIndex = parseInt(movementRow.getAttribute('data-index'), 10);
                    self.removeMovement(movementIndex);
                }
                return;
            }
            if (action === 'add-maneuver') {
                self.addManeuver();
                return;
            }
            if (action === 'remove-maneuver') {
                var maneuverRow = actionButton.closest('[data-index]');
                if (maneuverRow) {
                    var maneuverIndex = parseInt(maneuverRow.getAttribute('data-index'), 10);
                    self.removeManeuver(maneuverIndex);
                }
                return;
            }
            if (action === 'add-penalty') {
                self.addPenalty();
                return;
            }
            if (action === 'remove-penalty') {
                var penaltyIndex = parseInt(actionButton.getAttribute('data-index'), 10);
                if (!isNaN(penaltyIndex)) {
                    self.removePenalty(penaltyIndex);
                }
            }
        });

        this.editor.addEventListener('change', function (event) {
            var select = event.target.closest('[data-preset-select]');
            if (select && self.editor.contains(select)) {
                var value = select.value;
                if (value) {
                    self.applyPreset(value);
                }
                select.value = '';
            }
        });

        if (this.builder) {
            this.builder.addEventListener('change', function (event) {
                self.handleBuilderChange(event);
            });

            this.builder.addEventListener('input', function (event) {
                self.handleBuilderInput(event);
            });

            var penaltyInput = this.builder.querySelector('[data-western-penalty-input]');
            if (penaltyInput) {
                penaltyInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        self.addPenalty();
                    }
                });
            }
        }

        this.form.addEventListener('submit', function () {
            if (!self.manualMode && self.state) {
                self.syncTextarea();
            }
        });
    };

    RuleEditor.prototype.handleBuilderChange = function (event) {
        if (!this.state || !this.builder || !this.builder.contains(event.target)) {
            return;
        }

        var target = event.target;

        if (target.matches('[data-rule-type]')) {
            this.changeType(target.value);
            return;
        }

        if (target.matches('[data-dressage-aggregate]')) {
            this.state.aggregate = target.value || 'average';
            this.syncTextarea();
            return;
        }

        if (target.matches('[data-dressage-drop]')) {
            this.state.drop_high_low = target.checked;
            this.syncTextarea();
            return;
        }

        if (target.matches('[data-jumping-faults]')) {
            this.state.fault_points = !!target.checked;
            this.syncTextarea();
            return;
        }
    };

    RuleEditor.prototype.handleBuilderInput = function (event) {
        if (!this.state || !this.builder || !this.builder.contains(event.target)) {
            return;
        }

        var target = event.target;

        if (target.closest('[data-dressage-movements]') && target.hasAttribute('data-field')) {
            var movementRow = target.closest('[data-index]');
            if (!movementRow) {
                return;
            }
            var index = parseInt(movementRow.getAttribute('data-index'), 10);
            if (isNaN(index) || !this.state.movements || !this.state.movements[index]) {
                return;
            }
            var field = target.getAttribute('data-field');
            if (field === 'max') {
                var maxValue = toNumberOrNull(target.value);
                this.state.movements[index].max = maxValue !== null ? maxValue : null;
            } else {
                this.state.movements[index].label = target.value;
            }
            this.syncTextarea();
            return;
        }

        if (target.matches('[data-dressage-step]')) {
            var stepValue = toNumberOrNull(target.value);
            this.state.step = stepValue !== null ? stepValue : null;
            this.syncTextarea();
            return;
        }

        if (target.matches('[data-jumping-time]')) {
            var allowed = toNumberOrNull(target.value);
            this.state.time_allowed = allowed !== null ? allowed : null;
            this.syncTextarea();
            return;
        }

        if (target.matches('[data-jumping-penalty]')) {
            var penalty = toNumberOrNull(target.value);
            this.state.time_penalty_per_second = penalty !== null ? penalty : null;
            this.syncTextarea();
            return;
        }

        if (target.closest('[data-western-maneuvers]')) {
            var maneuverRow = target.closest('[data-index]');
            if (!maneuverRow) {
                return;
            }
            var maneuverIndex = parseInt(maneuverRow.getAttribute('data-index'), 10);
            if (isNaN(maneuverIndex) || !this.state.maneuvers || !this.state.maneuvers[maneuverIndex]) {
                return;
            }

            if (target.getAttribute('data-field') === 'label') {
                this.state.maneuvers[maneuverIndex].label = target.value;
                this.syncTextarea();
                return;
            }

            if (target.hasAttribute('data-range')) {
                var range = this.state.maneuvers[maneuverIndex].range || [];
                var isMin = target.getAttribute('data-range') === 'min';
                var rangeValue = toNumberOrNull(target.value);
                if (isMin) {
                    range[0] = rangeValue !== null ? rangeValue : null;
                } else {
                    range[1] = rangeValue !== null ? rangeValue : null;
                }
                this.state.maneuvers[maneuverIndex].range = range;
                this.syncTextarea();
            }
            return;
        }
    };

    RuleEditor.prototype.bootstrapState = function () {
        var raw = (this.textarea.value || '').trim();
        if (raw === '') {
            this.state = this.defaultRule('dressage');
            this.manualMode = false;
            this.errorMessage = null;
            this.render();
            return;
        }

        try {
            var data = JSON.parse(raw);
            if (!data || typeof data !== 'object') {
                this.manualMode = true;
                this.state = null;
                this.setError(translate('classes.designer.errors.not_object', null, 'Rule JSON must be an object.'));
                this.render();
                return;
            }
            var type = data.type || 'dressage';
            if (this.supportedTypes.indexOf(type) === -1) {
                this.manualMode = true;
                this.state = null;
                this.setError(translate('classes.designer.errors.unsupported_type', { type: type }, 'Rule type "{type}" is not supported by the editor.'));
                this.render();
                return;
            }
            this.state = this.normalizeRule(data);
            this.manualMode = false;
            this.errorMessage = null;
        } catch (error) {
            this.manualMode = true;
            this.state = null;
            this.setError(translate('classes.designer.errors.json_parse', { message: error.message }, 'Could not read rule JSON: {message}'));
        }

        this.render();
    };

    RuleEditor.prototype.getFallbackForType = function (type) {
        if (type === 'jumping') {
            return {
                type: 'jumping',
                fault_points: true,
                time_allowed: 75,
                time_penalty_per_second: 1
            };
        }
        if (type === 'western') {
            var maneuverPrefix = translate('classes.designer.defaults.maneuver_prefix', null, 'Manoeuvre');
            var maneuverLabel = translate('classes.designer.defaults.maneuver_label', { prefix: maneuverPrefix, index: 1 }, '{prefix} {index}');
            return {
                type: 'western',
                maneuvers: [
                    { label: maneuverLabel, range: [-1.5, 1.5] }
                ],
                penalties: [1, 2, 5]
            };
        }
        var movementPrefix = translate('classes.designer.defaults.movement_prefix', null, 'Movement');
        var movementLabel = translate('classes.designer.defaults.movement_label', { prefix: movementPrefix, index: 1 }, '{prefix} {index}');
        return {
            type: 'dressage',
            movements: [
                { label: movementLabel, max: 10 }
            ],
            step: 0.5,
            aggregate: 'average',
            drop_high_low: false
        };
    };

    RuleEditor.prototype.defaultRule = function (type) {
        var base = deepClone(this.getFallbackForType(type));
        var preset = this.presets[type];
        if (preset && typeof preset === 'object') {
            var presetClone = deepClone(preset);
            if (!presetClone.type) {
                presetClone.type = type;
            }
            base = mergeObjects(base, presetClone);
        }
        return this.normalizeRule(base);
    };

    RuleEditor.prototype.normalizeRule = function (rule) {
        if (!rule || typeof rule !== 'object') {
            return this.defaultRule('dressage');
        }
        var clone = deepClone(rule);
        var type = clone.type || 'dressage';
        if (type === 'jumping') {
            clone = this.normalizeJumpingRule(clone);
        } else if (type === 'western') {
            clone = this.normalizeWesternRule(clone);
        } else {
            clone = this.normalizeDressageRule(clone);
            type = 'dressage';
        }
        clone.type = type;
        return clone;
    };

    RuleEditor.prototype.normalizeDressageRule = function (rule) {
        var movementPrefix = translate('classes.designer.defaults.movement_prefix', null, 'Movement');
        rule.movements = this.normalizeMovements(rule.movements, movementPrefix);
        var step = toNumberOrNull(rule.step);
        rule.step = step !== null ? step : 0.5;
        rule.aggregate = typeof rule.aggregate === 'string' && rule.aggregate !== '' ? rule.aggregate : 'average';
        rule.drop_high_low = !!rule.drop_high_low;
        return rule;
    };

    RuleEditor.prototype.normalizeJumpingRule = function (rule) {
        rule.fault_points = typeof rule.fault_points === 'undefined' ? true : !!rule.fault_points;
        var timeAllowed = toNumberOrNull(rule.time_allowed);
        rule.time_allowed = timeAllowed !== null ? timeAllowed : 75;
        var penalty = toNumberOrNull(rule.time_penalty_per_second);
        rule.time_penalty_per_second = penalty !== null ? penalty : 1;
        return rule;
    };

    RuleEditor.prototype.normalizeWesternRule = function (rule) {
        rule.maneuvers = this.normalizeManeuvers(rule.maneuvers);
        rule.penalties = this.normalizePenalties(rule.penalties);
        return rule;
    };

    RuleEditor.prototype.normalizeMovements = function (items, prefix) {
        var list = Array.isArray(items) ? items : [];
        var effectivePrefix = typeof prefix === 'string' && prefix.trim() !== ''
            ? prefix
            : translate('classes.designer.defaults.movement_prefix', null, 'Movement');
        var buildLabel = function (index) {
            return translate('classes.designer.defaults.movement_label', { prefix: effectivePrefix, index: index }, '{prefix} {index}');
        };
        if (!list.length) {
            list = [{ label: buildLabel(1), max: 10 }];
        }
        return list.map(function (item, index) {
            var movement = deepClone(item || {});
            var label = typeof movement.label === 'string' && movement.label.trim() !== ''
                ? movement.label
                : buildLabel(index + 1);
            movement.label = label;
            var maxValue = toNumberOrNull(movement.max);
            movement.max = maxValue !== null ? maxValue : 10;
            return movement;
        });
    };

    RuleEditor.prototype.normalizeManeuvers = function (items) {
        var list = Array.isArray(items) ? items : [];
        var prefix = translate('classes.designer.defaults.maneuver_prefix', null, 'Manoeuvre');
        var buildLabel = function (index) {
            return translate('classes.designer.defaults.maneuver_label', { prefix: prefix, index: index }, '{prefix} {index}');
        };
        if (!list.length) {
            list = [{ label: buildLabel(1), range: [-1.5, 1.5] }];
        }
        return list.map(function (item, index) {
            var maneuver = deepClone(item || {});
            var label = typeof maneuver.label === 'string' && maneuver.label.trim() !== ''
                ? maneuver.label
                : buildLabel(index + 1);
            maneuver.label = label;
            var range = Array.isArray(maneuver.range) ? maneuver.range.slice(0, 2) : [];
            var min = toNumberOrNull(range[0]);
            var max = toNumberOrNull(range[1]);
            maneuver.range = [min !== null ? min : -1.5, max !== null ? max : 1.5];
            return maneuver;
        });
    };

    RuleEditor.prototype.normalizePenalties = function (items) {
        var list = Array.isArray(items) ? items : [];
        return list.map(function (value) {
            var number = toNumberOrNull(value);
            return number !== null ? number : null;
        }).filter(function (value) {
            return value !== null && !isNaN(value);
        });
    };

    RuleEditor.prototype.render = function () {
        if (!this.editor) {
            return;
        }

        if (this.manualMode) {
            if (this.builder) {
                this.builder.classList.add('d-none');
            }
            this.textarea.classList.remove('d-none');
            if (this.toggleButton) {
                this.toggleButton.textContent = translate('classes.designer.ui.use_builder', null, 'Use UI editor');
            }
            if (this.errorMessage) {
                this.setError(this.errorMessage);
            }
            return;
        }

        this.clearError();
        if (this.builder) {
            this.builder.classList.remove('d-none');
        }
        this.textarea.classList.add('d-none');
        if (this.toggleButton) {
            this.toggleButton.textContent = translate('classes.designer.ui.edit_json', null, 'Edit JSON');
        }
        if (!this.state) {
            this.state = this.defaultRule('dressage');
        }
        this.renderBuilder();
    };

    RuleEditor.prototype.renderBuilder = function () {
        if (!this.builder || !this.state) {
            return;
        }
        var type = this.state.type || 'dressage';
        var typeSelect = this.builder.querySelector('[data-rule-type]');
        if (typeSelect) {
            this.setSelectValue(typeSelect, type);
        }
        var panels = this.builder.querySelectorAll('[data-rule-panel]');
        panels.forEach(function (panel) {
            panel.classList.add('d-none');
        });
        var currentPanel = this.builder.querySelector('[data-rule-panel="' + type + '"]');
        if (currentPanel) {
            currentPanel.classList.remove('d-none');
        }
        if (type === 'jumping') {
            this.renderJumping();
        } else if (type === 'western') {
            this.renderWestern();
        } else {
            this.renderDressage();
        }
        this.syncTextarea();
    };

    RuleEditor.prototype.renderDressage = function () {
        this.renderDressageMovements();
        var stepInput = this.builder.querySelector('[data-dressage-step]');
        if (stepInput) {
            stepInput.value = typeof this.state.step === 'number' ? this.state.step : '';
        }
        var aggregateSelect = this.builder.querySelector('[data-dressage-aggregate]');
        if (aggregateSelect) {
            this.setSelectValue(aggregateSelect, this.state.aggregate);
        }
        var dropCheckbox = this.builder.querySelector('[data-dressage-drop]');
        if (dropCheckbox) {
            dropCheckbox.checked = !!this.state.drop_high_low;
        }
    };

    RuleEditor.prototype.renderDressageMovements = function () {
        var container = this.builder.querySelector('[data-dressage-movements]');
        var emptyIndicator = this.builder.querySelector('[data-dressage-empty]');
        var labelPlaceholder = translate('classes.designer.placeholders.movement', null, 'e.g. extended trot');
        var removeMovementLabel = translate('classes.designer.actions.remove_movement', null, 'Remove movement');
        if (!container) {
            return;
        }
        container.innerHTML = '';
        if (!this.state.movements || !this.state.movements.length) {
            if (emptyIndicator) {
                emptyIndicator.classList.remove('d-none');
            }
            return;
        }
        if (emptyIndicator) {
            emptyIndicator.classList.add('d-none');
        }
        this.state.movements.forEach(function (movement, index) {
            var row = createElement('<div class="row g-2 align-items-center" data-index="' + index + '"></div>');
            var labelCol = createElement('<div class="col"></div>');
            var labelInput = createElement('<input type="text" class="form-control form-control-sm" placeholder="' + escapeHtml(labelPlaceholder) + '" data-field="label">');
            labelInput.value = movement.label || '';
            labelCol.appendChild(labelInput);

            var maxCol = createElement('<div class="col-auto" style="width: 120px;"></div>');
            var maxInput = createElement('<input type="number" class="form-control form-control-sm" min="0" step="0.1" data-field="max">');
            if (typeof movement.max === 'number') {
                maxInput.value = String(movement.max);
            }
            maxCol.appendChild(maxInput);

            var removeCol = createElement('<div class="col-auto"></div>');
            var removeButton = createElement('<button type="button" class="btn btn-sm btn-outline-danger" data-action="remove-movement" aria-label="' + escapeHtml(removeMovementLabel) + '">&times;</button>');
            removeCol.appendChild(removeButton);

            row.appendChild(labelCol);
            row.appendChild(maxCol);
            row.appendChild(removeCol);
            container.appendChild(row);
        });
    };

    RuleEditor.prototype.renderJumping = function () {
        var faultsCheckbox = this.builder.querySelector('[data-jumping-faults]');
        if (faultsCheckbox) {
            faultsCheckbox.checked = this.state.fault_points !== false;
        }
        var timeInput = this.builder.querySelector('[data-jumping-time]');
        if (timeInput) {
            timeInput.value = typeof this.state.time_allowed === 'number' ? this.state.time_allowed : '';
        }
        var penaltyInput = this.builder.querySelector('[data-jumping-penalty]');
        if (penaltyInput) {
            penaltyInput.value = typeof this.state.time_penalty_per_second === 'number' ? this.state.time_penalty_per_second : '';
        }
    };

    RuleEditor.prototype.renderWestern = function () {
        this.renderWesternManeuvers();
        this.renderWesternPenalties();
    };

    RuleEditor.prototype.renderWesternManeuvers = function () {
        var container = this.builder.querySelector('[data-western-maneuvers]');
        var emptyIndicator = this.builder.querySelector('[data-western-empty]');
        var labelPlaceholder = translate('classes.designer.placeholders.maneuver', null, 'e.g. spin');
        var minPlaceholder = translate('classes.designer.placeholders.min', null, 'Min');
        var maxPlaceholder = translate('classes.designer.placeholders.max', null, 'Max');
        var separatorLabel = translate('classes.designer.placeholders.to', null, 'to');
        var removeLabel = translate('classes.designer.actions.remove_maneuver', null, 'Remove manoeuvre');
        if (!container) {
            return;
        }
        container.innerHTML = '';
        if (!this.state.maneuvers || !this.state.maneuvers.length) {
            if (emptyIndicator) {
                emptyIndicator.classList.remove('d-none');
            }
            return;
        }
        if (emptyIndicator) {
            emptyIndicator.classList.add('d-none');
        }
        this.state.maneuvers.forEach(function (maneuver, index) {
            var row = createElement('<div class="row g-2 align-items-center" data-index="' + index + '"></div>');
            var labelCol = createElement('<div class="col-sm-5 col-md-6"></div>');
            var labelInput = createElement('<input type="text" class="form-control form-control-sm" placeholder="' + escapeHtml(labelPlaceholder) + '" data-field="label">');
            labelInput.value = maneuver.label || '';
            labelCol.appendChild(labelInput);

            var rangeCol = createElement('<div class="col-sm-5 col-md-4"></div>');
            var rangeGroup = createElement('<div class="input-group input-group-sm"></div>');
            var minInput = createElement('<input type="number" class="form-control" step="0.1" data-range="min" placeholder="' + escapeHtml(minPlaceholder) + '">');
            if (Array.isArray(maneuver.range) && typeof maneuver.range[0] === 'number') {
                minInput.value = String(maneuver.range[0]);
            }
            var separator = createElement('<span class="input-group-text">' + escapeHtml(separatorLabel) + '</span>');
            var maxInput = createElement('<input type="number" class="form-control" step="0.1" data-range="max" placeholder="' + escapeHtml(maxPlaceholder) + '">');
            if (Array.isArray(maneuver.range) && typeof maneuver.range[1] === 'number') {
                maxInput.value = String(maneuver.range[1]);
            }
            rangeGroup.appendChild(minInput);
            rangeGroup.appendChild(separator);
            rangeGroup.appendChild(maxInput);
            rangeCol.appendChild(rangeGroup);

            var removeCol = createElement('<div class="col-auto"></div>');
            var removeButton = createElement('<button type="button" class="btn btn-sm btn-outline-danger" data-action="remove-maneuver" aria-label="' + escapeHtml(removeLabel) + '">&times;</button>');
            removeCol.appendChild(removeButton);

            row.appendChild(labelCol);
            row.appendChild(rangeCol);
            row.appendChild(removeCol);
            container.appendChild(row);
        });
    };

    RuleEditor.prototype.renderWesternPenalties = function () {
        var list = this.builder.querySelector('[data-western-penalties]');
        if (!list) {
            return;
        }
        list.innerHTML = '';
        if (!Array.isArray(this.state.penalties) || !this.state.penalties.length) {
            var emptyText = createElement('<span class="text-muted small">' + escapeHtml(translate('classes.designer.penalties.empty', null, 'No penalties defined.')) + '</span>');
            list.appendChild(emptyText);
            return;
        }
        this.state.penalties.forEach(function (penalty, index) {
            var value = typeof penalty === 'number' ? penalty : penalty || 0;
            var button = createElement('<button type="button" class="btn btn-sm btn-outline-secondary" data-action="remove-penalty" data-index="' + index + '"></button>');
            button.innerHTML = value + ' &times;';
            list.appendChild(button);
        });
    };

    RuleEditor.prototype.syncTextarea = function () {
        if (!this.state) {
            return;
        }
        try {
            this.textarea.value = JSON.stringify(this.state, null, 2);
        } catch (error) {
            this.setError(translate('classes.designer.errors.serialize_failed', { message: error.message }, 'Rules could not be serialised: {message}'));
        }
    };

    RuleEditor.prototype.toggleMode = function () {
        if (this.manualMode) {
            var parsed = this.parseManualJson();
            if (!parsed) {
                return;
            }
            var type = parsed.type || 'dressage';
            if (this.supportedTypes.indexOf(type) === -1) {
                this.setError(translate('classes.designer.errors.unsupported_type', { type: type }, 'Rule type "{type}" is not supported by the editor.'));
                return;
            }
            this.state = this.normalizeRule(parsed);
            this.manualMode = false;
            this.errorMessage = null;
            this.render();
            return;
        }
        this.manualMode = true;
        this.errorMessage = null;
        this.clearError();
        this.syncTextarea();
        this.render();
    };

    RuleEditor.prototype.parseManualJson = function () {
        var raw = (this.textarea.value || '').trim();
        if (raw === '') {
            return this.defaultRule('dressage');
        }
        try {
            var data = JSON.parse(raw);
            if (!data || typeof data !== 'object') {
                this.setError(translate('classes.designer.errors.not_object', null, 'Rule JSON must be an object.'));
                return null;
            }
            return data;
        } catch (error) {
            this.setError(translate('classes.designer.errors.json_parse', { message: error.message }, 'Could not read rule JSON: {message}'));
            return null;
        }
    };

    RuleEditor.prototype.changeType = function (type) {
        if (this.supportedTypes.indexOf(type) === -1) {
            return;
        }
        this.state = this.defaultRule(type);
        this.errorMessage = null;
        this.render();
    };

    RuleEditor.prototype.addMovement = function () {
        if (!this.state) {
            return;
        }
        if (!Array.isArray(this.state.movements)) {
            this.state.movements = [];
        }
        this.state.movements.push({ label: translate('classes.designer.defaults.new_movement', null, 'New movement'), max: 10 });
        this.render();
    };

    RuleEditor.prototype.removeMovement = function (index) {
        if (!this.state || !Array.isArray(this.state.movements)) {
            return;
        }
        if (index < 0 || index >= this.state.movements.length) {
            return;
        }
        this.state.movements.splice(index, 1);
        this.render();
    };

    RuleEditor.prototype.addManeuver = function () {
        if (!this.state) {
            return;
        }
        if (!Array.isArray(this.state.maneuvers)) {
            this.state.maneuvers = [];
        }
        this.state.maneuvers.push({ label: translate('classes.designer.defaults.new_maneuver', null, 'New manoeuvre'), range: [-1.5, 1.5] });
        this.render();
    };

    RuleEditor.prototype.removeManeuver = function (index) {
        if (!this.state || !Array.isArray(this.state.maneuvers)) {
            return;
        }
        if (index < 0 || index >= this.state.maneuvers.length) {
            return;
        }
        this.state.maneuvers.splice(index, 1);
        this.render();
    };

    RuleEditor.prototype.addPenalty = function () {
        if (!this.state) {
            return;
        }
        if (!Array.isArray(this.state.penalties)) {
            this.state.penalties = [];
        }
        var input = this.builder.querySelector('[data-western-penalty-input]');
        if (!input) {
            return;
        }
        var value = toNumberOrNull(input.value);
        if (value === null) {
            return;
        }
        this.state.penalties.push(value);
        input.value = '';
        this.render();
    };

    RuleEditor.prototype.removePenalty = function (index) {
        if (!this.state || !Array.isArray(this.state.penalties)) {
            return;
        }
        if (index < 0 || index >= this.state.penalties.length) {
            return;
        }
        this.state.penalties.splice(index, 1);
        this.render();
    };

    RuleEditor.prototype.applyPreset = function (key) {
        var preset = this.presets[key];
        if (!preset || typeof preset !== 'object') {
            return;
        }
        var clone = deepClone(preset);
        if (!clone.type) {
            clone.type = key;
        }
        if (this.supportedTypes.indexOf(clone.type) === -1) {
            this.manualMode = true;
            this.state = null;
            this.setError(translate('classes.designer.errors.preset_unavailable', null, 'The selected preset cannot be rendered in the editor.'));
            try {
                this.textarea.value = JSON.stringify(clone, null, 2);
            } catch (error) {
                this.textarea.value = '';
            }
            this.render();
            return;
        }
        this.state = this.normalizeRule(clone);
        this.manualMode = false;
        this.errorMessage = null;
        this.render();
    };

    RuleEditor.prototype.setSelectValue = function (select, value) {
        if (!select) {
            return;
        }
        Array.prototype.slice.call(select.querySelectorAll('option[data-dynamic="1"]')).forEach(function (option) {
            option.remove();
        });
        var exists = Array.prototype.slice.call(select.options).some(function (option) {
            return option.value === String(value);
        });
        if (!exists && value) {
            var option = window.document.createElement('option');
            option.value = value;
            option.textContent = translate('classes.designer.custom_option', { value: value }, '{value} (custom)');
            option.setAttribute('data-dynamic', '1');
            select.appendChild(option);
        }
        select.value = value || '';
    };

    RuleEditor.prototype.setError = function (message) {
        this.errorMessage = message;
        if (!this.errorBox) {
            return;
        }
        this.errorBox.textContent = message || '';
        if (message) {
            this.errorBox.classList.remove('d-none');
        }
    };

    RuleEditor.prototype.clearError = function () {
        this.errorMessage = null;
        if (!this.errorBox) {
            return;
        }
        this.errorBox.textContent = '';
        this.errorBox.classList.add('d-none');
    };

    window.document.addEventListener('DOMContentLoaded', function () {
        var form = window.document.querySelector('[data-class-form]');
        if (!form) {
            return;
        }
        var presets = safeParseJson(form.getAttribute('data-presets'), {});
        var editor = new RuleEditor(form, presets);
        editor.init();

        var arenaData = safeParseJson(form.getAttribute('data-arenas'), { events: {} });
        var arenaPicker = new ArenaPicker(form, arenaData);
        arenaPicker.init();
    });
})(window);
