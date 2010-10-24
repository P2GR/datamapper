<div class="bugview columns smallRight"><div class="colset">
	<div class="bugDiscussion leftcol">
		<div><b>Status:</b> <?= htmlspecialchars($bug->status->name) ?> <a href="<?= site_url('bugs/edit/' . $bug->id)?>" title="Edit this Bug"><img src="<?= site_url('img/icon/16/edit.png') ?>" width="16" height="16" alt="Edit this Bug" /> Edit this Bug</a></div>
		<div class="Description"><?= auto_typography($bug->description) ?></div>
	</div>
	
	<div class="bugInfo box rightcol">
		<h3>Assigned Users</h3>
		<ul>
		<? if($bug->users->result_count() == 0): ?>
			<li><em>No users assigned to this bug.</em></li>
		<? else: ?>
			<? foreach($bug->users as $user): ?>
			<li><?= $user->name ?></li>
			<? endforeach; ?>
		<? endif; ?>
		</ul>
		<? if($bug->categories->result_count() > 0): ?>
		<h3>Categories:</h3>
		<ul>
			<? foreach($bug->categories as $cat): ?>
			<li><?= $cat->name ?></li>
			<? endforeach; ?>
		</ul>
		<? endif; ?>
		<h3>Other</h3>
		<ul>
			<li><b>Priority:</b> <?= $bug->get_priority() ?></li>
			<li><b>Reported By:</b> <?= htmlspecialchars($bug->creator->name) ?></li>
			<li><b>Date Created:</b> <?= strftime('%A, %B %e, %G at %l:%M %P', strtotime($bug->created)) ?></li>
			<li><b>Last Edited By:</b> <?= htmlspecialchars($bug->editor->name) ?></li>
			<li><b>Last Updated:</b> <?= strftime('%A, %B %e, %G at %l:%M %P', strtotime($bug->updated)) ?></li>
		</ul>
	</div>
</div></div>
<span class="clear"></span>
