(function () {
    'use strict';

    const MIN_ZOOM = 0.25;
    const MAX_ZOOM = 4;
    const ZOOM_STEP = 1.2;
    const RULER_STEP = 100;
    const CREATION_TOOLS = new Set(['text', 'image', 'shape', 'table', 'placeholder']);
    const MIN_ELEMENT_SIZE = 32;
    const RESIZE_HANDLES = ['top-left', 'top-right', 'bottom-right', 'bottom-left'];

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function parseConfig(raw) {
        if (!raw) {
            return {};
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            console.warn('Unable to parse layout editor config', error);
            return {};
        }
    }

    function formatTemplate(template, replacements) {
        if (typeof template !== 'string') {
            return '';
        }

        return template.replace(/:([a-zA-Z]+)/g, (match, key) => {
            if (Object.prototype.hasOwnProperty.call(replacements, key)) {
                return String(replacements[key]);
            }

            return match;
        });
    }

    function initLayoutEditor(root) {
        const config = parseConfig(root.dataset.layoutEditorConfig);
        const canvasConfig = config.canvas || {};
        const labels = Object.assign(
            {
                page: 'Page :index',
                status: 'Page :current / :total',
                cursor: ':x × :y px',
            },
            config.labels || {}
        );

        const rawPages = Array.isArray(config.pages) ? config.pages.slice() : [];
        const pages = rawPages.map((page, index) => {
            const copy = Object.assign({}, page);
            delete copy.elements;
            if (!copy.id) {
                copy.id = 'page-' + (index + 1 || 1);
            }
            if (!copy.title) {
                copy.title = formatTemplate(labels.page, { index: index + 1 });
            }
            return copy;
        });

        let pageCounter = pages.reduce((max, page) => {
            const match = /page-(\d+)/i.exec(page.id || '');
            return match ? Math.max(max, Number.parseInt(match[1], 10)) : max;
        }, pages.length);
        if (!pageCounter) {
            pageCounter = pages.length;
        }

        const initialCanvasWidth = Number(canvasConfig.width) || 1024;
        const initialCanvasHeight = Number(canvasConfig.height) || 768;
        const initialGridSize = Number(canvasConfig.gridSize) || 40;

        const elementPresets = {
            text: {
                width: 320,
                height: 140,
                data: {
                    text: 'Sample text block',
                    subline: 'Add your content',
                },
            },
            image: {
                width: 280,
                height: 200,
                data: {
                    alt: 'Image placeholder',
                    src: '',
                },
            },
            shape: {
                width: 220,
                height: 220,
                data: {
                    variant: 'rectangle',
                },
            },
            table: {
                width: 360,
                height: 220,
                data: {
                    rows: 3,
                    cols: 4,
                },
            },
            placeholder: {
                width: 220,
                height: 220,
                data: {
                    label: 'QR Code',
                    sample: 'https://example.com',
                },
            },
        };

        let initialElementCounter = 0;
        const elementsByPage = {};

        function registerElementIdLocal(id) {
            const match = /element-(\d+)/i.exec(id || '');
            if (match) {
                const numericId = Number.parseInt(match[1], 10);
                if (!Number.isNaN(numericId)) {
                    initialElementCounter = Math.max(initialElementCounter, numericId);
                }
            }
        }

        function nextElementIdLocal() {
            initialElementCounter += 1;
            return 'element-' + initialElementCounter;
        }

        function getElementDefaults(type) {
            const preset = elementPresets[type] || {};
            return {
                width: preset.width || 240,
                height: preset.height || 160,
                data: Object.assign({}, preset.data || {}),
            };
        }

        function normalizeElement(raw) {
            const type = CREATION_TOOLS.has(raw && raw.type) ? raw.type : 'shape';
            const defaults = getElementDefaults(type);
            const parsedX = raw && raw.x !== undefined ? Number.parseFloat(raw.x) : Number.NaN;
            const parsedY = raw && raw.y !== undefined ? Number.parseFloat(raw.y) : Number.NaN;
            const parsedWidth = raw && raw.width !== undefined ? Number.parseFloat(raw.width) : Number.NaN;
            const parsedHeight = raw && raw.height !== undefined ? Number.parseFloat(raw.height) : Number.NaN;
            const parsedRotation = raw && raw.rotation !== undefined ? Number.parseFloat(raw.rotation) : Number.NaN;

            const element = {
                id: raw && raw.id ? String(raw.id) : nextElementIdLocal(),
                type,
                x: Number.isFinite(parsedX) ? parsedX : 80,
                y: Number.isFinite(parsedY) ? parsedY : 80,
                width: Number.isFinite(parsedWidth) ? Math.max(MIN_ELEMENT_SIZE, parsedWidth) : defaults.width,
                height: Number.isFinite(parsedHeight) ? Math.max(MIN_ELEMENT_SIZE, parsedHeight) : defaults.height,
                rotation: Number.isFinite(parsedRotation) ? parsedRotation : 0,
                data: Object.assign({}, defaults.data, raw && typeof raw.data === 'object' && raw.data ? raw.data : {}),
            };
            registerElementIdLocal(element.id);
            return element;
        }

        pages.forEach((page, index) => {
            const rawPage = rawPages[index] || page;
            const rawElements = Array.isArray(rawPage.elements) ? rawPage.elements : [];
            elementsByPage[page.id] = rawElements.map((element) => normalizeElement(element));
        });

        const state = {
            zoom: Number(config.zoom) || 1,
            pan: { x: 0, y: 0 },
            pages,
            activePageId: config.activePageId || (pages[0] ? pages[0].id : null),
            showGrid: true,
            showGuides: true,
            activeTool: 'select',
            canvasWidth: initialCanvasWidth,
            canvasHeight: initialCanvasHeight,
            gridSize: initialGridSize,
            elementsByPage,
            selectedElementId: null,
            elementCounter: initialElementCounter,
            interaction: null,
        };

        const elements = {
            pagesList: root.querySelector('[data-layout-editor-pages]'),
            emptyState: root.querySelector('[data-layout-editor-empty-state]'),
            pageStatus: root.querySelector('[data-layout-editor-page-status]'),
            zoomValue: root.querySelector('[data-layout-editor-zoom-value]'),
            viewport: root.querySelector('[data-layout-editor-viewport]'),
            stage: root.querySelector('[data-layout-editor-stage]'),
            inner: root.querySelector('[data-layout-editor-inner]'),
            grid: root.querySelector('[data-layout-editor-grid]'),
            guides: root.querySelector('[data-layout-editor-guides]'),
            canvas: root.querySelector('[data-layout-editor-canvas]'),
            elementsLayer: root.querySelector('[data-layout-editor-elements]'),
            placeholder: root.querySelector('[data-layout-editor-placeholder]'),
            cursor: root.querySelector('[data-layout-editor-cursor]'),
            prevButton: root.querySelector('[data-layout-editor-action="previous-page"]'),
            nextButton: root.querySelector('[data-layout-editor-action="next-page"]'),
            rulerHorizontal: root.querySelector('[data-layout-editor-ruler-scale="horizontal"]'),
            rulerVertical: root.querySelector('[data-layout-editor-ruler-scale="vertical"]'),
            toggleGridButton: root.querySelector('[data-layout-editor-action="toggle-grid"]'),
            toggleGuidesButton: root.querySelector('[data-layout-editor-action="toggle-guides"]'),
        };

        if (!elements.viewport || !elements.stage || !elements.inner || !elements.canvas || !elements.elementsLayer) {
            return;
        }

        const toolLabels = {};
        root.querySelectorAll('[data-layout-editor-tool]').forEach((button) => {
            const tool = button.dataset.layoutEditorTool;
            if (!tool) {
                return;
            }
            const label = button.dataset.layoutEditorToolLabel || button.textContent.trim();
            if (label) {
                toolLabels[tool] = label;
            }
        });

        const context = elements.canvas.getContext('2d');

        function applyDimensions() {
            root.style.setProperty('--layout-editor-canvas-width', state.canvasWidth + 'px');
            root.style.setProperty('--layout-editor-canvas-height', state.canvasHeight + 'px');
            root.style.setProperty('--layout-editor-grid-size', state.gridSize + 'px');
            if (elements.canvas) {
                elements.canvas.width = state.canvasWidth;
                elements.canvas.height = state.canvasHeight;
                elements.canvas.style.width = state.canvasWidth + 'px';
                elements.canvas.style.height = state.canvasHeight + 'px';
            }
        }

        function applyTransform() {
            root.style.setProperty('--layout-editor-pan-x', state.pan.x + 'px');
            root.style.setProperty('--layout-editor-pan-y', state.pan.y + 'px');
            root.style.setProperty('--layout-editor-zoom', state.zoom.toString());
            updateZoomDisplay();
            updateRulers();
        }

        function updateZoomDisplay() {
            if (elements.zoomValue) {
                const percent = Math.round(state.zoom * 100);
                elements.zoomValue.textContent = percent + '%';
            }
        }

        function updateRulers() {
            updateRuler(elements.rulerHorizontal, 'horizontal');
            updateRuler(elements.rulerVertical, 'vertical');
        }

        function updateRuler(element, orientation) {
            if (!element) {
                return;
            }

            const size = orientation === 'horizontal'
                ? elements.viewport.clientWidth
                : elements.viewport.clientHeight;
            const start = orientation === 'horizontal'
                ? (-state.pan.x) / state.zoom
                : (-state.pan.y) / state.zoom;
            const total = start + size / state.zoom;
            const firstTick = Math.floor(start / RULER_STEP) * RULER_STEP;

            element.innerHTML = '';

            for (let value = firstTick; value <= total + RULER_STEP; value += RULER_STEP) {
                const offset = (value - start) * state.zoom;
                const tick = document.createElement('div');
                tick.className = 'layout-editor__ruler-tick';
                if (orientation === 'vertical') {
                    tick.classList.add('layout-editor__ruler-tick--vertical');
                    tick.style.top = offset + 'px';
                } else {
                    tick.style.left = offset + 'px';
                }
                tick.dataset.label = Math.round(value);
                element.appendChild(tick);
            }
        }

        function updatePageStatus() {
            if (!elements.pageStatus) {
                return;
            }
            const total = state.pages.length;
            const index = total ? state.pages.findIndex((page) => page.id === state.activePageId) + 1 : 0;
            elements.pageStatus.textContent = formatTemplate(labels.status, {
                current: index || 0,
                total,
            });
        }

        function updateCursorDisplay(x, y) {
            if (!elements.cursor) {
                return;
            }
            const formatted = formatTemplate(labels.cursor, {
                x: typeof x === 'number' ? Math.round(x) : '–',
                y: typeof y === 'number' ? Math.round(y) : '–',
            });
            elements.cursor.textContent = formatted;
        }

        function updatePlaceholder() {
            if (!elements.placeholder) {
                return;
            }
            elements.placeholder.hidden = Boolean(state.activePageId);
        }

        function ensureElementsForPage(pageId) {
            if (!pageId) {
                return;
            }
            if (!Array.isArray(state.elementsByPage[pageId])) {
                state.elementsByPage[pageId] = [];
            }
        }

        function getActiveElements() {
            if (!state.activePageId) {
                return [];
            }
            ensureElementsForPage(state.activePageId);
            return state.elementsByPage[state.activePageId];
        }

        function getElementById(elementId) {
            if (!elementId) {
                return null;
            }
            const pageElements = getActiveElements();
            return pageElements.find((item) => item.id === elementId) || null;
        }

        function clampElementToCanvas(element) {
            if (!element) {
                return;
            }
            element.width = Math.max(MIN_ELEMENT_SIZE, element.width);
            element.height = Math.max(MIN_ELEMENT_SIZE, element.height);
            const maxX = Math.max(0, state.canvasWidth - element.width);
            const maxY = Math.max(0, state.canvasHeight - element.height);
            element.x = clamp(element.x, 0, maxX);
            element.y = clamp(element.y, 0, maxY);
        }

        function updateElementNodePosition(node, element) {
            if (!node || !element) {
                return;
            }
            node.style.left = element.x + 'px';
            node.style.top = element.y + 'px';
            node.style.width = element.width + 'px';
            node.style.height = element.height + 'px';
        }

        function labelForTool(tool) {
            if (!tool) {
                return '';
            }
            return toolLabels[tool] || tool.charAt(0).toUpperCase() + tool.slice(1);
        }

        function renderQrPreview(container, sampleText) {
            if (!container) {
                return;
            }
            container.innerHTML = '';
            const text = sampleText || '';
            if (typeof window !== 'undefined' && typeof window.QRCode === 'function') {
                try {
                    const size = Math.min(container.clientWidth || 120, container.clientHeight || 120);
                    new window.QRCode(container, {
                        text,
                        width: size,
                        height: size,
                        colorDark: '#0f172a',
                        colorLight: '#fefce8',
                        correctLevel: window.QRCode.CorrectLevel ? window.QRCode.CorrectLevel.M : 0,
                    });
                    return;
                } catch (error) {
                    console.warn('Unable to render QR preview', error);
                }
            }

            const fallback = document.createElement('span');
            fallback.textContent = text || 'QR Preview';
            fallback.style.fontSize = '0.75rem';
            fallback.style.lineHeight = '1.2';
            fallback.style.color = '#0f172a';
            container.appendChild(fallback);
        }

        function renderElementContent(element) {
            const container = document.createElement('div');
            container.className = 'layout-editor__element-content';

            switch (element.type) {
                case 'text': {
                    const headline = document.createElement('div');
                    headline.textContent = element.data.text || 'Sample text block';
                    headline.style.fontWeight = '600';
                    headline.style.fontSize = '1rem';
                    const subline = document.createElement('div');
                    subline.textContent = element.data.subline || 'Add your content';
                    subline.style.fontSize = '0.85rem';
                    subline.style.opacity = '0.8';
                    container.appendChild(headline);
                    container.appendChild(subline);
                    break;
                }
                case 'image': {
                    const figure = document.createElement('div');
                    figure.style.width = '80%';
                    figure.style.height = '70%';
                    figure.style.borderRadius = '0.65rem';
                    figure.style.border = '1px dashed rgba(148, 163, 184, 0.55)';
                    figure.style.background = 'rgba(15, 23, 42, 0.35)';
                    figure.style.display = 'flex';
                    figure.style.alignItems = 'center';
                    figure.style.justifyContent = 'center';
                    const icon = document.createElement('span');
                    icon.textContent = '🖼';
                    icon.style.fontSize = '1.8rem';
                    figure.appendChild(icon);
                    const caption = document.createElement('div');
                    caption.textContent = element.data.alt || 'Image';
                    caption.style.fontSize = '0.75rem';
                    caption.style.opacity = '0.75';
                    container.appendChild(figure);
                    container.appendChild(caption);
                    break;
                }
                case 'shape': {
                    const shapeVisual = document.createElement('div');
                    shapeVisual.style.width = '70%';
                    shapeVisual.style.height = '70%';
                    shapeVisual.style.borderRadius = element.data.variant === 'circle' ? '999px' : '0.75rem';
                    shapeVisual.style.background = 'linear-gradient(135deg, rgba(16, 185, 129, 0.8), rgba(14, 165, 233, 0.65))';
                    container.appendChild(shapeVisual);
                    break;
                }
                case 'table': {
                    const rows = Math.max(1, Number.parseInt(element.data.rows, 10) || 3);
                    const cols = Math.max(1, Number.parseInt(element.data.cols, 10) || 4);
                    const table = document.createElement('table');
                    table.style.width = '90%';
                    table.style.borderCollapse = 'collapse';
                    table.style.fontSize = '0.75rem';
                    table.style.lineHeight = '1.2';
                    for (let rowIndex = 0; rowIndex < rows; rowIndex += 1) {
                        const tr = document.createElement('tr');
                        for (let colIndex = 0; colIndex < cols; colIndex += 1) {
                            const cell = document.createElement('td');
                            cell.textContent = String.fromCharCode(65 + colIndex) + (rowIndex + 1);
                            cell.style.border = '1px solid rgba(148, 163, 184, 0.4)';
                            cell.style.padding = '0.2rem 0.35rem';
                            tr.appendChild(cell);
                        }
                        table.appendChild(tr);
                    }
                    container.appendChild(table);
                    break;
                }
                case 'placeholder': {
                    const label = document.createElement('div');
                    label.textContent = element.data.label || 'Placeholder';
                    label.style.fontWeight = '600';
                    label.style.fontSize = '0.85rem';
                    const visual = document.createElement('div');
                    visual.className = 'layout-editor__element-placeholder-visual';
                    container.appendChild(label);
                    container.appendChild(visual);
                    renderQrPreview(visual, element.data.sample || '');
                    break;
                }
                default: {
                    container.textContent = element.type;
                    break;
                }
            }

            return container;
        }

        function createElementNode(element) {
            const node = document.createElement('div');
            node.className = 'layout-editor__element layout-editor__element--' + element.type;
            node.dataset.layoutEditorElementId = element.id;
            node.dataset.layoutEditorElementType = element.type;
            node.dataset.layoutEditorElementLabel = labelForTool(element.type);
            updateElementNodePosition(node, element);
            const content = renderElementContent(element);
            node.appendChild(content);

            const handlesContainer = document.createElement('div');
            handlesContainer.className = 'layout-editor__element-handles';
            RESIZE_HANDLES.forEach((handleName) => {
                const handle = document.createElement('div');
                handle.className = 'layout-editor__element-handle';
                handle.dataset.handle = handleName;
                handlesContainer.appendChild(handle);
            });
            node.appendChild(handlesContainer);

            return node;
        }

        function updateSelectionStyles() {
            const nodes = elements.elementsLayer.querySelectorAll('[data-layout-editor-element-id]');
            nodes.forEach((node) => {
                const id = node.dataset.layoutEditorElementId;
                node.classList.toggle('is-selected', id === state.selectedElementId);
            });
        }

        function setSelectedElement(elementId) {
            if (state.selectedElementId === elementId) {
                return;
            }
            state.selectedElementId = elementId;
            updateSelectionStyles();
        }

        function renderElements() {
            elements.elementsLayer.innerHTML = '';
            const pageElements = getActiveElements();
            pageElements.forEach((element) => {
                clampElementToCanvas(element);
                const node = createElementNode(element);
                if (state.selectedElementId === element.id) {
                    node.classList.add('is-selected');
                }
                elements.elementsLayer.appendChild(node);
            });
        }

        function updateElementNodeFromData(element) {
            const node = elements.elementsLayer.querySelector(
                '[data-layout-editor-element-id="' + element.id + '"]'
            );
            if (node) {
                updateElementNodePosition(node, element);
            }
        }

        function generateElementId() {
            state.elementCounter += 1;
            return 'element-' + state.elementCounter;
        }

        function isCreationTool(tool) {
            return CREATION_TOOLS.has(tool);
        }

        function getCanvasCoordinates(event) {
            const rect = elements.inner.getBoundingClientRect();
            return {
                x: (event.clientX - rect.left) / state.zoom,
                y: (event.clientY - rect.top) / state.zoom,
            };
        }

        function createElementAt(tool, point) {
            if (!state.activePageId || !isCreationTool(tool)) {
                return null;
            }

            ensureElementsForPage(state.activePageId);
            const defaults = getElementDefaults(tool);
            const element = {
                id: generateElementId(),
                type: tool,
                x: point.x - defaults.width / 2,
                y: point.y - defaults.height / 2,
                width: defaults.width,
                height: defaults.height,
                rotation: 0,
                data: Object.assign({}, defaults.data),
            };
            clampElementToCanvas(element);
            state.elementsByPage[state.activePageId].push(element);
            renderElements();
            setSelectedElement(element.id);
            updateTool('select');
            return element;
        }

        function beginMove(event, elementId) {
            const element = getElementById(elementId);
            if (!element) {
                return;
            }
            const pointer = getCanvasCoordinates(event);
            state.interaction = {
                type: 'move',
                pointerId: event.pointerId,
                elementId,
                startPointer: pointer,
                startRect: {
                    x: element.x,
                    y: element.y,
                    width: element.width,
                    height: element.height,
                },
            };
            elements.inner.setPointerCapture(event.pointerId);
        }

        function beginResize(event, elementId, handle) {
            const element = getElementById(elementId);
            if (!element) {
                return;
            }
            const pointer = getCanvasCoordinates(event);
            state.interaction = {
                type: 'resize',
                pointerId: event.pointerId,
                elementId,
                handle,
                startPointer: pointer,
                startRect: {
                    x: element.x,
                    y: element.y,
                    width: element.width,
                    height: element.height,
                },
            };
            elements.inner.setPointerCapture(event.pointerId);
        }

        function handleInteractionMove(event) {
            if (!state.interaction || state.interaction.pointerId !== event.pointerId) {
                return;
            }

            const element = getElementById(state.interaction.elementId);
            if (!element) {
                return;
            }

            const pointer = getCanvasCoordinates(event);
            if (state.interaction.type === 'move') {
                const deltaX = pointer.x - state.interaction.startPointer.x;
                const deltaY = pointer.y - state.interaction.startPointer.y;
                element.x = state.interaction.startRect.x + deltaX;
                element.y = state.interaction.startRect.y + deltaY;
            } else if (state.interaction.type === 'resize') {
                const deltaX = pointer.x - state.interaction.startPointer.x;
                const deltaY = pointer.y - state.interaction.startPointer.y;
                let newX = state.interaction.startRect.x;
                let newY = state.interaction.startRect.y;
                let newWidth = state.interaction.startRect.width;
                let newHeight = state.interaction.startRect.height;

                if (state.interaction.handle.includes('right')) {
                    newWidth = Math.max(MIN_ELEMENT_SIZE, state.interaction.startRect.width + deltaX);
                }
                if (state.interaction.handle.includes('left')) {
                    newWidth = Math.max(MIN_ELEMENT_SIZE, state.interaction.startRect.width - deltaX);
                    newX = state.interaction.startRect.x + (state.interaction.startRect.width - newWidth);
                }
                if (state.interaction.handle.includes('bottom')) {
                    newHeight = Math.max(MIN_ELEMENT_SIZE, state.interaction.startRect.height + deltaY);
                }
                if (state.interaction.handle.includes('top')) {
                    newHeight = Math.max(MIN_ELEMENT_SIZE, state.interaction.startRect.height - deltaY);
                    newY = state.interaction.startRect.y + (state.interaction.startRect.height - newHeight);
                }

                element.x = newX;
                element.y = newY;
                element.width = newWidth;
                element.height = newHeight;
            }

            clampElementToCanvas(element);
            updateElementNodeFromData(element);
            event.preventDefault();
        }

        function finishInteraction(event) {
            if (!state.interaction) {
                return;
            }

            if (event && state.interaction.pointerId !== event.pointerId) {
                return;
            }

            try {
                if (event) {
                    elements.inner.releasePointerCapture(state.interaction.pointerId);
                }
            } catch (error) {
                // Ignore pointer capture errors
            }

            state.interaction = null;
        }

        elements.inner.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) {
                return;
            }

            const handleNode = event.target.closest('[data-handle]');
            if (handleNode) {
                const elementNode = handleNode.closest('[data-layout-editor-element-id]');
                if (elementNode) {
                    event.stopPropagation();
                    event.preventDefault();
                    const elementId = elementNode.dataset.layoutEditorElementId;
                    setSelectedElement(elementId);
                    beginResize(event, elementId, handleNode.dataset.handle || '');
                }
                return;
            }

            const elementNode = event.target.closest('[data-layout-editor-element-id]');
            if (elementNode) {
                event.stopPropagation();
                event.preventDefault();
                const elementId = elementNode.dataset.layoutEditorElementId;
                setSelectedElement(elementId);
                if (state.activeTool === 'select') {
                    beginMove(event, elementId);
                }
                return;
            }

            if (isCreationTool(state.activeTool)) {
                event.stopPropagation();
                event.preventDefault();
                const point = getCanvasCoordinates(event);
                createElementAt(state.activeTool, point);
                return;
            }

            if (state.activeTool === 'select') {
                setSelectedElement(null);
            }
        });

        elements.inner.addEventListener('pointermove', (event) => {
            if (state.interaction) {
                handleInteractionMove(event);
            }
        });

        elements.inner.addEventListener('pointerup', (event) => {
            if (state.interaction) {
                handleInteractionMove(event);
                finishInteraction(event);
            }
        });

        elements.inner.addEventListener('pointercancel', (event) => {
            if (state.interaction) {
                finishInteraction(event);
            }
        });

        function renderPages() {
            if (!elements.pagesList) {
                return;
            }

            elements.pagesList.innerHTML = '';
            state.pages.forEach((page) => {
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'layout-editor__page-button';
                if (page.id === state.activePageId) {
                    button.classList.add('is-active');
                }
                button.dataset.layoutEditorPageId = page.id;

                const titleSpan = document.createElement('span');
                titleSpan.className = 'layout-editor__page-title';
                titleSpan.textContent = page.title;

                const metaSpan = document.createElement('span');
                metaSpan.className = 'layout-editor__page-meta';
                metaSpan.textContent = 'ID: ' + page.id;

                button.appendChild(titleSpan);
                button.appendChild(metaSpan);
                item.appendChild(button);
                elements.pagesList.appendChild(item);
            });

            if (elements.emptyState) {
                elements.emptyState.hidden = state.pages.length > 0;
            }

            updatePageStatus();
            updatePaginationButtons();
        }

        function setActivePage(pageId) {
            if (state.activePageId === pageId) {
                return;
            }

            state.activePageId = pageId;
            ensureElementsForPage(pageId);
            setSelectedElement(null);
            renderPages();
            updatePlaceholder();
            renderElements();
            drawCanvas();
        }

        function getActivePageIndex() {
            return state.pages.findIndex((page) => page.id === state.activePageId);
        }

        function goToPage(offset) {
            if (!state.pages.length) {
                return;
            }
            const index = getActivePageIndex();
            const nextIndex = clamp(index + offset, 0, state.pages.length - 1);
            const nextPage = state.pages[nextIndex];
            if (nextPage) {
                setActivePage(nextPage.id);
            }
        }

        function updatePaginationButtons() {
            const index = getActivePageIndex();
            if (elements.prevButton) {
                elements.prevButton.disabled = state.pages.length <= 1 || index <= 0;
            }
            if (elements.nextButton) {
                elements.nextButton.disabled = state.pages.length <= 1 || index === state.pages.length - 1;
            }
        }

        function addPage() {
            pageCounter += 1;
            const id = 'page-' + pageCounter;
            const title = formatTemplate(labels.page, { index: pageCounter });
            const page = { id, title };
            state.pages.push(page);
            ensureElementsForPage(id);
            setActivePage(page.id);
        }

        function setZoom(newZoom, origin) {
            const clamped = clamp(newZoom, MIN_ZOOM, MAX_ZOOM);
            if (Math.abs(clamped - state.zoom) < 0.0001) {
                return;
            }

            const viewportRect = elements.stage.getBoundingClientRect();
            const anchor = origin || {
                x: viewportRect.width / 2,
                y: viewportRect.height / 2,
            };

            const factor = clamped / state.zoom;
            state.pan.x = anchor.x - (anchor.x - state.pan.x) * factor;
            state.pan.y = anchor.y - (anchor.y - state.pan.y) * factor;
            state.zoom = clamped;
            applyTransform();
        }

        function fitToViewport() {
            const viewportRect = elements.viewport.getBoundingClientRect();
            const availableWidth = viewportRect.width;
            const availableHeight = viewportRect.height;
            const scaleX = availableWidth / state.canvasWidth;
            const scaleY = availableHeight / state.canvasHeight;
            const newZoom = clamp(Math.min(scaleX, scaleY), MIN_ZOOM, MAX_ZOOM);

            state.zoom = newZoom;
            state.pan.x = (viewportRect.width - state.canvasWidth * newZoom) / 2;
            state.pan.y = (viewportRect.height - state.canvasHeight * newZoom) / 2;
            applyTransform();
        }

        function updateTool(tool) {
            if (state.activeTool === tool) {
                return;
            }
            state.activeTool = tool;
            const buttons = root.querySelectorAll('[data-layout-editor-tool]');
            buttons.forEach((button) => {
                button.classList.toggle('is-active', button.dataset.layoutEditorTool === tool);
            });
        }

        function applyGridVisibility() {
            if (elements.grid) {
                elements.grid.hidden = !state.showGrid;
            }
            if (elements.toggleGridButton) {
                elements.toggleGridButton.classList.toggle('active', state.showGrid);
                elements.toggleGridButton.setAttribute('aria-pressed', state.showGrid ? 'true' : 'false');
            }
        }

        function toggleGrid() {
            state.showGrid = !state.showGrid;
            applyGridVisibility();
        }

        function applyGuidesVisibility() {
            if (elements.guides) {
                elements.guides.hidden = !state.showGuides;
            }
            if (elements.toggleGuidesButton) {
                elements.toggleGuidesButton.classList.toggle('active', state.showGuides);
                elements.toggleGuidesButton.setAttribute('aria-pressed', state.showGuides ? 'true' : 'false');
            }
        }

        function toggleGuides() {
            state.showGuides = !state.showGuides;
            applyGuidesVisibility();
        }

        function getPointerPosition(event) {
            const rect = elements.stage.getBoundingClientRect();
            return {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top,
            };
        }

        function updateCursorFromEvent(event) {
            const rect = elements.inner.getBoundingClientRect();
            const x = (event.clientX - rect.left) / state.zoom;
            const y = (event.clientY - rect.top) / state.zoom;
            updateCursorDisplay(x, y);
        }

        function drawCanvas() {
            if (!context) {
                return;
            }

            const width = state.canvasWidth;
            const height = state.canvasHeight;

            context.setTransform(1, 0, 0, 1, 0, 0);
            context.clearRect(0, 0, width, height);

            context.fillStyle = '#0f172a';
            context.fillRect(0, 0, width, height);

            context.strokeStyle = 'rgba(148, 163, 184, 0.35)';
            context.lineWidth = 1;
            context.strokeRect(40.5, 40.5, width - 81, height - 81);

            context.font = '16px "Inter", "Segoe UI", sans-serif';
            context.fillStyle = 'rgba(226, 232, 240, 0.9)';
            const activePage = state.pages.find((page) => page.id === state.activePageId);
            const title = activePage ? activePage.title : '';
            context.fillText(title, 52, 70);

            context.font = '12px "Inter", "Segoe UI", sans-serif';
            context.fillStyle = 'rgba(148, 163, 184, 0.8)';
            context.fillText('Canvas: ' + width + ' × ' + height + ' px', 52, 94);
        }

        function handleAction(action, target) {
            switch (action) {
                case 'add-page':
                    addPage();
                    break;
                case 'previous-page':
                    goToPage(-1);
                    break;
                case 'next-page':
                    goToPage(1);
                    break;
                case 'zoom-in':
                    setZoom(state.zoom * ZOOM_STEP);
                    break;
                case 'zoom-out':
                    setZoom(state.zoom / ZOOM_STEP);
                    break;
                case 'zoom-fit':
                    fitToViewport();
                    break;
                case 'toggle-grid':
                    toggleGrid();
                    break;
                case 'toggle-guides':
                    toggleGuides();
                    break;
                default:
                    break;
            }
        }

        function handleTool(tool) {
            if (tool) {
                updateTool(tool);
            }
        }

        let isPanning = false;
        let panStart = { x: 0, y: 0, panX: 0, panY: 0 };

        elements.stage.addEventListener('pointerdown', (event) => {
            const isMiddleButton = event.button === 1;
            const isRightButton = event.button === 2;
            const isModifiedPan = event.altKey || event.metaKey || event.shiftKey || event.ctrlKey;
            const allowPan = isMiddleButton || isRightButton || (event.button === 0 && isModifiedPan);
            if (!allowPan) {
                return;
            }

            if (event.target.closest('[data-layout-editor-element-id]')) {
                return;
            }

            isPanning = true;
            panStart = {
                x: event.clientX,
                y: event.clientY,
                panX: state.pan.x,
                panY: state.pan.y,
            };
            elements.stage.setPointerCapture(event.pointerId);
            event.preventDefault();
        });

        elements.stage.addEventListener('pointermove', (event) => {
            if (isPanning) {
                const deltaX = event.clientX - panStart.x;
                const deltaY = event.clientY - panStart.y;
                state.pan.x = panStart.panX + deltaX;
                state.pan.y = panStart.panY + deltaY;
                applyTransform();
            }
            updateCursorFromEvent(event);
        });

        function endPan(event) {
            if (isPanning) {
                isPanning = false;
                try {
                    elements.stage.releasePointerCapture(event.pointerId);
                } catch (error) {
                    // ignore
                }
            }
        }

        elements.stage.addEventListener('pointerup', endPan);
        elements.stage.addEventListener('pointercancel', endPan);
        elements.stage.addEventListener('pointerleave', (event) => {
            if (isPanning) {
                isPanning = false;
                try {
                    elements.stage.releasePointerCapture(event.pointerId);
                } catch (error) {
                    // ignore
                }
            }
            updateCursorDisplay(null, null);
        });

        elements.viewport.addEventListener(
            'wheel',
            (event) => {
                if (event.ctrlKey || event.metaKey) {
                    event.preventDefault();
                    const point = getPointerPosition(event);
                    const zoomFactor = event.deltaY < 0 ? ZOOM_STEP : 1 / ZOOM_STEP;
                    setZoom(state.zoom * zoomFactor, point);
                } else {
                    state.pan.x -= event.deltaX;
                    state.pan.y -= event.deltaY;
                    applyTransform();
                    event.preventDefault();
                }
            },
            { passive: false }
        );

        root.addEventListener('click', (event) => {
            const actionButton = event.target.closest('[data-layout-editor-action]');
            if (actionButton) {
                event.preventDefault();
                handleAction(actionButton.dataset.layoutEditorAction, actionButton);
                return;
            }

            const toolButton = event.target.closest('[data-layout-editor-tool]');
            if (toolButton) {
                event.preventDefault();
                handleTool(toolButton.dataset.layoutEditorTool);
            }

            const pageButton = event.target.closest('[data-layout-editor-page-id]');
            if (pageButton) {
                event.preventDefault();
                setActivePage(pageButton.dataset.layoutEditorPageId || null);
            }
        });

        root.addEventListener('keydown', (event) => {
            if ((event.metaKey || event.ctrlKey) && !event.shiftKey) {
                if (event.key === '+' || event.key === '=') {
                    event.preventDefault();
                    setZoom(state.zoom * ZOOM_STEP);
                } else if (event.key === '-') {
                    event.preventDefault();
                    setZoom(state.zoom / ZOOM_STEP);
                }
            }
        });

        applyDimensions();
        applyTransform();
        renderPages();
        updatePlaceholder();
        ensureElementsForPage(state.activePageId);
        renderElements();
        updateCursorDisplay(0, 0);
        applyGridVisibility();
        applyGuidesVisibility();
        drawCanvas();
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-layout-editor]').forEach((root) => {
            initLayoutEditor(root);
        });
    });
})();
