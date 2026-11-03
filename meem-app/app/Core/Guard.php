<?php
declare(strict_types=1);
namespace App\Core;

/** Rate limiting, validation, audit log and settings storage. */
final class Guard
{
    /** Returns true if allowed. Fixed window per key. */
    public static function allow(string $key, int $max, int $windowSec): bool
    {
        $now = time();
        $row = DB::one('SELECT hits, reset_at FROM rate_limits WHERE rkey = ?', [$key]);
        if (!$row || $row['reset_at'] < $now) {
            DB::run('DELETE FROM rate_limits WHERE rkey = ?', [$key]);
            DB::run('INSERT INTO rate_limits (rkey, hits, reset_at) VALUES (?,?,?)', [$key, 1, $now + $windowSec]);
            return true;
        }
        if ($row['hits'] >= $max) return false;
        DB::run('UPDATE rate_limits SET hits = hits + 1 WHERE rkey = ?', [$key]);
        return true;
    }
    public static function audit(string $action, string $detail = '', ?array $user = null): void
    {
        DB::run('INSERT INTO audit_logs (user_id, actor, action, detail, ip_hash, created_at) VALUES (?,?,?,?,?,?)',
            [$user['id'] ?? null, $user['email'] ?? null, $action, mb_substr($detail, 0, 1000), Http::ipHash(), DB::now()]);
    }
    public static function setting(string $k, ?string $d = null): ?string
    {
        $r = DB::one('SELECT svalue FROM settings WHERE skey = ?', [$k]);
        return $r && $r['svalue'] !== null && $r['svalue'] !== '' ? $r['svalue'] : $d;
    }
    public static function saveSetting(string $k, string $v): void
    {
        DB::run('DELETE FROM settings WHERE skey = ?', [$k]);
        DB::run('INSERT INTO settings (skey, svalue, updated_at) VALUES (?,?,?)', [$k, $v, DB::now()]);
    }
    public static function validEmail(string $e): bool { return strlen($e) <= 190 && filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
    public static function len(string $s): int { return mb_strlen($s); }
}
