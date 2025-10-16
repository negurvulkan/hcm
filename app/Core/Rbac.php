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
        'instance' => ['admin'],
        'sync' => ['admin'],
    ];

    public static function allowed(string $role, string $permission): bool
    {
        $permissions = self::MAP[$permission] ?? [];
        return in_array($role, $permissions, true);
    }

    public static function menuFor(string $role): array
    {
        $menu = [
            'dashboard.php' => ['key' => 'dashboard', 'label_key' => 'nav.dashboard'],
            'persons.php' => ['key' => 'persons', 'label_key' => 'nav.persons'],
            'horses.php' => ['key' => 'horses', 'label_key' => 'nav.horses'],
            'clubs.php' => ['key' => 'clubs', 'label_key' => 'nav.clubs'],
            'events.php' => ['key' => 'events', 'label_key' => 'nav.events'],
            'classes.php' => ['key' => 'classes', 'label_key' => 'nav.classes'],
            'entries.php' => ['key' => 'entries', 'label_key' => 'nav.entries'],
            'startlist.php' => ['key' => 'startlist', 'label_key' => 'nav.startlists'],
            'schedule.php' => ['key' => 'schedule', 'label_key' => 'nav.schedule'],
            'display.php' => ['key' => 'display', 'label_key' => 'nav.display'],
            'judge.php' => ['key' => 'judge', 'label_key' => 'nav.judge'],
            'results.php' => ['key' => 'results', 'label_key' => 'nav.results'],
            'helpers.php' => ['key' => 'helpers', 'label_key' => 'nav.helpers'],
            'print.php' => ['key' => 'print', 'label_key' => 'nav.print'],
            'export.php' => ['key' => 'export', 'label_key' => 'nav.export'],
            'instance.php' => ['key' => 'instance', 'label_key' => 'nav.instance'],
            'sync_admin.php' => ['key' => 'sync', 'label_key' => 'nav.sync'],
        ];

        return array_filter($menu, static fn ($item) => self::allowed($role, $item['key']));
    }
}
