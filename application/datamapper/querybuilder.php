<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * DataMapper Query Builder Extension
 *
 * Provides a query builder interface for DataMapper ORM while maintaining
 * full backward compatibility with existing DataMapper functionality.
 *
 * @package    DataMapper ORM
 * @subpackage Extensions  
 * @category   Query Builder
 * @author     DataMapper Development Team
 * @license    MIT License
 * @link       http://datamapper.wanwizard.eu/
 * @version    2.0.0
 */

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
 * DataMapper Query Builder Class
 *
 * Query builder interface that extends DataMapper functionality without
 * breaking existing code. All original DataMapper methods remain unchanged.
 */
class DMZ_QueryBuilder {
    
    /**
     * DataMapper instance
     * @var DataMapper
     */
    protected $model;
    
    /**
     * Relations to eager load
     * @var array
     */
    protected $eager_loads = array();
    
    /**
     * Eager loading constraints
     * @var array
     */
    protected $eager_constraints = array();
    
    /**
     * Query limit
     * @var int
     */
    protected $_limit;
    
    /**
     * Query offset
     * @var int
     */
    protected $_offset;

    /**
     * Tracks generated aliases for relation count subqueries
     * @var array
     */
    protected $_relation_count_aliases = array();
    
    /**
     * Static cache for table existence checks
     * Prevents repeated SHOW TABLES queries
     * @var array
     */
    protected static $_table_exists_cache = array();
    
    /**
     * Constructor
     *
     * @param DataMapper $model The model instance
     */
    public function __construct($model) {
        if (!($model instanceof DataMapper)) {
            throw new Exception('QueryBuilder requires a DataMapper instance');
        }
        $this->model = $model->get_clone();
    }
    
    /**
     * Add WHERE clause
     *
     * @param string $field Field name
     * @param mixed $value Value to compare  
     * @param string $operator Comparison operator
     * @return DMZ_QueryBuilder
     */
    public function where($field, $value = NULL, $operator = '=') {
        if ($value === NULL && $operator === '=') {
            $this->model->where($field);
        } else {
            if ($operator !== '=') {
                $field = $field . ' ' . $operator;
            }
            $this->model->where($field, $value);
        }
        return $this;
    }
    
    /**
     * Add OR WHERE clause
     *
     * @param string $field Field name
     * @param mixed $value Value to compare
     * @param string $operator Comparison operator
     * @return DMZ_QueryBuilder
     */
    public function or_where($field, $value = NULL, $operator = '=') {
        if ($value === NULL && $operator === '=') {
            $this->model->or_where($field);
        } else {
            if ($operator !== '=') {
                $field = $field . ' ' . $operator;
            }
            $this->model->or_where($field, $value);
        }
        return $this;
    }
    
    /**
     * Add WHERE IN clause
     *
     * @param string $field Field name
     * @param array $values Array of values
     * @return DMZ_QueryBuilder
     */
    public function where_in($field, $values) {
        $this->model->where_in($field, $values);
        return $this;
    }
    
    /**
     * Add WHERE NOT IN clause
     *
     * @param string $field Field name  
     * @param array $values Array of values
     * @return DMZ_QueryBuilder
     */
    public function where_not_in($field, $values) {
        $this->model->where_not_in($field, $values);
        return $this;
    }
    
    /**
     * Add LIKE clause
     *
     * @param string $field Field name
     * @param string $match Pattern to match
     * @param string $side Which side to match (both, before, after)
     * @return DMZ_QueryBuilder
     */
    public function like($field, $match, $side = 'both') {
        $this->model->like($field, $match, $side);
        return $this;
    }
    
    /**
     * Add OR LIKE clause
     *
     * @param string $field Field name
     * @param string $match Pattern to match
     * @param string $side Which side to match (both, before, after)
     * @return DMZ_QueryBuilder
     */
    public function or_like($field, $match, $side = 'both') {
        $this->model->or_like($field, $match, $side);
        return $this;
    }
    
    /**
     * Add NOT LIKE clause
     *
     * @param string $field Field name
     * @param string $match Pattern to match
     * @param string $side Which side to match (both, before, after)
     * @return DMZ_QueryBuilder
     */
    public function not_like($field, $match, $side = 'both') {
        $this->model->not_like($field, $match, $side);
        return $this;
    }
    
    /**
     * Add OR NOT LIKE clause
     *
     * @param string $field Field name
     * @param string $match Pattern to match
     * @param string $side Which side to match (both, before, after)
     * @return DMZ_QueryBuilder
     */
    public function or_not_like($field, $match, $side = 'both') {
        $this->model->or_not_like($field, $match, $side);
        return $this;
    }
    
    /**
     * Add OR WHERE IN clause
     *
     * @param string $field Field name
     * @param array $values Array of values
     * @return DMZ_QueryBuilder
     */
    public function or_where_in($field, $values) {
        $this->model->or_where_in($field, $values);
        return $this;
    }
    
    /**
     * Add OR WHERE NOT IN clause
     *
     * @param string $field Field name
     * @param array $values Array of values
     * @return DMZ_QueryBuilder
     */
    public function or_where_not_in($field, $values) {
        $this->model->or_where_not_in($field, $values);
        return $this;
    }
    
    /**
     * Add WHERE BETWEEN clause
     *
     * @param string $field Field name
     * @param mixed $value1 Start value
     * @param mixed $value2 End value
     * @return DMZ_QueryBuilder
     */
    public function where_between($field, $value1, $value2) {
        $this->model->where_between($field, $value1, $value2);
        return $this;
    }
    
    /**
     * Add WHERE NOT BETWEEN clause
     *
     * @param string $field Field name
     * @param mixed $value1 Start value
     * @param mixed $value2 End value
     * @return DMZ_QueryBuilder
     */
    public function where_not_between($field, $value1, $value2) {
        $this->model->where_not_between($field, $value1, $value2);
        return $this;
    }
    
    /**
     * Add OR WHERE BETWEEN clause
     *
     * @param string $field Field name
     * @param mixed $value1 Start value
     * @param mixed $value2 End value
     * @return DMZ_QueryBuilder
     */
    public function or_where_between($field, $value1, $value2) {
        $this->model->or_where_between($field, $value1, $value2);
        return $this;
    }
    
    /**
     * Add OR WHERE NOT BETWEEN clause
     *
     * @param string $field Field name
     * @param mixed $value1 Start value
     * @param mixed $value2 End value
     * @return DMZ_QueryBuilder
     */
    public function or_where_not_between($field, $value1, $value2) {
        $this->model->or_where_not_between($field, $value1, $value2);
        return $this;
    }
    
    /**
     * Filter JSON columns containing a specific value.
     *
     * @param string $field JSON column (supports -> path syntax)
     * @param mixed $value Candidate value to locate inside the JSON document
     * @param string|array|null $path Optional extra path appended to $field
     * @return DMZ_QueryBuilder
     */
    public function where_json_contains($field, $value, $path = NULL) {
        $this->model->where_json_contains($field, $value, $path);
        return $this;
    }

    /**
     * CamelCase alias for {@see where_json_contains}.
     */
    public function whereJsonContains($field, $value, $path = NULL) {
        return $this->where_json_contains($field, $value, $path);
    }

    /**
     * OR variant of {@see where_json_contains}.
     */
    public function or_where_json_contains($field, $value, $path = NULL) {
        $this->model->or_where_json_contains($field, $value, $path);
        return $this;
    }

    /**
     * CamelCase OR alias for {@see or_where_json_contains}.
     */
    public function orWhereJsonContains($field, $value, $path = NULL) {
        return $this->or_where_json_contains($field, $value, $path);
    }

    /**
     * Negative containment helper for JSON columns.
     *
     * @param string $field
     * @param mixed $value
     * @param string|array|null $path
     * @return DMZ_QueryBuilder
     */
    public function where_json_doesnt_contain($field, $value, $path = NULL) {
        $this->model->where_json_doesnt_contain($field, $value, $path);
        return $this;
    }

    /**
     * CamelCase alias for {@see where_json_doesnt_contain}.
     */
    public function whereJsonDoesntContain($field, $value, $path = NULL) {
        return $this->where_json_doesnt_contain($field, $value, $path);
    }

    /**
     * OR variant of {@see where_json_doesnt_contain}.
     */
    public function or_where_json_doesnt_contain($field, $value, $path = NULL) {
        $this->model->or_where_json_doesnt_contain($field, $value, $path);
        return $this;
    }

    /**
     * CamelCase OR alias for {@see or_where_json_doesnt_contain}.
     */
    public function orWhereJsonDoesntContain($field, $value, $path = NULL) {
        return $this->or_where_json_doesnt_contain($field, $value, $path);
    }

    /**
     * Start a group of WHERE conditions
     *
     * @param string $not NOT prefix
     * @param string $type AND/OR type
     * @return DMZ_QueryBuilder
     */
    public function group_start($not = '', $type = 'AND ') {
        $this->model->group_start($not, $type);
        return $this;
    }
    
    /**
     * Start an OR group of WHERE conditions
     *
     * @return DMZ_QueryBuilder
     */
    public function or_group_start() {
        $this->model->or_group_start();
        return $this;
    }
    
    /**
     * Start a NOT group of WHERE conditions
     *
     * @return DMZ_QueryBuilder
     */
    public function not_group_start() {
        $this->model->not_group_start();
        return $this;
    }
    
    /**
     * Start an OR NOT group of WHERE conditions
     *
     * @return DMZ_QueryBuilder
     */
    public function or_not_group_start() {
        $this->model->or_not_group_start();
        return $this;
    }
    
    /**
     * End a group of WHERE conditions
     *
     * @return DMZ_QueryBuilder
     */
    public function group_end() {
        $this->model->group_end();
        return $this;
    }
    
