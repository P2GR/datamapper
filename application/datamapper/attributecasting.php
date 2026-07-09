<?php
/**
 * DataMapper ORM - Attribute Casting Trait
 * 
 * Provides modern attribute casting, accessors, and mutators
 * while maintaining full backward compatibility with legacy models.
 * 
 * @package     DataMapper
 * @category    DataMapper ORM
 * @author      DataMapper Development Team
 * @license     MIT License
 * @link        https://github.com/datamapper/datamapper
 * @version     2.0.0
 */

/**
 * Attribute Casting Trait
 * 
 * Add this trait to your models to enable modern casting and accessor/mutator support.
 * Models without this trait continue to work exactly as before.
 * 
 * Example usage:
 * 
 * class User extends DataMapper {
 *     use DMZ_AttributeCasting;
 *     
 *     protected array $casts = [
 *         'id' => 'int',
 *         'age' => 'int',
 *         'salary' => 'float',
 *         'is_active' => 'bool',
 *         'settings' => 'array',
 *         'created_at' => 'datetime'
 *     ];
 *     
 *     // Accessor - transforms value when reading
 *     public function getFullNameAttribute(): string {
 *         return $this->first_name . ' ' . $this->last_name;
 *     }
 *     
 *     // Mutator - transforms value when writing
 *     public function setEmailAttribute(string $value): void {
 *         $this->{$this->_field_tracking['email']} = strtolower($value);
 *     }
 * }
 */
trait DMZ_AttributeCasting
{
    /**
     * Define attribute casting rules
     * 
    * Supported types (prefer short names; long-form aliases remain for BC):
    * - 'int' (alias: 'integer')
    * - 'float' (aliases: 'double', 'real')
    * - 'bool' (alias: 'boolean')
     * - 'string'
     * - 'array' (JSON encode/decode)
     * - 'json' (alias for array)
     * - 'datetime' (DateTime object)
     * - 'date' (DateTime object, date only)
     * - 'timestamp' (Unix timestamp to DateTime)
     * 
     * @var array
     */
    protected array $casts = [];
    
    /**
     * Cache for accessor/mutator method existence checks
     * 
     * @var array
     */
    private static array $_accessor_cache = [];
    private static array $_mutator_cache = [];
    
    /**
     * Get an attribute value with casting and accessor support
     * 
     * Priority:
     * 1. Check for getXAttribute() accessor method
     * 2. Apply casting if defined in $casts
     * 3. Return raw value (backward compatible)
     * 
     * @param string $key Attribute name
     * @return mixed
     */
    public function __get($key)
    {
        // Check for accessor method first (highest priority)
        if ($this->has_get_accessor($key)) {
            return $this->get_attribute_value($key);
        }
        
        // Get the raw value from parent DataMapper
        $value = parent::__get($key);
        
        // Apply casting if defined
        if ($this->has_cast($key)) {
            return $this->cast_attribute($key, $value);
        }
        
        // Return raw value (backward compatible)
        return $value;
    }
    
    /**
     * Set an attribute value with mutator and reverse casting support
     * 
     * Priority:
     * 1. Check for setXAttribute() mutator method
     * 2. Apply reverse casting if defined in $casts
     * 3. Set raw value (backward compatible)
     * 
     * @param string $key Attribute name
     * @param mixed $value Value to set
     */
    public function __set($key, $value)
    {
        // Check for mutator method first (highest priority)
        if ($this->has_set_mutator($key)) {
            $this->set_attribute_value($key, $value);
            return;
        }
        
        // Apply reverse casting if defined
        if ($this->has_cast($key)) {
            $value = $this->reverse_cast_attribute($key, $value);
        }
        
        // Set via parent DataMapper (backward compatible)
        parent::__set($key, $value);
    }
    
    /**
     * Check if an attribute has a cast defined
     * 
     * @param string $key Attribute name
     * @return bool
     */
    protected function has_cast(string $key): bool
    {
        return isset($this->casts[$key]);
    }
    
    /**
     * Get the cast type for an attribute
     * 
     * @param string $key Attribute name
     * @return string|null
     */
    protected function get_cast_type(string $key): ?string
    {
        return $this->casts[$key] ?? null;
    }
    
    /**
     * Check if a get accessor exists for an attribute
     * 
     * @param string $key Attribute name
     * @return bool
     */
    protected function has_get_accessor(string $key): bool
    {
        $class = get_class($this);
        $cacheKey = $class . '::' . $key;
        
        if (!isset(self::$_accessor_cache[$cacheKey])) {
            $method = 'get' . $this->studly_case($key) . 'Attribute';
            self::$_accessor_cache[$cacheKey] = method_exists($this, $method);
        }
        
        return self::$_accessor_cache[$cacheKey];
    }
    
