<?php use App\Core\{Http, Auth}; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Admin</title><link rel="stylesheet" href="/app.css"></head><body>
<nav class="nav"><a href="/admin">Dashboard</a>
<?php foreach ([['leads','/admin/leads','Leads'],['kb','/admin/kb','Knowledge base'],['affiliates.view','/admin/affiliates','Affiliates'],['settings','/admin/settings','Settings'],['users','/admin/users','Users'],['audit','/admin/audit','Audit log']] as [$p,$h,$l]) if (Auth::can($u, $p)) echo '<a href="' . $h . '">' . $l . '</a>'; ?>
<span style="margin-left:auto"><?= Http::e($u['name']) ?> (<?= Http::e($u['role']) ?>)</span>
<form method="post" action="/admin/logout" style="margin:0"><?= Http::csrfField() ?><button class="ghost" type="submit">Log out</button></form></nav>
<main class="wrap"><?php foreach (Http::flashes() as [$t, $m]): ?><div class="flash <?= Http::e($t) ?>"><?= Http::e($m) ?></div><?php endforeach; ?><?= $content ?></main></body></html>
