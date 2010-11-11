<?php

/**
 * Nested Sets Extension for DataMapper classes.
 *
 * Nested Sets DataMapper model
 *
 * @license 	MIT License
 * @package		DMZ-Included-Extensions
 * @category	DMZ
 * @author  	WanWizard
 * @info		Based on nstrees by Rolf Brugger, edutech
 * 				http://www.edutech.ch/contribution/nstrees
 * @version 	1.0
 */

// --------------------------------------------------------------------------

/**
 * DMZ_Nestedsets Class
 *
 * @package		DMZ-Included-Extensions
 */
class DMZ_Nestedsets {

	/**
	 * name of the tree node left index field
	 *
	 * @var    string
	 * @access private
	 */
	private $_leftindex = 'left_id';

	/**
	 * name of the tree node right index field
	 *
	 * @var    string
	 * @access private
	 */
	private $_rightindex = 'right_id';

	/**
	 * name of the tree root id field. Used when the tree contains multiple roots
	 *
	 * @var    string
	 * @access private
	 */
	private $_rootfield = 'root_id';

	/**
	 * value of the root field we need to filter on
	 *
	 * @var    string
	 * @access private
	 */
	private $_rootindex = NULL;

	/**
	 * name of the tree node symlink index field
	 *
	 * @var    string
	 * @access private
	 */
	private $_symlinkindex = 'symlink_id';

	/**
	 * name of the tree node name field, used to build a path string
	 *
	 * @var    string
	 * @access private
	 */
	private $_nodename = NULL;

	/**
	 * indicates with pointers need to be used
	 *
	 * @var    string
	 * @access private
	 */
	private $use_symlink_pointers = TRUE;

	// -----------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * @param	mixed	optional, array of load-time options or NULL
	 * @param	object	the DataMapper object
	 * @return	void
	 * @access	public
	 */
	function __construct( $options = array(), $object = NULL )
	{
		// do we have the datamapper object
		if ( ! is_null($object) )
		{			// no, extension is loaded manually
			// update the config
			$this->tree_config($object, $options);
		}
	}

	// -----------------------------------------------------------------

