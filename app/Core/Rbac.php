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
        'dashboard.php' => [
            'key' => 'dashboard',
            'label_key' => 'nav.dashboard',
            'group' => 'overview',
            'priority' => 0,
            'tooltip_key' => 'nav.tooltips.dashboard',
            'subtitle_key' => 'nav.subtitles.dashboard',
        ],
        'persons.php' => [
            'key' => 'persons',
            'label_key' => 'nav.persons',
            'group' => 'management',
            'tooltip_key' => 'nav.tooltips.persons',
        ],
        'horses.php' => [
            'key' => 'horses',
            'label_key' => 'nav.horses',
            'group' => 'management',
            'tooltip_key' => 'nav.tooltips.horses',
        ],
        'clubs.php' => [
            'key' => 'clubs',
            'label_key' => 'nav.clubs',
            'group' => 'management',
            'tooltip_key' => 'nav.tooltips.clubs',
        ],
        'events.php' => [
            'key' => 'events',
            'label_key' => 'nav.events',
            'group' => 'management',
            'tooltip_key' => 'nav.tooltips.events',
            'variant' => 'secondary',
        ],
        'classes.php' => [
            'key' => 'classes',
            'label_key' => 'nav.classes',
            'group' => 'management',
            'tooltip_key' => 'nav.tooltips.classes',
            'variant' => 'secondary',
        ],
        'entries.php' => [
            'key' => 'entries',
            'label_key' => 'nav.entries',
            'group' => 'operations',
            'priority' => 5,
            'tooltip_key' => 'nav.tooltips.entries',
            'subtitle_key' => 'nav.subtitles.entries',
        ],
        'startlist.php' => [
            'key' => 'startlist',
            'label_key' => 'nav.startlists',
            'group' => 'operations',
            'priority' => 10,
            'tooltip_key' => 'nav.tooltips.startlist',
        ],
        'schedule.php' => [
            'key' => 'schedule',
            'label_key' => 'nav.schedule',
            'group' => 'operations',
            'priority' => 12,
            'tooltip_key' => 'nav.tooltips.schedule',
        ],
        'display.php' => [
            'key' => 'display',
            'label_key' => 'nav.display',
            'group' => 'operations',
            'tooltip_key' => 'nav.tooltips.display',
            'variant' => 'secondary',
        ],
        'judge.php' => [
            'key' => 'judge',
            'label_key' => 'nav.judge',
            'group' => 'operations',
            'priority' => 3,
            'tooltip_key' => 'nav.tooltips.judge',
            'subtitle_key' => 'nav.subtitles.judge',
        ],
        'results.php' => [
            'key' => 'results',
            'label_key' => 'nav.results',
            'group' => 'operations',
            'tooltip_key' => 'nav.tooltips.results',
        ],
        'helpers.php' => [
            'key' => 'helpers',
            'label_key' => 'nav.helpers',
            'group' => 'operations',
            'tooltip_key' => 'nav.tooltips.helpers',
        ],
        'print.php' => [
            'key' => 'print',
            'label_key' => 'nav.print',
            'group' => 'operations',
            'tooltip_key' => 'nav.tooltips.print',
            'variant' => 'secondary',
        ],
        'export.php' => [
            'key' => 'export',
            'label_key' => 'nav.export',
            'group' => 'management',
            'tooltip_key' => 'nav.tooltips.export',
            'variant' => 'secondary',
        ],
        'instance.php' => [
            'key' => 'instance',
            'label_key' => 'nav.instance',
            'group' => 'configuration',
            'priority' => 20,
            'tooltip_key' => 'nav.tooltips.instance',
        ],
        'sync_admin.php' => [
            'key' => 'sync',
            'label_key' => 'nav.sync',
            'group' => 'configuration',
            'tooltip_key' => 'nav.tooltips.sync',
            'variant' => 'secondary',
        ],
        'custom_fields.php' => [
            'key' => 'custom_fields',
            'label_key' => 'nav.custom_fields',
            'group' => 'configuration',
            'tooltip_key' => 'nav.tooltips.custom_fields',
            'variant' => 'secondary',
        ],
    ];

    private const GROUP_ORDER = [
        'overview' => 10,
        'management' => 20,
        'operations' => 30,
        'configuration' => 40,
    ];

    private const ROLE_MENU_OVERRIDES = [
        'admin' => [
            'priorities' => [
                'instance' => 2,
                'entries' => 6,
                'startlist' => 15,
            ],
        ],
        'office' => [
            'priorities' => [
                'entries' => 1,
                'startlist' => 4,
                'schedule' => 8,
                'results' => 12,
            ],
            'secondary' => ['print'],
        ],
        'judge' => [
            'group_order' => ['operations', 'overview', 'management', 'configuration'],
            'priorities' => [
                'judge' => 0,
                'schedule' => 4,
                'startlist' => 7,
            ],
            'secondary' => ['print', 'export', 'helpers', 'events', 'classes'],
        ],
        'steward' => [
            'group_order' => ['operations', 'overview', 'management', 'configuration'],
            'priorities' => [
                'schedule' => 0,
                'startlist' => 5,
                'display' => 8,
            ],
            'secondary' => ['print', 'export', 'helpers', 'results'],
        ],
        'helpers' => [
            'group_order' => ['operations', 'overview', 'management', 'configuration'],
            'priorities' => [
                'helpers' => 0,
                'schedule' => 6,
            ],
            'secondary' => ['results', 'print', 'export', 'display'],
        ],
        'announcer' => [
            'group_order' => ['operations', 'overview', 'management', 'configuration'],
            'priorities' => [
                'display' => 0,
                'schedule' => 4,
                'startlist' => 6,
            ],
            'secondary' => ['print', 'export', 'helpers', 'results'],
        ],
        'participant' => [
            'group_order' => ['overview', 'operations', 'management', 'configuration'],
            'priorities' => [
                'display' => 3,
                'results' => 5,
            ],
            'secondary' => ['entries', 'startlist', 'schedule', 'print', 'export', 'helpers', 'events', 'classes'],
        ],
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
            'href' => 'schedule.php?view=today',
            'label_key' => 'nav.quick.schedule_today',
        ],
        [
            'permissions' => ['schedule'],
            'href' => 'schedule.php#live-adjust',
            'label_key' => 'nav.quick.schedule_adjust',
        ],
        [
            'permissions' => ['judge'],
            'href' => 'judge.php',
            'label_key' => 'nav.quick.judge',
        ],
        [
            'permissions' => ['helpers'],
            'href' => 'helpers.php#check-in',
            'label_key' => 'nav.quick.helpers_checkin',
        ],
        [
            'permissions' => ['startlist'],
            'href' => 'startlist.php',
            'label_key' => 'nav.quick.startlist',
        ],
        [
            'permissions' => ['display'],
            'href' => 'display.php',
            'label_key' => 'nav.quick.display',
        ],
        [
            'permissions' => ['results'],
            'href' => 'results.php#pending-approvals',
            'label_key' => 'nav.quick.results_release',
        ],
    ];

    public static function allowed(string $role, string $permission): bool
    {
        $permissions = self::MAP[$permission] ?? [];
        return in_array($role, $permissions, true);
    }

    public static function menuFor(string $role): array
    {
        $items = array_filter(self::MENU, static fn ($item) => self::allowed($role, $item['key']));

        $groupOrder = self::GROUP_ORDER;
        $overrides = self::ROLE_MENU_OVERRIDES[$role] ?? [];

        if (isset($overrides['group_order'])) {
            $groupOrder = [];
            $weight = 0;
            foreach ($overrides['group_order'] as $group) {
                $groupOrder[$group] = $weight;
                $weight += 10;
            }
            foreach (self::GROUP_ORDER as $group => $defaultWeight) {
                if (!array_key_exists($group, $groupOrder)) {
                    $groupOrder[$group] = $weight;
                    $weight += 10;
                }
            }
        }

        foreach ($items as $path => &$item) {
            $item['variant'] = $item['variant'] ?? 'primary';
            $item['priority'] = $item['priority'] ?? 50;
            $item['group_priority'] = $groupOrder[$item['group']] ?? 999;

            if (isset($overrides['priorities'][$item['key']])) {
                $item['priority'] = $overrides['priorities'][$item['key']];
            }

            if (!empty($overrides['secondary']) && in_array($item['key'], $overrides['secondary'], true)) {
                $item['variant'] = 'secondary';
            }
        }
        unset($item);

        uasort($items, static function (array $left, array $right): int {
            $groupComparison = ($left['group_priority'] ?? 0) <=> ($right['group_priority'] ?? 0);
            if ($groupComparison !== 0) {
                return $groupComparison;
            }

            $priorityComparison = ($left['priority'] ?? 0) <=> ($right['priority'] ?? 0);
            if ($priorityComparison !== 0) {
                return $priorityComparison;
            }

            return strcmp($left['label_key'] ?? $left['key'], $right['label_key'] ?? $right['key']);
        });

        return $items;
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
