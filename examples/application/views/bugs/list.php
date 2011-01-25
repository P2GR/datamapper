<table class="bugs">
	<tr>
		<th class="id">ID</th>
		<th class="title">Title</th>
		<th class="status">Status</th>
		<th class="buttons">Options</th>
	</tr>
<?php	if($bugs->result_count() < 1): ?>
	<tr>
		<td colspan="4">No Bugs Found.</td>
	</tr>
<?php	else: ?>
<?php		$odd = FALSE;
		foreach($bugs as $b):
			$odd = !$odd;
		?>
	<tr class="<?php echo $odd ? 'odd' : 'even'; ?>">
		<td class="id"><?php echo $b->id; ?></td>
		<td class="title"><a href="<?php echo site_url('bugs/view/' . $b->id); ?>" title="View this Bug"><?php echo htmlspecialchars($b->title); ?></a></td>
		<td class="status"><?php echo htmlspecialchars($b->status->name); ?></td>
		<td class="buttons">
			<a href="<?php echo site_url('bugs/view/' . $b->id); ?>" title="View this Bug"><?php echo icon('view', 'View this Bug'); ?></a>
			&nbsp;
			<a href="<?php echo site_url('bugs/edit/' . $b->id); ?>" title="Edit this Bug"><?php echo icon('edit', 'Edit this Bug'); ?></a>
		</td>
	</tr>
<?php	endforeach; ?>
<?php	endif; ?>
</table>
