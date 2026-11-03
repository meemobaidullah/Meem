<?php
declare(strict_types=1);
/** Usage: php database/create-admin.php "Name" email@example.com   (password asked on the terminal, never stored in code) */
require __DIR__ . '/../app/bootstrap.php';
use App\Core\DB;
[$_, $name, $email] = $argv + [null, null, null];
if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) { fwrite(STDERR, "Usage: php database/create-admin.php \"Name\" email\n"); exit(1); }
$pass = getenv('ADMIN_PASSWORD') ?: null;
if (!$pass) { fwrite(STDOUT, 'Password (min 12 chars): '); system('stty -echo 2>/dev/null'); $pass = trim((string) fgets(STDIN)); system('stty echo 2>/dev/null'); echo "\n"; }
if (strlen($pass) < 12) { fwrite(STDERR, "Password must be at least 12 characters.\n"); exit(1); }
DB::run('INSERT INTO users (name,email,password_hash,role,created_at) VALUES (?,?,?,?,?)', [$name, strtolower($email), password_hash($pass, PASSWORD_DEFAULT), 'admin', DB::now()]);
echo "Admin created: $email\n";
