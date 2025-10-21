<?php
/** @var array $sponsors */
/** @var array|null $formSponsor */
/** @var bool $isEditing */
/** @var array $statusOptions */
/** @var array $filterStatuses */
/** @var string $statusFilter */
/** @var array $typeOptions */
/** @var array $tierOptions */
/** @var array $valueTypeOptions */
/** @var array $events */
/** @var int|null $eventFilter */
/** @var string $redirectStatus */
/** @var int|null $redirectEvent */
?>
<?php
$form = $formSponsor ?? [];
$defaults = [
    'status' => 'active',
    'type' => 'company',
    'tier' => 'partner',
    'value_type' => 'cash',
    'priority' => 0,
    'show_on_website' => true,
    'show_on_signage' => true,
    'show_in_program' => true,
    'invoice_required' => false,
];
foreach ($defaults as $key => $value) {
    if (!array_key_exists($key, $form) || $form[$key] === null) {
        $form[$key] = $value;
    }
}
$bool = static fn (string $key, bool $default = false): bool => isset($form[$key]) ? (bool) $form[$key] : $default;
$logoVariantsJson = isset($form['logo_variants']) && is_array($form['logo_variants']) && $form['logo_variants'] ? json_encode($form['logo_variants'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
$mediaPackageJson = isset($form['media_package']) && is_array($form['media_package']) && $form['media_package'] ? json_encode($form['media_package'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
$documentsJson = isset($form['documents']) && is_array($form['documents']) && $form['documents'] ? json_encode($form['documents'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
$historyJson = isset($form['sponsorship_history']) && is_array($form['sponsorship_history']) && $form['sponsorship_history'] ? json_encode($form['sponsorship_history'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
$statsJson = isset($form['display_stats']) && is_array($form['display_stats']) && $form['display_stats'] ? json_encode($form['display_stats'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
?>
<div class="mb-4">
    <p class="text-muted mb-0"><?= htmlspecialchars(t('sponsors.description'), ENT_QUOTES, 'UTF-8') ?></p>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label class="form-label" for="filter-status"><?= htmlspecialchars(t('sponsors.filters.status'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="status" id="filter-status">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>><?= htmlspecialchars(t('sponsors.filters.status_all'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php foreach ($filterStatuses as $status): ?>
                        <?php if ($status === 'all') { continue; } ?>
                        <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= htmlspecialchars(t('sponsors.status_labels.' . $status), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label" for="filter-event"><?= htmlspecialchars(t('sponsors.filters.event'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="event" id="filter-event">
                    <option value=""><?= htmlspecialchars(t('sponsors.filters.event_all'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php foreach ($events as $event): ?>
                        <?php $optionValue = (int) ($event['id'] ?? 0); ?>
                        <option value="<?= $optionValue ?>" <?= $eventFilter !== null && $eventFilter === $optionValue ? 'selected' : '' ?>>
                            <?= htmlspecialchars(trim(($event['title'] ?? '') . ($event['start_date'] ? ' · ' . date('d.m.Y', strtotime($event['start_date'])) : '')), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-sm-12">
                <button class="btn btn-outline-primary w-100" type="submit"><?= htmlspecialchars(t('sponsors.filters.apply'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
    </div>
</div>
<div class="row g-4">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t($isEditing ? 'sponsors.form.edit_title' : 'sponsors.form.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'create' ?>">
                    <input type="hidden" name="sponsor_id" value="<?= $isEditing ? (int) $form['id'] : '' ?>">
                    <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($redirectStatus, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="redirect_event" value="<?= $redirectEvent !== null ? (int) $redirectEvent : '' ?>">

                    <div class="mb-4 pb-2 border-bottom">
                        <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('sponsors.form.sections.identity'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.name'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($form['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.display_name'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="display_name" class="form-control" value="<?= htmlspecialchars($form['display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.type'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select name="type" class="form-select">
                                    <?php foreach ($typeOptions as $type): ?>
                                        <option value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" <?= ($form['type'] ?? 'company') === $type ? 'selected' : '' ?>><?= htmlspecialchars(t('sponsors.types.' . $type), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.status'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select name="status" class="form-select">
                                    <?php foreach ($statusOptions as $status): ?>
                                        <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= ($form['status'] ?? 'active') === $status ? 'selected' : '' ?>><?= htmlspecialchars(t('sponsors.status_labels.' . $status), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.tier'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select name="tier" class="form-select">
                                    <?php foreach ($tierOptions as $tier): ?>
                                        <option value="<?= htmlspecialchars($tier, ENT_QUOTES, 'UTF-8') ?>" <?= ($form['tier'] ?? 'partner') === $tier ? 'selected' : '' ?>><?= htmlspecialchars(t('sponsors.tiers.' . $tier), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.priority'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="number" name="priority" class="form-control" value="<?= htmlspecialchars((string) ($form['priority'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 pb-2 border-bottom">
                        <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('sponsors.form.sections.contact'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.contact_person'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($form['contact_person'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.email'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($form['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.phone'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($form['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.address'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($form['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">&nbsp;</div>
                        </div>
                    </div>

                    <div class="mb-4 pb-2 border-bottom">
                        <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('sponsors.form.sections.contract'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.contract_start'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="date" name="contract_start" class="form-control" value="<?= htmlspecialchars($form['contract_start'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.contract_end'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="date" name="contract_end" class="form-control" value="<?= htmlspecialchars($form['contract_end'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.value'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" name="value" class="form-control" value="<?= htmlspecialchars($form['value'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.value_type'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select name="value_type" class="form-select">
                                    <?php foreach ($valueTypeOptions as $valueType): ?>
                                        <option value="<?= htmlspecialchars($valueType, ENT_QUOTES, 'UTF-8') ?>" <?= ($form['value_type'] ?? 'cash') === $valueType ? 'selected' : '' ?>><?= htmlspecialchars(t('sponsors.value_types.' . $valueType), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="invoice-required" name="invoice_required" value="1" <?= $bool('invoice_required') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="invoice-required"><?= htmlspecialchars(t('sponsors.form.fields.invoice_required'), ENT_QUOTES, 'UTF-8') ?></label>
                        </div>
                        <div class="mt-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.invoice_number'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="invoice_number" class="form-control" value="<?= htmlspecialchars($form['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="mb-4 pb-2 border-bottom">
                        <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('sponsors.form.sections.media'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.logo_path'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="logo_path" class="form-control" value="<?= htmlspecialchars($form['logo_path'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.website'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($form['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="https://">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.description_short'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea name="description_short" class="form-control" rows="2"><?= htmlspecialchars($form['description_short'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.description_long'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea name="description_long" class="form-control" rows="3"><?= htmlspecialchars($form['description_long'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.tagline'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="tagline" class="form-control" value="<?= htmlspecialchars($form['tagline'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.color_primary'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="color_primary" class="form-control" value="<?= htmlspecialchars($form['color_primary'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="#0050FF">
                        </div>
                    </div>

                    <div class="mb-4 pb-2 border-bottom">
                        <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('sponsors.form.sections.visibility'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="show-website" name="show_on_website" value="1" <?= $bool('show_on_website', true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="show-website"><?= htmlspecialchars(t('sponsors.form.fields.show_on_website'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="show-program" name="show_in_program" value="1" <?= $bool('show_in_program', true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="show-program"><?= htmlspecialchars(t('sponsors.form.fields.show_in_program'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="show-signage" name="show_on_signage" value="1" <?= $bool('show_on_signage', true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="show-signage"><?= htmlspecialchars(t('sponsors.form.fields.show_on_signage'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.display_duration'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="number" name="display_duration" class="form-control" value="<?= htmlspecialchars($form['display_duration'] ?? '', ENT_QUOTES, 'UTF-8') ?>" min="0" placeholder="30">
                                </div>
                                <div class="mt-3">
                                    <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.display_frequency'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="number" name="display_frequency" class="form-control" value="<?= htmlspecialchars($form['display_frequency'] ?? '', ENT_QUOTES, 'UTF-8') ?>" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.overlay_template'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" name="overlay_template" class="form-control" value="<?= htmlspecialchars($form['overlay_template'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.linked_event_id'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select name="linked_event_id" class="form-select">
                                    <option value=""><?= htmlspecialchars(t('sponsors.form.placeholders.event'), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php foreach ($events as $event): ?>
                                        <?php $optionValue = (int) ($event['id'] ?? 0); ?>
                                        <option value="<?= $optionValue ?>" <?= isset($form['linked_event_id']) && (int) $form['linked_event_id'] === $optionValue ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($event['title'] ?? ('#' . $optionValue), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars(t('sponsors.form.sections.documents'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.contract_file'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="contract_file" class="form-control" value="<?= htmlspecialchars($form['contract_file'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.notes_internal'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea name="notes_internal" class="form-control" rows="2"><?= htmlspecialchars($form['notes_internal'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.logo_variants'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea name="logo_variants" class="form-control" rows="2" placeholder="<?= htmlspecialchars(t('sponsors.form.placeholders.json_array'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($logoVariantsJson, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.media_package'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea name="media_package" class="form-control" rows="2" placeholder="<?= htmlspecialchars(t('sponsors.form.placeholders.json_array'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($mediaPackageJson, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.documents'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea name="documents" class="form-control" rows="2" placeholder="<?= htmlspecialchars(t('sponsors.form.placeholders.json_array'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($documentsJson, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.sponsorship_history'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea name="sponsorship_history" class="form-control" rows="2" placeholder="<?= htmlspecialchars(t('sponsors.form.placeholders.json_array'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($historyJson, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.display_stats'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea name="display_stats" class="form-control" rows="2" placeholder="<?= htmlspecialchars(t('sponsors.form.placeholders.json_object'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statsJson, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.last_contacted'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="date" name="last_contacted" class="form-control" value="<?= htmlspecialchars($form['last_contacted'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= htmlspecialchars(t('sponsors.form.fields.follow_up_date'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="date" name="follow_up_date" class="form-control" value="<?= htmlspecialchars($form['follow_up_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" type="submit"><?= htmlspecialchars(t('sponsors.form.actions.save'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($isEditing): ?>
                            <a href="sponsors.php" class="btn btn-outline-secondary">&larr; <?= htmlspecialchars(t('sponsors.form.actions.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('sponsors.table.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><?= htmlspecialchars(t('sponsors.table.columns.name'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('sponsors.table.columns.tier'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('sponsors.table.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('sponsors.table.columns.contact'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('sponsors.table.columns.visibility'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th class="text-end">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sponsors as $sponsor): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($sponsor['logo_path'])): ?>
                                            <img src="<?= htmlspecialchars($sponsor['logo_path'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:36px;height:36px;object-fit:contain;" class="rounded border">
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($sponsor['display_name'] ?: $sponsor['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if (!empty($sponsor['tagline'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($sponsor['tagline'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php elseif (!empty($sponsor['description_short'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($sponsor['description_short'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($sponsor['website'])): ?>
                                                <a href="<?= htmlspecialchars($sponsor['website'], ENT_QUOTES, 'UTF-8') ?>" class="small" target="_blank" rel="noopener"><?= htmlspecialchars(t('sponsors.table.labels.website'), ENT_QUOTES, 'UTF-8') ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars(t('sponsors.tiers.' . $sponsor['tier']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge bg-<?= $sponsor['status'] === 'active' ? 'success' : ($sponsor['status'] === 'inactive' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars(t('sponsors.status_labels.' . $sponsor['status']), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($sponsor['contact_person'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="small"><a href="mailto:<?= htmlspecialchars($sponsor['email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sponsor['email'], ENT_QUOTES, 'UTF-8') ?></a></div>
                                    <div class="small text-muted"><?= htmlspecialchars($sponsor['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php if (!empty($sponsor['show_on_signage'])): ?><span class="badge rounded-pill bg-primary-subtle text-primary"><?= htmlspecialchars(t('sponsors.table.badges.signage'), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                        <?php if (!empty($sponsor['show_on_website'])): ?><span class="badge rounded-pill bg-success-subtle text-success"><?= htmlspecialchars(t('sponsors.table.badges.website'), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                        <?php if (!empty($sponsor['show_in_program'])): ?><span class="badge rounded-pill bg-info-subtle text-info"><?= htmlspecialchars(t('sponsors.table.badges.program'), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a class="btn btn-outline-secondary" href="sponsors.php?edit=<?= (int) $sponsor['id'] ?>&status=<?= htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8') ?><?= $eventFilter !== null ? '&event=' . (int) $eventFilter : '' ?>"><?= htmlspecialchars(t('sponsors.table.actions.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('sponsors.table.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="sponsor_id" value="<?= (int) $sponsor['id'] ?>">
                                            <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="redirect_event" value="<?= $eventFilter !== null ? (int) $eventFilter : '' ?>">
                                            <button class="btn btn-outline-danger" type="submit"><?= htmlspecialchars(t('sponsors.table.actions.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$sponsors): ?>
                            <tr>
                                <td colspan="6" class="text-muted text-center py-4"><?= htmlspecialchars(t('sponsors.table.empty'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
