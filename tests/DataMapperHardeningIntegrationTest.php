<?php

use PHPUnit\Framework\TestCase;
use Tests\Support\Ci3Database;

require_once APPPATH . 'datamapper/querybuilder.php';
require_once APPPATH . 'datamapper/SoftDeletes.php';
require_once APPPATH . 'datamapper/HasTimestamps.php';
require_once APPPATH . 'datamapper/nestedsets.php';
require_once APPPATH . 'datamapper/htmlform.php';
require_once APPPATH . 'datamapper/attributecasting.php';
require_once APPPATH . 'datamapper/array.php';
require_once APPPATH . 'datamapper/json.php';

class DataMapperHardeningIntegrationTest extends TestCase
{
    private $db;
    private $config;
    private $cacheDir;

    protected function setUp(): void
    {
        $this->config = DataMapper::$config;
        $this->db = Ci3Database::connect();
        get_instance()->db = $this->db;
        DataMapper::$config = array_merge(DataMapper::$_dmz_config_defaults, array('auto_populate_has_one' => FALSE));
        DataMapper::$common = array(DMZ_CLASSNAMES_KEY => array());
        $this->cacheDir = sys_get_temp_dir() . '/dmz-hardening-' . bin2hex(random_bytes(6));
        $this->db->query('CREATE TABLE hardening_records (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, active INTEGER, counter INTEGER, settings TEXT, published_at TEXT, deleted_at TEXT, amount TEXT)');
        foreach (array(array('Ada', 1), array('Bob', 0), array('Cat', 1)) as $row) {
            $this->db->insert('hardening_records', array('name' => $row[0], 'email' => 'raw@example.com', 'active' => $row[1], 'counter' => 0, 'settings' => '{"theme":"light","nested":{"enabled":false}}', 'published_at' => '2026-01-01 10:00:00', 'amount' => '999999999999.99999999'));
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            foreach (glob($this->cacheDir . '/*') as $file) {
                unlink($file);
            }
            rmdir($this->cacheDir);
        }
        DataMapper::$config = $this->config;
        DataMapper::$common = array(DMZ_CLASSNAMES_KEY => array());
        get_instance()->db = NULL;
    }

    private function cache(): void
    {
        DataMapper::$config['cache_driver'] = 'file';
        DataMapper::$config['cache_config'] = array('cache_dir' => $this->cacheDir);
    }

    public function test_collection_json_honors_model_controls_and_never_serializes_database(): void
    {
        $models = (new HardeningRecord())->get();
        foreach ($models as $model) {
            $model->hidden = array('email');
            $model->db->password = 'sentinel';
        }
        $json = $models->collect()->to_json(JSON_PRETTY_PRINT);
        $rows = json_decode($json, TRUE);
        $this->assertCount(3, $rows);
        $this->assertSame('Ada', $rows[0]['name']);
        $this->assertArrayNotHasKey('email', $rows[0]);
        $this->assertArrayNotHasKey('stored', $rows[0]);
        $this->assertStringNotContainsString('sentinel', $json);
        $this->assertSame(array(1, 2), json_decode((new DMZ_Collection(array(1, 2)))->to_json(), TRUE));
    }

    public function test_mass_assignment_preserves_internal_settings_and_custom_identity(): void
    {
        $this->relations();
        $model = (new HardeningAccount())->where('account_key', 'a')->get(1);
        $model->fill(array('account_key' => 'b', 'name' => 'changed', 'table' => 'victims', 'primary_key' => 'name', 'fields' => array('name'), '_force_delete_in_progress' => TRUE));
        $this->assertSame('hardening_accounts', $model->table);
        $this->assertSame('account_key', $model->primary_key);
        $this->assertSame('a', $model->account_key);
        $model->force_fill(array('table' => 'victims', 'primary_key' => 'name'));
        $this->assertSame('hardening_accounts', $model->table);
        $this->assertSame('account_key', $model->primary_key);
        $this->assertTrue($model->save());
        $this->assertSame('B', $this->db->get_where('hardening_accounts', array('account_key' => 'b'))->row()->name);
        $model->guarded = array();
        $model->fill(array('account_key' => 'b'));
        $this->assertSame('b', $model->account_key);
    }

    public function test_appended_attributes_obey_hidden_and_visible_controls(): void
    {
        $model = (new HardeningAppendedRecord())->get_by_id(1);
        foreach (array(
            array(array('email'), array(), array('id', 'caption')),
            array(array(), array('id', 'caption'), array('id', 'caption')),
            array(array('email', 'caption'), array('id', 'email', 'caption'), array('id')),
        ) as list($hidden, $visible, $expected)) {
            $model->hidden = $hidden;
            $model->visible = $visible;
            $data = (new DMZ_Array())->to_array($model);
            $this->assertArrayNotHasKey('email', $data);
            foreach ($expected as $field) { $this->assertArrayHasKey($field, $data); }
            if ($visible) { $this->assertSame($expected, array_keys($data)); }
            $this->assertSame($data, json_decode((new DMZ_Collection(array($model)))->to_json(), TRUE)[0]);
            $this->assertSame($data, json_decode((new DMZ_Json())->to_json($model), TRUE));
        }
    }

    public function test_json_numeric_object_keys_keep_their_object_shape(): void
    {
        $model = new HardeningJsonRecord();
        foreach (array('mysql', 'pgsql') as $family) {
            $model->family = $family;
            foreach (array('0' => '{"0":"admin"}', 'groups[]->0' => '{"groups":[{"0":"admin"}]}', 'groups[]->7' => '{"groups":[{"7":"admin"}]}') as $path => $candidate) {
                $model->where_json_contains('settings->' . $path, 'admin');
                $sql = $model->db->get_compiled_select('hardening_records');
                $this->assertStringContainsString($candidate, $sql);
            }
        }
    }

    public function test_counter_rejects_sql_as_amount_without_writing(): void
    {
        $model = (new HardeningRecord())->get_by_id(1);
        try {
            $model->increment('counter', "1, name = 'injected'");
            $this->fail('SQL amount accepted');
        } catch (InvalidArgumentException $expected) {
            $this->assertSame('Ada', $this->db->get_where('hardening_records', array('id' => 1))->row()->name);
        }
        $this->assertTrue($model->increment('counter', '2'));
        $this->assertTrue($model->decrement('counter'));
        $this->assertSame(1, (int) $model->fresh()->counter);
    }

