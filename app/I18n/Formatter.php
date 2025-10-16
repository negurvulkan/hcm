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

    public static function time(DateTimeInterface|string|int|null $value, string $locale, string $pattern = 'short'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = self::toTimestamp($value);
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, self::mapTimeType($pattern));
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

    public static function number(int|float|string|null $value, string $locale, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
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
