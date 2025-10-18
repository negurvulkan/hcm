<?php

declare(strict_types=1);

namespace App\CustomFields;

use function current_locale;
use function json_encode;
use function t;

class CustomFieldManager
{
    private CustomFieldRepository $repository;

    private string $entity;

    /**
     * @var array{organization_id?: int|null, tournament_id?: int|null, profiles?: array<int, string>|string|null}
     */
    private array $context;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $definitions = null;

    public function __construct(CustomFieldRepository $repository, string $entity, array $context = [])
    {
        $this->repository = $repository;
        $this->entity = $entity;
        $this->context = $context;
    }

    /**
     * @return array{organization_id?: int|null, tournament_id?: int|null, profiles?: array<int, string>|string|null}
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        if ($this->definitions === null) {
            $this->definitions = $this->repository->definitionsFor($this->entity, $this->context);
        }

        return $this->definitions;
    }

    /**
     * @param array<string, array<string, mixed>> $values
     * @return array<int, array<string, mixed>>
     */
    public function formFields(array $values = []): array
    {
        $fields = [];
        foreach ($this->definitions() as $definition) {
            $key = (string) $definition['key'];
            $value = $values[$key]['value'] ?? null;
            $fields[] = $this->buildFieldDescriptor($definition, $value);
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{values: array<string, mixed>, errors: array<int, string>}
     */
    public function validate(array $input): array
    {
        $values = [];
        $errors = [];

        foreach ($this->definitions() as $definition) {
            $key = (string) $definition['key'];
            $raw = $input[$key] ?? null;
            $result = $this->validateField($definition, $raw);
            if ($result['error'] !== null) {
                $errors[] = $result['error'];
            }
            $values[$key] = $result['value'];
        }

        return ['values' => $values, 'errors' => $errors];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listColumns(): array
    {
        $columns = [];
        foreach ($this->definitions() as $definition) {
            if (!in_array($definition['visibility'], ['internal', 'public'], true)) {
                continue;
            }
            $columns[] = [
                'key' => (string) $definition['key'],
                'label' => $this->resolveLabel($definition['label']) ?? (string) $definition['key'],
                'definition' => $definition,
            ];
        }

        return $columns;
    }

    /**
     * @param array<string, array<string, mixed>> $values
     * @return array<string, string>
     */
    public function formatListValues(array $values): array
    {
        $formatted = [];
        foreach ($this->listColumns() as $column) {
            $key = (string) $column['key'];
            $definition = $column['definition'];
            $value = $values[$key]['value'] ?? null;
            $formatted[$key] = $this->formatValue($definition, $value);
        }

        return $formatted;
    }

    /**
     * @param array<string, array<string, mixed>> $values
     * @return array<int, array<string, mixed>>
     */
    public function entityInfoFields(array $values): array
    {
        $fields = [];
        foreach ($this->definitions() as $definition) {
            if (!in_array($definition['visibility'], ['internal', 'public'], true)) {
                continue;
            }

            $key = (string) $definition['key'];
            $rawValue = $values[$key]['value'] ?? null;
            if ($rawValue === null || $rawValue === '') {
                continue;
            }

            $label = $this->resolveLabel($definition['label']) ?? $key;
            $formatted = $this->formatEntityInfoValue($definition, $rawValue);
            if ($formatted === '' || $formatted === '–') {
                continue;
            }

            $field = [
                'label' => $label,
                'value' => $formatted,
            ];

            if ($this->isMultilineType((string) $definition['type'])) {
                $field['multiline'] = true;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $definition
     * @param mixed $value
     */
    private function buildFieldDescriptor(array $definition, $value): array
    {
        $id = 'custom-field-' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $definition['key']);
        $type = (string) $definition['type'];
        $hasEnum = is_array($definition['enum_values']) && $definition['enum_values'] !== [];

        if ($hasEnum) {
            $inputType = 'select';
        } else {
            $inputType = match ($type) {
                'textarea', 'json' => 'textarea',
                'int', 'float' => 'number',
                'bool' => 'select',
                default => 'text',
            };
        }

        $descriptor = [
            'key' => (string) $definition['key'],
            'id' => $id,
            'name' => 'custom_fields[' . $definition['key'] . ']',
            'required' => (bool) $definition['required'],
            'label' => $this->resolveLabel($definition['label']) ?? (string) $definition['key'],
            'help' => $this->resolveLabel($definition['help']),
            'type' => $inputType,
            'definition_type' => $type,
            'value' => $this->prepareValueForForm($definition, $value),
            'options' => $hasEnum ? $this->prepareEnumOptions($definition) : $this->optionsForType($definition),
            'attributes' => $this->attributesForType($definition),
        ];

        return $descriptor;
    }

    /**
     * @param array<string, mixed> $definition
     * @param mixed $value
     */
    private function prepareValueForForm(array $definition, $value): mixed
    {
        $type = (string) $definition['type'];
        if (is_array($definition['enum_values']) && $definition['enum_values'] !== []) {
            return $value === null ? '' : (string) $value;
        }

        return match ($type) {
            'int', 'float' => $value === null ? '' : (string) $value,
            'bool' => $value === null ? '' : ((bool) $value ? '1' : '0'),
            'json' => $value === null ? '' : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            default => $value === null ? '' : (string) $value,
        };
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<int, array{value: string, label: string}>
     */
    private function prepareEnumOptions(array $definition): array
    {
        $options = [];
        if (!$definition['required']) {
            $options[] = ['value' => '', 'label' => t('custom_fields.fields.placeholder')];
        }

        $values = is_array($definition['enum_values']) ? $definition['enum_values'] : [];
        foreach ($values as $option) {
            $options[] = ['value' => (string) $option, 'label' => (string) $option];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<int, array{value: string, label: string}>
     */
    private function optionsForType(array $definition): array
    {
        if ((string) $definition['type'] !== 'bool') {
            return [];
        }

        $options = [];
        if (!$definition['required']) {
            $options[] = ['value' => '', 'label' => t('custom_fields.fields.placeholder')];
        }

        $options[] = ['value' => '1', 'label' => t('custom_fields.fields.yes')];
        $options[] = ['value' => '0', 'label' => t('custom_fields.fields.no')];

        return $options;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function attributesForType(array $definition): array
    {
        $attributes = [];
        $type = (string) $definition['type'];
        if ($type === 'float') {
            $attributes['step'] = 'any';
        }
        if ($type === 'int' || $type === 'float') {
            if ($definition['min'] !== null && $definition['min'] !== '') {
                $attributes['min'] = (string) $definition['min'];
            }
            if ($definition['max'] !== null && $definition['max'] !== '') {
                $attributes['max'] = (string) $definition['max'];
            }
        }

        return $attributes;
    }

    /**
     * @param array<string, mixed> $definition
     * @param mixed $raw
     * @return array{value: mixed, error: string|null}
     */
    private function validateField(array $definition, $raw): array
    {
        $type = (string) $definition['type'];
        $label = $this->resolveLabel($definition['label']) ?? (string) $definition['key'];
        $regex = $definition['regex'] ?? null;
        $enumValues = is_array($definition['enum_values']) ? $definition['enum_values'] : null;

        if (is_string($raw)) {
            $raw = trim($raw);
        }

        if ($enumValues !== null && $enumValues !== []) {
            $value = $raw === null || $raw === '' ? null : (string) $raw;
            if ($value === null) {
                if ($definition['required']) {
                    return ['value' => null, 'error' => t('custom_fields.validation.field_required', ['label' => $label])];
                }
                return ['value' => null, 'error' => null];
            }
            if (!in_array($value, $enumValues, true)) {
                return ['value' => null, 'error' => t('custom_fields.validation.field_enum', ['label' => $label])];
            }
            return ['value' => $value, 'error' => null];
        }

        switch ($type) {
            case 'int':
                $value = $this->validateInteger($raw, $definition, $label);
                break;
            case 'float':
                $value = $this->validateFloat($raw, $definition, $label);
                break;
            case 'bool':
                $value = $this->validateBool($raw, $definition, $label);
                break;
            case 'json':
                $value = $this->validateJson($raw, $definition, $label);
                break;
            default:
                $value = $this->validateString($raw, $definition, $label);
                break;
        }

        if ($value['error'] !== null) {
            return $value;
        }

        $normalized = $value['value'];
        if ($regex !== null && $regex !== '' && $normalized !== null) {
            $subject = is_scalar($normalized) ? (string) $normalized : null;
            if ($subject !== null && !preg_match($regex, $subject)) {
                return ['value' => null, 'error' => t('custom_fields.validation.field_invalid', ['label' => $label])];
            }
        }

        return $value;
    }

    /**
     * @param mixed $raw
     * @param array<string, mixed> $definition
     * @return array{value: int|null, error: string|null}
     */
    private function validateInteger($raw, array $definition, string $label): array
    {
        if ($raw === null || $raw === '') {
            if ($definition['required']) {
                return ['value' => null, 'error' => t('custom_fields.validation.field_required', ['label' => $label])];
            }
            return ['value' => null, 'error' => null];
        }

        if (!is_numeric($raw) || (string) (int) $raw !== (string) $raw) {
            if (is_string($raw) && preg_match('/^-?\d+$/', $raw)) {
                $raw = (int) $raw;
            } else {
                return ['value' => null, 'error' => t('custom_fields.validation.field_invalid', ['label' => $label])];
            }
        }

        $intValue = (int) $raw;
        if ($definition['min'] !== null && $intValue < (int) $definition['min']) {
            return ['value' => null, 'error' => t('custom_fields.validation.field_min', ['label' => $label, 'min' => $definition['min']])];
        }
        if ($definition['max'] !== null && $intValue > (int) $definition['max']) {
            return ['value' => null, 'error' => t('custom_fields.validation.field_max', ['label' => $label, 'max' => $definition['max']])];
        }

        return ['value' => $intValue, 'error' => null];
    }

    /**
     * @param mixed $raw
     * @param array<string, mixed> $definition
     * @return array{value: float|null, error: string|null}
     */
    private function validateFloat($raw, array $definition, string $label): array
    {
        if ($raw === null || $raw === '') {
            if ($definition['required']) {
                return ['value' => null, 'error' => t('custom_fields.validation.field_required', ['label' => $label])];
            }
            return ['value' => null, 'error' => null];
        }

        if (!is_numeric($raw)) {
            return ['value' => null, 'error' => t('custom_fields.validation.field_invalid', ['label' => $label])];
        }

        $floatValue = (float) $raw;
        if ($definition['min'] !== null && $floatValue < (float) $definition['min']) {
            return ['value' => null, 'error' => t('custom_fields.validation.field_min', ['label' => $label, 'min' => $definition['min']])];
        }
        if ($definition['max'] !== null && $floatValue > (float) $definition['max']) {
            return ['value' => null, 'error' => t('custom_fields.validation.field_max', ['label' => $label, 'max' => $definition['max']])];
        }

        return ['value' => $floatValue, 'error' => null];
    }

    /**
     * @param mixed $raw
     * @param array<string, mixed> $definition
     * @return array{value: bool|null, error: string|null}
     */
    private function validateBool($raw, array $definition, string $label): array
    {
        if ($raw === null || $raw === '') {
            if ($definition['required']) {
                return ['value' => null, 'error' => t('custom_fields.validation.field_required', ['label' => $label])];
            }
            return ['value' => null, 'error' => null];
        }

        if ($raw === '1' || $raw === 1 || $raw === true || $raw === 'true' || $raw === 'on') {
            return ['value' => true, 'error' => null];
        }
        if ($raw === '0' || $raw === 0 || $raw === false || $raw === 'false' || $raw === 'off') {
            return ['value' => false, 'error' => null];
        }

        return ['value' => null, 'error' => t('custom_fields.validation.field_invalid', ['label' => $label])];
    }

    /**
     * @param mixed $raw
     * @param array<string, mixed> $definition
     * @return array{value: array<mixed>|null, error: string|null}
     */
    private function validateJson($raw, array $definition, string $label): array
    {
        if ($raw === null || $raw === '') {
            if ($definition['required']) {
                return ['value' => null, 'error' => t('custom_fields.validation.field_required', ['label' => $label])];
            }
            return ['value' => null, 'error' => null];
        }

        if (!is_string($raw)) {
            return ['value' => null, 'error' => t('custom_fields.validation.field_json', ['label' => $label])];
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return ['value' => null, 'error' => t('custom_fields.validation.field_json', ['label' => $label])];
        }

        return ['value' => $decoded, 'error' => null];
    }

    /**
     * @param mixed $raw
     * @param array<string, mixed> $definition
     * @return array{value: string|null, error: string|null}
     */
    private function validateString($raw, array $definition, string $label): array
    {
        if ($raw === null || $raw === '') {
            if ($definition['required']) {
                return ['value' => null, 'error' => t('custom_fields.validation.field_required', ['label' => $label])];
            }
            return ['value' => null, 'error' => null];
        }

        return ['value' => (string) $raw, 'error' => null];
    }

    private function resolveLabel($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }
        if (!is_array($value)) {
            return null;
        }
        $locale = current_locale();
        if (isset($value[$locale]) && $value[$locale] !== '') {
            return (string) $value[$locale];
        }
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $definition
     * @param mixed $value
     */
    private function formatValue(array $definition, $value): string
    {
        if ($value === null || $value === '') {
            return '–';
        }

        return match ((string) $definition['type']) {
            'bool' => (bool) $value ? t('custom_fields.fields.yes') : t('custom_fields.fields.no'),
            'json' => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }

    /**
     * @param array<string, mixed> $definition
     * @param mixed $value
     */
    private function formatEntityInfoValue(array $definition, $value): string
    {
        if ((string) $definition['type'] === 'json' && !is_string($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return $this->formatValue($definition, $value);
    }

    private function isMultilineType(string $type): bool
    {
        return in_array($type, ['textarea', 'json'], true);
    }
}
