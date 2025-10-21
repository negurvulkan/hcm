(function () {
    'use strict';

    const DEFAULT_CANVAS_WIDTH = 1920;
    const DEFAULT_CANVAS_HEIGHT = 1080;

    function deepClone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function generateId(prefix) {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return prefix + '-' + crypto.randomUUID();
        }
        return prefix + '-' + Math.random().toString(16).slice(2, 10);
    }

    function formatDateTime(value) {
        if (!value) {
            return '';
        }
        try {
            const date = new Date(value);
            return date.toLocaleString();
        } catch (error) {
            return value;
        }
    }

    class SignageApp {
        constructor(root, config) {
            this.root = root;
            this.config = config || {};
            this.layouts = Array.isArray(this.config.layouts) ? this.config.layouts : [];
            this.displays = Array.isArray(this.config.displays) ? this.config.displays : [];
            this.playlists = Array.isArray(this.config.playlists) ? this.config.playlists : [];
            this.csrfToken = this.config.csrfToken || null;
            this.apiEndpoint = this.config.apiEndpoint || 'signage_api.php';
            this.locale = this.config.locale || 'de';

            this.activeLayoutId = null;
            this.activeLayout = null;
            this.activeSceneId = null;
            this.selectedElementId = null;

            this.undoStack = [];
            this.redoStack = [];

            this.dragState = null;

            this.dom = {
                configNode: root,
                layoutList: document.querySelector('[data-signage-list]'),
                searchInput: document.querySelector('[data-signage-search]'),
                activeName: document.querySelector('[data-signage-active-name]'),
                activeMeta: document.querySelector('[data-signage-active-meta]'),
                status: document.querySelector('[data-signage-status]'),
                canvas: document.querySelector('[data-signage-canvas]'),
                canvasInner: document.querySelector('[data-signage-canvas-inner]'),
                guides: document.querySelector('[data-signage-guides]'),
                timeline: document.querySelector('[data-signage-timeline]'),
                layers: document.querySelector('[data-signage-layers]'),
                bindings: document.querySelector('[data-signage-bindings]'),
                styles: document.querySelector('[data-signage-styles]'),
                palette: document.querySelector('[data-signage-palette]'),
                displays: document.querySelector('[data-signage-displays]'),
                playlists: document.querySelector('[data-signage-playlists]'),
                modal: document.getElementById('signageModal'),
                modalTitle: document.querySelector('[data-signage-modal-title]'),
                modalBody: document.querySelector('[data-signage-modal-body]'),
                modalSave: document.querySelector('[data-signage-modal-save]'),
            };

            this.bootstrapModal = this.dom.modal ? new window.bootstrap.Modal(this.dom.modal) : null;

            this.bindGlobalActions();
            this.renderLayoutList();
            this.renderDisplays();
            this.renderPlaylists();

            if (this.layouts.length > 0) {
                this.selectLayout(this.layouts[0].id);
            } else {
                this.updateStatus('');
            }
        }

        bindGlobalActions() {
            const actionButtons = document.querySelectorAll('[data-signage-action]');
            actionButtons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    const action = event.currentTarget.getAttribute('data-signage-action');
                    this.handleAction(action, event.currentTarget);
                });
            });

            if (this.dom.searchInput) {
                this.dom.searchInput.addEventListener('input', () => {
                    this.renderLayoutList(this.dom.searchInput.value);
                });
            }

            if (this.dom.palette) {
                this.dom.palette.addEventListener('click', (event) => {
                    const target = event.target.closest('button[data-element-type]');
                    if (!target) {
                        return;
                    }
                    const type = target.getAttribute('data-element-type');
                    this.addElement(type);
                });
            }

            if (this.dom.layers) {
                this.dom.layers.addEventListener('click', (event) => {
                    const target = event.target.closest('[data-layer-action]');
                    if (!target) {
                        return;
                    }
                    const elementId = target.getAttribute('data-element-id');
                    const action = target.getAttribute('data-layer-action');
                    if (action === 'select') {
                        this.selectElement(elementId);
                    } else if (action === 'delete') {
                        this.removeElement(elementId);
                    } else if (action === 'layer-up') {
                        this.bumpLayer(elementId, 1);
                    } else if (action === 'layer-down') {
                        this.bumpLayer(elementId, -1);
                    }
                });
            }

            if (this.dom.timeline) {
                this.dom.timeline.addEventListener('click', (event) => {
                    const sceneButton = event.target.closest('[data-scene-id]');
                    if (sceneButton) {
                        const sceneId = sceneButton.getAttribute('data-scene-id');
                        this.selectScene(sceneId);
                        return;
                    }
                    const actionButton = event.target.closest('[data-scene-action]');
                    if (actionButton) {
                        const sceneId = actionButton.getAttribute('data-scene-id');
                        const action = actionButton.getAttribute('data-scene-action');
                        if (action === 'delete') {
                            this.deleteScene(sceneId);
                        } else if (action === 'edit') {
                            this.editScene(sceneId);
                        }
                    }
                });
            }

            if (this.dom.styles) {
                this.dom.styles.addEventListener('input', (event) => {
                    const target = event.target;
                    const field = target.getAttribute('data-style-field');
                    if (!field) {
                        return;
                    }
                    this.updateElementStyle(field, target.value);
                });
            }

            if (this.dom.bindings) {
                this.dom.bindings.addEventListener('input', (event) => {
                    const target = event.target;
                    const field = target.getAttribute('data-binding-field');
                    if (!field) {
                        return;
                    }
                    this.updateElementBinding(field, target.value);
                });
            }

            if (this.dom.modalSave) {
                this.dom.modalSave.addEventListener('click', () => {
                    if (typeof this.modalSaveHandler === 'function') {
                        this.modalSaveHandler();
                    }
                });
            }

            document.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                    event.preventDefault();
                    this.saveLayout();
                }
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
                    event.preventDefault();
                    this.undo();
                }
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'y') {
                    event.preventDefault();
                    this.redo();
                }
            });
        }

        handleAction(action, button) {
            switch (action) {
                case 'create-layout':
                    this.promptCreateLayout();
                    break;
                case 'duplicate-layout':
                    this.duplicateLayout();
                    break;
                case 'preview-layout':
                    this.previewLayout();
                    break;
                case 'publish-layout':
                    this.publishLayout();
                    break;
                case 'delete-layout':
                    this.deleteLayout();
                    break;
                case 'save-layout':
                    this.saveLayout();
                    break;
                case 'create-display':
                    this.promptCreateDisplay();
                    break;
                case 'delete-display':
                    this.confirmDeleteDisplay(button?.getAttribute('data-display-id'));
                    break;
                case 'assign-layout':
                    this.assignLayoutToDisplay(button?.getAttribute('data-display-id'));
                    break;
                case 'create-playlist':
                    this.promptPlaylist();
                    break;
                case 'edit-playlist':
                    this.promptPlaylist(button?.getAttribute('data-playlist-id'));
                    break;
                case 'delete-playlist':
                    this.confirmDeletePlaylist(button?.getAttribute('data-playlist-id'));
                    break;
                case 'undo':
                    this.undo();
                    break;
                case 'redo':
                    this.redo();
                    break;
                case 'add-scene':
                    this.addScene();
                    break;
                default:
                    break;
            }
        }

        renderLayoutList(filterTerm = '') {
            if (!this.dom.layoutList) {
                return;
            }
            const fragment = document.createDocumentFragment();
            const normalized = filterTerm.trim().toLowerCase();
            const layouts = this.layouts.slice();
            layouts.sort((a, b) => new Date(b.updated_at || 0) - new Date(a.updated_at || 0));
            let matchCount = 0;

            layouts.forEach((layout) => {
                const matches = normalized === '' || (layout.name || '').toLowerCase().includes(normalized);
                if (!matches) {
                    return;
                }
                matchCount += 1;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'signage-layout-list__item';
                if (layout.id === this.activeLayoutId) {
                    button.classList.add('active');
                }
                button.dataset.layoutId = layout.id;
                button.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">${escapeHtml(layout.name || 'Layout')}</div>
                            <div class="text-muted small">${escapeHtml(this.translate('signage.layouts.updated', {
                                date: formatDateTime(layout.updated_at)
                            }))}</div>
                        </div>
                        <span class="badge ${layout.status === 'published' ? 'bg-success' : 'bg-secondary'}">${escapeHtml(this.translate('signage.status.' + (layout.status || 'draft')))}</span>
                    </div>
                `;
                button.addEventListener('click', () => this.selectLayout(layout.id));
                fragment.appendChild(button);
            });

            this.dom.layoutList.innerHTML = '';
            if (matchCount === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'text-center text-muted py-4';

                const message = document.createElement('p');
                message.className = 'mb-2';
                const messageKey = normalized === '' ? 'signage.layouts.empty' : 'signage.layouts.no_results';
                message.textContent = this.translate(messageKey);
                emptyState.appendChild(message);

                const actionButton = document.createElement('button');
                actionButton.type = 'button';
                actionButton.className = 'btn btn-sm btn-primary';
                actionButton.textContent = this.translate('signage.actions.new_layout');
                actionButton.addEventListener('click', () => this.promptCreateLayout());
                emptyState.appendChild(actionButton);

                this.dom.layoutList.appendChild(emptyState);
                return;
            }
            this.dom.layoutList.appendChild(fragment);
        }

        selectLayout(id) {
            const layout = this.layouts.find((item) => String(item.id) === String(id));
            if (!layout) {
                return;
            }
            this.activeLayoutId = layout.id;
            this.activeLayout = deepClone(layout);
            if (!Array.isArray(this.activeLayout.timeline) || this.activeLayout.timeline.length === 0) {
                this.activeLayout.timeline = [this.createDefaultScene()];
            }
            this.activeSceneId = this.activeLayout.timeline[0].id;
            this.selectedElementId = null;
            this.renderLayoutList(this.dom.searchInput ? this.dom.searchInput.value : '');
            this.renderActiveLayout();
            this.updateStatus(this.translate('signage.status.loaded', { name: layout.name || '' }));
        }

        renderActiveLayout() {
            if (!this.activeLayout) {
                return;
            }
            const activeScene = this.getActiveScene();
            if (activeScene) {
                this.activeSceneId = activeScene.id;
            }
            if (this.dom.activeName) {
                this.dom.activeName.textContent = this.activeLayout.name || this.translate('signage.designer.empty');
            }
            if (this.dom.activeMeta) {
                const updatedAt = this.activeLayout.updated_at || this.activeLayout.updatedAt;
                this.dom.activeMeta.textContent = this.translate('signage.designer.meta', {
                    version: this.activeLayout.version || 1,
                    updated: formatDateTime(updatedAt),
                });
            }
            this.renderCanvas();
            this.renderLayers();
            this.renderTimeline();
            this.renderBindings();
            this.renderStyles();
        }

        renderCanvas() {
            if (!this.dom.canvasInner || !this.activeLayout) {
                return;
            }
            this.dom.canvasInner.innerHTML = '';
            const elements = Array.isArray(this.activeLayout.elements) ? this.activeLayout.elements : [];
            const activeScene = this.getActiveScene();
            const restrictToScene = !!(activeScene && Array.isArray(activeScene.elementIds));
            const visibleIds = restrictToScene
                ? new Set(activeScene.elementIds.map((id) => String(id)))
                : null;
            elements.forEach((element) => {
                if (restrictToScene && visibleIds && !visibleIds.has(String(element.id))) {
                    return;
                }
                const node = document.createElement('div');
                node.dataset.signageElement = element.id;
                node.tabIndex = 0;
                this.positionElementNode(node, element);
                node.innerHTML = this.renderElementPreview(element);
                this.attachResizeHandle(node, element);
                if (element.id === this.selectedElementId) {
                    node.classList.add('is-active');
                }
                this.attachElementEvents(node, element);
                this.dom.canvasInner.appendChild(node);
            });
        }

        positionElementNode(node, element) {
            const position = element.position || {};
            const x = Math.max(0, Math.min(1, position.x ?? 0.1));
            const y = Math.max(0, Math.min(1, position.y ?? 0.1));
            const width = Math.max(0.05, Math.min(1 - x, position.width ?? 0.3));
            const height = Math.max(0.05, Math.min(1 - y, position.height ?? 0.1));
            node.style.left = `${x * 100}%`;
            node.style.top = `${y * 100}%`;
            node.style.width = `${width * 100}%`;
            node.style.height = `${height * 100}%`;
            element.position = Object.assign({}, element.position, { x, y, width, height });
        }

        renderElementPreview(element) {
            const type = element.type || 'text';
            const style = element.style || {};
            const content = element.content || {};
            if (type === 'text' || type === 'ticker') {
                const fontSize = style.fontSize ? `${style.fontSize}px` : '28px';
                const color = style.color || '#ffffff';
                const align = style.textAlign || 'left';
                return `<div class="signage-element-preview" style="color:${escapeAttr(color)};font-size:${escapeAttr(fontSize)};text-align:${escapeAttr(align)};padding:0.5rem;">${escapeHtml(content.text || this.translate('signage.preview.sample_text'))}</div>`;
            }
            if (type === 'image') {
                const src = content.src || '';
                return `<div class="signage-element-preview signage-element-preview--image">${src ? `<img src="${escapeAttr(src)}" alt="">` : `<span>${escapeHtml(this.translate('signage.preview.image_placeholder'))}</span>`}</div>`;
            }
            if (type === 'video') {
                return `<div class="signage-element-preview signage-element-preview--video">${escapeHtml(this.translate('signage.preview.video_placeholder'))}</div>`;
            }
            if (type === 'live') {
                return `<div class="signage-element-preview signage-element-preview--live">${escapeHtml(this.translate('signage.preview.live_placeholder'))}</div>`;
            }
            return `<div class="signage-element-preview">${escapeHtml(this.translate('signage.preview.generic_placeholder'))}</div>`;
        }

        attachElementEvents(node, element) {
            node.addEventListener('pointerdown', (event) => {
                if (event.button !== 0) {
                    return;
                }
                if (event.target.closest('[data-resize-handle]')) {
                    return;
                }
                if (!node.isConnected || !this.dom.canvasInner?.isConnected) {
                    return;
                }
                const canvasRect = this.dom.canvasInner?.getBoundingClientRect();
                if (!canvasRect || !canvasRect.width || !canvasRect.height) {
                    return;
                }
                this.selectElement(element.id, { preserveCanvas: true });
                const rect = node.getBoundingClientRect();
                const canvasWidth = canvasRect.width;
                const canvasHeight = canvasRect.height;
                const startX = event.clientX;
                const startY = event.clientY;
                const initial = {
                    x: (rect.left - canvasRect.left) / canvasWidth,
                    y: (rect.top - canvasRect.top) / canvasHeight,
                    width: rect.width / canvasWidth,
                    height: rect.height / canvasHeight,
                };
                const pointerId = typeof event.pointerId === 'number' ? event.pointerId : null;
                if (pointerId !== null && typeof node.setPointerCapture === 'function') {
                    try {
                        node.setPointerCapture(pointerId);
                    } catch (error) {
                        console.warn('Failed to capture pointer', error);
                    }
                }
                this.beginMutation();
                const handleMove = (moveEvent) => {
                    if (pointerId !== null && moveEvent.pointerId !== pointerId) {
                        return;
                    }
                    moveEvent.preventDefault();
                    const dx = canvasWidth ? (moveEvent.clientX - startX) / canvasWidth : 0;
                    const dy = canvasHeight ? (moveEvent.clientY - startY) / canvasHeight : 0;
                    const newX = Math.max(0, Math.min(1 - initial.width, initial.x + dx));
                    const newY = Math.max(0, Math.min(1 - initial.height, initial.y + dy));
                    element.position = Object.assign({}, element.position, {
                        x: newX,
                        y: newY,
                        width: initial.width,
                        height: initial.height,
                    });
                    this.positionElementNode(node, element);
                    this.updateGuides(newX, newY);
                };
                const handleUp = (upEvent) => {
                    if (pointerId !== null && upEvent.pointerId !== pointerId) {
                        return;
                    }
                    if (pointerId !== null && typeof node.releasePointerCapture === 'function') {
                        try {
                            node.releasePointerCapture(pointerId);
                        } catch (error) {
                            console.warn('Failed to release pointer capture', error);
                        }
                    }
                    window.removeEventListener('pointermove', handleMove);
                    window.removeEventListener('pointerup', handleUp);
                    this.updateGuides(null, null);
                    this.finalizeMutation();
                    this.renderLayers();
                };
                window.addEventListener('pointermove', handleMove);
                window.addEventListener('pointerup', handleUp);
            });

            node.addEventListener('click', () => this.selectElement(element.id));
        }

        attachResizeHandle(node, element) {
            const handle = document.createElement('span');
            handle.dataset.resizeHandle = 'se';
            node.appendChild(handle);
            handle.addEventListener('pointerdown', (event) => {
                if (event.button !== 0) {
                    return;
                }
                if (!node.isConnected || !this.dom.canvasInner?.isConnected) {
                    return;
                }
                const canvasRect = this.dom.canvasInner?.getBoundingClientRect();
                if (!canvasRect || !canvasRect.width || !canvasRect.height) {
                    return;
                }
                event.stopPropagation();
                this.selectElement(element.id, { preserveCanvas: true });
                const rect = node.getBoundingClientRect();
                const canvasWidth = canvasRect.width;
                const canvasHeight = canvasRect.height;
                const pointerId = typeof event.pointerId === 'number' ? event.pointerId : null;
                const startX = event.clientX;
                const startY = event.clientY;
                const initial = {
                    x: (rect.left - canvasRect.left) / canvasWidth,
                    y: (rect.top - canvasRect.top) / canvasHeight,
                    width: rect.width / canvasWidth,
                    height: rect.height / canvasHeight,
                };
                if (pointerId !== null && typeof node.setPointerCapture === 'function') {
                    try {
                        node.setPointerCapture(pointerId);
                    } catch (error) {
                        console.warn('Failed to capture pointer', error);
                    }
                }
                this.beginMutation();
                const handleMove = (moveEvent) => {
                    if (pointerId !== null && moveEvent.pointerId !== pointerId) {
                        return;
                    }
                    moveEvent.preventDefault();
                    const dx = canvasWidth ? (moveEvent.clientX - startX) / canvasWidth : 0;
                    const dy = canvasHeight ? (moveEvent.clientY - startY) / canvasHeight : 0;
                    let newWidth = initial.width + dx;
                    let newHeight = initial.height + dy;
                    newWidth = Math.max(0.05, Math.min(1 - initial.x, newWidth));
                    newHeight = Math.max(0.05, Math.min(1 - initial.y, newHeight));
                    if (moveEvent.shiftKey) {
                        const ratio = initial.height > 0 ? initial.width / initial.height : 1;
                        newHeight = Math.max(0.05, Math.min(1 - initial.y, newWidth / (ratio || 1)));
                        newWidth = Math.max(0.05, Math.min(1 - initial.x, newHeight * (ratio || 1)));
                    }
                    element.position = Object.assign({}, element.position, {
                        x: initial.x,
                        y: initial.y,
                        width: newWidth,
                        height: newHeight,
                    });
                    this.positionElementNode(node, element);
                };
                const handleUp = (upEvent) => {
                    if (pointerId !== null && upEvent.pointerId !== pointerId) {
                        return;
                    }
                    if (pointerId !== null && typeof node.releasePointerCapture === 'function') {
                        try {
                            node.releasePointerCapture(pointerId);
                        } catch (error) {
                            console.warn('Failed to release pointer capture', error);
                        }
                    }
                    window.removeEventListener('pointermove', handleMove);
                    window.removeEventListener('pointerup', handleUp);
                    this.finalizeMutation();
                    this.renderLayers();
                    this.renderStyles();
                };
                window.addEventListener('pointermove', handleMove);
                window.addEventListener('pointerup', handleUp);
            });
        }

        updateGuides(x, y) {
            if (!this.dom.guides) {
                return;
            }
            if (typeof x === 'number' && typeof y === 'number') {
                this.dom.guides.dataset.axes = 'both';
                this.dom.guides.style.setProperty('--guide-x', `${x * 100}%`);
                this.dom.guides.style.setProperty('--guide-y', `${y * 100}%`);
            } else {
                delete this.dom.guides.dataset.axes;
            }
        }

        beginMutation() {
            if (!this.activeLayout) {
                return;
            }
            this.mutationSnapshot = deepClone(this.activeLayout);
        }

        finalizeMutation() {
            if (!this.mutationSnapshot) {
                return;
            }
            this.undoStack.push(this.mutationSnapshot);
            if (this.undoStack.length > 30) {
                this.undoStack.shift();
            }
            this.redoStack = [];
            this.mutationSnapshot = null;
            this.markDirty();
        }

        markDirty() {
            if (this.dom.status) {
                this.dom.status.textContent = this.translate('signage.status.dirty');
            }
        }

        updateStatus(message) {
            if (this.dom.status) {
                this.dom.status.textContent = message;
            }
        }

        renderLayers() {
            if (!this.dom.layers || !this.activeLayout) {
                return;
            }
            const fragment = document.createDocumentFragment();
            const elements = Array.isArray(this.activeLayout.elements) ? this.activeLayout.elements.slice() : [];
            const activeScene = this.getActiveScene();
            const restrictToScene = !!(activeScene && Array.isArray(activeScene.elementIds));
            const visibleIds = restrictToScene
                ? new Set(activeScene.elementIds.map((id) => String(id)))
                : null;
            elements.sort((a, b) => (b.layer ?? 0) - (a.layer ?? 0));
            elements.forEach((element) => {
                if (restrictToScene && visibleIds && !visibleIds.has(String(element.id))) {
                    return;
                }
                const row = document.createElement('div');
                row.className = 'd-flex align-items-center justify-content-between mb-2 signage-layer-row';
                row.innerHTML = `
                    <div class="flex-grow-1">
                        <button type="button" class="btn btn-link btn-sm p-0" data-layer-action="select" data-element-id="${escapeAttr(element.id)}">${escapeHtml(element.label || element.type || 'Element')}</button>
                        <span class="badge bg-light text-dark ms-2">${escapeHtml(element.type || '')}</span>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" type="button" data-layer-action="layer-up" data-element-id="${escapeAttr(element.id)}">▲</button>
                        <button class="btn btn-outline-secondary" type="button" data-layer-action="layer-down" data-element-id="${escapeAttr(element.id)}">▼</button>
                        <button class="btn btn-outline-danger" type="button" data-layer-action="delete" data-element-id="${escapeAttr(element.id)}">${escapeHtml(this.translate('signage.actions.delete_short'))}</button>
                    </div>
                `;
                fragment.appendChild(row);
            });
            this.dom.layers.innerHTML = '';
            this.dom.layers.appendChild(fragment);
        }

        renderBindings() {
            if (!this.dom.bindings) {
                return;
            }
            const element = this.getSelectedElement();
            if (!element) {
                this.dom.bindings.innerHTML = `<p class="text-muted small mb-0">${escapeHtml(this.translate('signage.bindings.empty'))}</p>`;
                return;
            }
            const binding = element.binding || {};
            this.dom.bindings.innerHTML = `
                <div class="mb-2">
                    <label class="form-label form-label-sm">${escapeHtml(this.translate('signage.bindings.path'))}</label>
                    <input type="text" class="form-control form-control-sm" value="${escapeAttr(binding.path || '')}" data-binding-field="path">
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm">${escapeHtml(this.translate('signage.bindings.fallback'))}</label>
                    <input type="text" class="form-control form-control-sm" value="${escapeAttr(binding.fallback || '')}" data-binding-field="fallback">
                </div>
            `;
        }

        renderStyles() {
            if (!this.dom.styles) {
                return;
            }
            const element = this.getSelectedElement();
            if (!element) {
                this.dom.styles.innerHTML = `<p class="text-muted small mb-0">${escapeHtml(this.translate('signage.styles.empty'))}</p>`;
                return;
            }
            const style = element.style || {};
            const commonControls = `
                <div class="mb-2">
                    <label class="form-label form-label-sm">${escapeHtml(this.translate('signage.styles.font_size'))}</label>
                    <input type="number" class="form-control form-control-sm" value="${escapeAttr(style.fontSize || 32)}" data-style-field="fontSize">
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm">${escapeHtml(this.translate('signage.styles.color'))}</label>
                    <input type="color" class="form-control form-control-color" value="${escapeAttr(style.color || '#ffffff')}" data-style-field="color">
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm">${escapeHtml(this.translate('signage.styles.align'))}</label>
                    <select class="form-select form-select-sm" data-style-field="textAlign">
                        ${['left', 'center', 'right'].map((value) => `<option value="${value}" ${value === (style.textAlign || 'left') ? 'selected' : ''}>${escapeHtml(this.translate('signage.styles.align_' + value))}</option>`).join('')}
                    </select>
                </div>
            `;
            if (element.type === 'text' || element.type === 'ticker') {
                this.dom.styles.innerHTML = commonControls;
            } else {
                this.dom.styles.innerHTML = `<p class="text-muted small mb-2">${escapeHtml(this.translate('signage.styles.generic_hint'))}</p>` + commonControls;
            }
        }

        renderTimeline() {
            if (!this.dom.timeline || !this.activeLayout) {
                return;
            }
            const fragment = document.createDocumentFragment();
            const scenes = Array.isArray(this.activeLayout.timeline) ? this.activeLayout.timeline : [];
            const activeScene = this.getActiveScene();
            const activeId = activeScene ? activeScene.id : this.activeSceneId;
            scenes.forEach((scene) => {
                const item = document.createElement('div');
                item.className = 'signage-timeline__item';
                item.dataset.sceneId = scene.id;
                if (scene.id === activeId) {
                    item.classList.add('is-active');
                }
                item.innerHTML = `
                    <div class="fw-semibold">${escapeHtml(scene.name || this.translate('signage.timeline.unnamed'))}</div>
                    <div class="small text-muted">${escapeHtml(this.translate('signage.timeline.duration', { seconds: scene.duration || 30 }))}</div>
                    <div class="mt-2 d-flex gap-1">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-scene-action="edit" data-scene-id="${escapeAttr(scene.id)}">${escapeHtml(this.translate('signage.actions.edit'))}</button>
                        <button class="btn btn-sm btn-outline-danger" type="button" data-scene-action="delete" data-scene-id="${escapeAttr(scene.id)}">${escapeHtml(this.translate('signage.actions.delete_short'))}</button>
                    </div>
                `;
                fragment.appendChild(item);
            });
            this.dom.timeline.innerHTML = '';
            this.dom.timeline.appendChild(fragment);
        }

        getSelectedElement() {
            if (!this.activeLayout || !this.selectedElementId) {
                return null;
            }
            const element = (this.activeLayout.elements || []).find((item) => item.id === this.selectedElementId) || null;
            if (!element) {
                return null;
            }
            const activeScene = this.getActiveScene();
            if (activeScene && Array.isArray(activeScene.elementIds) && activeScene.elementIds.length > 0) {
                const isInScene = activeScene.elementIds.some((id) => String(id) === String(element.id));
                if (!isInScene) {
                    return null;
                }
            }
            return element;
        }

        selectElement(elementId, options = {}) {
            this.selectedElementId = elementId;
            const preserveCanvas = !!options.preserveCanvas;
            if (preserveCanvas) {
                this.updateCanvasSelectionState();
            } else {
                this.renderCanvas();
            }
            this.renderLayers();
            this.renderBindings();
            this.renderStyles();
        }

        updateCanvasSelectionState() {
            if (!this.dom.canvasInner) {
                return;
            }
            const nodes = this.dom.canvasInner.querySelectorAll('[data-signage-element]');
            nodes.forEach((node) => {
                const elementId = node.getAttribute('data-signage-element');
                if (String(elementId) === String(this.selectedElementId)) {
                    node.classList.add('is-active');
                } else {
                    node.classList.remove('is-active');
                }
            });
        }

        addElement(type) {
            if (!this.activeLayout) {
                return;
            }
            const element = this.createElementPayload(type);
            if (!Array.isArray(this.activeLayout.elements)) {
                this.activeLayout.elements = [];
            }
            this.beginMutation();
            this.activeLayout.elements.push(element);
            const scene = this.getActiveScene(true);
            if (scene) {
                if (!Array.isArray(scene.elementIds)) {
                    scene.elementIds = [];
                }
                scene.elementIds.push(element.id);
            }
            this.finalizeMutation();
            this.selectElement(element.id);
            this.renderTimeline();
        }

        createElementPayload(type) {
            const id = generateId('element');
            const base = {
                id,
                type,
                label: this.translate('signage.element.default_' + type, '', this.translate('signage.element.default_generic')),
                layer: 10,
                position: { x: 0.1, y: 0.1, width: 0.3, height: 0.12 },
                style: { fontSize: 32, color: '#ffffff', textAlign: 'left' },
                content: {},
                binding: { path: '', fallback: '' },
            };
            switch (type) {
                case 'text':
                    base.content.text = this.translate('signage.preview.sample_text');
                    break;
                case 'ticker':
                    base.content.text = this.translate('signage.preview.ticker_placeholder');
                    base.style.fontSize = 28;
                    base.position.height = 0.08;
                    base.position.width = 0.8;
                    break;
                case 'image':
                    base.content.src = '';
                    base.position.width = 0.25;
                    base.position.height = 0.25;
                    break;
                case 'video':
                    base.content.source = '';
                    base.position.width = 0.4;
                    base.position.height = 0.3;
                    break;
                case 'live':
                    base.binding.path = 'live.current';
                    base.position.width = 0.45;
                    base.position.height = 0.25;
                    break;
                default:
                    break;
            }
            return base;
        }

        removeElement(elementId) {
            if (!this.activeLayout) {
                return;
            }
            this.beginMutation();
            this.activeLayout.elements = (this.activeLayout.elements || []).filter((element) => element.id !== elementId);
            (this.activeLayout.timeline || []).forEach((scene) => {
                scene.elementIds = (scene.elementIds || []).filter((id) => id !== elementId);
            });
            this.finalizeMutation();
            this.selectedElementId = null;
            this.renderActiveLayout();
        }

        bumpLayer(elementId, delta) {
            const element = this.getSelectedElement() || (this.activeLayout?.elements || []).find((item) => item.id === elementId);
            if (!element) {
                return;
            }
            this.beginMutation();
            element.layer = (element.layer ?? 0) + delta;
            this.finalizeMutation();
            this.renderLayers();
            this.renderCanvas();
        }

        updateElementStyle(field, value) {
            const element = this.getSelectedElement();
            if (!element) {
                return;
            }
            this.beginMutation();
            const style = Object.assign({}, element.style || {});
            style[field] = field === 'fontSize' ? Number(value) : value;
            element.style = style;
            this.finalizeMutation();
            this.renderCanvas();
        }

        updateElementBinding(field, value) {
            const element = this.getSelectedElement();
            if (!element) {
                return;
            }
            this.beginMutation();
            const binding = Object.assign({}, element.binding || {});
            binding[field] = value;
            element.binding = binding;
            this.finalizeMutation();
        }

        addScene() {
            if (!this.activeLayout) {
                return;
            }
            const scene = this.createDefaultScene();
            scene.name = this.translate('signage.timeline.new_scene');
            this.beginMutation();
            this.activeLayout.timeline.push(scene);
            this.finalizeMutation();
            this.activeSceneId = scene.id;
            this.selectedElementId = null;
            this.renderActiveLayout();
        }

        selectScene(sceneId) {
            this.activeSceneId = sceneId;
            const scene = this.getActiveScene();
            this.activeSceneId = scene ? scene.id : null;
            this.selectedElementId = null;
            this.renderActiveLayout();
        }

        deleteScene(sceneId) {
            if (!this.activeLayout) {
                return;
            }
            if (this.activeLayout.timeline.length <= 1) {
                return;
            }
            this.beginMutation();
            this.activeLayout.timeline = this.activeLayout.timeline.filter((scene) => scene.id !== sceneId);
            this.finalizeMutation();
            const scene = this.getActiveScene();
            this.activeSceneId = scene ? scene.id : null;
            this.selectedElementId = null;
            this.renderActiveLayout();
        }

        getActiveScene(createIfMissing = false) {
            if (!this.activeLayout) {
                return null;
            }
            let scenes = Array.isArray(this.activeLayout.timeline) ? this.activeLayout.timeline : [];
            if (scenes.length === 0) {
                if (!createIfMissing) {
                    return null;
                }
                const defaultScene = this.createDefaultScene();
                this.activeLayout.timeline = [defaultScene];
                this.activeSceneId = defaultScene.id;
                scenes = this.activeLayout.timeline;
            }
            let scene = scenes.find((item) => String(item.id) === String(this.activeSceneId));
            if (!scene) {
                scene = scenes[0] || null;
                this.activeSceneId = scene ? scene.id : null;
            }
            if (scene && createIfMissing && !Array.isArray(scene.elementIds)) {
                scene.elementIds = [];
            }
            return scene || null;
        }

        editScene(sceneId) {
            if (!this.activeLayout) {
                return;
            }
            const scene = this.activeLayout.timeline.find((item) => item.id === sceneId);
            if (!scene) {
                return;
            }
            const body = document.createElement('div');
            body.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.timeline.scene_name'))}</label>
                    <input type="text" class="form-control" value="${escapeAttr(scene.name || '')}" data-field="name">
                </div>
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.timeline.scene_duration'))}</label>
                    <input type="number" class="form-control" value="${escapeAttr(scene.duration || 30)}" data-field="duration">
                </div>
            `;
            this.openModal({
                title: this.translate('signage.timeline.edit_title'),
                body,
                onSave: () => {
                    const nameInput = body.querySelector('[data-field="name"]');
                    const durationInput = body.querySelector('[data-field="duration"]');
                    this.beginMutation();
                    scene.name = nameInput.value.trim() || this.translate('signage.timeline.unnamed');
                    scene.duration = Math.max(5, Number(durationInput.value) || 30);
                    this.finalizeMutation();
                    this.renderTimeline();
                    this.closeModal();
                },
            });
        }

        createDefaultScene() {
            return {
                id: generateId('scene'),
                name: this.translate('signage.timeline.unnamed'),
                duration: 30,
                elementIds: [],
                transitions: { in: 'fade', out: 'fade' },
            };
        }

        promptCreateLayout() {
            const body = document.createElement('div');
            body.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.forms.layout_name'))}</label>
                    <input type="text" class="form-control" data-field="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.forms.layout_description'))}</label>
                    <textarea class="form-control" data-field="description" rows="3"></textarea>
                </div>
            `;
            this.openModal({
                title: this.translate('signage.forms.layout_create'),
                body,
                onSave: () => {
                    const name = body.querySelector('[data-field="name"]').value.trim();
                    const description = body.querySelector('[data-field="description"]').value.trim();
                    if (!name) {
                        body.querySelector('[data-field="name"]').classList.add('is-invalid');
                        return;
                    }
                    this.api('create_layout', { name, description })
                        .then((response) => {
                            if (response?.layout) {
                                this.layouts.unshift(response.layout);
                                this.selectLayout(response.layout.id);
                                this.renderLayoutList();
                            }
                            this.closeModal();
                        })
                        .catch((error) => {
                            console.error(error);
                            this.updateStatus(this.translate('signage.status.error'));
                        });
                },
            });
        }

        duplicateLayout() {
            if (!this.activeLayoutId) {
                return;
            }
            this.api('duplicate_layout', { id: this.activeLayoutId })
                .then((response) => {
                    if (response?.layout) {
                        this.layouts.unshift(response.layout);
                        this.selectLayout(response.layout.id);
                        this.renderLayoutList();
                    }
                })
                .catch((error) => {
                    console.error(error);
                    this.updateStatus(this.translate('signage.status.error'));
                });
        }

        previewLayout() {
            if (!this.activeLayout) {
                return;
            }
            const body = document.createElement('div');
            body.innerHTML = `
                <pre class="bg-light p-3 rounded small overflow-auto" style="max-height: 480px;">${escapeHtml(JSON.stringify(this.activeLayout, null, 2))}</pre>
            `;
            this.openModal({
                title: this.translate('signage.preview.modal_title'),
                body,
                saveHidden: true,
            });
        }

        publishLayout() {
            if (!this.activeLayoutId) {
                return;
            }
            this.api('publish_layout', { id: this.activeLayoutId })
                .then((response) => {
                    if (response?.layout) {
                        this.updateLayoutCache(response.layout);
                        this.renderLayoutList();
                        this.updateStatus(this.translate('signage.status.published_notice'));
                    }
                })
                .catch((error) => {
                    console.error(error);
                    this.updateStatus(this.translate('signage.status.error'));
                });
        }

        deleteLayout() {
            if (!this.activeLayoutId) {
                return;
            }
            if (!window.confirm(this.translate('signage.confirm.delete_layout'))) {
                return;
            }
            this.api('delete_layout', { id: this.activeLayoutId })
                .then(() => {
                    this.layouts = this.layouts.filter((layout) => layout.id !== this.activeLayoutId);
                    if (this.layouts.length > 0) {
                        this.selectLayout(this.layouts[0].id);
                    } else {
                        this.activeLayout = null;
                        this.activeLayoutId = null;
                        this.renderLayoutList();
                        this.renderActiveLayout();
                    }
                })
                .catch((error) => {
                    console.error(error);
                    this.updateStatus(this.translate('signage.status.error'));
                });
        }

        saveLayout() {
            if (!this.activeLayout) {
                return;
            }
            const payload = deepClone(this.activeLayout);
            payload.id = this.activeLayoutId;
            this.api('update_layout', payload)
                .then((response) => {
                    if (response?.layout) {
                        this.updateLayoutCache(response.layout);
                        this.selectLayout(response.layout.id);
                        this.renderLayoutList();
                        this.updateStatus(this.translate('signage.status.saved'));
                    }
                })
                .catch((error) => {
                    console.error(error);
                    this.updateStatus(this.translate('signage.status.error'));
                });
        }

        updateLayoutCache(layout) {
            const index = this.layouts.findIndex((item) => item.id === layout.id);
            if (index !== -1) {
                this.layouts[index] = layout;
            } else {
                this.layouts.push(layout);
            }
        }

        promptCreateDisplay() {
            const body = document.createElement('div');
            body.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.displays.form.name'))}</label>
                    <input type="text" class="form-control" data-field="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.displays.form.group'))}</label>
                    <input type="text" class="form-control" data-field="display_group" value="default">
                </div>
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.displays.form.location'))}</label>
                    <input type="text" class="form-control" data-field="location">
                </div>
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.displays.form.description'))}</label>
                    <textarea class="form-control" data-field="description" rows="3"></textarea>
                </div>
            `;
            this.openModal({
                title: this.translate('signage.displays.create_title'),
                body,
                onSave: () => {
                    const name = body.querySelector('[data-field="name"]').value.trim();
                    const group = body.querySelector('[data-field="display_group"]').value.trim();
                    const location = body.querySelector('[data-field="location"]').value.trim();
                    const description = body.querySelector('[data-field="description"]').value.trim();
                    if (!name) {
                        body.querySelector('[data-field="name"]').classList.add('is-invalid');
                        return;
                    }
                    this.api('register_display', {
                        name,
                        display_group: group,
                        location,
                        description,
                    }).then((response) => {
                        if (response?.display) {
                            this.displays.push(response.display);
                            this.renderDisplays();
                        }
                        this.closeModal();
                    }).catch((error) => {
                        console.error(error);
                        this.updateStatus(this.translate('signage.status.error'));
                    });
                },
            });
        }

        confirmDeleteDisplay(displayId) {
            if (!displayId) {
                return;
            }
            if (!window.confirm(this.translate('signage.confirm.delete_display'))) {
                return;
            }
            this.api('delete_display', { id: Number(displayId) })
                .then(() => {
                    this.displays = this.displays.filter((display) => String(display.id) !== String(displayId));
                    this.renderDisplays();
                })
                .catch((error) => {
                    console.error(error);
                    this.updateStatus(this.translate('signage.status.error'));
                });
        }

        assignLayoutToDisplay(displayId) {
            if (!displayId) {
                return;
            }
            const display = this.displays.find((item) => String(item.id) === String(displayId));
            if (!display) {
                return;
            }
            const body = document.createElement('div');
            body.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.displays.form.layout'))}</label>
                    <select class="form-select" data-field="layout_id">
                        <option value="">${escapeHtml(this.translate('signage.forms.none'))}</option>
                        ${this.layouts.map((layout) => `<option value="${escapeAttr(layout.id)}" ${layout.id === display.assigned_layout_id ? 'selected' : ''}>${escapeHtml(layout.name)}</option>`).join('')}
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.displays.form.playlist'))}</label>
                    <select class="form-select" data-field="playlist_id">
                        <option value="">${escapeHtml(this.translate('signage.forms.none'))}</option>
                        ${this.playlists.map((playlist) => `<option value="${escapeAttr(playlist.id)}" ${playlist.id === display.assigned_playlist_id ? 'selected' : ''}>${escapeHtml(playlist.title)}</option>`).join('')}
                    </select>
                </div>
            `;
            this.openModal({
                title: this.translate('signage.displays.assign_title'),
                body,
                onSave: () => {
                    const layoutId = body.querySelector('[data-field="layout_id"]').value;
                    const playlistId = body.querySelector('[data-field="playlist_id"]').value;
                    this.api('update_display', {
                        id: Number(displayId),
                        layout_id: layoutId ? Number(layoutId) : null,
                        playlist_id: playlistId ? Number(playlistId) : null,
                    }).then((response) => {
                        if (response?.display) {
                            const index = this.displays.findIndex((item) => item.id === response.display.id);
                            if (index !== -1) {
                                this.displays[index] = response.display;
                            }
                            this.renderDisplays();
                        }
                        this.closeModal();
                    }).catch((error) => {
                        console.error(error);
                        this.updateStatus(this.translate('signage.status.error'));
                    });
                },
            });
        }

        renderDisplays() {
            if (!this.dom.displays) {
                return;
            }
            const fragment = document.createDocumentFragment();
            if (this.displays.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'text-muted mb-0';
                empty.textContent = this.translate('signage.displays.empty');
                fragment.appendChild(empty);
            } else {
                this.displays.forEach((display) => {
                    const card = document.createElement('div');
                    card.className = 'signage-display';
                    card.dataset.displayId = display.id;
                    card.innerHTML = `
                        <div class="signage-display__header">
                            <div>
                                <strong>${escapeHtml(display.name || 'Display')}</strong>
                                <div class="text-muted small">${escapeHtml(display.display_group || 'default')}</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-signage-action="assign-layout" data-display-id="${escapeAttr(display.id)}">${escapeHtml(this.translate('signage.displays.assign'))}</button>
                                <button class="btn btn-sm btn-outline-danger" type="button" data-signage-action="delete-display" data-display-id="${escapeAttr(display.id)}">🗑</button>
                            </div>
                        </div>
                        <div class="signage-display__body">
                            <div class="small text-muted">Token: <code>${escapeHtml(display.access_token || '')}</code></div>
                            <div class="small text-muted">${escapeHtml(this.translate('signage.displays.last_seen', {
                                time: display.last_seen_at ? formatDateTime(display.last_seen_at) : this.translate('signage.displays.never'),
                            }))}</div>
                        </div>
                    `;
                    fragment.appendChild(card);
                });
            }
            this.dom.displays.innerHTML = '';
            this.dom.displays.appendChild(fragment);
        }

        promptPlaylist(playlistId = null) {
            const playlist = playlistId ? this.playlists.find((item) => String(item.id) === String(playlistId)) : null;
            const body = document.createElement('div');
            body.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.playlists.form.title'))}</label>
                    <input type="text" class="form-control" data-field="title" value="${escapeAttr(playlist?.title || '')}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.playlists.form.group'))}</label>
                    <input type="text" class="form-control" data-field="display_group" value="${escapeAttr(playlist?.display_group || 'default')}">
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">${escapeHtml(this.translate('signage.playlists.form.rotation'))}</label>
                        <input type="number" class="form-control" data-field="rotation_seconds" value="${escapeAttr(playlist?.rotation_seconds || 30)}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">${escapeHtml(this.translate('signage.playlists.form.priority'))}</label>
                        <input type="number" class="form-control" data-field="priority" value="${escapeAttr(playlist?.priority || 0)}">
                    </div>
                </div>
                <div class="form-check form-switch my-3">
                    <input class="form-check-input" type="checkbox" id="playlistEnabled" data-field="is_enabled" ${playlist?.is_enabled ? 'checked' : ''}>
                    <label class="form-check-label" for="playlistEnabled">${escapeHtml(this.translate('signage.playlists.form.enabled'))}</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">${escapeHtml(this.translate('signage.playlists.form.items'))}</label>
                    <div data-playlist-items></div>
                    <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-add-item>${escapeHtml(this.translate('signage.playlists.form.add_item'))}</button>
                </div>
            `;
            const itemsContainer = body.querySelector('[data-playlist-items]');
            const addItemButton = body.querySelector('[data-add-item]');
            const items = playlist ? deepClone(playlist.items || []) : [];
            let dragIndex = null;

            const renderItems = () => {
                itemsContainer.innerHTML = '';
                if (items.length === 0) {
                    itemsContainer.innerHTML = `<p class="text-muted small mb-0">${escapeHtml(this.translate('signage.playlists.form.empty_items'))}</p>`;
                    return;
                }
                items.forEach((item, index) => {
                    const row = document.createElement('div');
                    row.className = 'border rounded p-2 mb-2 bg-light-subtle signage-playlist-item';
                    row.dataset.index = String(index);
                    row.draggable = true;
                    row.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button class="signage-playlist-item__drag" type="button" data-drag-handle aria-label="${escapeAttr(this.translate('signage.playlists.form.reorder'))}">⇅</button>
                            <span class="badge text-bg-light">${index + 1}</span>
                        </div>
                        <div class="mb-2">
                            <label class="form-label form-label-sm">${escapeHtml(this.translate('signage.playlists.form.item_label'))}</label>
                            <input type="text" class="form-control form-control-sm" value="${escapeAttr(item.label || '')}" data-field="label" data-index="${index}">
                        </div>
                        <div class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label form-label-sm">${escapeHtml(this.translate('signage.playlists.form.item_layout'))}</label>
                                <select class="form-select form-select-sm" data-field="layout_id" data-index="${index}">
                                    ${this.layouts.map((layout) => `<option value="${escapeAttr(layout.id)}" ${layout.id === item.layout_id ? 'selected' : ''}>${escapeHtml(layout.name)}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label form-label-sm">${escapeHtml(this.translate('signage.playlists.form.item_duration'))}</label>
                                <input type="number" class="form-control form-control-sm" value="${escapeAttr(item.duration_seconds || 30)}" data-field="duration" data-index="${index}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-outline-danger btn-sm w-100" type="button" data-remove-index="${index}">🗑</button>
                            </div>
                        </div>
                    `;
                    const dragHandle = row.querySelector('[data-drag-handle]');
                    const enableDrag = (event) => {
                        if (event.target.closest('[data-drag-handle]') !== dragHandle) {
                            event.preventDefault();
                            return;
                        }
                        dragIndex = index;
                        row.classList.add('is-dragging');
                        if (event.dataTransfer) {
                            event.dataTransfer.effectAllowed = 'move';
                            event.dataTransfer.setData('text/plain', String(index));
                        }
                    };
                    row.addEventListener('dragstart', enableDrag);
                    row.addEventListener('dragend', () => {
                        row.classList.remove('is-dragging');
                        dragIndex = null;
                    });
                    row.addEventListener('dragover', (event) => {
                        event.preventDefault();
                        row.classList.add('is-dragover');
                        if (event.dataTransfer) {
                            event.dataTransfer.dropEffect = 'move';
                        }
                    });
                    row.addEventListener('dragleave', () => {
                        row.classList.remove('is-dragover');
                    });
                    row.addEventListener('drop', (event) => {
                        event.preventDefault();
                        row.classList.remove('is-dragover');
                        const targetIndex = Number(row.dataset.index ?? index);
                        if (dragIndex === null || Number.isNaN(targetIndex) || dragIndex === targetIndex) {
                            return;
                        }
                        const [moved] = items.splice(dragIndex, 1);
                        items.splice(targetIndex, 0, moved);
                        dragIndex = null;
                        renderItems();
                    });
                    itemsContainer.appendChild(row);
                });
            };
            renderItems();

            itemsContainer.addEventListener('input', (event) => {
                const index = Number(event.target.getAttribute('data-index'));
                if (Number.isNaN(index) || !items[index]) {
                    return;
                }
                const field = event.target.getAttribute('data-field');
                if (field === 'label') {
                    items[index].label = event.target.value;
                } else if (field === 'layout_id') {
                    items[index].layout_id = Number(event.target.value);
                } else if (field === 'duration') {
                    const duration = Math.max(5, Number(event.target.value) || 30);
                    items[index].duration_seconds = duration;
                    event.target.value = String(duration);
                }
            });
            itemsContainer.addEventListener('click', (event) => {
                const index = Number(event.target.getAttribute('data-remove-index'));
                if (!Number.isNaN(index)) {
                    items.splice(index, 1);
                    renderItems();
                }
            });
            addItemButton.addEventListener('click', () => {
                items.push({
                    label: this.translate('signage.playlists.item_default'),
                    layout_id: this.layouts[0]?.id || null,
                    duration_seconds: playlist?.rotation_seconds || 30,
                });
                renderItems();
            });

            this.openModal({
                title: playlist ? this.translate('signage.playlists.edit_title') : this.translate('signage.playlists.create_title'),
                body,
                onSave: () => {
                    const title = body.querySelector('[data-field="title"]').value.trim();
                    if (!title) {
                        body.querySelector('[data-field="title"]').classList.add('is-invalid');
                        return;
                    }
                    const payload = {
                        id: playlist ? Number(playlist.id) : null,
                        title,
                        display_group: body.querySelector('[data-field="display_group"]').value.trim() || 'default',
                        rotation_seconds: Number(body.querySelector('[data-field="rotation_seconds"]').value) || 30,
                        priority: Number(body.querySelector('[data-field="priority"]').value) || 0,
                        is_enabled: body.querySelector('[data-field="is_enabled"]').checked ? 1 : 0,
                        items,
                    };
                    this.api('save_playlist', payload)
                        .then((response) => {
                            if (response?.playlist) {
                                const index = this.playlists.findIndex((item) => item.id === response.playlist.id);
                                if (index === -1) {
                                    this.playlists.push(response.playlist);
                                } else {
                                    this.playlists[index] = response.playlist;
                                }
                                this.renderPlaylists();
                            }
                            this.closeModal();
                        })
                        .catch((error) => {
                            console.error(error);
                            this.updateStatus(this.translate('signage.status.error'));
                        });
                },
            });
        }

        confirmDeletePlaylist(playlistId) {
            if (!playlistId) {
                return;
            }
            if (!window.confirm(this.translate('signage.confirm.delete_playlist'))) {
                return;
            }
            this.api('delete_playlist', { id: Number(playlistId) })
                .then(() => {
                    this.playlists = this.playlists.filter((playlist) => String(playlist.id) !== String(playlistId));
                    this.renderPlaylists();
                })
                .catch((error) => {
                    console.error(error);
                    this.updateStatus(this.translate('signage.status.error'));
                });
        }

        renderPlaylists() {
            if (!this.dom.playlists) {
                return;
            }
            const fragment = document.createDocumentFragment();
            if (this.playlists.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'text-muted mb-0';
                empty.textContent = this.translate('signage.playlists.empty');
                fragment.appendChild(empty);
            } else {
                this.playlists.forEach((playlist) => {
                    const card = document.createElement('div');
                    card.className = 'signage-playlist';
                    card.dataset.playlistId = playlist.id;
                    card.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">${escapeHtml(playlist.title)}</div>
                                <div class="text-muted small">${escapeHtml(this.translate('signage.playlists.group', { group: playlist.display_group }))} · ${escapeHtml(this.translate('signage.playlists.rotation', { seconds: playlist.rotation_seconds || 30 }))}</div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary" type="button" data-signage-action="edit-playlist" data-playlist-id="${escapeAttr(playlist.id)}">${escapeHtml(this.translate('signage.actions.edit'))}</button>
                                <button class="btn btn-outline-danger" type="button" data-signage-action="delete-playlist" data-playlist-id="${escapeAttr(playlist.id)}">${escapeHtml(this.translate('signage.actions.delete_short'))}</button>
                            </div>
                        </div>
                        <div class="signage-playlist__items" data-signage-playlist-items>
                            ${(playlist.items || []).map((item) => `<div class="badge bg-light text-dark me-2 mb-2">${escapeHtml(item.label || this.translate('signage.playlists.item_default'))}<span class="text-muted ms-1">${item.duration_seconds || playlist.rotation_seconds || 30}s</span></div>`).join('')}
                        </div>
                    `;
                    fragment.appendChild(card);
                });
            }
            this.dom.playlists.innerHTML = '';
            this.dom.playlists.appendChild(fragment);
        }

        undo() {
            if (this.undoStack.length === 0 || !this.activeLayout) {
                return;
            }
            const state = this.undoStack.pop();
            this.redoStack.push(deepClone(this.activeLayout));
            this.activeLayout = deepClone(state);
            this.activeLayoutId = this.activeLayout.id;
            this.renderActiveLayout();
        }

        redo() {
            if (this.redoStack.length === 0 || !this.activeLayout) {
                return;
            }
            const state = this.redoStack.pop();
            this.undoStack.push(deepClone(this.activeLayout));
            this.activeLayout = deepClone(state);
            this.activeLayoutId = this.activeLayout.id;
            this.renderActiveLayout();
        }

        api(action, payload, options = {}) {
            const { retry = true } = options;
            const headers = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            };
            if (this.csrfToken) {
                headers['X-CSRF-Token'] = this.csrfToken;
            }
            const bodyPayload = Object.assign({}, payload || {});
            if (this.csrfToken) {
                bodyPayload._token = this.csrfToken;
            }
            return fetch(`${this.apiEndpoint}?action=${encodeURIComponent(action)}`, {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify(bodyPayload),
            }).then(async (response) => {
                const data = await response.json().catch(() => null);
                if (data && typeof data.csrf === 'string' && data.csrf) {
                    this.csrfToken = data.csrf;
                }
                if (!response.ok || (data && data.status === 'error')) {
                    if (response.status === 419 && retry && this.csrfToken) {
                        return this.api(action, payload, { retry: false });
                    }
                    const error = new Error(data?.message || 'Request failed');
                    if (data && data.code) {
                        error.code = data.code;
                    }
                    error.status = response.status;
                    throw error;
                }
                return data;
            });
        }

        openModal({ title, body, onSave, saveHidden = false }) {
            if (!this.bootstrapModal || !this.dom.modalBody || !this.dom.modalTitle) {
                return;
            }
            this.dom.modalTitle.textContent = title || '';
            this.dom.modalBody.innerHTML = '';
            if (body instanceof HTMLElement) {
                this.dom.modalBody.appendChild(body);
            } else if (typeof body === 'string') {
                this.dom.modalBody.innerHTML = body;
            }
            this.modalSaveHandler = onSave || null;
            if (this.dom.modalSave) {
                this.dom.modalSave.classList.toggle('d-none', !!saveHidden);
            }
            this.bootstrapModal.show();
        }

        closeModal() {
            if (this.bootstrapModal) {
                this.bootstrapModal.hide();
            }
            this.modalSaveHandler = null;
        }

        translate(key, replacements, fallback) {
            try {
                const translations = window.APP_TRANSLATIONS || {};
                const keys = key.split('.');
                let value = translations;
                for (const part of keys) {
                    if (value && Object.prototype.hasOwnProperty.call(value, part)) {
                        value = value[part];
                    } else {
                        value = null;
                        break;
                    }
                }
                if (typeof value !== 'string') {
                    value = fallback || key;
                }
                if (replacements && typeof replacements === 'object') {
                    Object.entries(replacements).forEach(([replaceKey, replaceValue]) => {
                        value = value.replace(`{${replaceKey}}`, String(replaceValue));
                    });
                }
                return value;
            } catch (error) {
                return fallback || key;
            }
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, '&#96;');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('signage-app');
        if (!root) {
            return;
        }
        let config = {};
        try {
            config = JSON.parse(root.getAttribute('data-signage-config') || '{}');
        } catch (error) {
            config = {};
        }
        window.signageApp = new SignageApp(root, config);
    });
})();