    public function test_checkbox_labels_are_text(): void
    {
        $form = new DMZ_HTMLForm();
        $html = $form->_checkbox('checkbox', 'item', array(), array('list' => array(1 => '<img src=x onerror=alert(1)>')));
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    public function test_counter_does_not_mark_unrelated_edits_as_saved(): void
    {
        $model = (new HardeningRecord())->get_by_id(1);
        $model->name = 'unsaved';
        $this->assertTrue($model->increment('counter'));
        $this->assertTrue($model->is_dirty('name'));
        $this->assertTrue($model->save());
        $this->assertSame('unsaved', $model->fresh()->name);
    }

    public function test_legacy_magic_getter_overrides_and_casting_trait_still_work(): void
    {
        $legacy = (new HardeningLegacyGetter())->get_by_id(1);
        $this->assertSame('legacy', $legacy->virtual);
        $this->assertSame('Ada', $legacy->name);
        $model = (new HardeningCastingTraitRecord())->get_by_id(1);
        $model->settings = array('theme' => 'first');
        $model->settings = '{"theme":"second"}';
        $this->assertSame(array('theme' => 'second'), $model->settings);
        $model->hidden = array('email');
        $this->assertArrayNotHasKey('email', $model->toArray());
        $this->assertSame('Ada!', $model->toArray()['caption']);
        $this->assertTrue($model->save());
        $this->assertSame(array('theme' => 'second'), $model->fresh()->settings);
    }

    public function test_traits_allow_documented_model_column_overrides(): void
    {
        $this->db->query('CREATE TABLE hardening_traits (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, created TEXT, modified TEXT, archived TEXT)');
        $model = new HardeningCustomTraits();
        $model->name = 'test';
        $this->assertTrue($model->save());
        $this->assertTrue(ctype_digit((string) $model->created));
        $this->assertSame($model->created, $model->modified);
        $this->assertTrue($model->delete());
        $this->assertSame(0, (new HardeningCustomTraits())->count());
        $this->assertSame(1, (new HardeningCustomTraits())->only_softdeleted()->count());
    }

    public function test_exact_decimal_metadata_does_not_force_binary_float(): void
    {
        $model = new HardeningDecimalRecord();
        $this->assertFalse($model->usesFloat('decimal'));
        $this->assertFalse($model->usesFloat('numeric'));
        $this->assertTrue($model->usesFloat('double'));
        $this->assertSame('999999999999.99999999', $model->get_by_id(1)->amount);
        $model->name = 'changed';
        $this->assertTrue($model->save());
        $this->assertSame('999999999999.99999999', $model->fresh()->amount);
    }

    public function test_json_predicates_compile_supported_containment_operators(): void
    {
        $model = new HardeningJsonRecord();
        $model->family = 'mysql';
        $model->where_json_contains('settings->roles[]->name', 'admin');
        $sql = $model->db->get_compiled_select('hardening_records');
        $this->assertStringContainsString('JSON_CONTAINS(', $sql);
        $this->assertStringContainsString('{"roles":[{"name":"admin"}]}', $sql);
        $this->assertStringNotContainsString('[*]', $sql);
        $model->family = 'pgsql';
        $model->where_json_doesnt_contain('settings->roles[]->name', 'admin');
        $sql = $model->db->get_compiled_select('hardening_records');
        $this->assertStringContainsString('NOT (', $sql);
        $this->assertStringContainsString('::jsonb @>', $sql);
        $this->assertStringNotContainsString('!@>', $sql);
    }

    public function test_cache_identity_survives_execution_and_hit_resets_builder(): void
    {
        $this->cache();
        $first = (new HardeningRecord())->where('active', 1)->cache()->get(1, 1);
        $queries = count($this->db->queries);
        $second = (new HardeningRecord())->where('active', 1)->cache()->get(1, 1);
        $this->assertSame($queries, count($this->db->queries));
        $this->assertSame(array(3), $second->collect()->ids());
        $this->assertSame(array(), $second->db->dm_get('qb_where'));
        $this->assertSame(array(1, 2, 3), (new HardeningRecord())->cache()->get()->collect()->ids());
        $this->assertSame($first->name, $second->name);
    }

    /** @dataProvider writeMethods */
    public function test_all_write_paths_invalidate_cached_results($method): void
    {
        $this->cache();
        (new HardeningRecord())->cache()->get();
        $model = (new HardeningRecord())->get_by_id(1);
        if ($method === 'update') {
            $this->assertTrue($model->where('id', 1)->update('name', 'changed'));
        } elseif ($method === 'increment') {
            $this->assertTrue($model->increment('counter'));
        } elseif ($method === 'destroy') {
            $this->assertTrue(HardeningRecord::destroy(1));
        } elseif ($method === 'truncate') {
            $this->assertTrue($model->truncate());
        } else {
            $this->assertTrue($model->update_all('name', 'changed'));
        }
        $queries = count($this->db->queries);
        $fresh = (new HardeningRecord())->cache()->get();
        $this->assertGreaterThan($queries, count($this->db->queries));
        $this->assertSame($this->db->count_all('hardening_records'), count($fresh->all));
    }

    public static function writeMethods(): array
    {
        return array(array('update'), array('update_all'), array('increment'), array('destroy'), array('truncate'));
    }

    public function test_soft_scope_groups_or_and_cache_respects_scope(): void
    {
        $this->cache();
        $this->db->where('id', 1)->update('hardening_records', array('deleted_at' => '2026-01-01'));
        $active = (new HardeningSoftRecord())->where('name', 'Ada')->or_where('active', 1)->cache()->get();
        $this->assertSame(array(3), $active->collect()->ids());
        $this->assertCount(3, (new HardeningSoftRecord())->with_softdeleted()->cache()->get()->all);
        $this->assertCount(2, (new HardeningSoftRecord())->cache()->get()->all);
        $this->assertSame(1, (new HardeningSoftRecord())->only_softdeleted()->count());
    }

    public function test_database_failure_in_another_model_rolls_back_transaction(): void
    {
        $first = new HardeningRecord();
        $second = new HardeningRecord();
        $this->assertTrue($first->trans_begin());
        $this->assertTrue($first->where('id', 1)->update('name', 'changed'));
        @$second->db->query('INSERT INTO hardening_records (id) VALUES (1)');
        $this->assertFalse($first->trans_complete());
        $this->assertSame('Ada', $this->db->get_where('hardening_records', array('id' => 1))->row()->name);
    }

    public function test_soft_scope_remains_grouped_with_legacy_ci_condition_cache(): void
    {
        $this->db->where('id', 1)->update('hardening_records', array('deleted_at' => '2026-01-01'));
        $model = new HardeningSoftRecord();
        $model->db->start_cache();
        $model->where('name', 'Ada')->or_where('active', 1);
        $model->db->stop_cache();
        for ($i = 0; $i < 2; $i++) {
            $this->assertSame(array(3), $model->get()->collect()->ids());
        }
        $this->assertSame(1, $model->count());
        $this->assertSame(2, $model->with_softdeleted()->count());
    }

    public function test_cache_distinguishes_having_distinct_and_database_connection(): void
    {
        $this->cache();
        $first = (new HardeningPlainRecord())->select('active')->group_by('active')->having('active', 1)->cache()->get();
        $this->assertSame(array(1), $first->collect()->pluck('active'));
        $other = (new HardeningPlainRecord())->select('active')->group_by('active')->having('active', 0)->cache()->get();
        $this->assertSame(array(0), $other->collect()->pluck('active'));
        $this->assertCount(3, (new HardeningPlainRecord())->select('counter')->cache()->get()->all);
        $this->assertCount(1, (new HardeningPlainRecord())->select('counter')->distinct()->cache()->get()->all);
        (new HardeningPlainRecord())->cache()->get();
        $connection = Ci3Database::connect();
        $connection->query('CREATE TABLE hardening_records (id INTEGER PRIMARY KEY, name TEXT)');
        $connection->query("INSERT INTO hardening_records VALUES (1, 'other database')");
        $model = new HardeningPlainRecord();
        $model->db = $connection;
        $this->assertSame('other database', $model->cache()->get()->name);
    }

    public function test_object_date_mutations_and_copy_have_independent_snapshots(): void
    {
        $model = (new HardeningRecord())->get_by_id(1);
        $this->assertFalse($model->is_dirty());
        $model->settings->nested->enabled = TRUE;
        $model->published_at->modify('+1 day');
        $this->assertTrue($model->is_dirty('settings'));
        $this->assertTrue($model->save());
        $fresh = $model->fresh();
        $this->assertTrue($fresh->settings->nested->enabled);
        $this->assertSame('2026-01-02', $fresh->published_at->format('Y-m-d'));
        $this->assertFalse($model->is_dirty());
        $copy = $model->get_copy();
        $copy->name = 'copy';
        $copy->settings->theme = 'copy';
        $this->assertTrue($copy->save());
        $this->assertSame('Ada', $model->get_original('name'));
        $this->assertSame(1, $model->get_original('id'));
        $this->assertSame('light', $model->settings->theme);
    }

    public function test_transformations_run_on_every_assignment_but_not_database_hydration(): void
    {
        $model = (new HardeningTransformRecord())->get_by_id(1);
        $this->assertSame(0, $model->mutations);
        $model->email = 'FIRST@EXAMPLE.COM';
        $model->email = 'SECOND@EXAMPLE.COM';
        $this->assertSame(2, $model->mutations);
        $this->assertSame('second@example.com', $model->email);
        $this->assertSame('ADA', $model->name);
        $this->assertTrue($model->save());
        $this->assertSame('Ada', $this->db->get_where('hardening_records', array('id' => 1))->row()->name);
        $this->assertSame('second@example.com', $model->fresh()->email);
        $this->assertSame('Ada', (new HardeningPlainRecord())->get_by_id(1)->name);
        $this->assertArrayHasKey('name', get_object_vars((new HardeningPlainRecord())->get_by_id(1)));
    }

    public function test_failed_mutator_does_not_bypass_later_assignments(): void
    {
        $model = (new HardeningFailingMutator())->get_by_id(1);
        try {
            $model->email = 'reject';
            $this->fail('Mutator exception was lost');
        } catch (InvalidArgumentException $expected) {
            $this->assertSame('raw@example.com', $model->email);
        }
        $model->email = 'VALID@EXAMPLE.COM';
        $this->assertSame('valid@example.com', $model->email);
    }

    public function test_same_model_instances_keep_the_source_connection(): void
    {
        $schema = $this->db->query("SELECT sql FROM sqlite_master WHERE name='hardening_records'")->row()->sql;
        $tenant = Ci3Database::connect();
        $tenant->query($schema);
        $tenant->query("INSERT INTO hardening_records(id,name) VALUES(1,'tenant one'),(2,'tenant two')");
        $model = new HardeningPlainRecord();
        $model->db = $tenant;
        $model->order_by('id')->get();
        $second = $model->all[1];
        $second->name = 'edited tenant';
        $this->assertTrue($second->save());
        $this->assertSame('edited tenant', $tenant->get_where('hardening_records', array('id' => 2))->row()->name);
        $this->assertSame('Bob', $this->db->get_where('hardening_records', array('id' => 2))->row()->name);
        $this->assertSame('tenant one', $model->fresh()->name);
        $this->assertSame('tenant one', $model->refresh()->name);
        $created = $model->first_or_create(array('name' => 'new tenant'));
        $this->assertInstanceOf(HardeningPlainRecord::class, $created);
        $this->assertSame(3, $tenant->count_all('hardening_records'));
        $this->assertSame(3, $this->db->count_all('hardening_records'));
        $this->cache();
        foreach (array(1, 2) as $attempt) {
            $query = new HardeningPlainRecord();
            $query->db = $tenant;
            $rows = $query->order_by('id')->cache()->get();
            foreach ($rows as $row) { $this->assertSame($tenant->conn_id, $row->db->conn_id); }
        }
    }

    public function test_eager_relation_collisions_preserve_columns_and_allow_explicit_edits(): void
    {
        $this->relations();
        $this->db->query('ALTER TABLE hardening_entries ADD COLUMN author TEXT');
        $this->db->query("UPDATE hardening_entries SET author='legacy text'");
        foreach (array(HardeningEntry::class, HardeningCollisionEntry::class) as $class) {
            $model = (new $class())->with('author')->first();
            $this->assertInstanceOf(HardeningAuthor::class, $model->author);
            $this->assertFalse($model->is_dirty('author'));
            $copy = $model->get_clone();
            $copy->name = 'edited';
            $this->assertTrue($copy->save());
            $this->assertSame('legacy text', $this->db->get_where('hardening_entries', array('entry_key' => 10))->row()->author);
            $model->force_fill(array('author' => NULL));
            $this->assertTrue($model->save());
            $this->assertNull($this->db->get_where('hardening_entries', array('entry_key' => 10))->row()->author);
            $this->db->query("UPDATE hardening_entries SET author='legacy text'");
        }
    }

    public function test_where_has_groups_or_conditions_before_parent_correlation(): void
    {
        $this->relations();
        $ids = (new HardeningAccount())->where_has('entry', function ($query) {
            $query->where('name', 'other')->or_where('name', 'absent');
        })->get()->collect()->ids();
        $this->assertSame(array('b'), $ids);
        $cached_ids = (new HardeningAccount())->where_has('entry', function ($query) {
            $query->start_cache();
            $query->where('name', 'other')->or_where('name', 'absent');
            $query->stop_cache();
        })->get()->collect()->ids();
        $this->assertSame(array('b'), $cached_ids);
    }

    public function test_automatic_integer_hydration_preserves_out_of_range_values(): void
    {
        $model = new HardeningPlainRecord();
        foreach (array('0' => 0, '42' => 42, '0007' => 7, (string) PHP_INT_MIN => PHP_INT_MIN, (string) PHP_INT_MAX => PHP_INT_MAX, '18446744073709551615' => '18446744073709551615', '-18446744073709551615' => '-18446744073709551615') as $value => $expected) {
            $model->_to_object($model, (object) array('id' => (string) $value));
            $this->assertSame($expected, $model->id);
        }
    }

    public function test_batch_query_failure_after_partial_work_is_reported(): void
    {
        $seen = array();
        try {
            @(new HardeningPlainRecord())->chunk_by_id(2, function ($rows) use (&$seen) {
                $seen = $rows->ids();
                $this->db->query('DROP TABLE hardening_records');
            });
            $this->fail('Failed batch reported success');
        } catch (DataMapper_Database_Exception $expected) {
            $this->assertSame(array(1, 2), $seen);
        }
    }

    public function test_transaction_reads_bypass_shared_cache_and_rollback_never_publishes(): void
    {
        $this->cache();
        $this->assertSame('Ada', (new HardeningRecord())->where('id', 1)->cache()->get()->name);
        $writer = new HardeningRecord();
        $this->assertTrue($writer->trans_begin());
        $this->assertTrue($writer->where('id', 1)->update('name', 'uncommitted'));
        $this->assertSame('uncommitted', (new HardeningRecord())->where('id', 1)->cache()->get()->name);
        $this->assertTrue($writer->trans_rollback());
        $this->assertSame('Ada', (new HardeningRecord())->where('id', 1)->cache()->get()->name);
    }

    public function test_outer_commit_invalidates_cache_through_the_ci_connection(): void
    {
        $this->cache();
        (new HardeningRecord())->where('id', 1)->cache()->get();
        $cache_file = glob($this->cacheDir . '/*')[0];
        $committed_payload = file_get_contents($cache_file);
        $this->assertTrue($this->db->trans_begin());
        $this->assertTrue($this->db->trans_begin());
        $this->assertTrue((new HardeningRecord())->where('id', 1)->update('name', 'committed'));
        $this->assertTrue($this->db->trans_commit());
        $this->assertNotEmpty($this->db->dm_get('_dm_cache_invalidations'));
        // Another connection can cache the previous committed value before commit.
        file_put_contents($cache_file, $committed_payload);
        $this->assertTrue($this->db->trans_complete());
        $this->assertSame(array(), $this->db->dm_get('_dm_cache_invalidations'));
        $this->assertSame('committed', (new HardeningRecord())->where('id', 1)->cache()->get()->name);
    }

    public function test_scalar_queries_preserve_types_accessors_casts_and_cache(): void
    {
        $this->assertSame(array(1, 3), (new HardeningPlainRecord())->where('active', 1)->pluck('id'));
        $this->assertStringNotContainsString('SELECT *', end($this->db->queries));
        $this->assertSame('Ada', (new HardeningPlainRecord())->value('name'));
        $this->assertStringNotContainsString('SELECT *', end($this->db->queries));
        $this->assertSame('none', (new HardeningPlainRecord())->where('id', 99)->value('name', 'none'));
        $this->assertSame(array('ADA', 'BOB', 'CAT'), (new HardeningTransformRecord())->pluck('name'));
        $this->assertInstanceOf(stdClass::class, (new HardeningRecord())->pluck('settings')[0]);
        $this->cache();
        $first = (new HardeningPlainRecord())->cache()->pluck('id');
        $queries = count($this->db->queries);
        $this->assertSame($first, (new HardeningPlainRecord())->cache()->pluck('id'));
        $this->assertSame($queries, count($this->db->queries));
        $this->assertTrue((new DMZ_QueryBuilder(new HardeningPlainRecord()))->where('active', 1)->exists());
        $this->assertStringNotContainsString('COUNT(', end($this->db->queries));
        $this->assertStringContainsString('LIMIT 1', end($this->db->queries));
        $this->assertFalse((new HardeningPlainRecord())->exists());
        $this->assertFalse((new DMZ_QueryBuilder((new HardeningPlainRecord())->where('id', 99)->select_sum('counter')))->exists());
    }

    public function test_aggregates_execute_sql_and_preserve_query_filters(): void
    {
        $this->db->query('UPDATE hardening_records SET counter=id*10');
        $this->assertSame(60, (new HardeningPlainRecord())->sum('counter'));
        $this->assertSame(20.0, (new HardeningPlainRecord())->average('counter'));
        $this->assertSame(10, (new HardeningPlainRecord())->min('counter'));
        $this->assertSame(30, (new HardeningPlainRecord())->max('counter'));
        $builder = new DMZ_QueryBuilder((new HardeningPlainRecord())->where('active', 1)->select('name'));
        $this->assertSame(40, $builder->sum('counter'));
        $this->assertSame(20.0, $builder->avg('counter'));
        $this->assertSame(array('Ada', 'Cat'), $builder->get()->collect()->pluck('name'));
        $this->assertNull((new HardeningPlainRecord())->where('id', 99)->avg('counter'));
    }

    public function test_terminal_helpers_report_real_sql_errors(): void
    {
        new HardeningPlainRecord();
        $this->db->query('DROP TABLE hardening_records');
        $operations = array(
            function () { return (new HardeningPlainRecord())->sum('counter'); },
            function () { return (new DMZ_QueryBuilder(new HardeningPlainRecord()))->count(); },
            function () { return (new DMZ_QueryBuilder(new HardeningPlainRecord()))->exists(); },
            function () { return (new HardeningPlainRecord())->chunk(2, function () {}); },
            function () { return iterator_to_array((new HardeningPlainRecord())->cursor()); },
            function () { return (new HardeningPlainRecord())->lazy(2)->to_array(); },
        );
        foreach ($operations as $operation) {
            try {
                @$operation();
                $this->fail('Failed query was treated as an empty result');
            } catch (DataMapper_Database_Exception $expected) {
                $this->assertNotSame('', $expected->getMessage());
            }
        }
    }

    public function test_lazy_operations_keep_order_and_null_values(): void
    {
        $this->assertSame(array(NULL, NULL, NULL), (new HardeningPlainRecord())->lazy(2)->map(function () { return NULL; })->to_array());
        $this->assertSame(array(), (new HardeningPlainRecord())->order_by('id')->lazy(1)->take(1)->filter(function ($row) { return $row->active === 0; })->to_array());
        $this->assertSame(array(2), (new HardeningPlainRecord())->order_by('id')->lazy(1)->filter(function ($row) { return $row->active === 0; })->take(1)->pluck('id')->to_array());
        $this->assertSame(array(2), (new HardeningPlainRecord())->order_by('id')->lazy(1)->skip(1)->take(1)->pluck('id')->to_array());
        $this->assertSame(array(), (new HardeningPlainRecord())->lazy(1)->take(1)->skip(1)->to_array());
        $this->assertSame(array(1, 2, 3), (new HardeningPlainRecord())->lazy(1)->unique('id')->pluck('id')->unique()->to_array());
    }

    public function test_eager_constraints_survive_all_batch_helpers_and_first_or_new(): void
    {
        $this->relations();
        $query = function () {
            return (new HardeningAccount())->with(array('entry' => function ($query) { $query->where('name', 'absent'); }));
        };
        $check = function ($rows) {
            foreach ($rows as $row) {
                $this->assertInstanceOf(DMZ_Collection::class, $row->entry);
                $this->assertSame(0, $row->entry->count());
            }
        };
        $this->assertTrue($query()->chunk(1, $check));
        $this->assertTrue($query()->chunk_by_id(1, $check));
        foreach (array($query()->lazy(1), $query()->lazy_by_id(1), $query()->cursor()) as $rows) { $check($rows); }
        $found = $query()->first_or_new(array('account_key' => 'a'));
        $this->assertInstanceOf(DMZ_Collection::class, $found->entry);
        $this->assertSame(0, $found->entry->count());
    }

    public function test_limited_eager_queries_bound_hydration_and_keep_per_parent_offsets(): void
    {
        $this->relations();
        $this->db->query("WITH RECURSIVE n(x) AS (SELECT 100 UNION ALL SELECT x+1 FROM n WHERE x<1099) INSERT INTO hardening_entries SELECT x,'a',1,'entry' FROM n");
        HardeningCountingEntry::$hydrated = 0;
        $rows = (new HardeningCountingAccount())->with(array('entry' => function ($query) { $query->order_by('entry_key')->limit(1); }))->get();
        $this->assertSame(2, HardeningCountingEntry::$hydrated);
        $this->assertSame(array(10), $rows->all[0]->entry->ids());
        $this->assertSame(array(11), $rows->all[1]->entry->ids());
        $rows = (new HardeningCountingAccount())->with(array('entry' => function ($query) { $query->order_by('entry_key')->limit(1, 1); }))->get();
        $this->assertSame(array(100), $rows->all[0]->entry->ids());
        $this->assertSame(array(), $rows->all[1]->entry->ids());
        HardeningCountingEntry::$hydrated = 0;
        (new HardeningCountingAccount())->with(array('entry' => function ($query) { $query->limit(0); }))->get();
        $this->assertSame(0, HardeningCountingEntry::$hydrated);
    }

    public function test_many_to_many_eager_limits_run_in_sql_for_each_parent(): void
    {
        foreach (array(
            'CREATE TABLE tenant_zowners (id INTEGER PRIMARY KEY)',
            'CREATE TABLE tenant_tags (id INTEGER PRIMARY KEY, name TEXT)',
            'CREATE TABLE join_tags_zowners (owner_id INTEGER, tag_id INTEGER)',
            'INSERT INTO tenant_zowners VALUES (1),(2)',
            "INSERT INTO tenant_tags VALUES (10,'a'),(11,'b'),(12,'c')",
            'INSERT INTO join_tags_zowners VALUES(1,10),(1,11),(2,11),(2,12)',
        ) as $sql) { $this->assertTrue($this->db->query($sql)); }
        $start = count($this->db->queries);
        $rows = (new HardeningOwner())->with(array('tag' => function ($query) { $query->order_by('id')->limit(1, 1); }))->get();
        $this->assertSame(array(11), $rows->all[0]->tag->ids());
        $this->assertSame(array(12), $rows->all[1]->tag->ids());
        $limited = array_filter(array_slice($this->db->queries, $start), function ($sql) { return preg_match('/\bLIMIT\s+1(?:,\s*1|\s+OFFSET\s+1)\b/i', $sql); });
        $this->assertCount(2, $limited);
        $source = new HardeningOwner();
        $source->start_cache();
        $source->where('id', 1);
        $source->stop_cache();
        $cached_where = $source->db->dm_get('qb_cache_where');
        $selected = $source->with('tag')->get();
        $this->assertSame(array(10, 11), $selected->all[0]->tag->ids());
        $this->assertSame($cached_where, $source->db->dm_get('qb_cache_where'));
    }

    public function test_has_uses_exists_and_count_filters_do_not_duplicate_subqueries(): void
    {
        $this->relations();
        $this->assertSame(array('a', 'b'), (new HardeningAccount())->has('entry')->get()->collect()->ids());
        $sql = end($this->db->queries);
        $this->assertStringContainsString('EXISTS', $sql);
        $this->assertStringNotContainsString('COUNT(', $sql);
        $this->assertSame(array(), (new HardeningAccount())->doesnt_have('entry')->get()->collect()->ids());
        $this->assertStringContainsString('NOT EXISTS', end($this->db->queries));
        $this->assertSame(array('a', 'b'), (new HardeningAccount())->has('entry', '=', 1)->get()->collect()->ids());
        $this->assertSame(1, substr_count(end($this->db->queries), 'COUNT('));
        $this->assertSame(array(1, 1), (new HardeningAccount())->include_related_count('entry')->get()->collect()->pluck('entry_count'));
    }

    /** @dataProvider creationDrivers */
    public function test_creation_helpers_recover_unique_conflicts_without_double_saving($driver, $automatic, $catches): void
    {
        if ($driver === 'pdo') {
            $schema = $this->db->query("SELECT sql FROM sqlite_master WHERE name='hardening_records'")->row()->sql;
            $this->db = Ci3Database::connect(array('dbdriver' => 'pdo', 'dsn' => 'sqlite::memory:', 'options' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)));
            get_instance()->db = $this->db;
            $this->db->query($schema);
        }
        $this->db->query('CREATE UNIQUE INDEX hardening_unique_name ON hardening_records(name)');
        $this->db->db_debug = TRUE;
        $class = $catches ? HardeningCatchingRaceRecord::class : HardeningRaceRecord::class;
        foreach (array(FALSE, TRUE) as $update) {
            HardeningRaceRecord::$events = array();
            HardeningRaceRecord::$race = TRUE;
            $source = new $class();
            $source->auto_transaction = $automatic;
            $name = $update ? 'concurrent update' : 'concurrent create';
            $result = $update
                ? $source->update_or_create(array('name' => $name), array('counter' => 5))
                : $source->first_or_create(array('name' => $name));
            $this->assertInstanceOf(HardeningRaceRecord::class, $result);
            $this->assertSame(1, (int) $this->db->where('name', $name)->count_all_results('hardening_records'));
            $this->assertSame($update ? array('before_create', 'before_update', 'after_update') : array('before_create'), HardeningRaceRecord::$events);
            $this->assertSame(0, $this->db->dm_get('_trans_depth'));
            $this->assertTrue($this->db->trans_status());
            $this->assertTrue($source->db->db_debug);
            if ($update) { $this->assertSame(5, $result->counter); }
        }
        HardeningRaceRecord::$events = array();
        $this->assertInstanceOf(HardeningRaceRecord::class, (new HardeningRaceRecord())->update_or_create(array('name' => 'ordinary create')));
        $this->assertSame(array('before_create', 'after_create'), HardeningRaceRecord::$events);
    }

