<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class DataMapperRelationshipSaveTest extends TestCase
{
    public function test_itfk_uses_related_custom_key_and_propagates_cleanup_failure(): void
    {
        $parent = new RelationshipParentStub();
        $parent->fields[] = 'entry_id';
        $parent->has_one['entry'] = $parent->has_many['entry'];
        unset($parent->has_many['entry']);
        $parent->entry_id = 'old-entry';
        $parent->cleanup_result = FALSE;
        $child = new RelationshipChildStub();
        $objects = array($child);

        $this->assertFalse($parent->saveItfk($objects, 'entry'));
        $this->assertSame('entry-9', $parent->entry_id);
        $this->assertSame(array(), $objects);
    }

    public function test_itfk_accepts_legacy_void_cleanup_override(): void
    {
        $parent = new VoidCleanupRelationshipParentStub();
        $parent->fields[] = 'entry_id';
        $parent->has_one['entry'] = $parent->has_many['entry'];
        unset($parent->has_many['entry']);
        $objects = array(new RelationshipChildStub());

        $this->assertTrue($parent->saveItfk($objects, 'entry'));
    }

    public function test_itfk_failure_after_base_write_refreshes_committed_state(): void
    {
        $parent = new SavingRelationshipParentStub();

        $this->assertFalse($parent->save(new RelationshipChildStub(), 'entry'));
        $this->assertSame('entry-9', $parent->stored->entry_id);
        $this->assertSame(1, $parent->cache_invalidations);
    }

    public function test_join_table_write_uses_both_configured_primary_keys(): void
    {
        $parent = new RelationshipParentStub();
        $child = new RelationshipChildStub();

        $this->assertTrue($parent->saveRelation($child, 'entry'));
        $this->assertSame('account_entries', $parent->db->inserted_table);
        $this->assertSame(
            array('account_id' => 'account-a', 'entry_id' => 'entry-9'),
            $parent->db->inserted_data
        );
    }

    public function test_relation_save_accepts_legacy_void_cleanup_override(): void
    {
        $parent = new VoidCleanupSaveRelationParentStub();

        $this->assertTrue($parent->saveRelation(new RelationshipChildStub(), 'entry'));
    }

    public function test_relation_array_accepts_legacy_void_save_override(): void
    {
        $parent = new VoidSaveRelationParentStub();

        $this->assertTrue($parent->saveRelatedArray(array(
            new RelationshipChildStub(),
            new RelationshipChildStub(),
        ), 'entry'));
    }

    public function test_join_table_write_propagates_database_failure(): void
    {
        $parent = new RelationshipParentStub();
        $parent->db->insert_result = FALSE;

        $this->assertFalse($parent->saveRelation(new RelationshipChildStub(), 'entry'));
    }

    public function test_join_table_delete_propagates_database_failure(): void
    {
        $parent = new RelationshipParentStub();
        $parent->db->delete_result = FALSE;

        $this->assertFalse($parent->deleteRelation(new RelationshipChildStub(), 'entry'));
    }

    public function test_relation_array_delete_rolls_back_after_later_failure(): void
    {
        $parent = new TransactionalRelationshipParentStub();
        $parent->db->delete_results = array(TRUE, FALSE);

        $this->assertFalse($parent->delete(array(
            new RelationshipChildStub(),
            new RelationshipChildStub(),
        ), 'entry'));
        $this->assertSame(1, $parent->rollback_calls);
        $this->assertSame(0, $parent->complete_calls);
    }

    public function test_single_relation_delete_rolls_back_on_failure(): void
    {
        $parent = new TransactionalRelationshipParentStub();
        $parent->db->delete_result = FALSE;

        $this->assertFalse($parent->delete(new RelationshipChildStub(), 'entry'));
        $this->assertSame(1, $parent->rollback_calls);
        $this->assertSame(0, $parent->complete_calls);
    }

    public function test_relation_array_delete_returns_true_on_success(): void
    {
        $parent = new TransactionalRelationshipParentStub();
        $parent->db->delete_results = array(TRUE, TRUE);

        $this->assertTrue($parent->delete(array(
            new RelationshipChildStub(),
            new RelationshipChildStub(),
        ), 'entry'));
        $this->assertSame(0, $parent->rollback_calls);
        $this->assertSame(1, $parent->complete_calls);
    }
}

