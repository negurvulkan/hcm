<?php
namespace App\I18n;

class LocaleManager
{
    private array $supported;
    private string $default;
    private string $sessionKey;
    private string $cookieName;

    public function __construct(array $supported, string $default = 'de', string $sessionKey = '_locale', string $cookieName = 'app_locale')
    {
        $normalized = [];
        foreach ($supported as $locale) {
            $candidate = $this->sanitize($locale);
            if ($candidate !== null) {
                $normalized[] = $candidate;
            }
        }
        if ($normalized === []) {
            $normalized = ['de'];
        }

        $this->supported = array_values(array_unique($normalized));
        $defaultCandidate = $this->sanitize($default);
        $this->default = in_array($defaultCandidate, $this->supported, true) ? $defaultCandidate : $this->supported[0];
        $this->sessionKey = $sessionKey;
        $this->cookieName = $cookieName;
    }

    public function supported(): array
    {
        return $this->supported;
    }

    public function default(): string
    {
        return $this->default;
    }

    public function detect(): string
    {
        $locale = $this->detectFromQuery();
        if ($locale !== null) {
            $this->persist($locale);
            return $locale;
        }

        $locale = $this->detectFromSession();
        if ($locale !== null) {
            return $locale;
        }

        $locale = $this->detectFromCookie();
        if ($locale !== null) {
            $this->persist($locale);
            return $locale;
        }

        $locale = $this->detectFromAcceptLanguage();
        if ($locale !== null) {
            $this->persist($locale);
            return $locale;
        }

        $this->persist($this->default);
        return $this->default;
    }

    public function persist(string $locale): void
    {
        $normalized = $this->normalize($locale);
        if ($normalized === null) {
            return;
        }

        $_SESSION[$this->sessionKey] = $normalized;
        if (PHP_SAPI !== 'cli') {
            setcookie($this->cookieName, $normalized, ['path' => '/', 'samesite' => 'Lax']);
        }
    }

    private function detectFromQuery(): ?string
    {
        if (!isset($_GET['lang'])) {
            return null;
        }

        $requested = $this->normalize((string) $_GET['lang']);
        return $requested;
    }

    private function detectFromSession(): ?string
    {
        if (!isset($_SESSION[$this->sessionKey])) {
            return null;
        }

        return $this->normalize((string) $_SESSION[$this->sessionKey]);
    }

    private function detectFromCookie(): ?string
    {
        if (!isset($_COOKIE[$this->cookieName])) {
            return null;
        }

        return $this->normalize((string) $_COOKIE[$this->cookieName]);
    }

    private function detectFromAcceptLanguage(): ?string
    {
        $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if ($header === '') {
            return null;
        }

        $locales = explode(',', $header);
        foreach ($locales as $entry) {
            $parts = explode(';', $entry);
            $localePart = trim($parts[0]);
            if ($localePart === '') {
                continue;
            }

            $normalized = $this->normalize($localePart);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function sanitize(string $locale): ?string
    {
        $locale = strtolower(substr($locale, 0, 2));
        if ($locale === '') {
            return null;
        }

        return $locale;
    }

    private function normalize(string $locale): ?string
    {
        $locale = $this->sanitize($locale);
        if ($locale === null) {
            return null;
        }

        if (!in_array($locale, $this->supported, true)) {
            return null;
        }

        return $locale;
    }
}
