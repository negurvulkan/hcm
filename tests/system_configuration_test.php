<?php
declare(strict_types=1);

use App\Services\SystemConfiguration;

require __DIR__ . '/../app/Services/SystemConfiguration.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE system_settings (setting_key TEXT PRIMARY KEY, value TEXT, updated_at TEXT NOT NULL)');

$config = new SystemConfiguration($pdo);

if ($config->timezone() !== 'Europe/Berlin') {
    throw new RuntimeException('Default timezone should be Europe/Berlin.');
}
if ($config->currencyCode() !== 'EUR') {
    throw new RuntimeException('Default currency should be EUR.');
}
if ($config->displayClockPattern() !== 'HH:mm') {
    throw new RuntimeException('Default clock pattern should be HH:mm.');
}

$changes = $config->save([
    'time_timezone' => 'Europe/Paris',
    'currency_default' => 'USD',
    'currency_rounding' => 2,
    'vat_rates' => [19, 7],
    'display_clock_format' => SystemConfiguration::DISPLAY_CLOCK_12,
    'display_show_seconds' => '1',
    'theme_primary_color' => '#ffffff',
]);

if (($changes['after']['time_timezone'] ?? null) !== 'Europe/Paris') {
    throw new RuntimeException('Timezone update not persisted.');
}
if ($config->timezone() !== 'Europe/Paris') {
    throw new RuntimeException('Timezone getter not updated.');
}
if ($config->currencyCode() !== 'USD') {
    throw new RuntimeException('Currency code not updated.');
}
if ($config->displayClockPattern() !== 'hh:mm:ss a') {
    throw new RuntimeException('Clock pattern should honour 12h with seconds.');
}

$context = $config->viewContext();
if (($context['display']['clock_format'] ?? '') !== SystemConfiguration::DISPLAY_CLOCK_12) {
    throw new RuntimeException('View context did not expose clock format.');
}
if (($context['display']['seconds'] ?? false) !== true) {
    throw new RuntimeException('View context did not expose seconds flag.');
}
if (count($config->vatRates()) !== 2) {
    throw new RuntimeException('VAT rates not stored as list.');
}

echo "SystemConfiguration tests passed\n";

