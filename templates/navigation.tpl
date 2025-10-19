<?php
/**
 * @var string $role
 * @var array $roles
 * @var array<int, array<string, mixed>> $groups
 * @var array<int, array<string, mixed>> $menuItems
 * @var array<int, string> $locales
 * @var string $token
 */

$currentLocale = current_locale();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0"><?= htmlspecialchars(t('navigation.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0"><?= htmlspecialchars(t('navigation.subheading'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <form method="get" class="d-inline-flex align-items-center gap-2">
        <label for="roleSelect" class="form-label mb-0 small text-uppercase text-muted"><?= htmlspecialchars(t('navigation.labels.role'), ENT_QUOTES, 'UTF-8') ?></label>
        <select id="roleSelect" name="role" class="form-select" onchange="this.form.submit()">
            <?php foreach ($roles as $option): ?>
                <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= $option === $role ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 text-uppercase"><?= htmlspecialchars(t('navigation.sections.groups'), ENT_QUOTES, 'UTF-8') ?></h2>
                <span class="badge bg-secondary"><?= count($groups) ?></span>
            </div>
            <div class="card-body">
                <?php if ($groups): ?>
                    <form method="post" class="mb-4">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="role" value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th><?= htmlspecialchars(t('navigation.labels.group_name'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th style="width: 110px;"><?= htmlspecialchars(t('navigation.labels.position'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(t('navigation.labels.default_key'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th class="text-end" style="width: 60px;"><?= htmlspecialchars(t('navigation.labels.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groups as $group): ?>
                                        <?php
                                        $translations = $group['label_translations'] ?? [];
                                        $defaultLabel = $group['label_key'] ? t($group['label_key']) : '';
                                        ?>
                                        <tr>
                                            <td>
                                                <?php foreach ($locales as $locale): ?>
                                                    <div class="mb-2">
                                                        <label class="form-label small text-uppercase text-muted mb-1"><?= htmlspecialchars(strtoupper($locale), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="text" class="form-control form-control-sm" name="groups[<?= (int) $group['id'] ?>][label][<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($translations[$locale] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm" name="groups[<?= (int) $group['id'] ?>][position]" value="<?= (int) ($group['position'] ?? 0) ?>">
                                            </td>
                                            <td>
                                                <?php if (!empty($group['label_key'])): ?>
                                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($group['label_key'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small"><?= htmlspecialchars(t('navigation.hints.custom_group'), ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <button type="submit" name="delete_group" value="<?= (int) $group['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= htmlspecialchars(t('navigation.confirm.delete_group'), ENT_QUOTES, 'UTF-8') ?>');">&times;</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="action" value="update_groups" class="btn btn-primary"><?= htmlspecialchars(t('navigation.actions.save_groups'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-muted"><?= htmlspecialchars(t('navigation.hints.no_groups'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <hr class="my-4">
                <h3 class="h6 text-uppercase mb-3"><?= htmlspecialchars(t('navigation.sections.add_group'), ENT_QUOTES, 'UTF-8') ?></h3>
                <form method="post" class="row g-3">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="role" value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="create_group">
                    <?php foreach ($locales as $locale): ?>
                        <div class="col-12">
                            <label class="form-label small text-uppercase text-muted"><?= htmlspecialchars(t('navigation.labels.group_label', ['locale' => strtoupper($locale)]), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="new_group[label][<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>]" class="form-control" placeholder="<?= htmlspecialchars(t('navigation.placeholders.group_label'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    <?php endforeach; ?>
                    <div class="col-sm-6">
                        <label class="form-label small text-uppercase text-muted"><?= htmlspecialchars(t('navigation.labels.position'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="number" name="new_group[position]" class="form-control" value="0">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-primary"><?= htmlspecialchars(t('navigation.actions.add_group'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 text-uppercase"><?= htmlspecialchars(t('navigation.sections.items'), ENT_QUOTES, 'UTF-8') ?></h2>
                <span class="badge bg-secondary"><?= count($menuItems) ?></span>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="role" value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 80px;"><?= htmlspecialchars(t('navigation.labels.enabled'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('navigation.labels.item'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th style="width: 200px;"><?= htmlspecialchars(t('navigation.labels.target'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th style="width: 220px;"><?= htmlspecialchars(t('navigation.labels.group'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th style="width: 150px;"><?= htmlspecialchars(t('navigation.labels.variant'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th style="width: 110px;"><?= htmlspecialchars(t('navigation.labels.position'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menuItems as $item): ?>
                                    <?php
                                    $label = t($item['label_key']);
                                    $tooltip = $item['tooltip_key'] ? t($item['tooltip_key']) : $label;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="item-<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>" name="items[<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>][enabled]" value="1" <?= $item['enabled'] ? 'checked' : '' ?>>
                                                <label class="form-check-label visually-hidden" for="item-<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></strong>
                                            <div class="text-muted small"><?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="items[<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>][target]" value="<?= htmlspecialchars($item['target'] ?? $item['path'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8') ?>">
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="items[<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>][group_id]" <?= $groups ? '' : 'disabled' ?>>
                                                <option value=""><?= htmlspecialchars(t('navigation.labels.choose_group'), ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php foreach ($groups as $group): ?>
                                                    <?php
                                                    $groupTranslations = $group['label_translations'] ?? [];
                                                    if (is_array($groupTranslations) && !empty($groupTranslations[$currentLocale])) {
                                                        $groupLabel = $groupTranslations[$currentLocale];
                                                    } elseif (!empty($group['label_key'])) {
                                                        $groupLabel = t($group['label_key']);
                                                    } else {
                                                        $groupLabel = t('navigation.placeholders.group_fallback');
                                                    }
                                                    ?>
                                                    <option value="<?= (int) $group['id'] ?>" <?= (int) ($item['group_id'] ?? 0) === (int) $group['id'] ? 'selected' : '' ?>><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="items[<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>][variant]">
                                                <option value="primary" <?= ($item['variant'] ?? 'primary') === 'primary' ? 'selected' : '' ?>><?= htmlspecialchars(t('navigation.labels.variant_primary'), ENT_QUOTES, 'UTF-8') ?></option>
                                                <option value="secondary" <?= ($item['variant'] ?? 'primary') === 'secondary' ? 'selected' : '' ?>><?= htmlspecialchars(t('navigation.labels.variant_secondary'), ENT_QUOTES, 'UTF-8') ?></option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" name="items[<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>][position]" value="<?= (int) ($item['position'] ?? 0) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!$groups): ?>
                        <div class="alert alert-warning"><?= htmlspecialchars(t('navigation.hints.groups_required'), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div class="d-flex flex-wrap justify-content-between gap-2 mt-3">
                        <button type="submit" name="action" value="save_items" class="btn btn-primary" <?= $groups ? '' : 'disabled' ?>><?= htmlspecialchars(t('navigation.actions.save_items'), ENT_QUOTES, 'UTF-8') ?></button>
                        <button type="submit" name="action" value="reset_layout" class="btn btn-outline-danger" onclick="return confirm('<?= htmlspecialchars(t('navigation.confirm.reset'), ENT_QUOTES, 'UTF-8') ?>');"><?= htmlspecialchars(t('navigation.actions.reset'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
