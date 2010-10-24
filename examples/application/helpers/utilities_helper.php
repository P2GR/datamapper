<?php



if( ! function_exists('icon'))
{
	function icon($name, $alt = 'icon', $size = 16)
	{
		$alt = htmlspecialchars($alt);
		return '<img src="' . site_url("img/icon/$size/$name.png") . "\" width=\"$size\" height=\"$size\" alt=\"$alt\" />";
	} 
}