    /**
     * Add OR HAVING clause
     *
     * @param string $field Field name
     * @param mixed $value Value to compare
     * @return DMZ_QueryBuilder
     */
    public function or_having($field, $value = NULL) {
        if ($value === NULL) {
            $this->model->or_having($field);
        } else {
            $this->model->or_having($field, $value);
        }
        return $this;
    }
    
    /**
     * Add ORDER BY clause
     *
     * @param string $field Field name
     * @param string $direction Sort direction (asc/desc)
     * @return DMZ_QueryBuilder
     */
    public function order_by($field, $direction = 'asc') {
        $this->model->order_by($field, $direction);
        return $this;
    }
    
    /**
     * Add offset to query
     *
     * @param int $offset Number of records to skip
     * @return DMZ_QueryBuilder
     */
    public function offset($offset) {
        $this->_offset = $offset;
        return $this;
    }
    
    /**
     * Select specific fields with aggregation functions
     *
     * @param string $select Field to select max from
     * @param string $alias Alias for the result
     * @return DMZ_QueryBuilder
     */
    public function select_max($select = '', $alias = '') {
        $this->model->select_max($select, $alias);
        return $this;
    }
    
    /**
     * Select specific fields with min aggregation
     *
     * @param string $select Field to select min from
     * @param string $alias Alias for the result
     * @return DMZ_QueryBuilder
     */
    public function select_min($select = '', $alias = '') {
        $this->model->select_min($select, $alias);
        return $this;
    }
    
    /**
     * Select specific fields with avg aggregation
     *
     * @param string $select Field to select avg from
     * @param string $alias Alias for the result
     * @return DMZ_QueryBuilder
     */
    public function select_avg($select = '', $alias = '') {
        $this->model->select_avg($select, $alias);
        return $this;
    }
    
    /**
     * Select specific fields with sum aggregation
     *
     * @param string $select Field to select sum from
     * @param string $alias Alias for the result
     * @return DMZ_QueryBuilder
     */
    public function select_sum($select = '', $alias = '') {
        $this->model->select_sum($select, $alias);
        return $this;
    }
    
    /**
     * Join related models (DataMapper-specific)
     *
     * @param string $related_field Related field name
     * @param mixed $fields Fields to select
     * @param bool $append_name Whether to append table name
     * @return DMZ_QueryBuilder
     */
    public function join_related($related_field, $fields = NULL, $append_name = TRUE) {
        $this->model->join_related($related_field, $fields, $append_name);
        return $this;
    }
    
    /**
     * Add LIMIT clause
     *
     * @param int $limit Number of records to limit
     * @param int $offset Number of records to offset
     * @return DMZ_QueryBuilder
     */
    public function limit($limit, $offset = NULL) {
        // Store for later use in get()
        $this->_limit = $limit;
        $this->_offset = $offset;
        return $this;
    }
    
    /**
     * Add GROUP BY clause
     *
     * @param string $field Field name
     * @return DMZ_QueryBuilder
     */
    public function group_by($field) {
        $this->model->group_by($field);
        return $this;
    }
    
    /**
     * Add HAVING clause
     *
     * @param string $field Field name
     * @param mixed $value Value to compare
     * @param string $operator Comparison operator
     * @return DMZ_QueryBuilder
     */
    public function having($field, $value = NULL, $operator = '=') {
        if ($value === NULL && $operator === '=') {
            $this->model->having($field);
        } else {
            if ($operator !== '=') {
                $field = $field . ' ' . $operator;
            }
            $this->model->having($field, $value);
        }
        return $this;
    }
    
    /**
     * Select specific fields
     *
     * @param string $fields Comma separated field names or single field
     * @return DMZ_QueryBuilder
     */
    public function select($fields = '*') {
        $this->model->select($fields);
        return $this;
    }
    
    /**
     * Add DISTINCT clause
     *
     * @return DMZ_QueryBuilder
     */
    public function distinct() {
        $this->model->distinct();
        return $this;
    }

    /**
     * Filter results where the given relationship satisfies the count/operator condition.
     *
     * @param string $relation Relation name (dot or slash notation)
     * @param string $operator Comparison operator
     * @param int $count Comparison count
     * @param callable|null $callback Optional constraint callback applied to relation
     * @return DMZ_QueryBuilder
     */
    public function has($relation, $operator = '>=', $count = 1, $callback = NULL)
    {
        return $this->_apply_has_constraint('and', $relation, $callback, $operator, $count);
    }

    /**
     * Alias for has() following snake_case conventions.
     */
    public function where_has($relation, $callback = NULL, $operator = '>=', $count = 1)
    {
        return $this->_apply_has_constraint('and', $relation, $callback, $operator, $count);
    }

    /**
     * CamelCase alias for has().
     */
    public function whereHas($relation, $callback = NULL, $operator = '>=', $count = 1)
    {
        return $this->where_has($relation, $callback, $operator, $count);
    }

    /**
     * OR variant of has().
     */
    public function or_has($relation, $callback = NULL, $operator = '>=', $count = 1)
    {
        return $this->_apply_has_constraint('or', $relation, $callback, $operator, $count);
    }

    /**
     * CamelCase OR alias for has().
     */
    public function orWhereHas($relation, $callback = NULL, $operator = '>=', $count = 1)
    {
        return $this->or_has($relation, $callback, $operator, $count);
    }

    /**
     * Filter results that do NOT have the given relationship.
     */
    public function doesnt_have($relation, $callback = NULL)
    {
        return $this->_apply_has_constraint('and', $relation, $callback, '<', 1);
    }

    /**
     * Alias for doesnt_have() with camelCase name.
     */
    public function doesntHave($relation, $callback = NULL)
    {
        return $this->doesnt_have($relation, $callback);
    }

    /**
     * Snake_case alias for doesnt_have().
     */
    public function where_doesnt_have($relation, $callback = NULL)
    {
        return $this->doesnt_have($relation, $callback);
    }

    /**
     * CamelCase alias for where_doesnt_have().
     */
    public function whereDoesntHave($relation, $callback = NULL)
    {
        return $this->doesnt_have($relation, $callback);
    }

    /**
     * OR variant for doesnt_have().
     */
    public function or_where_doesnt_have($relation, $callback = NULL)
    {
        return $this->_apply_has_constraint('or', $relation, $callback, '<', 1);
    }

    /**
     * CamelCase alias for or_where_doesnt_have().
     */
    public function orWhereDoesntHave($relation, $callback = NULL)
    {
        return $this->or_where_doesnt_have($relation, $callback);
    }

    /**
     * Expose the internal DataMapper instance used by this builder.
     *
     * @return DataMapper
     */
    public function get_model()
    {
        return $this->model;
    }
    
