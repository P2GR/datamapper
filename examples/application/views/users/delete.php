<p>Are you sure you want to delete the user <strong><?php echo htmlspecialchars($user->name); ?></strong>?</p>
<form action="<?php echo current_url(); ?>" method="post">
	<p>
		<input type="submit" name="deleteok" value="Yes, Delete the User" />
		<input type="submit" name="cancel" value="Cancel" />
	</p>
</form>
