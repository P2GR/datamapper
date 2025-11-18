<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * DataMapper Lazy Collection
 *
 * Provides chunked, memory-friendly iteration with fluent helpers.
 */

/**
 * Lazy evaluation wrapper for DataMapper queries with chainable operations.
 */
class DMZ_LazyCollection implements IteratorAggregate
{
	/**
	 * @var DataMapper Original DataMapper query
	 */
	protected $query;
	
	/**
	 * @var int Chunk size for fetching
	 */
	protected $chunkSize;
	
	/**
	 * @var array Operations to apply to items
	 */
	protected $operations = [];
	
	/**
	 * Constructor
	 *
	 * @param DataMapper $query The DataMapper query object
	 * @param int $chunkSize Chunk size for fetching (default: 1000)
	 */
	public function __construct($query, $chunkSize = 1000)
	{
		$this->query = $query;
		$this->chunkSize = $chunkSize;
	}
	
	/**
	 * Map operation - apply a transformation to each item
	 *
	 * Example:
	 *   $lazy->map(function($user) { return strtoupper($user->name); })
	 *
	 * @param callable $callback Transformation function
	 * @return DMZ_LazyCollection For chaining
	 */
	public function map(callable $callback)
	{
		$this->operations[] = ['type' => 'map', 'callback' => $callback];
		return $this;
	}
	
	/**
	 * Filter operation - keep only items matching condition
	 *
	 * Example:
	 *   $lazy->filter(function($user) { return $user->age > 18; })
	 *
	 * @param callable $callback Filter function (return true to keep)
	 * @return DMZ_LazyCollection For chaining
	 */
	public function filter(callable $callback)
	{
		$this->operations[] = ['type' => 'filter', 'callback' => $callback];
		return $this;
	}
	
	/**
	 * Take operation - limit the number of results
	 *
	 * Example:
	 *   $lazy->take(100)
	 *
	 * @param int $limit Maximum items to return
	 * @return DMZ_LazyCollection For chaining
	 */
	public function take($limit)
	{
		$this->operations[] = ['type' => 'take', 'limit' => $limit];
		return $this;
	}
	
	/**
	 * Skip operation - skip a number of results
	 *
	 * Example:
	 *   $lazy->skip(50)
	 *
	 * @param int $count Number of items to skip
	 * @return DMZ_LazyCollection For chaining
	 */
	public function skip($count)
	{
		$this->operations[] = ['type' => 'skip', 'count' => $count];
		return $this;
	}
	
	/**
	 * Pluck operation - extract a single field from each item
	 *
	 * Example:
	 *   $lazy->pluck('email')
	 *
	 * @param string $field Field name to extract
	 * @return DMZ_LazyCollection For chaining
	 */
	public function pluck($field)
	{
		$this->operations[] = ['type' => 'pluck', 'field' => $field];
		return $this;
	}
	
	/**
	 * Unique operation - remove duplicate items
	 *
	 * Example:
	 *   $lazy->unique()
	 *
	 * @param string|null $key Optional field to determine uniqueness
	 * @return DMZ_LazyCollection For chaining
	 */
	public function unique($key = null)
	{
		$this->operations[] = ['type' => 'unique', 'key' => $key];
		return $this;
	}
	
	/**
	 * Get the iterator for lazy evaluation
	 *
	 * @return Generator
	 */
	public function getIterator(): Traversable
	{
		$offset = 0;
		$taken = 0;
		$skipped = 0;
		$seenKeys = [];
		
		// Extract take/skip limits
		$takeLimit = null;
		$skipCount = 0;
		
		foreach ($this->operations as $op) {
			if ($op['type'] === 'take' && $takeLimit === null) {
				$takeLimit = $op['limit'];
			}
			if ($op['type'] === 'skip') {
				$skipCount += $op['count'];
			}
		}
		
		while (true) {
			// Stop if we've taken enough items
			if ($takeLimit !== null && $taken >= $takeLimit) {
				break;
			}
			
			// Clone query for this chunk
			$chunk_query = clone $this->query;
			
			// Fetch chunk
			$chunk_query->limit($this->chunkSize, $offset)->get();
			
			// Stop if no results
			if (empty($chunk_query->all)) {
				break;
			}
			
			// Process each item in chunk
			foreach ($chunk_query->all as $item) {
				// Stop if we've taken enough
				if ($takeLimit !== null && $taken >= $takeLimit) {
					break 2;
				}
				
				// Apply all operations
				$processed = $this->applyOperations($item, $seenKeys);
				
				// Skip if filtered out
				if ($processed === null) {
					continue;
				}
				
				// Handle skip operation
				if ($skipped < $skipCount) {
					$skipped++;
					continue;
				}
				
				// Yield the processed item
				yield $processed;
				$taken++;
			}
			
			// Stop if we got less than chunkSize (last chunk)
			if (count($chunk_query->all) < $this->chunkSize) {
				break;
			}
			
			// Move to next chunk
			$offset += $this->chunkSize;
			
			// Clear memory
			$chunk_query = null;
		}
	}
	
