(function (window, document) {
    'use strict';

    const ready = (fn) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    };

    const initShiftPresets = () => {
        const form = document.querySelector('[data-shift-form]');
        if (!form) {
            return;
        }
        const input = form.querySelector('[data-shift-input]');
        const buttons = form.querySelectorAll('[data-shift-preset]');
        buttons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                if (!input) {
                    return;
                }
                const value = button.getAttribute('data-shift-preset');
                input.value = value || '';
                form.requestSubmit();
            });
        });
    };

    const initSlotEditors = () => {
        const editors = document.querySelectorAll('[data-slot-editor]');
        editors.forEach((editor) => {
            const view = editor.querySelector('[data-slot-view]');
            const form = editor.querySelector('[data-slot-form]');
            const editButton = editor.querySelector('[data-slot-edit]');
            const cancelButton = editor.querySelector('[data-slot-cancel]');
            const input = editor.querySelector('[data-slot-input]');
            const initialValue = editor.getAttribute('data-slot-initial') || '';
            if (!view || !form || !editButton || !cancelButton || !input) {
                return;
            }
            editButton.addEventListener('click', () => {
                view.classList.add('d-none');
                form.classList.remove('d-none');
                input.focus();
            });
            cancelButton.addEventListener('click', () => {
                input.value = initialValue;
                form.classList.add('d-none');
                view.classList.remove('d-none');
            });
        });
    };

    ready(() => {
        initShiftPresets();
        initSlotEditors();
    });
})(window, window.document);
