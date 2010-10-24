<?php

	if(isset($title))
	{
		$page_title = $title . ' - '; 
	}
	else
	{
		$title = '';
		$page_title = '';
	}
	
	if(!isset($section))
	{
		$section = 'welcome';
	}
	
	$sections = array(
		'welcome' => array(
			'name' => 'Welcome',
			'url' => 'welcome'
		),
		'search' => array(
			'name' => 'Find Bugs',
			'url' => 'bugs/search'
		),
		'report' => array(
			'name' => 'Report a Bug',
			'url' => 'bugs/report'
		),
		'admin' => array(
			'name' => 'Admin',
			'url' => 'admin',
			'restrict' => 1
		),
		'logout' => array(
			'name' => 'Log Out',
			'url' => 'logout'
		)
	);
	
	$user = isset($this->login_manager) ? $this->login_manager->get_user() : FALSE;
	
	if( ! isset($message))
	{
		$message = $this->session->flashdata('message');
	}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?= $page_title ?>Squash Bug Tracker</title>
	<link type="text/css" rel="stylesheet" href="<?= str_replace('index.php/', '', site_url('css/style.css')) ?>" />
</head>
<body>
<!--
Squash Bug Tracker is licensed under the Creative Commons Attribution-Share Alike 3.0 United States License
More Info: http://creativecommons.org/licenses/by-sa/3.0/us/
-->
<!-- Header -->
<div class="header">
	<h1 title="Squash Bug Tracker">Squash Bug Tracker</h1>
<? if(!isset($hide_nav) || !$hide_nav): ?>
	<div class="nav">
		<ul>
<?			foreach($sections as $key => $s):
				if($user !== FALSE)
				{
					if(isset($s['restrict']))
					{
						if($user->group->id > $s['restrict'])
						{
							continue;
						}
					}
				}
				$sel = ($section == $key) ? ' selected' : ''; ?>
			<li class="<?= $key . $sel ?>"><a href="<?= site_url($s['url']) ?>"><?= $s['name'] ?></a></li>
<?			endforeach; ?>

		</ul>
	</div>
<?	if($user !== FALSE): ?>
	<div class="username">Welcome, <?= htmlspecialchars($user->name) ?></div>
<?	endif; ?>
<? endif; ?>
</div>
<!-- End Header -->

<? if( ! empty($title)): ?>
<!-- Page Title -->
<h2><?= $title ?></h2>
<? endif; ?>

<!-- Page Content -->
<div class="content">

<? if( ! empty($message)): ?>
<!-- Form Result Message --> 
<div id="page_message"><?= htmlspecialchars($message) ?></div>
<? endif; ?>