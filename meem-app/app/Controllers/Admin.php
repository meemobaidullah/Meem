<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, DB, Guard, Http};
use App\Services\Affiliates;

final class Admin
{
    public const STATUSES = ['New', 'Contacted', 'Qualified', 'In Discussion', 'Converted', 'Not Interested'];
    public const ROLES = ['admin', 'editor', 'author'];
    private function page(string $view, array $d, array $u): void { echo Http::view("admin/$view", $d + ['u' => $u], 'admin/layout'); }

    public function loginForm(): void { echo Http::view('admin/login', ['error' => null], null); }
    public function loginSubmit(): void
    {
        Http::requireCsrf();
        $err = Auth::attempt(Http::post('email'), $_POST['password'] ?? '');
        if ($err) { http_response_code(401); echo Http::view('admin/login', ['error' => $err], null); return; }
        Http::redirect('/admin');
    }
    public function logout(): void { Http::requireCsrf(); Auth::logout(); Http::redirect('/admin/login'); }
    public function dashboard(): void
    {
        $u = Auth::require('dashboard');
        $c = fn(string $sql) => (int) DB::one($sql)['c'];
        $this->page('dashboard', ['leads' => $c('SELECT COUNT(*) c FROM leads'), 'new' => $c("SELECT COUNT(*) c FROM leads WHERE status='New'"), 'kb' => $c('SELECT COUNT(*) c FROM kb_entries'), 'affPending' => $c("SELECT COUNT(*) c FROM affiliates WHERE status='pending'"), 'status' => Guard::setting('aff.status', 'coming_soon')], $u);
    }
    public function leads(): void
    {
        $u = Auth::require('leads');
        $st = $_GET['status'] ?? '';
        $rows = in_array($st, self::STATUSES, true) ? DB::all('SELECT * FROM leads WHERE status = ? ORDER BY id DESC LIMIT 200', [$st]) : DB::all('SELECT * FROM leads ORDER BY id DESC LIMIT 200');
        $this->page('leads', ['rows' => $rows, 'st' => $st], $u);
    }
    public function leadShow(int $id): void
    {
        $u = Auth::require('leads');
        $lead = DB::one('SELECT * FROM leads WHERE id = ?', [$id]) ?? Http::abort(404, 'Lead not found.');
        $this->page('lead', ['lead' => $lead, 'notes' => DB::all('SELECT n.*, u.name AS who FROM lead_notes n LEFT JOIN users u ON u.id = n.user_id WHERE lead_id = ? ORDER BY n.id DESC', [$id])], $u);
    }
    public function leadUpdate(int $id): void
    {
        $u = Auth::require('leads'); Http::requireCsrf();
        $lead = DB::one('SELECT * FROM leads WHERE id = ?', [$id]) ?? Http::abort(404, 'Lead not found.');
        $st = Http::post('status');
        if (!in_array($st, self::STATUSES, true)) Http::abort(422, 'Unknown status.');
        $amt = Http::post('deal_amount');
        $cents = null;
        if ($st === 'Converted' && $amt !== '') {
            if (!is_numeric($amt) || (float) $amt < 0 || (float) $amt > 100000000) Http::abort(422, 'Enter a valid deal amount.');
            $cents = (int) round((float) $amt * 100);
        }
        DB::run('UPDATE leads SET status = ?, deal_amount_cents = COALESCE(?, deal_amount_cents), updated_at = ? WHERE id = ?', [$st, $cents, DB::now(), $id]);
        if (Http::post('note') !== '') DB::run('INSERT INTO lead_notes (lead_id,user_id,note,created_at) VALUES (?,?,?,?)', [$id, $u['id'], mb_substr(Http::post('note'), 0, 2000), DB::now()]);
        Guard::audit('lead_updated', "lead #$id -> $st", $u);
        if ($st === 'Converted' && $lead['status'] !== 'Converted' && Auth::can($u, 'affiliates.manage')) {
            $fresh = DB::one('SELECT * FROM leads WHERE id = ?', [$id]);
            $msg = Affiliates::createCommissionForLead($fresh);
            if ($msg) Http::flash('err', 'No commission created: ' . $msg);
            elseif ($fresh['ref_code']) Http::flash('ok', 'Pending commission created for the referring affiliate.');
        }
        Http::flash('ok', 'Lead updated.'); Http::redirect("/admin/leads/$id");
    }
    public function kb(): void
    {
        $u = Auth::require('kb');
        $edit = isset($_GET['edit']) ? DB::one('SELECT * FROM kb_entries WHERE id = ?', [(int) $_GET['edit']]) : null;
        $this->page('kb', ['rows' => DB::all('SELECT * FROM kb_entries ORDER BY id DESC'), 'edit' => $edit], $u);
    }
    public function kbSave(): void
    {
        $u = Auth::require('kb'); Http::requireCsrf();
        $q = Http::post('question'); $a = Http::post('answer'); $id = (int) Http::post('id');
        if (Guard::len($q) < 3 || Guard::len($q) > 255 || Guard::len($a) < 3 || Guard::len($a) > 5000) Http::abort(422, 'Question (3-255) and answer (3-5000 characters) are required.');
        $pub = Http::post('published') === '1' ? 1 : 0;
        if ($id) DB::run('UPDATE kb_entries SET question=?, answer=?, tags=?, published=?, updated_at=? WHERE id=?', [$q, $a, Http::post('tags') ?: null, $pub, DB::now(), $id]);
        else DB::insert('INSERT INTO kb_entries (question,answer,tags,published,updated_at) VALUES (?,?,?,?,?)', [$q, $a, Http::post('tags') ?: null, $pub, DB::now()]);
        Guard::audit('kb_saved', $q, $u); Http::flash('ok', 'Saved.'); Http::redirect('/admin/kb');
    }
    public function kbDelete(): void
    {
        $u = Auth::require('kb'); Http::requireCsrf();
        DB::run('DELETE FROM kb_entries WHERE id = ?', [(int) Http::post('id')]); Guard::audit('kb_deleted', Http::post('id'), $u);
        Http::redirect('/admin/kb');
    }
    private const FIELDS = ['gtm_id' => '/^GTM-[A-Z0-9]{4,12}$/', 'ga4_id' => '/^G-[A-Z0-9]{6,14}$/', 'clarity_id' => '/^[a-z0-9]{8,12}$/', 'gsc_verification' => '/^[A-Za-z0-9_\-]{10,120}$/', 'contact_email' => null, 'wa_url' => '/^https:\/\/wa\.me\/[0-9]{7,15}$/', 'office_hours' => '/^.{0,120}$/'];
    public function settings(): void
    {
        $u = Auth::require('settings');
        $vals = []; foreach (array_merge(array_keys(self::FIELDS), Affiliates::KEYS) as $k) $vals[$k] = Guard::setting($k, '');
        $this->page('settings', ['v' => $vals, 'missing' => Affiliates::missingForLaunch()], $u);
    }
    public function settingsSave(): void
    {
        $u = Auth::require('settings'); Http::requireCsrf();
        $errors = [];
        foreach (self::FIELDS as $k => $re) {
            $v = Http::post($k);
            if ($v !== '' && (($k === 'contact_email' && !Guard::validEmail($v)) || ($re && !preg_match($re, $v)))) { $errors[] = "Invalid value for $k"; continue; }
            Guard::saveSetting($k, $v);
        }
        $type = Http::post('aff.type');
        if ($type !== '' && !in_array($type, ['percent', 'fixed'], true)) $errors[] = 'Commission type must be percent or fixed';
        foreach (['aff.value', 'aff.min_payout'] as $k) if (Http::post($k) !== '' && (!is_numeric(Http::post($k)) || (float) Http::post($k) < 0)) $errors[] = "$k must be a positive number";
        if ($type === 'percent' && Http::post('aff.value') !== '' && (float) Http::post('aff.value') > 100) $errors[] = 'Percent cannot exceed 100';
        foreach (['aff.cookie_days', 'aff.approval_days'] as $k) if (Http::post($k) !== '' && !ctype_digit(Http::post($k))) $errors[] = "$k must be a whole number of days";
        if ($errors) { Http::flash('err', implode('; ', $errors)); Http::redirect('/admin/settings'); }
        foreach (Affiliates::KEYS as $k) { if ($k === 'aff.status') continue; Guard::saveSetting($k, mb_substr(Http::post($k), 0, 2000)); }
        $want = Http::post('aff.status');
        if ($want === 'live') {
            $miss = Affiliates::missingForLaunch();
            if ($miss) { Http::flash('err', 'Cannot go live. Missing: ' . implode(', ', $miss)); Guard::saveSetting('aff.status', 'coming_soon'); Http::redirect('/admin/settings'); }
            Guard::saveSetting('aff.status', 'live');
        } else Guard::saveSetting('aff.status', 'coming_soon');
        Guard::audit('settings_saved', '', $u); Http::flash('ok', 'Settings saved.'); Http::redirect('/admin/settings');
    }
    public function users(): void { $u = Auth::require('users'); $this->page('users', ['rows' => DB::all('SELECT id,name,email,role,active,last_login FROM users ORDER BY id')], $u); }
    public function userSave(): void
    {
        $u = Auth::require('users'); Http::requireCsrf();
        $act = Http::post('action');
        if ($act === 'create') {
            $email = strtolower(Http::post('email')); $role = Http::post('role'); $pw = $_POST['password'] ?? '';
            if (!Guard::validEmail($email) || !in_array($role, self::ROLES, true) || strlen($pw) < 12 || Guard::len(Http::post('name')) < 2) Http::abort(422, 'Name, valid email, role and a password of 12+ characters are required.');
            if (DB::one('SELECT id FROM users WHERE email = ?', [$email])) Http::abort(422, 'Email already exists.');
            DB::insert('INSERT INTO users (name,email,password_hash,role,created_at) VALUES (?,?,?,?,?)', [Http::post('name'), $email, password_hash($pw, PASSWORD_DEFAULT), $role, DB::now()]);
            Guard::audit('user_created', "$email ($role)", $u);
        } elseif ($act === 'toggle') {
            $id = (int) Http::post('id'); if ($id === (int) $u['id']) Http::abort(422, 'You cannot disable your own account.');
            DB::run('UPDATE users SET active = 1 - active WHERE id = ?', [$id]); Guard::audit('user_toggled', "#$id", $u);
        }
        Http::redirect('/admin/users');
    }
    public function affiliates(): void
    {
        $u = Auth::require('affiliates.view');
        $this->page('affiliates', [
            'rows' => DB::all('SELECT * FROM affiliates ORDER BY id DESC'),
            'commissions' => DB::all('SELECT c.*, a.email FROM commissions c JOIN affiliates a ON a.id = c.affiliate_id ORDER BY c.id DESC LIMIT 100'),
            'payouts' => DB::all('SELECT p.*, a.email FROM payouts p JOIN affiliates a ON a.id = p.affiliate_id ORDER BY p.id DESC LIMIT 100'),
            'canManage' => Auth::can($u, 'affiliates.manage'),
        ], $u);
    }
    public function affiliateAction(): void
    {
        $u = Auth::require('affiliates.manage'); Http::requireCsrf();
        $id = (int) Http::post('id'); $act = Http::post('action');
        switch ($act) {
            case 'approve_aff':
                do { $code = Affiliates::newCode(); } while (DB::one('SELECT id FROM affiliates WHERE code = ?', [$code]));
                DB::run("UPDATE affiliates SET status='approved', code=COALESCE(code, ?), decided_at=? WHERE id=?", [$code, DB::now(), $id]); break;
            case 'reject_aff': DB::run("UPDATE affiliates SET status='rejected', decided_at=? WHERE id=?", [DB::now(), $id]); break;
            case 'approve_com':
                $c = DB::one('SELECT * FROM commissions WHERE id = ?', [$id]);
                $days = (int) Guard::setting('aff.approval_days', '0');
                if ($c && $c['status'] === 'pending' && strtotime($c['created_at'] . ' UTC') + $days * 86400 > time()) { Http::flash('err', "Approval period ($days days) has not passed yet."); Http::redirect('/admin/affiliates'); }
                DB::run("UPDATE commissions SET status='approved', decided_at=? WHERE id=? AND status='pending'", [DB::now(), $id]); break;
            case 'reject_com': DB::run("UPDATE commissions SET status='rejected', reason=?, decided_at=? WHERE id=? AND status IN ('pending','approved')", [mb_substr(Http::post('reason', 'Rejected by admin'), 0, 250), DB::now(), $id]); break;
            case 'pay':
                DB::run("UPDATE payouts SET status='paid', paid_at=? WHERE id=? AND status='requested'", [DB::now(), $id]);
                DB::run("UPDATE commissions SET status='paid' WHERE payout_id=?", [$id]); break;
            default: Http::abort(422, 'Unknown action.');
        }
        Guard::audit('affiliate_action', "$act #$id", $u); Http::redirect('/admin/affiliates');
    }
    public function audit(): void { $u = Auth::require('audit'); $this->page('audit', ['rows' => DB::all('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 300')], $u); }
}
