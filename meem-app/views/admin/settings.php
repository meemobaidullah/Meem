<?php use App\Core\Http; $f = fn($k, $label, $hint = '') => '<label for="' . $k . '">' . $label . '</label><input id="' . $k . '" name="' . $k . '" type="text" value="' . Http::e($v[$k] ?? '') . '">' . ($hint ? '<small>' . $hint . '</small>' : ''); ?>
<h1>Settings</h1>
<form method="post" action="/admin/settings"><?= Http::csrfField() ?>
<div class="card"><h2>Site and integrations</h2>
<?= $f('gtm_id', 'Google Tag Manager ID', 'Format GTM-XXXXXXX. Empty = not loaded.') ?><?= $f('ga4_id', 'GA4 Measurement ID', 'Format G-XXXXXXXXXX. Informational: configure GA4 inside GTM.') ?>
<?= $f('clarity_id', 'Microsoft Clarity project ID', 'Tracking ID only (for example 10 lowercase letters/numbers). Do NOT put the Clarity API token here.') ?><?= $f('gsc_verification', 'Search Console verification code', 'The content value of the google-site-verification meta tag.') ?><?= $f('contact_email', 'Contact email') ?><?= $f('wa_url', 'WhatsApp URL', 'Verified link only, https://wa.me/NUMBER') ?><?= $f('office_hours', 'Office hours') ?></div>
<div class="card"><h2>Affiliate program rules</h2><p>Nothing has a default. All of these must be set before the program can go live.</p>
<label for="at">Commission type</label><select id="at" name="aff.type"><option value="">(not set)</option><option value="percent" <?= $v['aff.type'] === 'percent' ? 'selected' : '' ?>>percent of deal amount</option><option value="fixed" <?= $v['aff.type'] === 'fixed' ? 'selected' : '' ?>>fixed amount</option></select>
<?= $f('aff.value', 'Commission value', 'Percent (0-100) or currency amount') ?><?= $f('aff.eligible', 'Eligible services / qualifying actions') ?><?= $f('aff.cookie_days', 'Attribution window (days)') ?><?= $f('aff.approval_days', 'Approval period (days)') ?>
<?= $f('aff.min_payout', 'Minimum payout') ?><?= $f('aff.methods', 'Payment methods') ?><?= $f('aff.schedule', 'Payment schedule') ?><?= $f('aff.refund_rule', 'Refund and cancellation rule') ?><?= $f('aff.terms_version', 'Published terms version') ?>
<label for="as">Program status</label><select id="as" name="aff.status"><option value="coming_soon">coming_soon</option><option value="live" <?= $v['aff.status'] === 'live' ? 'selected' : '' ?>>live</option></select>
<?php if ($missing): ?><p class="err">Still missing for launch: <?= Http::e(implode(', ', $missing)) ?></p><?php endif; ?></div>
<button type="submit">Save settings</button></form>
