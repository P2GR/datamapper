<?php

/**
 * User Class
 *
 * Transforms users table into an object.
 * This is just here for use with the example in the Controllers.
 *
 * @license		MIT License
 * @category	Models
 * @author		Phil DeJarnett
 * @link		http://www.overzealous.com/dmz/
 */
class User extends DataMapper {

	// --------------------------------------------------------------------
	// Relationships
	// --------------------------------------------------------------------
	
	public $has_one = array('group');
	
	public $has_many = array(
		// bugs created by this user
		'created_bug' => array(
			'class' => 'bug',
			'other_field' => 'creator'
		),
		// bugs edited by this user
		'edited_bug' => array(
			'class' => 'bug',
			'other_field' => 'editor'
		),
		// bugs assigned to this user
		'bug'
	);
	
	// --------------------------------------------------------------------
	// Validation
	// --------------------------------------------------------------------

	public $validation = array(
		'name' => array(
			'rules' => array('required', 'trim', 'unique', 'max_length' => 100)
		),
		'email' => array(
			'rules' => array('required', 'trim', 'unique', 'valid_email')
		),
		'username' => array(
			'rules' => array('required', 'trim', 'unique', 'alpha_dash', 'min_length' => 3, 'max_length' => 20)
		),
		'password' => array(
			'rules' => array('required', 'trim', 'min_length' => 3, 'max_length' => 40, 'encrypt'),
			'type' => 'password'
		),
		'confirm_password' => array(
			'rules' => array('required', 'encrypt', 'matches' => 'password', 'min_length' => 3, 'max_length' => 40),
			'type' => 'password'
		),
		'group' => array(
			'rules' => array('required')
		)
	);
	
	// Default to ordering by name
	public $default_order_by = array('name');
	
	// --------------------------------------------------------------------
	
	function __toString()
	{
		return empty($this->name) ? $this->localize_label('newuser') : $this->name;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Returns an array list of all users that can have bugs assigned
	 * to them.
	 * 
	 * @return $this for chaining
	 */
	function get_assignable()
	{
		return $this->where_in_related_group('id', array(1, 2))->get();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Login
	 *
	 * Authenticates a user for logging in.
	 *
	 * @access	public
	 * @return	bool
	 */
	function login()
	{
		// backup username for invalid logins
		$uname = $this->username;
		
		// Create a temporary user object
		$u = new User();

		// Get this users stored record via their username
		$u->where('username', $uname)->get();

		// Give this user their stored salt
		$this->salt = $u->salt;

		// Validate and get this user by their property values,
		// this will see the 'encrypt' validation run, encrypting the password with the salt
		$this->validate()->get();

		// If the username and encrypted password matched a record in the database,
		// this user object would be fully populated, complete with their ID.

		// If there was no matching record, this user would be completely cleared so their id would be empty.
		if ($this->exists())
		{
			// Login succeeded
			return TRUE;
		}
		else
		{
			// Login failed, so set a custom error message
			$this->error_message('login', $this->localize_label('error_login'));

			// restore username for login field
			$this->username = $uname;

			return FALSE;
		}
	}
	 
	// --------------------------------------------------------------------
	
	/**
	 * Encrypt (prep)
	 *
	 * Encrypts this objects password with a random salt.
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function _encrypt($field)
	{
		if (!empty($this->{$field}))
		{
			if (empty($this->salt))
			{
				$this->salt = md5(uniqid(rand(), true));
			}

			$this->{$field} = sha1($this->salt . $this->{$field});
		}
	}
}

/* End of file user.php */
/* Location: ./application/models/user.php */