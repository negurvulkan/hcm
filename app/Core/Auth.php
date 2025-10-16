<?php
namespace App\Core;

use PDO;

class Auth
{
    private PDO $pdo;
    private RateLimiter $rateLimiter;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->rateLimiter = new RateLimiter('auth_attempts', 5, 300);
    }

    public function attempt(string $email, string $password): bool
    {
        if ($this->rateLimiter->tooManyAttempts()) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower($email)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            $this->rateLimiter->reset();
            return true;
        }

        $this->rateLimiter->hit();
        return false;
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
    }

    public function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function updatePassword(int $userId, string $password): void
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute(['password' => $hash, 'id' => $userId]);
    }
}
