<p>
	<a href="<?php echo site_url('users/add'); ?>"><img src="<?php echo site_url('img/icon/16/add.png'); ?>" alt="Add a New User" width="16" height="16" /> Add a New User</a>
</p>
<table class="users">
	<tr>
		<th class="title">Name</th>
		<th class="email">Email</th>
		<th class="bugs">Open Bugs</th>
		<th class="group">Group</th>
		<th class="buttons">Options</th>
	</tr>
<?php		$odd = FALSE;
		foreach($users as $u):
			$odd = !$odd;
		?>
	<tr class="<?php echo $odd ? 'odd' : 'even'; ?>">
		<td class="name"><a href="<?php echo site_url('users/edit/' . $u->id); ?>" title="Edit this User"><?php echo htmlspecialchars($u->name); ?></a><?php
			if($u->id == $this->login_manager->get_user()->id) {
				echo(' *');
			}
		?></td>
		<td class="email"><a href="mailto:<?php echo htmlspecialchars($u->email); ?>"><?php echo htmlspecialchars($u->email); ?></a></td>
		<td class="bugs"><?php echo $u->bug_count; ?></td>
		<td class="group"><?php echo htmlspecialchars($u->group_name); ?></td>
		<td class="buttons"><a href="<?php echo site_url('users/edit/' . $u->id); ?>" title="Edit this User"><?php echo icon('edit', 'Edit this User'); ?></a><?php
			if($u->id != $this->login_manager->get_user()->id) {
				?> &nbsp; <a href="<?php echo site_url('users/delete/' . $u->id); ?>" title="Delete this User"><?php echo icon('delete', 'Delete this User'); ?></a><?php
			} ?></td>
	</tr>
<?php		endforeach; ?>
</table>
<p>* My Account</p>

<p class="back"><a href="<?php echo site_url('admin'); ?>"><?php echo icon('back'); ?> Back to Admin Console</a></p>
