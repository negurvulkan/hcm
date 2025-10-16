<?php
namespace App\Setup;

use App\Core\Database;
use DateTimeImmutable;
use PDO;
use RuntimeException;

class Installer
{
    public static function run(array $dbConfig, array $options = []): void
    {
        $pdo = Database::connect($dbConfig);
        self::migrate($pdo, $dbConfig['driver'] ?? 'sqlite');
        self::ensureAdmin($pdo, $options['admin'] ?? []);

        if (!empty($options['seed_demo'])) {
            self::seedDemo($pdo);
        }
    }

    public static function writeConfig(array $dbConfig, array $appConfig = []): void
    {
        if (!is_dir(__DIR__ . '/../../config')) {
            mkdir(__DIR__ . '/../../config', 0777, true);
        }

        $config = [
            'app' => array_merge(['name' => 'Turniermanagement V2'], $appConfig),
            'db' => $dbConfig,
        ];

        $export = "<?php\nreturn " . var_export($config, true) . ";\n";
        file_put_contents(__DIR__ . '/../../config/app.php', $export);

        $envLines = [
            'APP_NAME=' . ($config['app']['name'] ?? 'Turniermanagement V2'),
            'DB_DRIVER=' . ($dbConfig['driver'] ?? 'sqlite'),
            'DB_DATABASE=' . ($dbConfig['database'] ?? ''),
            'DB_HOST=' . ($dbConfig['host'] ?? ''),
            'DB_PORT=' . ($dbConfig['port'] ?? ''),
            'DB_USERNAME=' . ($dbConfig['username'] ?? ''),
        ];

        file_put_contents(__DIR__ . '/../../.env', implode(PHP_EOL, $envLines));
    }

