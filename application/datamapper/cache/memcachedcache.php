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
	 * @throws Exception If Memcached extension not available or connection fails
	 */
	public function __construct($config = [])
	{
		// Check if Memcached extension is available
		if (!extension_loaded('memcached')) {
			throw new Exception('Memcached extension not loaded');
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
			throw new Exception('Failed to connect to Memcached servers');
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
		$value = $this->memcached->get($this->prefix . $key);
		
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
			$this->prefix . $key,
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
		$result = $this->memcached->delete($this->prefix . $key);
		
		if ($result || $this->memcached->getResultCode() === constant($this->memcached_class . '::RES_NOTFOUND')) {
			$this->stats['deletes']++;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Clear all cache entries
	 *
	 * NOTE: This flushes the ENTIRE Memcached server, not just our keys!
	 * Use with caution in shared environments.
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function flush()
	{
		return $this->memcached->flush();
	}
	
	/**
	 * Check if cache key exists and is not expired
	 *
	 * @param string $key Cache key
	 * @return bool TRUE if exists, FALSE otherwise
	 */
	public function has($key)
	{
		$this->memcached->get($this->prefix . $key);
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
		// Memcached doesn't support pattern deletion natively
		// We would need to maintain a separate index of keys
		// For now, return 0 and log a warning
		
		dmz_log_message('warning', 'DataMapper Memcached Cache: Pattern deletion not supported. Consider using Redis for this feature.');
		
		return 0;
	}

	public function deletePattern($pattern)
	{
		return $this->delete_pattern($pattern);
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

	public function getStats()
	{
		return $this->get_stats();
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
		return $this->memcached->increment($this->prefix . $key, $offset);
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
		return $this->memcached->decrement($this->prefix . $key, $offset);
	}
	
	/**
	 * Get multiple cache items at once
	 *
	 * @param array $keys Array of cache keys
	 * @return array Associative array of key => value pairs
	 */
	public function get_multiple(array $keys)
	{
		$prefixed = array_map(function($key) {
			return $this->prefix . $key;
		}, $keys);
		
		$values = $this->memcached->getMulti($prefixed);
		
		if ($values === false) {
			return [];
		}
		
		// Remove prefix from keys
		$result = [];
		foreach ($values as $key => $value) {
			$original_key = str_replace($this->prefix, '', $key);
			$result[$original_key] = $value;
			$this->stats['hits']++;
		}
		
		// Count misses
		$misses = count($keys) - count($result);
		$this->stats['misses'] += $misses;
		
		return $result;
	}

	public function getMultiple(array $keys)
	{
		return $this->get_multiple($keys);
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
			$prefixed[$this->prefix . $key] = $value;
		}
		
		$result = $this->memcached->setMulti($prefixed, time() + $ttl);
		
		if ($result) {
			$this->stats['writes'] += count($items);
		}
		
		return $result;
	}

	public function setMultiple(array $items, $ttl = 3600)
	{
		return $this->set_multiple($items, $ttl);
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

	protected function formatBytes($bytes)
	{
		return $this->format_bytes($bytes);
	}
}

/* End of file memcachedcache.php */
/* Location: ./application/datamapper/cache/memcachedcache.php */
