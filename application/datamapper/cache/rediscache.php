<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * DataMapper cache driver backed by Redis.
 */

// Load interface
require_once(dirname(__FILE__) . '/cacheinterface.php');

if (!class_exists('DataMapper_Exception')) {
	class DataMapper_Exception extends \RuntimeException {}
}

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
	protected $redis_class = 'Redis';

	/**
	 * @var string Key prefix
	 */
	protected $prefix = 'dmz:';

	/** @var array|false Classes allowed when decoding cached values. */
	protected $allowed_classes = ['stdClass', 'DateTime', 'DateTimeImmutable'];
	
	/**
	 * @var array Cache statistics
	 */
	protected $stats = [
		'hits' => 0,
		'misses' => 0,
		'writes' => 0,
		'deletes' => 0
	];

	/** @var bool Whether the most recent pattern deletion encountered an error. */
	protected $last_pattern_delete_failed = FALSE;
	
	/**
	 * Constructor
	 *
	 * @param array $config Configuration options
	 *                      - host: Redis host (default: 127.0.0.1)
	 *                      - port: Redis port (default: 6379)
	 *                      - password: Redis password or [username, password] (optional)
	 *                      - database: Redis database number (default: 0)
	 *                      - prefix: Key prefix (default: 'dmz:')
	 *                      - timeout: Connection timeout (default: 2.5)
	 *                      - allowed_classes: Class-name allowlist, or FALSE to reject all objects
	 * @throws DataMapper_Exception If configuration, connection, authentication, or database selection fails
	 */
	public function __construct($config = [])
	{
		// Check if Redis extension is available
		if (!extension_loaded('redis')) {
			throw new DataMapper_Exception('Redis extension not loaded');
		}
		
		// Create Redis instance
		$this->redis = new $this->redis_class();
		
		// Set defaults
		$host = isset($config['host']) ? $config['host'] : '127.0.0.1';
		$port = isset($config['port']) ? $config['port'] : 6379;
		$timeout = isset($config['timeout']) ? $config['timeout'] : 2.5;
		if (array_key_exists('allowed_classes', $config)) {
			$allowed_classes = $config['allowed_classes'];
			if ($allowed_classes !== FALSE && (!is_array($allowed_classes) || array_filter($allowed_classes, static function ($class) {
				return !is_string($class) || $class === '';
			}))) {
				throw new DataMapper_Exception('Redis allowed_classes must be FALSE or an array of class names');
			}
			$this->allowed_classes = $allowed_classes;
		}
		
		// Connect to Redis
		$error = 'Redis connection failed';
		try {
			$connected = $this->redis->connect($host, $port, $timeout);
			
			if (!$connected) {
				throw new DataMapper_Exception("Failed to connect to Redis at $host:$port");
			}
			
			// Authenticate if password provided
			if (isset($config['password']) && $config['password'] !== '') {
				$error = 'Redis authentication failed';
				if (!$this->redis->auth($config['password'])) {
					throw new DataMapper_Exception($error);
				}
			}
			
			// Select database
			if (isset($config['database'])) {
				$error = 'Redis database selection failed';
				if (!$this->redis->select($config['database'])) {
					throw new DataMapper_Exception($error);
				}
			}
			
			// Set key prefix
			if (isset($config['prefix'])) {
				$this->prefix = $config['prefix'];
			}
			
		} catch (\Exception $e) {
			if ($e instanceof DataMapper_Exception) {
				throw $e;
			}
			// Client exception details can contain credentials and are logged by DataMapper.
			throw new DataMapper_Exception($error);
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
		return unserialize($value, ['allowed_classes' => $this->allowed_classes]);
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
		$result = $this->delete_pattern('*');
		
		return $result !== FALSE && !$this->last_pattern_delete_failed;
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
	public function delete_pattern($pattern)
	{
		$deleted = 0;
		$this->last_pattern_delete_failed = FALSE;
		$iterator = NULL;
		$this->redis->clearLastError();
		
		// Use SCAN to iterate through keys matching pattern
		do {
			$keys = $this->redis->scan($iterator, $this->prefix . $pattern, 100);
			if ($keys === FALSE) {
				if ($this->redis->getLastError() !== NULL) {
					$this->last_pattern_delete_failed = TRUE;
					break;
				}
				// An empty SCAN batch does not mean the cursor has finished.
				continue;
			}
			foreach ($keys as $key) {
				$result = $this->redis->del($key);
				if ($result === FALSE) {
					$this->last_pattern_delete_failed = TRUE;
					continue;
				}
				$deleted += $result;
			}
		} while ($iterator !== 0 && $iterator !== '0');
		
		return $deleted;
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache stats (hits, misses, memory, etc.)
	 */
	public function get_stats()
	{
		try {
			$info = $this->redis->info();
		} catch (\Exception $e) {
			$info = FALSE;
		}
		if (!is_array($info)) {
			return array_merge($this->stats, ['driver' => 'redis']);
		}
		
		// Count keys with our prefix
		$keyCount = 0;
		$iterator = NULL;
		$this->redis->clearLastError();
		do {
			$keys = $this->redis->scan($iterator, $this->prefix . '*', 1000);
			if ($keys === FALSE) {
				if ($this->redis->getLastError() !== NULL) {
					break;
				}
				continue;
			}
			$keyCount += count($keys);
		} while ($iterator !== 0 && $iterator !== '0');
		
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
	 * Increment a numeric cache value with a one-hour TTL.
	 *
	 * @param string $key Cache key
	 * @param int $offset Amount to increment by (default: 1)
	 * @return int|false New value after increment, or FALSE on failure
	 */
	public function increment($key, $offset = 1)
	{
		$key = $this->prefix . $key;
		if ($this->redis->multi() === FALSE) {
			return FALSE;
		}
		try {
			if ($this->redis->incrBy($key, $offset) === FALSE || $this->redis->expire($key, 3600) === FALSE) {
				$this->redis->discard();
				return FALSE;
			}
			$results = $this->redis->exec();
		} catch (\Throwable $e) {
			try {
				$this->redis->discard();
			} catch (\Throwable $discard_error) {
				// Keep the original command failure if cleanup is also denied.
			}
			throw $e;
		}
		return $results !== FALSE && $results[1] ? $results[0] : FALSE;
	}
	
	/**
	 * Decrement a numeric cache value with a one-hour TTL.
	 *
	 * @param string $key Cache key
	 * @param int $offset Amount to decrement by (default: 1)
	 * @return int|false New value after decrement, or FALSE on failure
	 */
	public function decrement($key, $offset = 1)
	{
		return $this->increment($key, -$offset);
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
		
		$values = $this->redis->mGet($prefixed);
		
		$result = [];
		foreach ($keys as $i => $key) {
			if ($values[$i] !== false) {
				$result[$key] = unserialize($values[$i], ['allowed_classes' => $this->allowed_classes]);
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
	public function set_multiple(array $items, $ttl = 3600)
	{
		$pipe = $this->redis->multi(constant($this->redis_class . '::PIPELINE'));
		
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
