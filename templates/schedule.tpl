<?php
/** @var array $classes */
/** @var array $selectedClass */
/** @var array $items */
/** @var array $shifts */

if (!function_exists('schedule_prepare_entity_fields')) {
    function schedule_prepare_entity_fields(array $fields): array
    {
        return array_values(array_filter($fields, static function (array $field): bool {
            $value = $field['value'] ?? null;
            return $value !== null && $value !== '';
        }));
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= htmlspecialchars(t('schedule.title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <form method="get" class="d-flex gap-2">
        <select name="class_id" class="form-select">
            <?php foreach ($classes as $class): ?>
                <option value="<?= (int) $class['id'] ?>" <?= (int) $selectedClass['id'] === (int) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['title'] . ' · ' . $class['label'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit"><?= htmlspecialchars(t('common.actions.switch'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5"><?= htmlspecialchars(t('schedule.shift.title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <form method="post" class="row g-3 align-items-end" data-shift-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="shift">
            <div class="col-sm-4">
                <label class="form-label"><?= htmlspecialchars(t('schedule.shift.minutes_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="number" name="minutes" class="form-control" required data-shift-input>
            </div>
            <div class="col-sm-4 col-lg-3">
                <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('schedule.shift.submit'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
            <div class="col-12 col-lg-5">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small text-muted text-uppercase fw-semibold"><?= htmlspecialchars(t('schedule.shift.presets_label'), ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="btn-group btn-group-sm" role="group">
                        <?php foreach ([-10, -5, +5, +10] as $preset): ?>
                            <button type="button" class="btn btn-outline-secondary" data-shift-preset="<?= $preset ?>">
                                <?= htmlspecialchars(sprintf('%+d', $preset), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </form>
        <p class="text-muted small mt-2"><?= htmlspecialchars(t('schedule.shift.note'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5"><?= htmlspecialchars(t('schedule.slots.title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="table-responsive">
            <?php if (!empty($isGroupClass)): ?>
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                    <tr>
                        <th><?= htmlspecialchars(t('schedule.slots.table.columns.position'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('schedule.slots.table.columns.start_number'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.group'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.members'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('schedule.slots.table.columns.planned_start'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th class="text-end"><?= htmlspecialchars(t('schedule.slots.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($groupedItems): ?>
                        <?php foreach ($groupedItems as $group): ?>
                            <?php $primary = $group['primary'] ?? []; ?>
                            <?php
                            $plannedInitial = !empty($group['planned_start']) ? date('Y-m-d\TH:i', strtotime($group['planned_start'])) : '';
                            $plannedDisplay = $group['planned_start'] ? date('H:i', strtotime($group['planned_start'])) : t('schedule.slots.table.no_time');
                            ?>
                            <tr>
                                <td><?= (int) $group['position'] ?></td>
                                <td>
                                    <?php if (!empty($group['start_numbers'])): ?>
                                        <?php foreach ($group['start_numbers'] as $number): ?>
                                            <span class="badge bg-primary text-light"><?= htmlspecialchars($number, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted"><?= htmlspecialchars(t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($group['department']) ? '<span class="badge bg-info-subtle text-info">' . htmlspecialchars($group['department'], ENT_QUOTES, 'UTF-8') . '</span>' : '–' ?></td>
                                <td>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($group['members'] as $member): ?>
                                            <?php
                                            $riderDate = !empty($member['rider_date_of_birth']) ? date('d.m.Y', strtotime($member['rider_date_of_birth'])) : null;
                                            $riderCustomFields = $member['rider_custom_fields'] ?? [];
                                            $riderFields = schedule_prepare_entity_fields(array_merge([
                                                ['label' => t('entity_info.labels.name'), 'value' => $member['rider'] ?? null],
                                                ['label' => t('entity_info.labels.club'), 'value' => $member['rider_club_name'] ?? null],
                                                ['label' => t('entity_info.labels.email'), 'value' => $member['rider_email'] ?? null],
                                                ['label' => t('entity_info.labels.phone'), 'value' => $member['rider_phone'] ?? null],
                                                ['label' => t('entity_info.labels.date_of_birth'), 'value' => $riderDate],
                                                ['label' => t('entity_info.labels.nationality'), 'value' => $member['rider_nationality'] ?? null],
                                            ], $riderCustomFields));
                                            $riderInfoPayload = [
                                                'title' => t('entity_info.title.rider', ['name' => $member['rider'] ?? '']),
                                                'fields' => $riderFields,
                                                'emptyMessage' => t('entity_info.empty'),
                                            ];
                                            $riderInfoJson = htmlspecialchars(json_encode($riderInfoPayload, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                                            $documentsOk = $member['horse_documents_ok'] ?? null;
                                            $documentsValue = $documentsOk === null ? null : ($documentsOk ? t('common.labels.yes') : t('common.labels.no'));
                                            $horseCustomFields = $member['horse_custom_fields'] ?? [];
                                            $horseFields = schedule_prepare_entity_fields(array_merge([
                                                ['label' => t('entity_info.labels.name'), 'value' => $member['horse'] ?? null],
                                                ['label' => t('entity_info.labels.owner'), 'value' => $member['horse_owner_name'] ?? null],
                                                ['label' => t('entity_info.labels.life_number'), 'value' => $member['horse_life_number'] ?? null],
                                                ['label' => t('entity_info.labels.microchip'), 'value' => $member['horse_microchip'] ?? null],
                                                ['label' => t('entity_info.labels.sex'), 'value' => $member['horse_sex'] ? t('horses.sex.' . $member['horse_sex']) : null],
                                                ['label' => t('entity_info.labels.birth_year'), 'value' => $member['horse_birth_year'] ? (string) $member['horse_birth_year'] : null],
                                                ['label' => t('entity_info.labels.documents'), 'value' => $documentsValue],
                                                ['label' => t('entity_info.labels.notes'), 'value' => $member['horse_notes'] ?? null, 'multiline' => true],
                                            ], $horseCustomFields));
                                            $horseInfoPayload = [
                                                'title' => t('entity_info.title.horse', ['name' => $member['horse'] ?? '']),
                                                'fields' => $horseFields,
                                                'emptyMessage' => t('entity_info.empty'),
                                            ];
                                            $horseInfoJson = htmlspecialchars(json_encode($horseInfoPayload, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                                            ?>
                                            <li class="mb-2">
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <?php if (!empty($member['start_number_display'])): ?>
                                                        <span class="badge bg-primary text-light"><?= htmlspecialchars($member['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                    <span><?= htmlspecialchars($member['rider'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <button type="button"
                                                            class="entity-info-trigger"
                                                            data-entity-info="<?= $riderInfoJson ?>"
                                                            aria-label="<?= htmlspecialchars(t('entity_info.actions.show_rider'), ENT_QUOTES, 'UTF-8') ?>">
                                                        <span aria-hidden="true">&#9432;</span>
                                                    </button>
                                                    <?php if (!empty($member['horse'])): ?>
                                                        <span class="text-muted">·</span>
                                                        <span><?= htmlspecialchars($member['horse'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        <button type="button"
                                                                class="entity-info-trigger"
                                                                data-entity-info="<?= $horseInfoJson ?>"
                                                                aria-label="<?= htmlspecialchars(t('entity_info.actions.show_horse'), ENT_QUOTES, 'UTF-8') ?>">
                                                            <span aria-hidden="true">&#9432;</span>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td data-slot-editor data-slot-initial="<?= htmlspecialchars($plannedInitial, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="d-flex align-items-center gap-2" data-slot-view>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($plannedDisplay, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-slot-edit><?= htmlspecialchars(t('schedule.slots.edit'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </div>
                                    <form method="post" class="d-flex flex-wrap gap-2 d-none" data-slot-form>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_item">
                                        <input type="hidden" name="item_id" value="<?= (int) ($primary['id'] ?? 0) ?>">
                                        <input type="datetime-local" name="planned_start" class="form-control form-control-sm" data-slot-input value="<?= htmlspecialchars($plannedInitial, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('common.actions.save'), ENT_QUOTES, 'UTF-8') ?></button>
                                            <button class="btn btn-outline-secondary" type="button" data-slot-cancel><?= htmlspecialchars(t('schedule.slots.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </div>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form method="post" class="d-inline" onsubmit="return confirm(<?= json_encode(t('schedule.slots.confirm_delete')) ?>)">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="item_id" value="<?= (int) ($primary['id'] ?? 0) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('common.actions.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-muted"><?= htmlspecialchars(t('startlist.table.empty'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                    <tr>
                        <th><?= htmlspecialchars(t('schedule.slots.table.columns.position'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('schedule.slots.table.columns.start_number'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('schedule.slots.table.columns.rider'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('schedule.slots.table.columns.horse'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('schedule.slots.table.columns.planned_start'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th class="text-end"><?= htmlspecialchars(t('schedule.slots.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $riderDate = !empty($item['rider_date_of_birth']) ? date('d.m.Y', strtotime($item['rider_date_of_birth'])) : null;
                        $riderCustomFields = $item['rider_custom_fields'] ?? [];
                        $riderFields = schedule_prepare_entity_fields(array_merge([
                            ['label' => t('entity_info.labels.name'), 'value' => $item['rider'] ?? null],
                            ['label' => t('entity_info.labels.club'), 'value' => $item['rider_club_name'] ?? null],
                            ['label' => t('entity_info.labels.email'), 'value' => $item['rider_email'] ?? null],
                            ['label' => t('entity_info.labels.phone'), 'value' => $item['rider_phone'] ?? null],
                            ['label' => t('entity_info.labels.date_of_birth'), 'value' => $riderDate],
                            ['label' => t('entity_info.labels.nationality'), 'value' => $item['rider_nationality'] ?? null],
                        ], $riderCustomFields));
                        $riderInfo = [
                            'title' => t('entity_info.title.rider', ['name' => $item['rider'] ?? '']),
                            'fields' => $riderFields,
                            'emptyMessage' => t('entity_info.empty'),
                        ];
                        $documentsOk = $item['horse_documents_ok'];
                        $documentsValue = $documentsOk === null ? null : ($documentsOk ? t('common.labels.yes') : t('common.labels.no'));
                        $horseCustomFields = $item['horse_custom_fields'] ?? [];
                        $horseFields = schedule_prepare_entity_fields(array_merge([
                            ['label' => t('entity_info.labels.name'), 'value' => $item['horse'] ?? null],
                            ['label' => t('entity_info.labels.owner'), 'value' => $item['horse_owner_name'] ?? null],
                            ['label' => t('entity_info.labels.life_number'), 'value' => $item['horse_life_number'] ?? null],
                            ['label' => t('entity_info.labels.microchip'), 'value' => $item['horse_microchip'] ?? null],
                            ['label' => t('entity_info.labels.sex'), 'value' => $item['horse_sex'] ? t('horses.sex.' . $item['horse_sex']) : null],
                            ['label' => t('entity_info.labels.birth_year'), 'value' => $item['horse_birth_year'] ? (string) $item['horse_birth_year'] : null],
                            ['label' => t('entity_info.labels.documents'), 'value' => $documentsValue],
                            ['label' => t('entity_info.labels.notes'), 'value' => $item['horse_notes'] ?? null, 'multiline' => true],
                        ], $horseCustomFields));
                        $horseInfo = [
                            'title' => t('entity_info.title.horse', ['name' => $item['horse'] ?? '']),
                            'fields' => $horseFields,
                            'emptyMessage' => t('entity_info.empty'),
                        ];
                        ?>
                        <tr>
                            <td><?= (int) $item['position'] ?></td>
                            <td>
                                <?php if (!empty($item['start_number_display'])): ?>
                                    <span class="badge bg-primary text-light"><?= htmlspecialchars($item['start_number_display'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="text-muted"><?= htmlspecialchars(t('common.labels.none'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span><?= htmlspecialchars($item['rider'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <button type="button"
                                            class="entity-info-trigger"
                                            data-entity-info="<?= htmlspecialchars(json_encode($riderInfo, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                                            aria-label="<?= htmlspecialchars(t('entity_info.actions.show_rider'), ENT_QUOTES, 'UTF-8') ?>">
                                        <span aria-hidden="true">&#9432;</span>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span><?= htmlspecialchars($item['horse'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <button type="button"
                                            class="entity-info-trigger"
                                            data-entity-info="<?= htmlspecialchars(json_encode($horseInfo, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                                            aria-label="<?= htmlspecialchars(t('entity_info.actions.show_horse'), ENT_QUOTES, 'UTF-8') ?>">
                                        <span aria-hidden="true">&#9432;</span>
                                    </button>
                                </div>
                            </td>
                            <td data-slot-editor data-slot-initial="<?= htmlspecialchars($item['planned_start'] ? date('Y-m-d\TH:i', strtotime($item['planned_start'])) : '', ENT_QUOTES, 'UTF-8') ?>">
                                <?php $plannedDisplay = $item['planned_start'] ? date('H:i', strtotime($item['planned_start'])) : t('schedule.slots.table.no_time'); ?>
                                <div class="d-flex align-items-center gap-2" data-slot-view>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($plannedDisplay, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-slot-edit><?= htmlspecialchars(t('schedule.slots.edit'), ENT_QUOTES, 'UTF-8') ?></button>
                                </div>
                                <form method="post" class="d-flex flex-wrap gap-2 d-none" data-slot-form>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_item">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <input type="datetime-local" name="planned_start" class="form-control form-control-sm" data-slot-input value="<?= htmlspecialchars($item['planned_start'] ? date('Y-m-d\TH:i', strtotime($item['planned_start'])) : '', ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('common.actions.save'), ENT_QUOTES, 'UTF-8') ?></button>
                                        <button class="btn btn-outline-secondary" type="button" data-slot-cancel><?= htmlspecialchars(t('schedule.slots.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </div>
                                </form>
                            </td>
                            <td class="text-end">
                                <form method="post" class="d-inline" onsubmit="return confirm(<?= json_encode(t('schedule.slots.confirm_delete')) ?>)">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('common.actions.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/entity_info_modal.tpl'; ?>

<div class="card">
    <div class="card-body">
        <h2 class="h6"><?= htmlspecialchars(t('schedule.history.title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <ul class="list-unstyled mb-0">
            <?php foreach ($shifts as $shift): ?>
                <li><span class="badge bg-light text-dark me-2"><?= htmlspecialchars(t('schedule.history.minutes', ['value' => (int) $shift['shift_minutes']]), ENT_QUOTES, 'UTF-8') ?></span> <?= htmlspecialchars(date('d.m.Y H:i', strtotime($shift['created_at'])), ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
            <?php if (!$shifts): ?>
                <li class="text-muted"><?= htmlspecialchars(t('schedule.history.empty'), ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
        </ul>
    </div>
</div>
