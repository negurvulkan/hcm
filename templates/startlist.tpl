<?php
/** @var array $classes */
/** @var array $selectedClass */
/** @var array $startlist */
/** @var array $conflicts */
/** @var bool $hasDepartments */

if (!function_exists('startlist_prepare_entity_fields')) {
    function startlist_prepare_entity_fields(array $fields): array
    {
        return array_values(array_filter($fields, static function (array $field): bool {
            $value = $field['value'] ?? null;
            return $value !== null && $value !== '';
        }));
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= htmlspecialchars(t('startlist.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
    <form method="get" class="d-flex gap-2">
        <select name="class_id" class="form-select">
            <?php foreach ($classes as $class): ?>
                <option value="<?= (int) $class['id'] ?>" <?= (int) $selectedClass['id'] === (int) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['title'] . ' · ' . $class['label'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit"><?= htmlspecialchars(t('common.actions.switch'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>

<div class="mb-3">
    <form method="post" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="generate">
        <input type="hidden" name="class_id" value="<?= (int) $selectedClass['id'] ?>">
        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('startlist.actions.generate'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>

<?php if ($conflicts): ?>
    <div class="alert alert-warning">
        <strong><?= htmlspecialchars(t('startlist.conflicts.title'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars(t('startlist.conflicts.description'), ENT_QUOTES, 'UTF-8') ?>
        <ul class="mb-0">
            <?php foreach ($conflicts as [$first, $second]): ?>
                <li><?= htmlspecialchars(t('startlist.conflicts.item', [
                    'first_horse' => $first['horse'],
                    'first_position' => $first['position'],
                    'second_horse' => $second['horse'],
                    'second_position' => $second['position'],
                ]), ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <?php if (!empty($isGroupClass)): ?>
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                    <tr>
                        <th><?= htmlspecialchars(t('startlist.table.columns.position'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.start_number'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.group'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.members'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.planned_start_note'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($groupedStartlist): ?>
                        <?php foreach ($groupedStartlist as $group): ?>
                            <?php $primary = $group['primary'] ?? []; ?>
                            <?php
                            $stateKey = $group['state'] ?? 'scheduled';
                            $stateBadge = match ($stateKey) {
                                'completed' => 'bg-success',
                                'running' => 'bg-primary',
                                'withdrawn' => 'bg-danger',
                                'mixed' => 'bg-warning text-dark',
                                default => 'bg-secondary text-dark',
                            };
                            $rowClass = $stateKey === 'withdrawn' ? 'table-secondary' : '';
                            $plannedValue = !empty($group['planned_start']) ? date('Y-m-d\TH:i', strtotime($group['planned_start'])) : '';
                            $noteValue = $group['note'] ?? '';
                            $toggleClass = $stateKey === 'withdrawn' ? 'btn-outline-success' : 'btn-outline-warning';
                            $toggleLabel = $stateKey === 'withdrawn' ? t('startlist.actions.reactivate') : t('startlist.actions.withdraw');
                            $stateTranslationKey = $stateKey === 'mixed' ? 'mixed' : $stateKey;
                            ?>
                            <tr class="<?= htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8') ?>">
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
                                            $riderFields = startlist_prepare_entity_fields(array_merge([
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
                                            $horseFields = startlist_prepare_entity_fields(array_merge([
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
                                                    <?php if (($member['state'] ?? 'scheduled') === 'withdrawn' && $stateKey !== 'withdrawn'): ?>
                                                        <span class="badge bg-danger"><?= htmlspecialchars(t('startlist.status.withdrawn'), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <form method="post" class="d-flex flex-column gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_time">
                                        <input type="hidden" name="item_id" value="<?= (int) ($primary['id'] ?? 0) ?>">
                                        <div class="d-flex gap-2">
                                            <input type="datetime-local" class="form-control form-control-sm" name="planned_start" value="<?= htmlspecialchars($plannedValue, ENT_QUOTES, 'UTF-8') ?>">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit"><?= htmlspecialchars(t('common.actions.save'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </div>
                                        <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="<?= htmlspecialchars(t('startlist.table.note_placeholder'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($noteValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge <?= htmlspecialchars($stateBadge, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(t('startlist.status.' . $stateTranslationKey), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="move">
                                        <input type="hidden" name="item_id" value="<?= (int) ($primary['id'] ?? 0) ?>">
                                        <input type="hidden" name="direction" value="up">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">▲</button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="move">
                                        <input type="hidden" name="item_id" value="<?= (int) ($primary['id'] ?? 0) ?>">
                                        <input type="hidden" name="direction" value="down">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">▼</button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_state">
                                        <input type="hidden" name="item_id" value="<?= (int) ($primary['id'] ?? 0) ?>">
                                        <button class="btn btn-sm <?= htmlspecialchars($toggleClass, ENT_QUOTES, 'UTF-8') ?>" type="submit">
                                            <?= htmlspecialchars($toggleLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="reassign_number">
                                        <input type="hidden" name="item_id" value="<?= (int) ($primary['id'] ?? 0) ?>">
                                        <button class="btn btn-sm btn-outline-primary" type="submit" <?= !empty($group['has_locked_start_number']) ? 'disabled' : '' ?>><?= htmlspecialchars(t('startlist.actions.reassign_number'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm(<?= json_encode(t('startlist.confirm.delete')) ?>)">
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
                            <td colspan="7" class="text-muted"><?= htmlspecialchars(t('startlist.table.empty'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                    <tr>
                        <th><?= htmlspecialchars(t('startlist.table.columns.position'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.start_number'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.rider'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.horse'), ENT_QUOTES, 'UTF-8') ?></th>
                        <?php if (!empty($hasDepartments)): ?>
                            <th><?= htmlspecialchars(t('startlist.table.columns.department'), ENT_QUOTES, 'UTF-8') ?></th>
                        <?php endif; ?>
                        <th><?= htmlspecialchars(t('startlist.table.columns.planned_start_note'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(t('startlist.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($startlist as $item): ?>
                        <?php
                        $riderDate = !empty($item['rider_date_of_birth']) ? date('d.m.Y', strtotime($item['rider_date_of_birth'])) : null;
                        $riderCustomFields = $item['rider_custom_fields'] ?? [];
                        $riderFields = startlist_prepare_entity_fields(array_merge([
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
                        $horseFields = startlist_prepare_entity_fields(array_merge([
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
                        <tr class="<?= $item['state'] === 'withdrawn' ? 'table-secondary' : '' ?>">
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
                            <?php if (!empty($hasDepartments)): ?>
                                <td><?= !empty($item['department']) ? '<span class="badge bg-info-subtle text-info">' . htmlspecialchars($item['department'], ENT_QUOTES, 'UTF-8') . '</span>' : '–' ?></td>
                            <?php endif; ?>
                            <td>
                                <form method="post" class="d-flex flex-column gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_time">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <div class="d-flex gap-2">
                                        <input type="datetime-local" class="form-control form-control-sm" name="planned_start" value="<?= htmlspecialchars($item['planned_start'] ? date('Y-m-d\TH:i', strtotime($item['planned_start'])) : '', ENT_QUOTES, 'UTF-8') ?>">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit"><?= htmlspecialchars(t('common.actions.save'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </div>
                                    <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="<?= htmlspecialchars(t('startlist.table.note_placeholder'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                </form>
                            </td>
                            <td>
                                <span class="badge <?= $item['state'] === 'withdrawn' ? 'bg-danger' : 'bg-success' ?>">
                                    <?= htmlspecialchars(t('startlist.status.' . ($item['state'] ?? 'scheduled')), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="move">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="direction" value="up">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">▲</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="move">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="direction" value="down">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">▼</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_state">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <button class="btn btn-sm <?= $item['state'] === 'withdrawn' ? 'btn-outline-success' : 'btn-outline-warning' ?>" type="submit">
                                        <?= htmlspecialchars($item['state'] === 'withdrawn' ? t('startlist.actions.reactivate') : t('startlist.actions.withdraw'), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </form>
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reassign_number">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <button class="btn btn-sm btn-outline-primary" type="submit" <?= !empty($item['start_number_locked_at']) ? 'disabled' : '' ?>><?= htmlspecialchars(t('startlist.actions.reassign_number'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                                <form method="post" class="d-inline" onsubmit="return confirm(<?= json_encode(t('startlist.confirm.delete')) ?>)">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('common.actions.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$startlist): ?>
                        <tr>
                            <td colspan="7" class="text-muted"><?= htmlspecialchars(t('startlist.table.empty'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/entity_info_modal.tpl'; ?>
