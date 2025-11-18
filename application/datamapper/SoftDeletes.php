<?php

/**
 * SoftDeletes Trait for DataMapper
 * 
 * Provides soft delete functionality - records are marked as deleted instead of being removed.
 * 
 * Usage:
 * ```php
 * class Post extends DataMapper {
 *     use SoftDeletes;
 * }
 * 
 * $post = new Post();
 * $post->get_by_id(1);
 * $post->delete(); // Soft deletes (sets deleted_at)
 * 
 * // Query only non-deleted
 * $posts = (new Post())->get(); // Excludes soft-deleted
 * 
 * // Include soft-deleted
 * $all = (new Post())->withTrashed()->get();
 * 
 * // Only soft-deleted
 * $trashed = (new Post())->onlyTrashed()->get();
 * 
 * // Restore
 * $post->restore();
 * 
 * // Permanently delete
 * $post->forceDelete();
 * ```
 * 
 * Customization:
 * ```php
 * class Post extends DataMapper {
 *     use SoftDeletes;
 *     
 *     protected $softDelete = TRUE;            // Enable/disable (default: TRUE)
 *     protected $deletedAtColumn = 'deleted';  // Customize column name
 *     protected $timestampFormat = 'U';        // Unix timestamp or date format
 * }
 * ```
 * 
 * @package DataMapper
 * @category Traits
 * @version 2.0
 */
trait SoftDeletes
{
    /**
     * Enable or disable soft deletes
     * @var bool
     */
    protected $softDelete = TRUE;
    
    /**
     * The name of the "deleted at" column
     * @var string
     */
    protected $deletedAtColumn = 'deleted_at';
    
    /**
     * Whether to include trashed models in queries
     * @var bool
     */
    protected $_include_trashed = FALSE;
    
    /**
     * Whether to query only trashed models
     * @var bool
     */
    protected $_only_trashed = FALSE;
    
    /**
     * Boot the soft deletes trait for a model
     * Called automatically when trait is used
     * 
     * @return void
     */
    protected function _soft_delete_boot()
    {
        // Add global scope to exclude soft-deleted records by default
        // This is applied in the get() method via _apply_soft_delete_scope()
    }
    
    /**
     * Apply soft delete scope to queries (excludes deleted records by default)
     * This is called internally before executing queries
     * 
     * @return void
     */
    protected function _apply_soft_delete_scope()
    {
        if (!$this->_use_soft_delete()) {
            return;
        }
        
        // If explicitly including trashed or this is already a trashed-only query, skip scope
        if ($this->_include_trashed || $this->_only_trashed) {
            return;
        }
        
        // Add where clause to exclude soft-deleted records
        $deleted_column = $this->getDeletedAtColumn();
        $this->where($deleted_column . ' IS NULL', NULL, FALSE);
    }
    
    /**
     * Check if soft deletes should be used
     * 
     * @return bool
     */
    protected function _use_soft_delete(): bool
    {
        // Must have the trait and soft delete enabled
        return property_exists($this, 'softDelete') && $this->softDelete === TRUE;
    }
    
    /**
     * Override delete to perform soft delete
     * 
     * @param mixed $object
     * @param string $related_field
     * @return bool
     */
    public function delete($object = '', $related_field = ''): bool
    {
        // If relationships are being deleted, handle normally
        if (!empty($object) || !empty($related_field)) {
            return $this->_parent_delete($object, $related_field);
        }
        
        // If soft delete is disabled, perform hard delete
        if (!$this->_use_soft_delete()) {
            return $this->_parent_delete($object, $related_field);
        }
        
        // Perform soft delete
        return $this->_perform_soft_delete();
    }
    
    /**
     * Perform the actual soft delete
     * 
     * @return bool
     */
    protected function _perform_soft_delete(): bool
    {
        if (!$this->exists()) {
            return FALSE;
        }
        
        $deleted_column = $this->getDeletedAtColumn();
        $this->{$deleted_column} = $this->_fresh_timestamp_for_soft_delete();
        
        // Update the deleted_at column
        $this->db->where($this->primary_key, $this->id);
        $result = $this->db->update($this->table, array(
            $deleted_column => $this->{$deleted_column}
        ));
        
        return $result !== FALSE;
    }
    
