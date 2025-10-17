(function (window, document) {
    'use strict';

    const ready = (fn) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    };

    const enhanceSelectFilters = () => {
        const inputs = document.querySelectorAll('[data-select-filter]');
        inputs.forEach((input) => {
            const selector = input.getAttribute('data-select-filter');
            if (!selector) {
                return;
            }
            const target = document.querySelector(selector);
            if (!target) {
                return;
            }
            const options = Array.from(target.options);
            const resetHidden = () => {
                options.forEach((option) => {
                    option.hidden = false;
                });
            };
            const filter = () => {
                const value = input.value.trim().toLowerCase();
                if (!value) {
                    resetHidden();
                    return;
                }
                options.forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    const text = option.text.toLowerCase();
                    option.hidden = !text.includes(value);
                });
                const visibleSelected = options.find((option) => option.selected && !option.hidden);
                if (!visibleSelected) {
                    target.selectedIndex = 0;
                }
            };

            input.addEventListener('input', filter);
            input.addEventListener('change', filter);
        });
    };

    const initBulkActions = () => {
        const form = document.getElementById('entries-bulk-form');
        if (!form) {
            return;
        }
        const master = form.querySelector('[data-check-all]');
        const checkboxes = Array.from(form.querySelectorAll('[data-entry-checkbox]'));
        const counter = form.querySelector('[data-selection-counter]');
        const template = counter ? (counter.getAttribute('data-template') || '{count}') : '{count}';
        const buttons = Array.from(form.querySelectorAll('[data-bulk-button]'));

        const updateState = () => {
            const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
            if (counter) {
                counter.textContent = template.replace('{count}', selected.toString());
            }
            buttons.forEach((button) => {
                button.disabled = selected === 0;
            });
            if (master) {
                master.indeterminate = selected > 0 && selected < checkboxes.length;
                master.checked = selected > 0 && selected === checkboxes.length;
            }
        };

        if (master) {
            master.addEventListener('change', () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = master.checked;
                });
                updateState();
            });
        }

        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', updateState);
        });

        updateState();
    };

    ready(() => {
        enhanceSelectFilters();
        initBulkActions();
    });
})(window, window.document);
