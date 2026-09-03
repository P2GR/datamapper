<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('dmz_log_message')) {
	function dmz_log_message($level, $message, array $context = array())
	{
		if (!function_exists('log_message')) {
			return;
		}

		if (!is_string($message)) {
			$message = print_r($message, TRUE);
		}

		if (!empty($context)) {
			$context_json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			if ($context_json !== FALSE) {
				$message .= ' | context=' . $context_json;
			}
		}

		call_user_func('log_message', $level, '[DataMapper] ' . $message);
	}
}

if (!class_exists('DataMapper_Exception')) {
	class DataMapper_Exception extends \RuntimeException {}
}

/**
 * DataMapper cache driver backed by Memcached.
 */

// Load interface
require_once(dirname(__FILE__) . '/cacheinterface.php');

/**
 * Memcached implementation of the DataMapper cache interface.
 */
class DMZ_MemcachedCache implements DMZ_CacheInterface
{
	/**
	 * @var object Memcached instance
	 */
	protected $memcached;
	
	/**
	 * @var string FQCN for the Memcached client
	 */
	protected $memcached_class = 'Memcached';

	/**
	 * @var string Key prefix
	 */
	protected $prefix = 'dmz:';

	/**
	 * @var array Cache statistics
	 */
	protected $stats = [
		'hits' => 0,
		'misses' => 0,
		'writes' => 0,
		'deletes' => 0
	];
	
	/**
	 * Constructor
	 *
	 * @param array $config Configuration options
	 *                      - servers: Array of server configs [host, port, weight], ...]
	 *                      - prefix: Key prefix (default: 'dmz:')
	 *                      - compression: Enable compression (default: true)
	 *                      - persistent_id: Persistent connection ID (optional)
	 * @throws DataMapper_Exception If Memcached extension not available or connection fails
	 */
	public function __construct($config = [])
	{
		// Check if Memcached extension is available
		if (!extension_loaded('memcached')) {
			throw new DataMapper_Exception('Memcached extension not loaded');
		}
		
		// Create Memcached instance
		$persistent_id = isset($config['persistent_id']) ? $config['persistent_id'] : null;
		$this->memcached = new $this->memcached_class($persistent_id);
		
		// Set prefix
		if (isset($config['prefix'])) {
			$this->prefix = $config['prefix'];
		}
		
		// Set options
		$this->memcached->setOption(constant($this->memcached_class . '::OPT_BINARY_PROTOCOL'), true);
		$this->memcached->setOption(constant($this->memcached_class . '::OPT_LIBKETAMA_COMPATIBLE'), true);
		
		// Enable compression by default
		$compression = isset($config['compression']) ? $config['compression'] : true;
		$this->memcached->setOption(constant($this->memcached_class . '::OPT_COMPRESSION'), $compression);
		
		// Add servers
		$servers = isset($config['servers']) ? $config['servers'] : [
			['127.0.0.1', 11211, 100] // Default server
		];
		
		// Only add servers if not using persistent connection or servers list is empty
		if (!$persistent_id || count($this->memcached->getServerList()) === 0) {
			$this->memcached->addServers($servers);
		}
		
		// Test connection
		$stats = $this->memcached->getStats();
		if (empty($stats) || !is_array($stats)) {
			throw new DataMapper_Exception('Failed to connect to Memcached servers');
		}
	}
	
	/**
	 * Get item from cache
	 *
	 * @param string $key Cache key
	 * @return mixed|null Cached value or null if not found/expired
	 */
	public function get($key)
	{
		$value = $this->memcached->get($this->physical_key($key));
		
		if ($this->memcached->getResultCode() === constant($this->memcached_class . '::RES_NOTFOUND')) {
			$this->stats['misses']++;
			return null;
		}
		
		if ($this->memcached->getResultCode() !== constant($this->memcached_class . '::RES_SUCCESS')) {
			$this->stats['misses']++;
			return null;
		}
		
		$this->stats['hits']++;
		return $value;
	}
	
	/**
	 * Store item in cache
	 *
	 * @param string $key Cache key
	 * @param mixed $value Value to cache
	 * @param int $ttl Time to live in seconds
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function set($key, $value, $ttl = 3600)
	{
		$result = $this->memcached->set(
			$this->physical_key($key),
			$value,
			time() + $ttl
		);
		
		if ($result) {
			$this->stats['writes']++;
		}
		
		return $result;
	}
	
	/**
	 * Delete item from cache
	 *
	 * @param string $key Cache key
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function delete($key)
	{
		$result = $this->memcached->delete($this->physical_key($key));
		
		if ($result || $this->memcached->getResultCode() === constant($this->memcached_class . '::RES_NOTFOUND')) {
			$this->stats['deletes']++;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Clear all DataMapper cache entries.
	 *
	 * Uses namespace generations so shared Memcached data is preserved.
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function flush()
	{
		// Invalidate DataMapper's namespace without flushing shared Memcached data.
		$generation = $this->memcached->increment($this->global_generation_key(), 1, 2);
		if ($generation === FALSE) {
			return FALSE;
		}

		$this->stats['deletes']++;
		return TRUE;
	}
	
	/**
	 * Check if cache key exists and is not expired
	 *
	 * @param string $key Cache key
	 * @return bool TRUE if exists, FALSE otherwise
	 */
	public function has($key)
	{
		$this->memcached->get($this->physical_key($key));
		return $this->memcached->getResultCode() === constant($this->memcached_class . '::RES_SUCCESS');
	}
	
