<?php

require_once APPPATH . 'datamapper/SoftDeletes.php';

use PHPUnit\Framework\TestCase;

class SoftDeleteBehaviorTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private $originalConfig = array();

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConfig = DataMapper::$config;
        DataMapper::$config['soft_delete'] = FALSE;
        DataMapper::$config['deleted_at_column'] = 'deleted_at';
        DataMapper::$config['timestamps'] = FALSE;
    }

    protected function tearDown(): void
    {
        DataMapper::$config = $this->originalConfig;
        parent::tearDown();
    }

    public function testSnakeCasePropertyEnablesSoftDelete(): void
    {
        $model = new SoftDeleteModelStub();
        $model->id = 42;

        $this->assertNull($model->archived_at);

        $model->delete();

        $this->assertSame('fake-timestamp', $model->archived_at);
        $this->assertTrue($model->save_called);
    }

    public function testLegacyCamelCasePropertyStillSupported(): void
    {
        $model = new SoftDeleteModelStub();
        $model->id = 42;
        $model->set_deleted_at_column(NULL);
        $model->deletedAtColumn = 'archived_at';

        $model->delete();

        $this->assertSame('fake-timestamp', $model->archived_at);
        $this->assertTrue($model->save_called);
    }

    public function testApplyScopeAddsPredicateForCustomColumn(): void
    {
        $model = new SoftDeleteModelStub();

        $model->apply_soft_delete_scope();

        $this->assertCount(1, $model->where_log);
        $this->assertSame('archived_at', $model->where_log[0][0]);
        $this->assertNull($model->where_log[0][1]);
    }

    public function testIncludeDeletedSkipsScope(): void
    {
        $model = new SoftDeleteModelStub();

        $model->with_softdeleted();
        $model->apply_soft_delete_scope();

        $this->assertSame(array(), $model->where_log);
    }

    public function testCamelCaseHelperStillWorks(): void
    {
        $model = new SoftDeleteModelStub();

        $model->withSoftDeleted();
        $model->apply_soft_delete_scope();

        $this->assertSame(array(), $model->where_log);
    }

    public function testTrashedRecognisesCamelCaseColumn(): void
    {
        $model = new SoftDeleteModelStub();
        $model->archived_at = '2025-11-19 00:00:00';

        $this->assertTrue($model->trashed());
    }

    public function testExplicitDisableByModelSkipsScope(): void
    {
        $model = new SoftDeleteDisabledModelStub();

        $model->apply_soft_delete_scope();

        $this->assertSame(array(), $model->where_log);
    }

    public function testTraitDoesNotEnableSoftDeleteWritesByDefault(): void
    {
        $model = new SoftDeleteDefaultWriteModelStub();
        $model->id = 42;

        $this->assertFalse($model->soft_delete_writes_enabled());
        $this->assertFalse($model->restore());
        $this->assertTrue($model->delete());
        $this->assertTrue($model->db->delete_called);
    }
}

class SoftDeleteModelStub extends DataMapper
{
    use SoftDeletes;

    protected $soft_delete_writes = TRUE;

    public $model = 'soft_delete_stub';
    public $table = 'soft_delete_stubs';
    public $primary_key = 'id';
    public $fields = array('id', 'archived_at', 'updated_at');

    /** @var bool */
    public $timestamps = FALSE;

    /** @var string|null */
    public $archived_at = NULL;

    /** @var string|null */
    public $updated_at = NULL;

    /** @var bool */
    public $save_called = FALSE;

    /** @var array<int, array<int, mixed>> */
    public $where_log = array();

    /** @var string|null */
    public $deletedAtColumn = NULL;

    public function __construct()
    {
        $this->db = new SoftDeleteDbStub();
        $this->all = array();
        $this->deleted_at_column = 'archived_at';
    }

    public function save($object = '', $related_field = '')
    {
        $this->save_called = TRUE;
        return TRUE;
    }

    protected function _fresh_timestamp()
    {
        return 'fake-timestamp';
    }

    public function where($field, $value = NULL, $escape_or_operator = TRUE)
    {
        $entry = array($field, $value, $escape_or_operator);
        $this->where_log[] = $entry;
        $this->db->qb_where[] = $entry;
        return $this;
    }

    public function set_deleted_at_column($column): void
    {
        $this->deleted_at_column = $column;
    }

    public function apply_soft_delete_scope(): void
    {
        $this->_apply_soft_delete_scope();
    }
}

class SoftDeleteDefaultWriteModelStub extends SoftDeleteModelStub
{
    protected $soft_delete_writes = FALSE;

    public function __construct()
    {
        parent::__construct();
    }

    public function clear()
    {
        return $this;
    }
}

class SoftDeleteDisabledModelStub extends SoftDeleteModelStub
{
    /**
     * Explicitly disable soft deletes for this model
     * @var bool
     */
    protected $soft_delete = FALSE;
    
    public function __construct()
    {
        parent::__construct();
    }
}

class SoftDeleteDbStub
{
    /** @var array<int, array<int, mixed>> */
    public $qb_where = array();
    public $delete_called = FALSE;

    public function dm_get($key)
    {
        if ($key === 'qb_where') {
            return $this->qb_where;
        }

        return array();
    }

    public function delete($table)
    {
        $this->delete_called = TRUE;
        return TRUE;
    }

    public function __call($name, $arguments)
    {
        // Allow DataMapper to call into the stub without side effects.
        return NULL;
    }
}
