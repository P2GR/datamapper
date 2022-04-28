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
 * @version 	2.0.0
 */

// determine our driver alias name
$org_driver = $driver;
$driver = str_replace('CI_DB_', 'DM_DB_', $driver);

if ( ! class_exists($driver, FALSE) )
{
	$dmclass = <<<CODE
class $driver extends $org_driver
{
	// public interface to internal driver methods
	public function dm_call_method(\$function, ...\$args)
	{
		return \$this->{\$function}(...\$args);
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
}
CODE;

	// dynamically add our class extension
	eval($dmclass);
	unset($dmclass);
}

/* End of file DB_driver.php */
/* Location: ./application/third_party/datamapper/system/DB_driver.php */
