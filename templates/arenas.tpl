<?php
/** @var array $locations */
/** @var array $arenas */
/** @var array $events */
/** @var array $eventArenaRows */
/** @var array $eventArenasByEvent */
/** @var array $availability */
/** @var array|null $editLocation */
/** @var array|null $editArena */
/** @var array|null $editEventArena */
$eventTitleMap = [];
foreach ($events as $event) {
    $eventTitleMap[(int) $event['id']] = $event['title'];
}
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('arenas.locations.heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post" class="mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editLocation ? 'update_location' : 'create_location' ?>">
                    <input type="hidden" name="location_id" value="<?= $editLocation ? (int) $editLocation['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('arenas.locations.fields.name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editLocation['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('arenas.locations.fields.address'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($editLocation['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('arenas.locations.fields.notes'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($editLocation['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars($editLocation ? t('arenas.locations.actions.update') : t('arenas.locations.actions.create'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editLocation): ?>
                            <a href="arenas.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('arenas.common.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="list-group list-group-flush">
                    <?php if ($locations): ?>
                        <?php foreach ($locations as $location): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="me-2">
                                    <div class="fw-semibold mb-1"><?= htmlspecialchars($location['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($location['address'])): ?>
                                        <div class="text-muted small"><?= nl2br(htmlspecialchars($location['address'], ENT_QUOTES, 'UTF-8')) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="arenas.php?edit_location=<?= (int) $location['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(t('arenas.common.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                    <form method="post" onsubmit="return confirm(<?= json_encode(t('arenas.locations.actions.confirm_delete')) ?>);">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_location">
                                        <input type="hidden" name="location_id" value="<?= (int) $location['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('arenas.common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item text-muted"><?= htmlspecialchars(t('arenas.locations.empty'), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('arenas.arenas.heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post" class="mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editArena ? 'update_arena' : 'create_arena' ?>">
                    <input type="hidden" name="arena_id" value="<?= $editArena ? (int) $editArena['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('arenas.arenas.fields.name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editArena['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('arenas.arenas.fields.location'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="location_id" class="form-select">
                            <option value=""><?= htmlspecialchars(t('arenas.arenas.fields.location_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= (int) $location['id'] ?>" <?= !empty($editArena['location_id']) && (int) $editArena['location_id'] === (int) $location['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($location['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.arenas.fields.type'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="type" class="form-select">
                                <option value="indoor" <?= !empty($editArena['type']) && $editArena['type'] === 'indoor' ? 'selected' : '' ?>><?= htmlspecialchars(t('arenas.arenas.types.indoor'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="outdoor" <?= empty($editArena['type']) || $editArena['type'] !== 'indoor' ? 'selected' : '' ?>><?= htmlspecialchars(t('arenas.arenas.types.outdoor'), ENT_QUOTES, 'UTF-8') ?></option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.arenas.fields.surface'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="surface" class="form-control" value="<?= htmlspecialchars($editArena['surface'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.arenas.fields.length'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="number" name="length_m" class="form-control" min="0" step="1" value="<?= htmlspecialchars(isset($editArena['length_m']) ? (int) $editArena['length_m'] : '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.arenas.fields.width'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="number" name="width_m" class="form-control" min="0" step="1" value="<?= htmlspecialchars(isset($editArena['width_m']) ? (int) $editArena['width_m'] : '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="covered" value="1" id="arena-covered" <?= !empty($editArena['covered']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="arena-covered"><?= htmlspecialchars(t('arenas.arenas.fields.covered'), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="lighting" value="1" id="arena-lighting" <?= !empty($editArena['lighting']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="arena-lighting"><?= htmlspecialchars(t('arenas.arenas.fields.lighting'), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="drainage" value="1" id="arena-drainage" <?= !empty($editArena['drainage']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="arena-drainage"><?= htmlspecialchars(t('arenas.arenas.fields.drainage'), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('arenas.arenas.fields.capacity'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="number" name="capacity" class="form-control" min="0" step="1" value="<?= htmlspecialchars(isset($editArena['capacity']) ? (int) $editArena['capacity'] : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('arenas.arenas.fields.notes'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($editArena['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars($editArena ? t('arenas.arenas.actions.update') : t('arenas.arenas.actions.create'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editArena): ?>
                            <a href="arenas.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('arenas.common.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if ($arenas): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($arenas as $arena): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="me-2">
                                        <div class="fw-semibold mb-1"><?= htmlspecialchars($arena['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!empty($arena['location_name'])): ?>
                                            <div class="text-muted small mb-1"><?= htmlspecialchars($arena['location_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($arena['summary'])): ?>
                                            <div class="text-muted small"><?= htmlspecialchars($arena['summary'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="arenas.php?edit_arena=<?= (int) $arena['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(t('arenas.common.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post" onsubmit="return confirm(<?= json_encode(t('arenas.arenas.actions.confirm_delete')) ?>);">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_arena">
                                            <input type="hidden" name="arena_id" value="<?= (int) $arena['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('arenas.common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= htmlspecialchars(t('arenas.arenas.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('arenas.event_arenas.heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post" class="mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editEventArena ? 'update_event_arena' : 'create_event_arena' ?>">
                    <input type="hidden" name="event_arena_id" value="<?= $editEventArena ? (int) $editEventArena['id'] : '' ?>">
                    <div class="row g-2">
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.event_arenas.fields.event'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="event_id" class="form-select" required>
                                <option value=""><?= htmlspecialchars(t('arenas.event_arenas.fields.event_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?= (int) $event['id'] ?>" <?= !empty($editEventArena['event_id']) && (int) $editEventArena['event_id'] === (int) $event['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.event_arenas.fields.arena'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="arena_id" class="form-select" required>
                                <option value=""><?= htmlspecialchars(t('arenas.event_arenas.fields.arena_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php foreach ($arenas as $arena): ?>
                                    <option value="<?= (int) $arena['id'] ?>" <?= !empty($editEventArena['arena_id']) && (int) $editEventArena['arena_id'] === (int) $arena['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($arena['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.event_arenas.fields.display_name'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="display_name" class="form-control" value="<?= htmlspecialchars($editEventArena['display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.event_arenas.fields.temp_surface'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="temp_surface" class="form-control" value="<?= htmlspecialchars($editEventArena['temp_surface'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.event_arenas.fields.warmup'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="warmup_arena_id" class="form-select">
                                <option value=""><?= htmlspecialchars(t('arenas.event_arenas.fields.warmup_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php foreach ($eventArenasByEvent as $eventId => $items): ?>
                                    <optgroup label="<?= htmlspecialchars($eventTitleMap[$eventId] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <?php foreach ($items as $item): ?>
                                            <option value="<?= (int) $item['id'] ?>" <?= !empty($editEventArena['warmup_arena_id']) && (int) $editEventArena['warmup_arena_id'] === (int) $item['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.event_arenas.fields.remarks'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="remarks" class="form-control" value="<?= htmlspecialchars($editEventArena['remarks'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars($editEventArena ? t('arenas.event_arenas.actions.update') : t('arenas.event_arenas.actions.create'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editEventArena): ?>
                            <a href="arenas.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('arenas.common.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if ($eventArenaRows): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><?= htmlspecialchars(t('arenas.event_arenas.columns.event'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('arenas.event_arenas.columns.label'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('arenas.event_arenas.columns.details'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(t('arenas.event_arenas.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventArenaRows as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['event_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($row['option']['label'] ?? ($row['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-muted small">
                                            <?php if (!empty($row['option']['summary'])): ?>
                                                <div><?= htmlspecialchars($row['option']['summary'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['option']['location'])): ?>
                                                <div><?= htmlspecialchars(t('classes.arena_badge.feature.location', ['location' => $row['option']['location']]), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="arenas.php?edit_event_arena=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(t('arenas.common.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                                <form method="post" onsubmit="return confirm(<?= json_encode(t('arenas.event_arenas.actions.confirm_delete')) ?>);">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete_event_arena">
                                                    <input type="hidden" name="event_arena_id" value="<?= (int) $row['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('arenas.common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= htmlspecialchars(t('arenas.event_arenas.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('arenas.availability.heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post" class="mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create_availability">
                    <div class="row g-2">
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.availability.fields.arena'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="arena_id" class="form-select" required>
                                <option value=""><?= htmlspecialchars(t('arenas.availability.fields.arena_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php foreach ($arenas as $arena): ?>
                                    <option value="<?= (int) $arena['id'] ?>"><?= htmlspecialchars($arena['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.availability.fields.start'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="datetime-local" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.availability.fields.end'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="datetime-local" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.availability.fields.status'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="status" class="form-select">
                                <option value="blocked"><?= htmlspecialchars(t('arenas.availability.status.blocked'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="maintenance"><?= htmlspecialchars(t('arenas.availability.status.maintenance'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="available"><?= htmlspecialchars(t('arenas.availability.status.available'), ENT_QUOTES, 'UTF-8') ?></option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.availability.fields.reason'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="reason" class="form-control" value="">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label"><?= htmlspecialchars(t('arenas.availability.fields.notes'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="notes" class="form-control" value="">
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('arenas.availability.actions.create'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </form>
                <?php if ($availability): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><?= htmlspecialchars(t('arenas.availability.columns.arena'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('arenas.availability.columns.period'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('arenas.availability.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(t('arenas.availability.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availability as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['arena_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-muted small">
                                            <div><?= htmlspecialchars(date('Y-m-d H:i', strtotime((string) $item['start_time'])), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div><?= htmlspecialchars(date('Y-m-d H:i', strtotime((string) $item['end_time'])), ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars(t('arenas.availability.status.' . ($item['status'] ?? 'blocked')), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if (!empty($item['reason'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($item['reason'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($item['notes'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($item['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <form method="post" onsubmit="return confirm(<?= json_encode(t('arenas.availability.actions.confirm_delete')) ?>);" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_availability">
                                                <input type="hidden" name="availability_id" value="<?= (int) $item['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('arenas.common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= htmlspecialchars(t('arenas.availability.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
