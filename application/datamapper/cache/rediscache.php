<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * DataMapper cache driver backed by Redis.
 */

// Load interface
require_once(dirname(__FILE__) . '/cacheinterface.php');

/**
 * Redis implementation of the DataMapper cache interface.
 */
class DMZ_RedisCache implements DMZ_CacheInterface
{
	/**
	 * @var object Redis instance
	 */
	protected $redis;

	/**
	 * @var string FQCN for the Redis client
	 */
	protected $redisClass = 'Redis';

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
	 *                      - host: Redis host (default: 127.0.0.1)
	 *                      - port: Redis port (default: 6379)
	 *                      - password: Redis password (optional)
	 *                      - database: Redis database number (default: 0)
	 *                      - prefix: Key prefix (default: 'dmz:')
	 *                      - timeout: Connection timeout (default: 2.5)
	 * @throws Exception If Redis extension not available or connection fails
	 */
	public function __construct($config = [])
	{
		// Check if Redis extension is available
		if (!extension_loaded('redis')) {
			throw new Exception('Redis extension not loaded');
		}
		
		// Create Redis instance
		$this->redis = new $this->redisClass();
		
		// Set defaults
		$host = isset($config['host']) ? $config['host'] : '127.0.0.1';
		$port = isset($config['port']) ? $config['port'] : 6379;
		$timeout = isset($config['timeout']) ? $config['timeout'] : 2.5;
		
		// Connect to Redis
		try {
			$connected = $this->redis->connect($host, $port, $timeout);
			
			if (!$connected) {
				throw new Exception("Failed to connect to Redis at $host:$port");
			}
			
			// Authenticate if password provided
			if (isset($config['password']) && $config['password'] !== '') {
				$this->redis->auth($config['password']);
			}
			
			// Select database
			if (isset($config['database'])) {
				$this->redis->select($config['database']);
			}
			
			// Set key prefix
			if (isset($config['prefix'])) {
				$this->prefix = $config['prefix'];
			}
			
		} catch (Exception $e) {
			throw new Exception('Redis connection error: ' . $e->getMessage());
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
		$value = $this->redis->get($this->prefix . $key);
		
		if ($value === false) {
			$this->stats['misses']++;
			return null;
		}
		
		$this->stats['hits']++;
		return unserialize($value);
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
		$result = $this->redis->setex(
			$this->prefix . $key,
			$ttl,
			serialize($value)
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
		$result = $this->redis->del($this->prefix . $key);
		
		if ($result > 0) {
			$this->stats['deletes']++;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Clear all cache entries
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function flush()
	{
		// Only flush keys with our prefix
		$pattern = $this->prefix . '*';
		$deleted = $this->deletePattern($pattern);
		
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
		return $this->redis->exists($this->prefix . $key);
	}
	
	/**
	 * Delete multiple cache keys matching pattern
	 *
	 * Uses SCAN for safe pattern deletion (doesn't block Redis)
	 *
	 * @param string $pattern Pattern to match (e.g., 'user:*')
	 * @return int Number of keys deleted
	 */
	public function deletePattern($pattern)
	{
		$deleted = 0;
		$iterator = null;
		
		// Use SCAN to iterate through keys matching pattern
		while ($keys = $this->redis->scan($iterator, $this->prefix . $pattern, 100)) {
			foreach ($keys as $key) {
				$this->redis->del($key);
				$deleted++;
			}
		}
		
		return $deleted;
	}
	
	/**
	 * Get cache statistics
	 *
	 * @return array Cache stats (hits, misses, memory, etc.)
	 */
	public function getStats()
	{
		$info = $this->redis->info();
		
		// Count keys with our prefix
		$keyCount = 0;
		$iterator = null;
		while ($keys = $this->redis->scan($iterator, $this->prefix . '*', 1000)) {
			$keyCount += count($keys);
		}
		
		return array_merge($this->stats, [
			'entries' => $keyCount,
			'memory_used' => isset($info['used_memory']) ? $info['used_memory'] : 0,
			'memory_human' => isset($info['used_memory_human']) ? $info['used_memory_human'] : 'N/A',
			'driver' => 'redis',
			'version' => isset($info['redis_version']) ? $info['redis_version'] : 'unknown',
			'connected_clients' => isset($info['connected_clients']) ? $info['connected_clients'] : 0
		]);
	}
	
	/**
	 * Increment a numeric cache value
	 *
	 * @param string $key Cache key
	 * @param int $offset Amount to increment by (default: 1)
	 * @return int New value after increment
	 */
	public function increment($key, $offset = 1)
	{
		return $this->redis->incrBy($this->prefix . $key, $offset);
	}
	
	/**
	 * Decrement a numeric cache value
	 *
	 * @param string $key Cache key
	 * @param int $offset Amount to decrement by (default: 1)
	 * @return int New value after decrement
	 */
	public function decrement($key, $offset = 1)
	{
		return $this->redis->decrBy($this->prefix . $key, $offset);
	}
	
	/**
	 * Get multiple cache items at once
	 *
	 * @param array $keys Array of cache keys
	 * @return array Associative array of key => value pairs
	 */
	public function getMultiple(array $keys)
	{
		$prefixed = array_map(function($key) {
			return $this->prefix . $key;
		}, $keys);
		
		$values = $this->redis->mGet($prefixed);
		
		$result = [];
		foreach ($keys as $i => $key) {
			if ($values[$i] !== false) {
				$result[$key] = unserialize($values[$i]);
				$this->stats['hits']++;
			} else {
				$this->stats['misses']++;
			}
		}
		
		return $result;
	}
	
	/**
	 * Set multiple cache items at once
	 *
	 * @param array $items Associative array of key => value pairs
	 * @param int $ttl Time to live in seconds
	 * @return bool TRUE on success
	 */
	public function setMultiple(array $items, $ttl = 3600)
	{
		$pipe = $this->redis->multi(constant($this->redisClass . '::PIPELINE'));
		
		foreach ($items as $key => $value) {
			$pipe->setex(
				$this->prefix . $key,
				$ttl,
				serialize($value)
			);
		}
		
		$results = $pipe->exec();
		$this->stats['writes'] += count($items);
		
		return !in_array(false, $results, true);
	}
	
	/**
	 * Close Redis connection
	 */
	public function close()
	{
		if ($this->redis) {
			$this->redis->close();
		}
	}
	
	/**
	 * Destructor - close connection
	 */
	public function __destruct()
	{
		$this->close();
	}
}

/* End of file rediscache.php */
/* Location: ./application/datamapper/cache/rediscache.php */
