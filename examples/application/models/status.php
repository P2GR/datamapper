<?php

/**
 * Status Class
 * Used to keep track of a Bug's status
 *
 * @license 	MIT License
 * @category	Models
 * @author  	Phil DeJarnett
 * @link    	http://www.overzealous.com/dmz/
 */
class Status extends DataMapper {

	// Overridden because inflector has trouble convering status <> statuses
	var $model = 'status';
	var $table = 'statuses';

	// --------------------------------------------------------------------
	// Relationships
	// --------------------------------------------------------------------

	var $has_many = array('bug');
	
	// --------------------------------------------------------------------
	// Validation
	// --------------------------------------------------------------------	
	
	var $validation = array(
		'name' => array(
			'rules' => array('required', 'trim', 'unique', 'max_length' => 40)
		),
		'closed' => array(
			'rules' => array('boolean'),
			'type' => 'checkbox'
		)
	);
	
	// Default to ordering by sortorder
	var $default_order_by = array('sortorder');
	
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

/* End of file status.php */
/* Location: ./application/models/status.php */