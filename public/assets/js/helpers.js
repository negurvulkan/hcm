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

    window.AppHelpers = { debounce, ajax, markDirty };
})(window, window.jQuery);
