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
        'custom_fields' => ['admin', 'office'],
    ];

    private const MENU = [
        'dashboard.php' => ['key' => 'dashboard', 'label_key' => 'nav.dashboard', 'group' => 'overview'],
        'persons.php' => ['key' => 'persons', 'label_key' => 'nav.persons', 'group' => 'management'],
        'horses.php' => ['key' => 'horses', 'label_key' => 'nav.horses', 'group' => 'management'],
        'clubs.php' => ['key' => 'clubs', 'label_key' => 'nav.clubs', 'group' => 'management'],
        'events.php' => ['key' => 'events', 'label_key' => 'nav.events', 'group' => 'management'],
        'classes.php' => ['key' => 'classes', 'label_key' => 'nav.classes', 'group' => 'management'],
        'entries.php' => ['key' => 'entries', 'label_key' => 'nav.entries', 'group' => 'operations'],
        'startlist.php' => ['key' => 'startlist', 'label_key' => 'nav.startlists', 'group' => 'operations'],
        'schedule.php' => ['key' => 'schedule', 'label_key' => 'nav.schedule', 'group' => 'operations'],
        'display.php' => ['key' => 'display', 'label_key' => 'nav.display', 'group' => 'operations'],
        'judge.php' => ['key' => 'judge', 'label_key' => 'nav.judge', 'group' => 'operations'],
        'results.php' => ['key' => 'results', 'label_key' => 'nav.results', 'group' => 'operations'],
        'helpers.php' => ['key' => 'helpers', 'label_key' => 'nav.helpers', 'group' => 'operations'],
        'print.php' => ['key' => 'print', 'label_key' => 'nav.print', 'group' => 'operations'],
        'export.php' => ['key' => 'export', 'label_key' => 'nav.export', 'group' => 'management'],
        'instance.php' => ['key' => 'instance', 'label_key' => 'nav.instance', 'group' => 'configuration'],
        'sync_admin.php' => ['key' => 'sync', 'label_key' => 'nav.sync', 'group' => 'configuration'],
        'custom_fields.php' => ['key' => 'custom_fields', 'label_key' => 'nav.custom_fields', 'group' => 'configuration'],
    ];

    private const QUICK_ACTIONS = [
        [
            'permissions' => ['entries'],
            'href' => 'entries.php#entry-form',
            'label_key' => 'nav.quick.entries_create',
        ],
        [
            'permissions' => ['entries'],
            'href' => 'entries.php#import-section',
            'label_key' => 'nav.quick.entries_import',
        ],
        [
            'permissions' => ['schedule'],
            'href' => 'schedule.php',
            'label_key' => 'nav.quick.schedule_today',
        ],
        [
            'permissions' => ['judge'],
            'href' => 'judge.php',
            'label_key' => 'nav.quick.judge',
        ],
        [
            'permissions' => ['startlist'],
            'href' => 'startlist.php',
            'label_key' => 'nav.quick.startlist',
        ],
    ];

    public static function allowed(string $role, string $permission): bool
    {
        $permissions = self::MAP[$permission] ?? [];
        return in_array($role, $permissions, true);
    }

    public static function menuFor(string $role): array
    {
        return array_filter(self::MENU, static fn ($item) => self::allowed($role, $item['key']));
    }

    public static function quickActionsFor(string $role): array
    {
        $actions = [];
        foreach (self::QUICK_ACTIONS as $action) {
            foreach ($action['permissions'] as $permission) {
                if (self::allowed($role, $permission)) {
                    $actions[] = $action;
                    break;
                }
            }
        }

        return $actions;
    }
}
