<?php

namespace App\StartNumber;

use DateInterval;
use DateTimeImmutable;
use RuntimeException;

class StartNumberService
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getRule(array $context): array
    {
        $context = $this->enrichContext($context);
        $eventId = (int) ($context['event']['id'] ?? 0);
        if ($eventId <= 0) {
            throw new RuntimeException('Event-Kontext fehlt.');
        }
        $row = \db_first('SELECT start_number_rules FROM events WHERE id = :id', ['id' => $eventId]);
        $json = $row['start_number_rules'] ?? null;
        $base = $json ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : [];
        if (!is_array($base)) {
            $base = [];
        }
        $rule = $this->ensureRuleStructure($base);

        $classOverride = $this->resolveClassOverride($context);
        if ($classOverride) {
            $rule = $this->ensureRuleStructure(array_replace_recursive($rule, $classOverride));
        }

        if (!empty($context['ruleOverride']) && is_array($context['ruleOverride'])) {
            $rule = $this->ensureRuleStructure(array_replace_recursive($rule, $context['ruleOverride']));
        }

        $rule = $this->applyOverrides($rule, $context);
        return $rule;
    }

    public function assign(array $context, array $subject): array
    {
        $context = $this->enrichContext($context);
        $rule = $this->getRule($context);
        $subjectInfo = $this->normalizeSubject($subject, $context, $rule);
        $scopeKey = $this->buildScopeKey($rule, $context, $subjectInfo);
        $subjectKey = $subjectInfo['subject_key'];

        $existing = \db_first('SELECT * FROM start_number_assignments WHERE subject_key = :key AND status = "active"', [
            'key' => $subjectKey,
        ]);
        if ($existing) {
            $payload = json_decode($existing['subject_payload'] ?? 'null', true) ?: [];
            if (!empty($subjectInfo['startlist_id']) && ($payload['startlist_id'] ?? null) !== $subjectInfo['startlist_id']) {
                $payload['startlist_id'] = $subjectInfo['startlist_id'];
                \db_execute('UPDATE start_number_assignments SET subject_payload = :payload WHERE id = :id', [
                    'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                    'id' => (int) $existing['id'],
                ]);
                $existing = $this->resolveAssignment((int) $existing['id']);
            }
            $this->bindAssignment($existing, $subjectInfo, $context, $rule);
            return $existing;
        }

        $reuseCandidate = $this->findReusableAssignment($rule, $context, $scopeKey, $subjectInfo);
        if ($reuseCandidate) {
            return $this->activateAssignment($reuseCandidate, $subjectInfo, $context, $rule);
        }

        $number = $this->nextNumber($rule, $context, $scopeKey, $subjectInfo);
        $display = $this->formatNumberRaw($number, $rule);
        $now = (new DateTimeImmutable())->format('c');
        \db_execute(
            'INSERT INTO start_number_assignments (event_id, class_id, arena, day, scope_key, rule_scope, rule_snapshot, allocation_entity, allocation_time, subject_type, subject_key, subject_payload, rider_id, horse_id, club_id, start_number_raw, start_number_display, status, created_by, created_at)
            VALUES (:event_id, :class_id, :arena, :day, :scope_key, :rule_scope, :rule_snapshot, :allocation_entity, :allocation_time, :subject_type, :subject_key, :subject_payload, :rider_id, :horse_id, :club_id, :raw, :display, :status, :created_by, :created_at)'
        , [
            'event_id' => (int) $context['event']['id'],
            'class_id' => $context['class']['id'] ?? null,
            'arena' => $context['arena'] ?? null,
            'day' => $context['day'] ?? null,
            'scope_key' => $scopeKey,
            'rule_scope' => $rule['scope'],
            'rule_snapshot' => json_encode($rule, JSON_THROW_ON_ERROR),
            'allocation_entity' => $rule['allocation']['entity'],
            'allocation_time' => $rule['allocation']['time'],
            'subject_type' => $subjectInfo['type'],
            'subject_key' => $subjectKey,
            'subject_payload' => json_encode($subjectInfo, JSON_THROW_ON_ERROR),
            'rider_id' => $subjectInfo['rider_id'] ?? null,
            'horse_id' => $subjectInfo['horse_id'] ?? null,
            'club_id' => $subjectInfo['club_id'] ?? null,
            'raw' => $number,
            'display' => $display,
            'status' => 'active',
            'created_by' => $context['actor'] ?? ($context['user']['name'] ?? null),
            'created_at' => $now,
        ]);
        $assignment = \db_first('SELECT * FROM start_number_assignments WHERE subject_key = :key AND status = "active"', [
            'key' => $subjectKey,
        ]);
        $this->bindAssignment($assignment, $subjectInfo, $context, $rule);
        \audit_log('start_numbers', (int) $assignment['id'], 'assign', null, $assignment);
        return $assignment;
    }

    public function release(array|int $startNumber, string $reason): void
    {
        $entryId = null;
        $startlistId = null;
        if (is_array($startNumber)) {
            if (isset($startNumber['entry_id'])) {
                $entryId = (int) $startNumber['entry_id'];
            }
            if (isset($startNumber['startlist_id'])) {
                $startlistId = (int) $startNumber['startlist_id'];
            }
        }
        $assignment = $this->resolveAssignment($startNumber);
        if (!$assignment) {
            return;
        }
        $before = $assignment;
        $this->unbindAssignment((int) $assignment['id'], $entryId, $startlistId);
        if ($this->hasActiveBindings((int) $assignment['id'])) {
            $after = $this->resolveAssignment((int) $assignment['id']);
            \audit_log('start_numbers', (int) $assignment['id'], 'release_partial', $before, $after);
            return;
        }
        if ($assignment['status'] !== 'released') {
            \db_execute('UPDATE start_number_assignments SET status = "released", released_at = :released, release_reason = :reason WHERE id = :id', [
                'released' => (new DateTimeImmutable())->format('c'),
                'reason' => $reason,
                'id' => (int) $assignment['id'],
            ]);
        }
        $after = $this->resolveAssignment((int) $assignment['id']);
        \audit_log('start_numbers', (int) $assignment['id'], 'release', $before, $after);
    }

    public function format(array|int $startNumber, array $context): string
    {
        $assignment = $this->resolveAssignment($startNumber);
        if ($assignment) {
            return $assignment['start_number_display'];
        }
        $rule = $this->getRule($context);
        $number = is_array($startNumber) ? (int) ($startNumber['start_number_raw'] ?? 0) : (int) $startNumber;
        return $this->formatNumberRaw($number, $rule);
    }

    public function validate(array|int $startNumber, array $context): array
    {
        $assignment = $this->resolveAssignment($startNumber);
        if (!$assignment) {
            $number = is_array($startNumber) ? (int) ($startNumber['start_number_raw'] ?? 0) : (int) $startNumber;
            $assignment = ['start_number_raw' => $number];
        }
        $rule = $this->getRule($context);
        $errors = $this->checkConstraints($assignment, $rule, $context);
        return [
            'ok' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function resolveBinding(string $printed): ?array
    {
        $row = \db_first('SELECT * FROM start_number_assignments WHERE start_number_display = :display ORDER BY id DESC LIMIT 1', [
            'display' => $printed,
        ]);
        if (!$row) {
            $row = \db_first('SELECT * FROM start_number_assignments WHERE start_number_raw = :raw ORDER BY id DESC LIMIT 1', [
                'raw' => (int) preg_replace('/\D+/', '', $printed),
            ]);
        }
        if (!$row) {
            return null;
        }
        return json_decode($row['subject_payload'] ?? 'null', true) ?: null;
    }

    public function preview(array $context, int $count = 20): array
    {
        $context = $this->enrichContext($context);
        $rule = $this->getRule($context);
        $subjectInfo = [
            'type' => $rule['allocation']['entity'],
            'subject_key' => 'preview',
            'rider_id' => null,
            'horse_id' => null,
            'club_id' => null,
        ];
        $scopeKey = $this->buildScopeKey($rule, $context, $subjectInfo);
        $sequence = $rule['sequence'];
        $start = (int) ($sequence['start'] ?? 1);
        $step = (int) ($sequence['step'] ?? 1) ?: 1;
        $range = $sequence['range'] ?? null;
        $rows = \db_all('SELECT start_number_raw FROM start_number_assignments WHERE event_id = :event AND scope_key = :scope', [
            'event' => (int) $context['event']['id'],
            'scope' => $scopeKey,
        ]);
        $used = array_map('intval', array_column($rows, 'start_number_raw'));
        $current = $used ? max($used) : $start - $step;
        $preview = [];
        while (count($preview) < $count) {
            $current += $step;
            if ($range && ($current < (int) $range[0] || $current > (int) $range[1])) {
                break;
            }
            if (!empty($rule['constraints']['blocklists']) && in_array((string) $current, array_map('strval', $rule['constraints']['blocklists']), true)) {
                continue;
            }
            $preview[] = [
                'raw' => $current,
                'display' => $this->formatNumberRaw($current, $rule),
            ];
        }
        return $preview;
    }

    public function lock(array|int $startNumber, string $trigger): void
    {
        $assignment = $this->resolveAssignment($startNumber);
        if (!$assignment) {
            return;
        }
        $rule = json_decode($assignment['rule_snapshot'] ?? 'null', true);
        $lockAfter = $rule['allocation']['lock_after'] ?? 'never';
        if ($lockAfter === 'never') {
            return;
        }
        $shouldLock = false;
        if ($lockAfter === 'start_called' && in_array($trigger, ['start_called', 'running'], true)) {
            $shouldLock = true;
        }
        if ($lockAfter === 'sign_off' && in_array($trigger, ['sign_off', 'result_released'], true)) {
            $shouldLock = true;
        }
        if ($shouldLock && empty($assignment['locked_at'])) {
            $timestamp = (new DateTimeImmutable())->format('c');
            \db_execute('UPDATE start_number_assignments SET locked_at = :locked WHERE id = :id', [
                'locked' => $timestamp,
                'id' => (int) $assignment['id'],
            ]);
            \db_execute('UPDATE startlist_items SET start_number_locked_at = :locked WHERE start_number_assignment_id = :id', [
                'locked' => $timestamp,
                'id' => (int) $assignment['id'],
            ]);
            \db_execute('UPDATE entries SET start_number_locked_at = :locked WHERE start_number_assignment_id = :id', [
                'locked' => $timestamp,
                'id' => (int) $assignment['id'],
            ]);
        }
    }

    private function activateAssignment(array $assignment, array $subjectInfo, array $context, array $rule): array
    {
        $before = $assignment;
        \db_execute('UPDATE start_number_assignments SET status = "active", subject_type = :type, subject_key = :subject_key, subject_payload = :payload, rider_id = :rider, horse_id = :horse, club_id = :club, class_id = :class, arena = :arena, day = :day, released_at = NULL, release_reason = NULL, locked_at = NULL WHERE id = :id', [
            'type' => $subjectInfo['type'],
            'subject_key' => $subjectInfo['subject_key'],
            'payload' => json_encode($subjectInfo, JSON_THROW_ON_ERROR),
            'rider' => $subjectInfo['rider_id'] ?? null,
            'horse' => $subjectInfo['horse_id'] ?? null,
            'club' => $subjectInfo['club_id'] ?? null,
            'class' => $context['class']['id'] ?? null,
            'arena' => $context['arena'] ?? null,
            'day' => $context['day'] ?? null,
            'id' => (int) $assignment['id'],
        ]);
        $assignment = $this->resolveAssignment((int) $assignment['id']);
        $this->bindAssignment($assignment, $subjectInfo, $context, $rule);
        \audit_log('start_numbers', (int) $assignment['id'], 'reassign', $before, $assignment);
        return $assignment;
    }

    private function bindAssignment(array $assignment, array $subjectInfo, array $context, array $rule): void
    {
        \db_execute('UPDATE start_number_assignments SET subject_payload = :payload WHERE id = :id', [
            'payload' => json_encode($subjectInfo, JSON_THROW_ON_ERROR),
            'id' => (int) $assignment['id'],
        ]);
        \db_execute('UPDATE start_number_assignments SET class_id = :class, arena = :arena, day = :day, rider_id = :rider, horse_id = :horse, club_id = :club WHERE id = :id', [
            'class' => $context['class']['id'] ?? null,
            'arena' => $context['arena'] ?? null,
            'day' => $context['day'] ?? null,
            'rider' => $subjectInfo['rider_id'] ?? null,
            'horse' => $subjectInfo['horse_id'] ?? null,
            'club' => $subjectInfo['club_id'] ?? null,
            'id' => (int) $assignment['id'],
        ]);
        if (!empty($subjectInfo['entry_id'])) {
            \db_execute('UPDATE entries SET start_number_assignment_id = :assignment, start_number_raw = :raw, start_number_display = :display, start_number_rule_snapshot = :snapshot, start_number_allocation_entity = :entity WHERE id = :id', [
                'assignment' => (int) $assignment['id'],
                'raw' => (int) $assignment['start_number_raw'],
                'display' => $assignment['start_number_display'],
                'snapshot' => $assignment['rule_snapshot'],
                'entity' => $assignment['allocation_entity'],
                'id' => (int) $subjectInfo['entry_id'],
            ]);
            \db_execute('UPDATE entries SET start_number_locked_at = :locked WHERE id = :id', [
                'locked' => $assignment['locked_at'] ?? null,
                'id' => (int) $subjectInfo['entry_id'],
            ]);
        }
        if (!empty($subjectInfo['startlist_id'])) {
            \db_execute('UPDATE startlist_items SET start_number_assignment_id = :assignment, start_number_raw = :raw, start_number_display = :display, start_number_rule_snapshot = :snapshot, start_number_allocation_entity = :entity WHERE id = :id', [
                'assignment' => (int) $assignment['id'],
                'raw' => (int) $assignment['start_number_raw'],
                'display' => $assignment['start_number_display'],
                'snapshot' => $assignment['rule_snapshot'],
                'entity' => $assignment['allocation_entity'],
                'id' => (int) $subjectInfo['startlist_id'],
            ]);
            \db_execute('UPDATE startlist_items SET start_number_locked_at = :locked WHERE id = :id', [
                'locked' => $assignment['locked_at'] ?? null,
                'id' => (int) $subjectInfo['startlist_id'],
            ]);
        }
    }

    private function unbindAssignment(int $assignmentId, ?int $entryId = null, ?int $startlistId = null): void
    {
        $entryParams = ['id' => $assignmentId];
        $entrySql = 'UPDATE entries SET start_number_assignment_id = NULL, start_number_raw = NULL, start_number_display = NULL, start_number_rule_snapshot = NULL, start_number_locked_at = NULL WHERE start_number_assignment_id = :id';
        if ($entryId !== null) {
            $entrySql .= ' AND id = :entry_id';
            $entryParams['entry_id'] = $entryId;
        }
        \db_execute($entrySql, $entryParams);

        $startlistParams = ['id' => $assignmentId];
        $startlistSql = 'UPDATE startlist_items SET start_number_assignment_id = NULL, start_number_raw = NULL, start_number_display = NULL, start_number_rule_snapshot = NULL, start_number_locked_at = NULL WHERE start_number_assignment_id = :id';
        if ($startlistId !== null) {
            $startlistSql .= ' AND id = :startlist_id';
            $startlistParams['startlist_id'] = $startlistId;
        } elseif ($entryId !== null) {
            $startlistSql .= ' AND entry_id = :entry_id';
            $startlistParams['entry_id'] = $entryId;
        }
        \db_execute($startlistSql, $startlistParams);
    }

    private function hasActiveBindings(int $assignmentId): bool
    {
        $entry = \db_first('SELECT COUNT(*) AS cnt FROM entries WHERE start_number_assignment_id = :id', ['id' => $assignmentId]);
        if ((int) ($entry['cnt'] ?? 0) > 0) {
            return true;
        }
        $start = \db_first('SELECT COUNT(*) AS cnt FROM startlist_items WHERE start_number_assignment_id = :id', ['id' => $assignmentId]);
        return (int) ($start['cnt'] ?? 0) > 0;
    }

    private function resolveAssignment(array|int $startNumber): ?array
    {
        if (is_array($startNumber)) {
            if (!empty($startNumber['id'])) {
                return \db_first('SELECT * FROM start_number_assignments WHERE id = :id', ['id' => (int) $startNumber['id']]);
            }
            if (!empty($startNumber['start_number_raw'])) {
                return \db_first('SELECT * FROM start_number_assignments WHERE start_number_raw = :raw AND status = "active" ORDER BY id DESC LIMIT 1', ['raw' => (int) $startNumber['start_number_raw']]);
            }
            return null;
        }
        if ($startNumber <= 0) {
            return null;
        }
        return \db_first('SELECT * FROM start_number_assignments WHERE id = :id', ['id' => (int) $startNumber]);
    }

    private function findReusableAssignment(array $rule, array $context, string $scopeKey, array $subjectInfo): ?array
    {
        $reuse = $rule['allocation']['reuse'] ?? 'never';
        if ($reuse === 'never') {
            return null;
        }
        $sql = 'SELECT * FROM start_number_assignments WHERE event_id = :event AND scope_key = :scope AND status = "released"';
        $params = [
            'event' => (int) $context['event']['id'],
            'scope' => $scopeKey,
        ];
        if ($reuse === 'after_scratch') {
            $sql .= ' AND release_reason IN ("scratch", "retire", "withdraw", "scratch_before_start")';
        }
        $sql .= ' ORDER BY released_at ASC LIMIT 1';
        return \db_first($sql, $params);
    }

    private function nextNumber(array $rule, array $context, string $scopeKey, array $subjectInfo): int
    {
        $sequence = $rule['sequence'];
        $start = (int) ($sequence['start'] ?? 1);
        $step = (int) ($sequence['step'] ?? 1);
        if ($step === 0) {
            $step = 1;
        }
        $range = $sequence['range'] ?? null;
        $rows = \db_all('SELECT start_number_raw, club_id FROM start_number_assignments WHERE scope_key = :scope AND event_id = :event', [
            'scope' => $scopeKey,
            'event' => (int) $context['event']['id'],
        ]);
        $numbers = array_column($rows, 'start_number_raw');
        $candidate = $numbers ? max($numbers) + $step : $start;
        $blocklist = $rule['constraints']['blocklists'] ?? [];
        $lastClub = null;
        if ($subjectInfo['club_id'] && $rule['constraints']['club_spacing'] > 0) {
            $last = \db_first('SELECT club_id FROM start_number_assignments WHERE scope_key = :scope AND status = "active" ORDER BY start_number_raw DESC LIMIT 1', [
                'scope' => $scopeKey,
            ]);
            $lastClub = $last['club_id'] ?? null;
        }
        while (true) {
            if ($range && (($candidate < (int) $range[0]) || ($candidate > (int) $range[1]))) {
                throw new RuntimeException('Startnummernbereich erschöpft.');
            }
            if ($blocklist && in_array((string) $candidate, array_map('strval', $blocklist), true)) {
                $candidate += $step;
                continue;
            }
            if ($lastClub !== null && $subjectInfo['club_id'] === $lastClub) {
                $candidate += $step;
                $lastClub = $subjectInfo['club_id'];
                continue;
            }
            if (!$this->isUnique($candidate, $rule, $context, $subjectInfo)) {
                $candidate += $step;
                continue;
            }
            if (!$this->checkHorseCooldown($candidate, $rule, $subjectInfo)) {
                $candidate += $step;
                continue;
            }
            break;
        }
        return $candidate;
    }

    private function isUnique(int $candidate, array $rule, array $context, array $subjectInfo): bool
    {
        $uniquePer = $rule['constraints']['unique_per'] ?? 'tournament';
        $sql = 'SELECT COUNT(*) AS cnt FROM start_number_assignments WHERE start_number_raw = :number AND status = "active"';
        $params = ['number' => $candidate];
        if ($uniquePer === 'tournament') {
            $sql .= ' AND event_id = :event';
            $params['event'] = (int) $context['event']['id'];
        }
        if ($uniquePer === 'class') {
            $sql .= ' AND class_id = :class';
            $params['class'] = $context['class']['id'] ?? 0;
        }
        if ($uniquePer === 'day') {
            $sql .= ' AND day = :day';
            $params['day'] = $context['day'] ?? null;
        }
        $row = \db_first($sql, $params);
        return (int) ($row['cnt'] ?? 0) === 0;
    }

    private function checkHorseCooldown(int $candidate, array $rule, array $subjectInfo): bool
    {
        $cooldown = (int) ($rule['constraints']['horse_cooldown_min'] ?? 0);
        if ($cooldown <= 0 || empty($subjectInfo['horse_id'])) {
            return true;
        }
        $last = \db_first('SELECT created_at FROM start_number_assignments WHERE horse_id = :horse AND status = "active" ORDER BY created_at DESC LIMIT 1', [
            'horse' => (int) $subjectInfo['horse_id'],
        ]);
        if (!$last || empty($last['created_at'])) {
            return true;
        }
        $lastTime = new DateTimeImmutable($last['created_at']);
        $min = $lastTime->add(new DateInterval('PT' . $cooldown . 'M'));
        return $min <= new DateTimeImmutable();
    }

    private function formatNumberRaw(int $number, array $rule): string
    {
        $format = $rule['format'];
        $width = (int) ($format['width'] ?? 0);
        $body = $width > 0 ? str_pad((string) $number, $width, '0', STR_PAD_LEFT) : (string) $number;
        $prefix = (string) ($format['prefix'] ?? '');
        $suffix = (string) ($format['suffix'] ?? '');
        $separator = (string) ($format['separator'] ?? '');
        $parts = [];
        if ($prefix !== '') {
            $parts[] = $prefix;
        }
        $parts[] = $body;
        if ($suffix !== '') {
            $parts[] = $suffix;
        }
        if ($separator === '') {
            return implode('', $parts);
        }
        return implode($separator, $parts);
    }

    private function ensureRuleStructure(array $rule): array
    {
        $defaults = [
            'mode' => 'classic',
            'scope' => 'tournament',
            'sequence' => [
                'start' => 1,
                'step' => 1,
                'range' => [1, 9999],
                'reset' => 'never',
            ],
            'format' => [
                'prefix' => '',
                'width' => 3,
                'suffix' => '',
                'separator' => '',
            ],
            'allocation' => [
                'entity' => 'start',
                'time' => 'on_startlist',
                'reuse' => 'never',
                'lock_after' => 'start_called',
            ],
            'constraints' => [
                'unique_per' => 'tournament',
                'blocklists' => [],
                'club_spacing' => 0,
                'horse_cooldown_min' => 0,
            ],
            'overrides' => [],
        ];
        $rule = array_replace_recursive($defaults, $rule);
        if (!isset($rule['overrides']) || !is_array($rule['overrides'])) {
            $rule['overrides'] = [];
        }
        return $rule;
    }

    private function applyOverrides(array $rule, array $context): array
    {
        $contextWithMode = $context;
        $contextWithMode['rule_mode'] = $rule['mode'] ?? null;
        foreach ($rule['overrides'] as $override) {
            if (!is_array($override) || empty($override['if'])) {
                continue;
            }
            if ($this->overrideMatches($override['if'], $contextWithMode)) {
                foreach (['sequence', 'format', 'allocation', 'constraints'] as $section) {
                    if (!empty($override[$section]) && is_array($override[$section])) {
                        $rule[$section] = array_replace_recursive($rule[$section], $override[$section]);
                    }
                }
            }
        }
        return $rule;
    }

    private function overrideMatches(array $conditions, array $context): bool
    {
        foreach ($conditions as $key => $value) {
            switch ($key) {
                case 'class_id':
                    if ((int) ($context['class']['id'] ?? 0) !== (int) $value) {
                        return false;
                    }
                    break;
                case 'class_tag':
                    $tags = (array) ($context['class']['tags'] ?? []);
                    if (!in_array($value, $tags, true)) {
                        return false;
                    }
                    break;
                case 'division':
                    if (($context['class']['division'] ?? null) !== $value) {
                        return false;
                    }
                    break;
                case 'arena':
                    if (($context['arena'] ?? null) !== $value) {
                        return false;
                    }
                    break;
                case 'date':
                    if (($context['day'] ?? null) !== $value) {
                        return false;
                    }
                    break;
                case 'mode':
                    if (($context['mode'] ?? $context['rule_mode'] ?? null) !== $value) {
                        return false;
                    }
                    break;
                default:
                    if (($context[$key] ?? null) !== $value) {
                        return false;
                    }
            }
        }
        return true;
    }

    private function resolveClassOverride(array $context): ?array
    {
        $raw = null;
        if (isset($context['class']) && array_key_exists('start_number_rules', $context['class'])) {
            $raw = $context['class']['start_number_rules'];
        } elseif (isset($context['class']) && array_key_exists('start_number_rules_text', $context['class'])) {
            $raw = $context['class']['start_number_rules_text'];
        }
        if ($raw === null) {
            return null;
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && trim($raw) !== '') {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                return null;
            }
            return is_array($decoded) ? $decoded : null;
        }
        return null;
    }

    private function enrichContext(array $context): array
    {
        if (!isset($context['event']) && isset($context['eventId'])) {
            $event = \db_first('SELECT * FROM events WHERE id = :id', ['id' => (int) $context['eventId']]);
            if (!$event) {
                throw new RuntimeException('Event nicht gefunden.');
            }
            $context['event'] = $event;
        }
        if (isset($context['classId']) && !isset($context['class'])) {
            $class = \db_first('SELECT * FROM classes WHERE id = :id', ['id' => (int) $context['classId']]);
            if ($class) {
                $context['class'] = $class;
                if (!isset($context['arena']) && !empty($class['arena'])) {
                    $context['arena'] = $class['arena'];
                }
                if (!isset($context['day']) && !empty($class['start_time'])) {
                    $context['day'] = substr($class['start_time'], 0, 10);
                }
            }
        }
        if (!isset($context['day']) && isset($context['date'])) {
            $context['day'] = $context['date'];
        }
        return $context;
    }

    private function normalizeSubject(array $subject, array $context, array $rule): array
    {
        $type = $subject['type'] ?? ($rule['allocation']['entity'] === 'pair' ? 'pair' : 'start');
        if ($type === 'start') {
            $entryId = (int) ($subject['entry_id'] ?? 0);
            $startlistId = (int) ($subject['startlist_id'] ?? 0);
            if ($entryId <= 0 && $startlistId > 0) {
                $row = \db_first('SELECT entry_id FROM startlist_items WHERE id = :id', ['id' => $startlistId]);
                $entryId = (int) ($row['entry_id'] ?? 0);
            }
            if ($entryId <= 0) {
                throw new RuntimeException('Start benötigt entry_id.');
            }
            $row = $this->loadEntry($entryId);
            return [
                'type' => 'start',
                'entry_id' => $entryId,
                'startlist_id' => $startlistId ?: null,
                'rider_id' => (int) $row['rider_id'],
                'horse_id' => (int) $row['horse_id'],
                'club_id' => $row['club_id'] ? (int) $row['club_id'] : null,
                'subject_key' => 'start:' . $entryId,
            ];
        }
        if ($type === 'pair') {
            $entryId = (int) ($subject['entry_id'] ?? 0);
            $startlistId = (int) ($subject['startlist_id'] ?? 0);
            if ($entryId <= 0 && $startlistId > 0) {
                $row = \db_first('SELECT entry_id FROM startlist_items WHERE id = :id', ['id' => $startlistId]);
                $entryId = (int) ($row['entry_id'] ?? 0);
            }
            $riderId = (int) ($subject['rider_id'] ?? 0);
            $horseId = (int) ($subject['horse_id'] ?? 0);
            $clubId = null;
            if ($entryId > 0) {
                $row = $this->loadEntry($entryId);
                $riderId = $riderId ?: (int) $row['rider_id'];
                $horseId = $horseId ?: (int) $row['horse_id'];
                $clubId = $row['club_id'] ? (int) $row['club_id'] : null;
            }
            if ($riderId <= 0 || $horseId <= 0) {
                throw new RuntimeException('Pair benötigt rider_id und horse_id.');
            }
            if ($clubId === null) {
                $person = \db_first('SELECT club_id FROM person_profiles WHERE party_id = :id', ['id' => $riderId]);
                $clubId = $person && $person['club_id'] ? (int) $person['club_id'] : null;
            }
            return [
                'type' => 'pair',
                'entry_id' => $entryId ?: null,
                'startlist_id' => $startlistId ?: null,
                'rider_id' => $riderId,
                'horse_id' => $horseId,
                'club_id' => $clubId,
                'subject_key' => 'pair:' . $riderId . ':' . $horseId,
            ];
        }
        throw new RuntimeException('Unbekannter Startnummerntyp.');
    }

    private function loadEntry(int $entryId): array
    {
        $row = \db_first('SELECT e.*, profile.club_id, pr.id AS rider_id, h.id AS horse_id FROM entries e JOIN parties pr ON pr.id = e.party_id LEFT JOIN person_profiles profile ON profile.party_id = pr.id JOIN horses h ON h.id = e.horse_id WHERE e.id = :id', ['id' => $entryId]);
        if (!$row) {
            throw new RuntimeException('Nennung nicht gefunden.');
        }
        return $row;
    }

    private function buildScopeKey(array $rule, array $context, array $subjectInfo): string
    {
        $parts = ['event:' . (int) $context['event']['id']];
        switch ($rule['scope']) {
            case 'class':
                $parts[] = 'class:' . (int) ($context['class']['id'] ?? 0);
                break;
            case 'arena':
                $parts[] = 'arena:' . ($context['arena'] ?? '');
                break;
            case 'day':
                $parts[] = 'day:' . ($context['day'] ?? '');
                break;
        }
        $reset = $rule['sequence']['reset'] ?? 'never';
        if ($reset === 'per_class') {
            $parts[] = 'reset_class:' . (int) ($context['class']['id'] ?? 0);
        }
        if ($reset === 'per_day') {
            $parts[] = 'reset_day:' . ($context['day'] ?? '');
        }
        return implode('|', $parts);
    }

    private function checkConstraints(array $assignment, array $rule, array $context): array
    {
        $errors = [];
        $raw = (int) ($assignment['start_number_raw'] ?? 0);
        if ($raw <= 0) {
            $errors[] = 'Startnummer ungültig.';
        }
        $range = $rule['sequence']['range'] ?? null;
        if ($range && ($raw < (int) $range[0] || $raw > (int) $range[1])) {
            $errors[] = 'Startnummer außerhalb des zulässigen Bereichs.';
        }
        $blocklist = $rule['constraints']['blocklists'] ?? [];
        if ($blocklist && in_array((string) $raw, array_map('strval', $blocklist), true)) {
            $errors[] = 'Startnummer gesperrt.';
        }
        return $errors;
    }
}
