<div class="modal fade" id="entity-info-modal" tabindex="-1" aria-labelledby="entity-info-modal-title" aria-hidden="true" data-entity-info-empty="<?= htmlspecialchars(t('entity_info.empty'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="entity-info-modal-title" data-entity-info-title><?= htmlspecialchars(t('entity_info.modal_title_default'), ENT_QUOTES, 'UTF-8') ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('common.actions.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="modal-body" data-entity-info-body>
                <p class="text-muted mb-0"><?= htmlspecialchars(t('entity_info.empty'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('common.actions.close'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
