<?php

namespace App\Party;

use DateTimeImmutable;
use PDO;
use PDOException;
use RuntimeException;

class PartyRepository
{
    private const ROLE_CONTEXT = 'system';

    public function __construct(private PDO $pdo)
    {
    }

    public function findPerson(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.display_name, p.sort_name, p.email, p.phone, p.created_at, p.updated_at, profile.club_id, c.name AS club_name
             FROM parties p
             LEFT JOIN person_profiles profile ON profile.party_id = p.id
             LEFT JOIN clubs c ON c.id = profile.club_id
             WHERE p.id = :id AND p.party_type = :type'
        );
        $stmt->execute(['id' => $id, 'type' => 'person']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $roles = $this->fetchRoles([$id]);
        $row['role_list'] = $roles[$id] ?? [];
        $row['name'] = $row['display_name'];

        return $row;
    }

    public function searchPersons(?string $name = null, ?string $role = null, int $limit = 100): array
    {
        $sql = 'SELECT p.id, p.display_name, p.sort_name, p.email, p.phone, p.created_at, p.updated_at, profile.club_id, c.name AS club_name
                FROM parties p
                LEFT JOIN person_profiles profile ON profile.party_id = p.id
                LEFT JOIN clubs c ON c.id = profile.club_id
                WHERE p.party_type = :type';
        $params = ['type' => 'person'];

        if ($name !== null && $name !== '') {
            $sql .= ' AND p.display_name LIKE :name';
            $params['name'] = '%' . $name . '%';
        }

        if ($role !== null && $role !== '') {
            $sql .= ' AND EXISTS (SELECT 1 FROM party_roles pr WHERE pr.party_id = p.id AND pr.role = :role AND pr.context = :context)';
            $params['role'] = $role;
            $params['context'] = self::ROLE_CONTEXT;
        }

        $sql .= ' ORDER BY p.sort_name ASC LIMIT ' . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $roles = $this->fetchRoles($ids);

        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            $row['role_list'] = $roles[$id] ?? [];
            $row['name'] = $row['display_name'];
        }

