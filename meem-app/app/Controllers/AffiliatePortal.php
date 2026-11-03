<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{DB, Guard, Http};
use App\Services\Affiliates;

/** Public affiliate pages. While the program is "coming_soon" nobody can register, log in or claim anything. */
final class AffiliatePortal
{
    private function live(): bool { return Affiliates::isLive(); }
    private function me(): ?array { $id = $_SESSION['aid'] ?? null; return $id ? DB::one('SELECT * FROM affiliates WHERE id = ?', [$id]) : null; }
    private function money(int $c): string { return number_format($c / 100, 2); }

    public function overview(): void { echo Http::view('public/affiliate', ['live' => $this->live(), 'rules' => $this->rules()], 'public/layout'); }
    public function terms(): void { echo Http::view('public/affiliate_terms', ['live' => $this->live(), 'rules' => $this->rules()], 'public/layout'); }
    /** Published rules, straight from admin settings. A missing value is shown as "not set". */
    private function rules(): array
    {
        $r = [];
        foreach (Affiliates::KEYS as $k) $r[$k] = Guard::setting($k);
        return $r;
    }
    public function registerForm(): void { echo Http::view('public/aff_register', ['live' => $this->live(), 'errors' => [], 'old' => []], 'public/layout'); }
    public function registerSubmit(): void
    {
        Http::requireCsrf();
        if (!$this->live()) Http::abort(403, 'The affiliate program has not launched. Registration is closed.');
        if (!Guard::allow('affreg:' . Http::ipHash(), 5, 3600)) Http::abort(429, 'Too many attempts. Try again later.');
        $old = ['name' => Http::post('name'), 'email' => strtolower(Http::post('email'))];
        $pw = Http::post('password'); $err = [];
        if (Http::post('website') !== '') Http::abort(400, 'Could not submit.');
        if (Guard::len($old['name']) < 2) $err['name'] = 'Enter your name.';
        if (!Guard::validEmail($old['email'])) $err['email'] = 'Enter a valid email.';
        if (strlen($pw) < 12) $err['password'] = 'Use at least 12 characters.';
        if (Http::post('terms') !== '1') $err['terms'] = 'You must accept the affiliate terms.';
        if (!$err && DB::one('SELECT id FROM affiliates WHERE email = ?', [$old['email']])) $err['email'] = 'This email is already registered.';
        if ($err) { http_response_code(422); echo Http::view('public/aff_register', ['live' => true, 'errors' => $err, 'old' => $old], 'public/layout'); return; }
        DB::insert('INSERT INTO affiliates (name,email,password_hash,status,terms_version,created_at) VALUES (?,?,?,?,?,?)', [$old['name'], $old['email'], password_hash($pw, PASSWORD_DEFAULT), 'pending', Guard::setting('aff.terms_version'), DB::now()]);
        Guard::audit('affiliate_registered', $old['email']);
        Http::flash('ok', 'Application received. An admin must approve it before you get a referral link.');
        Http::redirect('/affiliate/login');
    }
    public function loginForm(): void { echo Http::view('public/aff_login', ['live' => $this->live(), 'error' => null], 'public/layout'); }
    public function loginSubmit(): void
    {
        Http::requireCsrf();
        if (!$this->live()) Http::abort(403, 'The affiliate program has not launched. Login is closed.');
        $email = strtolower(Http::post('email'));
        if (!Guard::allow('afflogin:' . Http::ipHash(), 8, 600)) Http::abort(429, 'Too many attempts. Wait 10 minutes.');
        $a = DB::one('SELECT * FROM affiliates WHERE email = ?', [$email]);
        $ok = password_verify(Http::post('password'), $a['password_hash'] ?? password_hash('x', PASSWORD_DEFAULT)) && $a;
        if (!$ok) { http_response_code(401); echo Http::view('public/aff_login', ['live' => true, 'error' => 'Email or password is not correct.'], 'public/layout'); return; }
        session_regenerate_id(true); $_SESSION['aid'] = (int) $a['id'];
        Http::redirect('/affiliate/dashboard');
    }
    public function logout(): void { Http::requireCsrf(); unset($_SESSION['aid']); Http::redirect('/affiliate'); }
    public function dashboard(): void
    {
        $a = $this->me();
        if (!$a || !$this->live()) Http::redirect('/affiliate/login');
        $id = $a['id'];
        $sum = fn(string $st) => (int) (DB::one('SELECT COALESCE(SUM(amount_cents),0) AS s FROM commissions WHERE affiliate_id = ? AND status = ?', [$id, $st])['s'] ?? 0);
        echo Http::view('public/aff_dashboard', [
            'a' => $a,
            'clicks' => (int) DB::one('SELECT COUNT(*) AS c FROM affiliate_clicks WHERE affiliate_id = ?', [$id])['c'],
            'referrals' => (int) DB::one('SELECT COUNT(*) AS c FROM referrals WHERE affiliate_id = ?', [$id])['c'],
            'conversions' => (int) DB::one("SELECT COUNT(*) AS c FROM referrals WHERE affiliate_id = ? AND status = 'converted'", [$id])['c'],
            'pending' => $this->money($sum('pending')), 'approved' => $this->money($sum('approved')), 'rejected' => $this->money($sum('rejected')), 'paid' => $this->money($sum('paid')),
            'payouts' => DB::all('SELECT * FROM payouts WHERE affiliate_id = ? ORDER BY id DESC LIMIT 50', [$id]),
            'link' => $a['code'] ? rtrim((string) \App\Core\Env::get('APP_URL', ''), '/') . '/r/' . $a['code'] : null,
            'min' => Guard::setting('aff.min_payout'),
        ], 'public/layout');
    }
    public function payout(): void
    {
        Http::requireCsrf();
        $a = $this->me();
        if (!$a || !$this->live() || $a['status'] !== 'approved') Http::abort(403, 'Not allowed.');
        $min = (int) round(((float) Guard::setting('aff.min_payout', '0')) * 100);
        $rows = DB::all("SELECT id, amount_cents FROM commissions WHERE affiliate_id = ? AND status = 'approved' AND payout_id IS NULL", [$a['id']]);
        $tot = array_sum(array_column($rows, 'amount_cents'));
        if ($tot <= 0 || $min <= 0 || $tot < $min) { Http::flash('err', 'Approved commissions have not reached the minimum payout.'); Http::redirect('/affiliate/dashboard'); }
        $pid = DB::insert('INSERT INTO payouts (affiliate_id, amount_cents, method, status, created_at) VALUES (?,?,?,?,?)', [$a['id'], $tot, Http::post('method') ?: null, 'requested', DB::now()]);
        foreach ($rows as $r) DB::run('UPDATE commissions SET payout_id = ? WHERE id = ?', [$pid, $r['id']]);
        Http::flash('ok', 'Payout requested.'); Http::redirect('/affiliate/dashboard');
    }
}
