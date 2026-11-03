<?php use App\Core\Http; ?>
<h1>Affiliate program</h1>
<?php if (!$live): ?><div class="notice"><b>Coming soon.</b> The program has not launched. Registration and commission claims are closed, and no reward is promised.</div>
<?php else: ?><p><a class="btn" href="/affiliate/register">Register</a> <a class="btn ghost" href="/affiliate/login">Login</a></p><?php endif; ?>
<h2>Program rules</h2><div class="card"><?= Http::view('public/_rules', ['rules' => $rules]) ?></div>
<p><a href="/affiliate/terms">Affiliate terms</a></p>
