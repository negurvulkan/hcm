<?php

if (!function_exists('arenas_format_arena_summary')) {
    function arenas_format_arena_summary(array $arena): array
    {
        $label = trim((string) ($arena['name'] ?? ''));
        $type = (string) ($arena['type'] ?? 'outdoor');
        if (!in_array($type, ['indoor', 'outdoor'], true)) {
            $type = 'outdoor';
        }
        $typeLabel = t('classes.arena_badge.type.' . $type);
        $surface = trim((string) ($arena['surface'] ?? ''));
        $length = isset($arena['length_m']) ? (int) $arena['length_m'] : 0;
        $width = isset($arena['width_m']) ? (int) $arena['width_m'] : 0;
        $size = ($length > 0 && $width > 0)
            ? t('classes.arena_badge.size_format', ['width' => $width, 'length' => $length])
            : null;

        $features = [];
        if (!empty($arena['covered'])) {
            $features[] = t('classes.arena_badge.feature.covered');
        }
        if (!empty($arena['lighting'])) {
            $features[] = t('classes.arena_badge.feature.lighting');
        }
        if (!empty($arena['drainage'])) {
            $features[] = t('classes.arena_badge.feature.drainage');
        }
        if (!empty($arena['capacity'])) {
            $features[] = t('classes.arena_badge.feature.capacity', ['capacity' => (int) $arena['capacity']]);
        }

        $parts = array_filter(array_merge([$typeLabel], array_filter([$size, $surface]), $features));

        return [
            'label' => $label,
            'summary' => implode(' · ', $parts),
        ];
    }
}

if (!function_exists('arenas_format_event_arena')) {
    function arenas_format_event_arena(array $row): array
    {
        $label = trim((string) ($row['display_name'] ?? ''));
        if ($label === '') {
            $label = trim((string) ($row['arena_name'] ?? ''));
        }
        if ($label === '') {
            $label = t('events.arenas.unassigned');
        }

        $type = (string) ($row['arena_type'] ?? 'outdoor');
        if (!in_array($type, ['indoor', 'outdoor'], true)) {
            $type = 'outdoor';
        }
        $typeLabel = t('classes.arena_badge.type.' . $type);

        $surface = trim((string) ($row['temp_surface'] ?? ''));
        if ($surface === '') {
            $surface = trim((string) ($row['arena_surface'] ?? ''));
        }

        $length = isset($row['length_m']) ? (int) $row['length_m'] : 0;
        $width = isset($row['width_m']) ? (int) $row['width_m'] : 0;
        $size = ($length > 0 && $width > 0)
            ? t('classes.arena_badge.size_format', ['width' => $width, 'length' => $length])
            : null;

        $features = [];
        if (!empty($row['covered'])) {
            $features[] = t('classes.arena_badge.feature.covered');
        }
        if (!empty($row['lighting'])) {
            $features[] = t('classes.arena_badge.feature.lighting');
        }
        if (!empty($row['drainage'])) {
            $features[] = t('classes.arena_badge.feature.drainage');
        }
        if (!empty($row['capacity'])) {
            $features[] = t('classes.arena_badge.feature.capacity', ['capacity' => (int) $row['capacity']]);
        }

        $location = trim((string) ($row['location_name'] ?? ''));
        $remarks = trim((string) ($row['remarks'] ?? ''));

        $badgeParts = array_filter(array_merge([$label], array_filter([$size, $surface]), $features));
        $summaryParts = array_filter(array_merge([$typeLabel], array_filter([$size, $surface]), $features));

        if ($location !== '') {
            $summaryParts[] = t('classes.arena_badge.feature.location', ['location' => $location]);
        }

        if ($remarks !== '') {
            $summaryParts[] = t('classes.arena_badge.feature.remarks', ['remarks' => $remarks]);
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'event_id' => (int) ($row['event_id'] ?? 0),
            'arena_id' => (int) ($row['arena_id'] ?? 0),
            'label' => $label,
            'type' => $type,
            'type_label' => $typeLabel,
            'surface' => $surface,
            'size' => $size,
            'features' => $features,
            'location' => $location !== '' ? $location : null,
            'remarks' => $remarks !== '' ? $remarks : null,
            'badge' => implode(' · ', $badgeParts),
            'summary' => implode(' · ', $summaryParts),
            'warmup_arena_id' => isset($row['warmup_arena_id']) ? (int) $row['warmup_arena_id'] : null,
            'blocked_times' => !empty($row['blocked_times'])
                ? (json_decode((string) $row['blocked_times'], true) ?: [])
                : [],
        ];
    }
}

if (!function_exists('arenas_event_assignments')) {
    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    function arenas_event_assignments(PDO $pdo, array $eventIds): array
    {
        $eventIds = array_values(array_unique(array_map('intval', $eventIds)));
        if ($eventIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $sql = <<<SQL
SELECT
    ea.*,
    a.name AS arena_name,
    a.type AS arena_type,
    a.surface AS arena_surface,
    a.length_m,
    a.width_m,
    a.covered,
    a.lighting,
    a.drainage,
    a.capacity,
    l.name AS location_name
FROM event_arenas ea
LEFT JOIN arenas a ON a.id = ea.arena_id
LEFT JOIN locations l ON l.id = a.location_id
WHERE ea.event_id IN ({$placeholders})
ORDER BY l.name IS NULL, l.name, ea.display_name IS NULL, ea.display_name, a.name
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($eventIds);

        $assignments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $eventId = (int) ($row['event_id'] ?? 0);
            $assignments[$eventId][] = arenas_format_event_arena($row);
        }

        return $assignments;
    }
}

