<?php use App\Core\Http; ?>
<h1>Affiliate terms</h1>
<?php if (!$live): ?><div class="notice">Draft. These terms are not in force because the program has not launched.</div><?php endif; ?>
<?= Http::view('public/_rules', ['rules' => $rules]) ?>
<p>Terms version: <?= isset($rules['aff.terms_version']) && $rules['aff.terms_version'] ? Http::e($rules['aff.terms_version']) : '<em>not set yet</em>' ?>. Have these terms reviewed for your jurisdiction before launch.</p>