    private static function migrate(PDO $pdo, string $driver): void
    {
        $idPrimary = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $datetime = $driver === 'mysql' ? 'DATETIME' : 'TEXT';
        $boolean = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';

        $queries = [
            <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id {$idPrimary},
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL,
    created_at {$datetime} NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS persons (
    id {$idPrimary},
    name VARCHAR(160) NOT NULL,
    email VARCHAR(160),
    phone VARCHAR(80),
    roles TEXT NOT NULL,
    club_id INTEGER,
    created_at {$datetime} NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS horses (
    id {$idPrimary},
    name VARCHAR(160) NOT NULL,
    owner_id INTEGER,
    documents_ok {$boolean} DEFAULT 0,
    notes TEXT
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS clubs (
    id {$idPrimary},
    name VARCHAR(160) NOT NULL,
    short_name VARCHAR(20) NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS events (
    id {$idPrimary},
    title VARCHAR(190) NOT NULL,
    start_date DATE,
    end_date DATE,
    venues TEXT,
    is_active {$boolean} NOT NULL DEFAULT 0
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS classes (
    id {$idPrimary},
    event_id INTEGER NOT NULL,
    label VARCHAR(190) NOT NULL,
    arena VARCHAR(120),
    start_time {$datetime},
    end_time {$datetime},
    max_starters INTEGER,
    judge_assignments TEXT,
    rules_json TEXT,
    tiebreaker_json TEXT
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS entries (
    id {$idPrimary},
    event_id INTEGER NOT NULL,
    class_id INTEGER NOT NULL,
    person_id INTEGER NOT NULL,
    horse_id INTEGER NOT NULL,
    status VARCHAR(40) NOT NULL,
    fee_paid_at {$datetime},
    created_at {$datetime} NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS startlist_items (
    id {$idPrimary},
    class_id INTEGER NOT NULL,
    entry_id INTEGER NOT NULL,
    position INTEGER NOT NULL,
    planned_start {$datetime},
    state VARCHAR(40) NOT NULL DEFAULT 'scheduled',
    note TEXT,
    created_at {$datetime} NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at {$datetime}
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS schedule_shifts (
    id {$idPrimary},
    class_id INTEGER NOT NULL,
    shift_minutes INTEGER NOT NULL DEFAULT 0,
    created_at {$datetime} NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS results (
    id {$idPrimary},
    startlist_id INTEGER NOT NULL,
    scores_json TEXT,
    total DECIMAL(10,2),
    penalties DECIMAL(10,2),
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    signed_by VARCHAR(160),
    signed_at {$datetime},
    signature_hash VARCHAR(255),
    created_at {$datetime} NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS helper_shifts (
    id {$idPrimary},
    role VARCHAR(120) NOT NULL,
    station VARCHAR(120),
    person_id INTEGER,
    start_time {$datetime},
    end_time {$datetime},
    token VARCHAR(120) UNIQUE,
    checked_in_at {$datetime},
    created_at {$datetime} NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS notifications (
    id {$idPrimary},
    type VARCHAR(120) NOT NULL,
    payload TEXT NOT NULL,
    created_at {$datetime} NOT NULL
)
SQL,
            <<<SQL
CREATE TABLE IF NOT EXISTS audit_log (
    id {$idPrimary},
    entity VARCHAR(80) NOT NULL,
    entity_id INTEGER NOT NULL,
    action VARCHAR(60) NOT NULL,
    user_id INTEGER,
    before_state TEXT,
    after_state TEXT,
    created_at {$datetime} NOT NULL
)
SQL,
        ];

        foreach ($queries as $query) {
            $pdo->exec($query);
        }

        self::ensureEventActiveColumn($pdo, $driver, $boolean);
    }

    private static function ensureEventActiveColumn(PDO $pdo, string $driver, string $boolean): void
    {
        if ($driver === 'mysql') {
            $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'is_active'");
            if ($stmt && $stmt->fetch() === false) {
                $pdo->exec('ALTER TABLE events ADD COLUMN is_active ' . $boolean . ' NOT NULL DEFAULT 0');
            }
            return;
        }

        $columns = $pdo->query('PRAGMA table_info(events)')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'is_active') {
                return;
            }
        }

        $pdo->exec('ALTER TABLE events ADD COLUMN is_active ' . $boolean . ' NOT NULL DEFAULT 0');
    }
    private static function ensureAdmin(PDO $pdo, array $admin): void
    {
        $email = mb_strtolower(trim($admin['email'] ?? ''));
        $password = $admin['password'] ?? '';
        $name = trim($admin['name'] ?? 'Administrator');
        $role = 'admin';

        if ($email === '' || $password === '') {
            throw new RuntimeException('Admin-Zugang benötigt E-Mail und Passwort.');
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $exists = $stmt->fetchColumn();
        if ($exists) {
            return;
        }

        $insert = $pdo->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, :created_at)');
        $insert->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'created_at' => (new DateTimeImmutable())->format('c'),
        ]);
    }

    private static function seedDemo(PDO $pdo): void
    {
        $now = (new DateTimeImmutable());

        $pdo->exec('DELETE FROM events');
        $pdo->exec('DELETE FROM classes');
        $pdo->exec('DELETE FROM persons');
        $pdo->exec('DELETE FROM horses');
        $pdo->exec('DELETE FROM clubs');

        $clubStmt = $pdo->prepare('INSERT INTO clubs (name, short_name) VALUES (:name, :short)');
        $clubStmt->execute(['name' => 'Reitverein Sonnental', 'short' => 'RVS']);
        $clubId = (int) $pdo->lastInsertId();

        $personStmt = $pdo->prepare('INSERT INTO persons (name, email, phone, roles, club_id, created_at) VALUES (:name, :email, :phone, :roles, :club, :created)');
        $demoPersons = [
            ['name' => 'Anna Richter', 'roles' => ['judge']],
            ['name' => 'Lena Office', 'roles' => ['office']],
            ['name' => 'Marco Steward', 'roles' => ['steward']],
            ['name' => 'Paul Teilnehmer', 'roles' => ['participant']],
        ];
        $personIds = [];
        foreach ($demoPersons as $person) {
            $personStmt->execute([
                'name' => $person['name'],
                'email' => strtolower(str_replace(' ', '.', $person['name'])) . '@demo.local',
                'phone' => '+49 170 1234567',
                'roles' => json_encode($person['roles'], JSON_THROW_ON_ERROR),
                'club' => $clubId,
                'created' => $now->format('c'),
            ]);
            $personIds[$person['roles'][0]] = (int) $pdo->lastInsertId();
        }

        $horseStmt = $pdo->prepare('INSERT INTO horses (name, owner_id, documents_ok, notes) VALUES (:name, :owner, :ok, :notes)');
        $horseStmt->execute([
            'name' => 'Flashlight',
            'owner' => $personIds['participant'] ?? null,
            'ok' => 1,
            'notes' => 'Impfungen aktuell',
        ]);

        $eventStmt = $pdo->prepare('INSERT INTO events (title, start_date, end_date, venues) VALUES (:title, :start, :end, :venues)');
        $eventStmt->execute([
            'title' => 'Sommerevent Demo',
            'start' => $now->modify('+7 days')->format('Y-m-d'),
            'end' => $now->modify('+8 days')->format('Y-m-d'),
            'venues' => json_encode(['Hauptplatz', 'Abreitehalle'], JSON_THROW_ON_ERROR),
        ]);
        $eventId = (int) $pdo->lastInsertId();

        $pdo->exec('UPDATE events SET is_active = 0');
        $setActive = $pdo->prepare('UPDATE events SET is_active = 1 WHERE id = :id');
        $setActive->execute(['id' => $eventId]);

        $classStmt = $pdo->prepare('INSERT INTO classes (event_id, label, arena, start_time, end_time, max_starters, judge_assignments, rules_json, tiebreaker_json) VALUES (:event, :label, :arena, :start, :end, :max, :judges, :rules, :tiebreaker)');
        $classStmt->execute([
            'event' => $eventId,
            'label' => 'Dressur L*',
            'arena' => 'Hauptplatz',
            'start' => $now->modify('+7 days 09:00')->format('c'),
            'end' => $now->modify('+7 days 11:00')->format('c'),
            'max' => 30,
            'judges' => json_encode(['Anna Richter'], JSON_THROW_ON_ERROR),
            'rules' => json_encode(self::dressagePreset(), JSON_THROW_ON_ERROR),
            'tiebreaker' => json_encode(['highest_movement', 'best_collective', 'random'], JSON_THROW_ON_ERROR),
        ]);
    }

    public static function dressagePreset(): array
    {
        return [
            'type' => 'dressage',
            'movements' => [
                ['label' => 'Trabverstärkungen', 'max' => 10],
                ['label' => 'Galoppvolten', 'max' => 10],
                ['label' => 'Übergänge', 'max' => 10],
            ],
            'step' => 0.5,
            'aggregate' => 'average',
            'drop_high_low' => false,
        ];
    }

    public static function jumpingPreset(): array
    {
        return [
            'type' => 'jumping',
            'fault_points' => true,
            'time_allowed' => 75,
            'time_penalty_per_second' => 1,
        ];
    }

    public static function westernPreset(): array
    {
        return [
            'type' => 'western',
            'maneuvers' => [
                ['label' => 'Stop', 'range' => [-1.5, 1.5]],
                ['label' => 'Spin', 'range' => [-1.5, 1.5]],
                ['label' => 'Lead Change', 'range' => [-1.5, 1.5]],
            ],
            'penalties' => [1, 2, 5],
        ];
    }
}
