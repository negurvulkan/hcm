(function () {
    'use strict';

    const ordersEqual = (a, b) => {
        if (a.length !== b.length) {
            return false;
        }
        for (let i = 0; i < a.length; i += 1) {
            if (a[i] !== b[i]) {
                return false;
            }
        }
        return true;
    };

    const getOrder = (container) => Array.from(container.querySelectorAll('tr[data-startlist-item]'))
        .map((row) => parseInt(row.getAttribute('data-startlist-item') || '0', 10))
        .filter((id) => id > 0);

    const refreshPositions = (container) => {
        const rows = Array.from(container.querySelectorAll('tr[data-startlist-item]'));
        rows.forEach((row, index) => {
            const position = index + 1;
            const value = row.querySelector('[data-position-value]');
            if (value) {
                value.textContent = String(position);
            }
        });
    };

    const getDragAfterElement = (container, y) => {
        const rows = Array.from(container.querySelectorAll('tr[data-startlist-item]:not(.is-dragging)'));
        let closest = { offset: Number.NEGATIVE_INFINITY, element: null };
        rows.forEach((row) => {
            const box = row.getBoundingClientRect();
            const offset = y - box.top - (box.height / 2);
            if (offset < 0 && offset > closest.offset) {
                closest = { offset, element: row };
            }
        });
        return closest.element;
    };

    const onReady = () => {
        const tables = window.document.querySelectorAll('[data-startlist-table]');
        tables.forEach((table) => {
            const reorderUrl = table.getAttribute('data-reorder-url') || 'startlist_reorder.php';
            const classId = parseInt(table.getAttribute('data-class-id') || '0', 10);
            const csrfToken = table.getAttribute('data-csrf') || '';
            const errorGeneric = table.getAttribute('data-error-generic') || 'Reorder failed.';

            if (!classId || !csrfToken) {
                return;
            }

            const handles = table.querySelectorAll('[data-drag-handle]');
            if (handles.length === 0) {
                return;
            }

            let draggingRow = null;
            let lastOrder = getOrder(table);
            let isSaving = false;

            handles.forEach((handle) => {
                handle.setAttribute('draggable', 'true');
                handle.addEventListener('dragstart', (event) => {
                    const row = handle.closest('tr[data-startlist-item]');
                    if (!row) {
                        return;
                    }
                    if (isSaving) {
                        event.preventDefault();
                        return;
                    }
                    draggingRow = row;
                    row.classList.add('is-dragging');
                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', row.getAttribute('data-startlist-item') || '');
                    }
                    event.stopPropagation();
                });
                handle.addEventListener('dragend', async (event) => {
                    event.stopPropagation();
                    if (!draggingRow) {
                        return;
                    }
                    const row = draggingRow;
                    row.classList.remove('is-dragging');
                    draggingRow = null;
                    const order = getOrder(table);
                    if (order.length <= 1 || ordersEqual(order, lastOrder)) {
                        refreshPositions(table);
                        return;
                    }
                    try {
                        isSaving = true;
                        table.classList.add('is-saving');
                        const response = await window.fetch(reorderUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'reorder',
                                class_id: classId,
                                order,
                                _token: csrfToken,
                            }),
                        });
                        const data = await response.json().catch(() => null);
                        if (!response.ok || !data || data.success !== true) {
                            const message = data && data.message ? data.message : errorGeneric;
                            throw new Error(message);
                        }
                        lastOrder = order;
                        refreshPositions(table);
                    } catch (error) {
                        const message = error instanceof Error ? error.message : errorGeneric;
                        window.alert(message);
                        window.location.reload();
                    } finally {
                        isSaving = false;
                        table.classList.remove('is-saving');
                    }
                });
            });

            table.addEventListener('dragover', (event) => {
                if (!draggingRow) {
                    return;
                }
                if (event.target && event.target.closest('button, a, input, textarea, select')) {
                    return;
                }
                event.preventDefault();
                const afterElement = getDragAfterElement(table, event.clientY);
                if (!afterElement) {
                    table.appendChild(draggingRow);
                } else if (afterElement !== draggingRow) {
                    table.insertBefore(draggingRow, afterElement);
                }
            });

            table.addEventListener('drop', (event) => {
                if (draggingRow) {
                    event.preventDefault();
                }
            });
        });
    };

    window.document.addEventListener('DOMContentLoaded', onReady);
})();
