<?php

/**
 * Bug DataMapper Model
 *
 * The core class for the application
 *
 * @license		MIT License
 * @category	Models
 * @author  	Phil DeJarnett
 * @link    	http://www.overzealous.com
 */
class Bug extends DataMapper {

	// --------------------------------------------------------------------
	// Relationships
	// --------------------------------------------------------------------
	
	// Insert related models that Bug can have just one of.
	public $has_one = array(
		// The creator of this bug
		'creator' => array(
			'class' => 'user',
	 		'other_field' => 'created_bug'
	 	),
		// The editor of this bug 
		'editor' => array(
			'class' => 'user',
	 		'other_field' => 'edited_bug'
	 	),
		// Keep track of this bug's status
		'status'
	 );
	
	// Insert related models that Bug can have more than one of.
	public $has_many = array(
		// users assigned to this bug
		'user',
		// Other Bugs that depend on this Bug
		'dependent' => array(
			'class' => 'bug',
			'other_field' => 'dependency'
		),
		// Other Bugs that this Bug depends on
		'dependency' => array(
			'class' => 'bug',
			'other_field' => 'dependent'
		),
		// categories for this Bug
		'category'
	);
	
	// --------------------------------------------------------------------
	// Validation
	// --------------------------------------------------------------------
	
	public $validation = array(
		'title' => array(
			'rules' => array('required', 'trim', 'max_length' => 100)
		),
		'description' => array(
			'rules' => array('required', 'xss_clean'),
			'type' => 'textarea'
		),
		'priority' => array(
			'rules' => array('required', 'integer', 'min_size' => 0, 'max_size' => 3),
			'get_rules' => array('intval'),
			'type' => 'dropdown',
			'values' => array(
				'0' => 'None',
				'1' => 'Low',
				'2' => 'Medium',
				'3' => 'High' 
			)
		),
		'creator' => array(
			'rules' => array('required')
		),
		'editor' => array(
			'rules' => array('required')
		),
		'status' => array(
			'rules' => array('required')
		)
	);
	
	// --------------------------------------------------------------------
	
	public function get_priority()
	{
		$p = $this->priority;
		if( ! is_numeric($p))
		{
			$p = 0;
		}
		return $this->validation['priority']['values'][$p];
	}
}

/* End of file bug.php */
/* Location: ./application/models/bug.php */
