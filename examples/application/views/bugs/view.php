<div class="bugview columns smallRight"><div class="colset">
	<div class="bugDiscussion leftcol">
		<div><b>Status:</b> <?php echo htmlspecialchars($bug->status->name); ?> <a href="<?php echo site_url('bugs/edit/' . $bug->id); ?>" title="Edit this Bug"><img src="<?php echo site_url('img/icon/16/edit.png'); ?>" width="16" height="16" alt="Edit this Bug" /> Edit this Bug</a></div>
		<div class="Description"><?php echo auto_typography($bug->description); ?></div>
	</div>

	<div class="bugInfo box rightcol">
		<h3>Assigned Users</h3>
		<ul>
		<?php if($bug->users->result_count() == 0): ?>
			<li><em>No users assigned to this bug.</em></li>
		<?php else: ?>
			<?php foreach($bug->users as $user): ?>
			<li><?php echo $user->name; ?></li>
			<?php endforeach; ?>
		<?php endif; ?>
		</ul>
		<?php if($bug->categories->result_count() > 0): ?>
		<h3>Categories:</h3>
		<ul>
			<?php foreach($bug->categories as $cat): ?>
			<li><?php echo $cat->name; ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
		<h3>Other</h3>
		<ul>
			<li><b>Priority:</b> <?php echo $bug->get_priority(); ?></li>
			<li><b>Reported By:</b> <?php echo htmlspecialchars($bug->creator->name); ?></li>
			<li><b>Date Created:</b> <?php echo strftime('%A, %B %e, %G at %l:%M %P', strtotime($bug->created)); ?></li>
			<li><b>Last Edited By:</b> <?php echo htmlspecialchars($bug->editor->name); ?></li>
			<li><b>Last Updated:</b> <?php echo strftime('%A, %B %e, %G at %l:%M %P', strtotime($bug->updated)); ?></li>
		</ul>
	</div>
</div></div>
<span class="clear"></span>
