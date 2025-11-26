<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * DataMapper file-based cache driver.
 */

// Load interface
require_once(dirname(__FILE__) . '/cacheinterface.php');

/**
 * File-backed cache driver with automatic expiration and cleanup.
 */
class DMZ_FileCache implements DMZ_CacheInterface
{
	/**
	 * @var string Cache directory path
	 */
	protected $cache_dir;
	
	/**
	 * @var int File permissions for cache files
	 */
	protected $file_mode = 0640;
	
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
	 *                      - cache_dir: Directory for cache files
	 *                      - file_mode: Permissions for cache files
	 */
	public function __construct($config = [])
	{
		// Set cache directory
		$this->cache_dir = isset($config['cache_dir']) 
			? rtrim($config['cache_dir'], '/') 
			: APPPATH . 'cache/datamapper';
		
		// Set file permissions
		if (isset($config['file_mode'])) {
			$this->file_mode = $config['file_mode'];
		}
		
		// Create cache directory if it doesn't exist
		if (!is_dir($this->cache_dir)) {
			@mkdir($this->cache_dir, 0755, true);
		}
		
		// Verify directory is writable
		if (!is_writable($this->cache_dir)) {
			dmz_log_message('error', 'DataMapper File Cache: Cache directory is not writable: ' . $this->cache_dir);
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
		$file = $this->get_file_path($key);
		
		if (!file_exists($file)) {
			$this->stats['misses']++;
			return null;
		}
		
		$data = @file_get_contents($file);
		
		if ($data === false) {
			$this->stats['misses']++;
			return null;
		}
		
		$data = unserialize($data);
		
		// Check expiration
		if ($data['expires'] < time()) {
			$this->delete($key);
			$this->stats['misses']++;
			return null;
		}
		
		$this->stats['hits']++;
		return $data['value'];
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
		$file = $this->get_file_path($key);
		
		$data = [
			'expires' => time() + $ttl,
			'value' => $value
		];
		
		$result = @file_put_contents($file, serialize($data), LOCK_EX);
		
		if ($result !== false) {
			@chmod($file, $this->file_mode);
			$this->stats['writes']++;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Delete item from cache
	 *
	 * @param string $key Cache key
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function delete($key)
	{
		$file = $this->get_file_path($key);
		
		if (file_exists($file)) {
			$this->stats['deletes']++;
			return @unlink($file);
		}
		
		return true;
	}
	
	/**
	 * Clear all cache entries
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function flush()
	{
		$files = glob($this->cache_dir . '/*');
		
		if ($files === false) {
			return false;
		}
		
		foreach ($files as $file) {
			if (is_file($file)) {
				@unlink($file);
			}
		}
		
		return true;
	}
	
	/**
	 * Check if cache key exists and is not expired
	 *
	 * @param string $key Cache key
	 * @return bool TRUE if exists, FALSE otherwise
	 */
	public function has($key)
	{
		return $this->get($key) !== null;
	}
	
	/**
	 * Delete multiple cache keys matching pattern
	 *
	 * @param string $pattern Pattern to match (e.g., 'user:*')
	 * @return int Number of keys deleted
	 */
	public function delete_pattern($pattern)
	{
		// Convert pattern to filesystem glob
		$pattern = str_replace(':', '_', $pattern);
		$pattern = str_replace('*', '*', $pattern);
		
		$files = glob($this->cache_dir . '/' . $pattern);
		
		if ($files === false) {
			return 0;
		}
		
		$deleted = 0;
		foreach ($files as $file) {
			if (is_file($file) && @unlink($file)) {
				$deleted++;
			}
		}
		
		return $deleted;
	}

	public function deletePattern($pattern)
	{
		return $this->delete_pattern($pattern);
	}
	
	/**
	 * Get cache statistics
	 *
	 * @return array Cache stats (hits, misses, size, etc.)
	 */
	public function get_stats()
	{
		$files = glob($this->cache_dir . '/*');
		$size = 0;
		$count = 0;
		
		if ($files !== false) {
			foreach ($files as $file) {
				if (is_file($file)) {
					$size += filesize($file);
					$count++;
				}
			}
		}
		
		return array_merge($this->stats, [
			'entries' => $count,
			'size' => $size,
			'size_human' => $this->format_bytes($size),
			'driver' => 'file',
			'cache_dir' => $this->cache_dir
		]);
	}

	public function getStats()
	{
		return $this->get_stats();
	}
	
	/**
	 * Clean up expired cache entries
	 *
	 * @return int Number of entries deleted
	 */
	public function clean_expired()
	{
		$files = glob($this->cache_dir . '/*');
		
		if ($files === false) {
			return 0;
		}
		
		$deleted = 0;
		foreach ($files as $file) {
			if (!is_file($file)) {
				continue;
			}
			
			$data = @file_get_contents($file);
			if ($data === false) {
				continue;
			}
			
			$data = @unserialize($data);
			if ($data === false || !isset($data['expires'])) {
				continue;
			}
			
			// Delete if expired
			if ($data['expires'] < time()) {
				@unlink($file);
				$deleted++;
			}
		}
		
		return $deleted;
	}

	public function cleanExpired()
	{
		return $this->clean_expired();
	}
	
	/**
	 * Get file path for cache key
	 *
	 * @param string $key Cache key
	 * @return string File path
	 */
	protected function get_file_path($key)
	{
		// Sanitize key for filename
		$safe_key = preg_replace('/[^a-z0-9_\-]/i', '_', $key);
		return $this->cache_dir . '/' . $safe_key;
	}

	protected function getFilePath($key)
	{
		return $this->get_file_path($key);
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

/* End of file filecache.php */
/* Location: ./application/datamapper/cache/filecache.php */
