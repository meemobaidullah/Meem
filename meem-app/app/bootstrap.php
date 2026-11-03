<?php
declare(strict_types=1);
// Tiny PSR-4 style autoloader: App\Core\Router -> app/Core/Router.php
spl_autoload_register(function (string $class): void {
    if (strpos($class, 'App\\') !== 0) return;
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});
define('BASE_PATH', dirname(__DIR__));
App\Core\Env::load(BASE_PATH . '/.env');
date_default_timezone_set('UTC');
