<?php
namespace App\Services;

use DateInterval;
use DateTimeImmutable;
use PDO;
use PDOException;

class SystemConfiguration
{
    public const TIME_FORMAT_24_HOUR = 'HH:mm';
    public const TIME_FORMAT_24_HOUR_SECONDS = 'HH:mm:ss';
    public const TIME_FORMAT_12_HOUR = 'hh:mm a';

    public const DATE_FORMAT_DMY = 'DD.MM.YYYY';
    public const DATE_FORMAT_YMD = 'YYYY-MM-DD';
    public const DATE_FORMAT_MDY = 'MM/DD/YYYY';

    public const WEEK_START_MONDAY = 'monday';
    public const WEEK_START_SUNDAY = 'sunday';

    public const DISPLAY_MODE_LIGHT = 'light';
    public const DISPLAY_MODE_DARK = 'dark';
    public const DISPLAY_MODE_AUTO = 'auto';

    public const DISPLAY_CLOCK_24 = '24h';
    public const DISPLAY_CLOCK_12 = '12h';

    public const DISPLAY_OFFLINE_CLIENT = 'client';
    public const DISPLAY_OFFLINE_FREEZE = 'freeze';

    public const CURRENCY_FORMAT_SYMBOL_FIRST = 'symbol_first';
    public const CURRENCY_FORMAT_SYMBOL_LAST = 'symbol_last';
    public const CURRENCY_FORMAT_CODE_FIRST = 'code_first';

    private const DEFAULTS = [
        'time_timezone' => 'Europe/Berlin',
        'time_format' => self::TIME_FORMAT_24_HOUR,
        'date_format' => self::DATE_FORMAT_DMY,
        'week_start' => self::WEEK_START_MONDAY,
        'time_daylight_saving' => '1',
        'time_offset_minutes' => '0',
        'time_sync_display' => '1',
        'time_live_clock' => '1',
        'locale_preferred' => 'de_DE',
        'number_decimal_separator' => ',',
        'number_thousand_separator' => '.',
        'number_datetime_separator' => ' ',
        'number_format_sample' => '1.234,56',
        'collation' => 'de_DE',
        'unit_length' => 'metric',
        'unit_temperature' => 'celsius',
        'currency_default' => 'EUR',
        'currency_format' => self::CURRENCY_FORMAT_SYMBOL_FIRST,
        'currency_rounding' => '2',
        'vat_rates' => '[]',
        'payment_terms' => '14 Tage',
        'price_list_format' => self::CURRENCY_FORMAT_SYMBOL_LAST,
        'db_driver' => 'sqlite',
        'db_host' => 'localhost',
        'db_port' => '3306',
        'db_name' => '',
        'db_user' => '',
        'db_password' => '',
        'db_table_prefix' => '',
        'backup_enabled' => '0',
        'backup_interval' => 'daily',
        'backup_location' => 'local',
        'backup_server_url' => '',
        'backup_auth_token' => '',
        'backup_sync_interval' => 'daily',
        'backup_api_keys' => '[]',
        'external_services' => '[]',
        'display_mode' => self::DISPLAY_MODE_AUTO,
        'display_clock_format' => self::DISPLAY_CLOCK_24,
        'display_show_seconds' => '0',
        'display_blink_colon' => '0',
        'display_offline_mode' => self::DISPLAY_OFFLINE_CLIENT,
        'display_time_overlay' => '0',
        'system_version' => '',
        'system_build' => '',
        'system_license_key' => '',
        'log_level' => 'info',
        'debug_enabled' => '0',
        'developer_mode' => '0',
        'custom_variables' => '{}',
        'theme_primary_color' => '#2b72ff',
        'theme_secondary_color' => '#11131a',
        'theme_logo_url' => '',
    ];

    private const BOOLEAN_KEYS = [
        'time_daylight_saving',
        'time_sync_display',
        'time_live_clock',
        'backup_enabled',
        'display_show_seconds',
        'display_blink_colon',
        'display_time_overlay',
        'debug_enabled',
        'developer_mode',
    ];

    private const INTEGER_KEYS = ['time_offset_minutes', 'currency_rounding'];

    private const JSON_LIST_KEYS = ['vat_rates', 'backup_api_keys', 'external_services'];

    private const JSON_OBJECT_KEYS = ['custom_variables'];

    private PDO $pdo;

