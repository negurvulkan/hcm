<?php
/**
 * @var array<int, array<string, mixed>> $customFieldFormData
 * @var bool|null $customFieldShowEmpty
 */
$fields = $customFieldFormData ?? [];
$showEmpty = !empty($customFieldShowEmpty);
?>
<?php if ($fields): ?>
    <fieldset class="mb-3">
        <legend class="fs-6 mb-2"><?= htmlspecialchars(t('custom_fields.form.values_title'), ENT_QUOTES, 'UTF-8') ?></legend>
        <div class="vstack gap-3">
            <?php foreach ($fields as $field): ?>
                <?php
                $inputAttributes = '';
                foreach (($field['attributes'] ?? []) as $attrKey => $attrValue) {
                    $inputAttributes .= ' ' . htmlspecialchars((string) $attrKey, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8') . '"';
                }
                $isRequired = !empty($field['required']);
                $fieldId = (string) $field['id'];
                $fieldName = (string) $field['name'];
                $currentValue = (string) ($field['value'] ?? '');
                ?>
                <div>
                    <label class="form-label" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) $field['label'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($isRequired): ?><span class="text-danger">*</span><?php endif; ?>
                    </label>
                    <?php if ($field['type'] === 'select'): ?>
                        <select id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                                name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"
                                class="form-select"
                                <?= $isRequired ? 'required' : '' ?>>
                            <?php foreach ($field['options'] as $option): ?>
                                <?php $selected = ($currentValue === (string) $option['value']) ? 'selected' : ''; ?>
                                <option value="<?= htmlspecialchars((string) $option['value'], ENT_QUOTES, 'UTF-8') ?>" <?= $selected ?>>
                                    <?= htmlspecialchars((string) $option['label'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <?php $rows = $field['definition_type'] === 'json' ? 6 : 3; ?>
                        <textarea id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                                  name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"
                                  class="form-control"
                                  rows="<?= $rows ?>"
                                  <?= $isRequired ? 'required' : '' ?>><?= htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php else: ?>
                        <input id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                               name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"
                               type="<?= $field['type'] === 'number' ? 'number' : 'text' ?>"
                               class="form-control"
                               value="<?= htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8') ?>"
                               <?= $isRequired ? 'required' : '' ?><?= $inputAttributes ?>>
                    <?php endif; ?>
                    <?php if (!empty($field['help'])): ?>
                        <small class="form-text text-muted"><?= htmlspecialchars((string) $field['help'], ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </fieldset>
<?php elseif ($showEmpty): ?>
    <p class="text-muted small mb-3"><?= htmlspecialchars(t('custom_fields.form.values_empty'), ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
