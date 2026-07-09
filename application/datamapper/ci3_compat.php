<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter 3 Compatibility Layer for DataMapper 2.0
 * 
 * This file ensures DataMapper 2.0 works with:
 * - Native CodeIgniter 3.x (official bcit-ci/CodeIgniter)
 * - PocketArc CI3 Fork (pocketarc/codeigniter) for PHP 8.2+
 * - Any other CI3 maintenance forks
 * 
 * @package     DataMapper 2.0
 * @category    Compatibility
 * @author      DataMapper 2.0 Team
 * @license     MIT
 * @version     2.0.0
 */

/**
 * CI3 Database Compatibility Trait
 * 
 * Provides fallback methods for database operations that may differ
 * between CI3 versions. This trait should be used by DataMapper to
 * ensure compatibility across all CI3 variants.
 */
trait DMZ_CI3_Compat
{
    /**
     * Safe call to database protected methods
     * Attempts to use public API first, falls back to dm_call_method if needed
     * 
     * @param string $method Method name to call
     * @param array $args Arguments to pass
     * @param mixed $fallback Fallback value if method doesn't exist
     * @return mixed
     */
    protected function safe_db_call($method, array $args = [], $fallback = null)
    {
        try {
            // Try to call via dm_call_method
            return $this->db->dm_call_method($method, ...$args);
        } catch (BadMethodCallException $e) {
            // Method doesn't exist - return fallback
            if ($fallback !== null) {
                return $fallback;
            }
            throw $e;
        }
    }
    
    /**
     * Check if a database method exists
     * 
     * @param string $method Method name
     * @return bool
     */
    protected function db_method_exists($method)
    {
        return method_exists($this->db, $method);
    }
    
    /**
     * Get database driver version info
     * 
     * @return array Driver info
     */
    protected function get_db_driver_info()
    {
        static $info = null;
        
        if ($info === null) {
            $info = [
                'class' => get_class($this->db),
                'driver' => $this->db->dbdriver,
                'version' => $this->db->version(),
                'has_dm_methods' => method_exists($this->db, 'dm_call_method')
            ];
        }
        
        return $info;
    }
}

/**
 * DataMapper CI3 Compatibility Utilities
 */
class DMZ_CI3_Utils
{
    /**
     * Detect CodeIgniter version and variant
     * 
     * @return array Version information
     */
    public static function detect_ci_version()
    {
        $info = [
            'version' => CI_VERSION,
            'variant' => 'unknown',
            'is_pocketarc' => false,
            'is_native' => false
        ];
        
        // Check if it's the pocketarc fork (has PHP 8.2+ support)
        if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
            // If running on PHP 8.2+ and CI3 is working, likely pocketarc fork
            $info['is_pocketarc'] = true;
            $info['variant'] = 'pocketarc';
        } else {
            // Native CI3 or other fork
            $info['is_native'] = true;
            $info['variant'] = 'native';
        }
        
        // Try to detect from system path
        if (defined('SYSDIR')) {
            if (strpos(SYSDIR, 'pocketarc') !== false) {
                $info['is_pocketarc'] = true;
                $info['variant'] = 'pocketarc';
            }
        }
        
        return $info;
    }
    
    /**
     * Check if a database protected method is available
     * 
     * @param CI_DB_driver $db Database instance
     * @param string $method Method name
     * @return bool
     */
    public static function has_db_method($db, $method)
    {
        return method_exists($db, $method);
    }
    
    /**
     * Log compatibility information for debugging
     * 
     * @param string $message Message to log
     * @param string $level Log level
     */
    public static function log_compat($message, $level = 'debug')
    {
        if (function_exists('log_message')) {
            log_message($level, '[DataMapper 2.0 Compat] ' . $message);
        }
    }
}

/* End of file ci3_compat.php */
/* Location: ./application/datamapper/ci3_compat.php */
