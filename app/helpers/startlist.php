<?php

if (!function_exists('startlist_normalize_department')) {
    function startlist_normalize_department(?string $value): string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return '';
        }
        $collapsed = preg_replace('/\s+/', ' ', $trimmed);
        return function_exists('mb_strtolower') ? mb_strtolower($collapsed, 'UTF-8') : strtolower($collapsed);
    }
}

if (!function_exists('startlist_group_entries')) {
    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    function startlist_group_entries(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $departmentLabel = trim((string) ($item['department'] ?? ''));
            $normalized = $departmentLabel !== '' ? startlist_normalize_department($departmentLabel) : '';
            $key = $normalized !== '' ? 'department:' . $normalized : 'entry:' . (int) ($item['id'] ?? 0);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'department' => $departmentLabel !== '' ? $departmentLabel : null,
                    'department_normalized' => $normalized,
                    'type' => $normalized !== '' ? 'group' : 'single',
                    'members' => [],
                    'states' => [],
                    'start_numbers' => [],
                    'note' => null,
                    'planned_start' => null,
                    'has_locked_start_number' => false,
                ];
            }
            $groups[$key]['members'][] = $item;
            $state = $item['state'] ?? 'scheduled';
            $groups[$key]['states'][$state] = true;
            if (!empty($item['start_number_display'])) {
                $groups[$key]['start_numbers'][(string) $item['start_number_display']] = true;
            }
            if ($groups[$key]['note'] === null && isset($item['note']) && $item['note'] !== null && $item['note'] !== '') {
                $groups[$key]['note'] = (string) $item['note'];
            }
            if ($groups[$key]['planned_start'] === null && !empty($item['planned_start'])) {
                $groups[$key]['planned_start'] = $item['planned_start'];
            }
            if (!$groups[$key]['has_locked_start_number'] && !empty($item['start_number_locked_at'])) {
                $groups[$key]['has_locked_start_number'] = true;
            }
        }
        foreach ($groups as &$group) {
            usort($group['members'], static function (array $left, array $right): int {
                return ((int) ($left['position'] ?? 0)) <=> ((int) ($right['position'] ?? 0));
            });
            $group['primary'] = $group['members'][0] ?? null;
            $group['position'] = (int) ($group['primary']['position'] ?? 0);
            if ($group['planned_start'] === null && isset($group['primary']['planned_start'])) {
                $group['planned_start'] = $group['primary']['planned_start'];
            }
            if ($group['note'] === null) {
                foreach ($group['members'] as $member) {
                    if (!empty($member['note'])) {
                        $group['note'] = (string) $member['note'];
                        break;
                    }
                }
            }
            $states = array_keys($group['states']);
            $group['state'] = count($states) === 1 ? $states[0] : 'mixed';
            $group['state_list'] = $states;
            $group['start_numbers'] = array_values(array_keys($group['start_numbers']));
            $group['size'] = count($group['members']);
        }
        unset($group);
        usort($groups, static function (array $left, array $right): int {
            return ($left['position'] ?? 0) <=> ($right['position'] ?? 0);
        });
        return array_values($groups);
    }
}

if (!function_exists('startlist_find_group_for_item')) {
    /**
     * @param array<int, array<string, mixed>> $groups
     * @param int $itemId
     * @return array<string, mixed>|null
     */
    function startlist_find_group_for_item(array $groups, int $itemId): ?array
    {
        foreach ($groups as $group) {
            foreach ($group['members'] as $member) {
                if ((int) ($member['id'] ?? 0) === $itemId) {
                    return $group;
                }
            }
        }
        return null;
    }
}

if (!function_exists('startlist_resequence_positions')) {
    /**
     * @param array<int, array<string, mixed>> $groups
     * @return array<int, array{id:int,position:int}>
     */
    function startlist_resequence_positions(array $groups): array
    {
        $position = 1;
        $updates = [];
        foreach ($groups as $group) {
            foreach ($group['members'] as $member) {
                $memberId = (int) ($member['id'] ?? 0);
                if ($memberId <= 0) {
                    continue;
                }
                if ((int) ($member['position'] ?? 0) !== $position) {
                    $updates[] = ['id' => $memberId, 'position' => $position];
                }
                $position++;
            }
        }
        return $updates;
    }
}
