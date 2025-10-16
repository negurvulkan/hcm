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
        Updater::runOnConnection($pdo, $driver);
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
