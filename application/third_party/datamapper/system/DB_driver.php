<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM bootstrap
 *
 * Dynamic CI Loader class extension
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.2.0
 */

// determine our driver alias name
$org_driver = $driver;
$driver = str_replace('CI_DB_', 'DM_DB_', $driver);

if ( ! class_exists($driver, FALSE) )
{
	$dmclass = <<<CODE
class $driver extends $org_driver
{
	protected \$_dm_cache_invalidations = array();
	// public interface to internal driver methods
	// Compatible with both native CI3 and pocketarc fork
	public function dm_call_method(\$function, ...\$args)
	{
		// Check if method exists (for compatibility)
		if (method_exists(\$this, \$function))
		{
			return \$this->{\$function}(...\$args);
		}
		
		// Fallback for methods that might not exist in newer CI versions
		// This handles edge cases in different CI3 versions
		throw new BadMethodCallException("Method '\$function' does not exist in database driver");
	}

	// public interface to internal driver properties
	public function dm_get(\$var)
	{
		return isset(\$this->{\$var}) ? \$this->{\$var} : NULL;
	}

	public function dm_set(\$var, \$value)
	{
		\$this->{\$var} = \$value;
	}

	public function dm_set_append(\$var, \$value)
	{
		\$this->{\$var}[] = \$value;
	}

	public function dm_share_transaction_state(\$source)
	{
		foreach (array('_trans_depth', '_trans_status', '_trans_failure', '_dm_cache_invalidations') as \$property)
		{
			\$this->{\$property} =& \$source->{\$property};
		}
	}

	public function dm_defer_cache_invalidation(\$cache, \$pattern)
	{
		\$this->_dm_cache_invalidations[spl_object_id(\$cache) . ':' . \$pattern] = array(\$cache, \$pattern);
	}

	public function trans_commit()
	{
		\$result = parent::trans_commit();
		if (\$result && \$this->_trans_depth === 0) {
			\$pending = \$this->_dm_cache_invalidations;
			\$this->_dm_cache_invalidations = array();
			foreach (\$pending as \$entry) {
				try {
					\$entry[0]->delete_pattern(\$entry[1]);
				} catch (Throwable \$error) {
					log_message('error', 'DataMapper cache invalidation failed after commit: ' . \$error->getMessage());
				}
			}
		}
		return \$result;
	}

	public function trans_rollback()
	{
		\$result = parent::trans_rollback();
		if (\$result && \$this->_trans_depth === 0) {
			\$this->_dm_cache_invalidations = array();
		}
		return \$result;
	}
}
CODE;

	// dynamically add our class extension
	eval($dmclass);
	unset($dmclass);
}

/* End of file DB_driver.php */
/* Location: ./application/third_party/datamapper/system/DB_driver.php */