    /**
     * Call parent delete method (for relationship deletes or force delete)
     * 
     * @param mixed $object
     * @param string $related_field
     * @return bool
     */
    protected function _parent_delete($object = '', $related_field = ''): bool
    {
        // Call DataMapper's original delete method
        return parent::delete($object, $related_field);
    }
    
    /**
     * Permanently delete the model from the database
     * 
     * @return bool
     */
    public function forceDelete(): bool
    {
        if (!$this->exists()) {
            return FALSE;
        }
        
        // Perform hard delete by calling parent method
        return $this->_parent_delete();
    }
    
    /**
     * Restore a soft-deleted model
     * 
     * @return bool
     */
    public function restore(): bool
    {
        if (!$this->_use_soft_delete()) {
            return FALSE;
        }
        
        if (!$this->trashed()) {
            return FALSE; // Not deleted
        }
        
        $deleted_column = $this->getDeletedAtColumn();
        $this->{$deleted_column} = NULL;
        
        // Update to set deleted_at to NULL
        $this->db->where($this->primary_key, $this->id);
        $result = $this->db->update($this->table, array(
            $deleted_column => NULL
        ));
        
        return $result !== FALSE;
    }
    
    /**
     * Determine if the model instance has been soft-deleted
     * 
     * @return bool
     */
    public function trashed(): bool
    {
        if (!$this->_use_soft_delete()) {
            return FALSE;
        }
        
        $deleted_column = $this->getDeletedAtColumn();
        return !empty($this->{$deleted_column});
    }
    
    /**
     * Include soft-deleted models in query results
     * 
     * @return $this
     */
    public function withTrashed(): self
    {
        $this->_include_trashed = TRUE;
        $this->_only_trashed = FALSE;
        return $this;
    }
    
    /**
     * Get only soft-deleted models
     * 
     * @return $this
     */
    public function onlyTrashed(): self
    {
        $this->_only_trashed = TRUE;
        $this->_include_trashed = FALSE;
        
        // Apply the constraint immediately
        if ($this->_use_soft_delete()) {
            $deleted_column = $this->getDeletedAtColumn();
            $this->where($deleted_column . ' IS NOT NULL', NULL, FALSE);
        }
        
        return $this;
    }
    
    /**
     * Get only non-deleted models (default behavior)
     * 
     * @return $this
     */
    public function withoutTrashed(): self
    {
        $this->_include_trashed = FALSE;
        $this->_only_trashed = FALSE;
        return $this;
    }
    
    /**
     * Get a fresh timestamp for soft delete
     * 
     * @return string|int
     */
    protected function _fresh_timestamp_for_soft_delete()
    {
        // If HasTimestamps trait is used, use its timestamp format
        if (method_exists($this, '_fresh_timestamp')) {
            return $this->_fresh_timestamp();
        }
        
        // Otherwise use default format
        $format = property_exists($this, 'timestampFormat') ? 
                  $this->timestampFormat : 
                  'Y-m-d H:i:s';
        
        if ($format === 'U') {
            return time(); // Unix timestamp
        }
        
        return date($format);
    }
    
    /**
     * Get the name of the "deleted at" column
     * 
     * @return string
     */
    public function getDeletedAtColumn(): string
    {
        return property_exists($this, 'deletedAtColumn') ? 
               $this->deletedAtColumn : 
               'deleted_at';
    }
    
    /**
     * Scope query to include trashed models
     * Can be used with DataMapper's query chaining
     * 
     * @return $this
     */
    public function with_trashed(): self
    {
        return $this->withTrashed();
    }
    
    /**
     * Scope query to only trashed models
     * Can be used with DataMapper's query chaining
     * 
     * @return $this
     */
    public function only_trashed(): self
    {
        return $this->onlyTrashed();
    }
}