    /**
     * Add eager loading for relationships
     *
     * This is the key feature that solves N+1 query problems.
     * Now supports WHERE constraints on related queries.
     *
     * Usage examples:
     *   ->with('installations')                                    // Simple eager load
     *   ->with('installations', function($q) { $q->where(...); })  // With constraint (DataMapper style)
     *   ->with(['installations' => function($q) {...}])            // Array syntax (Eloquent-like)
     *   ->with(['installations', 'building'])                      // Multiple relations
     *
     * @param string|array $relations Relations to eager load
     * @param callable|null $constraints Optional constraint callback (when $relations is string)
     * @return DMZ_QueryBuilder
     */
    public function with($relations, $constraints = NULL) {
        // Handle: with('relation', function($q) {...})
        if (is_string($relations) && is_callable($constraints)) {
            $this->eager_loads[] = $relations;
            $this->eager_constraints[$relations] = $constraints;
            return $this;
        }
        
        // Handle: with('relation') - simple string
        if (is_string($relations)) {
            $relations = array($relations);
        }
        
        // Handle: with(['relation1', 'relation2']) or with(['relation' => function($q) {...}])
        foreach ($relations as $key => $relation) {
            if (is_numeric($key)) {
                // Simple relation name: ['relation1', 'relation2']
                $this->eager_loads[] = $relation;
            } else {
                // Key-value pair: ['relation' => callback]
                $this->eager_loads[] = $key;
                if (is_callable($relation)) {
                    $this->eager_constraints[$key] = $relation;
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Execute query and return results
     *
     * @return DMZ_Collection
     */
    /**
     * Execute query and return results as DMZ_Collection
     * 
     * Returns a DMZ_Collection for easy use of collection helpers.
     * QueryBuilder always returns collections by default for consistency.
     *
     * @return DMZ_Collection
     */
    public function get() {
        // Store the model class before calling get()
        $model_class = get_class($this->model);
        
        // Call DataMapper's get() method (returns $this, populates $this->model->all)
        if (isset($this->_limit)) {
            $this->model->get($this->_limit, $this->_offset);
        } else {
            $this->model->get();
        }
        
        // Create collection from results
        $collection = new DMZ_Collection($this->model->all);
        
        // Apply eager loading if requested
        if (!empty($this->eager_loads)) {
            $queries_before = isset($this->model->db->queries) ? count($this->model->db->queries) : 0;
            
            // Apply eager loading
            $this->_load_eager_relations($collection);
            
            $queries_after = isset($this->model->db->queries) ? count($this->model->db->queries) : 0;
            $eager_query_count = $queries_after - $queries_before;
            
            // Log eager loading execution
            dmz_log_message('debug', "Eager loading for {$model_class}", array(
                'relations' => $this->eager_loads,
                'collection_size' => $collection->count(),
                'queries' => $eager_query_count
            ));
        }
        
        return $collection;
    }

    /**
     * Explicit collection accessor.
     *
    * Useful when you want terser query builder chains without repeating `get()`,
     * or when you need to highlight the transition from query constraints to
     * collection helpers.
     *
     * Example:
     *   $ids = (new User())->where('active', 1)->collect()->pluck('id');
     *
     * @return DMZ_Collection Hydrated collection of models respecting the active query
     */
    public function collect()
    {
        return $this->get();
    }

    /**
     * Pluck a field from every model into a plain array.
     *
     * Mirrors Laravel's `pluck()` helper and returns simple scalars, which is
     * perfect for building ID/email lists without dragging along the full
     * model payload.
     *
     * @param string $field Column or accessor name to extract from each model
     * @return array<int, mixed> Ordered list of extracted values
     */
    public function pluck($field)
    {
        return $this->get()->pluck($field);
    }

    /**
     * Pluck a field and keep collection chaining alive.
     *
     * Handy when you want the extracted values but still need collection
     * helpers like `filter()`/`map()`.
     *
     * @param string $field Column or accessor name to extract
     * @return DMZ_Collection Collection whose items are the extracted values
     */
    public function pluck_collection($field)
    {
        return $this->get()->pluck_collection($field);
    }

    /**
     * Alias for {@see pluck()} when you want to emphasise the array return type.
     *
     * Reads nicely in legacy codebases where the original helper was called
     * `pluck_values()`.
     *
     * @param string $field Column or accessor name to extract
     * @return array<int, mixed> Ordered list of extracted values
     */
    public function pluck_values($field)
    {
        return $this->pluck($field);
    }

    /**
     * Fetch a single scalar value from the first matching record.
     *
     * Useful for lightweight lookups (`value('id')`) where building an entire
     * model is overkill. Automatically restores any previously configured
     * limit/offset so the builder can keep chaining afterwards.
     *
     * @param string $field Column or accessor to read
     * @param mixed $default Value returned when the query produces no rows or the field is missing
     * @return mixed Scalar value (or the provided default)
     */
    public function value($field, $default = NULL)
    {
        $previousLimit = $this->_limit;
        $previousOffset = $this->_offset;

        $first = $this->limit(1)->get()->first();

        // Restore original pagination settings for further chaining
        $this->_limit = $previousLimit;
        $this->_offset = $previousOffset;

        if (!$first) {
            return $default;
        }

        if (is_array($first)) {
            return array_key_exists($field, $first) ? $first[$field] : $default;
        }

        return isset($first->{$field}) ? $first->{$field} : $default;
    }
    
    /**
     * Execute query and return single model or collection based on limit
     * 
     * Smart method that returns:
     * - Single DataMapper model if limit(1) was set
     * - DMZ_Collection otherwise
     * 
     * This provides the best of both worlds - explicit when you want one result,
     * collection when you want multiple.
     *
     * @return DataMapper|DMZ_Collection|NULL
     */
    public function getSmart() {
        // If limit is 1, return single model
        if ($this->_limit === 1) {
            return $this->first();
        }
        
        // Otherwise return collection
        return $this->get();
    }
    
    /**
     * Get first result
     *
     * @return DataMapper|NULL
     */
    public function first() {
        $previousLimit = $this->_limit;
        $previousOffset = $this->_offset;

        $results = $this->limit(1)->get();

        // Restore original pagination settings for further chaining
        $this->_limit = $previousLimit;
        $this->_offset = $previousOffset;

        return $results->count() > 0 ? $results->first() : NULL;
    }
    
    /**
     * Find by primary key
     *
     * @param int $id Primary key value
     * @return DataMapper|NULL
     */
    public function find($id) {
        return $this->where('id', $id)->first();
    }
    
    /**
     * Find by primary key or show error
     *
     * @param int $id Primary key value
     * @return DataMapper
     */
    public function find_or_fail($id) {
        $result = $this->find($id);
        if (!$result || !$result->exists()) {
            throw new Exception('Model not found with ID: ' . $id);
        }
        return $result;
    }
    
    /**
     * Check if any results exist
     *
     * @return bool
     */
    public function exists() {
        $model_copy = $this->model->get_clone();
        return $model_copy->count() > 0;
    }
    
    /**
     * Get count of results
     *
     * @return int
     */
    public function count() {
        $model_copy = $this->model->get_clone();
        return $model_copy->count();
    }
    
    /**
     * Get results as array (backward compatibility)
     *
     * @return array
     */
    public function get_array() {
        return $this->get()->to_array();
    }
    
    /**
     * Get SQL query string
     *
     * @return string
     */
    public function to_sql() {
        $limit = isset($this->_limit) ? $this->_limit : NULL;
        $offset = isset($this->_offset) ? $this->_offset : NULL;
        return $this->model->get_sql($limit, $offset);
    }

    /**
     * Core handler for has/whereHas style constraints.
     *
     * @param string $boolean 'and' or 'or'
     * @param string $relation Relation key (dot/slash notation)
     * @param callable|null $callback Constraint callback
     * @param string $operator Comparison operator
     * @param int $count Comparison count
     * @return DMZ_QueryBuilder
     */
    protected function _apply_has_constraint($boolean, $relation, $callback, $operator, $count)
    {
        if (empty($relation)) {
            return $this;
        }

        $normalized = $this->_normalize_relation_path($relation);
        $alias = $this->_reserve_relation_count_alias($relation);

        $this->model->include_related_count($normalized, $alias, $callback);

        $clause = $alias . ' ' . $operator;
        if (strtolower($boolean) === 'or') {
            $this->model->or_having($clause, $count);
        } else {
            $this->model->having($clause, $count);
        }

        return $this;
    }

    /**
     * Convert dotted relation path to DataMapper slash format.
     *
     * @param string $relation
     * @return string
     */
    protected function _normalize_relation_path($relation)
    {
        return str_replace('.', '/', trim($relation));
    }

    /**
     * Reserve a unique alias for relation count subqueries.
     *
     * @param string $relation
     * @return string
     */
    protected function _reserve_relation_count_alias($relation)
    {
        $base = '_dmz_' . preg_replace('/[^a-z0-9_]/i', '_', strtolower($relation)) . '_count';
        $alias = $base;
        $suffix = 1;
        while (isset($this->_relation_count_aliases[$alias])) {
            $alias = $base . '_' . $suffix++;
        }
        $this->_relation_count_aliases[$alias] = TRUE;
        return $alias;
    }
    
    /**
     * Load eager relationships for results
     * Optimized to avoid redundant loading of nested relationships
     *
     * @param DMZ_Collection $results
     */
    protected function _load_eager_relations($results) {
        if ($results->count() === 0) {
            return;
        }
        
        // Optimize eager loads by removing redundant nested paths
        // If loading 'a.b.c', don't also load 'a' and 'a.b' separately
        $optimized_loads = $this->_optimize_eager_loads($this->eager_loads);
        
        foreach ($optimized_loads as $relation) {
            $this->_load_relation($results, $relation);
        }
        
        // NOW disable auto-populate on all eagerly loaded models to prevent N+1 queries
        // This happens AFTER all nested relations are loaded, so nested loading works properly
        $this->_disable_auto_populate_recursive($results);
    }
    
    /**
     * Recursively disable auto-populate on all eagerly loaded models
     * Called AFTER all eager loading is complete to prevent N+1 queries on access
     *
     * @param DMZ_Collection $collection
     */
    protected function _disable_auto_populate_recursive($collection) {
        foreach ($collection->to_array() as $model) {
            // Disable auto-populate on this model
            $model->auto_populate_has_one = FALSE;
            $model->auto_populate_has_many = FALSE;

            $current_vars = get_object_vars($model);
            foreach (array_merge(array_keys($model->has_one), array_keys($model->has_many)) as $relation) {
                if (!isset($current_vars[$relation]) || !is_object($current_vars[$relation])) {
                    continue;
                }

                $related = $current_vars[$relation];

                if ($related instanceof DMZ_Collection) {
                    // has_many or many_to_many - recurse on collection
                    $this->_disable_auto_populate_recursive($related);
                } elseif ($related instanceof DataMapper && $related->exists()) {
                    // has_one - disable and recurse
                    $related->auto_populate_has_one = FALSE;
                    $related->auto_populate_has_many = FALSE;

                    $single_collection = new DMZ_Collection(array($related));
                    $this->_disable_auto_populate_recursive($single_collection);
                }
            }
        }
    }
    
    /**
     * Optimize eager load paths to avoid redundant loading
     * 
     * Example: ['installation', 'installation.building', 'installation.building.client']
     * Optimizes to: ['installation.building.client']
     * Because loading 'installation.building.client' will load all parent levels
     *
     * @param array $eager_loads
     * @return array
     */
    protected function _optimize_eager_loads($eager_loads) {
        if (empty($eager_loads)) {
            return array();
        }
        
        $optimized = array();
        
        foreach ($eager_loads as $load) {
            $is_redundant = FALSE;
            
            // Check if this load path is a subset of any other load path
            foreach ($eager_loads as $other_load) {
                if ($load !== $other_load && strpos($other_load, $load . '.') === 0) {
                    // This load is a parent of another load, skip it
                    $is_redundant = TRUE;
                    break;
                }
            }
            
            if (!$is_redundant) {
                $optimized[] = $load;
            }
        }
        
        return $optimized;
    }
    
    /**
     * Load a specific relation for results using DataMapper's relationship system
     *
     * @param DMZ_Collection $results
     * @param string $relation
     */
    protected function _load_relation($results, $relation) {
        // Handle nested relations (e.g., 'comments.user')
        if (strpos($relation, '.') !== FALSE) {
            $this->_load_nested_relation($results, $relation);
            return;
        }
        
        $first_model = $results->first();
        if (!$first_model) {
            return;
        }
        
        // Check if relation exists in has_one or has_many
        $is_has_many = isset($first_model->has_many[$relation]);
        $is_has_one = isset($first_model->has_one[$relation]);
        
        if (!$is_has_many && !$is_has_one) {
            return; // Skip invalid relations silently
        }
        
        // Get relationship configuration
        $relation_config = $is_has_many ? $first_model->has_many[$relation] : $first_model->has_one[$relation];
        
        // Normalize config - handle both simple strings and arrays
        if (is_string($relation_config)) {
            $relation_config = array('class' => $relation_config);
        }
        
        $related_class = $relation_config['class'];

        // Convert 'installation' -> 'Installation', 'building' -> 'Building', etc.
        if (!class_exists($related_class)) {
            // Try capitalizing first letter (DataMapper convention)
            $capitalized = ucfirst($related_class);
            if (class_exists($capitalized)) {
                $related_class = $capitalized;
            }
        }
        
        // Get all parent IDs for batch loading
        $parent_ids = array();
        foreach ($results->to_array() as $model) {
            if (!empty($model->id)) {
                $parent_ids[] = $model->id;
            }
        }
        
        if (empty($parent_ids)) {
            return;
        }
        
        // Determine the relationship table and keys based on DataMapper conventions
        $parent_model = strtolower(get_class($first_model));
        $parent_table = $first_model->table;
        
        // Create related model instance
        $related_model = new $related_class();
        $related_table = $related_model->table;
        
        // Determine join table and foreign keys using DataMapper's naming convention
        // For many-to-many: uses join table like 'users_roles'
        // For one-to-many/one-to-one: uses foreign key in related table
        
        if ($is_has_many && $this->_is_many_to_many($first_model, $relation, $relation_config)) {
            // Many-to-many relationship with join table
            $this->_load_many_to_many($results, $relation, $relation_config, $parent_ids);
        } else {
            // One-to-many or one-to-one with foreign key
            $this->_load_with_foreign_key($results, $relation, $relation_config, $parent_ids, $is_has_many);
        }
    }
    
    /**
     * Check if relationship is many-to-many (uses join table)
     * 
     * @param DataMapper $model
     * @param string $relation
     * @param array $config
     * @return bool
     */
    protected function _is_many_to_many($model, $relation, $config) {
        // If join_table is explicitly set to a non-empty value, it's many-to-many
        if (isset($config['join_table']) && !empty($config['join_table'])) {
            dmz_log_message('debug', "Relation '{$relation}' is many-to-many (join_table explicitly set)");
            return TRUE;
        }
        
        // If join_self_as and join_other_as are set, it's many-to-many
        if (isset($config['join_self_as']) && isset($config['join_other_as'])) {
            dmz_log_message('debug', "Relation '{$relation}' has join_self_as and join_other_as set - IS MANY-TO-MANY");
            return TRUE;  // MUST return TRUE immediately - join table configuration is explicit
        }
        
        // AUTO-DETECTION: Even if join table isn't explicitly configured,
        // check if a join table exists following DataMapper naming convention
        // This maintains backward compatibility with models that don't explicitly configure join tables
        $parent_table = $model->table;
        $related_class = $config['class'];
        
        dmz_log_message('debug', "Auto-detecting join table for '{$relation}'", array(
            'parent_table' => $parent_table,
            'related_class' => $related_class
        ));
        
        // Capitalize class name if needed
        if (!class_exists($related_class)) {
            $capitalized = ucfirst($related_class);
            if (class_exists($capitalized)) {
                $related_class = $capitalized;
                dmz_log_message('debug', "Capitalized class name to '{$related_class}'");
            }
        }
        
        $related_metadata = $this->_get_model_metadata($related_class);
        $related_table = ($related_metadata !== NULL && isset($related_metadata['table'])) ? $related_metadata['table'] : NULL;
		
        if ($related_table === NULL) {
            $related_model = new $related_class();
            $related_table = $related_model->table;
        }
        
        // Try both orderings (alphabetical is DataMapper convention)
        $join_table_1 = $parent_table . '_' . $related_table;
        $join_table_2 = $related_table . '_' . $parent_table;
        
        dmz_log_message('debug', "Checking for join tables", array(
            'option_1' => $join_table_1,
            'option_2' => $join_table_2
        ));
        
        // Check cache first
        $db = $model->db;
        $db_name = $db->database;
        
        $cache_key_1 = $db_name . '.' . $join_table_1;
        $cache_key_2 = $db_name . '.' . $join_table_2;
        
        // Check if we've already verified either table exists
        if (isset(self::$_table_exists_cache[$cache_key_1])) {
            $result = self::$_table_exists_cache[$cache_key_1];
            dmz_log_message('debug', "Found '{$join_table_1}' in cache", array('exists' => $result));
            return $result;
        }
        if (isset(self::$_table_exists_cache[$cache_key_2])) {
            $result = self::$_table_exists_cache[$cache_key_2];
            dmz_log_message('debug', "Found '{$join_table_2}' in cache", array('exists' => $result));
            return $result;
        }
        
        // Not in cache - check database (only once thanks to caching!)
        $exists_1 = $db->table_exists($join_table_1);
        $exists_2 = !$exists_1 ? $db->table_exists($join_table_2) : FALSE;
        
        dmz_log_message('debug', "Database join table check", array(
            'table_1' => $join_table_1,
            'exists_1' => $exists_1,
            'table_2' => $join_table_2,
            'exists_2' => $exists_2
        ));
        
        // Cache the results for future queries
        self::$_table_exists_cache[$cache_key_1] = $exists_1;
        if (!$exists_1) {
            self::$_table_exists_cache[$cache_key_2] = $exists_2;
        }
        
        $is_many_to_many = $exists_1 || $exists_2;
        dmz_log_message('debug', "Relation '{$relation}' detected as " . ($is_many_to_many ? 'MANY-TO-MANY' : 'ONE-TO-MANY'));
        
        return $is_many_to_many;
    }

    /**
     * Retrieve cached metadata for a DataMapper model class.
     *
     * @param string $class
     * @return array|null
     */
    protected function _get_model_metadata($class)
    {
        $lower = strtolower($class);

        if (isset(DataMapper::$common[DMZ_CLASSNAMES_KEY][$lower])) {
            $common_key = DataMapper::$common[DMZ_CLASSNAMES_KEY][$lower];
            if (isset(DataMapper::$common[$common_key])) {
                return DataMapper::$common[$common_key];
            }
        }

        if (!class_exists($class)) {
            return NULL;
        }

        $instance = new $class();
        $lower = strtolower($class);

        if (isset(DataMapper::$common[DMZ_CLASSNAMES_KEY][$lower])) {
            $common_key = DataMapper::$common[DMZ_CLASSNAMES_KEY][$lower];
            if (isset(DataMapper::$common[$common_key])) {
                return DataMapper::$common[$common_key];
            }
        }

        return array('table' => isset($instance->table) ? $instance->table : NULL);
    }
    
    /**
     * Load many-to-many relationship using join table
     * 
     * @param DMZ_Collection $results
     * @param string $relation
     * @param array $config
     * @param array $parent_ids
     */
    protected function _load_many_to_many($results, $relation, $config, $parent_ids) {
        $first_model = $results->first();
        $parent_table = $first_model->table;
        $parent_key = isset($config['join_self_as']) ? $config['join_self_as'] : rtrim($parent_table, 's');
        
        $related_class = $config['class'];
        
        // Capitalize class name if needed (DataMapper convention)
        if (!class_exists($related_class)) {
            $capitalized = ucfirst($related_class);
            if (class_exists($capitalized)) {
                $related_class = $capitalized;
            }
        }
        
        $related_model = new $related_class();
        $related_table = $related_model->table;
        $related_key = isset($config['join_other_as']) ? $config['join_other_as'] : rtrim($related_table, 's');
        
        // Determine join table
        $join_table = isset($config['join_table']) ? 
                     $config['join_table'] : 
                     $this->_get_join_table($parent_table, $related_table, $first_model->db);
        
        // Safety check: ensure join table is not empty
        if (empty($join_table)) {
            // Fallback: manually construct join table name
            $join_table = $this->_get_join_table($parent_table, $related_table, $first_model->db);
            
            // If still empty, log error and return
            if (empty($join_table)) {
                dmz_log_message('error', "Could not determine join table for relation '{$relation}'", array(
                    'parent_table' => $parent_table,
                    'related_table' => $related_table
                ));
                return;
            }
        }
        
        // Build query to get related records through join table
        $db = $first_model->db;
        
        // Reset query builder to ensure clean state
        $db->reset_query();
        
        // Query: SELECT related.*, join.parent_id FROM related 
        //        JOIN join_table ON related.id = join.related_id 
        //        WHERE join.parent_id IN (...)
        $db->select($related_table . '.*, ' . $join_table . '.' . $parent_key . '_id as _dm_parent_id')
           ->from($related_table)
           ->join($join_table, $related_table . '.id = ' . $join_table . '.' . $related_key . '_id')
           ->where_in($join_table . '.' . $parent_key . '_id', $parent_ids);
        
        // Apply eager loading constraints FIRST (to capture soft delete scope from user)
        // For many-to-many, we pass the DB instance directly since we're using manual queries
        $wrapper = $this->_apply_eager_constraints_to_db($db, $relation, $related_table);
        
        // Apply DataMapper 2.0 soft delete scope automatically
        // Check if the related model has soft deletes enabled (either trait or built-in)
        // Pass wrapper so we can respect withSoftDeleted()/onlySoftDeleted() (or legacy withDeleted()/onlyDeleted()) from constraint callback
        $related_instance = new $related_class();
        $this->_apply_soft_delete_scope_to_db($db, $related_instance, $related_table, $wrapper);
        
        // Execute query
        $query = $db->get();
        
        // Group results by parent ID
        $grouped = array();
        
        // Check if query succeeded
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $parent_id = $row->_dm_parent_id;
                unset($row->_dm_parent_id);
                
                // Create model instance (don't disable auto-populate yet - needed for nested loading)
                $item = new $related_class();
                $item->_populate($row);
                
                if (!isset($grouped[$parent_id])) {
                    $grouped[$parent_id] = array();
                }
                $grouped[$parent_id][] = $item;
            }
        }
        
        // Assign to parent models
        foreach ($results->to_array() as $model) {
            if (isset($grouped[$model->id])) {
                $model->{$relation} = new DMZ_Collection($grouped[$model->id]);
            } else {
                $model->{$relation} = new DMZ_Collection(array());
            }
        }
    }
    
    /**
     * Load relationship using foreign key
     * 
     * @param DMZ_Collection $results
     * @param string $relation
     * @param array $config
     * @param array $parent_ids
     * @param bool $is_has_many
     */
    protected function _load_with_foreign_key($results, $relation, $config, $parent_ids, $is_has_many) {
        $first_model = $results->first();
        $related_class = $config['class'];
        $related_model = new $related_class();
        
        if ($is_has_many) {
            // has_many: foreign key is in the RELATED table (e.g., installations.user_id)
            $parent_model_name = strtolower(get_class($first_model));
            $foreign_key = isset($config['other_field']) ? 
                          $config['other_field'] . '_id' : 
                          $parent_model_name . '_id';
            
            // Batch load related records where foreign_key IN (parent_ids)
            // Build base query
            $related_model->where_in($foreign_key, $parent_ids);
            
            // Apply eager loading constraints if any
            $this->_apply_eager_constraints($related_model, $relation);
            
            // Execute query
            $related_records = $related_model->get();
            
            // NOTE: Don't disable auto-populate here - it will prevent nested eager loading
            // Auto-populate will be disabled at the very end, after all nested relations are loaded
            
            // Group by foreign key
            $grouped = array();
            if (isset($related_records->all)) {
                foreach ($related_records->all as $record) {
                    $key = $record->{$foreign_key};
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = array();
                    }
                    $grouped[$key][] = $record;
                }
            }
            
            // Assign to parent models
            foreach ($results->to_array() as $model) {
                if (isset($grouped[$model->id])) {
                    $model->{$relation} = new DMZ_Collection($grouped[$model->id]);
                } else {
                    $model->{$relation} = new DMZ_Collection(array());
                }
            }
            
        } else {
            // has_one: foreign key is in the PARENT table (e.g., users.role_id)
            $foreign_key_field = $relation . '_id';
            
            // Collect all the foreign key IDs from parent models
            $foreign_ids = array();
            foreach ($results->to_array() as $model) {
                if (!empty($model->{$foreign_key_field})) {
                    $foreign_ids[] = $model->{$foreign_key_field};
                }
            }
            
            // Remove duplicates to avoid loading the same record multiple times
            $foreign_ids = array_unique($foreign_ids);
            
            if (empty($foreign_ids)) {
                // No foreign keys set, set all to NULL
                foreach ($results->to_array() as $model) {
                    $model->{$relation} = NULL;
                }
                return;
            }
            
            // Load related records by ID (BATCHED - single query)
            // Build base query
            $related_model->where_in('id', $foreign_ids);
            
            // Apply eager loading constraints if any
            $this->_apply_eager_constraints($related_model, $relation);
            
            // Execute query
            $related_records = $related_model->get();
            
            // NOTE: Don't disable auto-populate here - it will prevent nested eager loading
            // Auto-populate will be disabled at the very end, after all nested relations are loaded
            
            // Index by ID for fast lookup
            $indexed = array();
            if (isset($related_records->all)) {
                foreach ($related_records->all as $record) {
                    $indexed[$record->id] = $record;
                }
            }
            
            // Assign to parent models
            foreach ($results->to_array() as $model) {
                if (!empty($model->{$foreign_key_field}) && isset($indexed[$model->{$foreign_key_field}])) {
                    $model->{$relation} = $indexed[$model->{$foreign_key_field}];
                } else {
                    $model->{$relation} = NULL;
                }
            }
        }
    }
    
    /**
     * Get join table name for many-to-many relationship
     * DataMapper uses alphabetical ordering: installations_users (not users_installations)
     * 
     * @param string $table1
     * @param string $table2
     * @param object $db Database connection
     * @return string
     */
    protected function _get_join_table($table1, $table2, $db) {
        // DataMapper convention: alphabetical ordering
        // For 'users' and 'installations' -> 'installations_users'
        $tables = array($table1, $table2);
        sort($tables);
        return $tables[0] . '_' . $tables[1];
    }
    
    /**
     * Apply eager loading constraints that were registered for a relation.
     *
     * @param DataMapper $model The model being queried
     * @param string $relation The relation name
     * @return void
     */
    protected function _apply_eager_constraints($model, $relation) {
        if (!isset($this->eager_constraints[$relation])) {
            return; // No constraints for this relation
        }
        
        $constraint = $this->eager_constraints[$relation];
        
        if (is_callable($constraint)) {
            // Call the constraint callback, passing the model as the query builder
            call_user_func($constraint, $model);
        }
    }
    
    /**
     * Apply eager loading constraints to a CI database query builder
     * 
     * Used for many-to-many relationships which use direct DB queries.
     * Creates a temporary DataMapper wrapper to provide a consistent interface.
     * 
     * @param CI_DB_query_builder $db The database query builder
     * @param string $relation The relation name
     * @param string $table_prefix Optional table prefix for WHERE clauses
     * @return DMZ_DB_Constraint_Wrapper|null The wrapper instance (to check soft delete scope)
     */
    protected function _apply_eager_constraints_to_db($db, $relation, $table_prefix = '') {
        if (!isset($this->eager_constraints[$relation])) {
            return NULL; // No constraints for this relation
        }
        
        $constraint = $this->eager_constraints[$relation];
        
        if (is_callable($constraint)) {
            // Create a temporary wrapper to provide DataMapper-like interface to DB
            $wrapper = new DMZ_DB_Constraint_Wrapper($db, $table_prefix);
            call_user_func($constraint, $wrapper);
            return $wrapper; // Return wrapper so caller can check soft delete scope
        }
        
        return NULL;
    }
    
    /**
     * Apply soft delete scope to a database query builder
     * 
     * Automatically excludes soft-deleted records when:
     * 1. Model has deleted_at column (auto-detection)
     * 2. Model has soft_delete enabled (DataMapper 2.0 built-in)
     * 3. Model uses SoftDeletes trait
    * 
    * Keeps eager-loaded results consistent with standard queries.
    * 
     * @param CI_DB_query_builder $db The database query builder
     * @param DataMapper $model The model instance to check for soft delete configuration
     * @param string $table_prefix The table name prefix for WHERE clauses
     * @param DMZ_DB_Constraint_Wrapper|null $wrapper Optional wrapper to check for soft delete scope override
     * @return void
     */
    protected function _apply_soft_delete_scope_to_db($db, $model, $table_prefix = '', $wrapper = NULL) {
        // Check if user explicitly set soft delete scope in constraint callback
        if ($wrapper !== NULL) {
            $scope = $wrapper->getSoftDeleteScope();
			
            // If user called withSoftDeleted(), don't apply any deleted_at filter
            if ($scope === 'with_softdeleted' || $scope === 'with_deleted') {
                return;
            }
			
            // If user called onlySoftDeleted(), apply deleted_at IS NOT NULL
            if ($scope === 'only_softdeleted' || $scope === 'only_deleted') {
                $deleted_col = $this->_get_deleted_at_column($model);
                if ($deleted_col) {
                    $column_name = !empty($table_prefix) ? $table_prefix . '.' . $deleted_col : $deleted_col;
                    $db->where($column_name . ' IS NOT NULL', NULL, FALSE);
                }
                return;
            }
            
            // Otherwise fall through to apply default without_softdeleted() scope
        }
        
        // IMPORTANT: Ignore LODataMapper custom implementation
        // Check for native DataMapper 2.0 flags first (NOT LODataMapper's _withoutSoftDeletedScope)
        if ((property_exists($model, '_dm_with_softdeleted') && $model->_dm_with_softdeleted === TRUE)
            || (property_exists($model, '_dm_with_deleted') && $model->_dm_with_deleted === TRUE)) {
            // User explicitly called with_softdeleted() (or legacy with_deleted()) on main query - don't filter
            return;
        }
        
        if ((property_exists($model, '_dm_only_softdeleted') && $model->_dm_only_softdeleted === TRUE)
            || (property_exists($model, '_dm_only_deleted') && $model->_dm_only_deleted === TRUE)) {
            // User explicitly called only_softdeleted() (or legacy only_deleted()) on main query
            $deleted_col = $this->_get_deleted_at_column($model);
            if ($deleted_col) {
                $column_name = !empty($table_prefix) ? $table_prefix . '.' . $deleted_col : $deleted_col;
                $db->where($column_name . ' IS NOT NULL', NULL, FALSE);
            }
            return;
        }
        
        // Check various ways soft deletes can be configured
        // NOTE: We explicitly ignore LODataMapper's $_withoutSoftDeletedScope property
        //       That's a custom implementation - we only use native DataMapper 2.0 detection
        
        // 1. Check if model uses SoftDeletes trait (native DataMapper 2.0)
        $uses_trait = in_array('SoftDeletes', class_uses($model));
        
        // 2. Check if model has soft_delete property (native DataMapper 2.0 built-in)
        $has_soft_delete_property = property_exists($model, 'soft_delete');
        
        // 3. Check if model has deleted_at column (auto-detection)
        $deleted_col = $this->_get_deleted_at_column($model);
        
        // Check if column exists in model's fields
        $has_deleted_column = $deleted_col && property_exists($model, 'fields') && 
                             is_array($model->fields) && 
                             in_array($deleted_col, $model->fields);
        
        // Determine if soft deletes are enabled
        $soft_delete_enabled = FALSE;
        
        if ($uses_trait) {
            // Trait is used - check if enabled (default TRUE for trait)
            $soft_delete_enabled = !property_exists($model, 'softDelete') || $model->softDelete !== FALSE;
        } elseif ($has_soft_delete_property) {
            // Built-in soft delete - check property and config
            $config_soft_delete = isset(DataMapper::$config['soft_delete']) ? DataMapper::$config['soft_delete'] : FALSE;
            $soft_delete_enabled = $model->soft_delete !== NULL ? $model->soft_delete : $config_soft_delete;
        } elseif ($has_deleted_column) {
            // Auto-detection: has deleted_at column, enable automatically
            $soft_delete_enabled = TRUE;
        }
        
        // Apply the scope if enabled and column exists
        if ($soft_delete_enabled && $has_deleted_column) {
            $column_name = !empty($table_prefix) ? $table_prefix . '.' . $deleted_col : $deleted_col;
            $db->where($column_name, NULL);
        }
    }
    
    /**
     * Get the deleted_at column name for a model
     * 
     * @param DataMapper $model The model instance
     * @return string|null The column name or null if not found
     */
    protected function _get_deleted_at_column($model) {
        $deleted_col = 'deleted_at';
        
        // Get custom column name if specified
        if (property_exists($model, 'deleted_at_column') && !empty($model->deleted_at_column)) {
            $deleted_col = $model->deleted_at_column;
        } elseif (property_exists($model, 'deletedAtColumn') && !empty($model->deletedAtColumn)) {
            $deleted_col = $model->deletedAtColumn;
        } elseif (method_exists($model, 'getDeletedAtColumn')) {
            $deleted_col = $model->getDeletedAtColumn();
        }
        
        return $deleted_col;
    }
    
    /**
     * Load nested relations (e.g., 'installation.building.client')
     * Now properly handles multi-level nesting recursively
     *
     * @param DMZ_Collection $results
     * @param string $relation
     */
    protected function _load_nested_relation($results, $relation) {
        $parts = explode('.', $relation, 2);
        $first_relation = $parts[0];
        $nested_relation = $parts[1];
        
        // Load the first level relation
        $this->_load_relation($results, $first_relation);
        
        // Collect all loaded related models
        // Use property_exists to avoid triggering __get() which causes auto-population
        $related_models = array();
        foreach ($results->to_array() as $model) {
            // Check if the property was set by eager loading
            if (property_exists($model, $first_relation)) {
                $related = $model->{$first_relation};
                if ($related instanceof DMZ_Collection) {
                    foreach ($related->to_array() as $rel_model) {
                        if ($rel_model instanceof DataMapper) {
                            $related_models[] = $rel_model;
                        }
                    }
                } elseif ($related instanceof DataMapper && $related->exists()) {
                    $related_models[] = $related;
                }
            }
        }
        
        // Load the nested relation on related models RECURSIVELY
        if (!empty($related_models)) {
            $related_collection = new DMZ_Collection($related_models);
            
            // Check if there are more levels of nesting
            if (strpos($nested_relation, '.') !== FALSE) {
                // Recursively load nested relations
                $this->_load_nested_relation($related_collection, $nested_relation);
            } else {
                // Just one more level, load it directly
                $this->_load_relation($related_collection, $nested_relation);
            }
        }
    }
    
    /**
     * Magic method to delegate to DataMapper
     *
     * @param string $method Method name
     * @param array $args Method arguments
     * @return mixed
     */
    public function __call($method, $args) {
        $result = call_user_func_array(array($this->model, $method), $args);
        
        // If result is the model, return this for chaining
        if ($result === $this->model) {
            return $this;
        }
        
        return $result;
    }
}

/**
 * Collection class for DataMapper results
 * 
 * Implements IteratorAggregate and Countable for native PHP iteration.
 * Compatible with PHP 7.4 - 8.5
 */
class DMZ_Collection implements IteratorAggregate, Countable {
    
