<?php
declare(strict_types=1);
/** Integration tests. Usage: php tests/run.php   (starts a local PHP server with a throwaway SQLite database) */
$root = dirname(__DIR__);
$db = sys_get_temp_dir() . '/meem_test_' . bin2hex(random_bytes(4)) . '.sqlite';
$port = random_int(20000, 40000);
$env = ['DB_DRIVER' => 'sqlite', 'DB_SQLITE_PATH' => $db, 'APP_KEY' => bin2hex(random_bytes(16)), 'APP_URL' => "http://127.0.0.1:$port", 'APP_DEBUG' => 'true'];
foreach ($env as $k => $v) putenv("$k=$v");
passthru('php ' . escapeshellarg("$root/database/migrate.php") . ' > /dev/null');
$srv = proc_open(['php', '-S', "127.0.0.1:$port", '-t', "$root/public", "$root/public/index.php"], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, $root, $env + $_ENV);
register_shutdown_function(function () use ($srv, $db) { proc_terminate($srv); @unlink($db); });
usleep(600000);
$pdo = new PDO("sqlite:$db"); $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$q = fn(string $s, array $p = []) => (function () use ($pdo, $s, $p) { $st = $pdo->prepare($s); $st->execute($p); return $st; })();
$pw = 'Test-' . bin2hex(random_bytes(8)); // throwaway password, test DB only
foreach ([['Admin T', 'admin@test.local', 'admin'], ['Editor T', 'editor@test.local', 'editor'], ['Author T', 'author@test.local', 'author']] as [$n, $e, $r])
    $q('INSERT INTO users (name,email,password_hash,role,created_at) VALUES (?,?,?,?,?)', [$n, $e, password_hash($pw, PASSWORD_DEFAULT), $r, gmdate('Y-m-d H:i:s')]);

final class C {
    public string $jar; public function __construct(public string $base) { $this->jar = tempnam(sys_get_temp_dir(), 'jar'); }
    public function req(string $m, string $path, array $data = []): array {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 10]);
        if ($m === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); }
        $r = (string) curl_exec($ch); $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        return ['code' => $code, 'head' => substr($r, 0, $hs), 'body' => substr($r, $hs)];
    }
    public function get(string $p): array { return $this->req('GET', $p); }
    public function csrf(string $p): string { $r = $this->get($p); preg_match('/name="_csrf" value="([a-f0-9]+)"/', $r['body'], $m); return $m[1] ?? ''; }
    public function post(string $p, array $d, string $from): array { $d['_csrf'] = $this->csrf($from); return $this->req('POST', $p, $d); }
}
$results = []; $n = 0;
function t(string $name, bool $ok, string $extra = ''): void { global $results; $results[] = [$name, $ok, $extra]; echo ($ok ? 'PASS' : 'FAIL') . "  $name" . ($ok || !$extra ? '' : "  ($extra)") . "\n"; }
$base = "http://127.0.0.1:$port";
$reset = fn() => $q('DELETE FROM rate_limits');
$login = function (string $email) use ($base, $pw, $reset) { $reset(); $c = new C($base); $r = $c->post('/admin/login', ['email' => $email, 'password' => $pw], '/admin/login'); return [$c, $r]; };

// ---- Basics & headers
$a = new C($base);
$r = $a->get('/health'); t('health endpoint returns ok', $r['code'] === 200 && str_contains($r['body'], 'ok'));
t('security headers present (CSP, nosniff, frame)', str_contains($r['head'], 'Content-Security-Policy') && stripos($r['head'], 'X-Content-Type-Options: nosniff') !== false && stripos($r['head'], 'X-Frame-Options') !== false);
t('unknown page returns 404', $a->get('/nope')['code'] === 404);
$h = (new C($base))->get('/contact')['head']; t('session cookie is HttpOnly and SameSite', stripos($h, 'HttpOnly') !== false && stripos($h, 'SameSite=Lax') !== false);

