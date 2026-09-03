<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class DataMapperPrimaryKeySaveTest extends TestCase
{
    public function test_save_updates_using_the_configured_primary_key(): void
    {
        $model = new PrimaryKeySaveModelStub();
        $model->name = 'Updated name';

        $this->assertTrue($model->save());
        $this->assertSame(array(array('user_id', 42)), $model->db->where_calls);
        $this->assertSame('users', $model->db->updated_table);
        $this->assertSame(array('name' => 'Updated name'), $model->db->updated_data);
        $this->assertSame(0, $model->db->insert_calls);
    }

    public function test_save_treats_zero_configured_primary_key_as_existing(): void
    {
        $model = new PrimaryKeySaveModelStub();
        $model->user_id = 0;
        $model->stored->user_id = 0;
        $model->name = 'Zero key update';

        $this->assertTrue($model->save());
        $this->assertSame(array(array('user_id', 0)), $model->db->where_calls);
        $this->assertSame(0, $model->db->insert_calls);
    }

    public function test_save_assigns_generated_id_to_the_configured_primary_key(): void
    {
        $model = new PrimaryKeySaveModelStub();
        unset($model->user_id);
        $model->stored = new \stdClass();
        $model->name = 'New user';

        $this->assertTrue($model->save());
        $this->assertSame(73, $model->user_id);
        $this->assertSame(1, $model->db->insert_calls);
        $this->assertSame('users', $model->db->inserted_table);
        $this->assertSame(array('name' => 'New user'), $model->db->inserted_data);
    }

    public function test_before_create_key_assignment_does_not_change_insert_intent(): void
    {
        $model = new BeforeCreateKeyModelStub();
        unset($model->user_id);
        $model->stored = new \stdClass();
        $model->name = 'Assigned key';

        $this->assertTrue($model->save());
        $this->assertSame(1, $model->db->insert_calls);
        $this->assertSame(array(), $model->db->where_calls);
        $this->assertSame(88, $model->user_id);
        $this->assertSame(88, $model->db->inserted_data['user_id']);
    }

    public function test_failed_update_does_not_refresh_state_fire_after_events_or_invalidate_cache(): void
    {
        $model = new PrimaryKeySaveModelStub();
        $model->db->update_result = FALSE;
        $model->name = 'Rejected name';

        $this->assertFalse($model->save());
        $this->assertSame('Original name', $model->stored->name);
        $this->assertSame(array(), $model->after_events);
        $this->assertSame(0, $model->cache_invalidations);
    }

    public function test_failed_insert_does_not_fire_success_side_effects(): void
    {
        $model = new PrimaryKeySaveModelStub();
        unset($model->user_id);
        $model->stored = new \stdClass();
        $model->name = 'Rejected insert';
        $model->db->insert_result = FALSE;

        $this->assertFalse($model->save());
        $this->assertSame(array(), $model->after_events);
        $this->assertSame(0, $model->cache_invalidations);
    }

    public function test_transaction_rollback_restores_generated_key_and_skips_success_side_effects(): void
    {
        $model = new PrimaryKeySaveModelStub();
        unset($model->user_id);
        $model->stored = new \stdClass();
        $model->name = 'Rolled back insert';
        $model->auto_transaction = TRUE;
        $model->transaction_success = FALSE;

        $this->assertFalse($model->save());
        $this->assertNull($model->user_id);
        $this->assertSame(array(), $model->after_events);
        $this->assertSame(0, $model->cache_invalidations);
    }

    public function test_relationship_failure_prevents_success_side_effects(): void
    {
        $model = new PrimaryKeySaveModelStub();
        $model->name = 'Base update';
        $model->relationship_result = FALSE;

        $this->assertFalse($model->save(new \stdClass()));
        $this->assertSame('Base update', $model->stored->name);
        $this->assertSame(array(), $model->after_events);
        $this->assertSame(1, $model->cache_invalidations);
    }

    public function test_non_transactional_partial_insert_keeps_committed_generated_key(): void
    {
        $model = new PrimaryKeySaveModelStub();
        unset($model->user_id);
        $model->stored = new \stdClass();
        $model->name = 'Committed base insert';
        $model->relationship_result = FALSE;

        $this->assertFalse($model->save(new \stdClass()));
        $this->assertSame(73, $model->user_id);
        $this->assertSame(73, $model->stored->user_id);
        $this->assertSame(array(), $model->after_events);
        $this->assertSame(1, $model->cache_invalidations);
    }

    public function test_delete_uses_the_configured_primary_key(): void
    {
        $model = new PrimaryKeySaveModelStub();

        $this->assertTrue($model->delete());
        $this->assertSame(array(array('user_id', 42)), $model->db->where_calls);
        $this->assertSame('users', $model->db->deleted_table);
    }

    public function test_delete_propagates_database_failure(): void
    {
        $model = new PrimaryKeySaveModelStub();
        $model->db->delete_result = FALSE;

        $this->assertFalse($model->delete());
    }
}

class PrimaryKeySaveModelStub extends DataMapper
{
    public $model = 'user';
    public $table = 'users';
    public $primary_key = 'user_id';
    public $fields = array('user_id', 'name');
    public $auto_transaction = FALSE;
    public $timestamp_format = 'Y-m-d H:i:s';
    public $after_events = array();
    public $cache_invalidations = 0;
    public $transaction_success = TRUE;
    public $relationship_result = TRUE;

    public function __construct()
    {
        $this->db = new PrimaryKeySaveDatabaseStub();
        $this->stored = (object) array(
            'user_id' => 42,
            'name' => 'Original name',
        );
        $this->_field_tracking = array('matches' => array());
        $this->user_id = 42;
        $this->name = 'Original name';
    }

    protected function after_update()
    {
        $this->after_events[] = 'after_update';
    }

    protected function after_save()
    {
        $this->after_events[] = 'after_save';
    }

    protected function _invalidate_cache()
    {
        $this->cache_invalidations++;
    }

    protected function _save_itfk(&$objects, $related_field)
    {
    }

    protected function _save_related_recursive($object, $related_field)
    {
        return $this->relationship_result;
    }

    protected function _auto_trans_complete($label = 'complete')
    {
        if (!$this->transaction_success) {
            $this->valid = FALSE;
        }
    }

    public function trans_rollback()
    {
        $this->valid = FALSE;
        return TRUE;
    }

    protected function _auto_trans_begin()
    {
    }
}

class PrimaryKeySaveDatabaseStub
{
    public $where_calls = array();
    public $updated_table;
    public $updated_data;
    public $insert_calls = 0;
    public $inserted_table;
    public $inserted_data;
    public $update_result = TRUE;
    public $insert_result = TRUE;
    public $delete_result = TRUE;
    public $deleted_table;

    public function where($field, $value)
    {
        $this->where_calls[] = array($field, $value);
        return $this;
    }

    public function update($table, $data)
    {
        $this->updated_table = $table;
        $this->updated_data = $data;
        return $this->update_result;
    }

    public function insert($table, $data)
    {
        $this->insert_calls++;
        $this->inserted_table = $table;
        $this->inserted_data = $data;
        return $this->insert_result;
    }

    public function insert_id()
    {
        return 73;
    }

    public function delete($table)
    {
        $this->deleted_table = $table;
        return $this->delete_result;
    }
}

class BeforeCreateKeyModelStub extends PrimaryKeySaveModelStub
{
    protected function before_create()
    {
        $this->user_id = 88;
    }
}
