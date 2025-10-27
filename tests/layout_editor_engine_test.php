<?php
declare(strict_types=1);

use App\LayoutEditor\DummyDataProvider;
use App\LayoutEditor\Exceptions\TemplateSyntaxException;
use App\LayoutEditor\TemplateEngine;

require __DIR__ . '/../app/LayoutEditor/Exceptions/TemplateSyntaxException.php';
require __DIR__ . '/../app/LayoutEditor/TemplateEngine.php';
require __DIR__ . '/../app/LayoutEditor/DummyDataProvider.php';

function assertSame(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(($message ? $message . ': ' : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}

function assertTrue(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new RuntimeException($message ?: 'Assertion failed');
    }
}

$engine = new TemplateEngine();
$data = (new DummyDataProvider())->example();

$output = $engine->render('Hallo {{ person.name }}!', $data);
assertSame('Hallo Anna Schmidt!', $output, 'Simple variable rendering');

$output = $engine->render('{{#if person.is_competing}}Aktiv{{else}}Passiv{{/if}}', $data);
assertSame('Aktiv', trim($output), 'If branch should be used when condition is truthy');

$output = $engine->render('{{#if person.absent}}Aktiv{{else}}Passiv{{/if}}', $data);
assertSame('Passiv', trim($output), 'Else branch should render when condition is falsy');

$output = $engine->render('{{#each horses}}{{ name }},{{/each}}', $data);
assertSame('Golden Star,Blue Velvet,Calypso,', $output, 'Each loop should iterate array values');

$output = $engine->render('{{#each sponsors}}{{@index}}:{{ name }}|{{/each}}', $data);
assertSame('0:Sparkasse Lichtenau|1:Hofgut Sonnental|', $output, 'Each loop exposes @index helper');

try {
    $engine->render('{{#if person.name}}Ok', $data);
    throw new RuntimeException('Expected syntax exception was not thrown');
} catch (TemplateSyntaxException $exception) {
    assertTrue($exception->templateLine() >= 1, 'Syntax exception exposes line number');
}