	/**
	 * runtime configuration of this nestedsets tree
	 *
	 * @param	object	the DataMapper object
	 * @param	mixed	optional, array of options or NULL
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function tree_config($object, $options = array() )
	{
		// make sure the load-time options parameter is an array
		if ( ! is_array($options) )
		{
			$options = array();
		}

		// make sure the model options parameter is an array
		if ( ! isset($object->nestedsets) OR ! is_array($object->nestedsets) )
		{
			$object->nestedsets = array();
		}

		// loop through all options
		foreach( array( $object->nestedsets, $options ) as $optarray )
		{
			foreach( $optarray as $key => $value )
			{
				switch ( $key )
				{
					case 'name':
						$this->_nodename = (string) $value;
						break;
					case 'symlink':
						$this->_symlinkindex = (string) $value;
						break;
					case 'left':
						$this->_leftindex = (string) $value;
						break;
					case 'right':
						$this->_rightindex = (string) $value;
						break;
					case 'root':
						$this->_rootfield = (string) $value;
						break;
					case 'value':
						$this->_rootindex = (int) $value;
						break;
					case 'follow':
						$this->use_symlink_pointers = (bool) $value;
						break;
					default:
						break;
				}
			}
		}
	}

	// -----------------------------------------------------------------

	/**
	 * select a specific root if the table contains multiple trees
	 *
	 * @param	object	the DataMapper object
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function select_root($object, $tree = NULL)
	{
		// set the filter value
		$this->_rootindex = $tree;

		// return the object
		return $object;
	}

	// -----------------------------------------------------------------
	// Tree constructors
	// -----------------------------------------------------------------

	/**
	 * create a new tree root
	 *
	 * @param	object	the DataMapper object
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function new_root($object)
	{
		// set the pointers for the root object
		$object->id = NULL;
		$object->{$this->_leftindex} = 1;
		$object->{$this->_rightindex} = 2;

		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->{$this->_rootfield} = $this->_rootindex;
		}

		// create the new tree root, and return the updated object
		return $this->_insertNew($object);
	}

	// -----------------------------------------------------------------

	/**
	 * creates a new first child of 'node'
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the parent node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function new_first_child($object, $node)
	{
		// set the pointers for the root object
		$object->id = NULL;
		$object->{$this->_leftindex} = $node->{$this->_leftindex} + 1;
		$object->{$this->_rightindex} = $node->{$this->_leftindex} + 2;

		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->{$this->_rootfield} = $this->_rootindex;
		}

		// shift nodes to make room for the new child
		$this->_shiftRLValues($node, $object->{$this->_leftindex}, 2);

		// create the new tree node, and return the updated object
		return $this->_insertNew($object);
	}

	// -----------------------------------------------------------------

	/**
	 * creates a new last child of 'node'
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the parent node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function new_last_child($object, $node)
	{
		// set the pointers for the root object
		$object->id = NULL;
		$object->{$this->_leftindex} = $node->{$this->_rightindex};
		$object->{$this->_rightindex} = $node->{$this->_rightindex} + 1;

		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->{$this->_rootfield} = $this->_rootindex;
		}

		// shift nodes to make room for the new child
		$this->_shiftRLValues($node, $object->{$this->_leftindex}, 2);

		// create the new tree node, and return the updated object
		return $this->_insertNew($object);
	}

	// -----------------------------------------------------------------

	/**
	 * creates a new sibling before 'node'
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the sibling node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function new_previous_sibling($object, $node)
	{
		// set the pointers for the root object
		$object->id = NULL;
		$object->{$this->_leftindex} = $node->{$this->_leftindex};
		$object->{$this->_rightindex} = $node->{$this->_leftindex} + 1;

		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->{$this->_rootfield} = $this->_rootindex;
		}

		// shift nodes to make room for the new sibling
		$this->_shiftRLValues($node, $object->{$this->_leftindex}, 2);

		// create the new tree node, and return the updated object
		return $this->_insertNew($object);
	}

	// -----------------------------------------------------------------

	/**
	 * creates a new sibling after 'node'
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the sibling node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function new_next_sibling($object, $node)
	{
		// set the pointers for the root object
		$object->id = NULL;
		$object->{$this->_leftindex} = $node->{$this->_rightindex} + 1;
		$object->{$this->_rightindex} = $node->{$this->_rightindex} + 2;

		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->{$this->_rootfield} = $this->_rootindex;
		}

		// shift nodes to make room for the new sibling
		$this->_shiftRLValues($node, $object->{$this->_leftindex}, 2);

		// create the new tree node, and return the updated object
		return $this->_insertNew($object);
	}

	// -----------------------------------------------------------------
	// Tree queries
	// -----------------------------------------------------------------


	/**
	 * returns the root of the (selected) tree
	 *
	 * @param	object	the DataMapper object
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function get_root($object)
	{
		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->db->where($this->_rootfield, $this->_rootindex);
		}

		// get the tree's root node
		return $object->where($this->_leftindex, 1)->get();
	}

	// -----------------------------------------------------------------

	/**
	 * returns the parent of the child 'node'
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the child node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function get_parent($object, $node = NULL)
	{
		// a node passed?
		if ( is_null($node) )
		{
			// no, use the object itself
			$node =& $object;
		}

		// we need a valid node for this to work
		if ( ! $node->exists() )
		{
			return $node;
		}

		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->db->where($this->_rootfield, $this->_rootindex);
		}

		// get the node's parent node
		$object->where($this->_leftindex . ' <', $node->{$this->_leftindex});
		$object->where($this->_rightindex . ' >', $node->{$this->_rightindex});
		return $object->order_by($this->_rightindex, 'asc')->limit(1)->get();
	}

	// -----------------------------------------------------------------

	/**
	 * returns the node with the requested left index pointer
	 *
	 * @param	object	the DataMapper object
	 * @param	integer	a node's left index value
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function get_node_where_left($object, $left_id)
	{
		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->db->where($this->_rootfield, $this->_rootindex);
		}

		// get the node's parent node
		$object->where($this->_leftindex, $left_id);
		return $object->get();
	}

	// -----------------------------------------------------------------

	/**
	 * returns the node with the requested right index pointer
	 *
	 * @param	object	the DataMapper object
	 * @param	integer	a node's right index value
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function get_node_where_right($object, $right_id)
	{
		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->db->where($this->_rootfield, $this->_rootindex);
		}

		// get the node's parent node
		$object->where($this->_rightindex, $right_id);
		return $object->get();
	}

	// -----------------------------------------------------------------

	/**
	 * returns the first child of the given node
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the parent node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function get_first_child($object, $node = NULL)
	{
		// a node passed?
		if ( is_null($node) )
		{
			// no, use the object itself
			$node =& $object;
		}

		// we need a valid node for this to work
		if ( ! $node->exists() )
		{
			return $node;
		}

		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->db->where($this->_rootfield, $this->_rootindex);
		}

		// get the node's first child node
		$object->where($this->_leftindex, $node->{$this->_leftindex}+1);
		return $object->get();
	}

	// -----------------------------------------------------------------

	/**
	 * returns the last child of the given node
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the parent node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function get_last_child($object, $node = NULL)
	{
		// a node passed?
		if ( is_null($node) )
		{
			// no, use the object itself
			$node =& $object;
		}

		// we need a valid node for this to work
		if ( ! $node->exists() )
		{
			return $node;
		}

		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->db->where($this->_rootfield, $this->_rootindex);
		}

		// get the node's last child node
		$object->where($this->_rightindex, $node->{$this->_rightindex}-1);
		return $object->get();
	}

	// -----------------------------------------------------------------

	/**
	 * returns the previous sibling of the given node
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the sibling node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function get_previous_sibling($object, $node = NULL)
	{
		// a node passed?
		if ( is_null($node) )
		{
			// no, use the object itself
			$node =& $object;
		}

		// we need a valid node for this to work
		if ( ! $node->exists() )
		{
			return $node;
		}

		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->db->where($this->_rootfield, $this->_rootindex);
		}

		// get the node's previous sibling node
		$object->where($this->_rightindex, $node->{$this->_leftindex}-1);
		return $object->get();
	}

	// -----------------------------------------------------------------

	/**
	 * returns the next sibling of the given node
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the sibling node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function get_next_sibling($object, $node = NULL)
	{
		// a node passed?
		if ( is_null($node) )
		{
			// no, use the object itself
			$node =& $object;
		}

		// we need a valid node for this to work
		if ( ! $node->exists() )
		{
			return $node;
		}

		// add a root index if needed
		if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
		{
			$object->db->where($this->_rootfield, $this->_rootindex);
		}

		// get the node's next sibling node
		$object->where($this->_leftindex, $node->{$this->_rightindex}+1);
		return $object->get();
	}

	// -----------------------------------------------------------------
	// Boolean tree functions
	// -----------------------------------------------------------------

	/**
	 * check if the object is a valid tree node
	 *
	 * @param	object	the DataMapper object
	 * @return	boolean
	 * @access	public
	 */
	function is_valid_node($object)
	{
		if ( ! $object->exists() )
		{
			return FALSE;
		}
		elseif ( ! isset($object->{$this->_leftindex}) )
		{
			return FALSE;
		}
		elseif ( ! isset($object->{$this->_rightindex}) )
		{
			return FALSE;
		}
		elseif ( $object->{$this->_leftindex} >= $object->{$this->_rightindex} )
		{
			return FALSE;
		}
		elseif ( ! empty($this->_rootfield) && ! in_array($this->_rootfield, $object->fields) )
		{
			return FALSE;
		}

		// all looks well...
		return TRUE;
	}

