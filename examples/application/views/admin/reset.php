<?php if(isset($first_time)): ?>

<p>The database has not yet been created.  Please click on the link below if you want to have the database automatically created.</p>

<?php endif; ?>
<p class="database_setup">
	<a href="<?php echo site_url('admin/reset'); ?>">Click here to automatically <?php echo isset($first_time) ? 'create' : 'reset'; ?> the database.</a>
	<br/>
	This will be created on the <strong><?php echo $this->db->dbdriver; ?></strong> database <strong>&ldquo;<?php echo $this->db->database; ?>&rdquo;</strong>.
	<br/>
	Procede with caution: <strong>Any existing Squash Bug Tracker tables in this database <em>will be erased!</em></strong>
</p>
