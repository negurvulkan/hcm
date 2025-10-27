<?php
/** @var array $editorConfig */
$configJson = json_encode($editorConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$pages = $editorConfig['pages'] ?? [];
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
    </div>
</div>
