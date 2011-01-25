<div class="welcome columns smallRight"><div class="colset">

	<div class="welcomeMessage leftcol">
		<p>Welcome to the Squash Bug Tracker.</p>
	</div>

	<div class="welcomeList box rightcol">
		<h3>My Open Bugs (Most Recently Updated First)</h3>
		<div class="boxContent">
			<?php
				$this->load->view('bugs/list', array('bugs' => $bugs));
			?>
		</div>
	</div>

</div></div>
<span class="clear"></span>
