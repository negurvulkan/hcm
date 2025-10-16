<?php
namespace App\Core;

class App
{
    private static array $container = [];

    public static function set(string $key, mixed $value): void
    {
        self::$container[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$container);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$container[$key] ?? $default;
    }
}
