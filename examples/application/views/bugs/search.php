<div class="searchSection box">
<h3>Search</h3>
<?php

	echo $bug->render_form($form_fields, $url, array('save_button' => 'Search', 'reset_button' => TRUE));

?>
</div>

<div class="searchResults">
<?php

if( ! empty($bugs))
{

	$paging = $this->load->view('bugs/paging', array('bugs' => $bugs), TRUE);

	echo($paging);

	$this->load->view('bugs/list', array('bugs' => $bugs));

	echo($paging);
}
?>

</div>

<span class="clear"></span>