// ---- Contact form
$form = $a->get('/contact'); preg_match('/name="_t" value="([^"]+)"/', $form['body'], $m); $tok = $m[1] ?? '';
t('contact form renders with CSRF + spam token', $form['code'] === 200 && $tok !== '' && str_contains($form['body'], '_csrf'));
$r = $a->req('POST', '/contact', ['name' => 'X Y', 'email' => 'x@y.com', 'consent' => '1', '_t' => $tok]); t('contact POST without CSRF is rejected (419)', $r['code'] === 419);
sleep(3);
$base_post = fn(array $o = []) => array_merge(['name' => 'Rana Test', 'email' => 'rana@example.com', 'whatsapp' => '+1 555 0100', 'message' => 'Hello', 'consent' => '1', '_t' => $tok, 'markets' => ['Amazon FBA', 'Etsy']], $o);
$r = $a->post('/contact', $base_post(['website' => 'http://spam']), '/contact'); t('honeypot filled is blocked', $r['code'] === 400);
$r = $a->post('/contact', $base_post(['email' => 'bad']), '/contact'); t('invalid email gives 422 with message', $r['code'] === 422 && str_contains($r['body'], 'valid email'));
$r = $a->post('/contact', $base_post(['consent' => '']), '/contact'); t('missing consent gives 422', $r['code'] === 422 && str_contains($r['body'], 'agree'));
$r = $a->post('/contact', $base_post(['whatsapp' => 'abc']), '/contact'); t('invalid phone gives 422', $r['code'] === 422);
$r = $a->post('/contact', $base_post(['_t' => (string) time() . '.' . str_repeat('a', 64)]), '/contact'); t('forged spam token is blocked', $r['code'] === 400);
$r = $a->post('/contact', $base_post(['name' => '<script>alert(1)</script>']), '/contact');
$row = $q("SELECT * FROM leads WHERE email='rana@example.com'")->fetch();
t('valid contact stored as lead New with consent + hashed IP', $r['code'] === 200 && $row && $row['status'] === 'New' && (int) $row['consent'] === 1 && $row['consent_at'] && !str_contains((string) $row['ip_hash'], '127.0.0.1') && $row['marketplaces'] === 'Amazon FBA, Etsy');
$spam = false; for ($i = 0; $i < 8; $i++) { $r = $a->post('/contact', $base_post(), '/contact'); if ($r['code'] === 429) { $spam = true; break; } }
t('contact form is rate limited (429)', $spam);

// ---- Admin auth
$a2 = new C($base); t('admin area redirects to login when logged out', $a2->get('/admin')['code'] === 302);
$reset(); $r = $a2->post('/admin/login', ['email' => 'admin@test.local', 'password' => 'wrong'], '/admin/login'); t('wrong password rejected (401)', $r['code'] === 401);
$r = $a2->post('/admin/login', ['email' => "' OR 1=1 --", 'password' => "' OR '1'='1"], '/admin/login'); t('SQL injection login attempt fails', $r['code'] === 401);
[$adm, $r] = $login('admin@test.local'); t('admin logs in', $r['code'] === 302 && $adm->get('/admin')['code'] === 200);
$r = $adm->req('POST', '/admin/logout', []); t('logout without CSRF rejected', $r['code'] === 419);
$l = $adm->get('/admin/leads'); t('admin sees leads list', $l['code'] === 200 && str_contains($l['body'], 'rana@example.com'));
$lid = (int) $q("SELECT id FROM leads WHERE name LIKE '%script%'")->fetchColumn();
$d = $adm->get("/admin/leads/$lid"); t('lead detail escapes HTML (no raw <script>)', str_contains($d['body'], '&lt;script&gt;') && !str_contains($d['body'], '<script>alert(1)'));
$r = $adm->get('/admin/leads?status=' . urlencode("' OR 1=1 --")); t('SQL injection in status filter is harmless', $r['code'] === 200);
$r = $adm->post("/admin/leads/$lid", ['status' => 'Contacted', 'note' => 'Called him'], "/admin/leads/$lid"); $st = $q('SELECT status FROM leads WHERE id=?', [$lid])->fetchColumn();
t('lead status update + note saved', $r['code'] === 302 && $st === 'Contacted' && $q('SELECT COUNT(*) FROM lead_notes')->fetchColumn() == 1);
$r = $adm->post("/admin/leads/$lid", ['status' => 'Hacked'], "/admin/leads/$lid"); t('unknown lead status rejected', $r['code'] === 422);
t('audit log records actions', str_contains($adm->get('/admin/audit')['body'], 'lead_updated'));

