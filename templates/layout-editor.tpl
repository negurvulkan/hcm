<?php
/** @var array $editorConfig */
$configJson = json_encode($editorConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$pages = $editorConfig['pages'] ?? [];
$fonts = $fontOptions ?? ($editorConfig['fonts'] ?? []);
?>

<div class="layout-editor" data-layout-editor data-layout-editor-config="<?= htmlspecialchars($configJson, ENT_QUOTES, 'UTF-8') ?>">
    <div class="layout-editor__intro">
        <div>
            <h1 class="h4 mb-1"><?= htmlspecialchars(t('layout_editor.headline'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-muted mb-0"><?= htmlspecialchars(t('layout_editor.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="layout-editor__intro-actions">
            <button type="button" class="btn btn-outline-secondary" data-layout-editor-action="toggle-guides">
                <?= htmlspecialchars(t('layout_editor.actions.toggle_guides'), ENT_QUOTES, 'UTF-8') ?>
            </button>
            <button type="button" class="btn btn-outline-secondary" data-layout-editor-action="toggle-grid">
                <?= htmlspecialchars(t('layout_editor.actions.toggle_grid'), ENT_QUOTES, 'UTF-8') ?>
            </button>
            <button type="button" class="btn btn-primary" data-layout-editor-action="open-export" data-layout-editor-export-trigger>
                <?= htmlspecialchars(t('layout_editor.actions.export'), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </div>
    </div>

    <div class="row g-4 align-items-stretch">
        <aside class="col-xl-3 col-lg-4">
            <div class="layout-editor__panel card h-100">
                <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="h6 mb-1"><?= htmlspecialchars(t('layout_editor.navigation.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="text-muted small mb-0"><?= htmlspecialchars(t('layout_editor.navigation.hint'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-layout-editor-action="add-page">
                        <span class="me-1">+</span><?= htmlspecialchars(t('layout_editor.navigation.add'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
                <div class="card-body">
                    <nav class="layout-editor__page-nav" aria-label="<?= htmlspecialchars(t('layout_editor.navigation.aria'), ENT_QUOTES, 'UTF-8') ?>">
                        <ul class="layout-editor__page-list" data-layout-editor-pages>
                            <?php foreach ($pages as $index => $page): ?>
                                <li>
                                    <button type="button" class="layout-editor__page-button<?= $index === 0 ? ' is-active' : '' ?>" data-layout-editor-page-id="<?= htmlspecialchars($page['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="layout-editor__page-title"><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="layout-editor__page-meta">ID: <?= htmlspecialchars($page['id'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="layout-editor__page-empty" data-layout-editor-empty-state<?= $pages ? ' hidden' : '' ?>>
                            <p class="text-muted small mb-2"><?= htmlspecialchars(t('layout_editor.navigation.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                            <button type="button" class="btn btn-sm btn-primary" data-layout-editor-action="add-page">
                                <?= htmlspecialchars(t('layout_editor.navigation.add'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    </nav>
                </div>
                <div class="card-footer bg-light-subtle small text-muted">
                    <?= htmlspecialchars(t('layout_editor.navigation.footer'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </aside>

        <div class="col-xl-9 col-lg-8">
            <div class="row g-4 align-items-stretch">
                <div class="col-xxl-8 col-xl-7 col-lg-12">
                    <div class="layout-editor__workspace card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="layout-editor__toolbar" data-layout-editor-toolbar>
                                <div class="layout-editor__toolbar-group" role="group" aria-label="<?= htmlspecialchars(t('layout_editor.toolbar.modes'), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="button" class="layout-editor__tool-button is-active" data-layout-editor-tool="select" data-layout-editor-tool-label="<?= htmlspecialchars(t('layout_editor.toolbar.select'), ENT_QUOTES, 'UTF-8') ?>">
                                        <span aria-hidden="true">🖱</span>
                                        <span><?= htmlspecialchars(t('layout_editor.toolbar.select'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                </div>
                                <div class="layout-editor__toolbar-group" role="group" aria-label="<?= htmlspecialchars(t('layout_editor.toolbar.elements'), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="button" class="layout-editor__tool-button" data-layout-editor-tool="text" data-layout-editor-tool-label="<?= htmlspecialchars(t('layout_editor.toolbar.text'), ENT_QUOTES, 'UTF-8') ?>">
                                        <span aria-hidden="true">✎</span>
                                        <span><?= htmlspecialchars(t('layout_editor.toolbar.text'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                    <button type="button" class="layout-editor__tool-button" data-layout-editor-tool="image" data-layout-editor-tool-label="<?= htmlspecialchars(t('layout_editor.toolbar.image'), ENT_QUOTES, 'UTF-8') ?>">
                                        <span aria-hidden="true">🖼</span>
                                        <span><?= htmlspecialchars(t('layout_editor.toolbar.image'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                    <button type="button" class="layout-editor__tool-button" data-layout-editor-tool="shape" data-layout-editor-tool-label="<?= htmlspecialchars(t('layout_editor.toolbar.shape'), ENT_QUOTES, 'UTF-8') ?>">
                                        <span aria-hidden="true">⬛</span>
                                        <span><?= htmlspecialchars(t('layout_editor.toolbar.shape'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                    <button type="button" class="layout-editor__tool-button" data-layout-editor-tool="table" data-layout-editor-tool-label="<?= htmlspecialchars(t('layout_editor.toolbar.table'), ENT_QUOTES, 'UTF-8') ?>">
                                        <span aria-hidden="true">≣</span>
                                        <span><?= htmlspecialchars(t('layout_editor.toolbar.table'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                    <button type="button" class="layout-editor__tool-button" data-layout-editor-tool="placeholder" data-layout-editor-tool-label="<?= htmlspecialchars(t('layout_editor.toolbar.placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                                        <span aria-hidden="true">⌗</span>
                                        <span><?= htmlspecialchars(t('layout_editor.toolbar.placeholder'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                </div>
                                <div class="layout-editor__toolbar-divider" role="presentation"></div>
                                <div class="layout-editor__toolbar-group" data-layout-editor-zoom-controls>
                                    <button type="button" class="layout-editor__tool-button" data-layout-editor-action="zoom-out" aria-label="<?= htmlspecialchars(t('layout_editor.toolbar.zoom_out'), ENT_QUOTES, 'UTF-8') ?>">−</button>
                                    <span class="layout-editor__zoom-value" data-layout-editor-zoom-value>100%</span>
                                    <button type="button" class="layout-editor__tool-button" data-layout-editor-action="zoom-in" aria-label="<?= htmlspecialchars(t('layout_editor.toolbar.zoom_in'), ENT_QUOTES, 'UTF-8') ?>">+</button>
                                    <button type="button" class="layout-editor__tool-button" data-layout-editor-action="zoom-fit">
                                        <?= htmlspecialchars(t('layout_editor.toolbar.zoom_fit'), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </div>
                            </div>

                            <div class="layout-editor__viewport" data-layout-editor-viewport>
                                <div class="layout-editor__ruler layout-editor__ruler--horizontal" data-layout-editor-ruler="horizontal">
                                    <div class="layout-editor__ruler-scale" data-layout-editor-ruler-scale="horizontal"></div>
                                </div>
                                <div class="layout-editor__ruler layout-editor__ruler--vertical" data-layout-editor-ruler="vertical">
                                    <div class="layout-editor__ruler-scale" data-layout-editor-ruler-scale="vertical"></div>
                                </div>
                                <div class="layout-editor__ruler-origin" aria-hidden="true"></div>
                                <div class="layout-editor__canvas-stage" data-layout-editor-stage>
                                    <div class="layout-editor__canvas-inner" data-layout-editor-inner>
                                        <div class="layout-editor__grid" data-layout-editor-grid></div>
                                        <div class="layout-editor__guides" data-layout-editor-guides></div>
                                        <canvas class="layout-editor__canvas" data-layout-editor-canvas width="<?= (int) ($editorConfig['canvas']['width'] ?? 1024) ?>" height="<?= (int) ($editorConfig['canvas']['height'] ?? 768) ?>"></canvas>
                                        <div class="layout-editor__elements" data-layout-editor-elements></div>
                                        <div class="layout-editor__canvas-placeholder" data-layout-editor-placeholder>
                                            <?= htmlspecialchars(t('layout_editor.canvas.placeholder'), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="layout-editor__footer">
                                <div class="layout-editor__page-controls">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-layout-editor-action="previous-page">
                                        <?= htmlspecialchars(t('layout_editor.pages.previous'), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                    <div class="layout-editor__page-status" data-layout-editor-page-status>
                                        <?= htmlspecialchars(t('layout_editor.pages.status', ['current' => 1, 'total' => max(count($pages), 1)]), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-layout-editor-action="next-page">
                                        <?= htmlspecialchars(t('layout_editor.pages.next'), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </div>
                                <div class="layout-editor__checkpoint">
                                    <span class="text-muted small" data-layout-editor-cursor>0 × 0 px</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="col-xxl-4 col-xl-5 col-lg-12">
                    <div class="layout-editor__properties card h-100" data-layout-editor-properties>
                        <div class="card-header border-0 pb-0">
                            <div class="d-flex flex-column gap-1">
                                <h2 class="h6 mb-0"><?= htmlspecialchars(t('layout_editor.properties.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="text-muted small mb-0"><?= htmlspecialchars(t('layout_editor.properties.hint'), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="layout-editor__properties-empty text-muted small" data-layout-editor-properties-empty>
                                <?= htmlspecialchars(t('layout_editor.properties.empty'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <form class="layout-editor__properties-form" data-layout-editor-properties-form hidden novalidate>
                                <div class="layout-editor__properties-header">
                                    <div>
                                        <div class="layout-editor__properties-selected-name" data-layout-editor-selected-name>—</div>
                                        <div class="layout-editor__properties-selected-meta text-muted small" data-layout-editor-selected-meta></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-layout-editor-action="toggle-visibility" data-layout-editor-visibility-button data-layout-editor-visibility-label-visible="<?= htmlspecialchars(t('layout_editor.properties.visibility_hide'), ENT_QUOTES, 'UTF-8') ?>" data-layout-editor-visibility-label-hidden="<?= htmlspecialchars(t('layout_editor.properties.visibility_show'), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(t('layout_editor.properties.visibility_toggle'), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </div>
                                <div class="layout-editor__properties-layer">
                                    <div>
                                        <span class="text-muted small d-block"><?= htmlspecialchars(t('layout_editor.properties.layer_title'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="layout-editor__properties-layer-value" data-layout-editor-layer-indicator>–</span>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="<?= htmlspecialchars(t('layout_editor.properties.layer_title'), ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="button" class="btn btn-outline-secondary" data-layout-editor-action="send-to-back" title="<?= htmlspecialchars(t('layout_editor.properties.layer_back'), ENT_QUOTES, 'UTF-8') ?>">⤺</button>
                                        <button type="button" class="btn btn-outline-secondary" data-layout-editor-action="send-backward" title="<?= htmlspecialchars(t('layout_editor.properties.layer_down'), ENT_QUOTES, 'UTF-8') ?>">←</button>
                                        <button type="button" class="btn btn-outline-secondary" data-layout-editor-action="bring-forward" title="<?= htmlspecialchars(t('layout_editor.properties.layer_up'), ENT_QUOTES, 'UTF-8') ?>">→</button>
                                        <button type="button" class="btn btn-outline-secondary" data-layout-editor-action="bring-to-front" title="<?= htmlspecialchars(t('layout_editor.properties.layer_front'), ENT_QUOTES, 'UTF-8') ?>">⤻</button>
                                    </div>
                                </div>

                                <div class="layout-editor__properties-section">
                                    <span class="layout-editor__properties-section-title"><?= htmlspecialchars(t('layout_editor.properties.geometry'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="layout-editor-prop-x"><?= htmlspecialchars(t('layout_editor.properties.x'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <input type="number" class="form-control form-control-sm" id="layout-editor-prop-x" data-layout-editor-property="x" step="1" min="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="layout-editor-prop-y"><?= htmlspecialchars(t('layout_editor.properties.y'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <input type="number" class="form-control form-control-sm" id="layout-editor-prop-y" data-layout-editor-property="y" step="1" min="0">
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="layout-editor-prop-width"><?= htmlspecialchars(t('layout_editor.properties.width'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <input type="number" class="form-control form-control-sm" id="layout-editor-prop-width" data-layout-editor-property="width" step="1" min="32">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="layout-editor-prop-height"><?= htmlspecialchars(t('layout_editor.properties.height'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <input type="number" class="form-control form-control-sm" id="layout-editor-prop-height" data-layout-editor-property="height" step="1" min="32">
                                        </div>
                                    </div>
                                </div>

                                <div class="layout-editor__properties-section">
                                    <span class="layout-editor__properties-section-title"><?= htmlspecialchars(t('layout_editor.properties.appearance'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="layout-editor-prop-rotation"><?= htmlspecialchars(t('layout_editor.properties.rotation'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <input type="number" class="form-control form-control-sm" id="layout-editor-prop-rotation" data-layout-editor-property="rotation" step="1" min="-360" max="360">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="layout-editor-prop-opacity"><?= htmlspecialchars(t('layout_editor.properties.opacity'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <div class="layout-editor__properties-opacity">
                                                <input type="range" class="form-range" id="layout-editor-prop-opacity" data-layout-editor-property="opacity" min="0" max="100" step="5">
                                                <span class="layout-editor__properties-opacity-value" data-layout-editor-property-display="opacity">100%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="layout-editor__properties-section" data-layout-editor-properties-for="text">
                                    <span class="layout-editor__properties-section-title"><?= htmlspecialchars(t('layout_editor.properties.content'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="mb-3">
                                        <label class="form-label form-label-sm" for="layout-editor-prop-text"><?= htmlspecialchars(t('layout_editor.properties.text_label'), ENT_QUOTES, 'UTF-8') ?></label>
                                        <textarea class="form-control form-control-sm" id="layout-editor-prop-text" rows="3" data-layout-editor-data="text"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label form-label-sm" for="layout-editor-prop-subline"><?= htmlspecialchars(t('layout_editor.properties.subline_label'), ENT_QUOTES, 'UTF-8') ?></label>
                                        <input type="text" class="form-control form-control-sm" id="layout-editor-prop-subline" data-layout-editor-data="subline">
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label form-label-sm" for="layout-editor-prop-font"><?= htmlspecialchars(t('layout_editor.properties.font_label'), ENT_QUOTES, 'UTF-8') ?></label>
                                        <select class="form-select form-select-sm" id="layout-editor-prop-font" data-layout-editor-data="fontFamily">
                                            <?php foreach ($fonts as $font): ?>
                                                <option value="<?= htmlspecialchars($font, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($font, ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="layout-editor__properties-section" data-layout-editor-properties-for="image">
                                    <span class="layout-editor__properties-section-title"><?= htmlspecialchars(t('layout_editor.properties.content'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="mb-3">
                                        <label class="form-label form-label-sm" for="layout-editor-prop-alt"><?= htmlspecialchars(t('layout_editor.properties.image_alt'), ENT_QUOTES, 'UTF-8') ?></label>
                                        <input type="text" class="form-control form-control-sm" id="layout-editor-prop-alt" data-layout-editor-data="alt">
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label form-label-sm" for="layout-editor-prop-src"><?= htmlspecialchars(t('layout_editor.properties.image_src'), ENT_QUOTES, 'UTF-8') ?></label>
                                        <input type="url" class="form-control form-control-sm" id="layout-editor-prop-src" data-layout-editor-data="src" placeholder="https://">
                                    </div>
                                </div>

                                <div class="layout-editor__properties-section" data-layout-editor-properties-for="shape">
                                    <span class="layout-editor__properties-section-title"><?= htmlspecialchars(t('layout_editor.properties.content'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <label class="form-label form-label-sm" for="layout-editor-prop-variant"><?= htmlspecialchars(t('layout_editor.properties.shape_variant'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <select class="form-select form-select-sm" id="layout-editor-prop-variant" data-layout-editor-data="variant">
                                        <option value="rectangle"><?= htmlspecialchars(t('layout_editor.properties.shape_rectangle'), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="circle"><?= htmlspecialchars(t('layout_editor.properties.shape_circle'), ENT_QUOTES, 'UTF-8') ?></option>
                                    </select>
                                </div>

                                <div class="layout-editor__properties-section" data-layout-editor-properties-for="table">
                                    <span class="layout-editor__properties-section-title"><?= htmlspecialchars(t('layout_editor.properties.content'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="layout-editor-prop-rows"><?= htmlspecialchars(t('layout_editor.properties.table_rows'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <input type="number" class="form-control form-control-sm" id="layout-editor-prop-rows" data-layout-editor-data="rows" min="1" max="20">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm" for="layout-editor-prop-cols"><?= htmlspecialchars(t('layout_editor.properties.table_cols'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <input type="number" class="form-control form-control-sm" id="layout-editor-prop-cols" data-layout-editor-data="cols" min="1" max="20">
                                        </div>
                                    </div>
                                </div>

                                <div class="layout-editor__properties-section" data-layout-editor-properties-for="placeholder">
                                    <span class="layout-editor__properties-section-title"><?= htmlspecialchars(t('layout_editor.properties.content'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="mb-3">
                                        <label class="form-label form-label-sm" for="layout-editor-prop-label"><?= htmlspecialchars(t('layout_editor.properties.placeholder_label'), ENT_QUOTES, 'UTF-8') ?></label>
                                        <input type="text" class="form-control form-control-sm" id="layout-editor-prop-label" data-layout-editor-data="label">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label form-label-sm" for="layout-editor-prop-expression"><?= htmlspecialchars(t('layout_editor.properties.placeholder_expression'), ENT_QUOTES, 'UTF-8') ?></label>
                                        <textarea class="form-control form-control-sm font-monospace" id="layout-editor-prop-expression" data-layout-editor-data="expression" data-layout-editor-template-input rows="4" spellcheck="false" placeholder="{{ person.name }}"></textarea>
                                        <div class="form-text small" data-layout-editor-expression-hint><?= htmlspecialchars(t('layout_editor.properties.placeholder_expression_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="invalid-feedback d-block layout-editor__expression-error" data-layout-editor-expression-error hidden></div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label form-label-sm" for="layout-editor-prop-sample"><?= htmlspecialchars(t('layout_editor.properties.placeholder_sample'), ENT_QUOTES, 'UTF-8') ?></label>
                                        <input type="text" class="form-control form-control-sm" id="layout-editor-prop-sample" data-layout-editor-data="sample" placeholder="https://">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer bg-light-subtle text-muted small">
                            <?= htmlspecialchars(t('layout_editor.properties.footer'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout-editor-export-modal.tpl'; ?>
