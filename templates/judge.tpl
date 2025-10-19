<?php
/** @var array $classes */
/** @var array $selectedClass */
/** @var array|null $start */
/** @var array $starts */
/** @var array $rule */
/** @var array|null $result */
/** @var array $fieldsInput */
/** @var array $judgeComponents */
/** @var array|null $evaluation */
/** @var array $perJudgeScores */
/** @var array $otherJudges */
/** @var string $judgeKey */
/** @var array $startNumberRule */
/** @var array $startStateCounts */

if (!function_exists('judge_prepare_entity_fields')) {
    function judge_prepare_entity_fields(array $fields): array
    {
        return array_values(array_filter($fields, static function (array $field): bool {
            $value = $field['value'] ?? null;
            return $value !== null && $value !== '';
        }));
    }
}

$riderInfoPayload = null;
$horseInfoPayload = null;
$riderInfoJson = null;
$horseInfoJson = null;

if ($start) {
    $riderDate = !empty($start['rider_date_of_birth']) ? date('d.m.Y', strtotime($start['rider_date_of_birth'])) : null;
    $riderCustomFields = $start['rider_custom_fields'] ?? [];
    $riderFields = judge_prepare_entity_fields(array_merge([
        ['label' => t('entity_info.labels.name'), 'value' => $start['rider'] ?? null],
        ['label' => t('entity_info.labels.club'), 'value' => $start['rider_club_name'] ?? null],
        ['label' => t('entity_info.labels.email'), 'value' => $start['rider_email'] ?? null],
        ['label' => t('entity_info.labels.phone'), 'value' => $start['rider_phone'] ?? null],
        ['label' => t('entity_info.labels.date_of_birth'), 'value' => $riderDate],
        ['label' => t('entity_info.labels.nationality'), 'value' => $start['rider_nationality'] ?? null],
    ], $riderCustomFields));
    $riderInfoPayload = [
        'title' => t('entity_info.title.rider', ['name' => $start['rider'] ?? '']),
        'fields' => $riderFields,
        'emptyMessage' => t('entity_info.empty'),
    ];
    $riderInfoJson = htmlspecialchars(json_encode($riderInfoPayload, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');

    $documentsOk = $start['horse_documents_ok'] ?? null;
    $documentsValue = $documentsOk === null ? null : ($documentsOk ? t('common.labels.yes') : t('common.labels.no'));
    $horseCustomFields = $start['horse_custom_fields'] ?? [];
    $horseFields = judge_prepare_entity_fields(array_merge([
        ['label' => t('entity_info.labels.name'), 'value' => $start['horse'] ?? null],
        ['label' => t('entity_info.labels.owner'), 'value' => $start['horse_owner_name'] ?? null],
        ['label' => t('entity_info.labels.life_number'), 'value' => $start['horse_life_number'] ?? null],
        ['label' => t('entity_info.labels.microchip'), 'value' => $start['horse_microchip'] ?? null],
        ['label' => t('entity_info.labels.sex'), 'value' => $start['horse_sex'] ? t('horses.sex.' . $start['horse_sex']) : null],
        ['label' => t('entity_info.labels.birth_year'), 'value' => $start['horse_birth_year'] ? (string) $start['horse_birth_year'] : null],
        ['label' => t('entity_info.labels.documents'), 'value' => $documentsValue],
        ['label' => t('entity_info.labels.notes'), 'value' => $start['horse_notes'] ?? null, 'multiline' => true],
    ], $horseCustomFields));
    $horseInfoPayload = [
        'title' => t('entity_info.title.horse', ['name' => $start['horse'] ?? '']),
        'fields' => $horseFields,
        'emptyMessage' => t('entity_info.empty'),
    ];
    $horseInfoJson = htmlspecialchars(json_encode($horseInfoPayload, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
}
?>
<div class="alert alert-info d-flex justify-content-between align-items-center">
    <div>
        <strong><?= htmlspecialchars($selectedClass['label'] ?? t('judge.banner.class_fallback'), ENT_QUOTES, 'UTF-8') ?></strong>
        <?php if (!empty($start['start_number_display'])): ?>
            <span class="badge bg-primary text-light ms-2"><?= htmlspecialchars(t('judge.banner.start_number', ['number' => $start['start_number_display']]), ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <?php if ($start): ?>
            <span class="mx-2">·</span>
            <span class="d-inline-flex align-items-center gap-2">
                <span><?= htmlspecialchars($start['rider'] ?? t('judge.banner.no_start'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($riderInfoJson): ?>
                    <button type="button"
                            class="entity-info-trigger"
                            data-entity-info="<?= $riderInfoJson ?>"
                            aria-label="<?= htmlspecialchars(t('entity_info.actions.show_rider'), ENT_QUOTES, 'UTF-8') ?>">
                        <span aria-hidden="true">&#9432;</span>
                    </button>
                <?php endif; ?>
            </span>
            <?php if (!empty($start['horse'])): ?>
                <span class="mx-2">·</span>
                <span class="d-inline-flex align-items-center gap-2">
                    <span><?= htmlspecialchars($start['horse'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($horseInfoJson): ?>
                        <button type="button"
                                class="entity-info-trigger"
                                data-entity-info="<?= $horseInfoJson ?>"
                                aria-label="<?= htmlspecialchars(t('entity_info.actions.show_horse'), ENT_QUOTES, 'UTF-8') ?>">
                            <span aria-hidden="true">&#9432;</span>
                        </button>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        <?php else: ?>
            <span class="mx-2">·</span>
            <span><?= htmlspecialchars(t('judge.banner.no_start'), ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <?php if (!empty($start['state'])): ?>
            <?php
            $stateKey = $start['state'];
            $stateClass = match ($stateKey) {
                'completed' => 'bg-success',
                'running' => 'bg-primary',
                'withdrawn' => 'bg-danger',
                default => 'bg-secondary',
            };
            ?>
            <span class="badge <?= htmlspecialchars($stateClass, ENT_QUOTES, 'UTF-8') ?> ms-2">
                <?= htmlspecialchars(t('judge.controls.state.' . $stateKey), ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="text-muted small">
        <?= htmlspecialchars(t('judge.banner.offline_hint'), ENT_QUOTES, 'UTF-8') ?>
        <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars(t('judge.banner.offline_badge'), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <form method="get" class="d-flex gap-2">
            <select name="class_id" class="form-select">
                <?php foreach ($classes as $class): ?>
                    <option value="<?= (int) $class['id'] ?>" <?= (int) $selectedClass['id'] === (int) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['title'] . ' · ' . $class['label'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-secondary" type="submit"><?= htmlspecialchars(t('judge.controls.switch'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>
    </div>
    <div class="col-md-8">
        <div class="d-flex flex-column gap-2 align-items-md-end">
            <div class="d-flex flex-wrap align-items-center gap-2" data-start-filter-group>
                <span class="small text-muted text-uppercase fw-semibold"><?= htmlspecialchars(t('judge.controls.filter.label'), ENT_QUOTES, 'UTF-8') ?></span>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary active" data-start-filter="all"><?= htmlspecialchars(t('judge.controls.filter.all'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="button" class="btn btn-outline-secondary" data-start-filter="pending"><?= htmlspecialchars(t('judge.controls.filter.pending'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="button" class="btn btn-outline-secondary" data-start-filter="running"><?= htmlspecialchars(t('judge.controls.filter.running'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="button" class="btn btn-outline-secondary" data-start-filter="completed"><?= htmlspecialchars(t('judge.controls.filter.completed'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </div>
            <?php if (!empty($startStateCounts)): ?>
                <div class="small text-muted">
                    <?php foreach ($startStateCounts as $stateKey => $count): ?>
                        <span class="me-2">
                            <strong><?= (int) $count ?></strong> <?= htmlspecialchars(t('judge.controls.state.' . $stateKey), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-2 justify-content-md-end" data-start-buttons>
                <?php foreach ($starts as $candidate): ?>
                    <?php
                    $state = $candidate['state'] ?? 'scheduled';
                    $stateBadge = match ($state) {
                        'completed' => 'bg-success',
                        'running' => 'bg-primary',
                        'withdrawn' => 'bg-danger',
                        default => 'bg-secondary text-dark',
                    };
                    ?>
                    <a href="judge.php?class_id=<?= (int) $selectedClass['id'] ?>&start_id=<?= (int) $candidate['id'] ?>" class="btn btn-sm <?= (int) $candidate['id'] === (int) $start['id'] ? 'btn-accent' : 'btn-outline-secondary' ?> d-flex align-items-center gap-2" data-start-state="<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>">
                        <span><?= htmlspecialchars(t('judge.controls.position', ['position' => (int) $candidate['position']]), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($candidate['start_number_display'])): ?>
                            <span class="badge bg-primary text-light"><?= htmlspecialchars($candidate['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <span class="badge <?= htmlspecialchars($stateBadge, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('judge.controls.state.' . $state), ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!$start): ?>
    <p class="text-muted"><?= htmlspecialchars(t('judge.empty'), ENT_QUOTES, 'UTF-8') ?></p>
<?php else: ?>
<form method="post" class="card" data-autosave>
    <div class="card-body">
        <?= csrf_field() ?>
        <input type="hidden" name="class_id" value="<?= (int) $selectedClass['id'] ?>">
        <input type="hidden" name="start_id" value="<?= (int) $start['id'] ?>">
        <h2 class="h5 mb-2"><?= htmlspecialchars(t('judge.form.heading', ['rider' => $start['rider'], 'horse' => $start['horse']]), ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small text-uppercase fw-semibold"><?= htmlspecialchars(t('startlist.table.columns.rider'), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="fw-semibold"><?= htmlspecialchars($start['rider'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($riderInfoJson): ?>
                    <button type="button"
                            class="entity-info-trigger"
                            data-entity-info="<?= $riderInfoJson ?>"
                            aria-label="<?= htmlspecialchars(t('entity_info.actions.show_rider'), ENT_QUOTES, 'UTF-8') ?>">
                        <span aria-hidden="true">&#9432;</span>
                    </button>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small text-uppercase fw-semibold"><?= htmlspecialchars(t('startlist.table.columns.horse'), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="fw-semibold"><?= htmlspecialchars($start['horse'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($horseInfoJson): ?>
                    <button type="button"
                            class="entity-info-trigger"
                            data-entity-info="<?= $horseInfoJson ?>"
                            aria-label="<?= htmlspecialchars(t('entity_info.actions.show_horse'), ENT_QUOTES, 'UTF-8') ?>">
                        <span aria-hidden="true">&#9432;</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php $fields = $rule['input']['fields'] ?? []; ?>
        <?php if ($fields): ?>
            <div class="mb-4">
                <h3 class="h6"><?= htmlspecialchars(t('judge.form.global_inputs'), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="row g-3">
                    <?php foreach ($fields as $field): ?>
                        <?php $fieldId = $field['id'] ?? null; if (!$fieldId) { continue; }
                        $type = $field['type'] ?? 'number';
                        $value = $fieldsInput[$fieldId] ?? null; ?>
                        <div class="col-md-<?= in_array($type, ['set', 'textarea'], true) ? '12' : '4' ?>">
                            <label class="form-label"><?= htmlspecialchars($field['label'] ?? $fieldId, ENT_QUOTES, 'UTF-8') ?></label>
                            <?php if ($type === 'boolean'): ?>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="score[fields][<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>]" value="1" <?= $value ? 'checked' : '' ?>>
                                    <span class="form-text small ms-2"><?= htmlspecialchars(t('judge.form.toggle_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php elseif ($type === 'set'): ?>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach (($field['options'] ?? []) as $option): ?>
                                        <?php $checked = is_array($value) && in_array($option, $value, true); ?>
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox" name="score[fields][<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>][]" value="<?= htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8') ?>" <?= $checked ? 'checked' : '' ?>>
                                            <span class="form-check-label"><?= htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8') ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($type === 'text'): ?>
                                <input type="text"
                                       name="score[fields][<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>]"
                                       class="form-control"
                                       value="<?= $value !== null ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '' ?>">
                            <?php elseif ($type === 'textarea'): ?>
                                <?php $rows = max(1, (int) ($field['rows'] ?? 3)); ?>
                                <textarea name="score[fields][<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>]"
                                          class="form-control"
                                          rows="<?= $rows ?>"><?= $value !== null ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                            <?php else: ?>
                                <input type="number" step="0.01" name="score[fields][<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>]" class="form-control" value="<?= $value !== null ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '' ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <h3 class="h6"><?= htmlspecialchars(t('judge.form.judge_inputs', ['judge' => $judgeKey]), ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="row g-3">
                <?php foreach (($rule['input']['components'] ?? []) as $component): ?>
                    <?php $componentId = $component['id'] ?? null; if (!$componentId) { continue; }
                    $value = $judgeComponents[$componentId] ?? null; ?>
                    <div class="col-md-4">
                        <label class="form-label"><?= htmlspecialchars($component['label'] ?? $componentId, ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="number"
                               class="form-control"
                               name="score[components][<?= htmlspecialchars($componentId, ENT_QUOTES, 'UTF-8') ?>]"
                               value="<?= $value !== null ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '' ?>"
                               <?php if (isset($component['min'])): ?>min="<?= (float) $component['min'] ?>"<?php endif; ?>
                               <?php if (isset($component['max'])): ?>max="<?= (float) $component['max'] ?>"<?php endif; ?>
                               step="<?= isset($component['step']) ? (float) $component['step'] : 0.1 ?>">
                        <?php if (!empty($component['weight'])): ?>
                            <div class="form-text"><?= htmlspecialchars(t('judge.form.weight_hint', ['weight' => $component['weight']]), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php $lessons = $rule['input']['lessons'] ?? []; ?>
        <?php if ($lessons): ?>
            <div class="mb-4">
                <h3 class="h6"><?= htmlspecialchars(t('judge.form.lessons_heading', ['judge' => $judgeKey]), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="row g-3">
                    <?php foreach ($lessons as $lesson): ?>
                        <?php $lessonId = $lesson['id'] ?? null; if (!$lessonId) { continue; }
                        $value = $judgeLessons[$lessonId] ?? null; ?>
                        <div class="col-md-4">
                            <label class="form-label"><?= htmlspecialchars($lesson['label'] ?? $lessonId, ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="number"
                                   class="form-control"
                                   name="score[lessons][<?= htmlspecialchars($lessonId, ENT_QUOTES, 'UTF-8') ?>]"
                                   value="<?= $value !== null ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '' ?>"
                                   <?php if (isset($lesson['min'])): ?>min="<?= (float) $lesson['min'] ?>"<?php endif; ?>
                                   <?php if (isset($lesson['max'])): ?>max="<?= (float) $lesson['max'] ?>"<?php endif; ?>
                                   step="<?= isset($lesson['step']) ? (float) $lesson['step'] : 0.1 ?>">
                            <?php if (!empty($lesson['weight'])): ?>
                                <div class="form-text"><?= htmlspecialchars(t('judge.form.weight_hint', ['weight' => $lesson['weight']]), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($otherJudges)): ?>
            <div class="mb-4">
                <h3 class="h6"><?= htmlspecialchars(t('judge.form.other_judges'), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('judge.form.other_judges_columns.judge'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('judge.form.other_judges_columns.score'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('judge.form.other_judges_columns.submitted'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($otherJudges as $key => $entry): ?>
                            <?php $scoreEntry = $perJudgeScores[$key] ?? null; ?>
                            <tr>
                                <td><?= htmlspecialchars($entry['user']['name'] ?? $key, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $scoreEntry ? htmlspecialchars(number_format((float) ($scoreEntry['score'] ?? 0), 2), ENT_QUOTES, 'UTF-8') : htmlspecialchars(t('judge.form.no_score'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($entry['submitted_at'] ?? t('judge.form.no_value'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($evaluation['totals'])): ?>
            <?php $totals = $evaluation['totals']; ?>
            <div class="alert alert-light border">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong><?= htmlspecialchars(t('judge.form.current_total'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="ms-2"><?= htmlspecialchars(number_format((float) ($totals['total_rounded'] ?? $totals['total_raw'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($totals['unit'] ?? t('judge.form.unit_default'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="text-muted small"><?= htmlspecialchars(t('judge.form.penalties', ['value' => number_format((float) ($totals['penalties']['total'] ?? 0), 2)]), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-check form-switch mt-4">
            <input class="form-check-input" type="checkbox" id="sign" name="sign" <?= ($result['status'] ?? '') === 'signed' ? 'checked' : '' ?>>
            <label class="form-check-label" for="sign"><?= htmlspecialchars(t('judge.form.sign_label'), ENT_QUOTES, 'UTF-8') ?></label>
        </div>
        <div class="alert alert-secondary mt-3" data-signature-status data-status-draft="<?= htmlspecialchars(t('judge.form.signature_status.draft'), ENT_QUOTES, 'UTF-8') ?>" data-status-signed="<?= htmlspecialchars(t('judge.form.signature_status.signed'), ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars(($result['status'] ?? '') === 'signed' ? t('judge.form.signature_status.signed') : t('judge.form.signature_status.draft'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="text-muted small"><?= htmlspecialchars(t('judge.form.autosave_note'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="d-flex gap-2">
            <?php if ($result): ?>
                <button class="btn btn-outline-danger" type="submit" name="action" value="delete_result" formnovalidate onclick="return confirm('<?= htmlspecialchars(t('judge.form.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>')"><?= htmlspecialchars(t('judge.form.delete'), ENT_QUOTES, 'UTF-8') ?></button>
            <?php endif; ?>
            <button class="btn btn-accent" type="submit" name="action" value="save"><?= htmlspecialchars(t('judge.form.save'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</form>
<?php endif; ?>

<?php require __DIR__ . '/partials/entity_info_modal.tpl'; ?>

