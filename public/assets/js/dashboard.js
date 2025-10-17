(function (window, document) {
    'use strict';

    const ready = (fn) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    };

    ready(() => {
        const filterGroup = document.querySelector('[data-tile-filter-group]');
        const grid = document.querySelector('[data-tile-grid]');
        if (!filterGroup || !grid) {
            return;
        }

        const buttons = Array.from(filterGroup.querySelectorAll('[data-tile-filter]'));
        const tiles = Array.from(grid.querySelectorAll('[data-tile-role]'));

        const applyFilter = (value) => {
            tiles.forEach((tile) => {
                const role = tile.getAttribute('data-tile-role');
                const shouldShow = value === 'all' || role === value;
                tile.classList.toggle('d-none', !shouldShow);
            });
        };

        filterGroup.addEventListener('click', (event) => {
            const target = event.target.closest('[data-tile-filter]');
            if (!target) {
                return;
            }
            event.preventDefault();
            const value = target.getAttribute('data-tile-filter');
            buttons.forEach((button) => {
                button.classList.toggle('active', button === target);
            });
            applyFilter(value || 'all');
        });
    });
})(window, window.document);
