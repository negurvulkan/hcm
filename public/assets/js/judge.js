(function (window, document) {
    'use strict';

    const EPSILON = 0.0001;

    const parseNumber = (value) => {
        if (typeof value === 'number') {
            return Number.isFinite(value) ? value : null;
        }
        if (typeof value !== 'string') {
            return null;
        }
        const normalized = value.replace(',', '.').trim();
        if (!normalized) {
            return null;
        }
        const parsed = Number.parseFloat(normalized);
        return Number.isFinite(parsed) ? parsed : null;
    };

    const formatNumber = (value, decimals) => {
        if (!Number.isFinite(value)) {
            return '';
        }
        if (typeof decimals === 'number' && decimals >= 0) {
            let text = value.toFixed(decimals);
            if (decimals > 0) {
                text = text.replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
            }
            return text;
        }
        return String(value);
    };

    const widgetPrecision = (widget) => {
        const attr = widget.getAttribute('data-score-precision');
        if (!attr) {
            return null;
        }
        const parsed = Number.parseInt(attr, 10);
        return Number.isNaN(parsed) ? null : parsed;
    };

    const secondsToClock = (value) => {
        if (!Number.isFinite(value)) {
            return '';
        }
        const totalMs = Math.round(Math.max(0, value) * 1000);
        const minutes = Math.floor(totalMs / 60000);
        let remaining = totalMs - minutes * 60000;
        const seconds = Math.floor(remaining / 1000);
        remaining -= seconds * 1000;
        let result = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        if (remaining > 0) {
            let fraction = remaining.toString().padStart(3, '0');
            fraction = fraction.replace(/0+$/, '');
            if (fraction) {
                result += `.${fraction}`;
            }
        }
        return result;
    };

    const clockToSeconds = (raw) => {
        if (typeof raw !== 'string') {
            return null;
        }
        const trimmed = raw.trim();
        if (!trimmed) {
            return null;
        }
        const normalized = trimmed.replace(',', '.');
        if (/^\d+(?:\.\d+)?$/.test(normalized)) {
            const seconds = Number.parseFloat(normalized);
            return Number.isFinite(seconds) ? seconds : null;
        }
        const match = normalized.match(/^(\d+):(\d{1,2})(?:\.(\d+))?$/);
        if (!match) {
            return null;
        }
        const minutes = Number.parseInt(match[1], 10);
        const secondsPart = Number.parseInt(match[2], 10);
        if (!Number.isFinite(minutes) || !Number.isFinite(secondsPart) || secondsPart >= 60) {
            return null;
        }
        let fraction = 0;
        if (match[3]) {
            const fractionText = `0.${match[3]}`;
            const parsedFraction = Number.parseFloat(fractionText);
            if (Number.isFinite(parsedFraction)) {
                fraction = parsedFraction;
            }
        }
        return minutes * 60 + secondsPart + fraction;
    };

    const ready = (fn) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    };

    const initScaleWidget = (widget) => {
        const manual = widget.querySelector('[data-score-manual]');
        if (!manual) {
            return;
        }
        const hidden = widget.querySelector('[data-score-value]');
        const options = Array.from(widget.querySelectorAll('[data-score-option]'));
        const precision = widgetPrecision(widget);
        const applyValue = (numeric) => {
            if (numeric === null || !Number.isFinite(numeric)) {
                if (hidden) {
                    hidden.value = '';
                }
                manual.value = '';
                manual.classList.remove('is-invalid');
                options.forEach((option) => {
                    option.checked = false;
                });
                return;
            }
            const formatted = formatNumber(numeric, precision);
            if (hidden) {
                hidden.value = formatted;
            }
            manual.value = formatted;
            manual.classList.remove('is-invalid');
            options.forEach((option) => {
                const optionValue = parseNumber(option.value);
                const matches = optionValue !== null && Math.abs(optionValue - numeric) < EPSILON;
                option.checked = matches;
            });
        };

        const initial = (hidden ? parseNumber(hidden.value) : null) ?? parseNumber(manual.value);
        if (initial !== null) {
            applyValue(initial);
        }

        options.forEach((option) => {
            option.addEventListener('change', () => {
                if (!option.checked) {
                    return;
                }
                const numeric = parseNumber(option.value);
                if (numeric !== null) {
                    applyValue(numeric);
                }
            });
        });

        manual.addEventListener('input', () => {
            manual.classList.remove('is-invalid');
        });

        manual.addEventListener('change', () => {
            const numeric = parseNumber(manual.value);
            if (numeric === null) {
                if (manual.value.trim() === '') {
                    applyValue(null);
                } else {
                    manual.classList.add('is-invalid');
                    if (hidden) {
                        hidden.value = '';
                    }
                    options.forEach((option) => {
                        option.checked = false;
                    });
                }
                return;
            }
            applyValue(numeric);
        });

        manual.addEventListener('blur', () => {
            const numeric = parseNumber(manual.value);
            if (numeric !== null) {
                applyValue(numeric);
            }
        });
    };

    const initCountWidget = (widget) => {
        const input = widget.querySelector('[data-score-count-input]');
        if (!input) {
            return;
        }
        const hidden = widget.querySelector('[data-score-value]');
        const precision = widgetPrecision(widget);
        const min = parseNumber(widget.getAttribute('data-score-min')) ?? parseNumber(input.getAttribute('min'));
        const max = parseNumber(widget.getAttribute('data-score-max')) ?? parseNumber(input.getAttribute('max'));
        const step = Math.abs(parseNumber(widget.getAttribute('data-score-step')) ?? parseNumber(input.getAttribute('step')) ?? 1) || 1;
        const clamp = (numeric) => {
            let value = numeric;
            if (min !== null && value < min) {
                value = min;
            }
            if (max !== null && value > max) {
                value = max;
            }
            return value;
        };
        const applyValue = (numeric) => {
            if (numeric === null || !Number.isFinite(numeric)) {
                if (hidden) {
                    hidden.value = '';
                }
                input.value = '';
                input.classList.remove('is-invalid');
                return;
            }
            const formatted = formatNumber(numeric, precision);
            if (hidden) {
                hidden.value = formatted;
            }
            input.value = formatted;
            input.classList.remove('is-invalid');
        };
        const initial = (hidden ? parseNumber(hidden.value) : null) ?? parseNumber(input.value);
        if (initial !== null) {
            applyValue(clamp(initial));
        }
        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
        });
        input.addEventListener('change', () => {
            const numeric = parseNumber(input.value);
            if (numeric === null) {
                if (input.value.trim() === '') {
                    applyValue(null);
                } else {
                    input.classList.add('is-invalid');
                    if (hidden) {
                        hidden.value = '';
                    }
                }
                return;
            }
            applyValue(clamp(numeric));
        });
        const down = widget.querySelector('[data-score-count-down]');
        const up = widget.querySelector('[data-score-count-up]');
        const adjust = (direction) => {
            const current = (hidden ? parseNumber(hidden.value) : null) ?? parseNumber(input.value) ?? 0;
            const next = clamp(current + direction * step);
            applyValue(next);
        };
        if (down) {
            down.addEventListener('click', () => adjust(-1));
        }
        if (up) {
            up.addEventListener('click', () => adjust(1));
        }
    };

    const initTimeWidget = (widget) => {
        const hidden = widget.querySelector('[data-score-value]');
        const input = widget.querySelector('[data-score-time-input]');
        if (!hidden || !input) {
            return;
        }
        const precision = widgetPrecision(widget) ?? 3;
        const applyValue = (numeric) => {
            if (numeric === null || !Number.isFinite(numeric)) {
                hidden.value = '';
                input.value = '';
                input.classList.remove('is-invalid');
                return;
            }
            const formatted = formatNumber(numeric, precision);
            hidden.value = formatted;
            input.value = secondsToClock(numeric);
            input.classList.remove('is-invalid');
        };
        const initial = parseNumber(hidden.value);
        if (initial !== null) {
            input.value = secondsToClock(initial);
        } else if (input.value.trim() !== '') {
            const parsed = clockToSeconds(input.value);
            if (parsed !== null) {
                applyValue(parsed);
            }
        }
        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
            if (input.value.trim() === '') {
                hidden.value = '';
            }
        });
        input.addEventListener('change', () => {
            const numeric = clockToSeconds(input.value);
            if (numeric === null) {
                if (input.value.trim() === '') {
                    hidden.value = '';
                    input.value = '';
                    input.classList.remove('is-invalid');
                } else {
                    input.classList.add('is-invalid');
                }
                return;
            }
            applyValue(numeric);
        });
        input.addEventListener('blur', () => {
            const numeric = clockToSeconds(input.value);
            if (numeric !== null) {
                applyValue(numeric);
            }
        });
    };

    const initScoreWidgets = () => {
        const widgets = document.querySelectorAll('[data-score-widget]');
        widgets.forEach((widget) => {
            const type = (widget.getAttribute('data-score-type') || 'default').toLowerCase();
            switch (type) {
                case 'scale':
                case 'delta':
                    initScaleWidget(widget);
                    break;
                case 'count':
                    initCountWidget(widget);
                    break;
                case 'time':
                    initTimeWidget(widget);
                    break;
                default:
                    break;
            }
        });
    };

    const initAutosave = () => {
        const forms = document.querySelectorAll('[data-autosave]');
        forms.forEach((form) => {
            if (window.AppHelpers && window.AppHelpers.markDirty && window.jQuery) {
                window.AppHelpers.markDirty(window.jQuery(form));
            }
        });
    };

    const initStartFilters = () => {
        const filterGroup = document.querySelector('[data-start-filter-group]');
        const buttonContainer = document.querySelector('[data-start-buttons]');
        if (!filterGroup || !buttonContainer) {
            return;
        }
        const buttons = Array.from(filterGroup.querySelectorAll('[data-start-filter]'));
        const starters = Array.from(buttonContainer.querySelectorAll('[data-start-state]'));
        const matchesFilter = (state, filter) => {
            if (filter === 'all') {
                return true;
            }
            if (filter === 'pending') {
                return state === 'scheduled';
            }
            return state === filter;
        };
        filterGroup.addEventListener('click', (event) => {
            const target = event.target.closest('[data-start-filter]');
            if (!target) {
                return;
            }
            event.preventDefault();
            const value = target.getAttribute('data-start-filter') || 'all';
            buttons.forEach((button) => button.classList.toggle('active', button === target));
            starters.forEach((starter) => {
                const state = starter.getAttribute('data-start-state') || 'scheduled';
                const shouldShow = matchesFilter(state, value);
                starter.classList.toggle('d-none', !shouldShow);
            });
        });
    };

    const initSignatureStatus = () => {
        const checkbox = document.getElementById('sign');
        const status = document.querySelector('[data-signature-status]');
        if (!checkbox || !status) {
            return;
        }
        const draft = status.getAttribute('data-status-draft') || '';
        const signed = status.getAttribute('data-status-signed') || '';
        const update = () => {
            status.textContent = checkbox.checked ? signed : draft;
        };
        checkbox.addEventListener('change', update);
        update();
    };

    ready(() => {
        initAutosave();
        initStartFilters();
        initSignatureStatus();
        initScoreWidgets();
    });
})(window, window.document);
