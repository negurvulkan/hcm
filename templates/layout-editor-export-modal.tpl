<div class="modal fade" id="layoutExportModal" tabindex="-1" aria-labelledby="layoutExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" data-layout-export-modal>
            <div class="modal-header">
                <h5 class="modal-title" id="layoutExportModalLabel"><?= htmlspecialchars(t('layout_editor.export.title'), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('layout_editor.export.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-4"><?= htmlspecialchars(t('layout_editor.export.description'), ENT_QUOTES, 'UTF-8') ?></p>
                <form class="row g-3" data-layout-export-form>
                    <div class="col-sm-6">
                        <label class="form-label" for="layout-export-layout"><?= htmlspecialchars(t('layout_editor.export.layout_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="layout-export-layout" data-layout-export-layout></select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="layout-export-source"><?= htmlspecialchars(t('layout_editor.export.data_source_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="layout-export-source" data-layout-export-source></select>
                    </div>
                    <div class="col-sm-6" data-layout-export-class-wrapper hidden>
                        <label class="form-label" for="layout-export-class"><?= htmlspecialchars(t('layout_editor.export.class_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="layout-export-class" data-layout-export-class></select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="layout-export-paper"><?= htmlspecialchars(t('layout_editor.export.paper_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="layout-export-paper" data-layout-export-paper></select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="layout-export-bleed"><?= htmlspecialchars(t('layout_editor.export.bleed_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="number" class="form-control" id="layout-export-bleed" data-layout-export-bleed min="0" step="0.5" value="0">
                        <div class="form-text"><?= htmlspecialchars(t('layout_editor.export.bleed_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2" data-layout-export-progress hidden>
                        <div class="progress flex-grow-1" style="height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" data-layout-export-progress-bar></div>
                        </div>
                        <span class="small text-muted" data-layout-export-status></span>
                    </div>
                    <div class="col-12" data-layout-export-error hidden>
                        <div class="alert alert-danger mb-0" role="alert" data-layout-export-error-message></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-layout-export-close><?= htmlspecialchars(t('layout_editor.export.close'), ENT_QUOTES, 'UTF-8') ?></button>
                <a href="#" class="btn btn-success" data-layout-export-download hidden target="_blank" rel="noopener"><?= htmlspecialchars(t('layout_editor.export.download'), ENT_QUOTES, 'UTF-8') ?></a>
                <button type="button" class="btn btn-primary" data-layout-export-start>
                    <span data-layout-export-start-label><?= htmlspecialchars(t('layout_editor.export.start'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="spinner-border spinner-border-sm align-middle ms-2" role="status" aria-hidden="true" data-layout-export-spinner hidden></span>
                </button>
            </div>
        </div>
    </div>
</div>
