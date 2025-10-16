(function (window, $) {
    'use strict';

    const state = {
        lastEventId: null,
        interval: null
    };

    const render = (payload) => {
        const $ticker = $('[data-ticker]');
        if (!$ticker.length) {
            return;
        }
        if (payload.type === 'schedule_shift') {
            $ticker.find('[data-ticker-shift]').text(payload.message);
        }
        if (payload.type === 'next_starter') {
            $ticker.find('[data-ticker-current]').text(payload.current || '');
            $ticker.find('[data-ticker-upcoming]').text(payload.upcoming.join(', '));
        }
        if (payload.type === 'results_release') {
            $ticker.find('[data-ticker-result]').text(payload.message);
        }
    };

    const poll = () => {
        AppHelpers.ajax({
            url: 'notify.php',
            method: 'GET',
            data: state.lastEventId ? { after: state.lastEventId } : {},
            success: (response) => {
                if (!response || !Array.isArray(response.events)) {
                    return;
                }
                response.events.forEach((event) => {
                    state.lastEventId = event.id;
                    render(event);
                    $(document).trigger('ticker:event', event);
                });
            }
        });
    };

    const start = () => {
        if (state.interval) {
            window.clearInterval(state.interval);
        }
        poll();
        state.interval = window.setInterval(poll, 15000);
    };

    $(start);
})(window, window.jQuery);