    /**
     * Collection items
     * @var array
     */
    protected $items = array();
    
    /**
     * Constructor
     *
     * @param array $items Initial items
     */
    public function __construct($items = array()) {
        $this->items = is_array($items) ? array_values($items) : array();
    }
    
    /**
     * Get first item
     *
     * @return mixed
     */
    public function first() {
        return isset($this->items[0]) ? $this->items[0] : NULL;
    }
    
    /**
     * Get last item
     *
     * @return mixed
     */
    public function last() {
        $count = count($this->items);
        return $count > 0 ? $this->items[$count - 1] : NULL;
    }
    
    /**
     * Check if collection is empty
     *
     * @return bool
     */
    public function is_empty() {
        return empty($this->items);
    }
    
    /**
     * Check if collection has any items (alias for backward compatibility)
     * 
     * Mimics DataMapper's exists() method behavior for collections.
     *
     * @return bool
     */
    public function exists() {
        return !empty($this->items);
    }
    
    /**
     * Get count of items (Countable implementation)
     *
     * @return int
     */
    public function count(): int {
        return count($this->items);
    }
    
    /**
     * Convert to array
     *
     * @return array
     */
    public function to_array() {
        return $this->items;
    }
    
    /**
     * No-op conversion to collection (already a collection)
     * 
     * LEGACY COMPATIBILITY: Maintains chaining safety for old code that calls
     * ->to_collection() on what might be a collection or a single model.
     * 
     * Example:
     *   $result = $model->get();  // Now returns DMZ_Collection
     *   $collection = $result->to_collection();  // Safe no-op, returns $this
     *
     * @return DMZ_Collection Returns self
     */
    public function to_collection() {
        return $this;
    }
    
