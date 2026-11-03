<?php
declare(strict_types=1);
namespace App\Core;

/** Reads KEY=VALUE pairs from a .env file kept OUTSIDE the public web root. */
final class Env
{
    private static array $v = [];
    public static function load(string $file): void
    {
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
                [$k, $val] = explode('=', $line, 2);
                self::$v[trim($k)] = trim($val, " \t\"'");
            }
        }
    }
    public static function get(string $k, ?string $default = null): ?string
    {
        return $_ENV[$k] ?? getenv($k) ?: (self::$v[$k] ?? $default);
    }
    public static function set(string $k, string $val): void { self::$v[$k] = $val; }
    /** Secret used for IP hashing and signed form tokens. Refuses to run with a missing or weak key. */
    public static function appKey(): string
    {
        $k = (string) self::get('APP_KEY', '');
        if (strlen($k) < 32 || $k === 'change-me') {
            throw new \RuntimeException('APP_KEY is missing or shorter than 32 characters. Set it in .env (see docs/INSTALL-HOSTINGER.md).');
        }
        return $k;
    }
}
