<table class="bugs">
	<tr>
		<th class="id">ID</th>
		<th class="title">Title</th>
		<th class="status">Status</th>
		<th class="buttons">Options</th>
	</tr>
<?	if($bugs->result_count() < 1): ?>
	<tr>
		<td colspan="4">No Bugs Found.</td>
	</tr>
<?	else: ?>
<?		$odd = FALSE;
		foreach($bugs as $b):
			$odd = !$odd;
		?>
	<tr class="<?= $odd ? 'odd' : 'even' ?>">
		<td class="id"><?= $b->id ?></td>
		<td class="title"><a href="<?= site_url('bugs/view/' . $b->id) ?>" title="View this Bug"><?= htmlspecialchars($b->title) ?></a></td>
		<td class="status"><?= htmlspecialchars($b->status->name) ?></td>
		<td class="buttons">
			<a href="<?= site_url('bugs/view/' . $b->id) ?>" title="View this Bug"><?= icon('view', 'View this Bug') ?></a>
			&nbsp;
			<a href="<?= site_url('bugs/edit/' . $b->id) ?>" title="Edit this Bug"><?= icon('edit', 'Edit this Bug') ?></a>
		</td>
	</tr>	
<?		endforeach; ?>
<?	endif; ?>
</table>