    /**
     * @var array<string, mixed>
     */
    private array $settings = [];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $viewCache = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->settings = self::DEFAULTS;
        $this->load();
    }

    public static function timeFormats(): array
    {
        return [self::TIME_FORMAT_24_HOUR, self::TIME_FORMAT_24_HOUR_SECONDS, self::TIME_FORMAT_12_HOUR];
    }

    public static function dateFormats(): array
    {
        return [self::DATE_FORMAT_DMY, self::DATE_FORMAT_YMD, self::DATE_FORMAT_MDY];
    }

    public static function weekStarts(): array
    {
        return [self::WEEK_START_MONDAY, self::WEEK_START_SUNDAY];
    }

    public static function displayModes(): array
    {
        return [self::DISPLAY_MODE_AUTO, self::DISPLAY_MODE_LIGHT, self::DISPLAY_MODE_DARK];
    }

    public static function displayClockFormats(): array
    {
        return [self::DISPLAY_CLOCK_24, self::DISPLAY_CLOCK_12];
    }

    public static function displayOfflineModes(): array
    {
        return [self::DISPLAY_OFFLINE_CLIENT, self::DISPLAY_OFFLINE_FREEZE];
    }

    public static function currencyFormats(): array
    {
        return [self::CURRENCY_FORMAT_SYMBOL_FIRST, self::CURRENCY_FORMAT_SYMBOL_LAST, self::CURRENCY_FORMAT_CODE_FIRST];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->settings;
    }

    public function timezone(): string
    {
        return (string) ($this->settings['time_timezone'] ?? 'Europe/Berlin');
    }

    public function timeFormat(): string
    {
        $format = (string) ($this->settings['time_format'] ?? self::TIME_FORMAT_24_HOUR);
        return in_array($format, self::timeFormats(), true) ? $format : self::TIME_FORMAT_24_HOUR;
    }

    public function dateFormat(): string
    {
        $format = (string) ($this->settings['date_format'] ?? self::DATE_FORMAT_DMY);
        return in_array($format, self::dateFormats(), true) ? $format : self::DATE_FORMAT_DMY;
    }

    public function weekStart(): string
    {
        $value = (string) ($this->settings['week_start'] ?? self::WEEK_START_MONDAY);
        return in_array($value, self::weekStarts(), true) ? $value : self::WEEK_START_MONDAY;
    }

    public function daylightSavingEnabled(): bool
    {
        return $this->boolValue('time_daylight_saving');
    }

    public function timeOffsetMinutes(): int
    {
        return (int) ($this->settings['time_offset_minutes'] ?? 0);
    }

    public function syncDisplayWithServer(): bool
    {
        return $this->boolValue('time_sync_display');
    }

    public function liveClockEnabled(): bool
    {
        return $this->boolValue('time_live_clock');
    }

    public function locale(): string
    {
        $value = (string) ($this->settings['locale_preferred'] ?? 'de_DE');
        return $value !== '' ? $value : 'de_DE';
    }

    public function decimalSeparator(): string
    {
        return $this->settings['number_decimal_separator'] ?? ',';
    }

    public function thousandSeparator(): string
    {
        return $this->settings['number_thousand_separator'] ?? '.';
    }

    public function datetimeSeparator(): string
    {
        return $this->settings['number_datetime_separator'] ?? ' ';
    }

    public function numberFormatSample(): string
    {
        return $this->settings['number_format_sample'] ?? '1.234,56';
    }

    public function collation(): string
    {
        return $this->settings['collation'] ?? 'de_DE';
    }

    public function lengthUnit(): string
    {
        return $this->settings['unit_length'] ?? 'metric';
    }

    public function temperatureUnit(): string
    {
        return $this->settings['unit_temperature'] ?? 'celsius';
    }

    public function currencyCode(): string
    {
        $value = strtoupper((string) ($this->settings['currency_default'] ?? 'EUR'));
        return $value !== '' ? $value : 'EUR';
    }

    public function currencyFormat(): string
    {
        $format = (string) ($this->settings['currency_format'] ?? self::CURRENCY_FORMAT_SYMBOL_FIRST);
        return in_array($format, self::currencyFormats(), true) ? $format : self::CURRENCY_FORMAT_SYMBOL_FIRST;
    }

    public function priceListFormat(): string
    {
        $format = (string) ($this->settings['price_list_format'] ?? self::CURRENCY_FORMAT_SYMBOL_LAST);
        return in_array($format, self::currencyFormats(), true) ? $format : self::CURRENCY_FORMAT_SYMBOL_LAST;
    }

    public function currencyDecimals(): int
    {
        $raw = (int) ($this->settings['currency_rounding'] ?? 2);
        return max(0, min(4, $raw));
    }

    /**
     * @return list<float>
     */
    public function vatRates(): array
    {
        $decoded = $this->decodeJsonList('vat_rates');
        $rates = [];
        foreach ($decoded as $rate) {
            if (is_numeric($rate)) {
                $rates[] = (float) $rate;
            }
        }

        return $rates;
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    public function backupApiKeys(): array
    {
        $entries = $this->decodeJsonList('backup_api_keys');
        $normalized = [];
        foreach ($entries as $entry) {
            if (is_array($entry) && isset($entry['name'], $entry['key'])) {
                $normalized[] = [
                    'name' => (string) $entry['name'],
                    'key' => (string) $entry['key'],
                ];
            }
        }

        return $normalized;
    }

    /**
     * @return list<array{name: string, value: string}>
     */
    public function externalServices(): array
    {
        $entries = $this->decodeJsonList('external_services');
        $normalized = [];
        foreach ($entries as $entry) {
            if (is_array($entry) && isset($entry['name'], $entry['value'])) {
                $normalized[] = [
                    'name' => (string) $entry['name'],
                    'value' => (string) $entry['value'],
                ];
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    public function customVariables(): array
    {
        $decoded = $this->decodeJsonObject('custom_variables');
        $result = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $result[$key] = is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR);
            }
        }

        return $result;
    }

    public function displayMode(): string
    {
        $value = (string) ($this->settings['display_mode'] ?? self::DISPLAY_MODE_AUTO);
        return in_array($value, self::displayModes(), true) ? $value : self::DISPLAY_MODE_AUTO;
    }

    public function displayClockFormat(): string
    {
        $value = (string) ($this->settings['display_clock_format'] ?? self::DISPLAY_CLOCK_24);
        return in_array($value, self::displayClockFormats(), true) ? $value : self::DISPLAY_CLOCK_24;
    }

    public function displayShowsSeconds(): bool
    {
        return $this->boolValue('display_show_seconds');
    }

    public function displayBlinksColon(): bool
    {
        return $this->boolValue('display_blink_colon');
    }

    public function displayOfflineMode(): string
    {
        $value = (string) ($this->settings['display_offline_mode'] ?? self::DISPLAY_OFFLINE_CLIENT);
        return in_array($value, self::displayOfflineModes(), true) ? $value : self::DISPLAY_OFFLINE_CLIENT;
    }

    public function displayTimeOverlay(): bool
    {
        return $this->boolValue('display_time_overlay');
    }

    public function systemVersion(): string
    {
        return (string) ($this->settings['system_version'] ?? '');
    }

    public function systemBuild(): string
    {
        return (string) ($this->settings['system_build'] ?? '');
    }

    public function systemLicenseKey(): string
    {
        return (string) ($this->settings['system_license_key'] ?? '');
    }

    public function logLevel(): string
    {
        $value = strtolower((string) ($this->settings['log_level'] ?? 'info'));
        return in_array($value, ['info', 'warn', 'error', 'debug'], true) ? $value : 'info';
    }

    public function debugEnabled(): bool
    {
        return $this->boolValue('debug_enabled');
    }

    public function developerMode(): bool
    {
        return $this->boolValue('developer_mode');
    }

    public function themePrimaryColor(): string
    {
        return (string) ($this->settings['theme_primary_color'] ?? '#2b72ff');
    }

    public function themeSecondaryColor(): string
    {
        return (string) ($this->settings['theme_secondary_color'] ?? '#11131a');
    }

    public function themeLogoUrl(): string
    {
        return (string) ($this->settings['theme_logo_url'] ?? '');
    }

    public function paymentTerms(): string
    {
        return (string) ($this->settings['payment_terms'] ?? '');
    }

    public function dateIntlPattern(): string
    {
        return match ($this->dateFormat()) {
            self::DATE_FORMAT_YMD => 'yyyy-MM-dd',
            self::DATE_FORMAT_MDY => 'MM/dd/yyyy',
            default => 'dd.MM.yyyy',
        };
    }

    public function timeIntlPattern(): string
    {
        return match ($this->timeFormat()) {
            self::TIME_FORMAT_24_HOUR_SECONDS => 'HH:mm:ss',
            self::TIME_FORMAT_12_HOUR => 'hh:mm a',
            default => 'HH:mm',
        };
    }

    public function displayClockPattern(): string
    {
        $base = $this->displayClockFormat() === self::DISPLAY_CLOCK_12 ? 'hh:mm' : 'HH:mm';
        if ($this->displayShowsSeconds()) {
            $base .= ':ss';
        }

        if ($this->displayClockFormat() === self::DISPLAY_CLOCK_12) {
            $base .= ' a';
        }

        return $base;
    }

    public function adjustDateTime(DateTimeImmutable $value): DateTimeImmutable
    {
        $offset = $this->timeOffsetMinutes();
        if ($offset === 0) {
            return $value;
        }

        try {
            $interval = new DateInterval('PT' . abs($offset) . 'M');
            return $offset > 0 ? $value->add($interval) : $value->sub($interval);
        } catch (\Exception) {
            return $value;
        }
    }

    /**
     * @param array<string, mixed> $values
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public function diff(array $values): array
    {
        return $this->computeChanges($values);
    }

    /**
     * @param array<string, mixed> $values
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public function save(array $values): array
    {
        $changes = $this->computeChanges($values);
        if ($changes['after'] === []) {
            return $changes;
        }

        $timestamp = (new DateTimeImmutable('now'))->format('c');
        foreach ($changes['after'] as $key => $value) {
            $this->persist($key, $value, $timestamp);
            $this->settings[$key] = $value;
        }

        $this->viewCache = null;
        return $changes;
    }

    /**
     * @return array{
     *     time: array<string, mixed>,
     *     locale: array<string, mixed>,
     *     currency: array<string, mixed>,
     *     display: array<string, mixed>,
     *     integration: array<string, mixed>,
     *     system: array<string, mixed>,
     *     theme: array<string, mixed>
     * }
     */
    public function viewContext(): array
    {
        if ($this->viewCache !== null) {
            return $this->viewCache;
        }

        $this->viewCache = [
            'time' => [
                'timezone' => $this->timezone(),
                'format' => $this->timeFormat(),
                'date_format' => $this->dateFormat(),
                'week_start' => $this->weekStart(),
                'daylight_saving' => $this->daylightSavingEnabled(),
                'offset_minutes' => $this->timeOffsetMinutes(),
                'sync_display' => $this->syncDisplayWithServer(),
                'live_clock' => $this->liveClockEnabled(),
                'pattern_time' => $this->timeIntlPattern(),
                'pattern_date' => $this->dateIntlPattern(),
            ],
            'locale' => [
                'preferred' => $this->locale(),
                'decimal' => $this->decimalSeparator(),
                'thousand' => $this->thousandSeparator(),
                'datetime_separator' => $this->datetimeSeparator(),
                'number_example' => $this->numberFormatSample(),
                'collation' => $this->collation(),
                'unit_length' => $this->lengthUnit(),
                'unit_temperature' => $this->temperatureUnit(),
            ],
            'currency' => [
                'code' => $this->currencyCode(),
                'format' => $this->currencyFormat(),
                'decimals' => $this->currencyDecimals(),
                'vat_rates' => $this->vatRates(),
                'payment_terms' => $this->paymentTerms(),
                'price_list_format' => $this->priceListFormat(),
            ],
            'display' => [
                'mode' => $this->displayMode(),
                'clock_format' => $this->displayClockFormat(),
                'clock_pattern' => $this->displayClockPattern(),
                'seconds' => $this->displayShowsSeconds(),
                'blink_colon' => $this->displayBlinksColon(),
                'offline_mode' => $this->displayOfflineMode(),
                'time_overlay' => $this->displayTimeOverlay(),
            ],
            'integration' => [
                'driver' => $this->settings['db_driver'] ?? 'sqlite',
                'host' => $this->settings['db_host'] ?? '',
                'port' => $this->settings['db_port'] ?? '',
                'name' => $this->settings['db_name'] ?? '',
                'user' => $this->settings['db_user'] ?? '',
                'table_prefix' => $this->settings['db_table_prefix'] ?? '',
                'backup_enabled' => $this->boolValue('backup_enabled'),
                'backup_interval' => $this->settings['backup_interval'] ?? 'daily',
                'backup_location' => $this->settings['backup_location'] ?? 'local',
                'backup_server_url' => $this->settings['backup_server_url'] ?? '',
                'backup_sync_interval' => $this->settings['backup_sync_interval'] ?? 'daily',
                'api_keys' => $this->backupApiKeys(),
                'external_services' => $this->externalServices(),
            ],
            'system' => [
                'version' => $this->systemVersion(),
                'build' => $this->systemBuild(),
                'license_key' => $this->systemLicenseKey(),
                'log_level' => $this->logLevel(),
                'debug' => $this->debugEnabled(),
                'developer_mode' => $this->developerMode(),
                'custom_variables' => $this->customVariables(),
            ],
            'theme' => [
                'primary' => $this->themePrimaryColor(),
                'secondary' => $this->themeSecondaryColor(),
                'logo' => $this->themeLogoUrl(),
            ],
        ];

        return $this->viewCache;
    }

    /**
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    private function computeChanges(array $values): array
    {
        $before = [];
        $after = [];

        foreach ($values as $key => $value) {
            if (!array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            $normalized = $this->normalize($key, $value);
            $current = $this->settings[$key] ?? null;

            if ($current === $normalized) {
                continue;
            }

            $before[$key] = $current;
            $after[$key] = $normalized;
        }

        return ['before' => $before, 'after' => $after];
    }

    private function normalize(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            return $this->normalizeBoolean($value);
        }

        if (in_array($key, self::INTEGER_KEYS, true)) {
            return (string) (int) $value;
        }

        if (in_array($key, self::JSON_LIST_KEYS, true)) {
            $encoded = $this->encodeJsonList($value);
            return $encoded ?? '[]';
        }

        if (in_array($key, self::JSON_OBJECT_KEYS, true)) {
            $encoded = $this->encodeJsonObject($value);
            return $encoded ?? '{}';
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '') {
            return null;
        }

        if ($key === 'time_format' && !in_array($value, self::timeFormats(), true)) {
            return self::TIME_FORMAT_24_HOUR;
        }

        if ($key === 'date_format' && !in_array($value, self::dateFormats(), true)) {
            return self::DATE_FORMAT_DMY;
        }

        if ($key === 'week_start' && !in_array($value, self::weekStarts(), true)) {
            return self::WEEK_START_MONDAY;
        }

        if ($key === 'display_mode' && !in_array($value, self::displayModes(), true)) {
            return self::DISPLAY_MODE_AUTO;
        }

        if ($key === 'display_clock_format' && !in_array($value, self::displayClockFormats(), true)) {
            return self::DISPLAY_CLOCK_24;
        }

        if ($key === 'display_offline_mode' && !in_array($value, self::displayOfflineModes(), true)) {
            return self::DISPLAY_OFFLINE_CLIENT;
        }

        if ($key === 'currency_format' && !in_array($value, self::currencyFormats(), true)) {
            return self::CURRENCY_FORMAT_SYMBOL_FIRST;
        }

        if ($key === 'price_list_format' && !in_array($value, self::currencyFormats(), true)) {
            return self::CURRENCY_FORMAT_SYMBOL_LAST;
        }

        if ($key === 'log_level') {
            $value = strtolower((string) $value);
            return in_array($value, ['info', 'warn', 'error', 'debug'], true) ? $value : 'info';
        }

        return (string) $value;
    }

    private function normalizeBoolean(mixed $value): string
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '' || $value === false || $value === '0' || $value === 0) {
            return '0';
        }

        return '1';
    }

    private function boolValue(string $key): bool
    {
        $value = $this->settings[$key] ?? '0';
        return $value === '1' || $value === 1 || $value === true;
    }

    private function persist(string $key, mixed $value, string $timestamp): void
    {
        try {
            $update = $this->pdo->prepare('UPDATE system_settings SET value = :value, updated_at = :updated WHERE setting_key = :key');
            $update->execute([
                'value' => $value,
                'updated' => $timestamp,
                'key' => $key,
            ]);

            if ($update->rowCount() === 0) {
                $insert = $this->pdo->prepare('INSERT INTO system_settings (setting_key, value, updated_at) VALUES (:key, :value, :updated)');
                $insert->execute([
                    'key' => $key,
                    'value' => $value,
                    'updated' => $timestamp,
                ]);
            }
        } catch (PDOException) {
            // ignored when table is missing
        }
    }

    private function load(): void
    {
        try {
            $stmt = $this->pdo->query('SELECT setting_key, value FROM system_settings');
        } catch (PDOException) {
            return;
        }

        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as $row) {
            if (!array_key_exists($row['setting_key'], self::DEFAULTS)) {
                continue;
            }
            $this->settings[$row['setting_key']] = $row['value'];
        }
    }

    /**
     * @return list<mixed>
     */
    private function decodeJsonList(string $key): array
    {
        $raw = $this->settings[$key] ?? '[]';
        if ($raw === null || $raw === '') {
            return [];
        }

        try {
            $decoded = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? array_values($decoded) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $key): array
    {
        $raw = $this->settings[$key] ?? '{}';
        if ($raw === null || $raw === '') {
            return [];
        }

        try {
            $decoded = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function encodeJsonList(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return '[]';
            }
            $parts = preg_split('/\r?\n+/', $value) ?: [];
            $value = $parts;
        }

        if (!is_array($value)) {
            return '[]';
        }

        $normalized = [];
        foreach ($value as $entry) {
            if ($entry === null) {
                continue;
            }
            if (is_string($entry)) {
                $entry = trim($entry);
            }
            if ($entry === '') {
                continue;
            }
            $normalized[] = $entry;
        }

        try {
            return json_encode(array_values($normalized), JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return '[]';
        }
    }

    private function encodeJsonObject(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return '{}';
            }
            $lines = preg_split('/\r?\n/', $value) ?: [];
            $parsed = [];
            foreach ($lines as $line) {
                if ($line === '') {
                    continue;
                }
                if (!str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                if ($k !== '') {
                    $parsed[$k] = $v;
                }
            }
            $value = $parsed;
        }

        if (!is_array($value)) {
            return '{}';
        }

        $normalized = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            $normalized[$key] = $entry;
        }

        try {
            return json_encode($normalized, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return '{}';
        }
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public function redact(array $values): array
    {
        $redacted = $values;
        foreach (['db_password', 'backup_auth_token'] as $secretKey) {
            if (array_key_exists($secretKey, $redacted) && $redacted[$secretKey]) {
                $redacted[$secretKey] = '***';
            }
        }

        if (array_key_exists('system_license_key', $redacted) && $redacted['system_license_key']) {
            $redacted['system_license_key'] = '***';
        }

        return $redacted;
    }

    /**
     * @return array<string, mixed>
     */
    public function formDefaults(): array
    {
        $context = $this->viewContext();

        $formatVatRate = static fn ($rate) => rtrim(rtrim(number_format((float) $rate, 2, '.', ''), '0'), '.');

        return [
            'time' => array_merge($context['time'], [
                'timezone' => $this->timezone(),
            ]),
            'locale' => array_merge($context['locale'], [
                'preferred' => $this->locale(),
            ]),
            'currency' => array_merge($context['currency'], [
                'vat_rates_raw' => implode("\n", array_map($formatVatRate, $context['currency']['vat_rates'] ?? [])),
            ]),
            'integration' => array_merge($context['integration'], [
                'db_password' => $this->settings['db_password'] ?? '',
                'backup_auth_token' => $this->settings['backup_auth_token'] ?? '',
                'backup_api_keys_raw' => $this->serializePairs($this->backupApiKeys()),
                'external_services_raw' => $this->serializePairs($this->externalServices()),
            ]),
            'display' => $context['display'],
            'system' => array_merge($context['system'], [
                'custom_variables_raw' => $this->serializeMap($this->customVariables()),
            ]),
            'theme' => $context['theme'],
            'payment_terms' => $this->paymentTerms(),
        ];
    }

    /**
     * @param array<array{name: string, value: string}>|array<array{name: string, key: string}> $pairs
     */
    private function serializePairs(array $pairs): string
    {
        $lines = [];
        foreach ($pairs as $pair) {
            $name = trim((string) ($pair['name'] ?? ''));
            $value = trim((string) ($pair['value'] ?? ($pair['key'] ?? '')));
            if ($name === '' && $value === '') {
                continue;
            }
            $lines[] = $name . '=' . $value;
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, string> $map
     */
    private function serializeMap(array $map): string
    {
        $lines = [];
        foreach ($map as $key => $value) {
            $lines[] = $key . '=' . $value;
        }

        return implode("\n", $lines);
    }
}

