<?php
/**
 * Tests for DataMapper 2.0 features:
 * - Dirty Tracking
 * - Model Events
 * - Local Query Scopes
 * - Serialization Control ($hidden, $visible, $appends)
 * - Increment / Decrement
 * - Refresh / Fresh
 * - Model Comparison (is / is_not)
 * - Model Replication (replicate)
 * - Bulk Destroy
 * - Tap
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../application/datamapper/array.php';
require_once __DIR__ . '/../application/datamapper/json.php';

// ---------------------------------------------------------------
// Stub subclasses that live inside this test file
// ---------------------------------------------------------------

class DM2FakeModel extends \Tests\Support\FakeDataMapper
{
    /** Expose hidden/visible/appends for serialization tests */
    public $hidden = array();
    public $visible = array();
    public $appends = array();

    // track event calls
    public $event_log = array();

    // --- no-op stubs so increment/decrement can run ---------------
    protected function _soft_delete_is_enabled() { return FALSE; }
    protected function _timestamps_is_enabled() { return FALSE; }
    protected function _get_deleted_at_column() { return NULL; }
    protected function _fresh_timestamp() { return date('Y-m-d H:i:s'); }

    public function exists()
    {
        return isset($this->id) && ! empty($this->id);
    }

    public function get_by_id($id)
    {
        foreach ($this->all as $item) {
            if (isset($item->id) && $item->id == $id) {
                foreach (get_object_vars($item) as $k => $v) {
                    $this->{$k} = $v;
                }
                $this->_refresh_stored_values();
                return $this;
            }
        }
        $this->id = NULL;
        return $this;
    }
}

/**
 * Model with events defined
 */
class EventTestModel extends DM2FakeModel
{
    protected function before_save()
    {
        $this->event_log[] = 'before_save';
    }

    protected function after_save()
    {
        $this->event_log[] = 'after_save';
    }

    protected function before_create()
    {
        $this->event_log[] = 'before_create';
    }

    protected function after_create()
    {
        $this->event_log[] = 'after_create';
    }

    protected function before_update()
    {
        $this->event_log[] = 'before_update';
    }

    protected function after_update()
    {
        $this->event_log[] = 'after_update';
    }

    protected function before_delete()
    {
        $this->event_log[] = 'before_delete';
    }

    protected function after_delete()
    {
        $this->event_log[] = 'after_delete';
    }
}

/**
 * Model that cancels save via before_save returning FALSE
 */
class CancelSaveModel extends DM2FakeModel
{
    protected function before_save()
    {
        return FALSE;
    }
}

/**
 * Model that cancels delete via before_delete returning FALSE
 */
class CancelDeleteModel extends DM2FakeModel
{
    protected function before_delete()
    {
        return FALSE;
    }
}

/**
 * Model with scopes
 */
class ScopeTestModel extends DM2FakeModel
{
    public function scope_active()
    {
        return $this->where('active', 1);
    }

    public function scope_of_type($type)
    {
        return $this->where('type', $type);
    }

    public function scope_popular($min_votes = 100)
    {
        return $this->where('votes >', $min_votes);
    }
}

/**
 * Model with accessor for $appends
 */
class AppendTestModel extends DM2FakeModel
{
    public $appends = array('full_name');

    public function get_full_name_attribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}

/**
 * Model with multiple appends
 */
class MultiAppendModel extends DM2FakeModel
{
    public $appends = array('full_name', 'is_admin');

    public function get_full_name_attribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function get_is_admin_attribute()
    {
        return $this->role === 'admin';
    }
}

/**
 * Model with both hidden and appends
 */
class HiddenAppendModel extends DM2FakeModel
{
    public $hidden = array('password', 'api_token');
    public $appends = array('display_name');

    public function get_display_name_attribute()
    {
        return strtoupper($this->name);
    }
}

/**
 * Model with visible whitelist
 */
class VisibleModel extends DM2FakeModel
{
    public $visible = array('id', 'name');
}

/**
 * Model that logs all events (including create/update)
 */
class FullEventModel extends DM2FakeModel
{
    public $event_log = array();

