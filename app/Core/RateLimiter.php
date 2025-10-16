<?php
namespace App\Core;

class RateLimiter
{
    private string $key;
    private int $maxAttempts;
    private int $decaySeconds;

    public function __construct(string $key, int $maxAttempts, int $decaySeconds)
    {
        $this->key = $key;
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;

        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
    }

    public function hit(): void
    {
        $entry = $_SESSION['rate_limits'][$this->key] ?? ['count' => 0, 'expires_at' => time() + $this->decaySeconds];
        if ($entry['expires_at'] <= time()) {
            $entry = ['count' => 0, 'expires_at' => time() + $this->decaySeconds];
        }
        $entry['count']++;
        $_SESSION['rate_limits'][$this->key] = $entry;
    }

    public function tooManyAttempts(): bool
    {
        $entry = $_SESSION['rate_limits'][$this->key] ?? null;
        if (!$entry) {
            return false;
        }
        if ($entry['expires_at'] <= time()) {
            $this->reset();
            return false;
        }
        return $entry['count'] >= $this->maxAttempts;
    }

    public function reset(): void
    {
        unset($_SESSION['rate_limits'][$this->key]);
    }
}
