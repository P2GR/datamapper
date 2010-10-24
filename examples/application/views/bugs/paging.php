<?
	$base_url = current_url();

	// remove old page
	$base_url = preg_replace('#/page:\d+#i', '', $base_url);

	$paged = $bugs->paged;

?><div class="paging">
	<? if($paged->has_previous):
		?><a href="<?= $base_url ?>/page:1" class="first">&lt;&lt; First</a>&nbsp;<?
		?>&nbsp;<a href="<?= $base_url ?>/page:<?= $paged->previous_page ?>" class="prev">&lt;&nbsp;Previous</a><?
	   else:
		?><span class="disabled">&lt;&lt;&nbsp;First&nbsp;&nbsp;&lt;&nbsp;Previous</span><?
	   endif;
	   
	   ?>&nbsp;&nbsp;&middot;&nbsp;&nbsp;Found&nbsp;<?= $paged->total_rows ?>&nbsp;Total&nbsp;Bug<?= $paged->total_rows != 1 ? 's' : ''?>&nbsp;&nbsp;&middot;&nbsp;&nbsp;<?

	   if($paged->has_next):
		?><a href="<?= $base_url ?>/page:<?= $paged->next_page ?>" class="next">Next&nbsp;&gt;</a>&nbsp;<?
		?>&nbsp;<a href="<?= $base_url ?>/page:<?= $paged->total_pages ?>" class="last">Last&nbsp;&gt;&gt;</a><?
	   else:
		?><span class="disabled">Next&nbsp;&gt;&nbsp;&nbsp;Last&nbsp;&gt;&gt;</span><?
	   endif;
	?>
</div>