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
 *     protected $createdAtColumn = 'created'; // Customize column name
 *     protected $updatedAtColumn = 'modified'; // Customize column name
 *     protected $timestampFormat = 'U';       // Unix timestamp format
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
    protected $createdAtColumn = 'created_at';
    
    /**
     * The name of the "updated at" column
     * @var string
     */
    protected $updatedAtColumn = 'updated_at';
    
    /**
     * The format for timestamp values
     * Options: 'Y-m-d H:i:s' (MySQL), 'U' (Unix timestamp), 'c' (ISO 8601)
     * @var string
     */
    protected $timestampFormat = 'Y-m-d H:i:s';
    
    /**
     * Hook into DataMapper's save process to add timestamps
     * This method is called by DataMapper before validation
     * 
     * @return void
     */
    protected function _timestamp_before_save()
    {
        $timestamp = $this->_fresh_timestamp();
        
        // If this is a new record (no ID), set created_at
        if (!$this->exists()) {
            $created_column = $this->createdAtColumn;
            if (!isset($this->{$created_column}) || empty($this->{$created_column})) {
                $this->{$created_column} = $timestamp;
            }
        }
        
        // Always update updated_at on save
        $updated_column = $this->updatedAtColumn;
        $this->{$updated_column} = $timestamp;
    }
    
    /**
     * Get a fresh timestamp for the model
     * 
     * @return string|int
     */
    protected function _fresh_timestamp()
    {
        $format = property_exists($this, 'timestampFormat') ? 
                  $this->timestampFormat : 
                  'Y-m-d H:i:s';
        
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
        
        $updated_column = $this->updatedAtColumn;
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
    public function getCreatedAtColumn(): string
    {
        return property_exists($this, 'createdAtColumn') ? 
               $this->createdAtColumn : 
               'created_at';
    }
    
    /**
     * Get the name of the "updated at" column
     * 
     * @return string
     */
    public function getUpdatedAtColumn(): string
    {
        return property_exists($this, 'updatedAtColumn') ? 
               $this->updatedAtColumn : 
               'updated_at';
    }
}

}

namespace {
    if ( ! trait_exists('HasTimestamps', FALSE))
    {
        class_alias('DataMapper\\Traits\\HasTimestamps', 'HasTimestamps');
    }
}
