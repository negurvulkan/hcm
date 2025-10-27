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

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function ensureRuntime(element) {
        if (!element) {
            return null;
        }
        if (!Object.prototype.hasOwnProperty.call(element, '__runtime')) {
            Object.defineProperty(element, '__runtime', {
                value: {
                    previewHtml: '',
                    previewError: null,
                    previewLoading: false,
                    lastRequestId: 0,
                },
                writable: true,
                configurable: true,
                enumerable: false,
            });
        }
        return element.__runtime;
    }

    function getRuntime(element) {
        if (!element) {
            return null;
        }
        return element.__runtime || null;
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

        const fallbackFonts = [
            'Inter, "Segoe UI", sans-serif',
            'Roboto, "Helvetica Neue", Arial, sans-serif',
            'Montserrat, "Segoe UI", sans-serif',
            'Open Sans, "Helvetica Neue", sans-serif',
        ];
        const configuredFonts = Array.isArray(config.fonts)
            ? config.fonts.filter((font) => typeof font === 'string' && font.trim() !== '')
            : [];
        const fonts = configuredFonts.length ? configuredFonts : fallbackFonts;

        const defaultMessages = {
            previewLoading: 'Updating preview…',
            previewError: 'Expression error',
            placeholderHint: '',
        };
        const messages = Object.assign({}, defaultMessages, typeof config.messages === 'object' && config.messages ? config.messages : {});
        const apiBase =
            typeof config.api === 'object' && config.api && typeof config.api.base === 'string' && config.api.base
                ? config.api.base
                : 'layout_editor_api.php';

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
                    fontFamily: fonts[0] || fallbackFonts[0],
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
                    label: 'Datenplatzhalter',
                    sample: 'Anna Schmidt',
                    expression: '{{ person.name }}',
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
            const parsedOpacity = raw && raw.opacity !== undefined ? Number.parseFloat(raw.opacity) : Number.NaN;
            const rawVisible = raw && raw.visible !== undefined ? Boolean(raw.visible) : null;

            const element = {
                id: raw && raw.id ? String(raw.id) : nextElementIdLocal(),
                type,
                x: Number.isFinite(parsedX) ? parsedX : 80,
                y: Number.isFinite(parsedY) ? parsedY : 80,
                width: Number.isFinite(parsedWidth) ? Math.max(MIN_ELEMENT_SIZE, parsedWidth) : defaults.width,
                height: Number.isFinite(parsedHeight) ? Math.max(MIN_ELEMENT_SIZE, parsedHeight) : defaults.height,
                rotation: Number.isFinite(parsedRotation) ? parsedRotation : 0,
                opacity: Number.isFinite(parsedOpacity) ? clamp(parsedOpacity, 0, 1) : 1,
                visible: rawVisible !== null ? rawVisible : true,
                data: Object.assign({}, defaults.data, raw && typeof raw.data === 'object' && raw.data ? raw.data : {}),
            };
            if (element.type === 'text' && (!element.data.fontFamily || element.data.fontFamily === '')) {
                element.data.fontFamily = defaults.data.fontFamily || fonts[0] || fallbackFonts[0];
            }
            if (element.type === 'placeholder') {
                element.data.expression = typeof element.data.expression === 'string' ? element.data.expression : '';
                element.data.sample = typeof element.data.sample === 'string' ? element.data.sample : '';
                ensureRuntime(element);
            }
            registerElementIdLocal(element.id);
            return element;
        }

        pages.forEach((page, index) => {
            const rawPage = rawPages[index] || page;
            const rawElements = Array.isArray(rawPage.elements) ? rawPage.elements : [];
            elementsByPage[page.id] = rawElements.map((element) => normalizeElement(element));
        });

        Object.values(elementsByPage).forEach((pageElements) => {
            pageElements.forEach((element) => {
                if (element.type === 'placeholder') {
                    const runtime = ensureRuntime(element);
                    if (runtime && !runtime.previewHtml) {
                        runtime.previewHtml = element.data.sample ? escapeHtml(element.data.sample) : '';
                    }
                }
            });
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
            fonts,
            messages,
            apiBase,
            csrfToken: null,
            placeholderGroups: [],
            placeholderSuggestions: [],
            previewDataset: null,
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
            propertiesPanel: root.querySelector('[data-layout-editor-properties]'),
            propertiesEmpty: root.querySelector('[data-layout-editor-properties-empty]'),
            propertiesForm: root.querySelector('[data-layout-editor-properties-form]'),
            visibilityButton: root.querySelector('[data-layout-editor-visibility-button]'),
            layerIndicator: root.querySelector('[data-layout-editor-layer-indicator]'),
            opacityDisplay: root.querySelector('[data-layout-editor-property-display="opacity"]'),
            selectedName: root.querySelector('[data-layout-editor-selected-name]'),
            selectedMeta: root.querySelector('[data-layout-editor-selected-meta]'),
            expressionError: root.querySelector('[data-layout-editor-expression-error]'),
        };

        if (!elements.viewport || !elements.stage || !elements.inner || !elements.canvas || !elements.elementsLayer) {
            return;
        }

        const propertyInputs = {};
        root.querySelectorAll('[data-layout-editor-property]').forEach((input) => {
            const key = input.dataset.layoutEditorProperty;
            if (key) {
                propertyInputs[key] = input;
            }
        });

        const dataInputs = {};
        root.querySelectorAll('[data-layout-editor-data]').forEach((input) => {
            const key = input.dataset.layoutEditorData;
            if (key) {
                dataInputs[key] = input;
            }
        });

        const expressionInput = dataInputs.expression || null;

        const propertySections = Array.from(root.querySelectorAll('[data-layout-editor-properties-for]'));

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
        const previewTimers = new Map();
        let previewRequestSeq = 0;
        const autocomplete = createAutocompleteOverlay();

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

        function getElementLayerIndex(elementId) {
            if (!elementId) {
                return -1;
            }
            const pageElements = getActiveElements();
            return pageElements.findIndex((item) => item.id === elementId);
        }

        function getSelectedElement() {
            return getElementById(state.selectedElementId);
        }

        function updatePropertyPanel() {
            if (!elements.propertiesPanel) {
                return;
            }

            const selected = getSelectedElement();
            const pageElements = getActiveElements();
            if (!selected) {
                if (elements.propertiesForm) {
                    elements.propertiesForm.hidden = true;
                }
                if (elements.propertiesEmpty) {
                    elements.propertiesEmpty.hidden = false;
                }
                if (elements.visibilityButton) {
                    elements.visibilityButton.disabled = true;
                }
                if (elements.expressionError) {
                    elements.expressionError.hidden = true;
                    elements.expressionError.textContent = '';
                    elements.expressionError.classList.remove('text-danger', 'text-muted');
                }
                propertySections.forEach((section) => {
                    section.hidden = true;
                });
                return;
            }

            if (elements.propertiesForm) {
                elements.propertiesForm.hidden = false;
            }
            if (elements.propertiesEmpty) {
                elements.propertiesEmpty.hidden = true;
            }
            if (elements.visibilityButton) {
                elements.visibilityButton.disabled = false;
                const labelVisible = elements.visibilityButton.dataset.layoutEditorVisibilityLabelVisible;
                const labelHidden = elements.visibilityButton.dataset.layoutEditorVisibilityLabelHidden;
                elements.visibilityButton.textContent = selected.visible && labelVisible ? labelVisible : labelHidden || elements.visibilityButton.textContent;
                elements.visibilityButton.setAttribute('aria-pressed', selected.visible ? 'true' : 'false');
            }
            if (elements.selectedName) {
                elements.selectedName.textContent = labelForTool(selected.type) || selected.type;
            }
            if (elements.selectedMeta) {
                elements.selectedMeta.textContent = 'ID: ' + selected.id;
            }
            if (elements.layerIndicator) {
                const index = getElementLayerIndex(selected.id);
                const total = pageElements.length || 0;
                elements.layerIndicator.textContent = index >= 0 ? index + 1 + ' / ' + total : '–';
            }

            const numericFields = {
                x: Math.round(selected.x),
                y: Math.round(selected.y),
                width: Math.round(selected.width),
                height: Math.round(selected.height),
                rotation: Math.round(selected.rotation || 0),
                opacity: Math.round((Number.isFinite(selected.opacity) ? clamp(selected.opacity, 0, 1) : 1) * 100),
            };
            Object.keys(numericFields).forEach((key) => {
                if (propertyInputs[key]) {
                    propertyInputs[key].value = String(numericFields[key]);
                }
            });
            if (elements.opacityDisplay) {
                elements.opacityDisplay.textContent = numericFields.opacity + '%';
            }

            propertySections.forEach((section) => {
                const typesAttr = section.dataset.layoutEditorPropertiesFor || '';
                const allowed = typesAttr.split(',').map((item) => item.trim()).filter(Boolean);
                section.hidden = allowed.length > 0 && !allowed.includes(selected.type);
            });

            if (dataInputs.text) {
                dataInputs.text.value = selected.data.text || '';
            }
            if (dataInputs.subline) {
                dataInputs.subline.value = selected.data.subline || '';
            }
            if (dataInputs.fontFamily) {
                dataInputs.fontFamily.value = selected.data.fontFamily || fonts[0] || fallbackFonts[0];
            }
            if (dataInputs.alt) {
                dataInputs.alt.value = selected.data.alt || '';
            }
            if (dataInputs.src) {
                dataInputs.src.value = selected.data.src || '';
            }
            if (dataInputs.variant) {
                dataInputs.variant.value = selected.data.variant || 'rectangle';
            }
            if (dataInputs.rows) {
                dataInputs.rows.value = Number.isFinite(Number.parseInt(selected.data.rows, 10))
                    ? Number.parseInt(selected.data.rows, 10)
                    : 3;
            }
            if (dataInputs.cols) {
                dataInputs.cols.value = Number.isFinite(Number.parseInt(selected.data.cols, 10))
                    ? Number.parseInt(selected.data.cols, 10)
                    : 4;
            }
            if (dataInputs.label) {
                dataInputs.label.value = selected.data.label || '';
            }
            if (dataInputs.expression) {
                dataInputs.expression.value = selected.data.expression || '';
            }
            if (dataInputs.sample) {
                dataInputs.sample.value = selected.data.sample || '';
            }
            if (elements.expressionError) {
                const runtime = getRuntime(selected);
                const error = runtime ? runtime.previewError : null;
                const loading = runtime ? runtime.previewLoading : false;
                if (error) {
                    elements.expressionError.hidden = false;
                    elements.expressionError.textContent = error || state.messages.previewError;
                    elements.expressionError.classList.remove('text-muted');
                    elements.expressionError.classList.add('text-danger');
                } else if (loading) {
                    elements.expressionError.hidden = false;
                    elements.expressionError.textContent = state.messages.previewLoading;
                    elements.expressionError.classList.remove('text-danger');
                    elements.expressionError.classList.add('text-muted');
                } else {
                    elements.expressionError.hidden = true;
                    elements.expressionError.textContent = '';
                    elements.expressionError.classList.remove('text-danger', 'text-muted');
                }
            }
        }

        function updateSelectedElementProperty(property, rawValue) {
            const element = getSelectedElement();
            if (!element) {
                return;
            }
            const parsedValue = Number.parseFloat(rawValue);
            if (property === 'x' && Number.isFinite(parsedValue)) {
                element.x = parsedValue;
            } else if (property === 'y' && Number.isFinite(parsedValue)) {
                element.y = parsedValue;
            } else if (property === 'width' && Number.isFinite(parsedValue)) {
                element.width = Math.max(MIN_ELEMENT_SIZE, parsedValue);
            } else if (property === 'height' && Number.isFinite(parsedValue)) {
                element.height = Math.max(MIN_ELEMENT_SIZE, parsedValue);
            } else if (property === 'rotation' && Number.isFinite(parsedValue)) {
                element.rotation = parsedValue;
            } else if (property === 'opacity' && Number.isFinite(parsedValue)) {
                element.opacity = clamp(parsedValue / 100, 0, 1);
            }
            clampElementToCanvas(element);
            updateElementNodeFromData(element);
            updatePropertyPanel();
        }

        function normalizeTableValue(raw, fallback) {
            const parsed = Number.parseInt(raw, 10);
            if (!Number.isFinite(parsed) || parsed <= 0) {
                return fallback;
            }
            return Math.max(1, Math.min(parsed, 50));
        }

        function updateSelectedElementData(key, value) {
            const element = getSelectedElement();
            if (!element) {
                return;
            }
            if (!element.data || typeof element.data !== 'object') {
                element.data = {};
            }
            switch (key) {
                case 'text':
                    element.data.text = value;
                    break;
                case 'subline':
                    element.data.subline = value;
                    break;
                case 'fontFamily':
                    element.data.fontFamily = value || fonts[0] || fallbackFonts[0];
                    break;
                case 'alt':
                    element.data.alt = value;
                    break;
                case 'src':
                    element.data.src = value;
                    break;
                case 'variant': {
                    const allowed = ['rectangle', 'circle'];
                    element.data.variant = allowed.includes(value) ? value : 'rectangle';
                    break;
                }
                case 'rows':
                    element.data.rows = normalizeTableValue(value, 3);
                    break;
                case 'cols':
                    element.data.cols = normalizeTableValue(value, 4);
                    break;
                case 'label':
                    element.data.label = value;
                    break;
                case 'expression':
                    element.data.expression = value;
                    schedulePlaceholderPreview(element);
                    break;
                case 'sample':
                    element.data.sample = value;
                    if (!element.data.expression || element.data.expression.trim() === '') {
                        applySamplePreview(element);
                    }
                    break;
                default:
                    element.data[key] = value;
                    break;
            }
            renderElements();
        }

        function reorderElement(elementId, newIndex) {
            const pageElements = getActiveElements();
            const currentIndex = pageElements.findIndex((item) => item.id === elementId);
            if (currentIndex === -1 || newIndex === currentIndex) {
                return;
            }
            const targetIndex = clamp(newIndex, 0, pageElements.length - 1);
            const [element] = pageElements.splice(currentIndex, 1);
            pageElements.splice(targetIndex, 0, element);
            renderElements();
        }

        function bringForward(elementId) {
            const index = getElementLayerIndex(elementId);
            if (index === -1) {
                return;
            }
            reorderElement(elementId, index + 1);
        }

        function sendBackward(elementId) {
            const index = getElementLayerIndex(elementId);
            if (index === -1) {
                return;
            }
            reorderElement(elementId, index - 1);
        }

        function bringToFront(elementId) {
            const pageElements = getActiveElements();
            if (!pageElements.length) {
                return;
            }
            reorderElement(elementId, pageElements.length - 1);
        }

        function sendToBack(elementId) {
            reorderElement(elementId, 0);
        }

        function toggleVisibility(elementId) {
            const element = getElementById(elementId);
            if (!element) {
                return;
            }
            element.visible = !element.visible;
            updateElementNodeFromData(element);
            updatePropertyPanel();
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
            const rotation = Number.isFinite(element.rotation) ? element.rotation : 0;
            node.style.transform = 'rotate(' + rotation + 'deg)';
            const baseOpacity = Number.isFinite(element.opacity) ? clamp(element.opacity, 0, 1) : 1;
            const effectiveOpacity = element.visible ? baseOpacity : Math.min(baseOpacity, 0.35);
            node.style.opacity = effectiveOpacity;
            node.classList.toggle('is-hidden', !element.visible);
            const layerIndex = getElementLayerIndex(element.id);
            if (layerIndex >= 0) {
                node.style.zIndex = String(layerIndex + 1);
            }
        }

        function labelForTool(tool) {
            if (!tool) {
                return '';
            }
            return toolLabels[tool] || tool.charAt(0).toUpperCase() + tool.slice(1);
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
                    const fontFamily = element.data.fontFamily || fonts[0] || fallbackFonts[0];
                    container.style.fontFamily = fontFamily;
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
                    container.appendChild(label);
                    const visual = document.createElement('div');
                    visual.className = 'layout-editor__element-placeholder-visual';
                    const runtime = getRuntime(element) || ensureRuntime(element);
                    const previewError = runtime ? runtime.previewError : null;
                    const previewLoading = runtime ? runtime.previewLoading : false;
                    const previewHtml = runtime ? runtime.previewHtml : '';
                    if (previewError) {
                        const errorLabel = document.createElement('div');
                        errorLabel.textContent = previewError || state.messages.previewError;
                        errorLabel.style.color = '#b91c1c';
                        errorLabel.style.fontWeight = '600';
                        visual.appendChild(errorLabel);
                    } else if (previewLoading) {
                        const loadingLabel = document.createElement('div');
                        loadingLabel.textContent = state.messages.previewLoading;
                        loadingLabel.style.color = 'rgba(71, 85, 105, 0.9)';
                        visual.appendChild(loadingLabel);
                    } else if (previewHtml) {
                        visual.innerHTML = previewHtml;
                    } else if (element.data.sample) {
                        const sample = document.createElement('div');
                        sample.textContent = element.data.sample;
                        visual.appendChild(sample);
                    } else {
                        const empty = document.createElement('div');
                        empty.textContent = '—';
                        empty.style.opacity = '0.75';
                        visual.appendChild(empty);
                    }
                    container.appendChild(visual);
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
                updatePropertyPanel();
                return;
            }
            closeAutocomplete();
            state.selectedElementId = elementId;
            updateSelectionStyles();
            updatePropertyPanel();
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
            updateSelectionStyles();
            updatePropertyPanel();
        }

        function applySamplePreview(element) {
            if (!element || element.type !== 'placeholder') {
                return;
            }
            const runtime = ensureRuntime(element);
            if (!runtime) {
                return;
            }
            runtime.previewLoading = false;
            runtime.previewError = null;
            runtime.previewHtml = element.data.sample ? escapeHtml(element.data.sample) : '';
        }

        function schedulePlaceholderPreview(element) {
            if (!element || element.type !== 'placeholder') {
                return;
            }
            if (previewTimers.has(element.id)) {
                clearTimeout(previewTimers.get(element.id));
            }
            const timeout = window.setTimeout(() => {
                previewTimers.delete(element.id);
                requestPlaceholderPreview(element);
            }, 350);
            previewTimers.set(element.id, timeout);
        }

        function requestPlaceholderPreview(element) {
            if (!element || element.type !== 'placeholder') {
                return;
            }
            const runtime = ensureRuntime(element);
            if (!runtime) {
                return;
            }
            const expression = (element.data.expression || '').trim();
            if (expression === '') {
                applySamplePreview(element);
                renderElements();
                updatePropertyPanel();
                return;
            }

            if (!state.csrfToken) {
                runtime.previewLoading = false;
                runtime.previewError = null;
                runtime.previewHtml = element.data.sample ? escapeHtml(element.data.sample) : '';
                renderElements();
                updatePropertyPanel();
                return;
            }

            runtime.previewLoading = true;
            runtime.previewError = null;
            runtime.previewHtml = '';
            renderElements();
            updatePropertyPanel();

            previewRequestSeq += 1;
            const requestId = previewRequestSeq;
            runtime.lastRequestId = requestId;

            fetch(state.apiBase + '?action=render', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': state.csrfToken || '',
                },
                body: JSON.stringify({
                    template: expression,
                    _token: state.csrfToken || undefined,
                }),
            })
                .then(async (response) => {
                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }
                    return payload;
                })
                .then((payload) => {
                    const currentRuntime = getRuntime(element);
                    if (!currentRuntime || currentRuntime.lastRequestId !== requestId) {
                        return;
                    }
                    if (payload && typeof payload === 'object' && payload.csrf) {
                        state.csrfToken = payload.csrf;
                    }
                    currentRuntime.previewLoading = false;
                    if (payload && payload.status === 'ok') {
                        currentRuntime.previewHtml = typeof payload.html === 'string' ? payload.html : '';
                        currentRuntime.previewError = null;
                    } else if (payload && payload.status === 'error') {
                        currentRuntime.previewHtml = '';
                        currentRuntime.previewError = payload.message || state.messages.previewError;
                    } else {
                        currentRuntime.previewHtml = '';
                        currentRuntime.previewError = state.messages.previewError;
                    }
                    renderElements();
                    updatePropertyPanel();
                })
                .catch((error) => {
                    console.warn('Unable to render placeholder preview', error);
                    const currentRuntime = getRuntime(element);
                    if (!currentRuntime || currentRuntime.lastRequestId !== requestId) {
                        return;
                    }
                    currentRuntime.previewLoading = false;
                    currentRuntime.previewHtml = '';
                    currentRuntime.previewError = state.messages.previewError;
                    renderElements();
                    updatePropertyPanel();
                });
        }

        function refreshAllPlaceholderPreviews() {
            Object.values(state.elementsByPage).forEach((pageElements) => {
                pageElements.forEach((element) => {
                    if (element.type === 'placeholder') {
                        requestPlaceholderPreview(element);
                    }
                });
            });
        }

        function flattenPlaceholderSuggestions(groups) {
            const suggestions = [];
            if (!Array.isArray(groups)) {
                return suggestions;
            }
            groups.forEach((group) => {
                const groupName = typeof group.group === 'string' ? group.group : '';
                const items = Array.isArray(group.items) ? group.items : [];
                items.forEach((item) => {
                    if (!item || typeof item.insert !== 'string') {
                        return;
                    }
                    const path = typeof item.path === 'string' ? item.path : '';
                    const title = typeof item.label === 'string' && item.label !== '' ? item.label : path;
                    suggestions.push({
                        group: groupName,
                        insert: item.insert,
                        path,
                        title,
                        example: typeof item.example === 'string' ? item.example : '',
                        type: typeof item.type === 'string' ? item.type : 'variable',
                        hint: typeof item.hint === 'string' ? item.hint : '',
                        search: typeof item.search === 'string'
                            ? item.search
                            : (path + ' ' + title + ' ' + groupName).toLowerCase(),
                    });
                });
            });
            return suggestions;
        }

        function loadPlaceholderMeta() {
            fetch(state.apiBase + '?action=meta', { credentials: 'same-origin' })
                .then(async (response) => {
                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }
                    return payload;
                })
                .then((payload) => {
                    if (!payload || payload.status !== 'ok') {
                        return;
                    }
                    if (payload.csrf) {
                        state.csrfToken = payload.csrf;
                    }
                    state.placeholderGroups = Array.isArray(payload.placeholders) ? payload.placeholders : [];
                    state.placeholderSuggestions = flattenPlaceholderSuggestions(state.placeholderGroups);
                    state.previewDataset = payload.dataset || null;
                    refreshAllPlaceholderPreviews();
                })
                .catch((error) => {
                    console.error('Unable to load layout placeholders', error);
                });
        }

        function createAutocompleteOverlay() {
            const container = document.createElement('div');
            container.className = 'layout-editor__autocomplete';
            container.hidden = true;
            container.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });
            document.body.appendChild(container);
            return {
                container,
                suggestions: [],
                activeIndex: 0,
                range: null,
                anchor: null,
                visible: false,
            };
        }

        function positionAutocompleteOverlay() {
            if (!autocomplete.anchor) {
                return;
            }
            const rect = autocomplete.anchor.getBoundingClientRect();
            autocomplete.container.style.left = rect.left + window.scrollX + 'px';
            autocomplete.container.style.top = rect.bottom + window.scrollY + 4 + 'px';
            autocomplete.container.style.width = rect.width + 'px';
        }

        function renderAutocompleteSuggestions() {
            autocomplete.container.innerHTML = '';
            autocomplete.suggestions.forEach((suggestion, index) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'layout-editor__autocomplete-item';
                if (index === autocomplete.activeIndex) {
                    item.classList.add('is-active');
                }
                const title = document.createElement('div');
                title.className = 'layout-editor__autocomplete-label';
                title.innerHTML = escapeHtml(suggestion.title || suggestion.path || suggestion.insert);
                item.appendChild(title);
                const metaParts = [];
                if (suggestion.group) {
                    metaParts.push(suggestion.group);
                }
                if (suggestion.path && suggestion.path !== suggestion.title) {
                    metaParts.push(suggestion.path);
                }
                if (suggestion.hint) {
                    metaParts.push(suggestion.hint);
                }
                if (metaParts.length) {
                    const meta = document.createElement('div');
                    meta.className = 'layout-editor__autocomplete-meta';
                    meta.innerHTML = escapeHtml(metaParts.join(' · '));
                    item.appendChild(meta);
                }
                if (suggestion.example) {
                    const example = document.createElement('div');
                    example.className = 'layout-editor__autocomplete-example';
                    example.innerHTML = escapeHtml(suggestion.example);
                    item.appendChild(example);
                }
                item.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    applyAutocompleteSelection(index);
                });
                autocomplete.container.appendChild(item);
            });
        }

        function closeAutocomplete() {
            autocomplete.visible = false;
            autocomplete.container.hidden = true;
            autocomplete.container.innerHTML = '';
            autocomplete.suggestions = [];
            autocomplete.activeIndex = 0;
            autocomplete.range = null;
            autocomplete.anchor = null;
        }

        function openAutocomplete(input, range, suggestions) {
            autocomplete.anchor = input;
            autocomplete.range = range;
            autocomplete.suggestions = suggestions.slice(0, 12);
            autocomplete.activeIndex = 0;
            if (!autocomplete.suggestions.length) {
                closeAutocomplete();
                return;
            }
            renderAutocompleteSuggestions();
            positionAutocompleteOverlay();
            autocomplete.container.hidden = false;
            autocomplete.visible = true;
        }

        function findAutocompleteRange(value, caret) {
            const openIndex = value.lastIndexOf('{{', caret);
            if (openIndex === -1) {
                return null;
            }
            const closeIndex = value.lastIndexOf('}}', caret);
            if (closeIndex !== -1 && closeIndex > openIndex) {
                return null;
            }
            const segment = value.slice(openIndex + 2, caret);
            if (segment.includes('\n')) {
                return null;
            }
            return {
                start: openIndex,
                end: caret,
                term: segment,
            };
        }

        function filterSuggestions(term) {
            if (!state.placeholderSuggestions.length) {
                return [];
            }
            const raw = term.trim();
            let mode = 'variable';
            let searchTerm = raw;
            if (raw.startsWith('#')) {
                const remainder = raw.slice(1).trim();
                if (remainder.startsWith('if')) {
                    mode = 'if';
                    searchTerm = remainder.slice(2).trim();
                } else if (remainder.startsWith('each')) {
                    mode = 'each';
                    searchTerm = remainder.slice(4).trim();
                } else {
                    mode = 'block';
                    searchTerm = remainder;
                }
            }
            const normalized = searchTerm.toLowerCase();
            return state.placeholderSuggestions
                .filter((suggestion) => {
                    if (mode === 'if') {
                        if (!(suggestion.type === 'variable' || suggestion.type === 'if')) {
                            return false;
                        }
                    } else if (mode === 'each') {
                        if (suggestion.type !== 'each') {
                            return false;
                        }
                    } else if (mode === 'block') {
                        if (suggestion.type === 'variable') {
                            return false;
                        }
                    }
                    if (!normalized) {
                        return true;
                    }
                    return (
                        suggestion.search.includes(normalized) ||
                        (suggestion.title && suggestion.title.toLowerCase().includes(normalized)) ||
                        (suggestion.path && suggestion.path.toLowerCase().includes(normalized))
                    );
                })
                .slice(0, 12);
        }

        function updateAutocompleteForExpression(input) {
            if (!input) {
                closeAutocomplete();
                return;
            }
            const caret = input.selectionStart;
            const end = input.selectionEnd;
            if (caret === null || end === null || caret !== end) {
                closeAutocomplete();
                return;
            }
            const range = findAutocompleteRange(input.value, caret);
            if (!range) {
                closeAutocomplete();
                return;
            }
            const suggestions = filterSuggestions(range.term);
            if (!suggestions.length) {
                closeAutocomplete();
                return;
            }
            openAutocomplete(input, range, suggestions);
        }

        function applyAutocompleteSelection(index) {
            const suggestion = autocomplete.suggestions[index];
            if (!suggestion || !autocomplete.anchor || !autocomplete.range) {
                return;
            }
            const input = autocomplete.anchor;
            const before = input.value.slice(0, autocomplete.range.start);
            const after = input.value.slice(autocomplete.range.end);
            const insert = suggestion.insert;
            input.value = before + insert + after;
            const caret = before.length + insert.length;
            input.setSelectionRange(caret, caret);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            closeAutocomplete();
            input.focus();
        }

        function handleExpressionKeyDown(event) {
            if (!autocomplete.visible) {
                if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && state.placeholderSuggestions.length) {
                    updateAutocompleteForExpression(event.target);
                    if (autocomplete.visible) {
                        event.preventDefault();
                    }
                }
                return;
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                autocomplete.activeIndex = (autocomplete.activeIndex + 1) % autocomplete.suggestions.length;
                renderAutocompleteSuggestions();
                return;
            }
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                autocomplete.activeIndex =
                    (autocomplete.activeIndex - 1 + autocomplete.suggestions.length) % autocomplete.suggestions.length;
                renderAutocompleteSuggestions();
                return;
            }
            if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                applyAutocompleteSelection(autocomplete.activeIndex);
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                closeAutocomplete();
            }
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
                opacity: 1,
                visible: true,
                data: Object.assign({}, defaults.data),
            };
            clampElementToCanvas(element);
            if (tool === 'placeholder') {
                ensureRuntime(element);
                applySamplePreview(element);
                schedulePlaceholderPreview(element);
            }
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
            updatePropertyPanel();
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
                case 'bring-forward':
                    if (state.selectedElementId) {
                        bringForward(state.selectedElementId);
                    }
                    break;
                case 'send-backward':
                    if (state.selectedElementId) {
                        sendBackward(state.selectedElementId);
                    }
                    break;
                case 'bring-to-front':
                    if (state.selectedElementId) {
                        bringToFront(state.selectedElementId);
                    }
                    break;
                case 'send-to-back':
                    if (state.selectedElementId) {
                        sendToBack(state.selectedElementId);
                    }
                    break;
                case 'toggle-visibility':
                    if (state.selectedElementId) {
                        toggleVisibility(state.selectedElementId);
                    }
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

        if (elements.propertiesForm) {
            const handlePropertyInput = (event) => {
                const target = event.target;
                if (!target) {
                    return;
                }
                const propertyKey = target.dataset.layoutEditorProperty;
                const dataKey = target.dataset.layoutEditorData;
                if (propertyKey) {
                    updateSelectedElementProperty(propertyKey, target.value);
                }
                if (dataKey) {
                    updateSelectedElementData(dataKey, target.value);
                }
            };
            elements.propertiesForm.addEventListener('input', handlePropertyInput);
            elements.propertiesForm.addEventListener('change', handlePropertyInput);
        }

        if (expressionInput) {
            expressionInput.addEventListener('focus', () => {
                updateAutocompleteForExpression(expressionInput);
            });
            expressionInput.addEventListener('blur', () => {
                closeAutocomplete();
            });
            expressionInput.addEventListener('input', () => {
                updateAutocompleteForExpression(expressionInput);
            });
            expressionInput.addEventListener('keyup', () => {
                updateAutocompleteForExpression(expressionInput);
            });
            expressionInput.addEventListener('click', () => {
                updateAutocompleteForExpression(expressionInput);
            });
            expressionInput.addEventListener('keydown', handleExpressionKeyDown);
        }

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

        window.addEventListener('resize', () => {
            if (autocomplete.visible) {
                positionAutocompleteOverlay();
            }
        });

        window.addEventListener(
            'scroll',
            () => {
                if (autocomplete.visible) {
                    positionAutocompleteOverlay();
                }
            },
            true
        );

        loadPlaceholderMeta();

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