    public static function creationDrivers(): array
    {
        return array(
            array('sqlite3', FALSE, FALSE), array('sqlite3', TRUE, FALSE),
            array('pdo', FALSE, FALSE), array('pdo', TRUE, FALSE),
            array('sqlite3', FALSE, TRUE), array('sqlite3', TRUE, TRUE),
            array('pdo', FALSE, TRUE), array('pdo', TRUE, TRUE),
        );
    }

    public function test_unique_conflict_savepoint_preserves_the_outer_transaction(): void
    {
        $this->db->query('CREATE UNIQUE INDEX hardening_unique_name ON hardening_records(name)');
        $this->assertTrue($this->db->trans_begin());
        $this->db->query("UPDATE hardening_records SET counter=7 WHERE id=2");
        HardeningMissOnceRecord::$miss = TRUE;
        $source = new HardeningMissOnceRecord();
        $source->auto_transaction = TRUE;
        $result = $source->first_or_create(array('name' => 'Ada'));
        $this->assertSame(1, $result->id);
        $this->assertSame(1, $this->db->dm_get('_trans_depth'));
        $this->assertTrue($this->db->trans_status());
        $this->assertTrue($this->db->trans_complete());
        $this->assertSame(7, (int) $this->db->get_where('hardening_records', array('id' => 2))->row()->counter);
    }

