<?php
declare(strict_types=1);
/** Usage: php database/migrate.php   (idempotent: creates missing tables, records versions) */
require __DIR__ . '/../app/bootstrap.php';
use App\Core\DB;

$pk = DB::isSqlite() ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
$eng = DB::isSqlite() ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$migrations = [
'001_users' => "CREATE TABLE users (id $pk, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL DEFAULT 'author', active INT NOT NULL DEFAULT 1, created_at VARCHAR(19) NOT NULL, last_login VARCHAR(19) NULL)$eng",
'002_settings' => "CREATE TABLE settings (skey VARCHAR(120) NOT NULL PRIMARY KEY, svalue TEXT NULL, updated_at VARCHAR(19) NOT NULL)$eng",
'003_audit' => "CREATE TABLE audit_logs (id $pk, user_id INT NULL, actor VARCHAR(190) NULL, action VARCHAR(80) NOT NULL, detail TEXT NULL, ip_hash VARCHAR(64) NULL, created_at VARCHAR(19) NOT NULL)$eng",
'004_rate' => "CREATE TABLE rate_limits (rkey VARCHAR(190) NOT NULL PRIMARY KEY, hits INT NOT NULL, reset_at INT NOT NULL)$eng",
'005_leads' => "CREATE TABLE leads (id $pk, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL, whatsapp VARCHAR(40) NULL, marketplaces VARCHAR(255) NULL, message TEXT NULL, source VARCHAR(60) NOT NULL DEFAULT 'contact', status VARCHAR(20) NOT NULL DEFAULT 'New', consent INT NOT NULL DEFAULT 0, consent_at VARCHAR(19) NULL, ref_code VARCHAR(40) NULL, deal_amount_cents INT NULL, ip_hash VARCHAR(64) NULL, created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL)$eng",
'006_lead_notes' => "CREATE TABLE lead_notes (id $pk, lead_id INT NOT NULL, user_id INT NULL, note TEXT NOT NULL, created_at VARCHAR(19) NOT NULL)$eng",
'007_affiliates' => "CREATE TABLE affiliates (id $pk, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', code VARCHAR(40) NULL UNIQUE, terms_version VARCHAR(40) NULL, created_at VARCHAR(19) NOT NULL, decided_at VARCHAR(19) NULL)$eng",
'008_clicks' => "CREATE TABLE affiliate_clicks (id $pk, affiliate_id INT NOT NULL, ip_hash VARCHAR(64) NULL, landing VARCHAR(255) NULL, created_at VARCHAR(19) NOT NULL)$eng",
'009_referrals' => "CREATE TABLE referrals (id $pk, affiliate_id INT NOT NULL, lead_id INT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', created_at VARCHAR(19) NOT NULL)$eng",
'010_commissions' => "CREATE TABLE commissions (id $pk, affiliate_id INT NOT NULL, referral_id INT NOT NULL, base_cents INT NOT NULL, amount_cents INT NOT NULL, rule_snapshot TEXT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', reason VARCHAR(255) NULL, created_at VARCHAR(19) NOT NULL, decided_at VARCHAR(19) NULL, payout_id INT NULL)$eng",
'011_payouts' => "CREATE TABLE payouts (id $pk, affiliate_id INT NOT NULL, amount_cents INT NOT NULL, method VARCHAR(60) NULL, status VARCHAR(20) NOT NULL DEFAULT 'requested', note VARCHAR(255) NULL, created_at VARCHAR(19) NOT NULL, paid_at VARCHAR(19) NULL)$eng",
'012_kb' => "CREATE TABLE kb_entries (id $pk, question VARCHAR(255) NOT NULL, answer TEXT NOT NULL, tags VARCHAR(255) NULL, published INT NOT NULL DEFAULT 1, updated_at VARCHAR(19) NOT NULL)$eng",
'013_migrations' => "CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(80) NOT NULL PRIMARY KEY, applied_at VARCHAR(19) NOT NULL)$eng",
];
DB::pdo()->exec($migrations['013_migrations']);
$done = array_column(DB::all('SELECT name FROM schema_migrations'), 'name');
foreach ($migrations as $name => $sql) {
    if ($name === '013_migrations' || in_array($name, $done, true)) continue;
    DB::pdo()->exec($sql);
    DB::run('INSERT INTO schema_migrations (name, applied_at) VALUES (?, ?)', [$name, DB::now()]);
    echo "applied $name\n";
}
echo "migrations up to date\n";
