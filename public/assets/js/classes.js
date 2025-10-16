(function (window, $) {
    'use strict';

    $(function () {
        const $form = $('[data-class-form]');
        if (!$form.length) {
            return;
        }
        const presets = $form.data('presets') || {};
        $form.find('[data-preset]').on('click', function (event) {
            event.preventDefault();
            const key = $(this).data('preset');
            if (!presets[key]) {
                return;
            }
            $form.find('[name="rules_json"]').val(JSON.stringify(presets[key], null, 2)).trigger('input');
        });
    });
})(window, window.jQuery);