    public function test_creation_retry_does_not_hide_event_cancellation_or_other_errors(): void
    {
        HardeningMissOnceRecord::$miss = TRUE;
        HardeningMissOnceRecord::$cancel = TRUE;
        try {
            $this->assertFalse((new HardeningMissOnceRecord())->first_or_create(array('name' => 'Ada')));
        } finally {
            HardeningMissOnceRecord::$cancel = FALSE;
        }
        $this->db->query('CREATE TABLE hardening_required (id INTEGER PRIMARY KEY, name TEXT UNIQUE, email TEXT NOT NULL)');
        $this->db->query("INSERT INTO hardening_required VALUES(1,'existing','valid')");
        $this->assertFalse(@(new HardeningRequiredRecord())->first_or_create(array('name' => 'other')));
        $this->assertSame(1, $this->db->count_all('hardening_required'));
    }

    public function test_cached_relation_collisions_and_missing_relations_preserve_columns(): void
    {
        $this->relations();
        $this->db->query('ALTER TABLE hardening_entries ADD COLUMN author TEXT');
        $this->db->query("UPDATE hardening_entries SET author='legacy text', author_id=NULL WHERE entry_key=11");
        $this->cache();
        foreach (array(HardeningEntry::class, HardeningCollisionEntry::class) as $class) {
            (new $class())->where('entry_key', 11)->cache_relations()->with('author')->first();
            $queries = count($this->db->queries);
            $hit = (new $class())->where('entry_key', 11)->cache_relations()->with('author')->first();
            $this->assertSame($queries, count($this->db->queries));
            $this->assertNull($hit->author);
            $this->assertFalse($hit->is_dirty('author'));
            $hit->name = 'cache edit ' . $class;
            $this->assertTrue($hit->save());
            $this->assertSame('legacy text', $this->db->get_where('hardening_entries', array('entry_key' => 11))->row()->author);
            (new $class())->where('entry_key', 11)->cache()->get();
            $plain_hit = (new $class())->where('entry_key', 11)->cache()->get();
            $this->assertSame('legacy text', $plain_hit->author);
        }
    }

