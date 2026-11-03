<?php use App\Core\Http; $r = $rules; $nv = '<em>not set yet</em>';
$v = fn($k) => isset($r[$k]) && $r[$k] !== null && $r[$k] !== '' ? Http::e($r[$k]) : $nv;
$earn = ($r['aff.type'] ?? null) === 'percent' ? $v('aff.value') . '% of the client’s deal amount' : (($r['aff.type'] ?? null) === 'fixed' ? $v('aff.value') . ' per qualifying referral' : $nv); ?>
<ol>
<li><b>How much you earn:</b> <?= $earn ?></li>
<li><b>Qualifying actions:</b> <?= $v('aff.eligible') ?></li>
<li><b>Attribution:</b> your referral link sets a cookie for <?= $v('aff.cookie_days') ?> days</li>
<li><b>Approval:</b> commissions stay pending for <?= $v('aff.approval_days') ?> days, then an admin approves or rejects them</li>
<li><b>Payouts:</b> minimum <?= $v('aff.min_payout') ?>; methods: <?= $v('aff.methods') ?>; schedule: <?= $v('aff.schedule') ?></li>
<li><b>Cancellations and refunds:</b> <?= $v('aff.refund_rule') ?></li>
</ol>