// ---- Roles
[$ed] = $login('editor@test.local'); t('editor can open leads', $ed->get('/admin/leads')['code'] === 200);
t('editor cannot open settings (403)', $ed->get('/admin/settings')['code'] === 403); t('editor cannot open users (403)', $ed->get('/admin/users')['code'] === 403);
[$au] = $login('author@test.local'); t('author can open knowledge base', $au->get('/admin/kb')['code'] === 200); t('author cannot open leads (403)', $au->get('/admin/leads')['code'] === 403);
t('author cannot open affiliates (403)', $au->get('/admin/affiliates')['code'] === 403);
$r = $ed->post('/admin/affiliates', ['action' => 'approve_aff', 'id' => '1'], '/admin/affiliates'); t('editor cannot approve affiliates (403)', $r['code'] === 403);

// ---- Knowledge base
$r = $au->post('/admin/kb', ['question' => 'Do you guarantee reinstatement?', 'answer' => 'No. Outcomes are never guaranteed.', 'published' => '1'], '/admin/kb'); t('author creates KB entry', $r['code'] === 302 && str_contains($au->get('/admin/kb')['body'], 'guarantee reinstatement'));
$r = $au->post('/admin/kb', ['question' => 'x', 'answer' => 'y'], '/admin/kb'); t('KB validation rejects too-short entry', $r['code'] === 422);

// ---- Settings
$r = $adm->post('/admin/settings', ['gtm_id' => 'bad<script>'], '/admin/settings'); t('invalid GTM ID is refused', $r['code'] === 302 && str_contains($adm->get('/admin/settings')['body'], 'Invalid value'));
$adm->post('/admin/settings', ['gtm_id' => 'GTM-5Q2CWTXJ', 'wa_url' => 'https://wa.me/15550100', 'gsc_verification' => 'abcDEF1234567890xyz'], '/admin/settings');
$home = $a2->get('/'); t('GTM + Search Console tags appear on public pages once configured', str_contains($home['body'], 'GTM-5Q2CWTXJ') && str_contains($home['body'], 'google-site-verification') && preg_match("/nonce-([A-Za-z0-9+\/=]+)/", $home['head'], $nn) && str_contains($home['body'], 'nonce="' . $nn[1] . '"'));
$adm->post('/admin/settings', ['gtm_id' => 'GTM-5Q2CWTXJ', 'clarity_id' => 'z6zlgq2maq'], '/admin/settings'); $home2 = $a2->get('/'); t('Clarity tag appears with nonce once configured', str_contains($home2['body'], 'clarity.ms/tag') && str_contains($home2['body'], 'z6zlgq2maq'));
$adm->post('/admin/settings', ['clarity_id' => 'BAD ID!'], '/admin/settings'); t('invalid Clarity ID is refused', str_contains($adm->get('/admin/settings')['body'], 'Invalid value'));
$r = $adm->post('/admin/settings', ['wa_url' => 'http://evil.com'], '/admin/settings'); t('non-wa.me WhatsApp URL refused', str_contains($adm->get('/admin/settings')['body'], 'Invalid value'));