	// -----------------------------------------------------------------

	/**
	 * check if the object is a tree root
	 *
	 * @param	object	the DataMapper object
	 * @return	boolean
	 * @access	public
	 */
	function is_root($object)
	{
		return ( $object->exists() && $object->{$this->_leftindex} === 1 );
	}

	// -----------------------------------------------------------------

	/**
	 * check if the object is a tree leaf (node with no children)
	 *
	 * @param	object	the DataMapper object
	 * @return	boolean
	 * @access	public
	 */
	function is_leaf($object)
	{
		return ( $object->exists() && $object->{$this->_rightindex} - $object->{$this->_leftindex} == 1 );
	}

	// -----------------------------------------------------------------

	/**
	 * check if the object is a child of node
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the parent node
	 * @return	boolean
	 * @access	public
	 */
	function is_child_of($object, $node = NULL)
	{
		// a node passed?
		if ( is_null($node) OR ! $node->exists() )
		{
			return FALSE;
		}

		return ( $object->exists() && $object->{$this->_leftindex} > $node->{$this->_leftindex} && $object->{$this->_rightindex} < $node->{$this->_rightindex} );
	}

	// -----------------------------------------------------------------

	/**
	 * check if the object is the parent of node
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the parent node
	 * @return	boolean
	 * @access	public
	 */
	function is_parent_of($object, $node = NULL)
	{
		// a node passed?
		if ( is_null($node) OR ! $node->exists() )
		{
			return FALSE;
		}

		// fetch the parent using a clone
		$parent = $node->get_clone();
		$parent->get_parent($node);

		// found?
		return $parent->exists();
	}

	// -----------------------------------------------------------------

	/**
	 * check if the object has a parent
	 *
	 * @param	object	the DataMapper object
	 * @return	boolean
	 * @access	public
	 */
	function has_parent($object)
	{
		// fetch the result using a clone
		$node = $object->get_clone();
		return $this->is_valid_node($node->get_parent($object));
	}

