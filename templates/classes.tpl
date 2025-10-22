<?php
/** @var array $events */
/** @var array $classes */
/** @var array $presets */
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars($editClass ? t('classes.form.edit_title') : t('classes.form.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post" data-class-form data-presets='<?= json_encode($presets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>' data-arenas='<?= htmlspecialchars($arenaPickerDataJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>'>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editClass ? 'update' : 'create' ?>">
                    <input type="hidden" name="class_id" value="<?= $editClass ? (int) $editClass['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('classes.form.event_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="event_id" class="form-select" required>
                            <option value=""><?= htmlspecialchars(t('classes.form.event_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= (int) $event['id'] ?>" <?= $editClass && (int) $editClass['event_id'] === (int) $event['id'] ? 'selected' : '' ?>><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('classes.form.label_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="label" class="form-control" value="<?= htmlspecialchars($editClass['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <?php $selectedEventId = $editClass['event_id'] ?? null; $selectedArenaOptions = $selectedEventId && isset($arenaOptionsByEvent[(string) $selectedEventId]) ? $arenaOptionsByEvent[(string) $selectedEventId] : []; ?>
                    <div class="mb-3" data-arena-picker>
                        <label class="form-label"><?= htmlspecialchars(t('classes.form.arena_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="event_arena_id" class="form-select" data-arena-select data-selected="<?= !empty($editClass['event_arena_id']) ? (int) $editClass['event_arena_id'] : '' ?>">
                            <option value=""><?= htmlspecialchars(t('classes.form.arena_picker.placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($selectedArenaOptions as $option): ?>
                                <option value="<?= (int) $option['id'] ?>" <?= !empty($editClass['event_arena_id']) && (int) $editClass['event_arena_id'] === (int) $option['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" data-arena-summary data-empty="<?= htmlspecialchars(t('classes.form.arena_picker.empty'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($editClass['arena_option']['summary'])): ?>
                                <?= htmlspecialchars($editClass['arena_option']['summary'], ENT_QUOTES, 'UTF-8') ?>
                            <?php elseif (!empty($editClass['arena_display'] ?? $editClass['arena'] ?? '')): ?>
                                <?= htmlspecialchars($editClass['arena_display'] ?? $editClass['arena'], ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                <?= htmlspecialchars(t('classes.form.arena_picker.empty'), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-arena-quick-toggle><?= htmlspecialchars(t('classes.form.arena_picker.quick_toggle'), ENT_QUOTES, 'UTF-8') ?></button>
                        <div class="border rounded p-3 mt-2 d-none" data-arena-quick-form>
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <label class="form-label mb-1"><?= htmlspecialchars(t('classes.form.arena_picker.quick_name'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="text" name="arena_quick_name" class="form-control" value="">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label mb-1"><?= htmlspecialchars(t('classes.form.arena_picker.quick_location'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="text" name="arena_quick_location" class="form-control" value="">
                                </div>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-sm-4">
                                    <label class="form-label mb-1"><?= htmlspecialchars(t('classes.form.arena_picker.quick_type'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <select class="form-select" name="arena_quick_type">
                                        <option value="indoor"><?= htmlspecialchars(t('classes.form.arena_picker.types.indoor'), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="outdoor" selected><?= htmlspecialchars(t('classes.form.arena_picker.types.outdoor'), ENT_QUOTES, 'UTF-8') ?></option>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label mb-1"><?= htmlspecialchars(t('classes.form.arena_picker.quick_surface'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="text" name="arena_quick_surface" class="form-control" value="">
                                </div>
                                <div class="col-sm-2">
                                    <label class="form-label mb-1"><?= htmlspecialchars(t('classes.form.arena_picker.quick_length'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="number" name="arena_quick_length" class="form-control" min="0" step="1" value="">
                                </div>
                                <div class="col-sm-2">
                                    <label class="form-label mb-1"><?= htmlspecialchars(t('classes.form.arena_picker.quick_width'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="number" name="arena_quick_width" class="form-control" min="0" step="1" value="">
                                </div>
                            </div>
                            <div class="form-text mt-2"><?= htmlspecialchars(t('classes.form.arena_picker.quick_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php if (!empty($editClass['legacy_arena'])): ?>
                            <div class="alert alert-warning mt-2 small mb-0"><?= htmlspecialchars(t('classes.form.arena_picker.legacy_notice', ['name' => $editClass['legacy_arena']]), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label"><?= htmlspecialchars(t('classes.form.start_label'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="datetime-local" name="start_time" class="form-control" value="<?= htmlspecialchars($editClass['start_formatted'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col">
                            <label class="form-label"><?= htmlspecialchars(t('classes.form.end_label'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="datetime-local" name="end_time" class="form-control" value="<?= htmlspecialchars($editClass['end_formatted'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="mt-3 mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('classes.form.max_starters_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="number" name="max_starters" class="form-control" min="1" value="<?= htmlspecialchars($editClass['max_starters'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <?php if (!empty($supportsGroupMode)): ?>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_group" value="1" id="class-group-toggle" <?= !empty($editClass['is_group']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="class-group-toggle">
                                <?= htmlspecialchars(t('classes.form.is_group_label'), ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <div class="form-text"><?= htmlspecialchars(t('classes.form.is_group_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('classes.form.judges_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="judges" class="form-control" placeholder="<?= htmlspecialchars(t('classes.form.judges_placeholder'), ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($editClass['judges'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3" data-rule-editor>
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label mb-0"><?= htmlspecialchars(t('classes.form.rules_label'), ENT_QUOTES, 'UTF-8') ?></label>
                            <button class="btn btn-sm btn-outline-secondary d-none" data-rule-toggle type="button"><?= htmlspecialchars(t('classes.form.rules_toggle_json'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                        <div class="form-text mb-2"><?= htmlspecialchars(t('classes.form.rules_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <div class="input-group input-group-sm" style="max-width: 320px;">
                                <span class="input-group-text"><?= htmlspecialchars(t('classes.form.preset_select_label'), ENT_QUOTES, 'UTF-8') ?></span>
                                <select class="form-select" data-preset-select>
                                    <option value=""><?= htmlspecialchars(t('classes.form.preset_select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php foreach ($scoringPresetOptions as $option): ?>
                                        <option value="<?= htmlspecialchars($option['key'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="alert alert-warning d-none" role="alert" data-rule-error></div>
                        <div class="border rounded p-3 bg-light-subtle d-none" data-rule-builder>
                            <div class="row g-3 align-items-end mb-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?= htmlspecialchars(t('classes.designer.discipline'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <select class="form-select form-select-sm" data-rule-type>
                                        <option value="dressage"><?= htmlspecialchars(t('classes.designer.types.dressage'), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="jumping"><?= htmlspecialchars(t('classes.designer.types.jumping'), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="western"><?= htmlspecialchars(t('classes.designer.types.western'), ENT_QUOTES, 'UTF-8') ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <textarea id="class-scoring-rule" class="form-control font-monospace" data-rule-json name="rules_json" rows="14" spellcheck="false" placeholder='{"version":"1"}'><?= htmlspecialchars($editClass['rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text"><?= htmlspecialchars(t('classes.form.rules_note'), ENT_QUOTES, 'UTF-8') ?></div>
                        <button class="btn btn-outline-secondary btn-sm mt-2" type="button" data-bs-toggle="modal" data-bs-target="#class-scoring-designer-modal"><?= htmlspecialchars(t('classes.form.scoring_open_designer'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                    <div class="modal fade" id="class-scoring-designer-modal" tabindex="-1" aria-labelledby="class-scoring-designer-title" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="class-scoring-designer-title"><?= htmlspecialchars(t('scoring_designer.title'), ENT_QUOTES, 'UTF-8') ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('common.actions.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="card border-secondary" data-scoring-designer data-target="#class-scoring-rule"
                                         data-default='<?= htmlspecialchars($scoringDesignerDefaultJson ?? "{}", ENT_QUOTES, 'UTF-8') ?>'
                                         data-presets='<?= htmlspecialchars($scoringDesignerPresetsJson ?? "{}", ENT_QUOTES, 'UTF-8') ?>'>
                                        <div class="card-body">
                                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                                                <div>
                                                    <h3 class="h6 mb-1"><?= htmlspecialchars(t('scoring_designer.header.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                                                    <p class="text-muted mb-0"><?= htmlspecialchars(t('scoring_designer.header.description.classes'), ENT_QUOTES, 'UTF-8') ?></p>
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
                                                    <select class="form-select form-select-sm" data-scoring-path="input.judges.aggregation">
                                                        <option value="mean"><?= htmlspecialchars(t('scoring_designer.sections.judges.options.mean'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="sum"><?= htmlspecialchars(t('scoring_designer.sections.judges.options.sum'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="median"><?= htmlspecialchars(t('scoring_designer.sections.judges.options.median'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="best"><?= htmlspecialchars(t('scoring_designer.sections.judges.options.best'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.judges.drop_high'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-type="integer" data-scoring-path="input.judges.drop_high" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.judges.drop_low'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-type="integer" data-scoring-path="input.judges.drop_low" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.judges.weights'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control form-control-sm" data-type="csv-number" data-scoring-path="input.judges.weights">
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
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-action="add-tiebreak"><?= htmlspecialchars(t('scoring_designer.sections.tiebreakers.add'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </div>
                                            <div class="vstack gap-3 mb-4" data-scoring-list="tiebreakers" data-empty-text="<?= htmlspecialchars(t('scoring_designer.sections.tiebreakers.empty'), ENT_QUOTES, 'UTF-8') ?>"></div>
                                            <h4 class="h6"><?= htmlspecialchars(t('scoring_designer.sections.grouping.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-4 d-flex align-items-center">
                                                    <div class="form-check mt-3 mt-sm-0">
                                                        <input class="form-check-input" type="checkbox" value="1" id="class-scoring-department-enabled" data-scoring-path="grouping.department.enabled" data-type="boolean">
                                                        <label class="form-check-label" for="class-scoring-department-enabled"><?= htmlspecialchars(t('scoring_designer.sections.grouping.enabled'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.grouping.label'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="text" class="form-control form-control-sm" data-scoring-path="grouping.department.label">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.grouping.aggregation'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <select class="form-select form-select-sm" data-scoring-path="grouping.department.aggregation">
                                                        <option value="mean"><?= htmlspecialchars(t('scoring_designer.sections.grouping.options.mean'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="sum"><?= htmlspecialchars(t('scoring_designer.sections.grouping.options.sum'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="median"><?= htmlspecialchars(t('scoring_designer.sections.grouping.options.median'), ENT_QUOTES, 'UTF-8') ?></option>
                                                        <option value="best"><?= htmlspecialchars(t('scoring_designer.sections.grouping.options.best'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.grouping.rounding'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="grouping.department.rounding" data-type="integer" min="0">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label"><?= htmlspecialchars(t('scoring_designer.sections.grouping.min_members'), ENT_QUOTES, 'UTF-8') ?></label>
                                                    <input type="number" class="form-control form-control-sm" data-scoring-path="grouping.department.min_members" data-type="integer" min="1">
                                                </div>
                                            </div>
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
                                                        <input class="form-check-input" type="checkbox" value="1" id="class-scoring-show-breakdown" data-scoring-path="output.show_breakdown" data-type="boolean">
                                                        <label class="form-check-label" for="class-scoring-show-breakdown"><?= htmlspecialchars(t('scoring_designer.sections.output.breakdown'), ENT_QUOTES, 'UTF-8') ?></label>
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
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="input-group input-group-sm" style="max-width: 200px;">
                            <span class="input-group-text"><?= htmlspecialchars(t('classes.form.simulation_count_label'), ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="number" class="form-control" name="simulation_count" value="<?= (int) ($simulationCount ?? 10) ?>" min="1" max="50">
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="submit" name="simulate_scoring" value="1" formnovalidate><?= htmlspecialchars(t('classes.form.simulate_scoring'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if (!empty($scoringSimulationError)): ?>
                            <span class="text-danger small"><?= htmlspecialchars($scoringSimulationError, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($scoringSimulation)): ?>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm">
                                <thead class="table-light">
                                <tr>
                                    <th><?= htmlspecialchars(t('classes.scoring_simulation.columns.index'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('classes.scoring_simulation.columns.total'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('classes.scoring_simulation.columns.penalties'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('classes.scoring_simulation.columns.time'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('classes.scoring_simulation.columns.eliminated'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('classes.scoring_simulation.columns.rank'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($scoringSimulation as $index => $sample): ?>
                                    <?php $result = $sample['result'] ?? []; ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars(number_format((float) ($result['total_rounded'] ?? $result['total_raw'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(number_format((float) ($result['penalties']['total'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(isset($result['time']['seconds']) ? number_format((float) $result['time']['seconds'], 2) : t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= !empty($result['eliminated']) ? '<span class="badge bg-danger">' . htmlspecialchars(t('common.labels.yes'), ENT_QUOTES, 'UTF-8') . '</span>' : '<span class="badge bg-success-subtle text-success">' . htmlspecialchars(t('common.labels.no'), ENT_QUOTES, 'UTF-8') . '</span>' ?></td>
                                        <td><?= htmlspecialchars((string) ($result['rank'] ?? t('common.labels.none')), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="class-start-number-rules"><?= htmlspecialchars(t('classes.form.start_numbers_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea id="class-start-number-rules" name="start_number_rules" class="form-control" rows="6" spellcheck="false" placeholder='{"mode":"classic"}'><?= htmlspecialchars($editClass['start_number_rules_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text"><?= htmlspecialchars(t('classes.form.start_numbers_note'), ENT_QUOTES, 'UTF-8') ?></div>
                        <button class="btn btn-outline-secondary btn-sm mt-2" type="button" data-bs-toggle="modal" data-bs-target="#class-start-number-designer-modal"><?= htmlspecialchars(t('classes.form.start_numbers_open_designer'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                    <div class="modal fade" id="class-start-number-designer-modal" tabindex="-1" aria-labelledby="class-start-number-designer-title" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="class-start-number-designer-title"><?= htmlspecialchars(t('start_numbers.designer.title'), ENT_QUOTES, 'UTF-8') ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('common.actions.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="card border-secondary" data-start-number-designer data-target="#class-start-number-rules"
                                         data-rule="<?= htmlspecialchars($classRuleDesignerJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>"
                                         data-default="<?= htmlspecialchars($classRuleDefaultsJson ?? '{}', ENT_QUOTES, 'UTF-8') ?>"
                                         data-presets='<?= htmlspecialchars($startNumberDesignerPresetsJson ?? "{}", ENT_QUOTES, 'UTF-8') ?>'
                                        <?php if (!empty($classRuleEventJson)): ?> data-event-rule="<?= htmlspecialchars($classRuleEventJson, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
                                        <div class="card-body">
                                            <div class="d-flex align-items-start justify-content-between mb-3">
                                                <div>
                                                    <h3 class="h6 mb-1"><?= htmlspecialchars(t('start_numbers.designer.configuration.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                                                    <p class="text-muted mb-0"><?= htmlspecialchars(t('classes.form.start_numbers_description'), ENT_QUOTES, 'UTF-8') ?></p>
                                                </div>
                                                <div class="btn-toolbar" role="toolbar">
                                                    <div class="btn-group btn-group-sm me-2" role="group">
                                                        <button class="btn btn-outline-secondary" type="button" data-action="load-json"><?= htmlspecialchars(t('start_numbers.designer.actions.load_json_short'), ENT_QUOTES, 'UTF-8') ?></button>
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
                                                    <?php if (!empty($classRuleEventJson)): ?>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button class="btn btn-outline-secondary" type="button" data-action="load-event-rule"><?= htmlspecialchars(t('classes.form.use_event_rule'), ENT_QUOTES, 'UTF-8') ?></button>
                                                        </div>
                                                    <?php endif; ?>
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
                                                        <option value="department"><?= htmlspecialchars(t('start_numbers.designer.options.entity.department'), ENT_QUOTES, 'UTF-8') ?></option>
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
                                                <?= htmlspecialchars(t('classes.form.start_rules_note'), ENT_QUOTES, 'UTF-8') ?>
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
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <button class="btn btn-sm btn-outline-primary" type="submit" name="simulate" value="1" formnovalidate><?= htmlspecialchars(t('classes.form.simulate_start_numbers', ['count' => 10]), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if (!empty($classSimulationError)): ?>
                            <span class="text-danger small"><?= htmlspecialchars($classSimulationError, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($classSimulation)): ?>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th><?= htmlspecialchars(t('classes.start_number_simulation.columns.index'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('classes.start_number_simulation.columns.raw'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('classes.start_number_simulation.columns.display'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($classSimulation as $index => $preview): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars((string) $preview['raw'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($preview['display'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('classes.form.tiebreakers_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="tiebreakers" class="form-control" placeholder="<?= htmlspecialchars(t('classes.form.tiebreakers_placeholder'), ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($editClass['tiebreakers_list'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('classes.form.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editClass): ?>
                            <a href="classes.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('classes.form.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
      </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('classes.list.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('classes.list.columns.event'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('classes.list.columns.label'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('classes.list.columns.schedule'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('classes.list.columns.judges'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('classes.list.columns.tiebreakers'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th class="text-end"><?= htmlspecialchars(t('classes.list.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><?= htmlspecialchars($class['event_title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($class['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (!empty($class['arena_display'])): ?>
                                        <div class="fw-semibold"><?= htmlspecialchars($class['arena_display'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!empty($class['arena_summary'])): ?>
                                            <div class="text-muted small"><?= htmlspecialchars($class['arena_summary'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-muted"><?= htmlspecialchars(t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($class['schedule_display'])): ?>
                                        <div class="text-muted small mt-1"><?= htmlspecialchars($class['schedule_display'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php foreach ($class['judges'] as $judge): ?>
                                        <span class="badge bg-light text-dark me-1 mb-1"><?= htmlspecialchars($judge, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php foreach ($class['tiebreakers'] as $item): ?>
                                        <span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="classes.php?edit=<?= (int) $class['id'] ?>"><?= htmlspecialchars(t('classes.list.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post" onsubmit="return confirm(<?= json_encode(t('classes.list.confirm_delete')) ?>);">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('classes.list.delete'), ENT_QUOTES, 'UTF-8') ?></button>
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