    /**
     * Check if a set mutator exists for an attribute
     * 
     * @param string $key Attribute name
     * @return bool
     */
    protected function has_set_mutator(string $key): bool
    {
        $class = get_class($this);
        $cacheKey = $class . '::' . $key;
        
        if (!isset(self::$_mutator_cache[$cacheKey])) {
            $method = 'set' . $this->studly_case($key) . 'Attribute';
            self::$_mutator_cache[$cacheKey] = method_exists($this, $method);
        }
        
        return self::$_mutator_cache[$cacheKey];
    }
    
    /**
     * Get an attribute value using its accessor
     * 
     * @param string $key Attribute name
     * @return mixed
     */
    protected function get_attribute_value(string $key): mixed
    {
        $method = 'get' . $this->studly_case($key) . 'Attribute';
        return $this->{$method}();
    }
    
    /**
     * Set an attribute value using its mutator
     * 
     * @param string $key Attribute name
     * @param mixed $value Value to set
     */
    protected function set_attribute_value(string $key, mixed $value): void
    {
        $method = 'set' . $this->studly_case($key) . 'Attribute';
        $this->{$method}($value);
    }
    
    /**
     * Cast an attribute to its defined type
     * 
     * @param string $key Attribute name
     * @param mixed $value Raw value
     * @return mixed Casted value
     */
    protected function cast_attribute(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        
        $castType = $this->get_cast_type($key);
        
        return match($castType) {
            'int', 'integer' => (int) $value,
            'float', 'double', 'real' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string' => (string) $value,
            'array', 'json' => $this->from_json($value),
            'datetime' => $this->as_date_time($value),
            'date' => $this->as_date($value),
            'timestamp' => $this->as_date_time($value),
            default => $value
        };
    }
    
    /**
     * Reverse cast an attribute for storage
     * 
     * @param string $key Attribute name
     * @param mixed $value Value to reverse cast
     * @return mixed
     */
    protected function reverse_cast_attribute(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        
        $castType = $this->get_cast_type($key);
        
        return match($castType) {
            'array', 'json' => $this->as_json($value),
            'datetime', 'date' => $this->from_date_time($value),
            'timestamp' => $this->from_date_time($value),
            default => $value
        };
    }
    
    /**
     * Convert a JSON string to an array
     * 
     * @param mixed $value
     * @return array
     */
    protected function from_json(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }
    
    /**
     * Convert a value to JSON string
     * 
     * @param mixed $value
     * @return string
     */
    protected function as_json(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        
        return json_encode($value);
    }
    
    /**
     * Convert a value to DateTime object
     * 
     * @param mixed $value
     * @return DateTime|null
     */
    protected function as_date_time(mixed $value): ?DateTime
    {
        if ($value instanceof DateTime) {
            return $value;
        }
        
        if (is_numeric($value)) {
            // Unix timestamp
            $dt = new DateTime();
            $dt->setTimestamp((int) $value);
            return $dt;
        }
        
        if (is_string($value)) {
            try {
                return new DateTime($value);
            } catch (Exception $e) {
                return null;
            }
        }
        
        return null;
    }

    /**
     * Convert a value to date-only DateTime object
     * 
     * @param mixed $value
     * @return DateTime|null
     */
    protected function as_date(mixed $value): ?DateTime
    {
        $dt = $this->as_date_time($value);
        if ($dt) {
            $dt->setTime(0, 0, 0);
        }
        return $dt;
    }

    /**
     * Convert a DateTime object to string for storage
     * 
     * @param mixed $value
     * @return string|null
     */
    protected function from_date_time(mixed $value): ?string
    {
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        
        if (is_string($value)) {
            return $value;
        }
        
        return null;
    }

    /**
     * Convert snake_case to StudlyCase
     * 
     * @param string $value
     * @return string
     */
    protected function studly_case(string $value): string
    {
        $value = str_replace('_', ' ', $value);
        $value = ucwords($value);
        return str_replace(' ', '', $value);
    }
    
    /**
     * Get all attributes with casting applied
     * 
     * @return array
     */
    public function to_array(): array
    {
        $attributes = parent::__call('all_to_array', []);
        
        if (!is_array($attributes)) {
            return [];
        }
        
        // Apply casts to all attributes
        foreach ($this->casts as $key => $type) {
            if (isset($attributes[$key])) {
                $attributes[$key] = $this->cast_attribute($key, $attributes[$key]);
            }
        }
        
        // Apply accessors
        foreach (get_class_methods($this) as $method) {
            if (preg_match('/^get(.+)Attribute$/', $method, $matches)) {
                $key = $this->snake_case($matches[1]);
                if (!array_key_exists($key, $attributes)) {
                    $attributes[$key] = $this->get_attribute_value($key);
                }
            }
        }
        
        return $attributes;
    }

    public function toArray(): array
    {
        return $this->to_array();
    }
    
    /**
     * Convert StudlyCase to snake_case
     * 
     * @param string $value
     * @return string
     */
    protected function snake_case(string $value): string
    {
        $value = preg_replace('/([A-Z])/', '_$1', $value);
        return strtolower(ltrim($value, '_'));
    }
}
