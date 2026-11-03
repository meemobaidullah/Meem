<?php use App\Core\Http; ?>
<h1>Affiliate login</h1>
<?php if (!$live): ?><div class="notice"><b>Coming soon.</b> Login opens when the program launches.</div><?php else: ?>
<form method="post" action="/affiliate/login" class="card"><?= Http::csrfField() ?>
<?php if ($error): ?><div class="err"><?= Http::e($error) ?></div><?php endif; ?>
<label for="e">Email</label><input id="e" name="email" type="email"><label for="p">Password</label><input id="p" name="password" type="password" autocomplete="current-password"><p><button type="submit">Log in</button></p></form><?php endif; ?>