	// -----------------------------------------------------------------

	/**
	 * check if the object has children
	 *
	 * @param	object	the DataMapper object
	 * @return	boolean
	 * @access	public
	 */
	function has_children($object)
	{
		return ( $object->exists() && $object->{$this->_rightindex} - $object->{$this->_leftindex} > 1 );
	}

	// -----------------------------------------------------------------

	/**
	 * check if the object has a previous silbling
	 *
	 * @param	object	the DataMapper object
	 * @return	boolean
	 * @access	public
	 */
	function has_previous_sibling($object)
	{
		// fetch the result using a clone
		$node = $object->get_clone();
		return $this->is_valid_node($node->get_previous_sibling($object));
	}

	// -----------------------------------------------------------------

	/**
	 * check if the object has a next silbling
	 *
	 * @param	object	the DataMapper object
	 * @return	boolean
	 * @access	public
	 */
	function has_next_sibling($object)
	{
		// fetch the result using a clone
		$node = $object->get_clone();
		return $this->is_valid_node($node->get_next_sibling($object));
	}

	// -----------------------------------------------------------------
	// Integer tree functions
	// -----------------------------------------------------------------

	/**
	 * return the count of the objects children
	 *
	 * @param	object	the DataMapper object
	 * @return	integer
	 * @access	public
	 */
	function count_children($object)
	{
		return ( $object->exists() ? (($object->{$this->_rightindex} - $object->{$this->_leftindex} - 1) / 2) : 0 );
	}

	// -----------------------------------------------------------------

	/**
	 * return the node level, where the root = 0
	 *
	 * @param	object	the DataMapper object
	 * @return	mixed	integer, of FALSE in case no valid object was passed
	 * @access	public
	 */
	function level($object)
	{
		if ( $object->exists() )
		{
			// add a root index if needed
			if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
			{
				$object->db->where($this->_rootfield, $this->_rootindex);
			}

			$object->where($this->_leftindex.' <', $object->{$this->_leftindex});
			$object->where($this->_rightindex.' >', $object->{$this->_rightindex});
			return $object->count();
		}
		else
		{
			return FALSE;
		}
	}

	// -----------------------------------------------------------------
	// Tree reorganisation
	// -----------------------------------------------------------------

	/**
	 * move the object as next sibling of 'node'
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the sibling node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function make_next_sibling_of($object, $node)
	{
		return $this->_moveSubtree($object, $node, $node->{$this->_rightindex}+1);
	}

	// -----------------------------------------------------------------

	/**
	 * move the object as previous sibling of 'node'
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the sibling node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function make_previous_sibling_of($object, $node)
	{
		return $this->_moveSubtree($object, $node, $node->{$this->_leftindex});
	}

	// -----------------------------------------------------------------

	/**
	 * move the object as first child of 'node'
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the sibling node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function make_first_child_of($object, $node)
	{
		return $this->_moveSubtree($object, $node, $node->{$this->_leftindex}+1);
	}

	// -----------------------------------------------------------------

	/**
	 * move the object as last child of 'node'
	 *
	 * @param	object	the DataMapper object
	 * @param	object	the sibling node
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function make_last_child_of($object, $node)
	{
		return $this->_moveSubtree($object, $node, $node->{$this->_rightindex});
	}

	// -----------------------------------------------------------------
	// Tree destructors
	// -----------------------------------------------------------------

	/**
	 * deletes the entire tree structure including all records
	 *
	 * @param	object	the DataMapper object
	 * @param	mixed	optional, id of the tree to delete
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function remove_tree($object, $tree_id = NULL)
	{
		// if we have multiple roots
		if ( in_array($this->_rootfield, $object->fields) )
		{
			// was a tree id passed?
			if ( ! is_null($tree_id) )
			{
				// only delete the selected one
				$object->db->where($this->_rootfield, $tree_id)->delete($object->table);
			}
			elseif ( ! is_null($this->_rootindex) )
			{
				// only delete the selected one
				$object->db->where($this->_rootfield, $this->_rootindex)->delete($object->table);
			}
			else
			{
			// delete them all
			$object->db->truncate($object->table);
			}
		}
		else
		{
			// delete them all
			$object->db->truncate($object->table);
		}

		// return the cleared object
		return $object->clear();
	}

	// -----------------------------------------------------------------

	/**
	 * deletes the current object, and all childeren
	 *
	 * @param	object	the DataMapper object
	 * @return	object	the updated DataMapper object
	 * @access	public
	 */
	function remove_node($object)
	{
		// we need a valid node to do this
		if ( $object->exists() )
		{
			// if we have multiple roots
			if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
			{
				// only delete the selected one
				$object->db->where($this->_rootfield, $this->_rootindex);
			}

			// clone the object, we need to it shift later
			$clone = $object->get_clone();

			// select the node and all children
			$object->db->where($this->_leftindex . ' >=', $object->{$this->_leftindex});
			$object->db->where($this->_rightindex . ' <=', $object->{$this->_rightindex});

			// delete them all
			$object->db->delete($object->table);

			// re-index the tree
			$this->_shiftRLValues($clone, $object->{$this->_rightindex} + 1, $clone->{$this->_leftindex} - $object->{$this->_rightindex} -1);
		}

		// return the cleared object
		return $object->clear();
	}

