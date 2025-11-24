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

    public function testCamelCasePropertiesEnableSoftDelete(): void
    {
        $model = new SoftDeleteModelStub();
        $model->id = 42;

        $this->assertNull($model->archived_at);

        $model->delete();

        $this->assertSame('fake-timestamp', $model->archived_at);
        $this->assertTrue($model->saveCalled);
    }

    public function testApplyScopeAddsPredicateForCustomColumn(): void
    {
        $model = new SoftDeleteModelStub();

        $model->applySoftDeleteScope();

        $this->assertCount(1, $model->whereLog);
        $this->assertSame('archived_at', $model->whereLog[0][0]);
        $this->assertNull($model->whereLog[0][1]);
    }

    public function testIncludeDeletedSkipsScope(): void
    {
        $model = new SoftDeleteModelStub();

        $model->with_softdeleted();
        $model->applySoftDeleteScope();

        $this->assertSame(array(), $model->whereLog);
    }

    public function testCamelCaseHelperStillWorks(): void
    {
        $model = new SoftDeleteModelStub();

        $model->withSoftDeleted();
        $model->applySoftDeleteScope();

        $this->assertSame(array(), $model->whereLog);
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

        $model->applySoftDeleteScope();

        $this->assertSame(array(), $model->whereLog);
    }
}

class SoftDeleteModelStub extends DataMapper
{
    use SoftDeletes;

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
    public $saveCalled = FALSE;

    /** @var array<int, array<int, mixed>> */
    public $whereLog = array();

    public function __construct()
    {
        $this->db = new SoftDeleteDbStub();
        $this->all = array();
        $this->deletedAtColumn = 'archived_at';
    }

    public function save($object = '', $related_field = '')
    {
        $this->saveCalled = TRUE;
        return TRUE;
    }

    protected function _fresh_timestamp()
    {
        return 'fake-timestamp';
    }

    public function where($field, $value = NULL, $escape_or_operator = TRUE)
    {
        $entry = array($field, $value, $escape_or_operator);
        $this->whereLog[] = $entry;
        $this->db->qb_where[] = $entry;
        return $this;
    }

    public function applySoftDeleteScope(): void
    {
        $this->_apply_soft_delete_scope();
    }
}

class SoftDeleteDisabledModelStub extends SoftDeleteModelStub
{
    /** @var bool|null */
    protected $softDelete = FALSE;
}

class SoftDeleteDbStub
{
    /** @var array<int, array<int, mixed>> */
    public $qb_where = array();

    public function dm_get($key)
    {
        if ($key === 'qb_where') {
            return $this->qb_where;
        }

        return array();
    }

    public function __call($name, $arguments)
    {
        // Allow DataMapper to call into the stub without side effects.
        return NULL;
    }
}
