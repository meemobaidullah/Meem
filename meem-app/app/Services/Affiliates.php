<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\DB;
use App\Core\Guard;

/**
 * Affiliate rules live in the settings table and are set by an admin.
 * Nothing here has a default reward: if a rule is missing the program counts as NOT configured
 * and no commission can be created.
 */
final class Affiliates
{
    public const KEYS = ['aff.status', 'aff.type', 'aff.value', 'aff.cookie_days', 'aff.approval_days', 'aff.min_payout', 'aff.methods', 'aff.schedule', 'aff.eligible', 'aff.refund_rule', 'aff.terms_version'];

    public static function isLive(): bool { return Guard::setting('aff.status', 'coming_soon') === 'live'; }

    /** All six disclosures must be filled in before the program may go live. */
    public static function missingForLaunch(): array
    {
        $need = ['aff.type' => 'commission type', 'aff.value' => 'commission amount', 'aff.eligible' => 'eligible services/actions', 'aff.cookie_days' => 'attribution window', 'aff.approval_days' => 'approval period', 'aff.min_payout' => 'minimum payout', 'aff.methods' => 'payment methods', 'aff.schedule' => 'payment schedule', 'aff.refund_rule' => 'refund/cancellation rule', 'aff.terms_version' => 'published terms version'];
        $miss = [];
        foreach ($need as $k => $label) if (Guard::setting($k) === null) $miss[] = $label;
        return $miss;
    }
    /** Commission in cents from a base (revenue) in cents. Returns null if rules are missing/invalid. */
    public static function calculate(int $baseCents): ?int
    {
        $type = Guard::setting('aff.type'); $val = Guard::setting('aff.value');
        if ($baseCents <= 0 || $type === null || $val === null || !is_numeric($val) || (float) $val < 0) return null;
        if ($type === 'percent') { if ((float) $val > 100) return null; return (int) round($baseCents * (float) $val / 100); }
        if ($type === 'fixed') return (int) round((float) $val * 100);
        return null;
    }
    public static function newCode(): string { return strtolower(substr(bin2hex(random_bytes(6)), 0, 8)); }
    /** Called when a lead is marked Converted with a deal amount. */
    public static function hasCommissionForLead(int $leadId): bool
    {
        return DB::one('SELECT c.id FROM commissions c JOIN referrals r ON r.id = c.referral_id WHERE r.lead_id = ?', [$leadId]) !== null;
    }
    /** Returns null on success, otherwise the reason no commission was created. */
    public static function createCommissionForLead(array $lead): ?string
    {
        if (empty($lead['ref_code'])) return 'Lead has no referral code.';
        if (empty($lead['deal_amount_cents'])) return 'Enter the deal amount to create the commission.';
        if (!self::isLive()) return 'The affiliate program is not live.';
        $aff = DB::one("SELECT * FROM affiliates WHERE code = ? AND status = 'approved'", [$lead['ref_code']]);
        if (!$aff) return 'Referral code is not an approved affiliate.';
        if (self::hasCommissionForLead((int) $lead['id'])) return 'Commission already exists for this lead.';
        $amt = self::calculate((int) $lead['deal_amount_cents']);
        if ($amt === null) return 'Commission rules are not configured.';
        $ref = DB::one('SELECT id FROM referrals WHERE lead_id = ?', [$lead['id']]);
        $rid = $ref['id'] ?? DB::insert('INSERT INTO referrals (affiliate_id, lead_id, status, created_at) VALUES (?,?,?,?)', [$aff['id'], $lead['id'], 'converted', DB::now()]);
        $snap = json_encode(['type' => Guard::setting('aff.type'), 'value' => Guard::setting('aff.value'), 'terms' => Guard::setting('aff.terms_version')]);
        DB::insert('INSERT INTO commissions (affiliate_id, referral_id, base_cents, amount_cents, rule_snapshot, status, created_at) VALUES (?,?,?,?,?,?,?)', [$aff['id'], $rid, $lead['deal_amount_cents'], $amt, $snap, 'pending', DB::now()]);
        return null;
    }
}