    /**
     * Get specific field values from all items
     * 
     * LEGACY COMPATIBILITY NOTE: Returns array by default to maintain backward compatibility.
     * Use pluck_collection() if you need a DMZ_Collection for chaining.
     * 
     * Examples:
     *   $ids = $collection->pluck('id');                    // Returns array [1, 2, 3]
     *   $ids = $collection->pluck_collection('id');         // Returns DMZ_Collection for chaining
     *   $ids = $collection->pluck_collection('id')->all();  // Chains and converts to array
     *
     * @param string $field Field name
     * @return array Array of values (NOT a collection for backward compatibility)
     */
    public function pluck($field) {
        $values = array();
        foreach ($this->items as $item) {
            if (is_object($item) && isset($item->{$field})) {
                $values[] = $item->{$field};
            } elseif (is_array($item) && isset($item[$field])) {
                $values[] = $item[$field];
            }
        }
        return $values;
    }
    
    /**
     * Get specific field values as a DMZ_Collection (chainable version of pluck)
     * 
     * Use this when you need to chain collection methods after plucking.
     * 
     * Examples:
     *   $collection->pluck_collection('id')->unique()->all()
     *   $collection->pluck_collection('email')->filter(function($e) { ... })
     *
     * @param string $field Field name
     * @return DMZ_Collection Collection of values for chaining
     */
    public function pluck_collection($field) {
        return new DMZ_Collection($this->pluck($field));
    }
    