class RelationshipParentStub extends DataMapper
{
    public $model = 'account';
    public $table = 'accounts';
    public $prefix = '';
    public $primary_key = 'account_uuid';
    public $fields = array('account_uuid');
    public $cleanup_result = TRUE;
    public $timestamp_format = 'Y-m-d H:i:s';

    public function __construct()
    {
        $this->account_uuid = 'account-a';
        $this->stored = new stdClass();
        $this->db = new RelationshipDatabaseStub();
        $this->has_many = array('entry' => $this->relationConfig());
        $this->has_one = array();
    }

    public function saveRelation(DataMapper $object, $related_field)
    {
        return $this->_save_relation($object, $related_field);
    }

    public function saveItfk(array &$objects, $related_field)
    {
        return $this->_save_itfk($objects, $related_field);
    }

    public function deleteRelation(DataMapper $object, $related_field)
    {
        return $this->_delete_relation($object, $related_field);
    }

    public function saveRelatedArray(array $objects, $related_field)
    {
        return $this->_save_related_recursive($objects, $related_field);
    }

    protected function _remove_other_one_to_one($related_field, $object)
    {
        return $this->cleanup_result;
    }

    private function relationConfig()
    {
        return array(
            'class' => RelationshipChildStub::class,
            'other_field' => 'account',
            'join_self_as' => 'account',
            'join_other_as' => 'entry',
            'join_table' => 'account_entries',
            'reciprocal' => FALSE,
        );
    }
}

class RelationshipChildStub extends DataMapper
{
    public $model = 'entry';
    public $table = 'entries';
    public $prefix = '';
    public $primary_key = 'entry_uuid';
    public $fields = array('entry_uuid');

    public function __construct()
    {
        $this->entry_uuid = 'entry-9';
        $this->stored = new stdClass();
        $this->has_one = array();
        $this->has_many = array('account' => array());
    }
}

class VoidCleanupRelationshipParentStub extends RelationshipParentStub
{
    protected function _remove_other_one_to_one($related_field, $object)
    {
    }
}

class VoidCleanupSaveRelationParentStub extends VoidCleanupRelationshipParentStub
{
    public function __construct()
    {
        parent::__construct();
        $this->fields[] = 'entry_id';
        $this->has_one['entry'] = $this->has_many['entry'];
        unset($this->has_many['entry']);
    }

    public function save($object = '', $related_field = '')
    {
        return TRUE;
    }
}

class VoidSaveRelationParentStub extends RelationshipParentStub
{
    protected function _save_relation($object, $related_field = '')
    {
    }
}

class TransactionalRelationshipParentStub extends RelationshipParentStub
{
    public $auto_transaction = TRUE;
    public $rollback_calls = 0;
    public $complete_calls = 0;

    protected function _auto_trans_begin()
    {
    }

    protected function _auto_trans_complete($label = 'complete')
    {
        $this->complete_calls++;
    }

    public function trans_rollback()
    {
        $this->rollback_calls++;
        return TRUE;
    }
}

class SavingRelationshipParentStub extends RelationshipParentStub
{
    public $cache_invalidations = 0;

    public function __construct()
    {
        parent::__construct();
        $this->fields[] = 'entry_id';
        $this->has_one['entry'] = $this->has_many['entry'];
        unset($this->has_many['entry']);
        $this->entry_id = 'old-entry';
        $this->stored = (object) array(
            'account_uuid' => 'account-a',
            'entry_id' => 'old-entry',
        );
        $this->validation = array();
        $this->_field_tracking = array('matches' => array());
        $this->cleanup_result = FALSE;
    }

    protected function _invalidate_cache()
    {
        $this->cache_invalidations++;
    }
}

class RelationshipDatabaseStub
{
    public $insert_result = TRUE;
    public $update_result = TRUE;
    public $delete_result = TRUE;
    public $delete_results = array();
    public $inserted_table;
    public $inserted_data;

    public function get_where($table, $data, $limit = NULL, $offset = NULL)
    {
        return new RelationshipQueryResultStub();
    }

    public function insert($table, $data)
    {
        $this->inserted_table = $table;
        $this->inserted_data = $data;
        return $this->insert_result;
    }

    public function where($field, $value)
    {
        return $this;
    }

    public function update($table, $data)
    {
        return $this->update_result;
    }

    public function delete($table, $data)
    {
        if (!empty($this->delete_results)) {
            return array_shift($this->delete_results);
        }
        return $this->delete_result;
    }
}

class RelationshipQueryResultStub
{
    public function num_rows()
    {
        return 0;
    }
}
