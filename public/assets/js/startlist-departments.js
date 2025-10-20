(function () {
    'use strict';

    const onReady = () => {
        const board = window.document.querySelector('[data-department-board]');
        if (!board) {
            return;
        }

        const classId = parseInt(board.dataset.classId || '0', 10);
        const updateUrl = board.dataset.updateUrl || 'startlist_departments.php';
        const csrfToken = board.dataset.csrf || '';
        const promptCreate = board.dataset.promptCreate || 'Neuen Namen eingeben';
        const promptRename = board.dataset.promptRename || 'Neuen Namen eingeben';
        const confirmDelete = board.dataset.confirmDelete || 'Wirklich löschen?';
        const errorGeneric = board.dataset.errorGeneric || 'Aktion fehlgeschlagen.';

        const createButton = window.document.querySelector('[data-action="create-department"]');
        if (createButton) {
            createButton.addEventListener('click', async () => {
                const label = window.prompt(promptCreate);
                if (!label || !label.trim()) {
                    return;
                }
                try {
                    await postJson({ action: 'create', label: label.trim() });
                    window.location.reload();
                } catch (error) {
                    showError(error);
                }
            });
        }

        board.querySelectorAll('[data-action="rename-department"]').forEach((button) => {
            button.addEventListener('click', async (event) => {
                const departmentId = parseInt(event.currentTarget.getAttribute('data-department-id') || '0', 10);
                if (!departmentId) {
                    return;
                }
                const currentTitle = event.currentTarget.closest('[data-department-id]')?.querySelector('[data-department-title]')?.textContent || '';
                const label = window.prompt(promptRename, currentTitle);
                if (label === null) {
                    return;
                }
                if (!label.trim()) {
                    showError(new Error(errorGeneric));
                    return;
                }
                try {
                    await postJson({ action: 'rename', department_id: departmentId, label: label.trim() });
                    window.location.reload();
                } catch (error) {
                    showError(error);
                }
            });
        });

        board.querySelectorAll('[data-action="delete-department"]').forEach((button) => {
            button.addEventListener('click', async (event) => {
                const departmentId = parseInt(event.currentTarget.getAttribute('data-department-id') || '0', 10);
                if (!departmentId) {
                    return;
                }
                if (!window.confirm(confirmDelete)) {
                    return;
                }
                try {
                    await postJson({ action: 'delete', department_id: departmentId });
                    window.location.reload();
                } catch (error) {
                    showError(error);
                }
            });
        });

        const lists = board.querySelectorAll('.startlist-department-list');
        const items = board.querySelectorAll('.startlist-department-item');
        const columns = Array.from(board.querySelectorAll('.startlist-department-column[data-department-id]')).filter((column) => {
            const id = column.getAttribute('data-department-id') || '';
            return id !== '' && id !== '0';
        });
        const columnDragType = 'text/x-startlist-department-column';
        let draggedColumn = null;

        const clearColumnIndicators = () => {
            board.querySelectorAll('.startlist-department-column').forEach((column) => {
                column.classList.remove('is-column-drop-target');
                column.classList.remove('is-column-dragging');
            });
        };

        columns.forEach((column) => {
            column.setAttribute('draggable', 'true');
            column.addEventListener('dragstart', (event) => {
                if (event.target && event.target.closest('.startlist-department-item')) {
                    return;
                }
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData(columnDragType, column.getAttribute('data-department-id') || '');
                }
                draggedColumn = column;
                column.classList.add('is-column-dragging');
            });
            column.addEventListener('dragend', () => {
                if (draggedColumn === column) {
                    draggedColumn = null;
                }
                clearColumnIndicators();
            });
        });

        board.addEventListener('dragover', (event) => {
            if (!draggedColumn) {
                return;
            }
            const targetColumn = event.target.closest('.startlist-department-column[data-department-id]');
            if (!targetColumn || targetColumn === draggedColumn) {
                return;
            }
            event.preventDefault();
            targetColumn.classList.add('is-column-drop-target');
        });

        board.addEventListener('dragleave', (event) => {
            if (!draggedColumn) {
                return;
            }
            const targetColumn = event.target.closest('.startlist-department-column[data-department-id]');
            if (!targetColumn || targetColumn === draggedColumn) {
                return;
            }
            targetColumn.classList.remove('is-column-drop-target');
        });

        board.addEventListener('drop', async (event) => {
            if (!draggedColumn) {
                return;
            }
            const targetColumn = event.target.closest('.startlist-department-column[data-department-id]');
            clearColumnIndicators();
            if (!targetColumn || targetColumn === draggedColumn) {
                return;
            }
            const container = targetColumn.parentElement;
            if (!container) {
                draggedColumn = null;
                return;
            }
            event.preventDefault();
            const rect = targetColumn.getBoundingClientRect();
            const insertAfter = event.clientX > rect.left + rect.width / 2;
            if (insertAfter) {
                container.insertBefore(draggedColumn, targetColumn.nextSibling);
            } else {
                container.insertBefore(draggedColumn, targetColumn);
            }
            const order = Array.from(container.querySelectorAll('.startlist-department-column[data-department-id]'))
                .map((element) => parseInt(element.getAttribute('data-department-id') || '0', 10))
                .filter((id) => id > 0);
            try {
                await postJson({ action: 'reorder', order });
            } catch (error) {
                showError(error);
                window.location.reload();
            }
            draggedColumn = null;
        });

        const clearDropIndicators = () => {
            lists.forEach((list) => list.classList.remove('is-drop-target'));
            items.forEach((item) => item.classList.remove('is-dragging'));
        };

        const getDragAfterElement = (container, y) => {
            const siblings = Array.from(container.querySelectorAll('.startlist-department-item:not(.is-dragging)'));
            let closest = { offset: Number.NEGATIVE_INFINITY, element: null };
            siblings.forEach((child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    closest = { offset, element: child };
                }
            });
            return closest.element;
        };

        lists.forEach((list) => {
            list.addEventListener('dragover', (event) => {
                event.preventDefault();
                list.classList.add('is-drop-target');
            });
            list.addEventListener('dragleave', () => {
                list.classList.remove('is-drop-target');
            });
            list.addEventListener('drop', async (event) => {
                event.preventDefault();
                const itemId = event.dataTransfer?.getData('text/plain');
                if (!itemId) {
                    clearDropIndicators();
                    return;
                }
                const item = board.querySelector(`.startlist-department-item[data-item-id="${itemId}"]`);
                if (!item) {
                    clearDropIndicators();
                    return;
                }
                const sourceList = item.closest('.startlist-department-list');
                const afterElement = getDragAfterElement(list, event.clientY);
                if (afterElement) {
                    list.insertBefore(item, afterElement);
                } else {
                    list.appendChild(item);
                }
                updateColumnSummary(list.closest('[data-department-id]'));
                if (sourceList && sourceList !== list) {
                    updateColumnSummary(sourceList.closest('[data-department-id]'));
                }
                const targetDepartment = list.getAttribute('data-department-id');
                const departmentId = targetDepartment !== '' ? parseInt(targetDepartment || '0', 10) : null;
                const order = Array.from(list.querySelectorAll('.startlist-department-item'))
                    .map((element) => parseInt(element.getAttribute('data-item-id') || '0', 10))
                    .filter((idValue) => idValue > 0);
                clearDropIndicators();
                const isReassignment = sourceList && sourceList !== list;
                try {
                    if (isReassignment) {
                        await postJson({
                            action: 'assign',
                            department_id: departmentId,
                            item_ids: [parseInt(itemId, 10)],
                            order,
                        });
                    } else {
                        await postJson({
                            action: 'reorder_members',
                            department_id: departmentId,
                            order,
                        });
                    }
                    window.location.reload();
                } catch (error) {
                    showError(error);
                    window.location.reload();
                }
            });
        });

        items.forEach((item) => {
            item.addEventListener('dragstart', (event) => {
                event.dataTransfer?.setData('text/plain', item.getAttribute('data-item-id') || '');
                event.stopPropagation();
                item.classList.add('is-dragging');
            });
            item.addEventListener('dragend', (event) => {
                event.stopPropagation();
                clearDropIndicators();
            });
        });

        function updateColumnSummary(column) {
            if (!column) {
                return;
            }
            const list = column.querySelector('.startlist-department-list');
            const items = list ? list.querySelectorAll('.startlist-department-item').length : 0;
            const badge = column.querySelector('.badge');
            if (badge) {
                badge.textContent = String(items);
            }
            const empty = column.querySelector('.startlist-department-empty');
            if (empty) {
                if (items === 0) {
                    empty.removeAttribute('hidden');
                } else {
                    empty.setAttribute('hidden', 'hidden');
                }
            }
        }

        async function postJson(payload) {
            const body = { ...payload, class_id: classId, _token: csrfToken };
            const response = await window.fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });
            let data = null;
            try {
                data = await response.json();
            } catch (error) {
                data = null;
            }
            if (!response.ok || !data || data.success !== true) {
                const message = data && data.message ? data.message : errorGeneric;
                throw new Error(message);
            }
            return data;
        }

        function showError(error) {
            const message = error instanceof Error ? error.message : errorGeneric;
            window.alert(message);
        }
    };

    window.document.addEventListener('DOMContentLoaded', onReady);
})();