	/**
	 * Delete multiple cache keys matching pattern
	 *
	 * NOTE: Memcached doesn't support pattern matching natively.
	 * This requires maintaining a key index.
	 *
	 * @param string $pattern Pattern to match (e.g., 'user:*')
	 * @return int Number of keys deleted
	 */
	public function delete_pattern($pattern)
	{
		$supported = ($pattern === 'relation-query:*' || preg_match('/^query:[^:*?\[]+:\*$/', $pattern));
		if (!$supported) {
			dmz_log_message('warning', 'DataMapper Memcached Cache: pattern is outside a supported cache namespace.');
			return 0;
		}

		$namespace = substr($pattern, 0, -1);
		$generation = $this->memcached->increment($this->generation_key($namespace), 1, 2);
		if ($generation === FALSE) {
			return 0;
		}

		$this->stats['deletes']++;
		return 1;
	}

	protected function physical_key($key)
	{
		$global_generation = $this->read_generation($this->global_generation_key());
		$namespace = $this->cache_namespace($key);
		$generation = $this->read_generation($this->generation_key($namespace));
		return $this->prefix . $global_generation . ':' . $generation . ':' . $key;
	}

	protected function read_generation($generation_key)
	{
		$generation = $this->memcached->get($generation_key);
		if (!is_int($generation)) {
			$this->memcached->add($generation_key, 1, 0);
			$generation = $this->memcached->get($generation_key);
			if (!is_int($generation)) {
				$generation = 1;
			}
		}
		return $generation;
	}

	protected function cache_namespace($key)
	{
		if (strpos($key, 'relation-query:') === 0) {
			return 'relation-query:';
		}
		if (strpos($key, 'query:') === 0) {
			$parts = explode(':', $key, 3);
			return count($parts) > 1 ? 'query:' . $parts[1] . ':' : 'query:';
		}
		return '';
	}

	protected function generation_key($namespace)
	{
		return $this->prefix . '__generation:' . md5($namespace);
	}

	protected function global_generation_key()
	{
		return $this->prefix . '__generation:global';
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache stats (hits, misses, memory, etc.)
	 */
	public function get_stats()
	{
		$server_stats = $this->memcached->getStats();
		
		// Aggregate stats from all servers
		$total_items = 0;
		$total_size = 0;
		$uptime = 0;
		
		foreach ($server_stats as $server => $stats) {
			if (isset($stats['curr_items'])) {
				$total_items += $stats['curr_items'];
			}
			if (isset($stats['bytes'])) {
				$total_size += $stats['bytes'];
			}
			if (isset($stats['uptime'])) {
				$uptime = max($uptime, $stats['uptime']);
			}
		}
		
		return array_merge($this->stats, [
			'entries' => $total_items,
			'memory_used' => $total_size,
			'memory_human' => $this->format_bytes($total_size),
			'driver' => 'memcached',
			'version' => $this->memcached->getVersion(),
			'servers' => count($server_stats),
			'uptime' => $uptime
		]);
	}

	/**
	 * Increment a numeric cache value
	 *
	 * @param string $key Cache key
	 * @param int $offset Amount to increment by (default: 1)
	 * @return int|false New value after increment, or FALSE on failure
	 */
	public function increment($key, $offset = 1)
	{
		return $this->memcached->increment($this->physical_key($key), $offset);
	}
	
	/**
	 * Decrement a numeric cache value
	 *
	 * @param string $key Cache key
	 * @param int $offset Amount to decrement by (default: 1)
	 * @return int|false New value after decrement, or FALSE on failure
	 */
	public function decrement($key, $offset = 1)
	{
		return $this->memcached->decrement($this->physical_key($key), $offset);
	}
	
	/**
	 * Get multiple cache items at once
	 *
	 * @param array $keys Array of cache keys
	 * @return array Associative array of key => value pairs
	 */
	public function get_multiple(array $keys)
	{
		$physical_keys = array();
		$physical_to_original = array();
		foreach ($keys as $key) {
			$physical_key = $this->physical_key($key);
			$physical_keys[] = $physical_key;
			$physical_to_original[$physical_key] = $key;
		}
		
		$values = $this->memcached->getMulti($physical_keys);
		
		if ($values === false) {
			return [];
		}
		
		// Restore caller-facing keys from their physical Memcached keys.
		$result = [];
		foreach ($values as $physical_key => $value) {
			if (!array_key_exists($physical_key, $physical_to_original)) {
				continue;
			}
			$original_key = $physical_to_original[$physical_key];
			$result[$original_key] = $value;
			$this->stats['hits']++;
		}
		
		// Count misses
		$misses = count($keys) - count($result);
		$this->stats['misses'] += $misses;
		
		return $result;
	}

	/**
	 * Set multiple cache items at once
	 *
	 * @param array $items Associative array of key => value pairs
	 * @param int $ttl Time to live in seconds
	 * @return bool TRUE on success
	 */
	public function set_multiple(array $items, $ttl = 3600)
	{
		$prefixed = [];
		foreach ($items as $key => $value) {
			$prefixed[$this->physical_key($key)] = $value;
		}
		
		$result = $this->memcached->setMulti($prefixed, time() + $ttl);
		
		if ($result) {
			$this->stats['writes'] += count($items);
		}
		
		return $result;
	}

	/**
	 * Format bytes to human-readable format
	 *
	 * @param int $bytes Bytes
	 * @return string Formatted size
	 */
	protected function format_bytes($bytes)
	{
		$units = ['B', 'KB', 'MB', 'GB'];
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		
		return round($bytes, 2) . ' ' . $units[$pow];
	}

}

/* End of file memcachedcache.php */
/* Location: ./application/datamapper/cache/memcachedcache.php */
