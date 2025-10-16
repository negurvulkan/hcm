<?php
declare(strict_types=1);

use App\I18n\LocaleManager;
use App\I18n\Translator;

require __DIR__ . '/../app/I18n/LocaleManager.php';
require __DIR__ . '/../app/I18n/Translator.php';

$_SESSION = [];
$_COOKIE = [];
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9,de;q=0.8';

$manager = new LocaleManager(['de', 'en']);
$locale = $manager->detect();
if ($locale !== 'en') {
    throw new RuntimeException('Locale detection should prefer English.');
}

if (!isset($_SESSION['_locale']) || $_SESSION['_locale'] !== 'en') {
    throw new RuntimeException('Locale should be stored in session.');
}

$translator = new Translator('en', 'de', __DIR__ . '/../lang');
if ($translator->translate('nav.dashboard') !== 'Dashboard') {
    throw new RuntimeException('Translation should return dashboard label.');
}

$pluralOne = $translator->translatePlural('tests.items', 1);
if ($pluralOne !== '1 item') {
    throw new RuntimeException('Pluralization for one failed: ' . $pluralOne);
}

$pluralMany = $translator->translatePlural('tests.items', 3);
if ($pluralMany !== '3 items') {
    throw new RuntimeException('Pluralization for many failed: ' . $pluralMany);
}

$missing = $translator->translate('non.existent.key');
if ($missing !== '[[non.existent.key]]') {
    throw new RuntimeException('Missing keys should be wrapped.');
}

echo "i18n tests passed\n";
