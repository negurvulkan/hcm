(function () {
    'use strict';

    const modalElement = document.getElementById('entity-info-modal');
    if (!modalElement || !window.bootstrap) {
        return;
    }

    const modalInstance = new window.bootstrap.Modal(modalElement);
    const titleElement = modalElement.querySelector('[data-entity-info-title]');
    const bodyElement = modalElement.querySelector('[data-entity-info-body]');
    const defaultEmptyMessage = modalElement.getAttribute('data-entity-info-empty') || '';

    const parsePayload = (raw) => {
        if (!raw) {
            return null;
        }
        try {
            return JSON.parse(raw);
        } catch (error) {
            console.warn('Failed to parse entity info payload', error);
            return null;
        }
    };

    const renderMultiline = (value, container) => {
        const lines = String(value).split(/\r?\n/);
        lines.forEach((line, index) => {
            if (index > 0) {
                container.appendChild(document.createElement('br'));
            }
            container.appendChild(document.createTextNode(line));
        });
    };

    const renderField = (field) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-3';

        const label = document.createElement('div');
        label.className = 'text-muted small text-uppercase';
        label.textContent = field.label || '';
        wrapper.appendChild(label);

        const valueElement = document.createElement('div');
        valueElement.className = 'fw-semibold';
        const value = field.value ?? '';
        if (field.multiline) {
            renderMultiline(value, valueElement);
        } else {
            valueElement.textContent = String(value);
        }
        wrapper.appendChild(valueElement);

        return wrapper;
    };

    const showModal = (payload) => {
        if (!payload) {
            return;
        }

        if (titleElement) {
            titleElement.textContent = payload.title || '';
        }

        if (bodyElement) {
            bodyElement.innerHTML = '';
            const fields = Array.isArray(payload.fields) ? payload.fields : [];
            if (fields.length === 0) {
                const emptyMessage = payload.emptyMessage || defaultEmptyMessage;
                const emptyParagraph = document.createElement('p');
                emptyParagraph.className = 'text-muted mb-0';
                emptyParagraph.textContent = emptyMessage || '';
                bodyElement.appendChild(emptyParagraph);
            } else {
                fields.forEach((field) => {
                    if (!field || (field.value ?? '') === '') {
                        return;
                    }
                    bodyElement.appendChild(renderField(field));
                });
            }
        }

        modalInstance.show();
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-entity-info]');
        if (!trigger) {
            return;
        }
        event.preventDefault();
        const payload = parsePayload(trigger.getAttribute('data-entity-info'));
        showModal(payload);
    });
})();
