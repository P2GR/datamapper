<?php

class Bugs extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		// prevent non-logged-in access
		$this->load->library('login_manager');
	}
	
	function index()
	{
		
	}
	
	function report($save = FALSE)
	{
		$bug = new Bug();
		$this->_edit('Report a Bug', 'report', $bug, 'bugs/report/save', $save);
	}
	
	function edit($id)
	{
		$bug = new Bug();
		if($id == 'save')
		{
			$bug->get_by_id($this->input->post('id'));
			$save = TRUE;
		}
		else
		{
			$bug->get_by_id($id);
			$save = FALSE;
		}
		if($bug->exists())
		{
			$this->_edit('Edit a Bug', 'search', $bug, 'bugs/edit/save', $save);
		}
		else
		{
			show_error('Invalid Bug ID');
		}
	}
	
	/**
	 * Called by the edit and report segments. 
	 * 
	 * @param string $title For the header
	 * @param string $section For the header
	 * @param Bug $bug Bug to edit or a blank bug
	 * @param string $url The url to save on
	 * @param boolean $save If TRUE, then attempt a save.
	 */
	function _edit($title, $section, $bug, $url, $save)
	{	
		if($save)
		{
			// attempt to save the bug
			$bug->trans_start();
			// Use the (already-loaded) array extension to process the POSTed values.
			$rel = $bug->from_array($_POST, array(
				'title',
				'description',
				'priority',
				'status',
				'category',
				'user'
			));
			
			// We also have to specify the editor...
			$rel['editor'] = $this->login_manager->get_user();
			if( ! $bug->exists())
			{
				// ...and creator for new bugs
				$rel['creator'] = $this->login_manager->get_user();
			}
			$exists = $bug->exists();
			if($bug->save($rel))
			{
				// saved successfully, so commit and redirect
				$bug->trans_complete();
				// Store a message
				if($exists)
				{
					$this->session->set_flashdata('message', 'This bug was updated successfully.');
				}
				else
				{
					$this->session->set_flashdata('message', 'This bug was created successfully.');
				}
				redirect('bugs/view/' . $bug->id);
			}
		}
		
		// Load the htmlform extension, so we can generate the form.
		$bug->load_extension('htmlform');
		
		// We want to limit the users to those who are assignable (not simply bug reporters)
		$users = new User();
		$users->get_assignable();
		
		// This is how are form will be rendered
		$form_fields = array(
			'id', // Hidden id field
			'title', // Title field
			'description' => array(  // multi-line field for description
				'rows' => 6, // height and width could be specified using CSS instead
				'cols' => 40
			),
			'priority', // Priority (a dropdown containing 4 items)
			'status', // Status (a dropdown with all known statuses)
			'category', // A checkbox or select list of categories
			'user' => array( // A checkbox or select list of users
				'list' => $users // limit the users to the list above
			)
		);
		
		// Send the results to the views
		$this->output->enable_profiler(TRUE);
		$this->load->view('template_header', array('title' => $title, 'section' => $section));
		$this->load->view('bugs/edit', array('bug' => $bug, 'form_fields' => $form_fields, 'url' => $url));
		$this->load->view('template_footer');
	}
	
	function search()
	{
		$this->output->enable_profiler(TRUE);
		
		if( ! empty($_POST))
		{
			// convert post to search, redirect (for bookmarkability)
			$url = $this->_write_search($_POST);
			redirect($url);
		}
		
		$search = FALSE;
		
		$args = func_get_args();
		if( ! empty($args))
		{
			$search = $this->_read_search($args);
		}
		
		$bug = new Bug();
		$bug->load_extension('htmlform');
		
		$values = array('text' => '', 'priority' => array(), 'status' => array(), 'category' => array(), 'user' => array());
		if($search)
		{
			foreach($values as $k => $v)
			{
				if(isset($search['args'][$k]))
				{
					$values[$k] = $search['args'][$k];
				}
			}
		}
		
		// Lets limit the users for a bug to Users and Admins
		$users = new User();
		$users->get_assignable();
		
		// Search Form Layout
		$form_fields = array(
			'text' => array(
				'type' => 'text',
				'label' => 'Containing Text',
				'size' => 30,
				'maxlength' => 100,
				'value' => $values['text']
			),
			'priority' => array(
				'label' => 'With Priorities',
				'type' => 'dropdown',
				'multiple' => 'multiple',
				'value' => $values['priority']
			),
			'status' => array(
				'label' => 'With Statuses',
				'type' => 'dropdown',
				'multiple' => 'multiple',
				'value' => $values['status']
			),
			'category' => array(
				'label' => 'With Categories',
				'type' => 'dropdown',
				'multiple' => 'multiple',
				'value' => $values['category']
			),
			'user' => array(
				'label' => 'Assigned to Users',
				'type' => 'dropdown',
				'multiple' => 'multiple',
				'value' => $values['user'],
				'list' => $users // limit the users to the ones selected above
			)
		);
		
		$view_data = array(
			'search' => $search,
			'bugs' => FALSE,
			'bug' => $bug,
			'form_fields' => $form_fields,
			'url' => 'bugs/search'
		);
		
		if( $search &&  empty($search['args']))
		{
			// show error that nothing was selected
			$bug->error_message('general', 'Nothing was selected'); 
		}
		
		if($search && ! empty($search['args']))
		{
			$view_data['bugs'] = $this->_process_search($search);
		}
		
		$this->output->enable_profiler(TRUE);
		$this->load->view('template_header', array('title' => 'Find Bugs', 'section' => 'search'));
		$this->load->view('bugs/search', $view_data);
		$this->load->view('template_footer');
	}
	
	function _write_search($array, $page = 1)
	{
		// convert post to search, redirect (for bookmarkability)
		$url = 'bugs/search';
		if( ! empty($array['text']))
		{
			$url .= '/text:' . str_replace('%', '~', rawurlencode(utf8_encode($this->input->post('text'))));
		}
		foreach(array('priority', 'status', 'category', 'user') as $x)
		{
			if( isset($array[$x]))
			{
				$url .= "/$x:" . implode('~', $array[$x]);
			}
		}
		$url .= '/page:' . $page;
		return $url;
	}
	
	function _read_search($args)
	{
		$search = array('args' => array(), 'page' => 0);
			
		// build search query
		foreach($args as $a)
		{
			if($a === '')
			{
				continue;
			}
			list($key, $value) = explode(':', $a, 2);
			if($key == 'text')
			{
				$search['args']['text'] = utf8_decode(rawurldecode(str_replace('~', '%', $value)));
			}
			else if($key == 'page')
			{
				// get_paged automatically handles the paging
				$search['page'] = $value;
			}
			else
			{
				$search['args'][$key] = explode('~', $value);
			}
		}
		
		return $search;
	}
	
	function _process_search($search)
	{
		$bugs = new Bug();
		$bugs->distinct();
		$args = $search['args'];
		// Put related first, to force prepending of table name
		foreach(array('status', 'category', 'user') as $rel)
		{
			if(isset($args[$rel]))
			{
				$v = array_unique(array_map('intval', $args[$rel]));
				$bugs->where_in_related($rel, 'id', $v);
			}
		}
		if(isset($args['text']))
		{
			$kws = explode(' ', $args['text']);
			if( ! empty($kws))
			{
				$bugs->group_start();
				foreach($kws as $kw)
				{
					if( $kw !== '')
					{
						// case insensitive search
						$kw = strtoupper($kw);
						$bugs->or_ilike('title', $kw);
						$bugs->or_ilike('description', $kw);
					}
				}
				$bugs->group_end();
			}
		}
		if(isset($args['priority']))
		{
			$v = array_unique(array_map('intval', $args['priority']));
			$bugs->where_in('priority', $v);
		}
		$limit = 15;
		$page = $limit * $search['page'];
		
		// add in extras
		$bugs->include_related('status', 'name', TRUE, TRUE);
		$bugs->order_by('updated', 'DESC');
		
		return $bugs->get_paged_iterated($search['page'], $limit);
	}
	
	function view($id)
	{
		$bug = new Bug();
		$bug->include_related('status', 'name', TRUE, TRUE);
		$bug->include_related('creator', 'name', TRUE, TRUE);
		$bug->include_related('editor', 'name', TRUE, TRUE);
		$bug->get_by_id($id);
		if( ! $bug->exists())
		{
			show_error('Invalid Bug ID');
		}
		
		$bug->categories->get_iterated();
		$bug->users->get_iterated();
		
		$this->load->helper('typography');
		
		$this->output->enable_profiler(TRUE);
		$this->load->view('template_header', array('title' => 'Bug: ' . $bug->title, 'section' => 'search'));
		$this->load->view('bugs/view', array('bug' => $bug));
		$this->load->view('template_footer');
	}
}

/* End of file bugs.php */
/* Location: ./system/application/controllers/bugs.php */