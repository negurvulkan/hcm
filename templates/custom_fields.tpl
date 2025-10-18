<?php
/** @var array<int, array<string, mixed>> $definitions */
/** @var array<string, string> $entityOptions */
/** @var array<string, string> $typeOptions */
/** @var array<string, string> $scopeOptions */
/** @var array<string, string> $visibilityOptions */
/** @var array<int, array<string, mixed>> $organizations */
/** @var array<int, array<string, mixed>> $tournaments */
/** @var array<int, string> $locales */
/** @var array<string, mixed>|null $editDefinition */
/** @var string $defaultLocale */

$edit = $editDefinition;
$labelForLocale = static function (?array $label, string $locale): string {
    if (!$label) {
        return '';
    }
    return (string) ($label[$locale] ?? '');
};
$helpForLocale = static function (?array $help, string $locale): string {
    if (!$help) {
        return '';
    }
    return (string) ($help[$locale] ?? '');
};
$enumValuesText = '';
if ($edit && isset($edit['enum_values']) && is_array($edit['enum_values'])) {
    $enumValuesText = implode("\n", array_map(static fn ($value): string => (string) $value, $edit['enum_values']));
}
$currentLocale = $currentLocale ?? current_locale();
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><?= htmlspecialchars(t('custom_fields.list.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <span class="badge bg-secondary"><?= count($definitions) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (!$definitions): ?>
                    <div class="p-4 text-center text-muted">
                        <?= htmlspecialchars(t('custom_fields.list.empty'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th><?= htmlspecialchars(t('custom_fields.table.entity'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('custom_fields.table.label'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('custom_fields.table.key'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('custom_fields.table.type'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('custom_fields.table.scope'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('custom_fields.table.visibility'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('custom_fields.table.version'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(t('custom_fields.table.values'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th class="text-end"><?= htmlspecialchars(t('custom_fields.table.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($definitions as $definition): ?>
                                <?php
                                $label = $definition['label'] ?? [];
                                $labelText = '';
                                if (is_array($label)) {
                                    $labelText = (string) ($label[$currentLocale] ?? reset($label) ?? '');
                                } elseif (is_string($label)) {
                                    $labelText = $label;
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($entityOptions[$definition['entity']] ?? $definition['entity'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($labelText, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><code><?= htmlspecialchars($definition['key'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td><?= htmlspecialchars($typeOptions[$definition['type']] ?? $definition['type'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($scopeOptions[$definition['scope']] ?? $definition['scope'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($visibilityOptions[$definition['visibility']] ?? $definition['visibility'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) $definition['version'] ?></td>
                                    <td>
                                        <?php if ((int) ($definition['value_count'] ?? 0) > 0): ?>
                                            <span class="badge bg-info text-dark"><?= (int) $definition['value_count'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="custom_fields.php?edit=<?= (int) $definition['id'] ?>">
                                            <?= htmlspecialchars(t('custom_fields.actions.edit'), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <form action="custom_fields.php" method="post" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="definition_id" value="<?= (int) $definition['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= htmlspecialchars(t('custom_fields.actions.confirm_delete', ['label' => $labelText, 'count' => (int) ($definition['value_count'] ?? 0)]), ENT_QUOTES, 'UTF-8') ?>');">
                                                <?= htmlspecialchars(t('custom_fields.actions.delete'), ENT_QUOTES, 'UTF-8') ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="h5 mb-0">
                    <?php if ($edit): ?>
                        <?= htmlspecialchars(t('custom_fields.form.edit_title'), ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        <?= htmlspecialchars(t('custom_fields.form.create_title'), ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </h2>
            </div>
            <div class="card-body">
                <form action="custom_fields.php" method="post" class="vstack gap-3" novalidate>
                    <?= csrf_field() ?>
                    <?php if ($edit): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="definition_id" value="<?= (int) $edit['id'] ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="create">
                    <?php endif; ?>

                    <div>
                        <label for="field-entity" class="form-label"><?= htmlspecialchars(t('custom_fields.form.entity'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select id="field-entity" name="entity" class="form-select" required>
                            <?php foreach ($entityOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $edit && $edit['entity'] === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="field-key" class="form-label"><?= htmlspecialchars(t('custom_fields.form.field_key'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" id="field-key" name="field_key" class="form-control" value="<?= $edit ? htmlspecialchars($edit['key'], ENT_QUOTES, 'UTF-8') : '' ?>" required pattern="[A-Za-z0-9_.-]+">
                        <div class="form-text"><?= htmlspecialchars(t('custom_fields.form.field_key_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label for="field-type" class="form-label"><?= htmlspecialchars(t('custom_fields.form.type'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select id="field-type" name="type" class="form-select">
                                <?php foreach ($typeOptions as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $edit && $edit['type'] === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="field-visibility" class="form-label"><?= htmlspecialchars(t('custom_fields.form.visibility'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select id="field-visibility" name="visibility" class="form-select">
                                <?php foreach ($visibilityOptions as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $edit && $edit['visibility'] === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="field-scope" class="form-label"><?= htmlspecialchars(t('custom_fields.form.scope'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select id="field-scope" name="scope" class="form-select">
                            <?php foreach ($scopeOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $edit && $edit['scope'] === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="scope-dependent" data-scope="organization">
                        <label for="field-organization" class="form-label"><?= htmlspecialchars(t('custom_fields.form.organization'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select id="field-organization" name="organization_id" class="form-select">
                            <option value=""><?= htmlspecialchars(t('custom_fields.form.organization_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($organizations as $organization): ?>
                                <option value="<?= (int) $organization['id'] ?>" <?= $edit && $edit['organization_id'] === (int) $organization['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($organization['display_name'] ?? ('#' . $organization['id']), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="scope-dependent" data-scope="tournament">
                        <label for="field-tournament" class="form-label"><?= htmlspecialchars(t('custom_fields.form.tournament'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select id="field-tournament" name="tournament_id" class="form-select">
                            <option value=""><?= htmlspecialchars(t('custom_fields.form.tournament_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($tournaments as $tournament): ?>
                                <?php
                                $title = $tournament['title'] ?? ('#' . $tournament['id']);
                                $startDate = $tournament['start_date'] ?? null;
                                $label = $title;
                                if ($startDate) {
                                    $label .= ' (' . $startDate . ')';
                                }
                                ?>
                                <option value="<?= (int) $tournament['id'] ?>" <?= $edit && $edit['tournament_id'] === (int) $tournament['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="vstack gap-2">
                        <div class="fw-semibold"><?= htmlspecialchars(t('custom_fields.form.labels'), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php foreach ($locales as $locale): ?>
                            <div>
                                <label for="label-<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>" class="form-label small mb-1 text-uppercase"><?= htmlspecialchars(strtoupper($locale), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" id="label-<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>" name="label[<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>]" class="form-control" value="<?= $edit ? htmlspecialchars($labelForLocale($edit['label'] ?? [], $locale), ENT_QUOTES, 'UTF-8') : '' ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="vstack gap-2">
                        <div class="fw-semibold"><?= htmlspecialchars(t('custom_fields.form.help'), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php foreach ($locales as $locale): ?>
                            <div>
                                <label for="help-<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>" class="form-label small mb-1 text-uppercase"><?= htmlspecialchars(strtoupper($locale), ENT_QUOTES, 'UTF-8') ?></label>
                                <textarea id="help-<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>" name="help[<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>]" class="form-control" rows="2"><?= $edit ? htmlspecialchars($helpForLocale($edit['help'] ?? [], $locale), ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="field-required" name="required" <?= $edit && $edit['required'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="field-required"><?= htmlspecialchars(t('custom_fields.form.required'), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="field-unique" name="is_unique" <?= $edit && $edit['is_unique'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="field-unique"><?= htmlspecialchars(t('custom_fields.form.unique'), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="field-sensitive" name="is_sensitive" <?= $edit && $edit['is_sensitive'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="field-sensitive"><?= htmlspecialchars(t('custom_fields.form.sensitive'), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="field-regex" class="form-label"><?= htmlspecialchars(t('custom_fields.form.regex'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" id="field-regex" name="regex_pattern" class="form-control" value="<?= $edit && $edit['regex'] ? htmlspecialchars($edit['regex'], ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="/^[0-9]+$/">
                        </div>
                        <div class="col-md-3">
                            <label for="field-min" class="form-label"><?= htmlspecialchars(t('custom_fields.form.min'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" id="field-min" name="min_value" class="form-control" value="<?= $edit && $edit['min'] !== null ? htmlspecialchars((string) $edit['min'], ENT_QUOTES, 'UTF-8') : '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="field-max" class="form-label"><?= htmlspecialchars(t('custom_fields.form.max'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" id="field-max" name="max_value" class="form-control" value="<?= $edit && $edit['max'] !== null ? htmlspecialchars((string) $edit['max'], ENT_QUOTES, 'UTF-8') : '' ?>">
                        </div>
                    </div>

                    <div>
                        <label for="field-enum" class="form-label"><?= htmlspecialchars(t('custom_fields.form.enum_values'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea id="field-enum" name="enum_values" class="form-control" rows="3" placeholder="<?= htmlspecialchars(t('custom_fields.form.enum_hint'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($enumValuesText, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="field-profile" class="form-label"><?= htmlspecialchars(t('custom_fields.form.profile_key'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" id="field-profile" name="profile_key" class="form-control" value="<?= $edit && $edit['profile_key'] ? htmlspecialchars($edit['profile_key'], ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="profile.default">
                        </div>
                        <div class="col-md-3">
                            <label for="field-version" class="form-label"><?= htmlspecialchars(t('custom_fields.form.version'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="number" id="field-version" name="version" class="form-control" value="<?= $edit ? (int) $edit['version'] : 1 ?>" min="1">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="field-valid-from" class="form-label"><?= htmlspecialchars(t('custom_fields.form.valid_from'), ENT_QUOTES, 'UTF-8') ?></label>
                            <?php
                            $validFromValue = '';
                            if ($edit && !empty($edit['valid_from'])) {
                                $timestamp = strtotime($edit['valid_from']);
                                if ($timestamp) {
                                    $validFromValue = date('Y-m-d\TH:i', $timestamp);
                                }
                            }
                            ?>
                            <input type="datetime-local" id="field-valid-from" name="valid_from" class="form-control" value="<?= htmlspecialchars($validFromValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="field-valid-to" class="form-label"><?= htmlspecialchars(t('custom_fields.form.valid_to'), ENT_QUOTES, 'UTF-8') ?></label>
                            <?php
                            $validToValue = '';
                            if ($edit && !empty($edit['valid_to'])) {
                                $timestamp = strtotime($edit['valid_to']);
                                if ($timestamp) {
                                    $validToValue = date('Y-m-d\TH:i', $timestamp);
                                }
                            }
                            ?>
                            <input type="datetime-local" id="field-valid-to" name="valid_to" class="form-control" value="<?= htmlspecialchars($validToValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?php if ($edit): ?>
                                <?= htmlspecialchars(t('custom_fields.form.save_changes'), ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                <?= htmlspecialchars(t('custom_fields.form.create_button'), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </button>
                        <?php if ($edit): ?>
                            <a href="custom_fields.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('custom_fields.form.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const scopeSelect = document.getElementById('field-scope');
        const dependentSections = document.querySelectorAll('.scope-dependent');

        const toggleSections = () => {
            const currentScope = scopeSelect?.value || 'global';
            dependentSections.forEach((section) => {
                if (!(section instanceof HTMLElement)) {
                    return;
                }
                const scope = section.dataset.scope;
                if (scope === currentScope) {
                    section.style.display = '';
                } else {
                    section.style.display = 'none';
                }
            });
        };

        scopeSelect?.addEventListener('change', toggleSections);
        toggleSections();
    });
</script>
