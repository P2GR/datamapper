<?php

namespace DataMapper\Traits {

/**
 * SoftDeletes Trait for DataMapper
 *
 * Provides soft delete functionality for DataMapper models.
 * Simply use this trait in your model to enable soft deletes.
 *
 * Usage:
 * ```php
 * use DataMapper\Traits\SoftDeletes;
 *
 * class User extends DataMapper {
 *     use SoftDeletes;
 * }
 * ```
 *
 * Customize the deleted_at column name:
 * ```php
 * use DataMapper\Traits\SoftDeletes;
 *
 * class User extends DataMapper {
 *     use SoftDeletes;
 *     
 *     protected $deletedAtColumn = 'archived_at';
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
	 * The name of the "deleted at" column.
	 * Override in your model to customize.
	 *
	 * @var string
	 */
	protected $deletedAtColumn = 'deleted_at';
	/**
	 * Get the name of the "deleted at" column.
	 *
	 * @return string
	 */
	public function getDeletedAtColumn()
	{
		return $this->deletedAtColumn;
	}

	/**
	 * Proxy delete() to parent.
	 *
	 * @param mixed $object
	 * @param string $related_field
	 * @return bool
	 */
	public function delete($object = '', $related_field = '')
	{
		return parent::delete($object, $related_field);
	}

	/**
	 * Proxy restore() to parent implementation.
	 *
	 * @return bool
	 */
	public function restore()
	{
		return parent::restore();
	}

	/**
	 * Proxy trashed() to parent implementation.
	 *
	 * @return bool
	 */
	public function trashed()
	{
		return parent::trashed();
	}

}

}


namespace {
	if ( ! trait_exists('SoftDeletes', FALSE))
	{
		class_alias('DataMapper\\Traits\\SoftDeletes', 'SoftDeletes');
	}
}