    /**
     * Alias for pluck() - returns array
     * 
     * Provides explicit naming when you want array output.
     * Useful for clarity: makes it obvious you expect a plain array.
     *
     * @param string $field Field name
     * @return array Array of values
     */
    public function pluck_values($field) {
        return $this->pluck($field);
    }
    
    /**
     * Convenience alias that mirrors to_array()/all() naming.
     *
     * Keeping the method lightweight means existing array-based
     * utilities can stay untouched while still letting teams call the
     * intent out explicitly in their code.
     *
     * @param string $field Field name
     * @return array Array of values
     */
    public function values() {
        return $this->items;
    }
    
    /**
     * Filter items using callback
     *
     * @param callable $callback Filter function
     * @return DMZ_Collection
     */
    public function filter($callback = NULL) {
        if ($callback === NULL) {
            $callback = function($item) {
                return !empty($item);
            };
        }
        return new DMZ_Collection(array_filter($this->items, $callback));
    }
    
    /**
     * Apply callback to each item
     *
     * @param callable $callback Map function
     * @return DMZ_Collection
     */
    public function map($callback) {
        return new DMZ_Collection(array_map($callback, $this->items));
    }
    
    /**
     * Execute callback for each item
     *
     * @param callable $callback Function to execute
     * @return DMZ_Collection
     */
    public function each($callback) {
        foreach ($this->items as $key => $item) {
            call_user_func($callback, $item, $key);
        }
        return $this;
    }
    
    /**
     * Merge with another collection
     *
     * @param DMZ_Collection $collection
     * @return DMZ_Collection
     */
    public function merge($collection) {
        $items = $collection instanceof DMZ_Collection ? 
                $collection->to_array() : 
                (array) $collection;
        return new DMZ_Collection(array_merge($this->items, $items));
    }
    
    /**
     * Convert to JSON
     *
     * @return string
     */
    public function to_json() {
        return json_encode($this->items);
    }
    
    /**
     * Get iterator for foreach loops (IteratorAggregate implementation)
     *
     * @return ArrayIterator
     */
    public function getIterator(): \Traversable {
        return new ArrayIterator($this->items);
    }
    
    /**
     * Get specific item by index
     *
     * @param int $index Array index
     * @return mixed
     */
    public function get($index) {
        return isset($this->items[$index]) ? $this->items[$index] : NULL;
    }
    
    /**
     * Check if collection contains a specific item or value
     *
     * @param mixed $value Value to search for
     * @param string $key Optional key to search in
     * @return bool
     */
    public function contains($value, $key = NULL) {
        if ($key !== NULL) {
            return in_array($value, $this->pluck($key));
        }
        return in_array($value, $this->items);
    }
    
