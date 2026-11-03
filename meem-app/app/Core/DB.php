<?php
declare(strict_types=1);
namespace App\Core;

use PDO;

/** PDO wrapper. Prepared statements only: never concatenate user input into SQL. */
final class DB
{
    private static ?PDO $pdo = null;
    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;
        $driver = Env::get('DB_DRIVER', 'mysql');
        if ($driver === 'sqlite') {
            $path = Env::get('DB_SQLITE_PATH', 'storage/data/app.sqlite');
            if ($path[0] !== '/') $path = BASE_PATH . '/' . $path;
            self::$pdo = new PDO('sqlite:' . $path);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', Env::get('DB_HOST', 'localhost'), Env::get('DB_PORT', '3306'), Env::get('DB_NAME', ''));
            self::$pdo = new PDO($dsn, Env::get('DB_USER', ''), Env::get('DB_PASS', ''));
        }
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return self::$pdo;
    }
    public static function reset(): void { self::$pdo = null; }
    public static function isSqlite(): bool { return Env::get('DB_DRIVER', 'mysql') === 'sqlite'; }
    public static function all(string $sql, array $p = []): array { $s = self::pdo()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
    public static function one(string $sql, array $p = []): ?array { $s = self::pdo()->prepare($sql); $s->execute($p); $r = $s->fetch(); return $r ?: null; }
    public static function run(string $sql, array $p = []): int { $s = self::pdo()->prepare($sql); $s->execute($p); return $s->rowCount(); }
    public static function insert(string $sql, array $p = []): int { self::run($sql, $p); return (int) self::pdo()->lastInsertId(); }
    /** Run $fn inside a transaction; rolls back and rethrows on any exception. */
    public static function tx(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try { $r = $fn(); $pdo->commit(); return $r; }
        catch (\Throwable $e) { $pdo->rollBack(); throw $e; }
    }
    public static function now(): string { return gmdate('Y-m-d H:i:s'); }
}
