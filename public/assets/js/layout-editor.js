(function () {
    'use strict';

    const MIN_ZOOM = 0.25;
    const MAX_ZOOM = 4;
    const ZOOM_STEP = 1.2;
    const RULER_STEP = 100;

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

        const pages = Array.isArray(config.pages) ? config.pages.slice() : [];
        let pageCounter = pages.reduce((max, page) => {
            const match = /page-(\d+)/i.exec(page.id || '');
            return match ? Math.max(max, Number.parseInt(match[1], 10)) : max;
        }, pages.length);
        if (!pageCounter) {
            pageCounter = pages.length;
        }

        const state = {
            zoom: Number(config.zoom) || 1,
            pan: { x: 0, y: 0 },
            pages,
            activePageId: config.activePageId || (pages[0] ? pages[0].id : null),
            showGrid: true,
            showGuides: true,
            activeTool: 'select',
            canvasWidth: Number(canvasConfig.width) || 1024,
            canvasHeight: Number(canvasConfig.height) || 768,
            gridSize: Number(canvasConfig.gridSize) || 40,
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
            placeholder: root.querySelector('[data-layout-editor-placeholder]'),
            cursor: root.querySelector('[data-layout-editor-cursor]'),
            prevButton: root.querySelector('[data-layout-editor-action="previous-page"]'),
            nextButton: root.querySelector('[data-layout-editor-action="next-page"]'),
            rulerHorizontal: root.querySelector('[data-layout-editor-ruler-scale="horizontal"]'),
            rulerVertical: root.querySelector('[data-layout-editor-ruler-scale="vertical"]'),
            toggleGridButton: root.querySelector('[data-layout-editor-action="toggle-grid"]'),
            toggleGuidesButton: root.querySelector('[data-layout-editor-action="toggle-guides"]'),
        };

        if (!elements.viewport || !elements.stage || !elements.inner || !elements.canvas) {
            return;
        }

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
            renderPages();
            updatePlaceholder();
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
            if (event.button !== 0) {
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
                elements.stage.releasePointerCapture(event.pointerId);
            }
        }

        elements.stage.addEventListener('pointerup', endPan);
        elements.stage.addEventListener('pointercancel', endPan);
        elements.stage.addEventListener('pointerleave', (event) => {
            if (isPanning) {
                isPanning = false;
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
