<?php
use App\Core\App;
use App\I18n\Formatter;
use App\I18n\Translator;

if (!function_exists('current_locale')) {
    function current_locale(): string
    {
        return App::get('locale', 'de');
    }
}

if (!function_exists('t')) {
    function t(string $key, array $params = []): string
    {
        $translator = App::get('translator');
        if (!$translator instanceof Translator) {
            return '[[' . $key . ']]';
        }

        return $translator->translate($key, $params);
    }
}

if (!function_exists('tn')) {
    function tn(string $key, int|float $count, array $params = []): string
    {
        $translator = App::get('translator');
        if (!$translator instanceof Translator) {
            return '[[' . $key . ']]';
        }

        return $translator->translatePlural($key, $count, $params);
    }
}

if (!function_exists('format_date')) {
    function format_date(\DateTimeInterface|string|int|null $value, string $pattern = 'medium'): string
    {
        return Formatter::date($value, current_locale(), $pattern);
    }
}

if (!function_exists('format_time')) {
    function format_time(\DateTimeInterface|string|int|null $value, string $pattern = 'short'): string
    {
        return Formatter::time($value, current_locale(), $pattern);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(\DateTimeInterface|string|int|null $value, string $datePattern = 'medium', string $timePattern = 'short'): string
    {
        return Formatter::datetime($value, current_locale(), $datePattern, $timePattern);
    }
}

if (!function_exists('format_number')) {
    function format_number(int|float|string|null $value, int $decimals = 0): string
    {
        return Formatter::number($value, current_locale(), $decimals);
    }
}

if (!function_exists('format_currency')) {
    function format_currency(int|float|string|null $value, string $currency = 'EUR'): string
    {
        return Formatter::currency($value, current_locale(), $currency);
    }
}
