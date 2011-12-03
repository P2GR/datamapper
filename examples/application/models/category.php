<?php

/**
 * Category Class
 * Used to organize Bugs
 *
 * @license 	MIT License
 * @category	Models
 * @author  	Phil DeJarnett
 * @link    	http://www.overzealous.com/dmz/
 */
class Category extends DataMapper {

	// --------------------------------------------------------------------
	// Relationships
	// --------------------------------------------------------------------

	public $has_many = array('bug');
	
	// --------------------------------------------------------------------
	// Validation
	// --------------------------------------------------------------------	
	
	public $validation = array(
		'name' => array(
			'rules' => array('required', 'trim', 'unique', 'max_length' => 40)
		)
	);
	
	// Default to ordering by name
	public $default_order_by = array('name');
	
	// --------------------------------------------------------------------	
	
	/**
	 * Returns the name of this status.
	 * @return $this->name
	 */
	function __toString()
	{
		return empty($this->name) ? $this->localize_label('unset') : $this->name;
	}
}

/* End of file category.php */
/* Location: ./application/models/category.php */