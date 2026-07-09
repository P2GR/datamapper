<?php

// Define testing environment constants expected by DataMapper
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../system/');
}

if (!defined('APPPATH')) {
    define('APPPATH', realpath(__DIR__ . '/../application/') . DIRECTORY_SEPARATOR);
}

if (!defined('EXT')) {
    define('EXT', '.php');
}

// Provide minimal CodeIgniter instance shim used by DataMapper
class CI_TestHarness
{
    public $load;
    public $config;
    public $lang;

    public function __construct()
    {
        $this->load = new CI_Loader_Shim();
        $this->config = new CI_Config_Shim();
        $this->lang = new CI_Lang_Shim();
    }
}

class CI_Loader_Shim
{
    public function helper($name)
    {
        $path = APPPATH . 'helpers/' . $name . '_helper.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
}

class CI_Config_Shim
{
    private $items = array();

    public function load($file, $use_sections = TRUE, $fail_gracefully = TRUE)
    {
        $path = APPPATH . 'config/' . $file . '.php';
        if (file_exists($path)) {
            $config = array();
            require $path;
            $this->items[$file] = isset($config) ? $config : array();
        }
    }

    public function item($key, $index = '')
    {
        if ($index !== '' && isset($this->items[$index][$key])) {
            return $this->items[$index][$key];
        }

        foreach ($this->items as $section) {
            if (isset($section[$key])) {
                return $section[$key];
            }
        }

        return NULL;
    }
}

class CI_Lang_Shim
{
    public function load($file, $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '')
    {
        // No-op for tests; language loading is not required here.
    }
}

if (!function_exists('get_instance')) {
    function &get_instance()
    {
        static $CI;
        if (!$CI) {
            $CI = new CI_TestHarness();
        }
        return $CI;
    }
}

// Seed DataMapper global config with defaults when running in isolation
require_once APPPATH . 'libraries/datamapper.php';
if (empty(DataMapper::$config)) {
    DataMapper::$config = DataMapper::$_dmz_config_defaults;
}

// Ensure helper functions commonly used by the library are available
get_instance()->load->helper('inflector');

// Autoload support classes for tests
spl_autoload_register(function ($class) {
    $prefix = 'Tests\\';
    $baseDir = __DIR__ . '/';

    if (strpos($class, $prefix) === 0) {
        $relative = substr($class, strlen($prefix));
        $path = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

// Provide a lightweight PHPUnit shim when the library is not installed
if (!class_exists('PHPUnit\\Framework\\TestCase')) {
    require_once __DIR__ . '/Support/TestCaseShim.php';
    class_alias('Tests\\Support\\TestCaseShim', 'PHPUnit\\Framework\\TestCase');
}

if (!class_exists('DataMapper_Database_Exception')) {
    class DataMapper_Database_Exception extends \Exception {}
}
