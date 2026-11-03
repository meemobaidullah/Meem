<?php
declare(strict_types=1);
namespace App\Core;

/** Staff authentication with roles. Roles: admin (everything), editor (leads, KB, affiliates view), author (KB only). */
final class Auth
{
    private const PERMS = [
        'admin'  => ['*'],
        'editor' => ['dashboard', 'leads', 'kb', 'affiliates.view'],
        'author' => ['dashboard', 'kb'],
    ];
    public static function user(): ?array
    {
        $id = $_SESSION['uid'] ?? null;
        if (!$id) return null;
        $u = DB::one('SELECT id, name, email, role, active FROM users WHERE id = ?', [$id]);
        return ($u && (int) $u['active'] === 1) ? $u : null;
    }
    public static function can(?array $u, string $perm): bool
    {
        if (!$u) return false;
        $p = self::PERMS[$u['role']] ?? [];
        return in_array('*', $p, true) || in_array($perm, $p, true);
    }
    public static function require(string $perm): array
    {
        $u = self::user();
        if (!$u) Http::redirect('/admin/login');
        if (!self::can($u, $perm)) Http::abort(403, 'You do not have permission to open this page.');
        return $u;
    }
    /** @return string|null error message or null on success */
    public static function attempt(string $email, string $pass): ?string
    {
        $email = strtolower(trim($email));
        if (!Guard::allow('login:' . Http::ipHash(), 8, 600) || !Guard::allow('loginmail:' . hash('sha256', $email), 8, 600)) {
            return 'Too many attempts. Wait 10 minutes and try again.';
        }
        $u = DB::one('SELECT * FROM users WHERE email = ?', [$email]);
        // Always run a hash check so timing does not reveal whether the email exists.
        $hash = $u['password_hash'] ?? password_hash('x', PASSWORD_DEFAULT);
        $ok = password_verify($pass, $hash) && $u && (int) $u['active'] === 1;
        if (!$ok) { Guard::audit('login_failed', $email); return 'Email or password is not correct.'; }
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $u['id'];
        DB::run('UPDATE users SET last_login = ? WHERE id = ?', [DB::now(), $u['id']]);
        Guard::audit('login', '', $u);
        return null;
    }
    public static function logout(): void { $_SESSION = []; session_regenerate_id(true); }
}
