<?php
declare(strict_types=1);

namespace App\LayoutEditor;

class PlaceholderCatalog
{
    /**
     * @return array<int, array{group: string, items: array<int, array<string, string>>}>
     */
    public function groups(): array
    {
        return [
            [
                'group' => 'Person',
                'items' => [
                    $this->variable('person.name', 'Name', 'Anna Schmidt'),
                    $this->variable('person.club', 'Verein', 'RFV Beispielhof'),
                    $this->variable('person.nation', 'Nation', 'DEU'),
                    $this->variable('person.role', 'Rolle', 'Reiterin'),
                ],
            ],
            [
                'group' => 'Pferd',
                'items' => [
                    $this->variable('horse.name', 'Name', 'Golden Star'),
                    $this->variable('horse.breed', 'Rasse', 'Hannoveraner'),
                    $this->variable('horse.age', 'Alter', '9'),
                    $this->variable('horse.color', 'Farbe', 'Fuchs'),
                ],
            ],
            [
                'group' => 'Event',
                'items' => [
                    $this->variable('event.title', 'Titel', 'Sommerturnier Lichtenau'),
                    $this->variable('event.location', 'Ort', 'Reithalle Lichtenau'),
                    $this->variable('event.start_date', 'Startdatum', '2024-08-21'),
                    $this->variable('event.end_date', 'Enddatum', '2024-08-24'),
                    $this->variable('event.discipline', 'Disziplin', 'Dressur'),
                ],
            ],
            [
                'group' => 'Start',
                'items' => [
                    $this->variable('start.number', 'Startnummer', 'A12'),
                    $this->variable('start.position', 'Platzierung', '2'),
                    $this->variable('start.score', 'Punktzahl', '72.438'),
                    $this->variable('start.start_time', 'Startzeit', '12:05'),
                    $this->variable('start.status', 'Status', 'running'),
                ],
            ],
            [
                'group' => 'Listen & Teams',
                'items' => [
                    $this->block('each', 'horses', 'Pferdeliste', "{{#each horses}}\n  {{ name }} ({{ breed }})\n{{/each}}"),
                    $this->block('each', 'team.members', 'Teammitglieder', "{{#each team.members}}\n  • {{ this }}\n{{/each}}"),
                    $this->block('if', 'person.is_competing', 'Bedingung Teilnahme', "{{#if person.is_competing}}\n  Startet aktuell\n{{else}}\n  Nicht am Start\n{{/if}}"),
                ],
            ],
            [
                'group' => 'Sponsoren',
                'items' => [
                    $this->block('each', 'sponsors', 'Sponsorenliste', "{{#each sponsors}}\n  {{ name }} – {{ tier }}\n{{/each}}"),
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function variable(string $path, string $label, string $example): array
    {
        return [
            'type' => 'variable',
            'path' => $path,
            'label' => $label,
            'example' => $example,
            'insert' => '{{ ' . $path . ' }}',
            'search' => strtolower($path . ' ' . $label . ' ' . $example),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function block(string $kind, string $path, string $label, string $snippet): array
    {
        $prefix = $kind === 'if' ? '#if ' : '#each ';
        $search = strtolower($path . ' ' . $label . ' ' . $snippet);
        return [
            'type' => $kind,
            'path' => $path,
            'label' => $label,
            'example' => '',
            'insert' => $snippet,
            'search' => $search,
            'hint' => '{{' . $prefix . $path . '}}',
        ];
    }
}