	/**
	 * Apply all queued operations to an item
	 *
	 * @param mixed $item The item to process
	 * @param array &$seenKeys For tracking unique items
	 * @return mixed|null Processed item or null if filtered out
	 */
	protected function applyOperations($item, &$seenKeys)
	{
		$result = $item;
		
		foreach ($this->operations as $op) {
			switch ($op['type']) {
				case 'map':
					$result = call_user_func($op['callback'], $result);
					break;
					
				case 'filter':
					if (!call_user_func($op['callback'], $result)) {
						return null; // Filtered out
					}
					break;
					
				case 'pluck':
					$field = $op['field'];
					if (is_object($result)) {
						$result = isset($result->{$field}) ? $result->{$field} : null;
					} elseif (is_array($result)) {
						$result = isset($result[$field]) ? $result[$field] : null;
					}
					break;
					
				case 'unique':
					$key = $op['key'];
					$uniqueValue = $key === null ? serialize($result) : (
						is_object($result) ? (isset($result->{$key}) ? $result->{$key} : null) :
						(is_array($result) ? (isset($result[$key]) ? $result[$key] : null) : null)
					);
					
					if (isset($seenKeys[$uniqueValue])) {
						return null; // Duplicate
					}
					$seenKeys[$uniqueValue] = true;
					break;
					
				// take and skip are handled in getIterator
				case 'take':
				case 'skip':
					break;
			}
		}
		
		return $result;
	}
	
	/**
	 * Convert lazy collection to array (forces evaluation)
	 *
	 * WARNING: This loads all results into memory!
	 *
	 * @return array
	 */
	public function toArray()
	{
		return iterator_to_array($this->getIterator(), false);
	}
	
	/**
	 * Count items (forces evaluation)
	 *
	 * WARNING: This processes all results!
	 *
	 * @return int
	 */
	public function count()
	{
		$count = 0;
		foreach ($this as $item) {
			$count++;
		}
		return $count;
	}
	
	/**
	 * Get first item (efficient - only fetches first chunk)
	 *
	 * @return mixed|null
	 */
	public function first()
	{
		foreach ($this as $item) {
			return $item;
		}
		return null;
	}
	
	/**
	 * Execute a callback for each item (forces evaluation)
	 *
	 * Example:
	 *   $lazy->each(function($user) { echo $user->name; })
	 *
	 * @param callable $callback Function to execute
	 * @return void
	 */
	public function each(callable $callback)
	{
		foreach ($this as $item) {
			call_user_func($callback, $item);
		}
	}
	
	/**
	 * Check if any items match condition (short-circuits on first match)
	 *
	 * Example:
	 *   $hasAdmin = $lazy->contains(function($user) { return $user->role === 'admin'; })
	 *
	 * @param callable $callback Test function
	 * @return bool
	 */
	public function contains(callable $callback)
	{
		foreach ($this as $item) {
			if (call_user_func($callback, $item)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Reduce collection to single value (forces evaluation)
	 *
	 * Example:
	 *   $total = $lazy->reduce(function($carry, $user) { return $carry + $user->points; }, 0)
	 *
	 * @param callable $callback Reducer function
	 * @param mixed $initial Initial value
	 * @return mixed
	 */
	public function reduce(callable $callback, $initial = null)
	{
		$carry = $initial;
		foreach ($this as $item) {
			$carry = call_user_func($callback, $carry, $item);
		}
		return $carry;
	}
	
	/**
	 * Chunk the lazy collection into smaller collections
	 *
	 * Example:
	 *   $lazy->chunk(100)->each(function($chunk) { process($chunk); })
	 *
	 * @param int $size Chunk size
	 * @return Generator Yields arrays of items
	 */
	public function chunk($size)
	{
		$chunk = [];
		foreach ($this as $item) {
			$chunk[] = $item;
			if (count($chunk) >= $size) {
				yield $chunk;
				$chunk = [];
			}
		}
		
		// Yield remaining items
		if (!empty($chunk)) {
			yield $chunk;
		}
	}
}

/* End of file lazycollection.php */
/* Location: ./application/datamapper/lazycollection.php */
