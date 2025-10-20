<?php

declare(strict_types=1);

return [
    'description' => 'Erweiterte Stammdatenfelder für Personen und Pferde sowie Custom-Feld-Struktur',
    'up' => static function (PDO $pdo, string $driver): void {
        $uuidType = $driver === 'mysql' ? 'CHAR(36)' : 'TEXT';
        $timestampType = $driver === 'mysql' ? 'DATETIME' : 'TEXT';
        $booleanType = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';

        $pdo->exec("ALTER TABLE parties ADD COLUMN uuid {$uuidType}");
        $pdo->exec("ALTER TABLE parties ADD COLUMN given_name VARCHAR(120)");
        $pdo->exec("ALTER TABLE parties ADD COLUMN family_name VARCHAR(120)");
        $pdo->exec("ALTER TABLE parties ADD COLUMN date_of_birth DATE");
        $pdo->exec("ALTER TABLE parties ADD COLUMN nationality VARCHAR(3)");
        $pdo->exec("ALTER TABLE parties ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
        $pdo->exec("UPDATE parties SET status = 'active' WHERE status IS NULL OR status = ''");
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS parties_uuid_unique ON parties (uuid)');

        $pdo->exec("ALTER TABLE horses ADD COLUMN uuid {$uuidType}");
        $pdo->exec("ALTER TABLE horses ADD COLUMN life_number VARCHAR(80)");
        $pdo->exec("ALTER TABLE horses ADD COLUMN microchip VARCHAR(80)");
        $pdo->exec("ALTER TABLE horses ADD COLUMN sex VARCHAR(20) DEFAULT 'unknown'");
        $pdo->exec("ALTER TABLE horses ADD COLUMN birth_year INTEGER");
        $pdo->exec("UPDATE horses SET sex = COALESCE(NULLIF(sex, ''), 'unknown')");
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS horses_uuid_unique ON horses (uuid)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS horses_life_number_idx ON horses (life_number)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS horses_microchip_idx ON horses (microchip)');

        $idPrimary = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS custom_field_definitions (
    id {$idPrimary},
    entity VARCHAR(60) NOT NULL,
    field_key VARCHAR(120) NOT NULL,
    label_i18n TEXT NOT NULL,
    help_i18n TEXT,
    type VARCHAR(20) NOT NULL,
    required {$booleanType} NOT NULL DEFAULT 0,
    is_unique {$booleanType} NOT NULL DEFAULT 0,
    is_sensitive {$booleanType} NOT NULL DEFAULT 0,
    regex_pattern VARCHAR(255),
    min_value VARCHAR(60),
    max_value VARCHAR(60),
    enum_values TEXT,
    visibility VARCHAR(20) NOT NULL DEFAULT 'internal',
    scope VARCHAR(20) NOT NULL DEFAULT 'global',
    organization_id INTEGER,
    tournament_id INTEGER,
    required_when TEXT,
    profile_key VARCHAR(120),
    version INTEGER NOT NULL DEFAULT 1,
    valid_from {$timestampType},
    valid_to {$timestampType},
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL
)
SQL
        );
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS custom_field_definitions_unique ON custom_field_definitions (entity, field_key, version)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS custom_field_definitions_scope_idx ON custom_field_definitions (entity, scope)');

        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS custom_field_values (
    id {$idPrimary},
    entity VARCHAR(60) NOT NULL,
    entity_id INTEGER NOT NULL,
    field_definition_id INTEGER NOT NULL,
    value TEXT,
    version INTEGER NOT NULL,
    created_at {$timestampType} NOT NULL,
    updated_at {$timestampType} NOT NULL
)
SQL
        );
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS custom_field_values_unique ON custom_field_values (entity, entity_id, field_definition_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS custom_field_values_definition_idx ON custom_field_values (field_definition_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS custom_field_values_entity_idx ON custom_field_values (entity, entity_id)');

        $generateUuid = static function (): string {
            $data = random_bytes(16);
            $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
            $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        };

        $selectPersons = $pdo->query("SELECT id, uuid, display_name, given_name, family_name FROM parties WHERE party_type = 'person'");
        $updatePerson = $pdo->prepare('UPDATE parties SET uuid = :uuid, given_name = :given, family_name = :family WHERE id = :id');
        while (($row = $selectPersons->fetch(PDO::FETCH_ASSOC)) !== false) {
            $uuid = trim((string) ($row['uuid'] ?? ''));
            if ($uuid === '') {
                $uuid = $generateUuid();
            }

            $given = trim((string) ($row['given_name'] ?? ''));
            $family = trim((string) ($row['family_name'] ?? ''));

            if ($given === '' && $family === '') {
                $display = trim((string) ($row['display_name'] ?? ''));
                if ($display !== '') {
                    $parts = preg_split('/\s+/', $display) ?: [];
                    if (count($parts) > 1) {
                        $family = array_pop($parts) ?: '';
                        $given = implode(' ', $parts);
                    } else {
                        $given = $display;
                    }
                }
            }

            $updatePerson->execute([
                'uuid' => $uuid,
                'given' => $given !== '' ? $given : null,
                'family' => $family !== '' ? $family : null,
                'id' => (int) $row['id'],
            ]);
        }

        $selectHorses = $pdo->query('SELECT id, uuid FROM horses');
        $updateHorse = $pdo->prepare('UPDATE horses SET uuid = :uuid WHERE id = :id');
        while (($row = $selectHorses->fetch(PDO::FETCH_ASSOC)) !== false) {
            $uuid = trim((string) ($row['uuid'] ?? ''));
            if ($uuid === '') {
                $uuid = $generateUuid();
                $updateHorse->execute([
                    'uuid' => $uuid,
                    'id' => (int) $row['id'],
                ]);
            }
        }
    },
];
