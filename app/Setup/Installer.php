<?php
namespace App\Setup;

use App\Core\Database;
use PDO;
use RuntimeException;

class Installer
{
    public static function run(array $dbConfig): void
    {
        $pdo = Database::connect($dbConfig);
        self::migrate($pdo);
        self::seed($pdo);
    }

    private static function migrate(PDO $pdo): void
    {
        $queries = [
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT NOT NULL,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS tournaments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                location TEXT,
                start_date TEXT,
                end_date TEXT
            )',
            'CREATE TABLE IF NOT EXISTS arenas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tournament_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                FOREIGN KEY(tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
            )',
            'CREATE TABLE IF NOT EXISTS persons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                role TEXT NOT NULL,
                email TEXT,
                phone TEXT
            )',
            'CREATE TABLE IF NOT EXISTS horses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                breed TEXT,
                owner TEXT
            )',
            'CREATE TABLE IF NOT EXISTS shifts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                role TEXT NOT NULL,
                person_name TEXT NOT NULL,
                arena_id INTEGER,
                start_time TEXT,
                end_time TEXT,
                FOREIGN KEY(arena_id) REFERENCES arenas(id) ON DELETE SET NULL
            )'
        ];

        foreach ($queries as $query) {
            $pdo->exec($query);
        }
    }

    private static function seed(PDO $pdo): void
    {
        $pdo->exec('DELETE FROM users');
        $pdo->exec('DELETE FROM tournaments');
        $pdo->exec('DELETE FROM arenas');
        $pdo->exec('DELETE FROM persons');
        $pdo->exec('DELETE FROM horses');
        $pdo->exec('DELETE FROM shifts');

        $roles = ['admin', 'meldestelle', 'richter', 'parcours', 'helfer', 'moderation', 'teilnehmer'];
        $now = (new \DateTimeImmutable())->format('c');

        foreach ($roles as $role) {
            $name = ucfirst($role) . ' Demo';
            $email = $role . '@demo.local';
            $password = password_hash($role, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, :created_at)');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'created_at' => $now,
            ]);
        }

        $pdo->prepare('INSERT INTO tournaments (name, location, start_date, end_date) VALUES (:name, :location, :start, :end)')
            ->execute([
                'name' => 'Sommerturnier 2024',
                'location' => 'Reitverein Sonnental',
                'start' => '2024-08-10',
                'end' => '2024-08-12',
            ]);
        $tournamentId = (int) $pdo->lastInsertId();

        $arenas = ['Arena Nord', 'Arena Süd'];
        $arenaIds = [];
        foreach ($arenas as $arena) {
            $stmt = $pdo->prepare('INSERT INTO arenas (tournament_id, name) VALUES (:tournament_id, :name)');
            $stmt->execute(['tournament_id' => $tournamentId, 'name' => $arena]);
            $arenaIds[] = (int) $pdo->lastInsertId();
        }

        $personRoles = ['Reiter', 'Reiterin'];
        for ($i = 1; $i <= 20; $i++) {
            $stmt = $pdo->prepare('INSERT INTO persons (name, role, email, phone) VALUES (:name, :role, :email, :phone)');
            $stmt->execute([
                'name' => 'Reiter ' . $i,
                'role' => $personRoles[$i % 2],
                'email' => 'reiter' . $i . '@demo.local',
                'phone' => '+49 170 000' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            ]);
        }

        for ($i = 1; $i <= 15; $i++) {
            $stmt = $pdo->prepare('INSERT INTO horses (name, breed, owner) VALUES (:name, :breed, :owner)');
            $stmt->execute([
                'name' => 'Pferd ' . $i,
                'breed' => $i % 2 === 0 ? 'Warmblut' : 'Pony',
                'owner' => 'Reiter ' . (($i % 20) + 1),
            ]);
        }

        $richterNamen = ['Richterin Clara', 'Richter Paul', 'Richterin Jana', 'Richter Felix'];
        foreach ($richterNamen as $name) {
            $stmt = $pdo->prepare('INSERT INTO persons (name, role, email, phone) VALUES (:name, :role, :email, :phone)');
            $stmt->execute([
                'name' => $name,
                'role' => 'Richter',
                'email' => str_replace(' ', '.', strtolower($name)) . '@demo.local',
                'phone' => '+49 170 555' . rand(100, 999),
            ]);
        }

        $shiftTemplate = [
            ['role' => 'Abreiteplatz', 'start' => '2024-08-10 08:00', 'end' => '2024-08-10 12:00'],
            ['role' => 'Richten', 'start' => '2024-08-10 09:00', 'end' => '2024-08-10 13:30'],
            ['role' => 'Parcoursdienst', 'start' => '2024-08-10 12:30', 'end' => '2024-08-10 17:00'],
        ];

        foreach ($shiftTemplate as $index => $shift) {
            $stmt = $pdo->prepare('INSERT INTO shifts (role, person_name, arena_id, start_time, end_time) VALUES (:role, :person, :arena, :start, :end)');
            $stmt->execute([
                'role' => $shift['role'],
                'person' => 'Helfer ' . ($index + 1),
                'arena' => $arenaIds[$index % count($arenaIds)],
                'start' => $shift['start'],
                'end' => $shift['end'],
            ]);
        }
    }
}