    protected function before_save()   { $this->event_log[] = 'before_save'; }
    protected function after_save()    { $this->event_log[] = 'after_save'; }
    protected function before_create() { $this->event_log[] = 'before_create'; }
    protected function after_create()  { $this->event_log[] = 'after_create'; }
    protected function before_update() { $this->event_log[] = 'before_update'; }
    protected function after_update()  { $this->event_log[] = 'after_update'; }
    protected function before_delete() { $this->event_log[] = 'before_delete'; }
    protected function after_delete()  { $this->event_log[] = 'after_delete'; }
}

/**
 * Model that cancels create via before_create returning FALSE
 */
class CancelCreateModel extends DM2FakeModel
{
    protected function before_create()
    {
        return FALSE;
    }
}

/**
 * Model that cancels update via before_update returning FALSE
 */
class CancelUpdateModel extends DM2FakeModel
{
    protected function before_update()
    {
        return FALSE;
    }
}


class DataMapper2FeaturesTest extends TestCase
{
    // ===============================================================
    // Dirty Tracking
    // ===============================================================

    public function test_is_dirty_returns_false_when_nothing_changed()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';
        $model->_refresh_stored_values();

        $this->assertFalse($model->is_dirty());
        $this->assertTrue($model->is_clean());
    }

    public function test_is_dirty_detects_modified_field()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';
        $model->_refresh_stored_values();

        $model->name = 'Bob';

        $this->assertTrue($model->is_dirty());
        $this->assertTrue($model->is_dirty('name'));
        $this->assertFalse($model->is_dirty('email'));
        $this->assertFalse($model->is_clean());
        $this->assertTrue($model->is_clean('email'));
    }

    public function test_is_dirty_with_array_of_fields()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';
        $model->_refresh_stored_values();

        $model->name = 'Bob';

        $this->assertTrue($model->is_dirty(['name', 'email']));
        $this->assertFalse($model->is_dirty(['email']));
    }

    public function test_get_dirty_returns_changed_fields()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';
        $model->_refresh_stored_values();

        $model->name = 'Bob';
        $model->email = 'bob@test.com';

        $dirty = $model->get_dirty();
        $this->assertArrayHasKey('name', $dirty);
        $this->assertArrayHasKey('email', $dirty);
        $this->assertSame('Bob', $dirty['name']);
        $this->assertArrayNotHasKey('id', $dirty);
    }

    public function test_get_original_returns_stored_values()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';
        $model->_refresh_stored_values();

        $model->name = 'Bob';

        $this->assertSame('Alice', $model->get_original('name'));
        $this->assertSame('Bob', $model->name);

        $all = $model->get_original();
        $this->assertSame('Alice', $all['name']);
        $this->assertSame('alice@test.com', $all['email']);
    }

    // ===============================================================
    // Model Comparison: is() / is_not()
    // ===============================================================

    public function test_is_returns_true_for_same_record()
    {
        $a = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $a->id = 1;

        $b = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $b->id = 1;

        $this->assertTrue($a->is($b));
        $this->assertFalse($a->is_not($b));
    }

    public function test_is_returns_false_for_different_record()
    {
        $a = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $a->id = 1;

        $b = new DM2FakeModel([['id' => 2, 'name' => 'Bob']]);
        $b->id = 2;

        $this->assertFalse($a->is($b));
        $this->assertTrue($a->is_not($b));
    }

    public function test_is_returns_false_for_null()
    {
        $a = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $a->id = 1;

        $this->assertFalse($a->is(NULL));
        $this->assertTrue($a->is_not(NULL));
    }

    // ===============================================================
    // Model Replication: replicate()
    // ===============================================================

    public function test_replicate_creates_unsaved_copy()
    {
        $model = new DM2FakeModel([
            ['id' => 5, 'name' => 'Alice', 'email' => 'alice@test.com'],
        ]);
        $model->id = 5;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';

        $replica = $model->replicate();

        $this->assertNull($replica->id);
        $this->assertSame('Alice', $replica->name);
        $this->assertSame('alice@test.com', $replica->email);
    }

    public function test_replicate_excludes_specified_fields()
    {
        $model = new DM2FakeModel([
            ['id' => 5, 'name' => 'Alice', 'email' => 'alice@test.com'],
        ]);
        $model->id = 5;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';

        $replica = $model->replicate(['email']);

        $this->assertNull($replica->id);
        $this->assertSame('Alice', $replica->name);
        $this->assertNull($replica->email);
    }

    // ===============================================================
    // Tap
    // ===============================================================

    public function test_tap_calls_closure_and_returns_self()
    {
        $model = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $model->id = 1;
        $model->name = 'Alice';

        $called = false;
        $result = $model->tap(function ($m) use (&$called) {
            $called = true;
            $m->name = 'Tapped';
        });

        $this->assertTrue($called);
        $this->assertSame($model, $result);
        $this->assertSame('Tapped', $model->name);
    }

    // ===============================================================
    // Local Query Scopes
    // ===============================================================

    public function test_scope_adds_where_clause()
    {
        $model = new ScopeTestModel([
            ['id' => 1, 'name' => 'Alice', 'active' => 1, 'type' => 'admin', 'votes' => 200],
        ]);

        $result = $model->active();

        $this->assertSame($model, $result);
        $this->assertCount(1, $model->queryLog);
        $this->assertSame('active', $model->queryLog[0][1]);
        $this->assertSame(1, $model->queryLog[0][2]);
    }

    public function test_scope_with_parameter()
    {
        $model = new ScopeTestModel([
            ['id' => 1, 'name' => 'Alice', 'active' => 1, 'type' => 'admin', 'votes' => 200],
        ]);

        $result = $model->of_type('editor');

        $this->assertSame($model, $result);
        $this->assertSame('type', $model->queryLog[0][1]);
        $this->assertSame('editor', $model->queryLog[0][2]);
    }

    public function test_scope_chaining()
    {
        $model = new ScopeTestModel([
            ['id' => 1, 'name' => 'Alice', 'active' => 1, 'type' => 'admin', 'votes' => 200],
        ]);

        $model->active()->of_type('admin')->popular(50);

        $this->assertCount(3, $model->queryLog);
    }

    // ===============================================================
    // Serialization Control
    // ===============================================================

    public function test_hidden_excludes_fields_from_to_array()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'password' => 'secret123'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->password = 'secret123';
        $model->hidden = array('password');

        $ext = new DMZ_Array();
        $result = $ext->to_array($model);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('password', $result);
    }

    public function test_visible_whitelists_fields_in_to_array()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com', 'password' => 'secret'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';
        $model->password = 'secret';
        $model->visible = array('id', 'name');

        $ext = new DMZ_Array();
        $result = $ext->to_array($model);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('email', $result);
        $this->assertArrayNotHasKey('password', $result);
    }

    public function test_appends_includes_computed_attributes()
    {
        $model = new AppendTestModel([
            ['id' => 1, 'first_name' => 'Alice', 'last_name' => 'Smith'],
        ]);
        $model->id = 1;
        $model->first_name = 'Alice';
        $model->last_name = 'Smith';

        $ext = new DMZ_Array();
        $result = $ext->to_array($model);

        $this->assertArrayHasKey('full_name', $result);
        $this->assertSame('Alice Smith', $result['full_name']);
    }

    public function test_hidden_works_in_to_json()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'password' => 'secret'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->password = 'secret';
        $model->hidden = array('password');
        $model->has_one = array();
        $model->has_many = array();

        $ext = new DMZ_Json();
        $json = $ext->to_json($model);
        $data = json_decode($json, true);

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function test_no_serialization_control_preserves_all_fields()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';

        $ext = new DMZ_Array();
        $result = $ext->to_array($model);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
    }

    // ===============================================================
    // Model Events — fire_event
    // ===============================================================

    public function test_fire_event_calls_existing_method()
    {
        $model = new EventTestModel([['id' => 1, 'name' => 'Alice']]);
        $model->id = 1;
        $model->name = 'Alice';

        // Use reflection to test the protected method
        $ref = new \ReflectionMethod($model, '_fire_event');
        $ref->setAccessible(true);

        $result = $ref->invoke($model, 'before_save');
        $this->assertNull($result); // returns void (not FALSE)
        $this->assertContains('before_save', $model->event_log);
    }

    public function test_fire_event_returns_true_when_no_handler()
    {
        $model = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);

        $ref = new \ReflectionMethod($model, '_fire_event');
        $ref->setAccessible(true);

        $result = $ref->invoke($model, 'before_save');
        $this->assertTrue($result);
    }

    public function test_fire_event_returns_false_on_cancel()
    {
        $model = new CancelSaveModel([['id' => 1, 'name' => 'Alice']]);

        $ref = new \ReflectionMethod($model, '_fire_event');
        $ref->setAccessible(true);

        $result = $ref->invoke($model, 'before_save');
        $this->assertFalse($result);
    }

    public function test_cancel_delete_model_returns_false()
    {
        $model = new CancelDeleteModel([['id' => 1, 'name' => 'Alice']]);

        $ref = new \ReflectionMethod($model, '_fire_event');
        $ref->setAccessible(true);

        $result = $ref->invoke($model, 'before_delete');
        $this->assertFalse($result);
    }

    // ===============================================================
    // was_changed — tracks last save
    // ===============================================================

    public function test_was_changed_is_false_initially()
    {
        $model = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $model->id = 1;
        $model->name = 'Alice';

        $this->assertFalse($model->was_changed());
        $this->assertFalse($model->was_changed('name'));
    }

    // ===============================================================
    // Refresh / Fresh
    // ===============================================================

    public function test_fresh_returns_null_when_not_persisted()
    {
        $model = new DM2FakeModel([]);
        $model->id = NULL;

        $this->assertNull($model->fresh());
    }

    public function test_refresh_returns_self_when_not_persisted()
    {
        $model = new DM2FakeModel([]);
        $model->id = NULL;

        $result = $model->refresh();
        $this->assertSame($model, $result);
    }

    // ===============================================================
    // Replicate — edge cases
    // ===============================================================

    public function test_replicate_returns_correct_class()
    {
        $model = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $model->id = 1;
        $model->name = 'Alice';

        $replica = $model->replicate();

        $this->assertInstanceOf(DM2FakeModel::class, $replica);
    }

    // ===============================================================
    // Scope edge — non-existent method still throws
    // ===============================================================

    public function test_nonexistent_method_still_throws()
    {
        $this->expectException(\DataMapper_Exception::class);

        $model = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $model->totally_fake_method();
    }

    // ===============================================================
    // Additional Dirty Tracking Tests
    // ===============================================================

    public function test_is_clean_with_array_of_fields()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com', 'bio' => 'Hello'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';
        $model->bio = 'Hello';
        $model->_refresh_stored_values();

        $model->name = 'Bob';

        $this->assertTrue($model->is_clean(['email', 'bio']));
        $this->assertFalse($model->is_clean(['name', 'email']));
    }

    public function test_get_dirty_returns_empty_when_clean()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->_refresh_stored_values();

        $dirty = $model->get_dirty();
        $this->assertIsArray($dirty);
        $this->assertEmpty($dirty);
    }

    public function test_get_original_returns_null_for_unknown_field()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->_refresh_stored_values();

        $this->assertNull($model->get_original('nonexistent_field'));
    }

    public function test_dirty_resets_after_refresh_stored_values()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->_refresh_stored_values();

        $model->name = 'Bob';
        $this->assertTrue($model->is_dirty('name'));

        // Simulate what save() does — refresh stored values
        $model->_refresh_stored_values();

        $this->assertFalse($model->is_dirty('name'));
        $this->assertSame('Bob', $model->get_original('name'));
    }

    // ===============================================================
    // Additional Model Events Tests
    // ===============================================================

    public function test_fire_event_all_eight_events()
    {
        $model = new FullEventModel([['id' => 1, 'name' => 'Alice']]);
        $model->id = 1;

        $ref = new \ReflectionMethod($model, '_fire_event');
        $ref->setAccessible(true);

        $events = array(
            'before_save', 'after_save',
            'before_create', 'after_create',
            'before_update', 'after_update',
            'before_delete', 'after_delete',
        );

        foreach ($events as $event) {
            $ref->invoke($model, $event);
        }

        $this->assertSame($events, $model->event_log);
    }

    public function test_cancel_create_returns_false()
    {
        $model = new CancelCreateModel([['id' => 1, 'name' => 'Alice']]);

        $ref = new \ReflectionMethod($model, '_fire_event');
        $ref->setAccessible(true);

        $result = $ref->invoke($model, 'before_create');
        $this->assertFalse($result);
    }

    public function test_cancel_update_returns_false()
    {
        $model = new CancelUpdateModel([['id' => 1, 'name' => 'Alice']]);

        $ref = new \ReflectionMethod($model, '_fire_event');
        $ref->setAccessible(true);

        $result = $ref->invoke($model, 'before_update');
        $this->assertFalse($result);
    }

    public function test_fire_event_with_undefined_event_returns_true()
    {
        $model = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);

        $ref = new \ReflectionMethod($model, '_fire_event');
        $ref->setAccessible(true);

        // No model has a 'before_launch' method
        $result = $ref->invoke($model, 'before_launch');
        $this->assertTrue($result);
    }

    // ===============================================================
    // Additional Serialization Tests
    // ===============================================================

    public function test_multiple_appends()
    {
        $model = new MultiAppendModel([
            ['id' => 1, 'first_name' => 'Jane', 'last_name' => 'Doe', 'role' => 'admin'],
        ]);
        $model->id = 1;
        $model->first_name = 'Jane';
        $model->last_name = 'Doe';
        $model->role = 'admin';

        $ext = new DMZ_Array();
        $result = $ext->to_array($model);

        $this->assertArrayHasKey('full_name', $result);
        $this->assertSame('Jane Doe', $result['full_name']);
        $this->assertArrayHasKey('is_admin', $result);
        $this->assertTrue($result['is_admin']);
    }

    public function test_hidden_and_appends_combined()
    {
        $model = new HiddenAppendModel([
            ['id' => 1, 'name' => 'Alice', 'password' => 'secret', 'api_token' => 'tok123'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->password = 'secret';
        $model->api_token = 'tok123';

        $ext = new DMZ_Array();
        $result = $ext->to_array($model);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('display_name', $result);
        $this->assertSame('ALICE', $result['display_name']);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('api_token', $result);
    }

    public function test_visible_excludes_unlisted_fields()
    {
        $model = new VisibleModel([
            ['id' => 1, 'name' => 'Alice', 'email' => 'a@test.com', 'password' => 'secret'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'a@test.com';
        $model->password = 'secret';

        $ext = new DMZ_Array();
        $result = $ext->to_array($model);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('email', $result);
        $this->assertArrayNotHasKey('password', $result);
    }

    public function test_hidden_and_appends_in_json()
    {
        $model = new HiddenAppendModel([
            ['id' => 1, 'name' => 'Bob', 'password' => 'secret', 'api_token' => 'x'],
        ]);
        $model->id = 1;
        $model->name = 'Bob';
        $model->password = 'secret';
        $model->api_token = 'x';
        $model->has_one = array();
        $model->has_many = array();

        $ext = new DMZ_Json();
        $json = $ext->to_json($model);
        $data = json_decode($json, true);

        $this->assertArrayHasKey('display_name', $data);
        $this->assertSame('BOB', $data['display_name']);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('api_token', $data);
    }

    public function test_appends_without_accessor_is_skipped()
    {
        // If an appended name has no accessor method, it is not added
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->appends = array('nonexistent_computed');

        $ext = new DMZ_Array();
        $result = $ext->to_array($model);

        $this->assertArrayNotHasKey('nonexistent_computed', $result);
        $this->assertArrayHasKey('name', $result);
    }

    // ===============================================================
    // Additional Scope Tests
    // ===============================================================

    public function test_scope_with_default_parameter()
    {
        $model = new ScopeTestModel([
            ['id' => 1, 'name' => 'Alice', 'active' => 1, 'type' => 'admin', 'votes' => 200],
        ]);

        // popular() uses default $min_votes = 100
        $model->popular();

        $this->assertCount(1, $model->queryLog);
        $this->assertSame('votes >', $model->queryLog[0][1]);
        $this->assertSame(100, $model->queryLog[0][2]);
    }

    public function test_scope_does_not_shadow_existing_methods()
    {
        // 'where' is an existing method, should not resolve as scope
        $model = new ScopeTestModel([
            ['id' => 1, 'name' => 'Alice', 'active' => 1, 'type' => 'admin', 'votes' => 200],
        ]);

        $result = $model->where('name', 'Alice');
        $this->assertSame($model, $result);
        $this->assertCount(1, $model->queryLog);
        $this->assertSame('where', $model->queryLog[0][0]);
    }

    // ===============================================================
    // Additional Model Comparison Tests
    // ===============================================================

    public function test_is_with_same_instance()
    {
        $model = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $model->id = 1;

        $this->assertTrue($model->is($model));
    }

    public function test_is_not_with_different_ids()
    {
        $a = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $a->id = 1;

        $b = new DM2FakeModel([['id' => 99, 'name' => 'Alice']]);
        $b->id = 99;

        $this->assertTrue($a->is_not($b));
    }

    // ===============================================================
    // Additional Replicate Tests
    // ===============================================================

    public function test_replicate_does_not_modify_original()
    {
        $model = new DM2FakeModel([
            ['id' => 5, 'name' => 'Alice', 'email' => 'alice@test.com'],
        ]);
        $model->id = 5;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';

        $replica = $model->replicate();
        $replica->name = 'Changed';

        $this->assertSame('Alice', $model->name);
        $this->assertSame(5, $model->id);
    }

    public function test_replicate_with_multiple_exclusions()
    {
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com', 'status' => 'active'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'alice@test.com';
        $model->status = 'active';

        $replica = $model->replicate(['email', 'status']);

        $this->assertNull($replica->id);
        $this->assertSame('Alice', $replica->name);
        $this->assertNull($replica->email);
        $this->assertNull($replica->status);
    }

    // ===============================================================
    // Additional Tap Tests
    // ===============================================================

    public function test_tap_receives_the_model_instance()
    {
        $model = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $model->id = 1;
        $model->name = 'Alice';

        $received = null;
        $model->tap(function ($m) use (&$received) {
            $received = $m;
        });

        $this->assertSame($model, $received);
    }

    public function test_tap_callback_return_value_ignored()
    {
        $model = new DM2FakeModel([['id' => 1, 'name' => 'Alice']]);
        $model->id = 1;

        // Callback returns 'something', but tap returns $this
        $result = $model->tap(function ($m) {
            return 'something_else';
        });

        $this->assertSame($model, $result);
    }

    // ===============================================================
    // Fresh edge cases
    // ===============================================================

    public function test_fresh_creates_new_instance_of_same_class()
    {
        // In a real DB scenario, fresh() returns a new model loaded by ID.
        // With fakes, the new instance has no data, so fresh() returns NULL
        // because the ID can't be found. But we can verify it doesn't error.
        $model = new DM2FakeModel([
            ['id' => 1, 'name' => 'Alice'],
        ]);
        $model->id = 1;
        $model->name = 'Alice';

        $fresh = $model->fresh();

        // The new instance has empty data so get_by_id finds nothing
        $this->assertNull($fresh);
        // Original is unchanged
        $this->assertSame('Alice', $model->name);
        $this->assertSame(1, $model->id);
    }

    // ===============================================================
    // was_changed edge cases
    // ===============================================================

    public function test_was_changed_with_array_is_false_initially()
    {
        $model = new DM2FakeModel([['id' => 1, 'name' => 'Alice', 'email' => 'a@test.com']]);
        $model->id = 1;
        $model->name = 'Alice';
        $model->email = 'a@test.com';

        $this->assertFalse($model->was_changed(['name', 'email']));
    }

    // ===============================================================
    // Scope + other methods integration
    // ===============================================================

    public function test_scope_chains_with_limit_and_order()
    {
        $model = new ScopeTestModel([
            ['id' => 1, 'name' => 'Alice', 'active' => 1, 'type' => 'admin', 'votes' => 200],
        ]);

        $model->active()->of_type('admin')->order_by('votes', 'desc')->limit(10);

        // 2 scope wheres + 1 order_by + 1 limit = 4 log entries
        $this->assertCount(4, $model->queryLog);
        $this->assertSame('order_by', $model->queryLog[2][0]);
        $this->assertSame('limit', $model->queryLog[3][0]);
    }
}
