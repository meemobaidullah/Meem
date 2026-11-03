<?php use App\Core\Http; $e = $edit; ?>
<h1>Knowledge base</h1><p>Approved answers the AI assistant may use. If the answer is not here, the assistant must hand off to a human.</p>
<form method="post" action="/admin/kb" class="card"><?= Http::csrfField() ?><input type="hidden" name="id" value="<?= (int) ($e['id'] ?? 0) ?>">
<label for="q">Question</label><input id="q" name="question" type="text" value="<?= Http::e($e['question'] ?? '') ?>"><label for="a">Answer</label><textarea id="a" name="answer" rows="4"><?= Http::e($e['answer'] ?? '') ?></textarea>
<label for="t">Tags</label><input id="t" name="tags" type="text" value="<?= Http::e($e['tags'] ?? '') ?>"><label style="font-weight:400"><input type="checkbox" name="published" value="1" <?= !$e || $e['published'] ? 'checked' : '' ?>> Published</label><p><button type="submit">Save</button></p></form>
<table><tr><th>Question</th><th>Published</th><th></th></tr><?php foreach ($rows as $r): ?><tr><td><?= Http::e($r['question']) ?></td><td><?= $r['published'] ? 'yes' : 'no' ?></td><td><a href="/admin/kb?edit=<?= (int) $r['id'] ?>">Edit</a>
<form method="post" action="/admin/kb/delete" style="display:inline"><?= Http::csrfField() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="ghost" type="submit">Delete</button></form></td></tr><?php endforeach; ?></table>
