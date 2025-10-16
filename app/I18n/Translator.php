<?php
namespace App\I18n;

use RuntimeException;

class Translator
{
    private string $locale;
    private string $fallback;
    private array $translations = [];
    private array $fallbackTranslations = [];
    private string $directory;

    public function __construct(string $locale, string $fallback = 'de', ?string $directory = null)
    {
        $this->locale = $locale;
        $this->fallback = $fallback;
        $this->directory = $directory ?? __DIR__ . '/../../lang';
        $this->fallbackTranslations = $this->loadFile($this->fallback);
        if ($locale === $fallback) {
            $this->translations = $this->fallbackTranslations;
        } else {
            $this->translations = $this->loadFile($locale);
        }
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function translate(string $key, array $params = []): string
    {
        $value = $this->getValue($this->translations, $key);
        if ($value === null) {
            $value = $this->getValue($this->fallbackTranslations, $key);
        }

        if (!is_string($value)) {
            $this->logMissingKey($key);
            return $this->missingKey($key);
        }

        return $this->replaceParams($value, $params);
    }

    public function translatePlural(string $key, int|float $count, array $params = []): string
    {
        $forms = $this->getPluralForms($this->translations, $key);
        if ($forms === null) {
            $forms = $this->getPluralForms($this->fallbackTranslations, $key);
        }

        if ($forms === null) {
            $this->logMissingKey($key);
            return $this->missingKey($key);
        }

        $formKey = $this->selectPluralForm($this->locale, $count);
        $value = $forms[$formKey] ?? $forms['other'] ?? reset($forms);
        if (!is_string($value)) {
            $this->logMissingKey($key . '.' . $formKey);
            return $this->missingKey($key);
        }

        $params = array_merge(['count' => $count], $params);

        return $this->replaceParams($value, $params);
    }

    public function all(): array
    {
        return $this->translations;
    }

    public function missingKey(string $key): string
    {
        return '[[' . $key . ']]';
    }

    private function loadFile(string $locale): array
    {
        $path = rtrim($this->directory, '/') . '/' . $locale . '.php';
        if (!is_file($path)) {
            if ($locale === $this->fallback) {
                throw new RuntimeException('Fallback locale missing: ' . $locale);
            }
            $this->logMissingFile($locale);
            return [];
        }

        $data = require $path;
        if (!is_array($data)) {
            throw new RuntimeException('Invalid translation file for locale: ' . $locale);
        }

        return $data;
    }

    private function getValue(array $source, string $key): mixed
    {
        $segments = explode('.', $key);
        $value = $source;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        if (is_array($value)) {
            return null;
        }

        return $value;
    }

    private function getPluralForms(array $source, string $key): ?array
    {
        $segments = explode('.', $key);
        $value = $source;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        if (!is_array($value)) {
            return null;
        }

        return $value;
    }

    private function replaceParams(string $value, array $params): string
    {
        if ($params === []) {
            return $value;
        }

        $replacements = [];
        foreach ($params as $key => $param) {
            $replacements['{' . $key . '}'] = (string) $param;
        }

        return strtr($value, $replacements);
    }

    private function selectPluralForm(string $locale, int|float $count): string
    {
        $language = strtolower(substr($locale, 0, 2));
        return match ($language) {
            'de' => $count == 1 ? 'one' : 'other',
            'en' => $count == 1 ? 'one' : 'other',
            default => $count == 1 ? 'one' : 'other',
        };
    }

    private function logMissingKey(string $key): void
    {
        error_log('[i18n] Missing translation key: ' . $this->locale . '::' . $key);
    }

    private function logMissingFile(string $locale): void
    {
        error_log('[i18n] Missing translation file for locale: ' . $locale);
    }
}
