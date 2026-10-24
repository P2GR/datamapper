<?php

require_once APPPATH . 'datamapper/SoftDeletes.php';
require_once APPPATH . 'datamapper/querybuilder.php';

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

    public function testScopeCanBeAppliedAfterWithDeletedOnReusedModel(): void
    {
        $model = new SoftDeleteModelStub();

        $model->with_softdeleted();
        $model->apply_soft_delete_scope();
        $model->without_softdeleted();
        $model->apply_soft_delete_scope();

        $this->assertCount(1, $model->where_log);
        $this->assertSame('archived_at', $model->where_log[0][0]);
    }

    public function testEagerConstraintTracksCustomDeletedColumn(): void
    {
        $wrapper = new DMZ_DB_Constraint_Wrapper(new SoftDeleteDbStub(), 'soft_delete_stubs', 'archived_at');

        $wrapper->where('archived_at', NULL);

        $this->assertTrue($wrapper->has_soft_delete_where());
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

    public function testEagerScopeHonorsModelOptOut(): void
    {
        $builder = (new ReflectionClass('DMZ_QueryBuilder'))->newInstanceWithoutConstructor();
        $apply_scope = new ReflectionMethod($builder, '_apply_soft_delete_scope_to_db');
        $apply_scope->setAccessible(TRUE);
        $db = new SoftDeleteDbStub();

        $apply_scope->invoke($builder, $db, new SoftDeleteDisabledModelStub(), 'soft_delete_stubs');

        $this->assertSame(array(), $db->where_log);
    }

    public function testSqliteRowsRespectActiveAndDeletedScopes(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE soft_delete_stubs (id INTEGER PRIMARY KEY, archived_at TEXT NULL)');
        $pdo->exec("INSERT INTO soft_delete_stubs (id, archived_at) VALUES (1, NULL), (2, '2025-11-19 00:00:00')");

        $active = new SoftDeleteModelStub();
        $active->apply_soft_delete_scope();
        $this->assertSame('archived_at', $active->where_log[0][0]);
        $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM soft_delete_stubs WHERE archived_at IS NULL')->fetchColumn());

        $with_deleted = new SoftDeleteModelStub();
        $with_deleted->with_softdeleted();
        $with_deleted->apply_soft_delete_scope();
        $this->assertSame(array(), $with_deleted->where_log);
        $this->assertSame(2, (int) $pdo->query('SELECT COUNT(*) FROM soft_delete_stubs')->fetchColumn());

        $only_deleted = new SoftDeleteModelStub();
        $only_deleted->only_softdeleted();
        $only_deleted->apply_soft_delete_scope();
        $this->assertSame('archived_at IS NOT NULL', $only_deleted->where_log[0][0]);
        $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM soft_delete_stubs WHERE archived_at IS NOT NULL')->fetchColumn());
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

    public function testNonPublicSoftDeleteSettingsReadWithoutDeprecations(): void
    {
        $cases = array(
            array(new class extends SoftDeleteScopeSettingsStub { private $soft_delete = FALSE; private $softDelete = TRUE; }, FALSE),
            array(new class extends SoftDeleteScopeSettingsStub { protected $soft_delete = TRUE; protected $softDelete = FALSE; }, TRUE),
            array(new class extends SoftDeleteScopeSettingsStub { private $softDelete = TRUE; }, TRUE),
            array(new class extends SoftDeleteScopeSettingsStub { protected $softDelete = FALSE; }, FALSE),
            array(new class extends SoftDeleteWriteSettingsStub { private $soft_delete_writes = TRUE; private $softDeleteWrites = FALSE; }, TRUE),
            array(new class extends SoftDeleteWriteSettingsStub { protected $soft_delete_writes = FALSE; protected $softDeleteWrites = TRUE; }, FALSE),
            array(new class extends SoftDeleteWriteSettingsStub { private $softDeleteWrites = TRUE; }, TRUE),
            array(new class extends SoftDeleteWriteSettingsStub { protected $softDeleteWrites = FALSE; }, FALSE),
            array(new class extends SoftDeleteTraitWriteSettingsStub { private $soft_delete_writes = TRUE; private $softDeleteWrites = FALSE; }, TRUE),
            array(new class extends SoftDeleteTraitWriteSettingsStub { protected $soft_delete_writes = FALSE; protected $softDeleteWrites = TRUE; }, FALSE),
            array(new class extends SoftDeleteTraitWriteSettingsStub { private $softDeleteWrites = TRUE; }, TRUE),
            array(new class extends SoftDeleteTraitWriteSettingsStub { protected $softDeleteWrites = FALSE; }, FALSE),
        );
        $builder = new SoftDeleteQuerySettingsStub();

        set_error_handler(static function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_DEPRECATED | E_USER_DEPRECATED);
        try {
            foreach ($cases as list($model, $expected)) {
                $this->assertSame($expected, $model->readSetting());
                if ($model instanceof SoftDeleteScopeSettingsStub) {
                    $this->assertSame($expected, $builder->readSetting($model));
                }
            }
        } finally {
            restore_error_handler();
        }
    }
}

class SoftDeleteScopeSettingsStub extends DataMapper
{
    use SoftDeletes;
    public function __construct() {}
    public function readSetting() { return $this->_soft_delete_is_enabled(); }
}

class SoftDeleteWriteSettingsStub extends DataMapper
{
    public function __construct() {}
    public function readSetting() { return $this->_soft_delete_writes_enabled(); }
}

class SoftDeleteTraitWriteSettingsStub extends SoftDeleteWriteSettingsStub
{
    use SoftDeletes;
}

class SoftDeleteQuerySettingsStub extends DMZ_QueryBuilder
{
    public function __construct() {}
    public function readSetting($model) { return $this->_soft_delete_model_is_enabled($model); }
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
    public $where_log = array();

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

    public function where($field, $value = NULL, $escape = TRUE)
    {
        $this->where_log[] = array($field, $value, $escape);
        return $this;
    }

    public function __call($name, $arguments)
    {
        // Allow DataMapper to call into the stub without side effects.
        return NULL;
    }
}
