<?php

/**
 * Group Class
 * Allows users to belong to one or more groups.
 *
 * @license 	MIT License
 * @category	Models
 * @author  	Phil DeJarnett
 * @link    	http://www.overzealous.com/dmz/
 */
class Group extends DataMapper {

	// --------------------------------------------------------------------
	// Relationships
	// --------------------------------------------------------------------

	public $has_many = array("user");
	
	// --------------------------------------------------------------------
	// Validation
	// --------------------------------------------------------------------	
	
	public $validation = array(
		'name' => array(
			'rules' => array('required', 'trim', 'unique', 'min_length' => 3, 'max_length' => 20)
		)
	);
	
	// Default to ordering by name
	public $default_order_by = array('id' => 'desc');
	
	/**
	 * Returns the name of this status.
	 * @return $this->name
	 */
	function __toString()
	{
		return empty($this->name) ? $this->localize_label('unset') : $this->name;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * This method is provided for the htmlform extension.
	 * It is used to prevent logged-in users from being able to accidentally
	 * convert themselves away from being an admin.
	 * 
	 * @param object $object
	 * @param object $field
	 * @return 
	 */
	function get_htmlform_list($object, $field)
	{
		if($object->model == 'user')
		{
			// limit the items if the user is the logged-in user
			$CI =& get_instance();
			if($CI->login_manager->get_user()->id == $object->id)
			{
				$this->get_by_id(1);
				return;
			}
		}
		$this->get_iterated();
	}
}

/* End of file group.php */
/* Location: ./application/models/group.php */