    private function relations(): void
    {
        foreach (array(
            'CREATE TABLE hardening_accounts (account_key TEXT PRIMARY KEY, name TEXT)',
            'CREATE TABLE hardening_entries (entry_key INTEGER PRIMARY KEY, account_id TEXT, author_id INTEGER, name TEXT)',
            'CREATE TABLE hardening_authors (id INTEGER PRIMARY KEY, name TEXT)',
            'CREATE TABLE hardening_comments (id INTEGER PRIMARY KEY, entry_id INTEGER, name TEXT)',
            "INSERT INTO hardening_accounts VALUES ('a','A'),('b','B')",
            "INSERT INTO hardening_entries VALUES (10,'a',1,'entry'),(11,'b',1,'other')",
            "INSERT INTO hardening_authors VALUES (1,'author')",
            "INSERT INTO hardening_comments VALUES (1,10,'comment')",
        ) as $sql) {
            $this->assertTrue($this->db->query($sql));
        }
    }

    public function test_nested_siblings_and_full_path_constraints(): void
    {
        $this->relations();
        $called = 0;
        $accounts = (new HardeningAccount())->with(array('entry.author', 'entry.comment' => function ($query) use (&$called) {
            $called++;
            $query->where('name', 'absent');
        }))->get();
        $entry = $accounts->all[0]->entry->first();
        $this->assertSame(1, $called);
        $this->assertSame('author', $entry->author->name);
        $this->assertSame(0, $entry->comment->count());
        $this->assertCount(2, $accounts->all);
    }

