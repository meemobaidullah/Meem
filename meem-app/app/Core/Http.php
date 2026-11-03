<?php
declare(strict_types=1);
namespace App\Core;

/** Request helpers, sessions, CSRF, security headers, views, redirects. */
final class Http
{
    private static string $nonce = '';
    public static function nonce(): string { return self::$nonce; }
    public static function start(): void
    {
        $https = (($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_name('mo_sess');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        self::$nonce = base64_encode(random_bytes(16));
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self' 'nonce-" . self::$nonce . "' https://www.googletagmanager.com https://www.clarity.ms https://scripts.clarity.ms; connect-src 'self' https://www.google-analytics.com https://www.googletagmanager.com https://*.clarity.ms; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
        if ($https) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    public static function method(): string { return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); }
    public static function path(): string { $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; return $p === '/' ? '/' : rtrim($p, '/'); }
    public static function ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }
    public static function ipHash(): string { return hash_hmac('sha256', self::ip(), Env::get('APP_KEY', 'change-me')); }
    public static function post(string $k, string $d = ''): string { $k = str_replace('.', '_', $k); $v = $_POST[$k] ?? $d; return is_string($v) ? trim($v) : $d; }
    public static function e(?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    public static function csrf(): string
    {
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf'];
    }
    public static function csrfField(): string { return '<input type="hidden" name="_csrf" value="' . self::e(self::csrf()) . '">'; }
    public static function csrfOk(): bool
    {
        $t = $_POST['_csrf'] ?? '';
        return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
    }
    public static function requireCsrf(): void { if (!self::csrfOk()) self::abort(419, 'Session expired. Go back, reload the page and try again.'); }
    public static function abort(int $code, string $msg): never
    {
        http_response_code($code);
        echo self::view('public/error', ['code' => $code, 'msg' => $msg], 'public/layout');
        exit;
    }
    public static function redirect(string $to, int $code = 302): never { header('Location: ' . $to, true, $code); exit; }
    public static function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
    public static function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
    public static function view(string $name, array $data = [], ?string $layout = null): string
    {
        $render = function (string $__f, array $__d): string { extract($__d, EXTR_SKIP); ob_start(); require $__f; return (string) ob_get_clean(); };
        $out = $render(BASE_PATH . '/views/' . $name . '.php', $data);
        if ($layout) $out = $render(BASE_PATH . '/views/' . $layout . '.php', $data + ['content' => $out]);
        return $out;
    }
    public static function json(array $d, int $code = 200): never { http_response_code($code); header('Content-Type: application/json'); echo json_encode($d); exit; }
}