// ---- Affiliate: coming soon
$aff = $a2->get('/affiliate'); t('affiliate page shows Coming soon by default', str_contains($aff['body'], 'Coming soon') && str_contains($aff['body'], 'not set yet'));
$r = $a2->post('/affiliate/register', ['name' => 'Aff One', 'email' => 'aff1@example.com', 'password' => 'long-password-123', 'terms' => '1'], '/contact'); t('registration blocked while coming soon (403)', $r['code'] === 403 && !$q("SELECT 1 FROM affiliates")->fetch());
t('referral link does nothing while coming soon', $a2->get('/r/abcdef12')['code'] === 302 && !$q('SELECT 1 FROM affiliate_clicks')->fetch());
$r = $adm->post('/admin/settings', ['aff.status' => 'live'], '/admin/settings'); t('program cannot go live with missing rules', str_contains($adm->get('/admin/settings')['body'], 'Cannot go live') && $q("SELECT svalue FROM settings WHERE skey='aff.status'")->fetchColumn() !== 'live');
$rules = ['aff.type' => 'percent', 'aff.value' => '10', 'aff.eligible' => 'Signed management agreements', 'aff.cookie_days' => '30', 'aff.approval_days' => '7', 'aff.min_payout' => '50', 'aff.methods' => 'Bank transfer', 'aff.schedule' => 'Monthly', 'aff.refund_rule' => 'Commission is cancelled if the client is refunded', 'aff.terms_version' => 'test-v1', 'aff.status' => 'live'];
$adm->post('/admin/settings', ['aff.type' => 'percent', 'aff.value' => '150'] + $rules, '/admin/settings'); t('percent above 100 refused', str_contains($adm->get('/admin/settings')['body'], 'exceed 100'));
$adm->post('/admin/settings', $rules, '/admin/settings'); t('program goes live once all rules are set', $q("SELECT svalue FROM settings WHERE skey='aff.status'")->fetchColumn() === 'live');
$aff = $a2->get('/affiliate/terms'); t('terms page shows the configured rules', str_contains($aff['body'], '10% of the client') && str_contains($aff['body'], 'Bank transfer'));

