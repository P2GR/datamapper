<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * DataMapper cache driver contract.
 */

/**
 * Standard interface for all DataMapper cache drivers.
 */
interface DMZ_CacheInterface
{
	/**
	 * Get a value from cache.
	 *
	 * @param string $key Cache key
	 * @return mixed|null Cached value or null if missing
	 */
	public function get($key);
	
	/**
	 * Store a value in cache.
	 *
	 * @param string $key Cache key
	 * @param mixed $value Value to cache
	 * @param int $ttl Time to live in seconds
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function set($key, $value, $ttl = 3600);
	
	/**
	 * Delete a cache entry.
	 *
	 * @param string $key Cache key
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function delete($key);
	
	/**
	 * Clear all cache entries.
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function flush();
	
	/**
	 * Check whether a cache key exists.
	 *
	 * @param string $key Cache key
	 * @return bool TRUE if present, FALSE otherwise
	 */
	public function has($key);
	
	/**
	 * Delete multiple cache keys matching a pattern.
	 *
	 * @param string $pattern Pattern to match (e.g., 'user:*')
	 * @return int Number of keys deleted
	 */
	public function delete_pattern($pattern);
	
	/**
	 * Get cache statistics.
	 *
	 * @return array Cache stats (hits, misses, size, etc.)
	 */
	public function get_stats();
}

/* End of file cacheinterface.php */
/* Location: ./application/datamapper/cache/cacheinterface.php */
