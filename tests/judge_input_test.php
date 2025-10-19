<?php
declare(strict_types=1);

require __DIR__ . '/../app/helpers/judge.php';

function assertFloatEquals(float $expected, ?float $actual, string $message = ''): void
{
    if ($actual === null || abs($expected - $actual) > 0.00001) {
        throw new RuntimeException(($message ? $message . ': ' : '') . 'expected ' . $expected . ' got ' . var_export($actual, true));
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(($message ? $message . ': ' : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}

$definitions = [
    ['id' => 'points', 'type' => 'number'],
    ['id' => 'time_s', 'type' => 'time'],
    ['id' => 'note', 'type' => 'text'],
];

$parsedFields = judge_parse_fields($definitions, [
    'points' => '7,5',
    'time_s' => '83,25',
    'note' => 'OK',
]);
assertFloatEquals(7.5, $parsedFields['points'], 'Kommawert sollte erkannt werden');
assertFloatEquals(83.25, $parsedFields['time_s'], 'Zeitfeld sollte Dezimalwerte akzeptieren');
assertSameValue('OK', $parsedFields['note'], 'Textfeld bleibt unverändert');

$normalizedFields = judge_normalize_field_values($definitions, [
    'points' => '6,75',
    'time_s' => '59,5',
    'note' => null,
]);
assertFloatEquals(6.75, $normalizedFields['points'], 'Normalisierung unterstützt Komma');
assertFloatEquals(59.5, $normalizedFields['time_s'], 'Normalisierung für Zeitfelder');
assertSameValue(null, $normalizedFields['note'], 'Leere Texte bleiben null');

$componentDefinitions = [
    ['id' => 'A'],
    ['id' => 'B'],
];
$parsedComponents = judge_parse_components($componentDefinitions, [
    'A' => '8,25',
    'B' => '',
]);
assertFloatEquals(8.25, $parsedComponents['A'], 'Komponentenwerte aus Kommazahlen');
assertSameValue(null, $parsedComponents['B'], 'Leere Komponenten werden zu null');

$normalizedComponents = judge_normalize_component_values($componentDefinitions, [
    'A' => '9,0',
    'B' => '1.234,5',
]);
assertFloatEquals(9.0, $normalizedComponents['A'], 'Normalisierte Komponenten übernehmen Werte');
assertFloatEquals(1234.5, $normalizedComponents['B'], 'Tausendertrennung und Komma werden korrigiert');

echo "Judge input tests passed\n";
