<?php
declare(strict_types=1);

namespace App\LayoutEditor;

class DummyDataProvider
{
    public function example(): array
    {
        return [
            'event' => [
                'title' => 'Sommerturnier Lichtenau',
                'location' => 'Reithalle Lichtenau',
                'start_date' => '2024-08-21',
                'end_date' => '2024-08-24',
                'discipline' => 'Dressur',
            ],
            'person' => [
                'name' => 'Anna Schmidt',
                'club' => 'RFV Beispielhof',
                'nation' => 'DEU',
                'age' => 27,
                'is_competing' => true,
                'role' => 'Reiterin',
            ],
            'horse' => [
                'name' => 'Golden Star',
                'breed' => 'Hannoveraner',
                'age' => 9,
                'color' => 'Fuchs',
            ],
            'horses' => [
                [
                    'name' => 'Golden Star',
                    'breed' => 'Hannoveraner',
                    'age' => 9,
                ],
                [
                    'name' => 'Blue Velvet',
                    'breed' => 'Oldenburger',
                    'age' => 11,
                ],
                [
                    'name' => 'Calypso',
                    'breed' => 'Holsteiner',
                    'age' => 7,
                ],
            ],
            'start' => [
                'number' => 'A12',
                'position' => 2,
                'score' => 72.438,
                'start_time' => '12:05',
                'status' => 'running',
            ],
            'team' => [
                'name' => 'Team Westfalen',
                'members' => ['Anna Schmidt', 'Lena Müller', 'Johannes Wolf'],
            ],
            'sponsors' => [
                ['name' => 'Sparkasse Lichtenau', 'tier' => 'Premium'],
                ['name' => 'Hofgut Sonnental', 'tier' => 'Partner'],
            ],
        ];
    }
}