    /**
     * Sort collection by callback or key
     *
     * @param callable|string $callback Sort function or key name
     * @param string $direction ASC or DESC
     * @return DMZ_Collection
     */
    public function sort($callback, $direction = 'ASC') {
        $items = $this->items;
        
        if (is_callable($callback)) {
            usort($items, $callback);
        } else {
            // Sort by key
            usort($items, function($a, $b) use ($callback, $direction) {
                $val_a = is_object($a) ? $a->{$callback} : $a[$callback];
                $val_b = is_object($b) ? $b->{$callback} : $b[$callback];
                
                $result = $val_a <=> $val_b;
                return strtoupper($direction) === 'DESC' ? -$result : $result;
            });
        }
        
        return new DMZ_Collection($items);
    }
    
    /**
     * Get unique items
     *
     * @param string $key Optional key to check uniqueness
     * @return DMZ_Collection
     */
    public function unique($key = NULL) {
        if ($key === NULL) {
            return new DMZ_Collection(array_unique($this->items, SORT_REGULAR));
        }
        
        $seen = array();
        $unique = array();
        
        foreach ($this->items as $item) {
            $value = is_object($item) ? $item->{$key} : $item[$key];
            if (!in_array($value, $seen)) {
                $seen[] = $value;
                $unique[] = $item;
            }
        }
        
        return new DMZ_Collection($unique);
    }
    
    /**
     * Chunk collection into smaller collections
     *
     * @param int $size Chunk size
     * @return DMZ_Collection Collection of collections
     */
    public function chunk($size) {
        $chunks = array_chunk($this->items, $size);
        return new DMZ_Collection(array_map(function($chunk) {
            return new DMZ_Collection($chunk);
        }, $chunks));
    }
    
    /**
     * Take first N items
     *
     * @param int $count Number of items to take
     * @return DMZ_Collection
     */
    public function take($count) {
        return new DMZ_Collection(array_slice($this->items, 0, $count));
    }
    
    /**
     * Skip first N items
     *
     * @param int $count Number of items to skip
     * @return DMZ_Collection
     */
    public function skip($count) {
        return new DMZ_Collection(array_slice($this->items, $count));
    }
    
    /**
     * Reverse collection order
     *
     * @return DMZ_Collection
     */
    public function reverse() {
        return new DMZ_Collection(array_reverse($this->items));
    }
    
    /**
     * Get number of items (count alias)
     * For DataMapper compatibility
     *
     * @return int
     */
    public function result_count() {
        return $this->count();
    }
    
    /**
     * Get values indexed by key
     *
     * @param string $key Key to index by
     * @return array
     */
    public function keyBy($key) {
        $result = array();
        foreach ($this->items as $item) {
            $key_value = is_object($item) ? $item->{$key} : $item[$key];
            $result[$key_value] = $item;
        }
        return $result;
    }
    
    /**
     * Group items by key
     *
     * @param string $key Key to group by
     * @return array
     */
    public function groupBy($key) {
        $result = array();
        foreach ($this->items as $item) {
            $key_value = is_object($item) ? $item->{$key} : $item[$key];
            if (!isset($result[$key_value])) {
                $result[$key_value] = array();
            }
            $result[$key_value][] = $item;
        }
        return $result;
    }
    
    /**
     * Magic method to proxy calls to the first item in the collection
     * 
     * This provides better compatibility when code expects a single model
     * but receives a collection. If the collection has exactly one item,
     * method calls are forwarded to that item.
     * 
     * IMPORTANT: This is a convenience feature. For better code clarity:
     * - Use ->first() to get a single model
     * - Use ->get() when you expect multiple results
     *
     * @param string $method Method name
     * @param array $args Method arguments
     * @return mixed
     * @throws BadMethodCallException
     */
    public function __call($method, $args) {
        // If collection has exactly one item, proxy to it
        if ($this->count() === 1) {
            $item = $this->first();
            if (is_object($item) && method_exists($item, $method)) {
                return call_user_func_array(array($item, $method), $args);
            }
        }
        
        // Provide helpful error message
        $itemCount = $this->count();
        $suggestion = '';
        
        if ($itemCount === 0) {
            $suggestion = "Collection is empty. Check if your query returned any results with ->exists() or ->count().";
        } elseif ($itemCount === 1) {
            $suggestion = "The collection has 1 item, but it doesn't have a method '{$method}'. Use ->first() to get the model and check available methods.";
        } else {
            $suggestion = "Collection has {$itemCount} items. Use ->first() to get a single model, or iterate: foreach (\$collection as \$item) { \$item->{$method}(); }";
        }
        
        throw new BadMethodCallException(
            "Method '{$method}' does not exist on DMZ_Collection. {$suggestion}"
        );
    }
    
    /**
     * Magic method to proxy property access to the first item
     * 
     * If the collection has exactly one item, property access is forwarded to that item.
     *
     * @param string $name Property name
     * @return mixed
     */
    public function __get($name) {
        // If collection has exactly one item, proxy to it
        if ($this->count() === 1) {
            $item = $this->first();
            if (is_object($item) && isset($item->{$name})) {
                return $item->{$name};
            }
        }
        
        return NULL;
    }
    
    /**
     * Magic method to check if property exists on the first item
     *
     * @param string $name Property name
     * @return bool
     */
    public function __isset($name) {
        if ($this->count() === 1) {
            $item = $this->first();
            return is_object($item) && isset($item->{$name});
        }
        return FALSE;
    }

    // -------------------------------------------------------------------------
    // Bulk Operation Methods
    // -------------------------------------------------------------------------

	/**
	 * Save all models in the collection
	 * 
	 * Example:
	 *   $users->where('active', 1)->collect()->each(function($user) {
	 *       $user->last_login = time();
	 *   })->save_all();
	 *
	 * @return array Array of save results (TRUE/FALSE for each model)
	 */
	public function save_all() {
		$results = array();
		foreach ($this->items as $item) {
			if (is_object($item) && method_exists($item, 'save')) {
				$results[] = $item->save();
			}
		}
		return $results;
	}

	/**
	 * Delete all models in the collection
	 * 
	 * Example:
	 *   $old_posts->where('created <', strtotime('-1 year'))->collect()->delete_all();
	 *
	 * @return array Array of delete results (TRUE/FALSE for each model)
	 */
	public function delete_all() {
		$results = array();
		foreach ($this->items as $item) {
			if (is_object($item) && method_exists($item, 'delete')) {
				$results[] = $item->delete();
			}
		}
		return $results;
	}

	/**
	 * Get all items as array (alias for to_array for Laravel compatibility)
	 * 
	 * @return array
	 */
	public function all() {
		return $this->items;
	}

	/**
	 * Get items by key-value pairs
	 * 
	 * Example:
	 *   $posts->collect()->where_in_collection('status', 'published');
	 *
	 * @param string $key Field name
	 * @param mixed $value Value to match
	 * @return DMZ_Collection New filtered collection
	 */
	public function where_in_collection($key, $value) {
		return $this->filter(function($item) use ($key, $value) {
			if (is_object($item) && isset($item->{$key})) {
				return $item->{$key} === $value;
			} elseif (is_array($item) && isset($item[$key])) {
				return $item[$key] === $value;
			}
			return FALSE;
		});
	}

	/**
	 * Find a model by its ID
	 * 
	 * Example:
	 *   $post = $posts->collect()->find(5);
	 *
	 * @param mixed $id ID value to find
	 * @param string $id_field Name of the ID field (default: 'id')
	 * @return mixed The found item or NULL
	 */
	public function find($id, $id_field = 'id') {
		foreach ($this->items as $item) {   
			if (is_object($item) && isset($item->{$id_field}) && $item->{$id_field} == $id) {
				return $item;
			} elseif (is_array($item) && isset($item[$id_field]) && $item[$id_field] == $id) {
				return $item;
			}
		}
		return NULL;
	}

	/**
	 * Get a collection of IDs
	 * 
	 * Convenience method, same as pluck('id')
	 * 
	 * Example:
	 *   $ids = $posts->collect()->ids();
	 *
	 * @param string $id_field Name of the ID field (default: 'id')
	 * @return array Array of IDs
	 */
	public function ids($id_field = 'id') {
		return $this->pluck($id_field);
	}

	// -------------------------------------------------------------------------
	// Soft Delete Methods (DataMapper 2.0)
	// -------------------------------------------------------------------------

    /**
     * Include soft-deleted records in query results.
     *
     * @return DataMapper Returns self for method chaining
     */
    public function withSoftDeleted() {
        $this->model->_dm_with_softdeleted = TRUE;
        $this->model->_dm_only_softdeleted = FALSE;
        // Maintain legacy flags for older integrations.
        $this->model->_dm_with_deleted = TRUE;
        $this->model->_dm_only_deleted = FALSE;
        return $this->model;
    }

    /**
     * @deprecated Use withSoftDeleted() instead.
     */
    public function withDeleted() {
        return $this->withSoftDeleted();
    }

    /**
     * @deprecated Use withSoftDeleted() instead.
     */
    public function with_softdeleted() {
        return $this->withSoftDeleted();
    }

    /**
     * @deprecated Use withSoftDeleted() instead.
     */
    public function with_deleted() {
        return $this->withSoftDeleted();
    }

    /**
     * Get only soft-deleted records.
     *
     * @return DataMapper Returns self for method chaining
     */
    public function onlySoftDeleted() {
        $this->model->_dm_only_softdeleted = TRUE;
        $this->model->_dm_with_softdeleted = FALSE;
        $this->model->_dm_only_deleted = TRUE;
        $this->model->_dm_with_deleted = FALSE;
        return $this->model;
    }