// ---- Affiliate flow
$f = new C($base); $reset();
$r = $f->post('/affiliate/register', ['name' => 'Aff One', 'email' => 'aff1@example.com', 'password' => 'short', 'terms' => '1'], '/affiliate/register'); t('affiliate weak password rejected', $r['code'] === 422);
$r = $f->post('/affiliate/register', ['name' => 'Aff One', 'email' => 'aff1@example.com', 'password' => 'long-password-123', 'terms' => '1'], '/affiliate/register'); t('affiliate registers as pending', $r['code'] === 302 && $q("SELECT status FROM affiliates WHERE email='aff1@example.com'")->fetchColumn() === 'pending');
$r = $f->post('/affiliate/login', ['email' => 'aff1@example.com', 'password' => 'long-password-123'], '/affiliate/login'); $dash = $f->get('/affiliate/dashboard'); t('pending affiliate sees dashboard without a referral link', $dash['code'] === 200 && str_contains($dash['body'], 'pending') && !str_contains($dash['body'], '/r/'));
$affId = (int) $q("SELECT id FROM affiliates WHERE email='aff1@example.com'")->fetchColumn();
$adm->post('/admin/affiliates', ['action' => 'approve_aff', 'id' => (string) $affId], '/admin/affiliates'); $code = (string) $q('SELECT code FROM affiliates WHERE id=?', [$affId])->fetchColumn(); t('admin approves affiliate and a code is issued', strlen($code) === 8);
$v = new C($base); $r = $v->get("/r/$code"); t('referral click recorded and cookie set', $r['code'] === 302 && stripos($r['head'], 'mo_ref=') !== false && $q('SELECT COUNT(*) FROM affiliate_clicks')->fetchColumn() == 1);
$f2 = $v->get('/contact'); preg_match('/name="_t" value="([^"]+)"/', $f2['body'], $m); sleep(3);
$r = $v->post('/contact', ['name' => 'Client Referred', 'email' => 'client@example.com', 'consent' => '1', '_t' => $m[1], 'markets' => ['Walmart WFS']], '/contact'); $lead = $q("SELECT * FROM leads WHERE email='client@example.com'")->fetch();
t('referred lead is attributed to the affiliate', $lead && $lead['ref_code'] === $code && $q('SELECT COUNT(*) FROM referrals WHERE lead_id=?', [$lead['id']])->fetchColumn() == 1);
$r = $adm->post("/admin/leads/{$lead['id']}", ['status' => 'Converted', 'deal_amount' => '1000'], "/admin/leads/{$lead['id']}"); $com = $q('SELECT * FROM commissions')->fetch();
t('converting a referred lead creates a pending commission of exactly 10% (100.00)', $com && (int) $com['amount_cents'] === 10000 && (int) $com['base_cents'] === 100000 && $com['status'] === 'pending' && str_contains((string) $com['rule_snapshot'], 'percent'));
$adm->post("/admin/leads/{$lead['id']}", ['status' => 'Converted', 'deal_amount' => '1000'], "/admin/leads/{$lead['id']}"); t('no duplicate commission on repeat save', $q('SELECT COUNT(*) FROM commissions')->fetchColumn() == 1);
$adm->post('/admin/affiliates', ['action' => 'approve_com', 'id' => (string) $com['id']], '/admin/affiliates'); t('commission cannot be approved before the approval period ends', $q('SELECT status FROM commissions WHERE id=?', [$com['id']])->fetchColumn() === 'pending');
$q('UPDATE commissions SET created_at=? WHERE id=?', [gmdate('Y-m-d H:i:s', time() - 8 * 86400), $com['id']]);
$adm->post('/admin/affiliates', ['action' => 'approve_com', 'id' => (string) $com['id']], '/admin/affiliates'); t('commission approved after the approval period', $q('SELECT status FROM commissions WHERE id=?', [$com['id']])->fetchColumn() === 'approved');
$f->post('/affiliate/login', ['email' => 'aff1@example.com', 'password' => 'long-password-123'], '/affiliate/login');
$q("UPDATE settings SET svalue='500' WHERE skey='aff.min_payout'"); $f->post('/affiliate/payout', ['method' => 'Bank transfer'], '/affiliate/dashboard'); t('payout below the minimum is refused', $q('SELECT COUNT(*) FROM payouts')->fetchColumn() == 0);
$q("UPDATE settings SET svalue='50' WHERE skey='aff.min_payout'"); $f->post('/affiliate/payout', ['method' => 'Bank transfer'], '/affiliate/dashboard'); t('payout request created at/above minimum', $q('SELECT COUNT(*) FROM payouts')->fetchColumn() == 1 && (int) $q('SELECT amount_cents FROM payouts')->fetchColumn() === 10000);
$pid = (int) $q('SELECT id FROM payouts')->fetchColumn(); $adm->post('/admin/affiliates', ['action' => 'pay', 'id' => (string) $pid], '/admin/affiliates'); t('admin marks payout paid and commission becomes paid', $q('SELECT status FROM payouts WHERE id=?', [$pid])->fetchColumn() === 'paid' && $q('SELECT status FROM commissions WHERE id=?', [$com['id']])->fetchColumn() === 'paid');
// refund / rejection
$q("INSERT INTO leads (name,email,status,consent,ref_code,deal_amount_cents,created_at,updated_at) VALUES ('R','r@example.com','Converted',1,?,50000,?,?)", [$code, gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s')]);
$l2 = $q("SELECT * FROM leads WHERE email='r@example.com'")->fetch(); \App\Services\Affiliates::class; 
$adm->post("/admin/leads/{$l2['id']}", ['status' => 'Contacted'], "/admin/leads/{$l2['id']}"); $adm->post("/admin/leads/{$l2['id']}", ['status' => 'Converted', 'deal_amount' => '500'], "/admin/leads/{$l2['id']}");
$c2 = $q("SELECT * FROM commissions WHERE base_cents=50000")->fetch(); $adm->post('/admin/affiliates', ['action' => 'reject_com', 'id' => (string) $c2['id'], 'reason' => 'Client refunded'], '/admin/affiliates');
t('refunded referral: admin rejects commission with a reason', $c2 && $q('SELECT status FROM commissions WHERE id=?', [$c2['id']])->fetchColumn() === 'rejected' && $q('SELECT reason FROM commissions WHERE id=?', [$c2['id']])->fetchColumn() === 'Client refunded');

// ---- Commission calculator (unit)
require $root . '/app/bootstrap.php'; \App\Core\Env::set('DB_DRIVER', 'sqlite'); \App\Core\Env::set('DB_SQLITE_PATH', $db); \App\Core\DB::reset();
$setv = fn($k, $v) => \App\Core\Guard::saveSetting($k, $v);
$setv('aff.type', 'percent'); $setv('aff.value', '12.5'); t('calc: 12.5% of 800.00 = 100.00', \App\Services\Affiliates::calculate(80000) === 10000);
$setv('aff.type', 'fixed'); $setv('aff.value', '75'); t('calc: fixed 75 = 7500 cents', \App\Services\Affiliates::calculate(123456) === 7500);
$setv('aff.type', 'percent'); $setv('aff.value', '101'); t('calc: percent above 100 returns null', \App\Services\Affiliates::calculate(10000) === null);
$setv('aff.value', '10'); t('calc: zero or negative base returns null', \App\Services\Affiliates::calculate(0) === null && \App\Services\Affiliates::calculate(-5) === null);
$q("DELETE FROM settings WHERE skey='aff.type'"); t('calc: missing rule returns null (no invented reward)', \App\Services\Affiliates::calculate(10000) === null);

// ---- Brute force login
$reset(); $bf = new C($base); $hit = false; for ($i = 0; $i < 12; $i++) { $r = $bf->post('/admin/login', ['email' => 'admin@test.local', 'password' => 'nope' . $i], '/admin/login'); if (str_contains($r['body'], 'Too many attempts')) { $hit = true; break; } }
t('admin login is rate limited after repeated failures', $hit);

// ---- SEO (phase 1)
$seo = new C($base); $h = $seo->get('/');
preg_match('/<title>(.*?)<\/title>/s', $h['body'], $tm); preg_match('/name="description" content="([^"]*)"/', $h['body'], $dm);
t('home title is 60 characters or fewer', isset($tm[1]) && mb_strlen(html_entity_decode($tm[1])) <= 60);
t('home meta description exists and is 155 characters or fewer', isset($dm[1]) && mb_strlen(html_entity_decode($dm[1])) <= 155 && $dm[1] !== '');
t('home has a canonical link and index,follow', str_contains($h['body'], 'rel="canonical"') && str_contains($h['body'], 'index,follow'));
preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $h['body'], $jm); $ld = json_decode($jm[1] ?? '', true);
$types = array_column($ld['@graph'] ?? [], '@type');
t('home has valid JSON-LD (Organization, Person, WebSite) and no rating/award/review claims', in_array('Organization', $types) && in_array('Person', $types) && in_array('WebSite', $types) && !preg_match('/aggregateRating|award|review/i', $jm[1] ?? ''));
t('contact page has BreadcrumbList JSON-LD', str_contains($seo->get('/contact')['body'], 'BreadcrumbList'));
$r = $seo->get('/case-stady/amazon-ranking-4-months?x=1');
t('/case-stady/... 301s to /case-study/... in one hop, keeping the query', $r['code'] === 301 && preg_match('#Location: /case-study/amazon-ranking-4-months\?x=1#i', $r['head']) === 1);
$r = $seo->get('/sucess/seo-awards'); t('/sucess/... 301s to /success/...', $r['code'] === 301 && stripos($r['head'], 'Location: /success/seo-awards') !== false);
$sm = $seo->get('/sitemap.xml'); t('sitemap.xml is valid XML and lists only indexable URLs (no admin, affiliate, misspelled slugs)', $sm['code'] === 200 && @simplexml_load_string($sm['body']) !== false && !preg_match('#/admin|/affiliate|stady|sucess#', $sm['body']));
$rb = $seo->get('/robots.txt'); t('robots.txt blocks admin and points to the sitemap', str_contains($rb['body'], 'Disallow: /admin') && str_contains($rb['body'], '/sitemap.xml'));
$ng = $seo->get('/admin/login'); t('admin login page is noindex', str_contains($ng['body'], 'noindex'));

// ---- Report
$pass = count(array_filter($results, fn($r) => $r[1])); $fail = count($results) - $pass;
echo "\n$pass passed, $fail failed, " . count($results) . " total\n";
$md = "# Automated test results (local, throwaway SQLite)\n\nRun: `php tests/run.php`\n\n| Result | Test |\n|---|---|\n";
foreach ($results as [$nm, $ok]) $md .= '| ' . ($ok ? 'Passed' : '**FAILED**') . " | $nm |\n";
file_put_contents($root . '/docs/AUTOMATED-TEST-RESULTS.md', $md . "\n$pass passed, $fail failed.\n");
exit($fail ? 1 : 0);