        return $rows;
    }

    public function personOptions(?string $role = null): array
    {
        $sql = 'SELECT p.id, p.display_name FROM parties p WHERE p.party_type = :type';
        $params = ['type' => 'person'];
        if ($role !== null && $role !== '') {
            $sql .= ' AND EXISTS (SELECT 1 FROM party_roles pr WHERE pr.party_id = p.id AND pr.role = :role AND pr.context = :context)';
            $params['role'] = $role;
            $params['context'] = self::ROLE_CONTEXT;
        }
        $sql .= ' ORDER BY p.sort_name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => $row['display_name'],
            ];
        }, $rows);
    }

    public function createPerson(string $name, ?string $email, ?string $phone, ?int $clubId, array $roles): int
    {
        $this->pdo->beginTransaction();
        try {
            $now = (new DateTimeImmutable())->format('c');
            $sortName = $this->normalizeSortName($name);
            $insertParty = $this->pdo->prepare('INSERT INTO parties (party_type, display_name, sort_name, email, phone, created_at, updated_at) VALUES (:type, :display, :sort, :email, :phone, :created, :updated)');
            $insertParty->execute([
                'type' => 'person',
                'display' => $name,
                'sort' => $sortName,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'created' => $now,
                'updated' => $now,
            ]);
            $id = (int) $this->pdo->lastInsertId();

            $this->upsertPersonProfile($id, $clubId, $now);
            $this->replaceRoles($id, $roles, $now);

            $this->pdo->commit();
            return $id;
        } catch (PDOException $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Konnte Person nicht anlegen: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public function updatePerson(int $id, string $name, ?string $email, ?string $phone, ?int $clubId, array $roles): void
    {
        $this->pdo->beginTransaction();
        try {
            $now = (new DateTimeImmutable())->format('c');
            $sortName = $this->normalizeSortName($name);
            $updateParty = $this->pdo->prepare('UPDATE parties SET display_name = :display, sort_name = :sort, email = :email, phone = :phone, updated_at = :updated WHERE id = :id AND party_type = :type');
            $updateParty->execute([
                'display' => $name,
                'sort' => $sortName,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'updated' => $now,
                'id' => $id,
                'type' => 'person',
            ]);

            $this->upsertPersonProfile($id, $clubId, $now);
            $this->replaceRoles($id, $roles, $now);

            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Konnte Person nicht aktualisieren: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public function deletePerson(int $id): void
    {
        $this->pdo->beginTransaction();
        try {
            $deleteRoles = $this->pdo->prepare('DELETE FROM party_roles WHERE party_id = :id AND context = :context');
            $deleteRoles->execute(['id' => $id, 'context' => self::ROLE_CONTEXT]);

            $deleteProfile = $this->pdo->prepare('DELETE FROM person_profiles WHERE party_id = :id');
            $deleteProfile->execute(['id' => $id]);

            $deleteParty = $this->pdo->prepare('DELETE FROM parties WHERE id = :id AND party_type = :type');
            $deleteParty->execute(['id' => $id, 'type' => 'person']);

            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Konnte Person nicht löschen: ' . $exception->getMessage(), 0, $exception);
        }
    }

    private function upsertPersonProfile(int $partyId, ?int $clubId, string $timestamp): void
    {
        $existsStmt = $this->pdo->prepare('SELECT party_id FROM person_profiles WHERE party_id = :id LIMIT 1');
        $existsStmt->execute(['id' => $partyId]);
        $exists = (bool) $existsStmt->fetchColumn();

        if ($exists) {
            $update = $this->pdo->prepare('UPDATE person_profiles SET club_id = :club, updated_at = :updated WHERE party_id = :id');
            $update->execute([
                'club' => $clubId ?: null,
                'updated' => $timestamp,
                'id' => $partyId,
            ]);
            return;
        }

        $insert = $this->pdo->prepare('INSERT INTO person_profiles (party_id, club_id, preferred_locale, updated_at) VALUES (:id, :club, NULL, :updated)');
        $insert->execute([
            'id' => $partyId,
            'club' => $clubId ?: null,
            'updated' => $timestamp,
        ]);
    }

    private function replaceRoles(int $partyId, array $roles, string $timestamp): void
    {
        $delete = $this->pdo->prepare('DELETE FROM party_roles WHERE party_id = :id AND context = :context');
        $delete->execute(['id' => $partyId, 'context' => self::ROLE_CONTEXT]);

        if (!$roles) {
            return;
        }

        $insert = $this->pdo->prepare('INSERT INTO party_roles (party_id, role, context, assigned_at, updated_at) VALUES (:party, :role, :context, :assigned, :updated)');
        foreach ($roles as $role) {
            if (!is_string($role) || $role === '') {
                continue;
            }
            $insert->execute([
                'party' => $partyId,
                'role' => $role,
                'context' => self::ROLE_CONTEXT,
                'assigned' => $timestamp,
                'updated' => $timestamp,
            ]);
        }
    }

    /**
     * @param int[] $ids
     * @return array<int, array<int, string>>
     */
    private function fetchRoles(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $placeholders = [];
        $params = ['context' => self::ROLE_CONTEXT];
        foreach (array_values($ids) as $index => $id) {
            $key = 'id' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $stmt = $this->pdo->prepare(
            'SELECT party_id, role FROM party_roles WHERE context = :context AND party_id IN (' . implode(', ', $placeholders) . ') ORDER BY role'
        );
        $stmt->execute($params);
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $partyId = (int) $row['party_id'];
            $role = (string) $row['role'];
            $result[$partyId][] = $role;
        }

        return $result;
    }

    private function normalizeSortName(string $name): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($name));
        return mb_strtolower($normalized ?? '');
    }
}
