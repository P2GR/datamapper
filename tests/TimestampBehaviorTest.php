<?php

use DataMapper\Traits\HasTimestamps;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once APPPATH . 'datamapper/HasTimestamps.php';

class TimestampBehaviorTest extends TestCase
{
    public function test_touch_uses_custom_primary_key_and_returns_database_result(): void
    {
        $model = new TimestampModelStub();

        $this->assertTrue($model->touch());
        $this->assertSame(array(array('user_id', 42)), $model->db->where_calls);
        $this->assertSame(array('updated_at' => 'new-timestamp'), $model->db->updated_data);
    }

    public function test_failed_touch_restores_previous_timestamp(): void
    {
        $model = new TimestampModelStub();
        $model->db->update_result = FALSE;

        $this->assertFalse($model->touch());
        $this->assertSame('old-timestamp', $model->updated_at);
    }

    public function test_existing_custom_primary_key_does_not_reset_created_timestamp(): void
    {
        $model = new TimestampModelStub();
        $model->created_at = NULL;

        $model->handleTimestamps();

        $this->assertNull($model->created_at);
        $this->assertSame('new-timestamp', $model->updated_at);
    }
}

class TimestampModelStub extends DataMapper
{
    use HasTimestamps;

    public $model = 'timestamp_stub';
    public $table = 'timestamp_stubs';
    public $primary_key = 'user_id';
    public $fields = array('user_id', 'created_at', 'updated_at');
    public $user_id = 42;
    public $updated_at = 'old-timestamp';

    public function __construct()
    {
        $this->db = new TimestampDatabaseStub();
    }

    public function exists()
    {
        return TRUE;
    }

    public function handleTimestamps()
    {
        $this->_handle_timestamps();
    }

    protected function _fresh_timestamp()
    {
        return 'new-timestamp';
    }
}

class TimestampDatabaseStub
{
    public $where_calls = array();
    public $updated_data;
    public $update_result = TRUE;

    public function where($field, $value)
    {
        $this->where_calls[] = array($field, $value);
        return $this;
    }

    public function update($table, $data)
    {
        $this->updated_data = $data;
        return $this->update_result;
    }
}