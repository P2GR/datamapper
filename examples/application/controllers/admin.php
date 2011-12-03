<?php
class Admin extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->library('login_manager', array('autologin' => FALSE));
	}

	function index()
	{
		$this->login_manager->check_login(1);
		$this->load->view('template_header', array('title' => 'Admin Console', 'section' => 'admin'));
		$this->load->view('admin/index');
		$this->load->view('template_footer');
	}

	function reset_warning()
	{
		if( ! $this->session->userdata('first_time') &&
				$this->db->table_exists('users') && $this->login_manager->get_user() !== FALSE)
		{
			show_error('The database is already configured');
		}
		$this->load->view('template_header', array('title' => 'First Time Setup', 'section' => 'admin', 'hide_nav' => TRUE));
		$this->load->view('admin/reset', array('first_time' => TRUE));
		$this->load->view('template_footer');
	}

	/**
	 * Resets the entire Database
	 */
	function reset()
	{
		$this->load->dbforge();
		try {
			// force disabling of g-zip so output can be streamed
			apache_setenv('no-gzip', '1');
		} catch(Exception $e) { /* ignore */ }

		$success = TRUE;

		$first_time = $this->session->userdata('first_time') ||
				( ! $this->db->table_exists('users') && $this->login_manager->get_user() === FALSE);

		if( ! $first_time)
		{
			$this->login_manager->check_login(1);
		}

		$this->session->set_userdata('first_time', TRUE);

		echo $this->load->view('template_header', array('title' => 'Resetting Database', 'section' => 'admin', 'hide_nav' => $first_time), TRUE);
		?><div class="database_setup"><?php
		$this->_message('Creating the Squash database at <strong>' . $this->db->database . '</strong><br/>', '');
		$success = $success && $this->_drop_tables();
		echo("<br/><br/>");
		$success = $success && $this->_create_tables();
		echo("<br/><br/>");
		$success = $success && $this->_init_data();

		?></div><?php
		if($success) {
			?><p><a href="<?= site_url('admin/init') ?>">Continue</a></p><?php
		} else {
			?>An error occurred.  Please reset the database and try again.<?php
		}

		$this->load->view('template_footer');
	}

	function _drop_tables() {
		$list = file(APPPATH . 'sql/tabledroplist.txt');
		foreach($list as $table) {
			$table = trim($table);
			if(empty($table) || $table[0] == '#') {
				continue;
			}
			if($this->db->table_exists($table)) {
				$this->_message("Dropping table $table...");
				if($this->dbforge->drop_table($table)) {
					echo("done.");
				} else {
					echo("ERROR.");
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	function _create_tables() {
		$this->load->helper('file');
		$path = APPPATH . 'sql/' . $this->db->dbdriver;
		if( ! file_exists($path)) {
			show_error("ERROR: Unable to automatically create tables for " . $this->db->dbdriver . ' databases.');
		}
		$tables = get_filenames($path);
		foreach($tables as $table) {
			$n = str_ireplace('.sql', '', $table);
			$this->_message("Creating table $n...");
			$sql = file_get_contents($path . '/' . $table);
			if($this->db->query($sql)) {
				echo("done.");
			} else {
				echo("ERROR.");
				return FALSE;
			}
		}
		return TRUE;
	}

	function _init_data() {
		$this->load->helper('file');
		$success = TRUE;
		$path = APPPATH . 'sql/data';
		$files = get_filenames($path);
		foreach($files as $file) {
			if( ! strpos($file, '.csv'))
			{
				continue;
			}
			$class = str_ireplace('.csv', '', $file);
			$this->_message("Importing data for $class ");
			$object = new $class();
			$object->load_extension('csv');
			$num = $object->csv_import($path . '/' . $file, '', TRUE, array($this, '_save_object'));
			$n = ($num == 1) ? $class : plural($class);
			echo(" $num $n  were imported.");
		}

		return $success;
	}

	function _save_object($obj) {
		if(!$obj->save())
		{
			$this->_message('Errors: <ul><li>' . implode('</li><li>', $r->error->all) . '</li></ul>', '');
			return FALSE;
		}
		$this->_message('.', '');
		return TRUE;
	}

	function _message($msg, $lb = '<br/>') {
		echo($lb . $msg);
		ob_flush();
		flush();
	}

	/**
	 * Allows the creation of an Administrator
	 *
	 */
	function init($save = FALSE) {
		$first_time = $this->session->userdata('first_time');
		if( ! $first_time) {
			show_error('This page can only be accessed the first time.');
		}
		$user = new User();

		if($save)
		{
			$user->trans_start();
			$user->from_array($_POST, array('name', 'email', 'username', 'password', 'confirm_password'));
			$group = new Group();
			$group->get_by_id(1);
			if($user->save($group)) {
				$user->password = $this->input->post('password');
				if(!$this->login_manager->process_login($user)) {
					show_error('Errors: <ul><li>' . implode('</li><li>', $user->error->all) . '</li></ul><pre>' . var_export($user->error, TRUE) . '</pre>');
				}
				$this->session->unset_userdata('first_time');
				$user->trans_complete();
				redirect('welcome');
			}
		}

		$user->load_extension('htmlform');

		// ID is not included because it is not necessary
		$form_fields = array(
			'Contact Information' => 'section',
			'name' => array(
				'label' => 'Your Name'
			),
			'email',
			'Login Information' => 'section',
			'username',
			'password',
			'confirm_password'
		);

		$this->load->view('template_header', array('title' => 'Set Up Your Account', 'section' => 'admin'));
		$this->load->view('admin/init', array('user' => $user, 'form_fields' => $form_fields));
		$this->load->view('template_footer');
	}

}