    public function test_auto_join_table_and_custom_prefix_work_on_repeated_loads(): void
    {
        foreach (array('CREATE TABLE tenant_zowners (id INTEGER PRIMARY KEY)', 'CREATE TABLE tenant_tags (id INTEGER PRIMARY KEY, name TEXT)', 'CREATE TABLE join_tags_zowners (owner_id INTEGER, tag_id INTEGER)', 'INSERT INTO tenant_zowners VALUES (1)', "INSERT INTO tenant_tags VALUES (2,'tag')", 'INSERT INTO join_tags_zowners VALUES (1,2)') as $sql) {
            $this->assertTrue($this->db->query($sql));
        }
        for ($i = 0; $i < 2; $i++) {
            $owners = (new HardeningOwner())->with('tag')->get();
            $this->assertSame(array('tag'), $owners->all[0]->tag->pluck('name'));
        }
    }

    public function test_keyset_iteration_groups_or_and_replaces_conflicting_order(): void
    {
        $seen = array();
        (new HardeningRecord())->order_by('id', 'DESC')->chunk_by_id(2, function ($chunk) use (&$seen) {
            $seen = array_merge($seen, $chunk->ids());
            return count($seen) < 10;
        });
        $this->assertSame(array(1, 2, 3), $seen);
        $this->assertSame(array(1, 3), (new HardeningRecord())->where('name', 'Ada')->or_where('active', 1)->lazy_by_id(1)->take(5)->pluck('id')->to_array());
    }

    /** @dataProvider cachedKeysetModes */
    public function test_keyset_iteration_preserves_cached_conditions_on_the_source($lazy, $active): void
    {
        $model = new HardeningRecord();
        $model->db->start_cache();
        $model->where('name', 'Ada')->or_where('active', 1);
        if (!$active) { $model->db->stop_cache(); }
        $state = array();
        foreach (array('qb_where', 'qb_cache_where', 'qb_cache_exists', 'qb_caching') as $property) {
            $state[$property] = $model->db->dm_get($property);
        }
        $seen = array();
        if ($lazy) {
            $seen = $model->lazy_by_id(1)->take(5)->pluck('id')->to_array();
        } else {
            $this->assertTrue($model->chunk_by_id(1, function ($chunk) use (&$seen) {
                $seen = array_merge($seen, $chunk->ids());
                return count($seen) < 5;
            }));
        }
        $this->assertSame(array(1, 3), $seen);
        foreach ($state as $property => $value) { $this->assertSame($value, $model->db->dm_get($property)); }
    }

