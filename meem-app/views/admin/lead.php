<?php use App\Core\Http; use App\Controllers\Admin; ?>
<h1>Lead #<?= (int) $lead['id'] ?></h1>
<div class="card"><b><?= Http::e($lead['name']) ?></b> · <?= Http::e($lead['email']) ?> · <?= Http::e($lead['whatsapp'] ?? '') ?><br>Marketplaces: <?= Http::e($lead['marketplaces'] ?? '-') ?><br>Consent given: <?= $lead['consent'] ? 'yes, ' . Http::e($lead['consent_at']) : 'no' ?><br>Referral code: <?= Http::e($lead['ref_code'] ?? '-') ?><p><?= nl2br(Http::e($lead['message'] ?? '')) ?></p></div>
<form method="post" action="/admin/leads/<?= (int) $lead['id'] ?>" class="card"><?= Http::csrfField() ?>
<label for="s">Status</label><select id="s" name="status"><?php foreach (Admin::STATUSES as $s): ?><option <?= $s === $lead['status'] ? 'selected' : '' ?>><?= Http::e($s) ?></option><?php endforeach; ?></select>
<label for="d">Deal amount (only when Converted; used for affiliate commission)</label><input id="d" name="deal_amount" type="number" step="0.01" min="0" value="<?= $lead['deal_amount_cents'] !== null ? Http::e((string) ($lead['deal_amount_cents'] / 100)) : '' ?>">
<label for="n">Add a note</label><textarea id="n" name="note" rows="3"></textarea><p><button type="submit">Save</button></p></form>
<h2>Notes</h2><?php foreach ($notes as $n): ?><div class="card"><small><?= Http::e($n['who'] ?? '') ?> · <?= Http::e($n['created_at']) ?></small><br><?= nl2br(Http::e($n['note'])) ?></div><?php endforeach; ?>