	// -----------------------------------------------------------------
	// internal methods
	// -----------------------------------------------------------------

	/**
	 * makes room for a new node (or nodes) by shifting the left and right
	 * id's of nodes with larger values than our object by $delta
	 *
	 * note that $delta can also be negative!
	 *
	 * @param	object	the DataMapper object
	 * @param	integer	left value of the start node
	 * @param	integer	number of positions to shift
	 * @return	object	the updated DataMapper object
	 * @access	private
	 */
	private function _shiftRLValues($object, $first, $delta)
	{
		// we need a valid object
		if ( $object->exists() )
		{
			// if we have multiple roots
			if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
			{
				// select the correct one
				$object->where($this->_rootfield, $this->_rootindex);
			}

			// select the range
			$object->where($this->_leftindex.' >=', $first);
			$object->update(array($this->_leftindex => $this->_leftindex.' + '.$delta), FALSE);

			// if we have multiple roots
			if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
			{
				// select the correct one
				$object->where($this->_rootfield, $this->_rootindex);
			}

			// select the range
			$object->where($this->_rightindex.' >=', $first);
			$object->update(array($this->_rightindex => $this->_rightindex.' + '.$delta), FALSE);
		}

		// return the object
		return $object;
	}

	// -----------------------------------------------------------------

	/**
	 * shifts a range of nodes up or down the left and right index by $delta
	 *
	 * note that $delta can also be negative!
	 *
	 * @param	object	the DataMapper object
	 * @param	integer	left value of the start node
	 * @param	integer	right value of the end node
	 * @param	integer	number of positions to shift
	 * @return	object	the updated DataMapper object
	 * @access	private
	 */
	private function _shiftRLRange($object, $first, $last, $delta)
	{
		// we need a valid object
		if ( $object->exists() )
		{
			// if we have multiple roots
			if ( in_array($this->_rootfield, $object->fields) && ! is_null($this->_rootindex) )
			{
				// select the correct one
				$object->where($this->_rootfield, $this->_rootindex);
			}

			// select the range
			$object->where($this->_leftindex.' >=', $first);
			$object->where($this->_rightindex.' <=', $last);
			$object->update(array($this->_leftindex => $this->_leftindex.' + '.$delta, $this->_rightindex => $this->_rightindex.' + '.$delta), FALSE);
		}

		// return the object
		return $object;
	}

	// -----------------------------------------------------------------

	/**
	 * inserts a new record into the tree
	 *
	 * @param	object	the DataMapper object
	 * @return	object	the updated DataMapper object
	 * @access	private
	 */
	private function _insertNew($object)
	{
		// for now, just save the object
		$object->save();

		// return the object
		return $object;
	}

	// -----------------------------------------------------------------

	/**
	 * move a section of the tree to another location within the tree
	 *
	 * @param	object	the DataMapper object we're going to move
	 * @param	integer	the destination node's left id value
	 * @return	object	the updated DataMapper object
	 * @access	private
	 */
	private function _moveSubtree($object, $node, $destination_id)
	{
		// determine the size of the tree to move
		$treesize = $object->{$this->_rightindex} - $object->{$this->_leftindex} + 1;

		// get the objects left- and right pointers
		$left_id = $object->{$this->_leftindex};
		$right_id = $object->{$this->_rightindex};

		// shift to make some space
		$this->_shiftRLValues($node, $destination_id, $treesize);

		// enough room now, start the move
		$this->_shiftRLRange($node, $left_id, $right_id, $destination_id - $left_id);

		// and correct index values after the source
		$this->_shiftRLValues($object, $right_id + 1, $treesize * -1);

		// return the object
		return $object->get_by_id($object->id);
	}

}

/* End of file nestedsets.php */
/* Location: ./application/datamapper/nestedsets.php */
