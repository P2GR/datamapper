<?php

namespace DataMapper\Traits {

/**
 * HasTimestamps Trait for DataMapper
 * 
 * Automatically manages created_at and updated_at timestamps on models.
 * Simply use this trait in your model to enable automatic timestamps.
 * 
 * Usage:
 * ```php
 * use DataMapper\Traits\HasTimestamps;
 *
 * class User extends DataMapper {
 *     use HasTimestamps;
 * }
 * ```
 * 
 * Customization:
 * ```php
 * use DataMapper\Traits\HasTimestamps;
 *
 * class User extends DataMapper {
 *     use HasTimestamps;
 *     
 *     protected $created_at_column = 'created'; // Customize column name
 *     protected $updated_at_column = 'modified'; // Customize column name
 *     protected $timestamp_format = 'U';       // Unix timestamp format
 * }
 * ```
 * 
 * @package DataMapper
 * @category Traits
 * @version 2.0
 */
trait HasTimestamps
{
    /**
     * The name of the "created at" column
     * @var string
     */
    protected $created_at_column = 'created_at';
    
    /**
     * The name of the "updated at" column
     * @var string
     */
    protected $updated_at_column = 'updated_at';
    
    /**
     * The format for timestamp values
     * Options: 'Y-m-d H:i:s' (MySQL), 'U' (Unix timestamp), 'c' (ISO 8601)
     * @var string
     */
    protected $timestamp_format = 'Y-m-d H:i:s';
    
    /**
     * Hook into DataMapper's save process to add timestamps
     * This method is called by DataMapper before validation
     * 
     * @return void
     */
    protected function _timestamp_before_save()
    {
        $timestamp = $this->_fresh_timestamp();

        $created_column = $this->get_created_at_column();
        $updated_column = $this->get_updated_at_column();

        // If this is a new record (no ID), set created_at
        if (!$this->exists()) {
            if (!isset($this->{$created_column}) || empty($this->{$created_column})) {
                $this->{$created_column} = $timestamp;
            }
        }

        // Always update updated_at on save
        $this->{$updated_column} = $timestamp;
    }
    
    /**
     * Get a fresh timestamp for the model
     * 
     * @return string|int
     */
    protected function _fresh_timestamp()
    {
        $format = $this->resolve_timestamp_format();
        
        if ($format === 'U') {
            return time(); // Unix timestamp
        }
        
        return date($format);
    }
    
    /**
     * Update only the updated_at timestamp without triggering full save
     * 
     * @return bool
     */
    public function touch(): bool
    {
        if (!$this->exists()) {
            return FALSE;
        }
        
        $updated_column = $this->get_updated_at_column();
        $this->{$updated_column} = $this->_fresh_timestamp();
        
        // Update only the timestamp column
        $this->db->where($this->primary_key, $this->id);
        $this->db->update($this->table, array($updated_column => $this->{$updated_column}));
        
        return TRUE;
    }
    
    /**
     * Get the name of the "created at" column
     * 
     * @return string
     */
    public function get_created_at_column(): string
    {
        return $this->resolve_timestamp_column('created_at_column', 'createdAtColumn', 'created_at');
    }
    
    /**
     * Get the name of the "updated at" column
     * 
     * @return string
     */
    public function get_updated_at_column(): string
    {
        return $this->resolve_timestamp_column('updated_at_column', 'updatedAtColumn', 'updated_at');
    }

    /**
     * Resolve timestamp column names while supporting legacy camelCase overrides.
     *
     * @param string $snake Property name expected in new snake_case style
     * @param string $legacy Legacy camelCase property name
     * @param string $default Default column value
     * @return string
     */
    protected function resolve_timestamp_column($snake, $legacy, $default)
    {
        if (property_exists($this, $snake) && !empty($this->{$snake})) {
            return $this->{$snake};
        }

        if (property_exists($this, $legacy) && !empty($this->{$legacy})) {
            return $this->{$legacy};
        }

        return $default;
    }

    /**
     * Resolve the timestamp format, honoring both snake_case and legacy camelCase properties.
     *
     * @return string
     */
    protected function resolve_timestamp_format()
    {
        if (property_exists($this, 'timestamp_format') && !empty($this->timestamp_format)) {
            return $this->timestamp_format;
        }

        if (property_exists($this, 'timestampFormat') && !empty($this->timestampFormat)) {
            return $this->timestampFormat;
        }

        return 'Y-m-d H:i:s';
    }
}

}

namespace {
    if ( ! trait_exists('HasTimestamps', FALSE))
    {
        class_alias('DataMapper\\Traits\\HasTimestamps', 'HasTimestamps');
    }
}