    /**
     * @deprecated Use onlySoftDeleted() instead.
     */
    public function onlyDeleted() {
        return $this->onlySoftDeleted();
    }

    /**
     * @deprecated Use onlySoftDeleted() instead.
     */
    public function only_softdeleted() {
        return $this->onlySoftDeleted();
    }

    /**
     * @deprecated Use onlySoftDeleted() instead.
     */
    public function only_deleted() {
        return $this->onlySoftDeleted();
    }

    /**
     * Exclude soft-deleted records (default behavior).
     *
     * @return DataMapper Returns self for method chaining
     */
    public function withoutSoftDeleted() {
        $this->model->_dm_with_softdeleted = FALSE;
        $this->model->_dm_only_softdeleted = FALSE;
        $this->model->_dm_with_deleted = FALSE;
        $this->model->_dm_only_deleted = FALSE;
        return $this->model;
    }

    /**
     * @deprecated Use withoutSoftDeleted() instead.
     */
    public function withoutDeleted() {
        return $this->withoutSoftDeleted();
    }

    /**
     * @deprecated Use withoutSoftDeleted() instead.
     */
    public function without_softdeleted() {
        return $this->withoutSoftDeleted();
    }

    /**
     * @deprecated Use withoutSoftDeleted() instead.
     */
    public function without_deleted() {
        return $this->withoutSoftDeleted();
    }
}

/**
 * Database Query Constraint Wrapper
 * 
 * Provides a DataMapper-style interface for applying constraints to CI's DB query builder.
 * Used primarily for many-to-many eager loading where the code works directly with DB queries.
 * 
 * @package DataMapper
 * @category Extensions
 * @author DataMapper Team
 */
class DMZ_DB_Constraint_Wrapper {
	
	/**
	 * CI Database query builder instance
	 * @var CI_DB_query_builder
	 */
	protected $db;
	
	/**
	 * Table prefix for qualified column names
	 * @var string
	 */
	protected $table_prefix;
	
    /**
     * Soft delete scope state
     * @var string 'active'|'with_softdeleted'|'only_softdeleted'
     */
    protected $soft_delete_scope = 'active';
	
	/**
	 * Constructor
	 * 
	 * @param CI_DB_query_builder $db Database query builder
	 * @param string $table_prefix Table name to prefix columns (e.g., 'users' for 'users.active')
	 */
	public function __construct($db, $table_prefix = '') {
		$this->db = $db;
		$this->table_prefix = $table_prefix;
	}
	
	/**
	 * Add WHERE clause
	 * 
	 * Automatically prefixes column names with table name for many-to-many joins.
	 * 
	 * @param string|array $key Column name or associative array of key => value
	 * @param mixed $value Value to compare (optional if $key is array)
	 * @param bool $escape Whether to escape values (default TRUE)
	 * @return DMZ_DB_Constraint_Wrapper
	 */
	public function where($key, $value = NULL, $escape = TRUE) {
		// Prefix column name with table if not already qualified
		if (is_string($key) && !empty($this->table_prefix) && strpos($key, '.') === FALSE) {
			$key = $this->table_prefix . '.' . $key;
		}
		
		$this->db->where($key, $value, $escape);
		return $this;
	}
	
	/**
	 * Add WHERE IN clause
	 * 
	 * @param string $key Column name
	 * @param array $values Array of values
	 * @param bool $escape Whether to escape values
	 * @return DMZ_DB_Constraint_Wrapper
	 */
	public function where_in($key, $values, $escape = TRUE) {
		if (!empty($this->table_prefix) && strpos($key, '.') === FALSE) {
			$key = $this->table_prefix . '.' . $key;
		}
		
		$this->db->where_in($key, $values, $escape);
		return $this;
	}
	
	/**
	 * Add WHERE NOT IN clause
	 * 
	 * @param string $key Column name
	 * @param array $values Array of values
	 * @param bool $escape Whether to escape values
	 * @return DMZ_DB_Constraint_Wrapper
	 */
	public function where_not_in($key, $values, $escape = TRUE) {
		if (!empty($this->table_prefix) && strpos($key, '.') === FALSE) {
			$key = $this->table_prefix . '.' . $key;
		}
		
		$this->db->where_not_in($key, $values, $escape);
		return $this;
	}
	
	/**
	 * Add OR WHERE clause
	 * 
	 * @param string|array $key Column name or associative array
	 * @param mixed $value Value to compare
	 * @param bool $escape Whether to escape values
	 * @return DMZ_DB_Constraint_Wrapper
	 */
	public function or_where($key, $value = NULL, $escape = TRUE) {
		if (is_string($key) && !empty($this->table_prefix) && strpos($key, '.') === FALSE) {
			$key = $this->table_prefix . '.' . $key;
		}
		
		$this->db->or_where($key, $value, $escape);
		return $this;
	}
	
	/**
	 * Add ORDER BY clause
	 * 
	 * @param string $orderby Column name
	 * @param string $direction Direction (ASC or DESC)
	 * @param bool $escape Whether to escape column name
	 * @return DMZ_DB_Constraint_Wrapper
	 */
	public function order_by($orderby, $direction = '', $escape = TRUE) {
		if (!empty($this->table_prefix) && strpos($orderby, '.') === FALSE && strpos($orderby, ',') === FALSE) {
			$orderby = $this->table_prefix . '.' . $orderby;
		}
		
		$this->db->order_by($orderby, $direction, $escape);
		return $this;
	}
	
	/**
	 * Add LIMIT clause
	 * 
	 * @param int $value Number of rows
	 * @param int $offset Offset (optional)
	 * @return DMZ_DB_Constraint_Wrapper
	 */
	public function limit($value, $offset = NULL) {
		$this->db->limit($value, $offset);
		return $this;
	}
	
	/**
	 * Add GROUP BY clause
	 * 
	 * @param string $by Column name
	 * @return DMZ_DB_Constraint_Wrapper
	 */
	public function group_by($by) {
		if (!empty($this->table_prefix) && strpos($by, '.') === FALSE) {
			$by = $this->table_prefix . '.' . $by;
		}
		
		$this->db->group_by($by);
		return $this;
	}
	
	/**
	 * Add HAVING clause
	 * 
	 * @param string $key Column name
	 * @param mixed $value Value to compare
	 * @param bool $escape Whether to escape values
	 * @return DMZ_DB_Constraint_Wrapper
	 */
	public function having($key, $value = NULL, $escape = TRUE) {
		if (!empty($this->table_prefix) && strpos($key, '.') === FALSE) {
			$key = $this->table_prefix . '.' . $key;
		}
		
		$this->db->having($key, $value, $escape);
		return $this;
	}
	
	// ============================================================
	// Soft Delete Methods
	// ============================================================
	
    /**
     * Include soft-deleted records (disable deleted_at filter)
     * 
     * @return DMZ_DB_Constraint_Wrapper
     */
    public function withSoftDeleted() {
        $this->soft_delete_scope = 'with_softdeleted';
		return $this;
	}

    /**
     * @deprecated Use withSoftDeleted() instead.
     */
    public function withDeleted() {
        return $this->withSoftDeleted();
    }

    /**
     * @deprecated Use withSoftDeleted() instead.
     */
    public function with_softdeleted() {
        return $this->withSoftDeleted();
    }

    /**
     * @deprecated Use withSoftDeleted() instead.
     */
    public function with_deleted() {
        return $this->withSoftDeleted();
    }
    
    /**
     * Exclude soft-deleted records (default behavior)
	 * Apply deleted_at IS NULL filter
	 * 
	 * @return DMZ_DB_Constraint_Wrapper
	 */
    public function withoutSoftDeleted() {
        $this->soft_delete_scope = 'active';
        return $this;
    }

    /**
     * @deprecated Use withoutSoftDeleted() instead.
     */
    public function withoutDeleted() {
        return $this->withoutSoftDeleted();
    }

    /**
     * @deprecated Use withoutSoftDeleted() instead.
     */
    public function without_softdeleted() {
        return $this->withoutSoftDeleted();
    }

    /**
     * @deprecated Use withoutSoftDeleted() instead.
     */
    public function without_deleted() {
        return $this->withoutSoftDeleted();
    }
    
    /**
     * Get ONLY soft-deleted records
	 * Apply deleted_at IS NOT NULL filter
	 * 
	 * @return DMZ_DB_Constraint_Wrapper
	 */
    public function onlySoftDeleted() {
        $this->soft_delete_scope = 'only_softdeleted';
		return $this;
	}

    /**
     * @deprecated Use onlySoftDeleted() instead.
     */
    public function onlyDeleted() {
        return $this->onlySoftDeleted();
    }

    /**
     * @deprecated Use onlySoftDeleted() instead.
     */
    public function only_softdeleted() {
        return $this->onlySoftDeleted();
    }

    /**
     * @deprecated Use onlySoftDeleted() instead.
     */
    public function only_deleted() {
        return $this->onlySoftDeleted();
    }
	
	/**
	 * Get the current soft delete scope state
	 * Used internally by eager loading to apply the correct WHERE clause
	 * 
     * @return string 'active'|'with_softdeleted'|'only_softdeleted'
	 */
	public function getSoftDeleteScope() {
		return $this->soft_delete_scope;
	}
	
	/**
	 * Magic method to proxy other methods to the DB instance
    * 
    * Provides access to other CI DB query builder methods for chaining.
    * 
	 * @param string $method Method name
	 * @param array $args Method arguments
	 * @return mixed
	 */
	public function __call($method, $args) {
		$result = call_user_func_array(array($this->db, $method), $args);
		
		// Return self for chaining if DB returned itself
		if ($result === $this->db) {
			return $this;
		}
		
		return $result;
	}
}

/* End of file querybuilder.php */
/* Location: ./application/datamapper/querybuilder.php */