<?php use App\Core\Http; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Admin login</title><link rel="stylesheet" href="/app.css"></head><body><main class="wrap" style="max-width:420px">
<h1>Admin login</h1><?php if ($error): ?><div class="flash err"><?= Http::e($error) ?></div><?php endif; ?>
<form method="post" action="/admin/login" class="card"><?= Http::csrfField() ?><label for="e">Email</label><input id="e" name="email" type="email" autocomplete="username"><label for="p">Password</label><input id="p" name="password" type="password" autocomplete="current-password"><p><button type="submit">Log in</button></p></form></main></body></html>
