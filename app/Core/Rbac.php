<?php
namespace App\Core;

class Rbac
{
    public const ROLES = [
        'admin',
        'office',
        'judge',
        'steward',
        'helpers',
        'announcer',
        'participant',
    ];

    private const MAP = [
        'dashboard' => ['admin', 'office', 'judge', 'steward', 'helpers', 'announcer', 'participant'],
        'persons' => ['admin', 'office'],
        'horses' => ['admin', 'office'],
        'clubs' => ['admin', 'office'],
        'events' => ['admin', 'office', 'steward'],
        'classes' => ['admin', 'office', 'steward'],
        'entries' => ['admin', 'office'],
        'startlist' => ['admin', 'office', 'steward', 'announcer'],
        'schedule' => ['admin', 'steward', 'announcer'],
        'display' => ['admin', 'steward', 'announcer', 'participant'],
        'judge' => ['admin', 'judge', 'steward'],
        'results' => ['admin', 'office', 'steward'],
        'helpers' => ['admin', 'helpers', 'office'],
        'print' => ['admin', 'office', 'steward'],
        'export' => ['admin', 'office'],
        'audit' => ['admin'],
    ];

    public static function allowed(string $role, string $permission): bool
    {
        $permissions = self::MAP[$permission] ?? [];
        return in_array($role, $permissions, true);
    }

    public static function menuFor(string $role): array
    {
        $menu = [
            'dashboard.php' => ['key' => 'dashboard', 'label' => 'Dashboard'],
            'persons.php' => ['key' => 'persons', 'label' => 'Personen'],
            'horses.php' => ['key' => 'horses', 'label' => 'Pferde'],
            'clubs.php' => ['key' => 'clubs', 'label' => 'Vereine'],
            'events.php' => ['key' => 'events', 'label' => 'Turniere'],
            'classes.php' => ['key' => 'classes', 'label' => 'Prüfungen'],
            'entries.php' => ['key' => 'entries', 'label' => 'Nennungen'],
            'startlist.php' => ['key' => 'startlist', 'label' => 'Startlisten'],
            'schedule.php' => ['key' => 'schedule', 'label' => 'Zeitplan'],
            'display.php' => ['key' => 'display', 'label' => 'Anzeige'],
            'judge.php' => ['key' => 'judge', 'label' => 'Richten'],
            'results.php' => ['key' => 'results', 'label' => 'Ergebnisse'],
            'helpers.php' => ['key' => 'helpers', 'label' => 'Helfer'],
            'print.php' => ['key' => 'print', 'label' => 'Druck'],
            'export.php' => ['key' => 'export', 'label' => 'Export'],
        ];

        return array_filter($menu, static fn ($item) => self::allowed($role, $item['key']));
    }
}
