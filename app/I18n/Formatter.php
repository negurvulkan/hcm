<?php
namespace App\I18n;

use DateTimeInterface;
use IntlDateFormatter;
use NumberFormatter;
use RuntimeException;

class Formatter
{
    public static function date(DateTimeInterface|string|int|null $value, string $locale, string $pattern = 'medium'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = self::toTimestamp($value);
        $formatter = new IntlDateFormatter($locale, self::mapDateType($pattern), IntlDateFormatter::NONE);
        return $formatter->format($timestamp) ?: '';
    }

    public static function datePattern(DateTimeInterface|string|int|null $value, string $locale, string $pattern, ?string $timezone = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = self::toTimestamp($value);
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $timezone, null, $pattern);
        return $formatter->format($timestamp) ?: '';
    }

    public static function time(DateTimeInterface|string|int|null $value, string $locale, string $pattern = 'short'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = self::toTimestamp($value);
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, self::mapTimeType($pattern));
        return $formatter->format($timestamp) ?: '';
    }

    public static function timePattern(DateTimeInterface|string|int|null $value, string $locale, string $pattern, ?string $timezone = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = self::toTimestamp($value);
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $timezone, null, $pattern);
        return $formatter->format($timestamp) ?: '';
    }

    public static function datetime(DateTimeInterface|string|int|null $value, string $locale, string $datePattern = 'medium', string $timePattern = 'short'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = self::toTimestamp($value);
        $formatter = new IntlDateFormatter($locale, self::mapDateType($datePattern), self::mapTimeType($timePattern));
        return $formatter->format($timestamp) ?: '';
    }

    public static function datetimePattern(DateTimeInterface|string|int|null $value, string $locale, string $datePattern, string $timePattern, ?string $timezone = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = self::toTimestamp($value);
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $timezone, null, trim($datePattern . ' ' . $timePattern));
        return $formatter->format($timestamp) ?: '';
    }

    public static function number(int|float|string|null $value, string $locale, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
        return $formatter->format((float) $value) ?: '';
    }

    public static function numberCustom(int|float|string|null $value, string $locale, int $decimals = 0, ?string $decimalSeparator = null, ?string $thousandSeparator = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
        if ($decimalSeparator !== null && $decimalSeparator !== '') {
            $formatter->setSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, $decimalSeparator);
        }
        if ($thousandSeparator !== null && $thousandSeparator !== '') {
            $formatter->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, $thousandSeparator);
        }

        return $formatter->format((float) $value) ?: '';
    }

    public static function currency(int|float|string|null $value, string $locale, string $currency): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $formatter->formatCurrency((float) $value, $currency) ?: '';
    }

    public static function currencyCustom(int|float|string|null $value, string $locale, string $currency, int $decimals, string $formatVariant, ?string $decimalSeparator = null, ?string $thousandSeparator = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $symbolFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        if ($decimalSeparator !== null && $decimalSeparator !== '') {
            $symbolFormatter->setSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, $decimalSeparator);
        }
        if ($thousandSeparator !== null && $thousandSeparator !== '') {
            $symbolFormatter->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, $thousandSeparator);
        }
        $symbolFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

        $symbol = $symbolFormatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
        if (!$symbol) {
            $symbol = $currency;
        }

        $formattedNumber = self::numberCustom($value, $locale, $decimals, $decimalSeparator, $thousandSeparator);
        if ($formattedNumber === '') {
            return '';
        }

        return match ($formatVariant) {
            'code_first' => trim($currency . ' ' . $formattedNumber),
            'symbol_last' => trim($formattedNumber . ' ' . $symbol),
            default => trim($symbol . ' ' . $formattedNumber),
        };
    }

    private static function toTimestamp(DateTimeInterface|string|int $value): int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            $timestamp = strtotime($value);
            if ($timestamp === false) {
                throw new RuntimeException('Invalid datetime value: ' . $value);
            }
            return $timestamp;
        }

        throw new RuntimeException('Unsupported datetime value.');
    }

    private static function mapDateType(string $pattern): int
    {
        return match ($pattern) {
            'short' => IntlDateFormatter::SHORT,
            'long' => IntlDateFormatter::LONG,
            'full' => IntlDateFormatter::FULL,
            default => IntlDateFormatter::MEDIUM,
        };
    }

    private static function mapTimeType(string $pattern): int
    {
        return match ($pattern) {
            'medium' => IntlDateFormatter::MEDIUM,
            'long' => IntlDateFormatter::LONG,
            'full' => IntlDateFormatter::FULL,
            default => IntlDateFormatter::SHORT,
        };
    }
}
