<?php

class Login extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->library('login_manager', array('autologin' => FALSE));
	}
	
	function index()
	{
		$user = $this->login_manager->get_user();
		if($user !== FALSE)
		{
			// already logged in, redirect to welcome page
			redirect('welcome');
		}
		// Create a user to store the login validation
		$user = new User();
		if($this->input->post('username') !== FALSE)
		{
			// A login was attempted, load the user data
			$user->from_array($_POST, array('username', 'password'));
			// get the result of the login request
			$login_redirect = $this->login_manager->process_login($user);
			if($login_redirect)
			{
				if($login_redirect === TRUE)
				{
					// if the result was simply TRUE, redirect to the welcome page.
					redirect('welcome');
				}
				else
				{
					// otherwise, redirect to the stored page that was last accessed. 
					redirect($login_redirect);
				}
			} 
		}
		
		$user->load_extension('htmlform');
		
		$this->output->enable_profiler(TRUE);
		$this->load->view('template_header', array('title' => 'Login', 'hide_nav' => TRUE));
		$this->load->view('login', array('user' => $user));
		$this->load->view('template_footer');
	}
}

/* End of file login.php */
/* Location: ./system/application/controllers/login.php */