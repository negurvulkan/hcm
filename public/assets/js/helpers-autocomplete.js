(function (window, document) {
    'use strict';

    const collectOptions = (listElement) => {
        if (!listElement) {
            return [];
        }
        return Array.prototype.slice.call(listElement.options || []);
    };

    const findOptionByValue = (listElement, value) => {
        if (!value) {
            return null;
        }
        const normalized = value.trim().toLowerCase();
        if (normalized === '') {
            return null;
        }
        return collectOptions(listElement).find((option) => option.value.trim().toLowerCase() === normalized) || null;
    };

    const findOptionById = (listElement, id) => {
        if (!id) {
            return null;
        }
        return collectOptions(listElement).find((option) => (option.dataset.id || '') === String(id)) || null;
    };

    const updateHiddenValue = (input, listElement, hidden) => {
        const option = findOptionByValue(listElement, input.value);
        if (option && option.dataset.id) {
            hidden.value = option.dataset.id;
        } else {
            hidden.value = '';
        }
    };

    const hydrateInputFromHidden = (input, listElement, hidden) => {
        if (hidden.value === '') {
            if (input.value && !findOptionByValue(listElement, input.value)) {
                input.value = '';
            }
            return;
        }
        const option = findOptionById(listElement, hidden.value);
        if (option && option.value) {
            input.value = option.value;
        }
    };

    const init = () => {
        const inputs = document.querySelectorAll('[data-autocomplete-target]');
        inputs.forEach((input) => {
            if (input.dataset.autocompleteInitialised === 'true') {
                return;
            }
            const targetId = input.getAttribute('data-autocomplete-target');
            if (!targetId) {
                return;
            }
            const hidden = document.getElementById(targetId);
            if (!hidden) {
                return;
            }
            const listId = input.getAttribute('list');
            if (!listId) {
                return;
            }
            const listElement = document.getElementById(listId);
            if (!listElement) {
                return;
            }

            input.dataset.autocompleteInitialised = 'true';
            hydrateInputFromHidden(input, listElement, hidden);

            input.addEventListener('input', () => {
                updateHiddenValue(input, listElement, hidden);
            });

            input.addEventListener('change', () => {
                updateHiddenValue(input, listElement, hidden);
            });

            input.addEventListener('blur', () => {
                if (!hidden.value) {
                    const option = findOptionByValue(listElement, input.value);
                    if (!option) {
                        input.value = '';
                    }
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', init);
})(window, window.document);