    public static function cachedKeysetModes(): array
    {
        return array(array(FALSE, FALSE), array(FALSE, TRUE), array(TRUE, FALSE), array(TRUE, TRUE));
    }

    public function test_chunk_by_id_replaces_cached_order_only_on_its_clones(): void
    {
        $model = new HardeningRecord();
        $model->db->start_cache();
        $model->order_by('id', 'DESC');
        $model->db->stop_cache();
        $model->get();
        $cached = $model->db->dm_get('qb_cache_orderby');
        $seen = array();
        $this->assertTrue($model->chunk_by_id(2, function ($chunk) use (&$seen) {
            $seen = array_merge($seen, $chunk->ids());
            return count($seen) < 6;
        }));
        $this->assertSame(array(1, 2, 3), $seen);
        $this->assertSame($cached, $model->db->dm_get('qb_cache_orderby'));
        $this->assertSame(array(3, 2, 1), $model->get()->collect()->ids());
    }

    public function test_lazy_by_id_rejects_cached_ordering(): void
    {
        $model = new HardeningRecord();
        $model->db->start_cache();
        $model->order_by('id', 'DESC');
        $model->db->stop_cache();
        $model->get();
        $this->assertSame(array(), $model->db->dm_get('qb_orderby'));
        $this->expectException(InvalidArgumentException::class);
        $model->lazy_by_id(1)->take(5)->to_array();
    }

    public function test_relation_counts_and_has_use_custom_parent_key(): void
    {
        $this->relations();
        $models = (new HardeningAccount())->include_related_count('entry')->get();
        $this->assertSame(array(1, 1), $models->collect()->pluck('entry_count'));
        $this->assertSame(array('a', 'b'), (new HardeningAccount())->has('entry')->get()->collect()->ids());
        $this->assertSame(array('a'), (new HardeningAccount())->where_has('entry', function ($query) {
            $query->where('name', 'entry');
        })->get()->collect()->ids());
    }

    public function test_eager_or_constraint_stays_within_selected_parent(): void
    {
        $this->relations();
        $model = (new HardeningAccount())->where('account_key', 'a')->with(array('entry' => function ($query) {
            $query->where('name', 'entry')->or_where('name', 'other');
        }))->get()->all[0];
        $this->assertSame(array(10), $model->entry->pluck('entry_key'));
    }

    public function test_relation_graph_cache_reuses_graph_and_invalidates_child_writes(): void
    {
        $this->relations();
        $this->cache();
        (new HardeningAccount())->cache_relations()->with('entry.author', 'entry.comment')->get();
        $queries = count($this->db->queries);
        $hit = (new HardeningAccount())->cache_relations()->with('entry.author', 'entry.comment')->get();
        $this->assertSame($queries, count($this->db->queries));
        $this->assertSame('author', $hit->all[0]->entry->first()->author->name);
        $this->assertTrue((new HardeningEntry())->where('entry_key', 10)->update('name', 'changed'));
        $fresh = (new HardeningAccount())->cache_relations()->with('entry.author', 'entry.comment')->get();
        $this->assertSame('changed', $fresh->all[0]->entry->first()->name);
    }

    public function test_collect_fetches_pending_query_and_preserves_loaded_collection(): void
    {
        $this->assertSame(array(1, 3), (new HardeningRecord())->where('active', 1)->collect()->ids());
        $loaded = (new HardeningRecord())->get(1);
        $queries = count($this->db->queries);
        $this->assertSame(array(1), $loaded->collect()->ids());
        $this->assertSame($queries, count($this->db->queries));
        $this->assertSame(array(2), $loaded->where('id', 2)->collect()->ids());
    }

    public function test_nested_set_failure_restores_boundaries(): void
    {
        $this->db->query('CREATE TABLE hardening_nodes (id INTEGER PRIMARY KEY AUTOINCREMENT, left_id INTEGER, right_id INTEGER, root_id INTEGER, name TEXT)');
        $this->db->query("INSERT INTO hardening_nodes VALUES (1,1,2,1,'root')");
        $extension = new DMZ_Nestedsets(array('value' => 1), new HardeningNode());
        $parent = (new HardeningNode())->get_by_id(1);
        $child = new HardeningNode();
        $child->cancel = TRUE;
        $this->assertFalse($extension->new_last_child($child, $parent));
        $this->assertSame(2, (int) $parent->fresh()->right_id);
        $child->cancel = FALSE;
        $child->name = 'child';
        $this->assertSame($child, $extension->new_last_child($child, $parent));
        $this->assertSame(4, (int) $parent->fresh()->right_id);
        $parent->name = 'unsaved root';
        $this->assertSame($parent, $extension->new_first_child($parent));
        $this->assertSame('unsaved root', $parent->fresh()->name);
        $this->assertSame(6, (int) $this->db->get_where('hardening_nodes', array('id' => 1))->row()->right_id);
        $this->assertTrue($this->db->trans_begin());
        $this->assertNull($extension->remove_tree(new HardeningNode(), 1));
        $this->assertSame(0, $this->db->count_all('hardening_nodes'));
        $this->assertTrue($this->db->trans_rollback());
        $this->assertSame(3, $this->db->count_all('hardening_nodes'));
    }

    public function test_nested_set_refresh_uses_the_node_connection_and_preserves_edits(): void
    {
        $schema = 'CREATE TABLE hardening_nodes (id INTEGER PRIMARY KEY AUTOINCREMENT, left_id INTEGER, right_id INTEGER, root_id INTEGER, name TEXT)';
        $this->db->query($schema);
        $this->db->query("INSERT INTO hardening_nodes VALUES (1,1,6,1,'global'),(2,2,3,1,'a'),(3,4,5,1,'b')");
        $target = Ci3Database::connect();
        $target->query($schema);
        $target->query("INSERT INTO hardening_nodes VALUES (1,1,2,1,'target')");
        $parent = new HardeningNode();
        $parent->db = $target;
        $parent->get_by_id(1);
        $parent->name = 'unsaved parent';
        $parent->skip_validation(TRUE);
        $child = new HardeningNode();
        $child->db = $target;
        $child->name = 'unsaved child';
        $extension = new DMZ_Nestedsets(array('value' => 1), $child);
        $this->assertSame($child, $extension->new_last_child($child, $parent));
        $this->assertSame(array(2, 3), array($child->left_id, $child->right_id));
        $this->assertSame('unsaved parent', $parent->name);
        $this->assertSame('unsaved child', $target->get_where('hardening_nodes', array('id' => $child->id))->row()->name);
        $this->assertSame(4, (int) $target->get_where('hardening_nodes', array('id' => 1))->row()->right_id);
        $this->assertSame(6, (int) $this->db->get_where('hardening_nodes', array('id' => 1))->row()->right_id);
    }

