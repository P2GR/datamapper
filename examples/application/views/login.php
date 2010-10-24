<div class="login box">
	<div class="boxContent">
		<p>Welcome to the Squash Bug Tracker.  Please log in.</p>
<?php

	echo $user->render_form(array(
			'username',
			'password'
		),
		'login',
		array(
			'save_button' => 'Log In',
			'reset_button' => 'Clear'
		)
	);

?>
	</div>
</div>