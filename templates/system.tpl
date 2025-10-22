<?php
/** @var array $form */
/** @var array $errors */
/** @var array $timezones */
/** @var array $timeFormats */
/** @var array $dateFormats */
/** @var array $weekStarts */
/** @var array $displayModes */
/** @var array $displayClockFormats */
/** @var array $displayOfflineModes */
/** @var array $currencyFormats */
/** @var array $lengthUnits */
/** @var array $temperatureUnits */
/** @var array $databaseDrivers */
/** @var array $backupIntervals */
/** @var array $backupLocations */
/** @var array $logLevels */

$time = $form['time'] ?? [];
$locale = $form['locale'] ?? [];
$currency = $form['currency'] ?? [];
$integration = $form['integration'] ?? [];
$display = $form['display'] ?? [];
$systemMeta = $form['system'] ?? [];
$theme = $form['theme'] ?? [];
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4">
        <div class="mb-3 mb-lg-0">
            <h1 class="h3 mb-1"><?= htmlspecialchars(t('system.title'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-muted mb-0"><?= htmlspecialchars(t('system.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="text-muted small">
            <?= htmlspecialchars(t('system.instance_summary', [
                'timezone' => $time['timezone'] ?? date_default_timezone_get(),
                'locale' => $locale['preferred'] ?? current_locale(),
                'currency' => $currency['code'] ?? 'EUR',
            ]), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2"><?= htmlspecialchars(t('system.validation.title'), ENT_QUOTES, 'UTF-8') ?></div>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0"><?= htmlspecialchars(t('system.sections.time'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="badge bg-secondary"><?= htmlspecialchars(t('system.section_tags.core'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="system-time-timezone"><?= htmlspecialchars(t('system.fields.timezone'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select class="form-select" id="system-time-timezone" name="settings[time][timezone]">
                                <?php foreach ($timezones as $timezone): ?>
                                    <option value="<?= htmlspecialchars($timezone, ENT_QUOTES, 'UTF-8') ?>" <?= ($time['timezone'] ?? '') === $timezone ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($timezone, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="system-time-format"><?= htmlspecialchars(t('system.fields.time_format'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-time-format" name="settings[time][format]">
                                    <?php foreach ($timeFormats as $format): ?>
                                        <option value="<?= htmlspecialchars($format, ENT_QUOTES, 'UTF-8') ?>" <?= ($time['format'] ?? '') === $format ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.time_format.option', ['format' => $format]), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="system-date-format"><?= htmlspecialchars(t('system.fields.date_format'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-date-format" name="settings[time][date_format]">
                                    <?php foreach ($dateFormats as $format): ?>
                                        <option value="<?= htmlspecialchars($format, ENT_QUOTES, 'UTF-8') ?>" <?= ($time['date_format'] ?? '') === $format ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.date_format.option', ['format' => $format]), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="system-week-start"><?= htmlspecialchars(t('system.fields.week_start'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-week-start" name="settings[time][week_start]">
                                    <?php foreach ($weekStarts as $option): ?>
                                        <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= ($time['week_start'] ?? '') === $option ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.week_start.' . strtolower($option)), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="system-time-offset"><?= htmlspecialchars(t('system.fields.offset'), ENT_QUOTES, 'UTF-8') ?></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="system-time-offset" name="settings[time][offset]" value="<?= htmlspecialchars((string) ($time['offset_minutes'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="input-group-text"><?= htmlspecialchars(t('system.fields.offset_unit'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="form-text text-muted"><?= htmlspecialchars(t('system.fields.offset_help'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="system-time-daylight" name="settings[time][daylight_saving]" value="1" <?= !empty($time['daylight_saving']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="system-time-daylight"><?= htmlspecialchars(t('system.fields.daylight_saving'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="system-time-sync" name="settings[time][sync_display]" value="1" <?= !empty($time['sync_display']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="system-time-sync"><?= htmlspecialchars(t('system.fields.sync_display'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="system-time-live" name="settings[time][live_clock]" value="1" <?= !empty($time['live_clock']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="system-time-live"><?= htmlspecialchars(t('system.fields.live_clock'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0"><?= htmlspecialchars(t('system.sections.locale'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="badge bg-secondary"><?= htmlspecialchars(t('system.section_tags.locale'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="system-locale-preferred"><?= htmlspecialchars(t('system.fields.locale'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" class="form-control" id="system-locale-preferred" name="settings[locale][preferred]" value="<?= htmlspecialchars($locale['preferred'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="de_DE">
                            <div class="form-text"><?= htmlspecialchars(t('system.fields.locale_help'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="system-locale-decimal"><?= htmlspecialchars(t('system.fields.decimal_separator'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" maxlength="1" class="form-control" id="system-locale-decimal" name="settings[locale][decimal_separator]" value="<?= htmlspecialchars($locale['decimal'] ?? ',', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-locale-thousand"><?= htmlspecialchars(t('system.fields.thousand_separator'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" maxlength="1" class="form-control" id="system-locale-thousand" name="settings[locale][thousand_separator]" value="<?= htmlspecialchars($locale['thousand'] ?? '.', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-locale-datetime"><?= htmlspecialchars(t('system.fields.datetime_separator'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" maxlength="2" class="form-control" id="system-locale-datetime" name="settings[locale][datetime_separator]" value="<?= htmlspecialchars($locale['datetime_separator'] ?? ' ', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="system-locale-example"><?= htmlspecialchars(t('system.fields.number_example'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" class="form-control" id="system-locale-example" name="settings[locale][number_example]" value="<?= htmlspecialchars($locale['number_example'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="1.234,56">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="system-locale-collation"><?= htmlspecialchars(t('system.fields.collation'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" class="form-control" id="system-locale-collation" name="settings[locale][collation]" value="<?= htmlspecialchars($locale['collation'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="de_DE">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="system-locale-length"><?= htmlspecialchars(t('system.fields.length_unit'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-locale-length" name="settings[locale][unit_length]">
                                    <?php foreach ($lengthUnits as $unit): ?>
                                        <option value="<?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?>" <?= ($locale['unit_length'] ?? '') === $unit ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.units.length.' . $unit), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="system-locale-temperature"><?= htmlspecialchars(t('system.fields.temperature_unit'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-locale-temperature" name="settings[locale][unit_temperature]">
                                    <?php foreach ($temperatureUnits as $unit): ?>
                                        <option value="<?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?>" <?= ($locale['unit_temperature'] ?? '') === $unit ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.units.temperature.' . $unit), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0"><?= htmlspecialchars(t('system.sections.currency'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="badge bg-secondary"><?= htmlspecialchars(t('system.section_tags.finance'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="system-currency-code"><?= htmlspecialchars(t('system.fields.currency_code'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control text-uppercase" id="system-currency-code" maxlength="3" name="settings[currency][code]" value="<?= htmlspecialchars($currency['code'] ?? 'EUR', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-currency-format"><?= htmlspecialchars(t('system.fields.currency_format'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-currency-format" name="settings[currency][format]">
                                    <?php foreach ($currencyFormats as $format): ?>
                                        <option value="<?= htmlspecialchars($format, ENT_QUOTES, 'UTF-8') ?>" <?= ($currency['format'] ?? '') === $format ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.currency.format.' . $format), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-currency-price-format"><?= htmlspecialchars(t('system.fields.price_format'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-currency-price-format" name="settings[currency][price_list_format]">
                                    <?php foreach ($currencyFormats as $format): ?>
                                        <option value="<?= htmlspecialchars($format, ENT_QUOTES, 'UTF-8') ?>" <?= ($currency['price_list_format'] ?? '') === $format ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.currency.format.' . $format), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="system-currency-decimals"><?= htmlspecialchars(t('system.fields.currency_decimals'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="number" min="0" max="4" class="form-control" id="system-currency-decimals" name="settings[currency][decimals]" value="<?= htmlspecialchars((string) ($currency['decimals'] ?? 2), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="system-currency-payment"><?= htmlspecialchars(t('system.fields.payment_terms'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-currency-payment" name="settings[currency][payment_terms]" value="<?= htmlspecialchars($currency['payment_terms'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="14 Tage">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="system-currency-vat"><?= htmlspecialchars(t('system.fields.vat_rates'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea class="form-control" id="system-currency-vat" name="settings[currency][vat_rates]" rows="3" placeholder="19
7"><?= htmlspecialchars($currency['vat_rates_raw'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text"><?= htmlspecialchars(t('system.fields.vat_help'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0"><?= htmlspecialchars(t('system.sections.integration'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="badge bg-secondary"><?= htmlspecialchars(t('system.section_tags.integration'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="system-db-driver"><?= htmlspecialchars(t('system.fields.db_driver'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-db-driver" name="settings[integration][driver]">
                                    <?php foreach ($databaseDrivers as $driver): ?>
                                        <option value="<?= htmlspecialchars($driver, ENT_QUOTES, 'UTF-8') ?>" <?= ($integration['driver'] ?? '') === $driver ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.db.driver.' . $driver), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-db-host"><?= htmlspecialchars(t('system.fields.db_host'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-db-host" name="settings[integration][host]" value="<?= htmlspecialchars($integration['host'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-db-port"><?= htmlspecialchars(t('system.fields.db_port'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-db-port" name="settings[integration][port]" value="<?= htmlspecialchars($integration['port'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="system-db-name"><?= htmlspecialchars(t('system.fields.db_name'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-db-name" name="settings[integration][name]" value="<?= htmlspecialchars($integration['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="system-db-user"><?= htmlspecialchars(t('system.fields.db_user'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-db-user" name="settings[integration][user]" value="<?= htmlspecialchars($integration['user'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="system-db-password"><?= htmlspecialchars(t('system.fields.db_password'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="password" class="form-control" id="system-db-password" name="settings[integration][password]" value="<?= htmlspecialchars($integration['db_password'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="system-db-prefix"><?= htmlspecialchars(t('system.fields.db_prefix'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-db-prefix" name="settings[integration][table_prefix]" value="<?= htmlspecialchars($integration['table_prefix'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="system-backup-enabled" name="settings[integration][backup_enabled]" value="1" <?= !empty($integration['backup_enabled']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="system-backup-enabled"><?= htmlspecialchars(t('system.fields.backup_enabled'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-backup-interval"><?= htmlspecialchars(t('system.fields.backup_interval'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-backup-interval" name="settings[integration][backup_interval]">
                                    <?php foreach ($backupIntervals as $interval): ?>
                                        <option value="<?= htmlspecialchars($interval, ENT_QUOTES, 'UTF-8') ?>" <?= ($integration['backup_interval'] ?? '') === $interval ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.backup.interval.' . $interval), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-backup-location"><?= htmlspecialchars(t('system.fields.backup_location'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-backup-location" name="settings[integration][backup_location]">
                                    <?php foreach ($backupLocations as $location): ?>
                                        <option value="<?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?>" <?= ($integration['backup_location'] ?? '') === $location ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.backup.location.' . $location), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="system-backup-url"><?= htmlspecialchars(t('system.fields.backup_url'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" class="form-control" id="system-backup-url" name="settings[integration][backup_server_url]" value="<?= htmlspecialchars($integration['backup_server_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="system-backup-token"><?= htmlspecialchars(t('system.fields.backup_token'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" class="form-control" id="system-backup-token" name="settings[integration][backup_auth_token]" value="<?= htmlspecialchars($integration['backup_auth_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="system-backup-sync"><?= htmlspecialchars(t('system.fields.backup_sync_interval'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" class="form-control" id="system-backup-sync" name="settings[integration][backup_sync_interval]" value="<?= htmlspecialchars($integration['backup_sync_interval'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="daily">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="system-backup-api"><?= htmlspecialchars(t('system.fields.backup_api_keys'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea class="form-control" id="system-backup-api" name="settings[integration][backup_api_keys]" rows="3" placeholder="Display=ABC123
Player=XYZ789"><?= htmlspecialchars($integration['backup_api_keys_raw'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text"><?= htmlspecialchars(t('system.fields.backup_api_help'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label" for="system-external-services"><?= htmlspecialchars(t('system.fields.external_services'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea class="form-control" id="system-external-services" name="settings[integration][external_services]" rows="3" placeholder="maps=apikey
weather=token"><?= htmlspecialchars($integration['external_services_raw'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text"><?= htmlspecialchars(t('system.fields.external_services_help'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0"><?= htmlspecialchars(t('system.sections.display'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="badge bg-secondary"><?= htmlspecialchars(t('system.section_tags.display'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="system-display-mode"><?= htmlspecialchars(t('system.fields.display_mode'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-display-mode" name="settings[display][mode]">
                                    <?php foreach ($displayModes as $mode): ?>
                                        <option value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" <?= ($display['mode'] ?? '') === $mode ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.display.mode.' . $mode), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-display-clock"><?= htmlspecialchars(t('system.fields.clock_format'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-display-clock" name="settings[display][clock_format]">
                                    <?php foreach ($displayClockFormats as $format): ?>
                                        <option value="<?= htmlspecialchars($format, ENT_QUOTES, 'UTF-8') ?>" <?= ($display['clock_format'] ?? '') === $format ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.display.clock.' . $format), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-display-offline"><?= htmlspecialchars(t('system.fields.offline_mode'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-display-offline" name="settings[display][offline_mode]">
                                    <?php foreach ($displayOfflineModes as $mode): ?>
                                        <option value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" <?= ($display['offline_mode'] ?? '') === $mode ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.display.offline.' . $mode), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="system-display-seconds" name="settings[display][show_seconds]" value="1" <?= !empty($display['seconds']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="system-display-seconds"><?= htmlspecialchars(t('system.fields.show_seconds'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="system-display-blink" name="settings[display][blink_colon]" value="1" <?= !empty($display['blink_colon']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="system-display-blink"><?= htmlspecialchars(t('system.fields.blink_colon'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="system-display-overlay" name="settings[display][time_overlay]" value="1" <?= !empty($display['time_overlay']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="system-display-overlay"><?= htmlspecialchars(t('system.fields.time_overlay'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0"><?= htmlspecialchars(t('system.sections.system_info'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="badge bg-secondary"><?= htmlspecialchars(t('system.section_tags.meta'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="system-version"><?= htmlspecialchars(t('system.fields.version'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-version" name="settings[system][version]" value="<?= htmlspecialchars($systemMeta['version'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-build"><?= htmlspecialchars(t('system.fields.build'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-build" name="settings[system][build]" value="<?= htmlspecialchars($systemMeta['build'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="system-license"><?= htmlspecialchars(t('system.fields.license_key'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-license" name="settings[system][license_key]" value="<?= htmlspecialchars($systemMeta['license_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="system-log-level"><?= htmlspecialchars(t('system.fields.log_level'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" id="system-log-level" name="settings[system][log_level]">
                                    <?php foreach ($logLevels as $level): ?>
                                        <option value="<?= htmlspecialchars($level, ENT_QUOTES, 'UTF-8') ?>" <?= ($systemMeta['log_level'] ?? '') === $level ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(t('system.log.level.' . $level), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="system-debug" name="settings[system][debug]" value="1" <?= !empty($systemMeta['debug']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="system-debug"><?= htmlspecialchars(t('system.fields.debug'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="system-developer" name="settings[system][developer_mode]" value="1" <?= !empty($systemMeta['developer_mode']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="system-developer"><?= htmlspecialchars(t('system.fields.developer_mode'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label" for="system-custom-variables"><?= htmlspecialchars(t('system.fields.custom_variables'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea class="form-control" id="system-custom-variables" name="settings[system][custom_variables]" rows="4" placeholder="feature_flag=true
branding=classic"><?= htmlspecialchars($systemMeta['custom_variables_raw'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text"><?= htmlspecialchars(t('system.fields.custom_variables_help'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0"><?= htmlspecialchars(t('system.sections.theme'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="badge bg-secondary"><?= htmlspecialchars(t('system.section_tags.branding'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="system-theme-primary"><?= htmlspecialchars(t('system.fields.theme_primary'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-theme-primary" name="settings[theme][primary]" value="<?= htmlspecialchars($theme['primary'] ?? '#2b72ff', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="system-theme-secondary"><?= htmlspecialchars(t('system.fields.theme_secondary'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" id="system-theme-secondary" name="settings[theme][secondary]" value="<?= htmlspecialchars($theme['secondary'] ?? '#11131a', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="system-theme-logo"><?= htmlspecialchars(t('system.fields.theme_logo'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" class="form-control" id="system-theme-logo" name="settings[theme][logo]" value="<?= htmlspecialchars($theme['logo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="https://...">
                        </div>
                        <div class="alert alert-info small mb-0" role="alert">
                            <?= htmlspecialchars(t('system.theme.preview_hint'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-3 mt-4">
            <a href="dashboard.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('system.actions.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
            <button type="submit" class="btn btn-primary">
                <span class="me-2" aria-hidden="true">💾</span>
                <?= htmlspecialchars(t('system.actions.save'), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </div>
    </form>
</div>

