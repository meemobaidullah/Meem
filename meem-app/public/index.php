<?php
declare(strict_types=1);
// App code lives OUTSIDE the web root on the server (e.g. /home/USER/meem-app). Either layout works:
foreach ([__DIR__ . '/../app/bootstrap.php', __DIR__ . '/../meem-app/app/bootstrap.php'] as $__b) { if (is_file($__b)) { require $__b; break; } }
if (!defined('BASE_PATH')) { http_response_code(500); exit('App folder not found. See docs/INSTALL-HOSTINGER.md'); }

use App\Core\{Http, Env, Seo};
use App\Controllers\{Site, Admin, AffiliatePortal};

Http::start();
try {
    $m = Http::method(); $p = Http::path();
    $seg = array_values(array_filter(explode('/', $p), 'strlen'));
    $routes = [
        'GET /'                    => [Site::class, 'home'],
        'GET /health'              => [Site::class, 'health'],
        'GET /contact'             => [Site::class, 'contactForm'],
        'POST /contact'            => [Site::class, 'contactSubmit'],
        'GET /affiliate'           => [AffiliatePortal::class, 'overview'],
        'GET /affiliate/register'  => [AffiliatePortal::class, 'registerForm'],
        'POST /affiliate/register' => [AffiliatePortal::class, 'registerSubmit'],
        'GET /affiliate/login'     => [AffiliatePortal::class, 'loginForm'],
        'POST /affiliate/login'    => [AffiliatePortal::class, 'loginSubmit'],
        'GET /affiliate/dashboard' => [AffiliatePortal::class, 'dashboard'],
        'POST /affiliate/payout'   => [AffiliatePortal::class, 'payout'],
        'POST /affiliate/logout'   => [AffiliatePortal::class, 'logout'],
        'GET /affiliate/terms'     => [AffiliatePortal::class, 'terms'],
        'GET /admin/login'         => [Admin::class, 'loginForm'],
        'POST /admin/login'        => [Admin::class, 'loginSubmit'],
        'POST /admin/logout'       => [Admin::class, 'logout'],
        'GET /admin'               => [Admin::class, 'dashboard'],
        'GET /admin/leads'         => [Admin::class, 'leads'],
        'GET /admin/kb'            => [Admin::class, 'kb'],
        'POST /admin/kb'           => [Admin::class, 'kbSave'],
        'POST /admin/kb/delete'    => [Admin::class, 'kbDelete'],
        'GET /admin/settings'      => [Admin::class, 'settings'],
        'POST /admin/settings'     => [Admin::class, 'settingsSave'],
        'GET /admin/users'         => [Admin::class, 'users'],
        'POST /admin/users'        => [Admin::class, 'userSave'],
        'GET /admin/affiliates'    => [Admin::class, 'affiliates'],
        'POST /admin/affiliates'   => [Admin::class, 'affiliateAction'],
        'GET /admin/audit'         => [Admin::class, 'audit'],
    ];
    if (in_array($m, ['GET', 'HEAD'], true) && ($to = Seo::legacyTarget($p)) !== null) {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        Http::redirect($to . ($qs !== '' ? '?' . $qs : ''), 301);
    }
    if ($m === 'GET' && $p === '/sitemap.xml') { header('Content-Type: application/xml; charset=utf-8'); echo Seo::sitemap(); exit; }
    if ($m === 'GET' && $p === '/robots.txt') { header('Content-Type: text/plain; charset=utf-8'); echo Seo::robots(); exit; }
    $key = "$m $p";
    if (isset($routes[$key])) { [$c, $fn] = $routes[$key]; (new $c)->$fn(); exit; }
    if ($m === 'GET' && count($seg) === 2 && $seg[0] === 'r') { (new Site)->referral($seg[1]); exit; }
    if (count($seg) === 3 && $seg[0] === 'admin' && $seg[1] === 'leads') {
        if ($m === 'GET') { (new Admin)->leadShow((int) $seg[2]); exit; }
        if ($m === 'POST') { (new Admin)->leadUpdate((int) $seg[2]); exit; }
    }
    Http::abort(404, 'Page not found.');
} catch (Throwable $e) {
    $dir = BASE_PATH . '/storage/logs'; if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($dir . '/error.log', '[' . gmdate('c') . '] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n", FILE_APPEND);
    http_response_code(500);
    echo Env::get('APP_DEBUG') === 'true' ? '<pre>' . htmlspecialchars((string) $e) . '</pre>' : 'Something went wrong. It has been logged.';
}