    public function test_nested_set_missing_parent_returns_false_without_writing(): void
    {
        $this->db->query('CREATE TABLE hardening_nodes (id INTEGER PRIMARY KEY AUTOINCREMENT, left_id INTEGER, right_id INTEGER, root_id INTEGER)');
        $this->db->query('INSERT INTO hardening_nodes VALUES (1,1,2,1)');
        $parent = (new HardeningNode())->get_by_id(1);
        $this->db->where('id', 1)->delete('hardening_nodes');
        $extension = new DMZ_Nestedsets(array('value' => 1), new HardeningNode());
        $this->assertFalse($extension->new_last_child(new HardeningNode(), $parent));
        $this->assertSame(0, $this->db->count_all('hardening_nodes'));
        $this->assertSame(0, $this->db->dm_get('_trans_depth'));
        $this->assertTrue($this->db->trans_status());
    }
}

abstract class HardeningModel extends DataMapper
{
    public $validation = array('id' => array('rules' => array()));
}
class HardeningRecord extends HardeningModel
{
    public $table = 'hardening_records';
    protected $casts = array('settings' => 'object', 'published_at' => 'datetime');
}
class HardeningPlainRecord extends HardeningModel { public $table = 'hardening_records'; }
class HardeningAppendedRecord extends HardeningPlainRecord
{
    public $appends = array('email', 'caption');
    public function get_email_attribute() { return 'private@example.com'; }
    public function get_caption_attribute() { return $this->name . '!'; }
}
class HardeningLegacyGetter extends HardeningPlainRecord
{
    public function __get($name) { return $name === 'virtual' ? 'legacy' : parent::__get($name); }
}
class HardeningCastingTraitRecord extends HardeningPlainRecord
{
    use DMZ_AttributeCasting;
    protected $casts = array('settings' => 'array');
    public function getCaptionAttribute() { return $this->name . '!'; }
}
class HardeningCustomTraits extends HardeningModel
{
    use HasTimestamps, SoftDeletes;
    public $table = 'hardening_traits';
    protected $created_at_column = 'created';
    protected $updated_at_column = 'modified';
    protected $timestamp_format = 'U';
    protected $deleted_at_column = 'archived';
    protected $soft_delete_writes = TRUE;
}
class HardeningDecimalRecord extends HardeningPlainRecord
{
    public function usesFloat($type) { return $this->_is_float_field_type($type); }
}
class HardeningJsonRecord extends HardeningPlainRecord
{
    public $family;
    protected function _get_database_driver_family() { return $this->family; }
}
class HardeningTransformRecord extends HardeningRecord
{
    public $mutations = 0;
    public function setEmailAttribute($value) { $this->mutations++; $this->email = strtolower($value); }
    public function getNameAttribute() { return strtoupper($this->name); }
}
class HardeningFailingMutator extends HardeningTransformRecord
{
    public function setEmailAttribute($value)
    {
        $this->email = $value;
        if ($value === 'reject') { throw new InvalidArgumentException('Rejected'); }
        parent::setEmailAttribute($value);
    }
}
class HardeningSoftRecord extends HardeningRecord { use SoftDeletes; protected $soft_delete_writes = TRUE; }
class HardeningAccount extends HardeningModel
{
    public $table = 'hardening_accounts';
    public $model = 'account';
    public $primary_key = 'account_key';
    public $has_many = array('entry' => array('class' => 'HardeningEntry', 'other_field' => 'account'));
}
class HardeningEntry extends HardeningModel
{
    public $table = 'hardening_entries';
    public $model = 'entry';
    public $primary_key = 'entry_key';
    public $has_one = array('account' => array('class' => 'HardeningAccount', 'other_field' => 'entry'), 'author' => array('class' => 'HardeningAuthor', 'other_field' => 'entry'));
    public $has_many = array('comment' => array('class' => 'HardeningComment', 'other_field' => 'entry'));
}
class HardeningCollisionEntry extends HardeningEntry { protected $casts = array('author' => 'string'); }
class HardeningCountingAccount extends HardeningAccount
{
    public $has_many = array('entry' => array('class' => 'HardeningCountingEntry', 'other_field' => 'account'));
}
class HardeningCountingEntry extends HardeningEntry
{
    public static $hydrated = 0;
    public $has_one = array('account' => array('class' => 'HardeningCountingAccount', 'other_field' => 'entry'));
    public function _to_object($item, $row) { self::$hydrated++; parent::_to_object($item, $row); }
}
class HardeningRaceRecord extends HardeningPlainRecord
{
    public static $race = FALSE;
    public static $events = array();
    protected function before_create()
    {
        self::$events[] = 'before_create';
        if (self::$race) {
            self::$race = FALSE;
            $this->db->insert($this->table, array('name' => $this->name));
        }
    }
    protected function after_create() { self::$events[] = 'after_create'; }
    protected function before_update() { self::$events[] = 'before_update'; }
    protected function after_update() { self::$events[] = 'after_update'; }
}
class HardeningCatchingRaceRecord extends HardeningRaceRecord
{
    public function save($object = '', $related_field = '')
    {
        try { return parent::save($object, $related_field); }
        catch (Exception $error) { return FALSE; }
    }
}
class HardeningMissOnceRecord extends HardeningPlainRecord
{
    public $auto_transaction = TRUE;
    public static $miss = FALSE;
    public static $cancel = FALSE;
    protected function before_create() { return !self::$cancel; }
    public function get($limit = NULL, $offset = NULL)
    {
        if (self::$miss) {
            self::$miss = FALSE;
            $this->clear();
            $this->_last_query_succeeded = TRUE;
            return $this;
        }
        return parent::get($limit, $offset);
    }
}
class HardeningRequiredRecord extends HardeningModel { public $table = 'hardening_required'; }
class HardeningAuthor extends HardeningModel
{
    public $table = 'hardening_authors';
    public $model = 'author';
    public $has_many = array('entry' => array('class' => 'HardeningEntry', 'other_field' => 'author'));
}
class HardeningComment extends HardeningModel
{
    public $table = 'hardening_comments';
    public $model = 'comment';
    public $has_one = array('entry' => array('class' => 'HardeningEntry', 'other_field' => 'comment'));
}
class HardeningOwner extends HardeningModel
{
    public $table = 'zowners';
    public $model = 'owner';
    public $prefix = 'tenant_';
    public $join_prefix = 'join_';
    public $has_many = array('tag' => array('class' => 'HardeningTag', 'other_field' => 'owner'));
}
class HardeningTag extends HardeningModel
{
    public $table = 'tags';
    public $model = 'tag';
    public $prefix = 'tenant_';
    public $join_prefix = 'join_';
    public $has_many = array('owner' => array('class' => 'HardeningOwner', 'other_field' => 'tag'));
}
class HardeningNode extends HardeningModel
{
    public $table = 'hardening_nodes';
    public $cancel = FALSE;
    protected function before_create() { return !$this->cancel; }
}
