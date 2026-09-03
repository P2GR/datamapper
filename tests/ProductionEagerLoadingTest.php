<?php

namespace Tests;

use DMZ_Collection;
use DMZ_QueryBuilder;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeDataMapper;

require_once APPPATH . 'datamapper/querybuilder.php';

class ProductionEagerLoadingTest extends TestCase
{
    public function test_has_many_uses_normalized_aliases_custom_keys_and_per_parent_limit(): void
    {
        $first = new ProductionEagerParent('account-a');
        $second = new ProductionEagerParent('account-b');
        $builder = new ProductionEagerQueryBuilder($first);
        $builder->with(array('entries' => function ($query) {
            $query->limit(1);
        }));

        $builder->loadRelations(new DMZ_Collection(array($first, $second), $first));

        $this->assertSame(array(11), $first->entries->pluck('entry_uuid'));
        $this->assertSame(array(21), $second->entries->pluck('entry_uuid'));
        $this->assertSame(
            array('where_in', 'owner_id', array('account-a', 'account-b')),
            ProductionEagerChild::$lastQuery[0]
        );
        $this->assertSame(4, ProductionEagerChild::$loadedRows);
    }

    public function test_many_to_many_query_failure_is_propagated(): void
    {
        $parent = new ProductionEagerParent('account-a');
        $parent->db = new FailedManyToManyDatabaseStub();
        $builder = new ProductionEagerQueryBuilder($parent);

        $this->expectException(\DataMapper_Database_Exception::class);
        $builder->loadManyToMany(
            new DMZ_Collection(array($parent), $parent),
            array(
                'class' => ProductionEagerChild::class,
                'join_table' => 'account_entries',
                '_dm_parent_alias' => 'account',
                '_dm_related_alias' => 'entry',
                '_dm_parent_key' => 'account_uuid',
                '_dm_related_key' => 'entry_uuid',
            )
        );
    }
}

class ProductionEagerQueryBuilder extends DMZ_QueryBuilder
{
    public function loadRelations(DMZ_Collection $results): void
    {
        $this->_load_eager_relations($results);
    }

    public function loadManyToMany(DMZ_Collection $results, array $config): void
    {
        $this->_load_many_to_many($results, 'entries', $config, array('account-a'));
    }
}

class ProductionEagerParent extends FakeDataMapper
{
    public $primary_key = 'account_uuid';

    public function __construct($key = NULL)
    {
        parent::__construct();
        $this->model = 'account';
        $this->table = 'accounts';
        $this->fields = array('account_uuid');
        $this->account_uuid = $key;
        $this->has_many = array(
            'entries' => array(
                'class' => ProductionEagerChild::class,
                'other_field' => 'account',
                'join_self_as' => 'owner',
                'join_other_as' => 'entry',
                'join_table' => '',
            ),
        );
        $this->has_one = array();
    }
}

class ProductionEagerChild extends FakeDataMapper
{
    public static $lastQuery = array();
    public static $loadedRows = 0;
    public $primary_key = 'entry_uuid';
    private $whereInValues = array();

    public function __construct()
    {
        parent::__construct(array(
            array('entry_uuid' => 11, 'owner_id' => 'account-a'),
            array('entry_uuid' => 12, 'owner_id' => 'account-a'),
            array('entry_uuid' => 21, 'owner_id' => 'account-b'),
            array('entry_uuid' => 22, 'owner_id' => 'account-b'),
            array('entry_uuid' => 31, 'owner_id' => 'account-c'),
        ));
        $this->model = 'entry';
        $this->table = 'entries';
        $this->fields = array('entry_uuid', 'owner_id');
        $this->has_many = array();
        $this->has_one = array();
        $this->db = new ProductionEagerDatabaseStub();
    }

    public function get($limit = NULL, $offset = NULL)
    {
        parent::get($limit, $offset);
        if (!empty($this->whereInValues)) {
            $this->all = array_values(array_filter($this->all, function ($row) {
                return in_array($row->owner_id, $this->whereInValues, TRUE);
            }));
        }
        self::$loadedRows = count($this->all);
        $models = array();
        foreach ($this->all as $row) {
            $model = clone $this;
            $model->entry_uuid = $row->entry_uuid;
            $model->owner_id = $row->owner_id;
            $model->all = array();
            $models[] = $model;
        }
        $this->all = $models;
        return $this;
    }

    public function where_in($field = NULL, $values = NULL)
    {
        $this->queryLog[] = array('where_in', $field, $values);
        $this->whereInValues = (array) $values;
        self::$lastQuery = $this->queryLog;
        return $this;
    }

    public function limit($limit, $offset = NULL)
    {
        $this->db->dm_set('qb_limit', $limit);
        $this->db->dm_set('qb_offset', $offset);
        return $this;
    }
}

class ProductionEagerDatabaseStub
{
    private $state = array(
        'qb_limit' => FALSE,
        'qb_offset' => FALSE,
    );

    public function dm_get($key)
    {
        return isset($this->state[$key]) ? $this->state[$key] : NULL;
    }

    public function dm_set($key, $value): void
    {
        $this->state[$key] = $value;
    }
}

class FailedManyToManyDatabaseStub
{
    private $state = array(
        'qb_limit' => FALSE,
        'qb_offset' => FALSE,
    );

    public function reset_query()
    {
        return $this;
    }

    public function select($fields)
    {
        return $this;
    }

    public function from($table)
    {
        return $this;
    }

    public function join($table, $condition)
    {
        return $this;
    }

    public function where_in($field, $values)
    {
        return $this;
    }

    public function dm_get($key)
    {
        return isset($this->state[$key]) ? $this->state[$key] : NULL;
    }

    public function dm_set($key, $value): void
    {
        $this->state[$key] = $value;
    }

    public function get()
    {
        return FALSE;
    }
}
