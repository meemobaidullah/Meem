<?php use App\Core\Http; $o = fn($k) => Http::e($old[$k] ?? ''); $sel = (array) ($old['markets'] ?? []); ?>
<h1>Book a consultation</h1>
<p>Tell me which marketplaces you want to be on. <?php if ($wa): ?><a href="<?= Http::e($wa) ?>" rel="noopener">Message on WhatsApp</a><?php endif; ?></p>
<form method="post" action="/contact" novalidate class="card">
<?= Http::csrfField() ?><input type="hidden" name="_t" value="<?= Http::e($tok) ?>">
<div class="hp" aria-hidden="true"><label>Leave empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
<label for="name">Full name</label><input id="name" name="name" type="text" value="<?= $o('name') ?>" required><?php if (isset($errors['name'])): ?><div class="err"><?= Http::e($errors['name']) ?></div><?php endif; ?>
<label for="email">Email</label><input id="email" name="email" type="email" value="<?= $o('email') ?>" required><?php if (isset($errors['email'])): ?><div class="err"><?= Http::e($errors['email']) ?></div><?php endif; ?>
<label for="wa">WhatsApp number (optional)</label><input id="wa" name="whatsapp" type="tel" value="<?= $o('whatsapp') ?>"><?php if (isset($errors['whatsapp'])): ?><div class="err"><?= Http::e($errors['whatsapp']) ?></div><?php endif; ?>
<fieldset style="border:0;padding:0;margin:14px 0"><legend style="font-weight:600;font-size:14px">Marketplaces</legend>
<?php foreach ($markets as $m): ?><label style="display:inline-flex;gap:6px;margin-right:16px;font-weight:400"><input type="checkbox" name="markets[]" value="<?= Http::e($m) ?>" <?= in_array($m, $sel, true) ? 'checked' : '' ?>> <?= Http::e($m) ?></label><?php endforeach; ?></fieldset>
<label for="message">Your goals</label><textarea id="message" name="message" rows="4"><?= $o('message') ?></textarea><?php if (isset($errors['message'])): ?><div class="err"><?= Http::e($errors['message']) ?></div><?php endif; ?>
<label style="font-weight:400"><input type="checkbox" name="consent" value="1"> I agree that Meem may contact me about this request and store my details for that purpose. [Privacy policy link to be added]</label><?php if (isset($errors['consent'])): ?><div class="err"><?= Http::e($errors['consent']) ?></div><?php endif; ?>
<p><button type="submit">Request my consultation</button></p></form>
