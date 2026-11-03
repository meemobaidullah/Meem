<?php use App\Core\Http; ?>
<h1>Register as an affiliate</h1>
<?php if (!$live): ?><div class="notice"><b>Coming soon.</b> Registration is closed until the program launches.</div><?php else: ?>
<form method="post" action="/affiliate/register" class="card"><?= Http::csrfField() ?>
<div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
<label for="n">Name</label><input id="n" name="name" type="text" value="<?= Http::e($old['name'] ?? '') ?>"><?php if (isset($errors['name'])): ?><div class="err"><?= Http::e($errors['name']) ?></div><?php endif; ?>
<label for="e">Email</label><input id="e" name="email" type="email" value="<?= Http::e($old['email'] ?? '') ?>"><?php if (isset($errors['email'])): ?><div class="err"><?= Http::e($errors['email']) ?></div><?php endif; ?>
<label for="p">Password (12+ characters)</label><input id="p" name="password" type="password" autocomplete="new-password"><?php if (isset($errors['password'])): ?><div class="err"><?= Http::e($errors['password']) ?></div><?php endif; ?>
<label style="font-weight:400"><input type="checkbox" name="terms" value="1"> I accept the <a href="/affiliate/terms">affiliate terms</a></label><?php if (isset($errors['terms'])): ?><div class="err"><?= Http::e($errors['terms']) ?></div><?php endif; ?>
<p><button type="submit">Apply</button></p></form><?php endif; ?>
