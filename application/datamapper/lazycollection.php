<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * DataMapper Lazy Collection
 *
 * Provides chunked, memory-friendly iteration with query builder-style helpers.
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
	protected $chunk_size;

	/**
	 * @deprecated Temporary camelCase alias for backward compatibility.
	 * @var int
	 */
	protected $chunkSize;

	/** @var string|null Key column used for keyset iteration. */
	protected $keyset_column;
	
	/**
	 * @var array Operations to apply to items
	 */
	protected $operations = [];
	protected $eager_loader;

	public function _with_eager_loader(callable $loader)
	{
		$this->eager_loader = $loader;
		return $this;
	}
	
	/**
	 * Constructor
	 *
	 * @param DataMapper $query The DataMapper query object
	 * @param int $chunkSize Chunk size for fetching (default: 1000)
	 */
	public function __construct($query, $chunk_size = 1000, $keyset_column = NULL)
	{
		$this->query = $query;
		$this->chunk_size = $chunk_size;
		$this->chunkSize =& $this->chunk_size;
		$this->keyset_column = $keyset_column;
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
		if ($this->keyset_column !== NULL && method_exists($this->query->db, 'dm_get')
			&& (!empty($this->query->db->dm_get('qb_orderby')) || !empty($this->query->db->dm_get('qb_cache_orderby')))) {
			throw new InvalidArgumentException('lazy_by_id() requires a query without pre-existing ordering.');
		}

		$offset = 0;
		$last_key = NULL;
		$seen_keys = [];
		foreach ($this->operations as $op) {
			if ($op['type'] === 'take' && $op['limit'] <= 0) {
				return;
			}
		}
		
		while (true) {
			// Clone both model and mutable CI query-builder state for this chunk.
			$chunk_query = $this->query->get_clone(TRUE)->no_cache();
			// Freeze cached clauses before wrapping the cursor predicate's conditions.
			if (method_exists($chunk_query->db, 'flush_cache')) {
				$chunk_query->db->dm_call_method('_merge_cache');
				$chunk_query->db->stop_cache()->flush_cache();
			}
			
			// Fetch chunk
			if ($this->keyset_column !== NULL) {
				if ($last_key !== NULL) {
					DataMapper::_group_query_conditions($chunk_query->db);
					$chunk_query->where($this->keyset_column . ' >', $last_key);
				}
				$chunk_query->order_by($this->keyset_column, 'ASC')->limit($this->chunk_size)->get();
			} else {
				$chunk_query->limit($this->chunk_size, $offset)->get();
			}
			if (!$chunk_query->_query_was_successful()) {
				throw new DataMapper_Database_Exception('Query failed during lazy iteration.');
			}
			
			// Stop if no results
			if (empty($chunk_query->all)) {
				break;
			}
			if ($this->eager_loader) {
				call_user_func($this->eager_loader, new DMZ_Collection($chunk_query->all, $chunk_query));
			}

			if ($this->keyset_column !== NULL) {
				$last_item = end($chunk_query->all);
				if (!is_object($last_item) || !isset($last_item->{$this->keyset_column})) {
					throw new RuntimeException('Keyset lazy iteration requires the selected key column in every result.');
				}
			}
			
			// Process each item in chunk
			foreach ($chunk_query->all as $item) {
				$processed = $this->apply_operations($item, $seen_keys);
				if (empty($seen_keys['_skip'])) {
					yield $processed;
				}
				if (!empty($seen_keys['_stop'])) {
					return;
				}
			}
			
			// Stop if we got less than chunkSize (last chunk)
			if (count($chunk_query->all) < $this->chunk_size) {
				break;
			}
			
			// Move to next chunk
			if ($this->keyset_column !== NULL) {
				$last_key = $last_item->{$this->keyset_column};
			} else {
				$offset += $this->chunk_size;
			}
			
			// Clear memory
			$chunk_query = null;
		}
	}

	public function get_iterator(): Traversable
	{
		return $this->getIterator();
	}
	
	/**
	 * Apply all queued operations to an item
	 *
	 * @param mixed $item The item to process
	 * @param array &$seenKeys For tracking unique items
	 * @return mixed|null Processed item or null if filtered out
	 */
	protected function apply_operations($item, &$seen_keys)
	{
		$result = $item;
		$seen_keys['_skip'] = FALSE;
		
		foreach ($this->operations as $index => $op) {
			switch ($op['type']) {
				case 'map':
					$result = call_user_func($op['callback'], $result);
					break;
					
				case 'filter':
					if (!call_user_func($op['callback'], $result)) {
						$seen_keys['_skip'] = TRUE;
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
					$unique_value = $key === null ? serialize($result) : (
						is_object($result) ? (isset($result->{$key}) ? $result->{$key} : null) :
						(is_array($result) ? (isset($result[$key]) ? $result[$key] : null) : null)
					);
					
					if (isset($seen_keys[$index]['unique'][$unique_value])) {
						$seen_keys['_skip'] = TRUE;
						return null; // Duplicate
					}
					$seen_keys[$index]['unique'][$unique_value] = true;
					break;
					
				case 'take':
					$seen_keys[$index]['count'] = ($seen_keys[$index]['count'] ?? 0) + 1;
					if ($seen_keys[$index]['count'] >= $op['limit']) {
						$seen_keys['_stop'] = TRUE;
					}
					break;
				case 'skip':
					$seen_keys[$index]['count'] = ($seen_keys[$index]['count'] ?? 0) + 1;
					if ($seen_keys[$index]['count'] <= $op['count']) {
						$seen_keys['_skip'] = TRUE;
						return NULL;
					}
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
	public function to_array()
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
