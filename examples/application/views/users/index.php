<p>
	<a href="<?= site_url('users/add') ?>"><img src="<?= site_url('img/icon/16/add.png') ?>" alt="Add a New User" width="16" height="16" /> Add a New User</a>
</p>
<table class="users">
	<tr>
		<th class="title">Name</th>
		<th class="email">Email</th>
		<th class="bugs">Open Bugs</th>
		<th class="group">Group</th>
		<th class="buttons">Options</th>
	</tr>
<?		$odd = FALSE;
		foreach($users as $u):
			$odd = !$odd;
		?>
	<tr class="<?= $odd ? 'odd' : 'even' ?>">
		<td class="name"><a href="<?= site_url('users/edit/' . $u->id) ?>" title="Edit this User"><?= htmlspecialchars($u->name) ?></a><?
			if($u->id == $this->login_manager->get_user()->id) {
				echo(' *');
			}
		?></td>
		<td class="email"><a href="mailto:<?= htmlspecialchars($u->email) ?>"><?= htmlspecialchars($u->email) ?></a></td>
		<td class="bugs"><?= $u->bug_count ?></td>
		<td class="group"><?= htmlspecialchars($u->group_name) ?></td>
		<td class="buttons"><a href="<?= site_url('users/edit/' . $u->id) ?>" title="Edit this User"><?= icon('edit', 'Edit this User') ?></a><?
			if($u->id != $this->login_manager->get_user()->id) {
				?> &nbsp; <a href="<?= site_url('users/delete/' . $u->id) ?>" title="Delete this User"><?= icon('delete', 'Delete this User') ?></a><?
			} ?></td>
	</tr>	
<?		endforeach; ?>
</table>
<p>* My Account</p>

<p class="back"><a href="<?= site_url('admin') ?>"><?= icon('back') ?> Back to Admin Console</a></p>