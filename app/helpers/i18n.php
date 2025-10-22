<?php
use App\Core\App;
use App\I18n\Formatter;
use App\I18n\Translator;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Throwable;

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
        $config = i18n_resolve_system_config();
        if ($config) {
            $date = i18n_normalize_datetime($value, $config->timezone(), $config->timeOffsetMinutes());
            if ($date instanceof DateTimeImmutable) {
                return Formatter::datePattern($date, $config->locale(), $config->dateIntlPattern(), $config->timezone());
            }
        }

        return Formatter::date($value, current_locale(), $pattern);
    }
}

if (!function_exists('format_time')) {
    function format_time(\DateTimeInterface|string|int|null $value, string $pattern = 'short'): string
    {
        $config = i18n_resolve_system_config();
        if ($config) {
            $date = i18n_normalize_datetime($value, $config->timezone(), $config->timeOffsetMinutes());
            if ($date instanceof DateTimeImmutable) {
                return Formatter::timePattern($date, $config->locale(), $config->timeIntlPattern(), $config->timezone());
            }
        }

        return Formatter::time($value, current_locale(), $pattern);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(\DateTimeInterface|string|int|null $value, string $datePattern = 'medium', string $timePattern = 'short'): string
    {
        $config = i18n_resolve_system_config();
        if ($config) {
            $date = i18n_normalize_datetime($value, $config->timezone(), $config->timeOffsetMinutes());
            if ($date instanceof DateTimeImmutable) {
                $datePart = Formatter::datePattern($date, $config->locale(), $config->dateIntlPattern(), $config->timezone());
                $timePart = Formatter::timePattern($date, $config->locale(), $config->timeIntlPattern(), $config->timezone());
                $separator = $config->datetimeSeparator();
                return trim($datePart . ($timePart !== '' ? $separator . $timePart : ''));
            }
        }

        return Formatter::datetime($value, current_locale(), $datePattern, $timePattern);
    }
}

if (!function_exists('format_number')) {
    function format_number(int|float|string|null $value, int $decimals = 0): string
    {
        $config = i18n_resolve_system_config();
        if ($config) {
            return Formatter::numberCustom($value, $config->locale(), $decimals, $config->decimalSeparator(), $config->thousandSeparator());
        }

        return Formatter::number($value, current_locale(), $decimals);
    }
}

if (!function_exists('format_currency')) {
    function format_currency(int|float|string|null $value, string $currency = 'EUR'): string
    {
        $config = i18n_resolve_system_config();
        if ($config) {
            $decimals = $config->currencyDecimals();
            $code = $currency ?: $config->currencyCode();
            return Formatter::currencyCustom(
                $value,
                $config->locale(),
                $code,
                $decimals,
                $config->currencyFormat(),
                $config->decimalSeparator(),
                $config->thousandSeparator()
            );
        }

        return Formatter::currency($value, current_locale(), $currency);
    }
}

if (!function_exists('i18n_resolve_system_config')) {
    function i18n_resolve_system_config(): ?\App\Services\SystemConfiguration
    {
        if (!function_exists('system_config')) {
            return null;
        }

        try {
            $config = system_config();
        } catch (Throwable) {
            return null;
        }

        return $config instanceof \App\Services\SystemConfiguration ? $config : null;
    }
}

if (!function_exists('i18n_normalize_datetime')) {
    function i18n_normalize_datetime(\DateTimeInterface|string|int|null $value, ?string $timezone, int $offsetMinutes = 0): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof DateTimeImmutable) {
                $date = $value;
            } elseif ($value instanceof DateTimeInterface) {
                $date = DateTimeImmutable::createFromInterface($value);
            } elseif (is_int($value)) {
                $date = (new DateTimeImmutable('@' . $value))->setTimezone(new DateTimeZone($timezone ?: date_default_timezone_get()));
            } else {
                $date = new DateTimeImmutable((string) $value, $timezone ? new DateTimeZone($timezone) : null);
            }

            if ($offsetMinutes !== 0) {
                $date = $date->modify(($offsetMinutes > 0 ? '+' : '-') . abs($offsetMinutes) . ' minutes');
            }

            return $date;
        } catch (Throwable) {
            return null;
        }
    }
}
