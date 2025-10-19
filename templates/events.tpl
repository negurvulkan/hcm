<?php
/** @var array $events */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars($editEvent ? t('events.form.edit_title') : t('events.form.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="default_action" value="<?= $editEvent ? 'update' : 'create' ?>">
                    <input type="hidden" name="event_id" value="<?= $editEvent ? (int) $editEvent['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('events.form.title_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editEvent['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label"><?= htmlspecialchars(t('events.form.start_label'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($editEvent['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col">
                            <label class="form-label"><?= htmlspecialchars(t('events.form.end_label'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($editEvent['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label"><?= htmlspecialchars(t('events.form.venues_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="venues" class="form-control" placeholder="<?= htmlspecialchars(t('events.form.venues_placeholder'), ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars(isset($editEvent['venues_list']) ? implode(', ', $editEvent['venues_list']) : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="scoring-rule-json"><?= htmlspecialchars(t('events.form.scoring_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea id="scoring-rule-json" name="scoring_rule_json" class="form-control font-monospace" rows="8" spellcheck="false" placeholder='<?= htmlspecialchars(t('events.form.scoring_placeholder'), ENT_QUOTES, 'UTF-8') ?>'><?= htmlspecialchars($editEvent['scoring_rule_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text"><?= htmlspecialchars(t('events.form.scoring_note'), ENT_QUOTES, 'UTF-8') ?></div>
                        <button class="btn btn-outline-secondary btn-sm mt-2" type="button" data-bs-toggle="modal" data-bs-target="#event-scoring-designer-modal"><?= htmlspecialchars(t('events.form.scoring_open_designer'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                    <div class="modal fade" id="event-scoring-designer-modal" tabindex="-1" aria-labelledby="event-scoring-designer-title" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="event-scoring-designer-title"><?= htmlspecialchars(t('scoring_designer.title'), ENT_QUOTES, 'UTF-8') ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('common.actions.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="card border-secondary" data-scoring-designer data-target="#scoring-rule-json"
                                         data-default='<?= htmlspecialchars($scoringDesignerDefaultJson ?? "{}", ENT_QUOTES, 'UTF-8') ?>'
                                         data-presets='<?= htmlspecialchars($scoringDesignerPresetsJson ?? "{}", ENT_QUOTES, 'UTF-8') ?>'>
                                        <div class="card-body">
                                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                                                <div>
                                                    <h3 class="h6 mb-1"><?= htmlspecialchars(t('scoring_designer.header.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                                                    <p class="text-muted mb-0"><?= htmlspecialchars(t('scoring_designer.header.description.events'), ENT_QUOTES, 'UTF-8') ?></p>
                                                </div>
                                                <div class="btn-toolbar" role="toolbar">
                                                    <div class="btn-group btn-group-sm me-2" role="group">
                                                        <button class="btn btn-outline-secondary" type="button" data-action="load-json"><?= htmlspecialchars(t('scoring_designer.toolbar.load_json'), ENT_QUOTES, 'UTF-8') ?></button>
                                                        <button class="btn btn-outline-secondary" type="button" data-action="reset-default"><?= htmlspecialchars(t('scoring_designer.toolbar.reset'), ENT_QUOTES, 'UTF-8') ?></button>
                                                    </div>
                                                    <div class="input-group input-group-sm" style="min-width: 220px;">
                                                        <span class="input-group-text"><?= htmlspecialchars(t('scoring_designer.toolbar.preset_select_label'), ENT_QUOTES, 'UTF-8') ?></span>
                                                        <select class="form-select" data-preset-select>
                                                            <option value=""><?= htmlspecialchars(t('scoring_designer.toolbar.preset_select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                                                            <?php foreach ($scoringPresetOptions as $option): ?>
                                                                <option value="<?= htmlspecialchars($option['key'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.fields.version'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control form-control-sm" data-scoring-path="version">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.fields.rule_id'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control form-control-sm" data-scoring-path="id">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.fields.label'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control form-control-sm" data-scoring-path="label">
                                                </div>
                                            </div>
                                            <h4 class="h6"><?= htmlspecialchars(t('scoring_designer.sections.judges.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.judges.min'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="input.judges.min" data-type="integer" min="1">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.judges.max'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="input.judges.max" data-type="integer" min="1">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.judges.aggregation'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select form-select-sm" data-scoring-path="input.judges.aggregation.method">
                                                        <option value="mean"><?= htmlspecialchars(t('scoring_designer.sections.judges.options.mean'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="sum"><?= htmlspecialchars(t('scoring_designer.sections.judges.options.sum'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="median"><?= htmlspecialchars(t('scoring_designer.sections.judges.options.median'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="best"><?= htmlspecialchars(t('scoring_designer.sections.judges.options.best'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.judges.drop_high'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="input.judges.aggregation.drop_high" data-type="integer" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.judges.drop_low'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="input.judges.aggregation.drop_low" data-type="integer" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.judges.weights'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control form-control-sm" placeholder="1,1,1" data-scoring-path="input.judges.aggregation.weights" data-type="csv-number">
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h4 class="h6 mb-0"><?= htmlspecialchars(t('scoring_designer.sections.fields.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-field"><?= htmlspecialchars(t('scoring_designer.sections.fields.add'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </div>
                                            <div class="vstack gap-3 mb-4" data-scoring-list="fields" data-empty-text="<?= htmlspecialchars(t('scoring_designer.sections.fields.empty'), ENT_QUOTES, 'UTF-8') ?>"></div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h4 class="h6 mb-0"><?= htmlspecialchars(t('scoring_designer.sections.components.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-component"><?= htmlspecialchars(t('scoring_designer.sections.components.add'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </div>
                                            <div class="vstack gap-3 mb-4" data-scoring-list="components" data-empty-text="<?= htmlspecialchars(t('scoring_designer.sections.components.empty'), ENT_QUOTES, 'UTF-8') ?>"></div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h4 class="h6 mb-0"><?= htmlspecialchars(t('scoring_designer.sections.lessons.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-lesson"><?= htmlspecialchars(t('scoring_designer.sections.lessons.add'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </div>
                                            <div class="vstack gap-3 mb-4" data-scoring-list="lessons" data-empty-text="<?= htmlspecialchars(t('scoring_designer.sections.lessons.empty'), ENT_QUOTES, 'UTF-8') ?>"></div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h4 class="h6 mb-0"><?= htmlspecialchars(t('scoring_designer.sections.penalties.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-penalty"><?= htmlspecialchars(t('scoring_designer.sections.penalties.add'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </div>
                                            <div class="vstack gap-3 mb-4" data-scoring-list="penalties" data-empty-text="<?= htmlspecialchars(t('scoring_designer.sections.penalties.empty'), ENT_QUOTES, 'UTF-8') ?>"></div>
                                            <h4 class="h6"><?= htmlspecialchars(t('scoring_designer.sections.time.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.time.mode'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select form-select-sm" data-scoring-path="time.mode">
                                                        <option value="none"><?= htmlspecialchars(t('scoring_designer.sections.time.options.none'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="faults_from_time"><?= htmlspecialchars(t('scoring_designer.sections.time.options.faults_from_time'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="bonus_from_time"><?= htmlspecialchars(t('scoring_designer.sections.time.options.bonus_from_time'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="best_time"><?= htmlspecialchars(t('scoring_designer.sections.time.options.best_time'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.time.allowed'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="time.allowed_s" min="0" step="0.01">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.time.faults'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="time.fault_per_s" step="0.01">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.time.cap'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="time.cap_s" min="0" step="0.01">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.time.bonus'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-type="number" data-scoring-path="time.bonus_per_s" step="0.01">
                                                </div>
                                            </div>
                                            <h4 class="h6"><?= htmlspecialchars(t('scoring_designer.sections.formulas.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                            <div class="row g-3 mb-4">
                                                <div class="col-md-6">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.formulas.per_judge'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <textarea class="form-control font-monospace form-control-sm" rows="2" data-scoring-path="per_judge_formula"></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.formulas.aggregate'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <textarea class="form-control font-monospace form-control-sm" rows="2" data-scoring-path="aggregate_formula"></textarea>
                                                </div>
                                            </div>
                                            <h4 class="h6"><?= htmlspecialchars(t('scoring_designer.sections.ranking.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                            <div class="row g-3 mb-3">
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.ranking.order'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select form-select-sm" data-scoring-path="ranking.order">
                                                        <option value="desc"><?= htmlspecialchars(t('scoring_designer.sections.ranking.options.desc'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="asc"><?= htmlspecialchars(t('scoring_designer.sections.ranking.options.asc'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h5 class="h6 mb-0"><?= htmlspecialchars(t('scoring_designer.sections.tiebreakers.title'), ENT_QUOTES, 'UTF-8') ?></h5>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-tiebreaker"><?= htmlspecialchars(t('scoring_designer.sections.tiebreakers.add'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </div>
                                            <div class="vstack gap-3 mb-4" data-scoring-list="tiebreakers" data-empty-text="<?= htmlspecialchars(t('scoring_designer.sections.tiebreakers.empty'), ENT_QUOTES, 'UTF-8') ?>"></div>
                                            <h4 class="h6"><?= htmlspecialchars(t('scoring_designer.sections.output.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                            <div class="row g-3">
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.output.rounding'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="output.rounding" data-type="integer" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.output.unit'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control form-control-sm" data-scoring-path="output.unit">
                                                </div>
                                                <div class="col-sm-4 d-flex align-items-center">
                                                    <div class="form-check mt-3 mt-sm-0">
                                                        <input class="form-check-input" type="checkbox" value="1" id="event-scoring-show-breakdown" data-scoring-path="output.show_breakdown" data-type="boolean">
                                                        <label class="form-check-label" for="event-scoring-show-breakdown"><?= htmlspecialchars(t('scoring_designer.sections.output.breakdown'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <small class="text-muted me-auto"><?= htmlspecialchars(t('scoring_designer.footer.note'), ENT_QUOTES, 'UTF-8') ?></small>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('common.actions.close'), ENT_QUOTES, 'UTF-8') ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="start-number-rules-input"><?= htmlspecialchars(t('events.form.start_numbers_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea id="start-number-rules-input" name="start_number_rules" class="form-control" rows="10" spellcheck="false" placeholder="{ &quot;mode&quot;: &quot;classic&quot;, ... }"><?= htmlspecialchars($editEvent['start_number_rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text"><?= htmlspecialchars(t('events.form.start_numbers_note'), ENT_QUOTES, 'UTF-8') ?></div>
                        <button class="btn btn-outline-secondary btn-sm mt-2" type="button" data-bs-toggle="modal" data-bs-target="#event-start-number-designer-modal"><?= htmlspecialchars(t('events.form.start_numbers_open_designer'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                    <div class="modal fade" id="event-start-number-designer-modal" tabindex="-1" aria-labelledby="event-start-number-designer-title" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="event-start-number-designer-title"><?= htmlspecialchars(t('start_numbers.designer.title'), ENT_QUOTES, 'UTF-8') ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('common.actions.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="card border-secondary" data-start-number-designer data-target="#start-number-rules-input"
                                         data-rule="<?= htmlspecialchars($ruleDesignerJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>"
                                         data-default="<?= htmlspecialchars($ruleDesignerDefaultsJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>"
                                         data-presets='<?= htmlspecialchars($startNumberDesignerPresetsJson ?? "{}", ENT_QUOTES, 'UTF-8') ?>'>
                                        <div class="card-body">
                                            <div class="d-flex align-items-start justify-content-between mb-3">
                                                <div>
                                                    <h3 class="h6 mb-1"><?= htmlspecialchars(t('start_numbers.designer.configuration.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                                                    <p class="text-muted mb-0"><?= htmlspecialchars(t('start_numbers.designer.configuration.description'), ENT_QUOTES, 'UTF-8') ?></p>
                                                </div>
                                                <div class="btn-toolbar" role="toolbar">
                                                    <div class="btn-group btn-group-sm me-2" role="group">
                                                        <button class="btn btn-outline-secondary" type="button" data-action="load-json"><?= htmlspecialchars(t('start_numbers.designer.actions.load_json'), ENT_QUOTES, 'UTF-8') ?></button>
                                                        <button class="btn btn-outline-secondary" type="button" data-action="reset-defaults"><?= htmlspecialchars(t('start_numbers.designer.actions.reset'), ENT_QUOTES, 'UTF-8') ?></button>
                                                    </div>
                                                    <div class="input-group input-group-sm" style="min-width: 220px;">
                                                        <span class="input-group-text"><?= htmlspecialchars(t('start_numbers.designer.presets.select_label'), ENT_QUOTES, 'UTF-8') ?></span>
                                                        <select class="form-select" data-preset-select>
                                                            <option value=""><?= htmlspecialchars(t('start_numbers.designer.presets.select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                                                            <?php foreach ($startNumberPresetOptions as $option): ?>
                                                                <option value="<?= htmlspecialchars($option['key'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-sm-6">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.fields.mode'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select" data-designer-field="mode">
                                                        <option value="classic"><?= htmlspecialchars(t('start_numbers.designer.options.mode.classic'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="western"><?= htmlspecialchars(t('start_numbers.designer.options.mode.western'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="custom"><?= htmlspecialchars(t('start_numbers.designer.options.mode.custom'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.fields.scope'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select" data-designer-field="scope">
                                                        <option value="tournament"><?= htmlspecialchars(t('start_numbers.designer.options.scope.tournament'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="class"><?= htmlspecialchars(t('start_numbers.designer.options.scope.class'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="arena"><?= htmlspecialchars(t('start_numbers.designer.options.scope.arena'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="day"><?= htmlspecialchars(t('start_numbers.designer.options.scope.day'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <hr>
                                            <h4 class="h6"><?= htmlspecialchars(t('start_numbers.designer.sections.sequence.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                            <div class="row g-3">
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.sequence.start'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control" data-designer-field="sequence.start" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.sequence.step'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control" data-designer-field="sequence.step" min="1">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.sequence.reset'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select" data-designer-field="sequence.reset">
                                                        <option value="never"><?= htmlspecialchars(t('start_numbers.designer.options.reset.never'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="per_class"><?= htmlspecialchars(t('start_numbers.designer.options.reset.per_class'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="per_day"><?= htmlspecialchars(t('start_numbers.designer.options.reset.per_day'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.sequence.range_from'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control" data-designer-field="sequence.range_min" min="0">
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.sequence.range_to'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control" data-designer-field="sequence.range_max" min="0">
                                                </div>
                                            </div>
                                            <hr>
                                            <h4 class="h6"><?= htmlspecialchars(t('start_numbers.designer.sections.format.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                            <div class="row g-3">
                                                <div class="col-sm-3">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.format.prefix'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control" data-designer-field="format.prefix" maxlength="10">
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.format.width'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control" data-designer-field="format.width" min="0">
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.format.suffix'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control" data-designer-field="format.suffix" maxlength="10">
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.format.separator'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control" data-designer-field="format.separator" maxlength="5">
                                                </div>
                                            </div>
                                            <hr>
                                            <h4 class="h6"><?= htmlspecialchars(t('start_numbers.designer.sections.allocation.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                            <div class="row g-3">
                                                <div class="col-sm-3">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.allocation.entity'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select" data-designer-field="allocation.entity">
                                                        <option value="start"><?= htmlspecialchars(t('start_numbers.designer.options.entity.start'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="pair"><?= htmlspecialchars(t('start_numbers.designer.options.entity.pair'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.allocation.time'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select" data-designer-field="allocation.time">
                                                        <option value="on_entry"><?= htmlspecialchars(t('start_numbers.designer.options.time.on_entry'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="on_startlist"><?= htmlspecialchars(t('start_numbers.designer.options.time.on_startlist'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="on_gate"><?= htmlspecialchars(t('start_numbers.designer.options.time.on_gate'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.allocation.reuse'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select" data-designer-field="allocation.reuse">
                                                        <option value="never"><?= htmlspecialchars(t('start_numbers.designer.options.reset.never'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="after_scratch"><?= htmlspecialchars(t('start_numbers.designer.options.reuse.after_scratch'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="session"><?= htmlspecialchars(t('start_numbers.designer.options.reuse.session'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.allocation.lock_after'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select" data-designer-field="allocation.lock_after">
                                                        <option value="sign_off"><?= htmlspecialchars(t('start_numbers.designer.options.lock_after.sign_off'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="start_called"><?= htmlspecialchars(t('start_numbers.designer.options.lock_after.start_called'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="never"><?= htmlspecialchars(t('start_numbers.designer.options.reset.never'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <hr>
                                            <h4 class="h6"><?= htmlspecialchars(t('start_numbers.designer.sections.constraints.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                            <div class="row g-3">
                                                <div class="col-sm-6">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.constraints.unique'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select" data-designer-field="constraints.unique_per">
                                                        <option value="tournament"><?= htmlspecialchars(t('start_numbers.designer.options.scope.tournament'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="class"><?= htmlspecialchars(t('start_numbers.designer.options.scope.class'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="day"><?= htmlspecialchars(t('start_numbers.designer.options.scope.day'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.constraints.blocked'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control" data-designer-field="constraints.blocklists" placeholder="13, 666">
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.constraints.club_spacing'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control" data-designer-field="constraints.club_spacing" min="0">
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label"><?= htmlspecialchars(t('start_numbers.designer.sections.constraints.horse_cooldown'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control" data-designer-field="constraints.horse_cooldown_min" min="0">
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h4 class="h6 mb-0"><?= htmlspecialchars(t('start_numbers.designer.overrides.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-override"><?= htmlspecialchars(t('start_numbers.designer.overrides.add'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </div>
                                            <div class="vstack gap-3" data-override-list></div>
                                            <div class="alert alert-secondary mt-3 mb-0 small">
                                                <?= htmlspecialchars(t('start_numbers.designer.overrides.note'), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <small class="text-muted me-auto"><?= htmlspecialchars(t('start_numbers.designer.footer.note'), ENT_QUOTES, 'UTF-8') ?></small>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('common.actions.close'), ENT_QUOTES, 'UTF-8') ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('events.form.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <button class="btn btn-outline-primary" type="submit" name="action" value="simulate_rules" formnovalidate><?= htmlspecialchars(t('events.form.simulate', ['count' => 20]), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editEvent): ?>
                            <a href="events.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('events.form.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if (!empty($simulationError)): ?>
                    <div class="alert alert-danger mt-3"><?= htmlspecialchars($simulationError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
<?php if (!empty($simulation)): ?>
                    <div class="card mt-3">
                        <div class="card-body">
                            <h3 class="h6"><?= htmlspecialchars(t('events.simulation.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($simulation as $entry): ?>
                                    <li><span class="badge bg-primary text-light me-2"><?= htmlspecialchars($entry['display'], ENT_QUOTES, 'UTF-8') ?></span><span class="text-muted"><?= htmlspecialchars(t('events.simulation.raw', ['value' => (int) $entry['raw']]), ENT_QUOTES, 'UTF-8') ?></span></li>
                                <?php endforeach; ?>
                                <?php if (!$simulation): ?>
                                    <li class="text-muted"><?= htmlspecialchars(t('events.simulation.empty'), ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('events.table.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('events.table.columns.title'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('events.table.columns.period'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('events.table.columns.venues'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('events.table.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th class="text-end"><?= htmlspecialchars(t('events.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($event['start_date'] ?? t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($event['end_date'] ?? t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php foreach ($event['venues_list'] as $venue): ?>
                                        <span class="badge bg-light text-dark me-1 mb-1"><?= htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php if ((int) ($event['is_active'] ?? 0) === 1): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars(t('events.status.active'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars(t('events.status.inactive'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <?php if (!empty($isAdmin)): ?>
                                            <?php if ((int) ($event['is_active'] ?? 0) === 1): ?>
                                                <form method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning"><?= htmlspecialchars(t('events.table.deactivate'), ENT_QUOTES, 'UTF-8') ?></button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="set_active">
                                                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success"><?= htmlspecialchars(t('events.table.set_active'), ENT_QUOTES, 'UTF-8') ?></button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <a class="btn btn-sm btn-outline-secondary" href="events.php?edit=<?= (int) $event['id'] ?>"><?= htmlspecialchars(t('events.table.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post" onsubmit="return confirm(<?= json_encode(t('events.table.confirm_delete')) ?>);">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(t('events.table.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
