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
}

class PrimaryKeySaveModelStub extends DataMapper
{
    public $model = 'user';
    public $table = 'users';
    public $primary_key = 'user_id';
    public $fields = array('user_id', 'name');
    public $auto_transaction = FALSE;
    public $timestamp_format = 'Y-m-d H:i:s';

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
}

class PrimaryKeySaveDatabaseStub
{
    public $where_calls = array();
    public $updated_table;
    public $updated_data;
    public $insert_calls = 0;
    public $inserted_table;
    public $inserted_data;

    public function where($field, $value)
    {
        $this->where_calls[] = array($field, $value);
        return $this;
    }

    public function update($table, $data)
    {
        $this->updated_table = $table;
        $this->updated_data = $data;
        return TRUE;
    }

    public function insert($table, $data)
    {
        $this->insert_calls++;
        $this->inserted_table = $table;
        $this->inserted_data = $data;
        return TRUE;
    }

    public function insert_id()
    {
        return 73;
    }
}