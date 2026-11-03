<?php use App\Core\Http; use App\Controllers\Admin; ?>
<h1>Leads</h1><p><a href="/admin/leads">All</a><?php foreach (Admin::STATUSES as $s): ?> · <a href="/admin/leads?status=<?= urlencode($s) ?>"><?= Http::e($s) ?></a><?php endforeach; ?></p>
<table><tr><th>#</th><th>Name</th><th>Email</th><th>Status</th><th>Created</th></tr>
<?php foreach ($rows as $r): ?><tr><td><a href="/admin/leads/<?= (int) $r['id'] ?>"><?= (int) $r['id'] ?></a></td><td><?= Http::e($r['name']) ?></td><td><?= Http::e($r['email']) ?></td><td><?= Http::e($r['status']) ?></td><td><?= Http::e($r['created_at']) ?></td></tr><?php endforeach; ?></table>
