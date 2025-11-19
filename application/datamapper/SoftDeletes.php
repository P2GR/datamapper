<?php

/**
 * SoftDeletes Trait for DataMapper
 *
 * Provides a thin compatibility layer that enables soft deletes when the trait
 * is used while forwarding to DataMapper's native implementation. It also
 * exposes camelCase helper aliases to mirror Laravel-style method names.
 *
 * @package DataMapper
 * @category Traits
 * @version 2.0
 */
trait SoftDeletes
{
	/**
	 * Default soft delete toggle for trait consumers (camelCase for BC).
	 *
	 * @var bool|null
	 */
	protected $softDelete = TRUE;

	/**
	 * Optional camelCase column override retained for backwards compatibility.
	 *
	 * @var string|null
	 */
	protected $deletedAtColumn = 'deleted_at';

	/**
	 * Normalise configuration before delegating to the core implementation.
	 *
	 * @return void
	 */
	protected function syncSoftDeleteConfiguration()
	{
		if ($this->soft_delete === NULL && $this->softDelete !== NULL)
		{
			$this->soft_delete = (bool) $this->softDelete;
		}

		if (($this->deleted_at_column === NULL || $this->deleted_at_column === '') && !empty($this->deletedAtColumn))
		{
			$this->deleted_at_column = $this->deletedAtColumn;
		}
	}

	/**
	 * Allow invoking from legacy bootstrapping hooks without side effects.
	 *
	 * @return void
	 */
	protected function _soft_delete_boot()
	{
		// Intentional no-op: DataMapper applies the scope internally.
	}

	/**
	 * Proxy delete() to parent while ensuring configuration is in sync.
	 *
	 * @param mixed $object
	 * @param string $related_field
	 * @return bool
	 */
	public function delete($object = '', $related_field = '')
	{
		$this->syncSoftDeleteConfiguration();
		return parent::delete($object, $related_field);
	}

	/**
	 * Proxy restore() to parent implementation.
	 *
	 * @return bool
	 */
	public function restore()
	{
		$this->syncSoftDeleteConfiguration();
		return parent::restore();
	}

	/**
	 * Proxy trashed() to parent implementation.
	 *
	 * @return bool
	 */
	public function trashed()
	{
		$this->syncSoftDeleteConfiguration();
		return parent::trashed();
	}

	/**
	 * Provide camelCase alias for with_softdeleted().
	 *
	 * @return $this
	 */
	public function withSoftDeleted()
	{
		$this->syncSoftDeleteConfiguration();
		return $this->with_softdeleted();
	}

	/**
	 * Provide camelCase alias for only_softdeleted().
	 *
	 * @return $this
	 */
	public function onlySoftDeleted()
	{
		$this->syncSoftDeleteConfiguration();
		return $this->only_softdeleted();
	}

	/**
	 * Provide camelCase alias for without_softdeleted().
	 *
	 * @return $this
	 */
	public function withoutSoftDeleted()
	{
		$this->syncSoftDeleteConfiguration();
		return $this->without_softdeleted();
	}

	/**
	 * @deprecated Use withSoftDeleted() instead.
	 */
	public function withDeleted()
	{
		return $this->withSoftDeleted();
	}

	/**
	 * @deprecated Use onlySoftDeleted() instead.
	 */
	public function onlyDeleted()
	{
		return $this->onlySoftDeleted();
	}

	/**
	 * @deprecated Use withoutSoftDeleted() instead.
	 */
	public function withoutDeleted()
	{
		return $this->withoutSoftDeleted();
	}

	/**
	 * Provide camelCase alias for force_delete().
	 *
	 * @return bool
	 */
	public function forceDelete()
	{
		$this->syncSoftDeleteConfiguration();
		return $this->force_delete();
	}

	/**
	 * Expose the resolved deleted_at column for consumers expecting camelCase.
	 *
	 * @return string|null
	 */
	public function getDeletedAtColumn()
	{
		$this->syncSoftDeleteConfiguration();
		return $this->deleted_at_column !== NULL && $this->deleted_at_column !== ''
			? $this->deleted_at_column
			: (isset(DataMapper::$config['deleted_at_column']) ? DataMapper::$config['deleted_at_column'] : NULL);
	}
}
