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

if (!function_exists('startlist_sanitize_department_label')) {
    function startlist_sanitize_department_label(?string $value): string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return '';
        }
        $collapsed = preg_replace('/\s+/', ' ', $trimmed);
        if (function_exists('mb_substr')) {
            return mb_substr($collapsed, 0, 120);
        }
        return substr($collapsed, 0, 120);
    }
}

if (!function_exists('startlist_ensure_department')) {
    function startlist_ensure_department(int $classId, string $label, bool $create = true): ?array
    {
        if ($classId <= 0) {
            return null;
        }
        $sanitized = startlist_sanitize_department_label($label);
        if ($sanitized === '') {
            return null;
        }
        $normalized = startlist_normalize_department($sanitized);
        if ($normalized === '') {
            return null;
        }
        $existing = db_first('SELECT * FROM class_departments WHERE class_id = :class AND normalized_label = :normalized', [
            'class' => $classId,
            'normalized' => $normalized,
        ]);
        if ($existing) {
            return $existing;
        }
        if (!$create) {
            return null;
        }
        $positionRow = db_first('SELECT MAX(position) AS max_position FROM class_departments WHERE class_id = :class', [
            'class' => $classId,
        ]);
        $position = (int) ($positionRow['max_position'] ?? 0) + 1;
        $timestamp = (new \DateTimeImmutable())->format('c');
        db_execute('INSERT INTO class_departments (class_id, label, normalized_label, position, created_at, updated_at) VALUES (:class_id, :label, :normalized, :position, :created, :updated)', [
            'class_id' => $classId,
            'label' => $sanitized,
            'normalized' => $normalized,
            'position' => $position,
            'created' => $timestamp,
            'updated' => $timestamp,
        ]);
        $id = (int) app_pdo()->lastInsertId();
        $row = db_first('SELECT * FROM class_departments WHERE id = :id', ['id' => $id]);
        if ($row) {
            return $row;
        }
        return [
            'id' => $id,
            'class_id' => $classId,
            'label' => $sanitized,
            'normalized_label' => $normalized,
            'position' => $position,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
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
                    'department_id' => null,
                    'department_position' => null,
                ];
            }
            $groups[$key]['members'][] = $item;
            $state = $item['state'] ?? 'scheduled';
            $groups[$key]['states'][$state] = true;
            if (!empty($item['start_number_display'])) {
                $groups[$key]['start_numbers'][(string) $item['start_number_display']] = true;
            }
            if ($groups[$key]['department_id'] === null && isset($item['department_id']) && (int) $item['department_id'] > 0) {
                $groups[$key]['department_id'] = (int) $item['department_id'];
            }
            if ($groups[$key]['department_position'] === null && isset($item['department_position']) && $item['department_position'] !== null) {
                $groups[$key]['department_position'] = (int) $item['department_position'];
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
                $leftOrder = $left['department_order'] ?? null;
                $rightOrder = $right['department_order'] ?? null;
                if ($leftOrder !== null && $rightOrder !== null && $leftOrder !== $rightOrder) {
                    return $leftOrder <=> $rightOrder;
                }
                if ($leftOrder !== null && $rightOrder === null) {
                    return -1;
                }
                if ($leftOrder === null && $rightOrder !== null) {
                    return 1;
                }
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
            $leftDept = $left['department_position'] ?? null;
            $rightDept = $right['department_position'] ?? null;
            if ($leftDept !== null && $rightDept !== null && $leftDept !== $rightDept) {
                return $leftDept <=> $rightDept;
            }
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
