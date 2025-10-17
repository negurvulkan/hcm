<?php

use DateTimeImmutable;
use PDO;
use Throwable;

return [
    'description' => 'Umstellung auf Party/Rollen-Modell für Personen und Organisationen',
    'up' => static function (PDO $pdo, string $driver): void {
        $idPrimary = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $intType = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
        $varchar = $driver === 'mysql' ? 'VARCHAR(190)' : 'TEXT';
        $timestamp = $driver === 'mysql' ? 'DATETIME' : 'TEXT';

        $tableExists = static function (PDO $pdo, string $driver, string $table): bool {
            if ($driver === 'mysql') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
                $stmt->execute(['table' => $table]);
                return (bool) $stmt->fetchColumn();
            }

            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = '" . $table . "'");
            return $stmt && $stmt->fetchColumn() !== false;
        };

        $columnExists = static function (PDO $pdo, string $driver, string $table, string $column): bool {
            if ($driver === 'mysql') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
                $stmt->execute(['table' => $table, 'column' => $column]);
                return (bool) $stmt->fetchColumn();
            }

            $stmt = $pdo->query("PRAGMA table_info('" . $table . "')");
            if (!$stmt) {
                return false;
            }

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $info) {
                if (strcasecmp((string) $info['name'], $column) === 0) {
                    return true;
                }
            }

            return false;
        };

        // Ensure base tables exist for older installations
        if (!$tableExists($pdo, $driver, 'parties')) {
            $pdo->exec(<<<SQL
CREATE TABLE parties (
    id {$idPrimary},
    party_type VARCHAR(40) NOT NULL,
    display_name {$varchar} NOT NULL,
    sort_name {$varchar},
    email {$varchar},
    phone VARCHAR(80),
    created_at {$timestamp} NOT NULL,
    updated_at {$timestamp}
)
SQL);
        } else {
            if (!$columnExists($pdo, $driver, 'parties', 'sort_name')) {
                $pdo->exec('ALTER TABLE parties ADD COLUMN sort_name ' . $varchar . ' NULL');
            }
            if (!$columnExists($pdo, $driver, 'parties', 'updated_at')) {
                $pdo->exec('ALTER TABLE parties ADD COLUMN updated_at ' . $timestamp . ' NULL');
            }
        }

        if (!$tableExists($pdo, $driver, 'party_roles')) {
            $pdo->exec(<<<SQL
CREATE TABLE party_roles (
    id {$idPrimary},
    party_id {$intType} NOT NULL,
    role VARCHAR(60) NOT NULL,
    context VARCHAR(60) NOT NULL DEFAULT 'system',
    assigned_at {$timestamp} NOT NULL,
    updated_at {$timestamp}
)
SQL);
        } else {
            if (!$columnExists($pdo, $driver, 'party_roles', 'updated_at')) {
                $pdo->exec('ALTER TABLE party_roles ADD COLUMN updated_at ' . $timestamp . ' NULL');
            }
        }

        if (!$tableExists($pdo, $driver, 'person_profiles')) {
            $pdo->exec(<<<SQL
CREATE TABLE person_profiles (
    party_id {$intType} PRIMARY KEY,
    club_id {$intType},
    preferred_locale VARCHAR(10),
    updated_at {$timestamp}
)
SQL);
        } else {
            if (!$columnExists($pdo, $driver, 'person_profiles', 'updated_at')) {
                $pdo->exec('ALTER TABLE person_profiles ADD COLUMN updated_at ' . $timestamp . ' NULL');
            }
            if (!$columnExists($pdo, $driver, 'person_profiles', 'preferred_locale')) {
                $pdo->exec('ALTER TABLE person_profiles ADD COLUMN preferred_locale VARCHAR(10) NULL');
            }
        }

        if (!$tableExists($pdo, $driver, 'organization_profiles')) {
            $pdo->exec(<<<SQL
CREATE TABLE organization_profiles (
    party_id {$intType} PRIMARY KEY,
    category VARCHAR(60) NOT NULL,
    short_name VARCHAR(40),
    metadata TEXT,
    updated_at {$timestamp}
)
SQL);
        } else {
            if (!$columnExists($pdo, $driver, 'organization_profiles', 'updated_at')) {
                $pdo->exec('ALTER TABLE organization_profiles ADD COLUMN updated_at ' . $timestamp . ' NULL');
            }
            if (!$columnExists($pdo, $driver, 'organization_profiles', 'category')) {
                $pdo->exec('ALTER TABLE organization_profiles ADD COLUMN category VARCHAR(60) NOT NULL DEFAULT "club"');
            }
        }

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS party_roles_unique ON party_roles (party_id, role, context)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS parties_type_name_idx ON parties (party_type, sort_name)');

        if (!$columnExists($pdo, $driver, 'clubs', 'party_id')) {
            $pdo->exec('ALTER TABLE clubs ADD COLUMN party_id ' . $intType . ' NULL');
        }
        if (!$columnExists($pdo, $driver, 'horses', 'owner_party_id')) {
            $pdo->exec('ALTER TABLE horses ADD COLUMN owner_party_id ' . $intType . ' NULL');
        }
        if (!$columnExists($pdo, $driver, 'entries', 'party_id')) {
            $pdo->exec('ALTER TABLE entries ADD COLUMN party_id ' . $intType . ' NULL');
        }
        if (!$columnExists($pdo, $driver, 'helper_shifts', 'party_id')) {
            $pdo->exec('ALTER TABLE helper_shifts ADD COLUMN party_id ' . $intType . ' NULL');
        }

        $now = (new DateTimeImmutable())->format('c');

        if ($tableExists($pdo, $driver, 'persons')) {
            $selectPersons = $pdo->query('SELECT * FROM persons');
            $persons = $selectPersons ? $selectPersons->fetchAll(PDO::FETCH_ASSOC) : [];

            $insertParty = $pdo->prepare('INSERT OR IGNORE INTO parties (id, party_type, display_name, sort_name, email, phone, created_at, updated_at) VALUES (:id, :type, :name, :sort_name, :email, :phone, :created_at, :updated_at)');
            if ($driver === 'mysql') {
                $insertParty = $pdo->prepare('INSERT IGNORE INTO parties (id, party_type, display_name, sort_name, email, phone, created_at, updated_at) VALUES (:id, :type, :name, :sort_name, :email, :phone, :created_at, :updated_at)');
            }
            $insertProfile = $pdo->prepare('INSERT OR REPLACE INTO person_profiles (party_id, club_id, preferred_locale, updated_at) VALUES (:party_id, :club_id, NULL, :updated_at)');
            if ($driver === 'mysql') {
                $insertProfile = $pdo->prepare('INSERT INTO person_profiles (party_id, club_id, preferred_locale, updated_at) VALUES (:party_id, :club_id, NULL, :updated_at) ON DUPLICATE KEY UPDATE club_id = VALUES(club_id), updated_at = VALUES(updated_at)');
            }
            $insertRole = $pdo->prepare('INSERT OR IGNORE INTO party_roles (party_id, role, context, assigned_at, updated_at) VALUES (:party_id, :role, :context, :assigned_at, :updated_at)');
            if ($driver === 'mysql') {
                $insertRole = $pdo->prepare('INSERT IGNORE INTO party_roles (party_id, role, context, assigned_at, updated_at) VALUES (:party_id, :role, :context, :assigned_at, :updated_at)');
            }

            foreach ($persons as $person) {
                $id = (int) $person['id'];
                $displayName = (string) $person['name'];
                $sortName = mb_strtolower($displayName);
                $insertParty->execute([
                    'id' => $id,
                    'type' => 'person',
                    'name' => $displayName,
                    'sort_name' => $sortName,
                    'email' => $person['email'] ?? null,
                    'phone' => $person['phone'] ?? null,
                    'created_at' => $person['created_at'] ?? $now,
                    'updated_at' => $now,
                ]);

                $clubId = isset($person['club_id']) ? (int) $person['club_id'] : null;
                $insertProfile->execute([
                    'party_id' => $id,
                    'club_id' => $clubId ?: null,
                    'updated_at' => $now,
                ]);

                $roles = [];
                if (isset($person['roles']) && $person['roles'] !== '') {
                    try {
                        $decoded = json_decode((string) $person['roles'], true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($decoded)) {
                            $roles = $decoded;
                        }
                    } catch (Throwable $exception) {
                        $roles = [];
                    }
                }

                foreach ($roles as $role) {
                    if (!is_string($role) || $role === '') {
                        continue;
                    }
                    $insertRole->execute([
                        'party_id' => $id,
                        'role' => $role,
                        'context' => 'system',
                        'assigned_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            if ($columnExists($pdo, $driver, 'entries', 'person_id')) {
                $pdo->exec('UPDATE entries SET party_id = person_id WHERE party_id IS NULL');
            }
            if ($columnExists($pdo, $driver, 'horses', 'owner_id')) {
                $pdo->exec('UPDATE horses SET owner_party_id = owner_id WHERE owner_party_id IS NULL');
            }
            if ($columnExists($pdo, $driver, 'helper_shifts', 'person_id')) {
                $pdo->exec('UPDATE helper_shifts SET party_id = person_id WHERE party_id IS NULL');
            }

            $pdo->exec('DROP TABLE IF EXISTS persons');
        }

        // Ensure clubs have party references
        $clubsStmt = $pdo->query('SELECT id, name, short_name, party_id FROM clubs');
        $clubs = $clubsStmt ? $clubsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $insertOrgParty = $pdo->prepare('INSERT INTO parties (party_type, display_name, sort_name, email, phone, created_at, updated_at) VALUES ("organization", :name, :sort_name, NULL, NULL, :created, :updated)');
        $insertOrgProfile = $pdo->prepare('INSERT INTO organization_profiles (party_id, category, short_name, metadata, updated_at) VALUES (:party_id, :category, :short_name, NULL, :updated)');
        foreach ($clubs as $club) {
            if (!empty($club['party_id'])) {
                continue;
            }
            $insertOrgParty->execute([
                'name' => $club['name'],
                'sort_name' => mb_strtolower((string) $club['name']),
                'created' => $now,
                'updated' => $now,
            ]);
            $partyId = (int) $pdo->lastInsertId();
            $insertOrgProfile->execute([
                'party_id' => $partyId,
                'category' => 'club',
                'short_name' => $club['short_name'],
                'updated' => $now,
            ]);
            if ($columnExists($pdo, $driver, 'clubs', 'updated_at')) {
                $updateClub = $pdo->prepare('UPDATE clubs SET party_id = :party_id, updated_at = COALESCE(updated_at, :updated) WHERE id = :id');
                $updateClub->execute([
                    'party_id' => $partyId,
                    'id' => $club['id'],
                    'updated' => $now,
                ]);
            } else {
                $updateClub = $pdo->prepare('UPDATE clubs SET party_id = :party_id WHERE id = :id');
                $updateClub->execute([
                    'party_id' => $partyId,
                    'id' => $club['id'],
                ]);
            }
        }

        if ($columnExists($pdo, $driver, 'entries', 'person_id')) {
            if ($driver === 'mysql') {
                $pdo->exec('ALTER TABLE entries DROP COLUMN person_id');
            } else {
                $pdo->exec('ALTER TABLE entries DROP COLUMN person_id');
            }
        }
        if ($columnExists($pdo, $driver, 'horses', 'owner_id')) {
            if ($driver === 'mysql') {
                $pdo->exec('ALTER TABLE horses DROP COLUMN owner_id');
            } else {
                $pdo->exec('ALTER TABLE horses DROP COLUMN owner_id');
            }
        }
        if ($columnExists($pdo, $driver, 'helper_shifts', 'person_id')) {
            if ($driver === 'mysql') {
                $pdo->exec('ALTER TABLE helper_shifts DROP COLUMN person_id');
            } else {
                $pdo->exec('ALTER TABLE helper_shifts DROP COLUMN person_id');
            }
        }

        $pdo->exec('UPDATE parties SET sort_name = COALESCE(sort_name, LOWER(display_name)) WHERE party_type = "person" AND (sort_name IS NULL OR sort_name = "")');
    },
];
