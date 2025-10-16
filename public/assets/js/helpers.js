(function (window, $) {
    'use strict';

    const debounce = (fn, wait = 400) => {
        let timeout;
        return function (...args) {
            window.clearTimeout(timeout);
            timeout = window.setTimeout(() => fn.apply(this, args), wait);
        };
    };

    const ajax = (options) => {
        const defaults = {
            method: 'POST',
            dataType: 'json',
            error: (xhr) => console.error('AJAX error', xhr)
        };
        return $.ajax({ ...defaults, ...options });
    };

    const markDirty = ($form) => {
        const onChange = () => $form.data('dirty', true);
        $form.find('input,select,textarea').on('change input', debounce(onChange, 200));
        window.addEventListener('beforeunload', (event) => {
            if ($form.data('dirty')) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    };

    const initDateTimePickers = () => {
        const containers = window.document.querySelectorAll('[data-datetime-picker]');
        containers.forEach((container) => {
            if (container.dataset.enhanced === 'true') {
                return;
            }

            const original = container.querySelector('input[name]');
            if (!original) {
                return;
            }

            container.dataset.enhanced = 'true';

            const hidden = original;
            hidden.classList.remove('form-control');
            hidden.type = 'hidden';
            hidden.setAttribute('data-datetime-storage', 'true');

            const group = window.document.createElement('div');
            group.className = 'input-group';

            const dateInput = window.document.createElement('input');
            dateInput.type = 'date';
            dateInput.className = 'form-control';
            const dateLabel = hidden.getAttribute('aria-label') || (window.I18n ? window.I18n.t('forms.date') : 'Datum');
            dateInput.setAttribute('aria-label', dateLabel);

            const timeInput = window.document.createElement('input');
            timeInput.type = 'time';
            timeInput.className = 'form-control';
            timeInput.step = hidden.getAttribute('step') || '60';
            const timeLabel = hidden.getAttribute('aria-label') || (window.I18n ? window.I18n.t('forms.time') : 'Uhrzeit');
            timeInput.setAttribute('aria-label', timeLabel);

            group.append(dateInput);
            group.append(timeInput);
            container.append(group);

            const sync = () => {
                if (dateInput.value && timeInput.value) {
                    hidden.value = `${dateInput.value}T${timeInput.value}`;
                } else if (dateInput.value) {
                    hidden.value = dateInput.value;
                } else {
                    hidden.value = '';
                }
            };

            const populate = () => {
                if (!hidden.value) {
                    return;
                }
                const [datePart, timePart] = hidden.value.split('T');
                if (datePart) {
                    dateInput.value = datePart;
                }
                if (timePart) {
                    timeInput.value = timePart.slice(0, 5);
                }
            };

            populate();

            dateInput.addEventListener('change', sync);
            timeInput.addEventListener('change', sync);
            dateInput.addEventListener('input', sync);
            timeInput.addEventListener('input', sync);
        });
    };

    window.document.addEventListener('DOMContentLoaded', initDateTimePickers);

    window.AppHelpers = { debounce, ajax, markDirty, initDateTimePickers };
})(window, window.jQuery);
