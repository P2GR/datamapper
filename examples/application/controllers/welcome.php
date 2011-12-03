<?php

class Welcome extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->library('login_manager');
	}
	
	function index()
	{
		$user = $this->login_manager->get_user();
		// get open bugs, order with most recently updated at the top 
		$bugs = $user->bugs;
		$bugs->where_related_status('closed', FALSE);
		$bugs->include_related('status', 'name', TRUE, TRUE);
		$bugs = $bugs->order_by('updated', 'DESC')->order_by_related_status('sortorder')->limit(25)->get_iterated();
		
		$this->output->enable_profiler(TRUE);
		$this->load->view('template_header', array('title' => 'Welcome', 'section' => 'welcome'));
		$this->load->view('welcome/index', array('bugs' => $bugs));
		$this->load->view('template_footer');
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */