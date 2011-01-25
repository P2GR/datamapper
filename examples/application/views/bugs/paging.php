<?php
	$base_url = current_url();

	// remove old page
	$base_url = preg_replace('#/page:\d+#i', '', $base_url);

	$paged = $bugs->paged;

?><div class="paging">
	<?php if($paged->has_previous):
		?><a href="<?php echo $base_url; ?>/page:1" class="first">&lt;&lt; First</a>&nbsp;<?php
		?>&nbsp;<a href="<?php echo $base_url; ?>/page:<?php echo $paged->previous_page; ?>" class="prev">&lt;&nbsp;Previous</a><?php
	   else:
		?><span class="disabled">&lt;&lt;&nbsp;First&nbsp;&nbsp;&lt;&nbsp;Previous</span><?php
	   endif;

	   ?>&nbsp;&nbsp;&middot;&nbsp;&nbsp;Found&nbsp;<?php echo $paged->total_rows; ?>&nbsp;Total&nbsp;Bug<?php echo $paged->total_rows != 1 ? 's' : ''; ?>&nbsp;&nbsp;&middot;&nbsp;&nbsp;<?php

	   if($paged->has_next):
		?><a href="<?php echo $base_url; ?>/page:<?php echo $paged->next_page; ?>" class="next">Next&nbsp;&gt;</a>&nbsp;<?php
		?>&nbsp;<a href="<?php echo $base_url; ?>/page:<?php echo $paged->total_pages; ?>" class="last">Last&nbsp;&gt;&gt;</a><?php
	   else:
		?><span class="disabled">Next&nbsp;&gt;&nbsp;&nbsp;Last&nbsp;&gt;&gt;</span><?php
	   endif;
	?>
</div>
