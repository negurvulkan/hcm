(function (window, $) {
    'use strict';

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

    function toNumberOrNull(value) {
        var number = parseFloat(value);
        if (!isNaN(number) && isFinite(number)) {
            return number;
        }
        return null;
    }

    function RuleEditor($form, presets) {
        this.$form = $form;
        this.$editor = $form.find('[data-rule-editor]');
        this.$textarea = this.$editor.find('[data-rule-json]');
        this.$builder = this.$editor.find('[data-rule-builder]');
        this.$toggle = this.$editor.find('[data-rule-toggle]');
        this.$error = this.$editor.find('[data-rule-error]');
        this.presets = presets || {};
        this.supportedTypes = ['dressage', 'jumping', 'western'];
        this.state = null;
        this.manualMode = true;
        this.errorMessage = null;
    }

    RuleEditor.prototype.init = function () {
        if (!this.$editor.length || !this.$textarea.length) {
            return;
        }

        var self = this;
        this.$toggle.removeClass('d-none');
        this.$toggle.on('click', function (event) {
            event.preventDefault();
            self.toggleMode();
        });

        this.$editor.on('click', '[data-preset]', function (event) {
            event.preventDefault();
            var key = $(this).data('preset');
            self.applyPreset(key);
        });

        this.$builder.on('change', '[data-rule-type]', function () {
            var type = $(this).val();
            self.changeType(type);
        });

        this.$builder.on('click', '[data-action="add-movement"]', function (event) {
            event.preventDefault();
            self.addMovement();
        });

        this.$builder.on('click', '[data-action="remove-movement"]', function (event) {
            event.preventDefault();
            var $row = $(this).closest('[data-index]');
            var index = parseInt($row.data('index'), 10);
            self.removeMovement(index);
        });

        this.$builder.on('input', '[data-dressage-movements] [data-field]', function () {
            if (!self.state || !Array.isArray(self.state.movements)) {
                return;
            }
            var $row = $(this).closest('[data-index]');
            var index = parseInt($row.data('index'), 10);
            if (isNaN(index) || !self.state.movements[index]) {
                return;
            }
            var field = $(this).data('field');
            if (field === 'max') {
                var maxValue = toNumberOrNull($(this).val());
                self.state.movements[index].max = maxValue !== null ? maxValue : null;
            } else {
                self.state.movements[index].label = $(this).val();
            }
            self.syncTextarea();
        });

        this.$builder.on('input', '[data-dressage-step]', function () {
            if (!self.state) {
                return;
            }
            var value = toNumberOrNull($(this).val());
            self.state.step = value !== null ? value : null;
            self.syncTextarea();
        });

        this.$builder.on('change', '[data-dressage-aggregate]', function () {
            if (!self.state) {
                return;
            }
            self.state.aggregate = $(this).val();
            self.syncTextarea();
        });

        this.$builder.on('change', '[data-dressage-drop]', function () {
            if (!self.state) {
                return;
            }
            self.state.drop_high_low = $(this).is(':checked');
            self.syncTextarea();
        });

        this.$builder.on('change', '[data-jumping-faults]', function () {
            if (!self.state) {
                return;
            }
            self.state.fault_points = $(this).is(':checked');
            self.syncTextarea();
        });

        this.$builder.on('input', '[data-jumping-time]', function () {
            if (!self.state) {
                return;
            }
            var value = toNumberOrNull($(this).val());
            self.state.time_allowed = value !== null ? value : null;
            self.syncTextarea();
        });

        this.$builder.on('input', '[data-jumping-penalty]', function () {
            if (!self.state) {
                return;
            }
            var value = toNumberOrNull($(this).val());
            self.state.time_penalty_per_second = value !== null ? value : null;
            self.syncTextarea();
        });

        this.$builder.on('click', '[data-action="add-maneuver"]', function (event) {
            event.preventDefault();
            self.addManeuver();
        });

        this.$builder.on('click', '[data-action="remove-maneuver"]', function (event) {
            event.preventDefault();
            var $row = $(this).closest('[data-index]');
            var index = parseInt($row.data('index'), 10);
            self.removeManeuver(index);
        });

        this.$builder.on('input', '[data-western-maneuvers] [data-field="label"]', function () {
            if (!self.state || !Array.isArray(self.state.maneuvers)) {
                return;
            }
            var $row = $(this).closest('[data-index]');
            var index = parseInt($row.data('index'), 10);
            if (isNaN(index) || !self.state.maneuvers[index]) {
                return;
            }
            self.state.maneuvers[index].label = $(this).val();
            self.syncTextarea();
        });

        this.$builder.on('input', '[data-western-maneuvers] [data-range]', function () {
            if (!self.state || !Array.isArray(self.state.maneuvers)) {
                return;
            }
            var $row = $(this).closest('[data-index]');
            var index = parseInt($row.data('index'), 10);
            if (isNaN(index) || !self.state.maneuvers[index]) {
                return;
            }
            var range = self.state.maneuvers[index].range || [];
            if ($(this).data('range') === 'min') {
                range[0] = toNumberOrNull($(this).val());
            } else {
                range[1] = toNumberOrNull($(this).val());
            }
            self.state.maneuvers[index].range = range;
            self.syncTextarea();
        });

        this.$builder.on('click', '[data-action="add-penalty"]', function (event) {
            event.preventDefault();
            self.addPenalty();
        });

        this.$builder.on('keydown', '[data-western-penalty-input]', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                self.addPenalty();
            }
        });

        this.$builder.on('click', '[data-action="remove-penalty"]', function (event) {
            event.preventDefault();
            var index = parseInt($(this).data('index'), 10);
            self.removePenalty(index);
        });

        this.$form.on('submit', function () {
            if (!self.manualMode && self.state) {
                self.syncTextarea();
            }
        });

        this.bootstrapState();
    };

    RuleEditor.prototype.bootstrapState = function () {
        var raw = $.trim(this.$textarea.val() || '');
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
                this.setError('Regel-JSON muss ein Objekt sein.');
                this.render();
                return;
            }
            var type = data.type || 'dressage';
            if ($.inArray(type, this.supportedTypes) === -1) {
                this.manualMode = true;
                this.state = null;
                this.setError('Der Regeltyp "' + type + '" wird vom Editor nicht unterstützt.');
                this.render();
                return;
            }
            this.state = this.normalizeRule(data);
            this.manualMode = false;
            this.errorMessage = null;
        } catch (error) {
            this.manualMode = true;
            this.state = null;
            this.setError('Regel-JSON konnte nicht gelesen werden: ' + error.message);
        }

        this.render();
    };

    RuleEditor.prototype.getFallbackForType = function (type) {
        var fallback;
        switch (type) {
            case 'jumping':
                fallback = {
                    type: 'jumping',
                    fault_points: true,
                    time_allowed: 75,
                    time_penalty_per_second: 1
                };
                break;
            case 'western':
                fallback = {
                    type: 'western',
                    maneuvers: [
                        { label: 'Manöver 1', range: [-1.5, 1.5] }
                    ],
                    penalties: [1, 2, 5]
                };
                break;
            default:
                fallback = {
                    type: 'dressage',
                    movements: [
                        { label: 'Bewegung 1', max: 10 }
                    ],
                    step: 0.5,
                    aggregate: 'average',
                    drop_high_low: false
                };
                break;
        }
        return fallback;
    };

    RuleEditor.prototype.defaultRule = function (type) {
        var base = deepClone(this.getFallbackForType(type));
        var preset = this.presets[type];
        if (preset && typeof preset === 'object') {
            var presetClone = deepClone(preset);
            if (!presetClone.type) {
                presetClone.type = type;
            }
            base = $.extend(true, base, presetClone);
        }
        return this.normalizeRule(base);
    };

    RuleEditor.prototype.normalizeRule = function (rule) {
        if (!rule || typeof rule !== 'object') {
            return this.defaultRule('dressage');
        }
        var clone = deepClone(rule);
        var type = clone.type || 'dressage';
        switch (type) {
            case 'jumping':
                clone = this.normalizeJumpingRule(clone);
                break;
            case 'western':
                clone = this.normalizeWesternRule(clone);
                break;
            default:
                clone = this.normalizeDressageRule(clone);
                type = 'dressage';
                break;
        }
        clone.type = type;
        return clone;
    };

    RuleEditor.prototype.normalizeDressageRule = function (rule) {
        rule.movements = this.normalizeMovements(rule.movements, 'Bewegung');
        rule.step = toNumberOrNull(rule.step);
        if (rule.step === null) {
            rule.step = 0.5;
        }
        rule.aggregate = typeof rule.aggregate === 'string' && rule.aggregate !== '' ? rule.aggregate : 'average';
        rule.drop_high_low = !!rule.drop_high_low;
        return rule;
    };

    RuleEditor.prototype.normalizeJumpingRule = function (rule) {
        if (typeof rule.fault_points === 'undefined') {
            rule.fault_points = true;
        } else {
            rule.fault_points = !!rule.fault_points;
        }
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
        if (!list.length) {
            list = [{ label: prefix + ' 1', max: 10 }];
        }
        return list.map(function (item, index) {
            var movement = deepClone(item || {});
            movement.label = typeof movement.label === 'string' && movement.label.trim() !== ''
                ? movement.label
                : prefix + ' ' + (index + 1);
            var maxValue = toNumberOrNull(movement.max);
            movement.max = maxValue !== null ? maxValue : 10;
            return movement;
        });
    };

    RuleEditor.prototype.normalizeManeuvers = function (items) {
        var list = Array.isArray(items) ? items : [];
        if (!list.length) {
            list = [{ label: 'Manöver 1', range: [-1.5, 1.5] }];
        }
        return list.map(function (item, index) {
            var maneuver = deepClone(item || {});
            maneuver.label = typeof maneuver.label === 'string' && maneuver.label.trim() !== ''
                ? maneuver.label
                : 'Manöver ' + (index + 1);
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
            return number !== null ? number : 0;
        }).filter(function (value) {
            return value !== null && !isNaN(value);
        });
    };

    RuleEditor.prototype.render = function () {
        if (this.manualMode) {
            this.$builder.addClass('d-none');
            this.$textarea.removeClass('d-none');
            this.$toggle.text('UI-Editor nutzen');
            if (this.errorMessage) {
                this.setError(this.errorMessage);
            }
            return;
        }
        this.clearError();
        this.$textarea.addClass('d-none');
        this.$builder.removeClass('d-none');
        this.$toggle.text('JSON bearbeiten');
        if (!this.state) {
            this.state = this.defaultRule('dressage');
        }
        this.renderBuilder();
    };

    RuleEditor.prototype.renderBuilder = function () {
        var type = this.state.type || 'dressage';
        this.$builder.find('[data-rule-type]').val(type);
        this.$builder.find('[data-rule-panel]').addClass('d-none');
        this.$builder.find('[data-rule-panel="' + type + '"]').removeClass('d-none');

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
        var step = (typeof this.state.step === 'number') ? this.state.step : '';
        this.$builder.find('[data-dressage-step]').val(step);
        var $aggregate = this.$builder.find('[data-dressage-aggregate]');
        this.setSelectValue($aggregate, this.state.aggregate);
        this.$builder.find('[data-dressage-drop]').prop('checked', !!this.state.drop_high_low);
    };

    RuleEditor.prototype.renderDressageMovements = function () {
        var $container = this.$builder.find('[data-dressage-movements]');
        var $empty = this.$builder.find('[data-dressage-empty]');
        $container.empty();

        if (!this.state.movements || !this.state.movements.length) {
            $empty.removeClass('d-none');
            return;
        }
        $empty.addClass('d-none');

        this.state.movements.forEach(function (movement, index) {
            var $row = $('<div class="row g-2 align-items-center" data-index="' + index + '"></div>');
            var $labelCol = $('<div class="col"></div>');
            var $labelInput = $('<input type="text" class="form-control form-control-sm" placeholder="z. B. Trabverstärkung" data-field="label">');
            $labelInput.val(movement.label || '');
            $labelCol.append($labelInput);

            var $maxCol = $('<div class="col-auto" style="width: 120px;"></div>');
            var $maxInput = $('<input type="number" class="form-control form-control-sm" min="0" step="0.1" data-field="max">');
            if (typeof movement.max === 'number') {
                $maxInput.val(movement.max);
            }
            $maxCol.append($maxInput);

            var $removeCol = $('<div class="col-auto"></div>');
            var $removeButton = $('<button type="button" class="btn btn-sm btn-outline-danger" data-action="remove-movement" aria-label="Bewegung entfernen">&times;</button>');
            $removeCol.append($removeButton);

            $row.append($labelCol, $maxCol, $removeCol);
            $container.append($row);
        });
    };

    RuleEditor.prototype.renderJumping = function () {
        this.$builder.find('[data-jumping-faults]').prop('checked', this.state.fault_points !== false);
        var timeAllowed = (typeof this.state.time_allowed === 'number') ? this.state.time_allowed : '';
        this.$builder.find('[data-jumping-time]').val(timeAllowed);
        var penalty = (typeof this.state.time_penalty_per_second === 'number') ? this.state.time_penalty_per_second : '';
        this.$builder.find('[data-jumping-penalty]').val(penalty);
    };

    RuleEditor.prototype.renderWestern = function () {
        this.renderWesternManeuvers();
        this.renderWesternPenalties();
    };

    RuleEditor.prototype.renderWesternManeuvers = function () {
        var $container = this.$builder.find('[data-western-maneuvers]');
        var $empty = this.$builder.find('[data-western-empty]');
        $container.empty();

        if (!this.state.maneuvers || !this.state.maneuvers.length) {
            $empty.removeClass('d-none');
            return;
        }
        $empty.addClass('d-none');

        this.state.maneuvers.forEach(function (maneuver, index) {
            var $row = $('<div class="row g-2 align-items-center" data-index="' + index + '"></div>');
            var $labelCol = $('<div class="col-sm-5 col-md-6"></div>');
            var $labelInput = $('<input type="text" class="form-control form-control-sm" placeholder="z. B. Spin" data-field="label">');
            $labelInput.val(maneuver.label || '');
            $labelCol.append($labelInput);

            var $rangeCol = $('<div class="col-sm-5 col-md-4"></div>');
            var $rangeGroup = $('<div class="input-group input-group-sm"></div>');
            var $minInput = $('<input type="number" class="form-control" step="0.1" data-range="min" placeholder="Min">');
            if (Array.isArray(maneuver.range) && typeof maneuver.range[0] === 'number') {
                $minInput.val(maneuver.range[0]);
            }
            var $separator = $('<span class="input-group-text">bis</span>');
            var $maxInput = $('<input type="number" class="form-control" step="0.1" data-range="max" placeholder="Max">');
            if (Array.isArray(maneuver.range) && typeof maneuver.range[1] === 'number') {
                $maxInput.val(maneuver.range[1]);
            }
            $rangeGroup.append($minInput, $separator, $maxInput);
            $rangeCol.append($rangeGroup);

            var $removeCol = $('<div class="col-auto"></div>');
            var $removeButton = $('<button type="button" class="btn btn-sm btn-outline-danger" data-action="remove-maneuver" aria-label="Manöver entfernen">&times;</button>');
            $removeCol.append($removeButton);

            $row.append($labelCol, $rangeCol, $removeCol);
            $container.append($row);
        });
    };

    RuleEditor.prototype.renderWesternPenalties = function () {
        var $list = this.$builder.find('[data-western-penalties]');
        $list.empty();
        if (!Array.isArray(this.state.penalties) || !this.state.penalties.length) {
            $list.append('<span class="text-muted small">Keine Strafpunkte definiert.</span>');
            return;
        }
        this.state.penalties.forEach(function (penalty, index) {
            var label = typeof penalty === 'number' ? penalty : penalty || 0;
            var $button = $('<button type="button" class="btn btn-sm btn-outline-secondary" data-action="remove-penalty" data-index="' + index + '"></button>');
            $button.html(label + ' &times;');
            $list.append($button);
        });
    };

    RuleEditor.prototype.syncTextarea = function () {
        if (!this.state) {
            return;
        }
        try {
            var json = JSON.stringify(this.state, null, 2);
            this.$textarea.val(json);
        } catch (error) {
            this.setError('Regeln konnten nicht serialisiert werden: ' + error.message);
        }
    };

    RuleEditor.prototype.toggleMode = function () {
        if (this.manualMode) {
            var parsed = this.parseManualJson();
            if (!parsed) {
                return;
            }
            var type = parsed.type || 'dressage';
            if ($.inArray(type, this.supportedTypes) === -1) {
                this.setError('Der Regeltyp "' + type + '" wird vom Editor nicht unterstützt.');
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
        var raw = $.trim(this.$textarea.val() || '');
        if (raw === '') {
            return this.defaultRule('dressage');
        }
        try {
            var data = JSON.parse(raw);
            if (!data || typeof data !== 'object') {
                this.setError('Regel-JSON muss ein Objekt sein.');
                return null;
            }
            return data;
        } catch (error) {
            this.setError('Regel-JSON konnte nicht gelesen werden: ' + error.message);
            return null;
        }
    };

    RuleEditor.prototype.changeType = function (type) {
        if ($.inArray(type, this.supportedTypes) === -1) {
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
        this.state.movements.push({ label: 'Neue Bewegung', max: 10 });
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
        this.state.maneuvers.push({ label: 'Neues Manöver', range: [-1.5, 1.5] });
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
        var $input = this.$builder.find('[data-western-penalty-input]');
        var value = toNumberOrNull($input.val());
        if (value === null) {
            return;
        }
        this.state.penalties.push(value);
        $input.val('');
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
        if ($.inArray(clone.type, this.supportedTypes) === -1) {
            this.manualMode = true;
            this.state = null;
            this.setError('Die ausgewählte Vorlage kann nicht im Editor dargestellt werden.');
            this.$textarea.val(JSON.stringify(clone, null, 2));
            this.render();
            return;
        }
        this.state = this.normalizeRule(clone);
        this.manualMode = false;
        this.errorMessage = null;
        this.render();
    };

    RuleEditor.prototype.setSelectValue = function ($select, value) {
        $select.find('option[data-dynamic="1"]').remove();
        var exists = false;
        $select.find('option').each(function () {
            if ($(this).val() === String(value)) {
                exists = true;
                return false;
            }
        });
        if (!exists && value) {
            var text = value + ' (benutzerdefiniert)';
            $select.append($('<option>', {
                value: value,
                text: text,
                'data-dynamic': '1'
            }));
        }
        $select.val(value);
    };

    RuleEditor.prototype.setError = function (message) {
        this.errorMessage = message;
        if (!this.$error.length) {
            return;
        }
        this.$error.text(message).removeClass('d-none');
    };

    RuleEditor.prototype.clearError = function () {
        this.errorMessage = null;
        if (!this.$error.length) {
            return;
        }
        this.$error.addClass('d-none').text('');
    };

    $(function () {
        var $form = $('[data-class-form]');
        if (!$form.length) {
            return;
        }
        var presets = $form.data('presets') || {};
        var editor = new RuleEditor($form, presets);
        editor.init();
    });
})(window, window.jQuery);
