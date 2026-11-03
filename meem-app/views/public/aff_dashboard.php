<?php use App\Core\Http; ?>
<h1>Your affiliate dashboard</h1>
<?php if ($a['status'] !== 'approved'): ?><div class="notice">Your application is <b><?= Http::e($a['status']) ?></b>. A referral link is created after admin approval.</div><?php else: ?>
<div class="card"><b>Your referral link:</b> <code><?= Http::e($link) ?></code></div><?php endif; ?>
<div class="grid"><div class="stat">Clicks<b><?= $clicks ?></b></div><div class="stat">Referrals<b><?= $referrals ?></b></div><div class="stat">Conversions<b><?= $conversions ?></b></div>
<div class="stat">Pending<b><?= Http::e($pending) ?></b></div><div class="stat">Approved<b><?= Http::e($approved) ?></b></div><div class="stat">Rejected<b><?= Http::e($rejected) ?></b></div><div class="stat">Paid<b><?= Http::e($paid) ?></b></div></div>
<?php if ($a['status'] === 'approved'): ?><form method="post" action="/affiliate/payout" class="card"><?= Http::csrfField() ?><label for="m">Payout method</label><input id="m" name="method" type="text"><p><button type="submit">Request payout</button> <small>Minimum: <?= $min ? Http::e($min) : 'not set' ?></small></p></form><?php endif; ?>
<h2>Payment history</h2><table><tr><th>Date</th><th>Amount</th><th>Status</th></tr><?php foreach ($payouts as $p): ?><tr><td><?= Http::e($p['created_at']) ?></td><td><?= number_format($p['amount_cents'] / 100, 2) ?></td><td><?= Http::e($p['status']) ?></td></tr><?php endforeach; ?></table>
<form method="post" action="/affiliate/logout"><?= Http::csrfField() ?><button class="ghost" type="submit">Log out</button></form>
