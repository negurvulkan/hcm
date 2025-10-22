<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';

use App\Core\Csrf;
use App\Services\SystemConfiguration;

$user = auth_require('system');
$systemConfig = system_config();

$timezones = timezone_identifiers_list();
sort($timezones);

$timeFormats = SystemConfiguration::timeFormats();
$dateFormats = SystemConfiguration::dateFormats();
$weekStarts = SystemConfiguration::weekStarts();
$displayModes = SystemConfiguration::displayModes();
$displayClockFormats = SystemConfiguration::displayClockFormats();
$displayOfflineModes = SystemConfiguration::displayOfflineModes();
$currencyFormats = SystemConfiguration::currencyFormats();
$lengthUnits = ['metric', 'imperial'];
$temperatureUnits = ['celsius', 'fahrenheit'];
$databaseDrivers = ['sqlite', 'mysql'];
$backupIntervals = ['daily', 'weekly', 'monthly'];
$backupLocations = ['local', 'cloud', 'nas'];
$logLevels = ['info', 'warn', 'error', 'debug'];

$form = $systemConfig->formDefaults();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        $errors[] = t('system.validation.csrf');
    } else {
        require_write_access('instance');

        $input = $_POST['settings'] ?? [];
        $updates = [];

        // Time
        $timeInput = $input['time'] ?? [];
        $timezone = trim((string) ($timeInput['timezone'] ?? $form['time']['timezone'] ?? ''));
        if ($timezone === '' || !in_array($timezone, $timezones, true)) {
            $errors[] = t('system.validation.timezone');
        } else {
            $updates['time_timezone'] = $timezone;
            $form['time']['timezone'] = $timezone;
        }

        $timeFormat = (string) ($timeInput['format'] ?? $form['time']['format'] ?? SystemConfiguration::TIME_FORMAT_24_HOUR);
        if (!in_array($timeFormat, $timeFormats, true)) {
            $errors[] = t('system.validation.time_format');
        } else {
            $updates['time_format'] = $timeFormat;
            $form['time']['format'] = $timeFormat;
        }

        $dateFormat = (string) ($timeInput['date_format'] ?? $form['time']['date_format'] ?? SystemConfiguration::DATE_FORMAT_DMY);
        if (!in_array($dateFormat, $dateFormats, true)) {
            $errors[] = t('system.validation.date_format');
        } else {
            $updates['date_format'] = $dateFormat;
            $form['time']['date_format'] = $dateFormat;
        }

        $weekStart = (string) ($timeInput['week_start'] ?? $form['time']['week_start'] ?? SystemConfiguration::WEEK_START_MONDAY);
        if (!in_array($weekStart, $weekStarts, true)) {
            $errors[] = t('system.validation.week_start');
        } else {
            $updates['week_start'] = $weekStart;
            $form['time']['week_start'] = $weekStart;
        }

        $updates['time_daylight_saving'] = !empty($timeInput['daylight_saving']) ? '1' : '0';
        $form['time']['daylight_saving'] = !empty($timeInput['daylight_saving']);

        $offset = isset($timeInput['offset']) ? (int) $timeInput['offset'] : (int) ($form['time']['offset_minutes'] ?? 0);
        $updates['time_offset_minutes'] = (string) $offset;
        $form['time']['offset_minutes'] = $offset;

        $updates['time_sync_display'] = !empty($timeInput['sync_display']) ? '1' : '0';
        $form['time']['sync_display'] = !empty($timeInput['sync_display']);

        $updates['time_live_clock'] = !empty($timeInput['live_clock']) ? '1' : '0';
        $form['time']['live_clock'] = !empty($timeInput['live_clock']);

        // Locale
        $localeInput = $input['locale'] ?? [];
        $preferredLocale = trim((string) ($localeInput['preferred'] ?? $form['locale']['preferred'] ?? ''));
        if ($preferredLocale === '') {
            $errors[] = t('system.validation.locale');
        } else {
            $updates['locale_preferred'] = $preferredLocale;
            $form['locale']['preferred'] = $preferredLocale;
        }

        $decimalSeparator = (string) ($localeInput['decimal_separator'] ?? $form['locale']['decimal']);
        if (!in_array($decimalSeparator, [',', '.', ' '], true)) {
            $errors[] = t('system.validation.decimal_separator');
        } else {
            $updates['number_decimal_separator'] = $decimalSeparator;
            $form['locale']['decimal'] = $decimalSeparator;
        }

        $thousandSeparator = (string) ($localeInput['thousand_separator'] ?? $form['locale']['thousand']);
        if (!in_array($thousandSeparator, ['.', ',', ' ', ' '], true)) {
            $errors[] = t('system.validation.thousand_separator');
        } else {
            $updates['number_thousand_separator'] = $thousandSeparator;
            $form['locale']['thousand'] = $thousandSeparator;
        }

        $datetimeSeparator = (string) ($localeInput['datetime_separator'] ?? $form['locale']['datetime_separator']);
        $updates['number_datetime_separator'] = $datetimeSeparator !== '' ? $datetimeSeparator : ' ';
        $form['locale']['datetime_separator'] = $updates['number_datetime_separator'];

        $numberExample = trim((string) ($localeInput['number_example'] ?? $form['locale']['number_example'] ?? ''));
        if ($numberExample === '') {
            $errors[] = t('system.validation.number_example');
        } else {
            $updates['number_format_sample'] = $numberExample;
            $form['locale']['number_example'] = $numberExample;
        }

        $collation = trim((string) ($localeInput['collation'] ?? $form['locale']['collation'] ?? ''));
        $updates['collation'] = $collation !== '' ? $collation : 'de_DE';
        $form['locale']['collation'] = $updates['collation'];

        $lengthUnit = (string) ($localeInput['unit_length'] ?? $form['locale']['unit_length'] ?? 'metric');
        if (!in_array($lengthUnit, $lengthUnits, true)) {
            $errors[] = t('system.validation.length_unit');
        } else {
            $updates['unit_length'] = $lengthUnit;
            $form['locale']['unit_length'] = $lengthUnit;
        }

        $temperatureUnit = (string) ($localeInput['unit_temperature'] ?? $form['locale']['unit_temperature'] ?? 'celsius');
        if (!in_array($temperatureUnit, $temperatureUnits, true)) {
            $errors[] = t('system.validation.temperature_unit');
        } else {
            $updates['unit_temperature'] = $temperatureUnit;
            $form['locale']['unit_temperature'] = $temperatureUnit;
        }

        // Currency
        $currencyInput = $input['currency'] ?? [];
        $currencyCode = strtoupper(trim((string) ($currencyInput['code'] ?? $form['currency']['code'] ?? '')));
        if ($currencyCode === '' || strlen($currencyCode) !== 3) {
            $errors[] = t('system.validation.currency');
        } else {
            $updates['currency_default'] = $currencyCode;
            $form['currency']['code'] = $currencyCode;
        }

        $currencyFormat = (string) ($currencyInput['format'] ?? $form['currency']['format'] ?? SystemConfiguration::CURRENCY_FORMAT_SYMBOL_FIRST);
        if (!in_array($currencyFormat, $currencyFormats, true)) {
            $errors[] = t('system.validation.currency_format');
        } else {
            $updates['currency_format'] = $currencyFormat;
            $form['currency']['format'] = $currencyFormat;
        }

        $priceListFormat = (string) ($currencyInput['price_list_format'] ?? $form['currency']['price_list_format'] ?? SystemConfiguration::CURRENCY_FORMAT_SYMBOL_LAST);
        if (!in_array($priceListFormat, $currencyFormats, true)) {
            $errors[] = t('system.validation.price_format');
        } else {
            $updates['price_list_format'] = $priceListFormat;
            $form['currency']['price_list_format'] = $priceListFormat;
        }

        $decimals = isset($currencyInput['decimals']) ? (int) $currencyInput['decimals'] : $form['currency']['decimals'];
        if ($decimals < 0 || $decimals > 4) {
            $errors[] = t('system.validation.currency_decimals');
        } else {
            $updates['currency_rounding'] = (string) $decimals;
            $form['currency']['decimals'] = $decimals;
        }

        $vatRatesRaw = (string) ($currencyInput['vat_rates'] ?? '');
        $vatRates = [];
        if ($vatRatesRaw !== '') {
            $parts = preg_split('/\r?\n|;/', $vatRatesRaw) ?: [];
            foreach ($parts as $part) {
                $value = str_replace(',', '.', trim($part));
                if ($value === '') {
                    continue;
                }
                if (!is_numeric($value)) {
                    $errors[] = t('system.validation.vat_rates');
                    break;
                }
                $vatRates[] = (float) $value;
            }
        }
        if (!$errors) {
            $updates['vat_rates'] = $vatRates;
            $form['currency']['vat_rates_raw'] = implode("\n", array_map(static fn ($rate) => rtrim(rtrim(number_format((float) $rate, 2, '.', ''), '0'), '.'), $vatRates));
        }

        $paymentTerms = trim((string) ($currencyInput['payment_terms'] ?? $form['currency']['payment_terms'] ?? ''));
        $updates['payment_terms'] = $paymentTerms;
        $form['currency']['payment_terms'] = $paymentTerms;

        // Integration
        $integrationInput = $input['integration'] ?? [];
        $driver = (string) ($integrationInput['driver'] ?? $form['integration']['driver'] ?? 'sqlite');
        if (!in_array($driver, $databaseDrivers, true)) {
            $errors[] = t('system.validation.driver');
        } else {
            $updates['db_driver'] = $driver;
            $form['integration']['driver'] = $driver;
        }

        $host = trim((string) ($integrationInput['host'] ?? $form['integration']['host'] ?? ''));
        $updates['db_host'] = $host;
        $form['integration']['host'] = $host;

        $port = trim((string) ($integrationInput['port'] ?? $form['integration']['port'] ?? ''));
        if ($port !== '' && !ctype_digit($port)) {
            $errors[] = t('system.validation.port');
        } else {
            $updates['db_port'] = $port;
            $form['integration']['port'] = $port;
        }

        $dbName = trim((string) ($integrationInput['name'] ?? $form['integration']['name'] ?? ''));
        $updates['db_name'] = $dbName;
        $form['integration']['name'] = $dbName;

        $dbUser = trim((string) ($integrationInput['user'] ?? $form['integration']['user'] ?? ''));
        $updates['db_user'] = $dbUser;
        $form['integration']['user'] = $dbUser;

        $dbPassword = (string) ($integrationInput['password'] ?? '');
        $updates['db_password'] = $dbPassword;
        $form['integration']['db_password'] = $dbPassword;

        $tablePrefix = trim((string) ($integrationInput['table_prefix'] ?? $form['integration']['table_prefix'] ?? ''));
        $updates['db_table_prefix'] = $tablePrefix;
        $form['integration']['table_prefix'] = $tablePrefix;

        $updates['backup_enabled'] = !empty($integrationInput['backup_enabled']) ? '1' : '0';
        $form['integration']['backup_enabled'] = !empty($integrationInput['backup_enabled']);

        $backupInterval = (string) ($integrationInput['backup_interval'] ?? $form['integration']['backup_interval'] ?? 'daily');
        if (!in_array($backupInterval, $backupIntervals, true)) {
            $errors[] = t('system.validation.backup_interval');
        } else {
            $updates['backup_interval'] = $backupInterval;
            $form['integration']['backup_interval'] = $backupInterval;
        }

        $backupLocation = (string) ($integrationInput['backup_location'] ?? $form['integration']['backup_location'] ?? 'local');
        if (!in_array($backupLocation, $backupLocations, true)) {
            $errors[] = t('system.validation.backup_location');
        } else {
            $updates['backup_location'] = $backupLocation;
            $form['integration']['backup_location'] = $backupLocation;
        }

        $backupServerUrl = trim((string) ($integrationInput['backup_server_url'] ?? $form['integration']['backup_server_url'] ?? ''));
        if ($backupServerUrl !== '' && !filter_var($backupServerUrl, FILTER_VALIDATE_URL)) {
            $errors[] = t('system.validation.backup_url');
        } else {
            $updates['backup_server_url'] = $backupServerUrl;
            $form['integration']['backup_server_url'] = $backupServerUrl;
        }

        $backupAuthToken = (string) ($integrationInput['backup_auth_token'] ?? '');
        $updates['backup_auth_token'] = $backupAuthToken;
        $form['integration']['backup_auth_token'] = $backupAuthToken;

        $backupSyncInterval = trim((string) ($integrationInput['backup_sync_interval'] ?? $form['integration']['backup_sync_interval'] ?? ''));
        $updates['backup_sync_interval'] = $backupSyncInterval !== '' ? $backupSyncInterval : 'daily';
        $form['integration']['backup_sync_interval'] = $updates['backup_sync_interval'];

        $backupApiKeysRaw = (string) ($integrationInput['backup_api_keys'] ?? '');
        $backupApiKeys = [];
        if ($backupApiKeysRaw !== '') {
            $lines = preg_split('/\r?\n/', $backupApiKeysRaw) ?: [];
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                if (!str_contains($line, '=')) {
                    $errors[] = t('system.validation.api_keys');
                    break;
                }
                [$name, $key] = array_map('trim', explode('=', $line, 2));
                if ($name === '' || $key === '') {
                    $errors[] = t('system.validation.api_keys');
                    break;
                }
                $backupApiKeys[] = ['name' => $name, 'key' => $key];
            }
        }
        if (!$errors) {
            $updates['backup_api_keys'] = $backupApiKeys;
            $form['integration']['backup_api_keys_raw'] = $backupApiKeysRaw;
        }

        $externalServicesRaw = (string) ($integrationInput['external_services'] ?? '');
        $externalServices = [];
        if ($externalServicesRaw !== '') {
            $lines = preg_split('/\r?\n/', $externalServicesRaw) ?: [];
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                if (!str_contains($line, '=')) {
                    $errors[] = t('system.validation.external_services');
                    break;
                }
                [$name, $value] = array_map('trim', explode('=', $line, 2));
                if ($name === '') {
                    $errors[] = t('system.validation.external_services');
                    break;
                }
                $externalServices[] = ['name' => $name, 'value' => $value];
            }
        }
        if (!$errors) {
            $updates['external_services'] = $externalServices;
            $form['integration']['external_services_raw'] = $externalServicesRaw;
        }

        // Display
        $displayInput = $input['display'] ?? [];
        $displayMode = (string) ($displayInput['mode'] ?? $form['display']['mode'] ?? SystemConfiguration::DISPLAY_MODE_AUTO);
        if (!in_array($displayMode, $displayModes, true)) {
            $errors[] = t('system.validation.display_mode');
        } else {
            $updates['display_mode'] = $displayMode;
            $form['display']['mode'] = $displayMode;
        }

        $clockFormat = (string) ($displayInput['clock_format'] ?? $form['display']['clock_format'] ?? SystemConfiguration::DISPLAY_CLOCK_24);
        if (!in_array($clockFormat, $displayClockFormats, true)) {
            $errors[] = t('system.validation.clock_format');
        } else {
            $updates['display_clock_format'] = $clockFormat;
            $form['display']['clock_format'] = $clockFormat;
        }

        $updates['display_show_seconds'] = !empty($displayInput['show_seconds']) ? '1' : '0';
        $form['display']['seconds'] = !empty($displayInput['show_seconds']);

        $updates['display_blink_colon'] = !empty($displayInput['blink_colon']) ? '1' : '0';
        $form['display']['blink_colon'] = !empty($displayInput['blink_colon']);

        $offlineMode = (string) ($displayInput['offline_mode'] ?? $form['display']['offline_mode'] ?? SystemConfiguration::DISPLAY_OFFLINE_CLIENT);
        if (!in_array($offlineMode, $displayOfflineModes, true)) {
            $errors[] = t('system.validation.offline_mode');
        } else {
            $updates['display_offline_mode'] = $offlineMode;
            $form['display']['offline_mode'] = $offlineMode;
        }

        $updates['display_time_overlay'] = !empty($displayInput['time_overlay']) ? '1' : '0';
        $form['display']['time_overlay'] = !empty($displayInput['time_overlay']);

        // System info
        $systemInput = $input['system'] ?? [];
        $version = trim((string) ($systemInput['version'] ?? $form['system']['version'] ?? ''));
        $updates['system_version'] = $version;
        $form['system']['version'] = $version;

        $build = trim((string) ($systemInput['build'] ?? $form['system']['build'] ?? ''));
        $updates['system_build'] = $build;
        $form['system']['build'] = $build;

        $licenseKey = trim((string) ($systemInput['license_key'] ?? $form['system']['license_key'] ?? ''));
        $updates['system_license_key'] = $licenseKey;
        $form['system']['license_key'] = $licenseKey;

        $logLevel = strtolower((string) ($systemInput['log_level'] ?? $form['system']['log_level'] ?? 'info'));
        if (!in_array($logLevel, $logLevels, true)) {
            $errors[] = t('system.validation.log_level');
        } else {
            $updates['log_level'] = $logLevel;
            $form['system']['log_level'] = $logLevel;
        }

        $updates['debug_enabled'] = !empty($systemInput['debug']) ? '1' : '0';
        $form['system']['debug'] = !empty($systemInput['debug']);

        $updates['developer_mode'] = !empty($systemInput['developer_mode']) ? '1' : '0';
        $form['system']['developer_mode'] = !empty($systemInput['developer_mode']);

        $customVariablesRaw = (string) ($systemInput['custom_variables'] ?? '');
        $customVariables = [];
        if ($customVariablesRaw !== '') {
            $lines = preg_split('/\r?\n/', $customVariablesRaw) ?: [];
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                if (!str_contains($line, '=')) {
                    $errors[] = t('system.validation.custom_variables');
                    break;
                }
                [$key, $value] = array_map('trim', explode('=', $line, 2));
                if ($key === '') {
                    $errors[] = t('system.validation.custom_variables');
                    break;
                }
                $customVariables[$key] = $value;
            }
        }
        if (!$errors) {
            $updates['custom_variables'] = $customVariables;
            $form['system']['custom_variables_raw'] = $customVariablesRaw;
        }

        // Theme
        $themeInput = $input['theme'] ?? [];
        $primaryColor = trim((string) ($themeInput['primary'] ?? $form['theme']['primary'] ?? ''));
        if ($primaryColor !== '' && !preg_match('/^#?[0-9a-fA-F]{3,6}$/', $primaryColor)) {
            $errors[] = t('system.validation.primary_color');
        } else {
            $updates['theme_primary_color'] = $primaryColor !== '' ? (str_starts_with($primaryColor, '#') ? $primaryColor : '#' . $primaryColor) : '#2b72ff';
            $form['theme']['primary'] = $updates['theme_primary_color'];
        }

        $secondaryColor = trim((string) ($themeInput['secondary'] ?? $form['theme']['secondary'] ?? ''));
        if ($secondaryColor !== '' && !preg_match('/^#?[0-9a-fA-F]{3,6}$/', $secondaryColor)) {
            $errors[] = t('system.validation.secondary_color');
        } else {
            $updates['theme_secondary_color'] = $secondaryColor !== '' ? (str_starts_with($secondaryColor, '#') ? $secondaryColor : '#' . $secondaryColor) : '#11131a';
            $form['theme']['secondary'] = $updates['theme_secondary_color'];
        }

        $logoUrl = trim((string) ($themeInput['logo'] ?? $form['theme']['logo'] ?? ''));
        if ($logoUrl !== '' && !filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            $errors[] = t('system.validation.logo_url');
        } else {
            $updates['theme_logo_url'] = $logoUrl;
            $form['theme']['logo'] = $logoUrl;
        }

        if (!$errors) {
            $changes = $systemConfig->save($updates);
            if (!empty($changes['after'])) {
                audit_log('system_settings', 0, 'system_update', $systemConfig->redact($changes['before']), $systemConfig->redact($changes['after']));
                system_refresh_view();
                flash('success', t('system.flash.saved'));
            } else {
                flash('info', t('system.flash.no_changes'));
            }
            header('Location: system.php');
            exit;
        }
    }
}

render_page('system.tpl', [
    'titleKey' => 'system.title',
    'page' => 'system',
    'form' => $form,
    'errors' => $errors,
    'timezones' => $timezones,
    'timeFormats' => $timeFormats,
    'dateFormats' => $dateFormats,
    'weekStarts' => $weekStarts,
    'displayModes' => $displayModes,
    'displayClockFormats' => $displayClockFormats,
    'displayOfflineModes' => $displayOfflineModes,
    'currencyFormats' => $currencyFormats,
    'lengthUnits' => $lengthUnits,
    'temperatureUnits' => $temperatureUnits,
    'databaseDrivers' => $databaseDrivers,
    'backupIntervals' => $backupIntervals,
    'backupLocations' => $backupLocations,
    'logLevels' => $logLevels,
]);

