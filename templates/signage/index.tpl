<?php
/** @var array $layouts */
/** @var array $displays */
/** @var array $playlists */
/** @var array $designerConfig */
?>
<?php $configJson = json_encode($designerConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h1 class="h4 mb-1"><?= htmlspecialchars(t('signage.headline'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0"><?= htmlspecialchars(t('signage.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="button" data-signage-action="create-layout">
            <span class="me-1 fw-semibold">+</span><?= htmlspecialchars(t('signage.actions.new_layout'), ENT_QUOTES, 'UTF-8') ?>
        </button>
        <button class="btn btn-outline-secondary" type="button" data-signage-action="create-display">
            <span class="me-1 fw-semibold">🖥</span><?= htmlspecialchars(t('signage.actions.new_display'), ENT_QUOTES, 'UTF-8') ?>
        </button>
        <button class="btn btn-outline-secondary" type="button" data-signage-action="create-playlist">
            <span class="me-1 fw-semibold">▶</span><?= htmlspecialchars(t('signage.actions.new_playlist'), ENT_QUOTES, 'UTF-8') ?>
        </button>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-3">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <h2 class="h6 mb-1"><?= htmlspecialchars(t('signage.layouts.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-muted small mb-0"><?= htmlspecialchars(t('signage.layouts.hint'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="search" class="form-control" placeholder="<?= htmlspecialchars(t('signage.layouts.search'), ENT_QUOTES, 'UTF-8') ?>" data-signage-search>
                </div>
                <div class="signage-layout-list" data-signage-list>
                    <?php if (!$layouts): ?>
                        <div class="text-center text-muted py-4" data-empty-state>
                            <p class="mb-2"><?= htmlspecialchars(t('signage.layouts.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                            <button class="btn btn-sm btn-primary" type="button" data-signage-action="create-layout">
                                <?= htmlspecialchars(t('signage.actions.new_layout'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($layouts as $layout): ?>
                            <button type="button" class="signage-layout-list__item" data-layout-id="<?= (int) $layout['id'] ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($layout['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars(t('signage.layouts.updated', ['date' => format_datetime($layout['updated_at'] ?? null)]), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                    <span class="badge <?= ($layout['status'] ?? 'draft') === 'published' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= htmlspecialchars(t('signage.status.' . ($layout['status'] ?? 'draft')), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer bg-light-subtle small text-muted">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="signage-marker">•</span>
                        <span><?= htmlspecialchars(t('signage.layouts.features.layers'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="signage-marker">•</span>
                        <span><?= htmlspecialchars(t('signage.layouts.features.timeline'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="signage-marker">•</span>
                        <span><?= htmlspecialchars(t('signage.layouts.features.themes'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-9">
        <div class="card signage-designer">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1" data-signage-active-name><?= htmlspecialchars($layouts[0]['name'] ?? t('signage.designer.empty'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <div class="text-muted small" data-signage-active-meta><?= htmlspecialchars(t('signage.designer.meta_placeholder'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-signage-action="duplicate-layout" title="<?= htmlspecialchars(t('signage.actions.duplicate'), ENT_QUOTES, 'UTF-8') ?>">
                            <span aria-hidden="true">⧉</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-signage-action="preview-layout" title="<?= htmlspecialchars(t('signage.actions.preview'), ENT_QUOTES, 'UTF-8') ?>">
                            <span aria-hidden="true">👁</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-signage-action="publish-layout" title="<?= htmlspecialchars(t('signage.actions.publish'), ENT_QUOTES, 'UTF-8') ?>">
                            <span aria-hidden="true">📡</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" data-signage-action="delete-layout" title="<?= htmlspecialchars(t('signage.actions.delete'), ENT_QUOTES, 'UTF-8') ?>">
                            <span aria-hidden="true">🗑</span>
                        </button>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="signage-designer__canvas" data-signage-canvas>
                            <div class="signage-designer__canvas-inner" data-signage-canvas-inner>
                                <div class="text-muted small text-center" data-signage-placeholder><?= htmlspecialchars(t('signage.designer.select_prompt'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="signage-designer__guides" data-signage-guides></div>
                        </div>
                        <div class="signage-designer__timeline mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h3 class="h6 mb-0"><?= htmlspecialchars(t('signage.timeline.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                                <button class="btn btn-sm btn-outline-primary" type="button" data-signage-action="add-scene">
                                    <span class="me-1 fw-semibold">+</span><?= htmlspecialchars(t('signage.timeline.add'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </div>
                            <div class="signage-timeline" data-signage-timeline></div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="signage-designer__sidebar">
                            <div class="mb-4">
                                <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('signage.palette.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="signage-palette" data-signage-palette>
                                <button class="btn btn-outline-secondary btn-sm" type="button" data-element-type="text"><?= htmlspecialchars(t('signage.palette.text'), ENT_QUOTES, 'UTF-8') ?></button>
                                <button class="btn btn-outline-secondary btn-sm" type="button" data-element-type="image"><?= htmlspecialchars(t('signage.palette.image'), ENT_QUOTES, 'UTF-8') ?></button>
                                <button class="btn btn-outline-secondary btn-sm" type="button" data-element-type="video"><?= htmlspecialchars(t('signage.palette.video'), ENT_QUOTES, 'UTF-8') ?></button>
                                <button class="btn btn-outline-secondary btn-sm" type="button" data-element-type="live"><?= htmlspecialchars(t('signage.palette.live'), ENT_QUOTES, 'UTF-8') ?></button>
                                <button class="btn btn-outline-secondary btn-sm" type="button" data-element-type="ticker"><?= htmlspecialchars(t('signage.palette.ticker'), ENT_QUOTES, 'UTF-8') ?></button>
                            </div>
                        </div>
                        <div class="mb-4">
                            <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('signage.layers.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="signage-layers" data-signage-layers></div>
                        </div>
                        <div class="mb-4">
                            <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('signage.content.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="signage-content" data-signage-content></div>
                        </div>
                        <div class="mb-4">
                            <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('signage.bindings.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="signage-bindings" data-signage-bindings></div>
                        </div>
                        <div>
                            <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('signage.styles.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                                <div class="signage-styles" data-signage-styles></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small" data-signage-status></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-signage-action="undo"><?= htmlspecialchars(t('signage.actions.undo'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-signage-action="redo"><?= htmlspecialchars(t('signage.actions.redo'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button class="btn btn-success btn-sm" type="button" data-signage-action="save-layout">
                        <span class="me-1">💾</span><?= htmlspecialchars(t('signage.actions.save'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <h2 class="h6 mb-1"><?= htmlspecialchars(t('signage.displays.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-muted small mb-0"><?= htmlspecialchars(t('signage.displays.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="card-body">
                <div class="signage-displays" data-signage-displays>
                    <?php if (!$displays): ?>
                        <p class="text-muted mb-0"><?= htmlspecialchars(t('signage.displays.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <?php foreach ($displays as $display): ?>
                            <div class="signage-display" data-display-id="<?= (int) $display['id'] ?>">
                                <div class="signage-display__header">
                                    <div>
                                        <strong><?= htmlspecialchars($display['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <div class="text-muted small"><?= htmlspecialchars($display['display_group'] ?? 'default', ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-signage-action="assign-layout" data-display-id="<?= (int) $display['id'] ?>"><?= htmlspecialchars(t('signage.displays.assign'), ENT_QUOTES, 'UTF-8') ?></button>
                                        <button class="btn btn-sm btn-outline-danger" type="button" data-signage-action="delete-display" data-display-id="<?= (int) $display['id'] ?>" title="<?= htmlspecialchars(t('signage.actions.delete'), ENT_QUOTES, 'UTF-8') ?>">
                                            <span aria-hidden="true">🗑</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="signage-display__body">
                                    <div class="small text-muted">Token: <code><?= htmlspecialchars($display['access_token'], ENT_QUOTES, 'UTF-8') ?></code></div>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars(t('signage.displays.last_seen', ['time' => $display['last_seen_at'] ? format_datetime($display['last_seen_at']) : t('signage.displays.never')]), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <h2 class="h6 mb-1"><?= htmlspecialchars(t('signage.playlists.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-muted small mb-0"><?= htmlspecialchars(t('signage.playlists.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="card-body">
                <div class="signage-playlists" data-signage-playlists>
                    <?php if (!$playlists): ?>
                        <p class="text-muted mb-0"><?= htmlspecialchars(t('signage.playlists.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <?php foreach ($playlists as $playlist): ?>
                            <div class="signage-playlist" data-playlist-id="<?= (int) $playlist['id'] ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($playlist['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars(t('signage.playlists.group', ['group' => $playlist['display_group']]), ENT_QUOTES, 'UTF-8') ?> ·
                                            <?= htmlspecialchars(t('signage.playlists.rotation', ['seconds' => (int) $playlist['rotation_seconds']]), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" type="button" data-signage-action="edit-playlist" data-playlist-id="<?= (int) $playlist['id'] ?>" title="<?= htmlspecialchars(t('signage.actions.edit'), ENT_QUOTES, 'UTF-8') ?>">
                                            <span aria-hidden="true">✏</span>
                                        </button>
                                        <button class="btn btn-outline-danger" type="button" data-signage-action="delete-playlist" data-playlist-id="<?= (int) $playlist['id'] ?>" title="<?= htmlspecialchars(t('signage.actions.delete'), ENT_QUOTES, 'UTF-8') ?>">
                                            <span aria-hidden="true">🗑</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="signage-playlist__items" data-signage-playlist-items>
                                    <?php foreach ($playlist['items'] as $item): ?>
                                        <div class="badge bg-light text-dark me-2 mb-2">
                                            <?= htmlspecialchars($item['label'] ?? t('signage.playlists.item_default'), ENT_QUOTES, 'UTF-8') ?>
                                            <span class="text-muted ms-1"><?= (int) $item['duration_seconds'] ?>s</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="signage-app" data-signage-config="<?= $configJson ?>" hidden></div>

<div class="modal fade" id="signageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" data-signage-modal>
            <div class="modal-header">
                <h5 class="modal-title" data-signage-modal-title></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('signage.modal.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="modal-body" data-signage-modal-body></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('signage.modal.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                <button type="button" class="btn btn-primary" data-signage-modal-save><?= htmlspecialchars(t('signage.modal.save'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
