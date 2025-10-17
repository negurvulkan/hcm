(function (window, document) {
    'use strict';

    const ready = (fn) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
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
    });
})(window, window.document);
