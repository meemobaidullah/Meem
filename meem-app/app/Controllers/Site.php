<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{DB, Env, Guard, Http};
use App\Services\Affiliates;

final class Site
{
    public const MARKETS = ['Amazon FBA', 'Walmart WFS', 'TikTok Shop', 'Etsy', 'Temu', 'eBay', 'Shopify'];

    public function home(): void { echo Http::view('public/home', ['title' => 'Meem Obaidullah | Amazon & Marketplace Store Management'], 'public/layout'); }
    public function health(): void { DB::one('SELECT 1 AS ok'); Http::json(['status' => 'ok']); }

    public function contactForm(): void
    {
        $t = (string) time();
        echo Http::view('public/contact', ['title' => 'Contact', 'pageName' => 'Contact', 'errors' => [], 'old' => [], 'tok' => $t . '.' . hash_hmac('sha256', $t, Env::appKey()), 'markets' => self::MARKETS, 'wa' => Guard::setting('wa_url')], 'public/layout');
    }

    public function contactSubmit(): void
    {
        Http::requireCsrf();
        // Spam traps: hidden field must be empty and the form must have been open for at least 3 seconds.
        [$ts, $sig] = array_pad(explode('.', Http::post('_t'), 2), 2, '');
        $validTok = ctype_digit($ts) && hash_equals(hash_hmac('sha256', $ts, Env::appKey()), $sig);
        if (Http::post('website') !== '' || !$validTok || time() - (int) $ts < 3 || time() - (int) $ts > 7200) {
            Guard::audit('spam_blocked', 'contact'); Http::abort(400, 'Could not send the form. Please reload the page and try again.');
        }
        if (!Guard::allow('contact:' . Http::ipHash(), 5, 3600)) Http::abort(429, 'Too many submissions. Please try again later or email directly.');

        $old = ['name' => Http::post('name'), 'email' => strtolower(Http::post('email')), 'whatsapp' => Http::post('whatsapp'), 'message' => Http::post('message')];
        $mk = array_values(array_intersect(self::MARKETS, (array) ($_POST['markets'] ?? [])));
        $errors = [];
        if (Guard::len($old['name']) < 2 || Guard::len($old['name']) > 120) $errors['name'] = 'Enter your name (2 to 120 characters).';
        if (!Guard::validEmail($old['email'])) $errors['email'] = 'Enter a valid email address.';
        if ($old['whatsapp'] !== '' && !preg_match('/^\+?[0-9 ()\-]{7,25}$/', $old['whatsapp'])) $errors['whatsapp'] = 'Enter a valid phone number or leave it empty.';
        if (Guard::len($old['message']) > 3000) $errors['message'] = 'Message is too long (3000 characters maximum).';
        if (Http::post('consent') !== '1') $errors['consent'] = 'Please agree so we can contact you about your request.';
        if ($errors) {
            http_response_code(422);
            $t = (string) time();
            echo Http::view('public/contact', ['title' => 'Contact', 'pageName' => 'Contact', 'errors' => $errors, 'old' => $old + ['markets' => $mk], 'tok' => $t . '.' . hash_hmac('sha256', $t, Env::appKey()), 'markets' => self::MARKETS, 'wa' => Guard::setting('wa_url')], 'public/layout');
            return;
        }
        $ref = null;
        $c = $_COOKIE['mo_ref'] ?? '';
        if (Affiliates::isLive() && preg_match('/^[a-z0-9]{6,12}$/', (string) $c) && DB::one("SELECT id FROM affiliates WHERE code = ? AND status = 'approved'", [$c])) $ref = $c;
        $now = DB::now();
        $id = DB::insert('INSERT INTO leads (name,email,whatsapp,marketplaces,message,source,status,consent,consent_at,ref_code,ip_hash,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$old['name'], $old['email'], $old['whatsapp'] ?: null, implode(', ', $mk) ?: null, $old['message'] ?: null, 'contact', 'New', 1, $now, $ref, Http::ipHash(), $now, $now]);
        if ($ref) {
            $aff = DB::one('SELECT id FROM affiliates WHERE code = ?', [$ref]);
            DB::insert('INSERT INTO referrals (affiliate_id, lead_id, status, created_at) VALUES (?,?,?,?)', [$aff['id'], $id, 'pending', $now]);
        }
        Guard::audit('lead_created', 'lead #' . $id);
        // Email notification is NOT sent yet: it needs SMTP settings (see docs/KNOWN-LIMITATIONS.md).
        echo Http::view('public/thanks', [], 'public/layout');
    }

    /** Referral link: /r/CODE. Only approved affiliates, only while the program is live. */
    public function referral(string $code): void
    {
        $code = strtolower($code);
        if (Affiliates::isLive() && preg_match('/^[a-z0-9]{6,12}$/', $code)) {
            $aff = DB::one("SELECT id FROM affiliates WHERE code = ? AND status = 'approved'", [$code]);
            if ($aff && Guard::allow('click:' . $aff['id'] . ':' . Http::ipHash(), 30, 3600)) {
                DB::insert('INSERT INTO affiliate_clicks (affiliate_id, ip_hash, landing, created_at) VALUES (?,?,?,?)', [$aff['id'], Http::ipHash(), mb_substr($_SERVER['HTTP_REFERER'] ?? '', 0, 255), DB::now()]);
                $days = (int) Guard::setting('aff.cookie_days', '0');
                if ($days > 0) setcookie('mo_ref', $code, ['expires' => time() + $days * 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => (($_SERVER['HTTPS'] ?? '') === 'on')]);
            }
        }
        Http::redirect('/');
    }
}
