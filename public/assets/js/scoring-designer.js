(function (window) {
    'use strict';

    function deepClone(value) {
        if (value === null || typeof value !== 'object') {
            return value;
        }
        if (Array.isArray(value)) {
            return value.map(function (item) { return deepClone(item); });
        }
        var clone = {};
        Object.keys(value).forEach(function (key) {
            clone[key] = deepClone(value[key]);
        });
        return clone;
    }

    function isNumericKey(part) {
        return /^\d+$/.test(part);
    }

    function toList(value, cloneItems) {
        if (Array.isArray(value)) {
            return cloneItems ? value.map(function (item) { return deepClone(item); }) : value;
        }
        if (!value || typeof value !== 'object') {
            return [];
        }
        return Object.keys(value).map(function (key) {
            return cloneItems ? deepClone(value[key]) : value[key];
        });
    }

    function mergeWithDefaults(defaults, source) {
        var result = deepClone(defaults || {});
        if (!source || typeof source !== 'object') {
            return result;
        }
        Object.keys(source).forEach(function (key) {
            var value = source[key];
            if (Array.isArray(value)) {
                result[key] = value.map(function (item) { return deepClone(item); });
            } else if (value && typeof value === 'object' && !Array.isArray(value)) {
                var defaultChild = defaults ? defaults[key] : undefined;
                if (Array.isArray(defaultChild)) {
                    result[key] = toList(value, true);
                } else if (defaultChild && typeof defaultChild === 'object' && !Array.isArray(defaultChild)) {
                    result[key] = mergeWithDefaults(defaultChild, value);
                } else {
                    result[key] = deepClone(value);
                }
            } else {
                result[key] = value;
            }
        });
        return result;
    }

    function getByPath(object, path) {
        if (!object || !path) {
            return undefined;
        }
        var parts = path.split('.');
        var current = object;
        for (var i = 0; i < parts.length; i++) {
            if (current === null || typeof current === 'undefined') {
                return undefined;
            }
            var part = parts[i];
            if (Array.isArray(current)) {
                var index = parseInt(part, 10);
                if (isNaN(index) || index < 0 || index >= current.length) {
                    return undefined;
                }
                current = current[index];
            } else {
                if (!Object.prototype.hasOwnProperty.call(current, part)) {
                    return undefined;
                }
                current = current[part];
            }
        }
        return current;
    }

    function ensureContainer(parent, key, nextPart) {
        if (Array.isArray(parent)) {
            var index = parseInt(key, 10);
            if (isNaN(index)) {
                return null;
            }
            if (typeof parent[index] === 'undefined') {
                parent[index] = isNumericKey(nextPart) ? [] : {};
            }
            return parent[index];
        }
        if (!parent[key] || typeof parent[key] !== 'object') {
            parent[key] = isNumericKey(nextPart) ? [] : {};
        }
        return parent[key];
    }

    function setByPath(object, path, value) {
        if (!object || !path) {
            return;
        }
        var parts = path.split('.');
        var current = object;
        for (var i = 0; i < parts.length - 1; i++) {
            var part = parts[i];
            var nextPart = parts[i + 1];
            if (Array.isArray(current)) {
                var index = parseInt(part, 10);
                if (isNaN(index)) {
                    return;
                }
                if (typeof current[index] === 'undefined') {
                    current[index] = isNumericKey(nextPart) ? [] : {};
                }
                current = current[index];
            } else {
                current = ensureContainer(current, part, nextPart);
            }
        }
        var last = parts[parts.length - 1];
        if (Array.isArray(current)) {
            var lastIndex = parseInt(last, 10);
            if (!isNaN(lastIndex)) {
                current[lastIndex] = value;
            }
        } else {
            current[last] = value;
        }
    }

    function parseJsonSafe(raw, fallback) {
        if (!raw || typeof raw !== 'string') {
            return fallback;
        }
        try {
            return JSON.parse(raw);
        } catch (error) {
            return fallback;
        }
    }

    function parseNumber(value, integer) {
        if (value === '' || value === null || typeof value === 'undefined') {
            return null;
        }
        var number = integer ? parseInt(value, 10) : parseFloat(value);
        if (isNaN(number) || !isFinite(number)) {
            return null;
        }
        return number;
    }

    function parseCsv(value, asNumber) {
        if (!value || typeof value !== 'string') {
            return [];
        }
        return value.split(',').map(function (item) {
            var trimmed = item.trim();
            if (trimmed === '') {
                return null;
            }
            if (!asNumber) {
                return trimmed;
            }
            var numeric = parseFloat(trimmed);
            if (isNaN(numeric) || !isFinite(numeric)) {
                return null;
            }
            return numeric;
        }).filter(function (entry) { return entry !== null; });
    }

    function formatCsv(value) {
        if (!Array.isArray(value)) {
            return '';
        }
        return value.join(', ');
    }

    function stripLessonsSection(target) {
        if (!target || typeof target !== 'object') {
            return;
        }
        if (target.input && typeof target.input === 'object' && Object.prototype.hasOwnProperty.call(target.input, 'lessons')) {
            delete target.input.lessons;
        }
    }

    function ScoringDesigner(root) {
        this.root = root;
        this.targetSelector = root.getAttribute('data-target');
        this.target = this.targetSelector ? window.document.querySelector(this.targetSelector) : null;
        this.defaults = deepClone(parseJsonSafe(root.getAttribute('data-default'), {}));
        stripLessonsSection(this.defaults);
        this.presets = parseJsonSafe(root.getAttribute('data-presets'), {});
        if (this.presets && typeof this.presets === 'object') {
            var self = this;
            Object.keys(this.presets).forEach(function (key) {
                stripLessonsSection(self.presets[key]);
            });
        }
        this.state = deepClone(this.defaults);
        this.modal = root.closest('.modal');
        this.handleShow = this.handleShow.bind(this);
        this.handleInput = this.handleInput.bind(this);
        this.handleChange = this.handleChange.bind(this);
        this.handleClick = this.handleClick.bind(this);
        this.init();
    }

    ScoringDesigner.prototype.init = function () {
        if (!this.target) {
            return;
        }
        if (this.modal) {
            this.modal.addEventListener('show.bs.modal', this.handleShow);
        }
        this.root.addEventListener('input', this.handleInput);
        this.root.addEventListener('change', this.handleChange);
        this.root.addEventListener('click', this.handleClick);
    };

    ScoringDesigner.prototype.handleShow = function () {
        this.loadFromTextarea();
        this.renderStructure();
    };

    ScoringDesigner.prototype.loadFromTextarea = function () {
        if (!this.target) {
            this.state = deepClone(this.defaults);
            return;
        }
        var raw = (this.target.value || '').trim();
        if (raw === '') {
            this.state = deepClone(this.defaults);
            return;
        }
        var parsed = parseJsonSafe(raw, null);
        if (!parsed || typeof parsed !== 'object') {
            this.state = deepClone(this.defaults);
            return;
        }
        this.state = mergeWithDefaults(this.defaults, parsed);
        stripLessonsSection(this.state);
    };

    ScoringDesigner.prototype.renderStructure = function () {
        if (!this.state || typeof this.state !== 'object') {
            this.state = deepClone(this.defaults);
        }
        if (!this.state.input || typeof this.state.input !== 'object') {
            this.state.input = {};
        }
        stripLessonsSection(this.state);
        this.state.input.fields = toList(this.state.input.fields, false);
        this.state.input.components = toList(this.state.input.components, false);
        this.state.penalties = toList(this.state.penalties, false);
        if (!this.state.ranking) {
            this.state.ranking = {};
        }
        this.state.ranking.tiebreak_chain = toList(this.state.ranking.tiebreak_chain, false);
        if (!this.state.grouping || typeof this.state.grouping !== 'object') {
            this.state.grouping = {};
        }
        if (!this.state.grouping.department || typeof this.state.grouping.department !== 'object') {
            if (this.defaults && this.defaults.grouping && this.defaults.grouping.department) {
                this.state.grouping.department = deepClone(this.defaults.grouping.department);
            } else {
                this.state.grouping.department = {
                    enabled: false,
                    aggregation: 'mean',
                    rounding: 2,
                    label: '',
                    min_members: 2
                };
            }
        }

        this.renderList('fields', this.state.input.fields, this.renderFieldRow.bind(this));
        this.renderList('components', this.state.input.components, this.renderComponentRow.bind(this));
        this.renderList('penalties', this.state.penalties, this.renderPenaltyRow.bind(this));
        this.renderList('tiebreakers', this.state.ranking.tiebreak_chain, this.renderTiebreakRow.bind(this));
        this.applyValues();
    };

    ScoringDesigner.prototype.renderList = function (name, items, renderer) {
        var container = this.root.querySelector('[data-scoring-list="' + name + '"]');
        if (!container) {
            return;
        }
        container.innerHTML = '';
        if (!items || !items.length) {
            var emptyText = container.getAttribute('data-empty-text');
            if (emptyText) {
                var placeholder = window.document.createElement('div');
                placeholder.className = 'text-muted fst-italic small';
                placeholder.textContent = emptyText;
                container.appendChild(placeholder);
            }
            return;
        }
        items.forEach(function (item, index) {
            var element = renderer(item, index);
            if (element) {
                container.appendChild(element);
            }
        });
    };

    ScoringDesigner.prototype.renderFieldRow = function (field, index) {
        var wrapper = window.document.createElement('div');
        wrapper.className = 'border rounded p-3';
        wrapper.innerHTML = '' +
            '<div class="d-flex justify-content-between align-items-center mb-3">' +
            '  <h5 class="h6 mb-0">Feld ' + (index + 1) + '</h5>' +
            '  <button class="btn btn-sm btn-outline-danger" type="button" data-action="remove-item" data-list="fields" data-index="' + index + '">Entfernen</button>' +
            '</div>' +
            '<div class="row g-3">' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Feld-ID</label>' +
            '    <input type="text" class="form-control form-control-sm" data-scoring-path="input.fields.' + index + '.id">' +
            '  </div>' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Label</label>' +
            '    <input type="text" class="form-control form-control-sm" data-scoring-path="input.fields.' + index + '.label">' +
            '  </div>' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Typ</label>' +
            '    <select class="form-select form-select-sm" data-scoring-path="input.fields.' + index + '.type">' +
            '      <option value="number">Zahl</option>' +
            '      <option value="time">Zeit</option>' +
            '      <option value="set">Auswahl (Set)</option>' +
            '      <option value="boolean">Ja/Nein</option>' +
            '      <option value="text">Text (einzeilig)</option>' +
            '      <option value="textarea">Text (mehrzeilig)</option>' +
            '    </select>' +
            '  </div>' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Pflichtfeld</label>' +
            '    <div class="form-check">' +
            '      <input class="form-check-input" type="checkbox" data-type="boolean" data-scoring-path="input.fields.' + index + '.required" id="field-required-' + index + '">' +
            '      <label class="form-check-label" for="field-required-' + index + '">erforderlich</label>' +
            '    </div>' +
            '  </div>' +
            '</div>' +
            '<div class="row g-3 mt-2">' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Min</label>' +
            '    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="input.fields.' + index + '.min" step="0.01">' +
            '  </div>' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Max</label>' +
            '    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="input.fields.' + index + '.max" step="0.01">' +
            '  </div>' +
            '  <div class="col-md-4">' +
            '    <label class="form-label">Optionen (Set, Komma getrennt)</label>' +
            '    <input type="text" class="form-control form-control-sm" data-type="csv-string" data-scoring-path="input.fields.' + index + '.options">' +
            '  </div>' +
            '  <div class="col-md-2">' +
            '    <label class="form-label">Textarea-Zeilen (optional)</label>' +
            '    <input type="number" class="form-control form-control-sm" data-type="integer" data-scoring-path="input.fields.' + index + '.rows" min="1">' +
            '  </div>' +
            '</div>';
        return wrapper;
    };

    ScoringDesigner.prototype.renderComponentRow = function (component, index) {
        var wrapper = window.document.createElement('div');
        wrapper.className = 'border rounded p-3';
        wrapper.innerHTML = '' +
            '<div class="d-flex justify-content-between align-items-center mb-3">' +
            '  <h5 class="h6 mb-0">Komponente ' + (index + 1) + '</h5>' +
            '  <button class="btn btn-sm btn-outline-danger" type="button" data-action="remove-item" data-list="components" data-index="' + index + '">Entfernen</button>' +
            '</div>' +
            '<div class="row g-3">' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">ID</label>' +
            '    <input type="text" class="form-control form-control-sm" data-scoring-path="input.components.' + index + '.id">' +
            '  </div>' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Label</label>' +
            '    <input type="text" class="form-control form-control-sm" data-scoring-path="input.components.' + index + '.label">' +
            '  </div>' +
            '  <div class="col-md-2">' +
            '    <label class="form-label">Min</label>' +
            '    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="input.components.' + index + '.min" step="0.01">' +
            '  </div>' +
            '  <div class="col-md-2">' +
            '    <label class="form-label">Max</label>' +
            '    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="input.components.' + index + '.max" step="0.01">' +
            '  </div>' +
            '  <div class="col-md-2">' +
            '    <label class="form-label">Schritt</label>' +
            '    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="input.components.' + index + '.step" step="0.01">' +
            '  </div>' +
            '</div>' +
            '<div class="row g-3 mt-2">' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Gewichtung</label>' +
            '    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="input.components.' + index + '.weight" step="0.01">' +
            '  </div>' +
            '</div>';
        return wrapper;
    };

    ScoringDesigner.prototype.renderPenaltyRow = function (penalty, index) {
        var wrapper = window.document.createElement('div');
        wrapper.className = 'border rounded p-3';
        wrapper.innerHTML = '' +
            '<div class="d-flex justify-content-between align-items-center mb-3">' +
            '  <h5 class="h6 mb-0">Penalty ' + (index + 1) + '</h5>' +
            '  <button class="btn btn-sm btn-outline-danger" type="button" data-action="remove-item" data-list="penalties" data-index="' + index + '">Entfernen</button>' +
            '</div>' +
            '<div class="row g-3">' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">ID</label>' +
            '    <input type="text" class="form-control form-control-sm" data-scoring-path="penalties.' + index + '.id">' +
            '  </div>' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Label</label>' +
            '    <input type="text" class="form-control form-control-sm" data-scoring-path="penalties.' + index + '.label">' +
            '  </div>' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Bedingung</label>' +
            '    <input type="text" class="form-control form-control-sm" data-scoring-path="penalties.' + index + '.when">' +
            '  </div>' +
            '  <div class="col-md-3">' +
            '    <label class="form-label">Punkte/Expression</label>' +
            '    <input type="text" class="form-control form-control-sm" data-scoring-path="penalties.' + index + '.points">' +
            '  </div>' +
            '</div>';
        return wrapper;
    };

    ScoringDesigner.prototype.renderTiebreakRow = function (entry, index) {
        var wrapper = window.document.createElement('div');
        wrapper.className = 'border rounded p-3';
        wrapper.innerHTML = '' +
            '<div class="d-flex justify-content-between align-items-center">' +
            '  <div class="flex-grow-1 me-3">' +
            '    <label class="form-label">Tiebreaker #' + (index + 1) + '</label>' +
            '    <input type="text" class="form-control form-control-sm" data-scoring-path="ranking.tiebreak_chain.' + index + '">' +
            '  </div>' +
            '  <button class="btn btn-sm btn-outline-danger mt-4" type="button" data-action="remove-item" data-list="tiebreakers" data-index="' + index + '">Entfernen</button>' +
            '</div>';
        return wrapper;
    };

    ScoringDesigner.prototype.applyValues = function () {
        var self = this;
        this.root.querySelectorAll('[data-scoring-path]').forEach(function (element) {
            var path = element.getAttribute('data-scoring-path');
            var type = element.getAttribute('data-type') || element.type;
            var value = getByPath(self.state, path);
            if (type === 'boolean') {
                element.checked = !!value;
            } else if (type === 'number' || type === 'integer') {
                element.value = value === null || typeof value === 'undefined' ? '' : value;
            } else if (type === 'csv-number' || type === 'csv-string') {
                element.value = formatCsv(value);
            } else {
                element.value = value === null || typeof value === 'undefined' ? '' : value;
            }
        });
    };

    ScoringDesigner.prototype.updateFromElement = function (element) {
        var path = element.getAttribute('data-scoring-path');
        if (!path) {
            return;
        }
        var type = element.getAttribute('data-type') || element.type;
        var value;
        if (type === 'boolean') {
            value = !!element.checked;
        } else if (type === 'number') {
            value = parseNumber(element.value, false);
        } else if (type === 'integer') {
            value = parseNumber(element.value, true);
        } else if (type === 'csv-number') {
            value = parseCsv(element.value, true);
        } else if (type === 'csv-string') {
            value = parseCsv(element.value, false);
        } else {
            value = element.value;
        }
        setByPath(this.state, path, value);
        this.updateTextarea();
    };

    ScoringDesigner.prototype.handleInput = function (event) {
        if (!event.target.hasAttribute('data-scoring-path')) {
            return;
        }
        var type = event.target.getAttribute('data-type') || event.target.type;
        if (type === 'checkbox' || type === 'boolean') {
            return;
        }
        this.updateFromElement(event.target);
    };

    ScoringDesigner.prototype.handleChange = function (event) {
        var target = event.target;
        if (target && target.hasAttribute('data-preset-select')) {
            var preset = target.value;
            if (preset) {
                this.loadPreset(preset);
            }
            target.value = '';
            return;
        }
        if (target.hasAttribute('data-scoring-path')) {
            this.updateFromElement(target);
        }
    };

    ScoringDesigner.prototype.handleClick = function (event) {
        var button = event.target.closest('[data-action]');
        if (!button || !this.root.contains(button)) {
            return;
        }
        var action = button.getAttribute('data-action');
        if (action === 'add-field') {
            this.addField();
        } else if (action === 'add-component') {
            this.addComponent();
        } else if (action === 'add-penalty') {
            this.addPenalty();
        } else if (action === 'add-tiebreak') {
            this.addTiebreak();
        } else if (action === 'remove-item') {
            var listName = button.getAttribute('data-list');
            var index = parseInt(button.getAttribute('data-index'), 10);
            this.removeItem(listName, index);
        } else if (action === 'reset-default') {
            this.resetDefaults();
        }
    };

    ScoringDesigner.prototype.updateTextarea = function () {
        if (!this.target) {
            return;
        }
        try {
            this.target.value = JSON.stringify(this.state, null, 2);
        } catch (error) {
            return;
        }
        var event = new window.Event('input', { bubbles: true });
        this.target.dispatchEvent(event);
    };

    ScoringDesigner.prototype.addField = function () {
        if (!Array.isArray(this.state.input.fields)) {
            this.state.input.fields = [];
        }
        var index = this.state.input.fields.length + 1;
        this.state.input.fields.push({
            id: 'field_' + index,
            label: 'Neues Feld',
            type: 'number'
        });
        this.renderStructure();
        this.updateTextarea();
    };

    ScoringDesigner.prototype.addComponent = function () {
        if (!Array.isArray(this.state.input.components)) {
            this.state.input.components = [];
        }
        var index = this.state.input.components.length + 1;
        this.state.input.components.push({
            id: 'component_' + index,
            label: 'Komponente ' + index,
            min: 0,
            max: 10,
            step: 0.5,
            weight: 1
        });
        this.renderStructure();
        this.updateTextarea();
    };

    ScoringDesigner.prototype.addPenalty = function () {
        if (!Array.isArray(this.state.penalties)) {
            this.state.penalties = [];
        }
        var index = this.state.penalties.length + 1;
        this.state.penalties.push({
            id: 'penalty_' + index,
            label: 'Penalty ' + index,
            when: '',
            points: '1'
        });
        this.renderStructure();
        this.updateTextarea();
    };

    ScoringDesigner.prototype.addTiebreak = function () {
        if (!Array.isArray(this.state.ranking.tiebreak_chain)) {
            this.state.ranking.tiebreak_chain = [];
        }
        this.state.ranking.tiebreak_chain.push('random_draw');
        this.renderStructure();
        this.updateTextarea();
    };

    ScoringDesigner.prototype.removeItem = function (listName, index) {
        if (typeof index !== 'number' || index < 0) {
            return;
        }
        if (listName === 'fields' && Array.isArray(this.state.input.fields)) {
            this.state.input.fields.splice(index, 1);
        } else if (listName === 'components' && Array.isArray(this.state.input.components)) {
            this.state.input.components.splice(index, 1);
        } else if (listName === 'penalties' && Array.isArray(this.state.penalties)) {
            this.state.penalties.splice(index, 1);
        } else if (listName === 'tiebreakers' && Array.isArray(this.state.ranking.tiebreak_chain)) {
            this.state.ranking.tiebreak_chain.splice(index, 1);
        }
        this.renderStructure();
        this.updateTextarea();
    };

    ScoringDesigner.prototype.loadPreset = function (name) {
        if (!name || !this.presets || typeof this.presets !== 'object') {
            return;
        }
        var preset = this.presets[name];
        if (!preset || typeof preset !== 'object') {
            return;
        }
        this.state = mergeWithDefaults(this.defaults, preset);
        stripLessonsSection(this.state);
        this.renderStructure();
        this.updateTextarea();
    };

    ScoringDesigner.prototype.resetDefaults = function () {
        this.state = deepClone(this.defaults);
        stripLessonsSection(this.state);
        this.renderStructure();
        this.updateTextarea();
    };

    window.document.addEventListener('DOMContentLoaded', function () {
        window.document.querySelectorAll('[data-scoring-designer]').forEach(function (element) {
            new ScoringDesigner(element);
        });
    });
})(window);
