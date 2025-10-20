(function () {
    var modalElement = document.getElementById('start-number-edit-modal');
    if (!modalElement || !window.bootstrap || !window.bootstrap.Modal) {
        return;
    }

    var modal = new window.bootstrap.Modal(modalElement);
    var itemInput = modalElement.querySelector('input[name="item_id"]');
    var numberInput = modalElement.querySelector('input[name="start_number_raw"]');
    var currentDisplay = modalElement.querySelector('[data-current-number]');

    var openModal = function (trigger) {
        if (!itemInput || !numberInput) {
            return;
        }
        var itemId = trigger.getAttribute('data-item-id');
        var raw = trigger.getAttribute('data-start-number-raw') || '';
        var formatted = trigger.getAttribute('data-start-number') || '';

        itemInput.value = itemId || '';
        numberInput.value = raw || formatted.replace(/\D+/g, '');
        if (currentDisplay) {
            currentDisplay.textContent = formatted || '–';
        }

        modal.show();
    };

    modalElement.addEventListener('shown.bs.modal', function () {
        if (numberInput) {
            numberInput.focus();
            numberInput.select();
        }
    });

    document.querySelectorAll('[data-start-number-edit]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            openModal(trigger);
        });
        trigger.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openModal(trigger);
            }
        });
    });
})();
