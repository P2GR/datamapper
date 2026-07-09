<?php

namespace Tests;

use DMZ_Collection;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeDataMapper;

require_once APPPATH . 'datamapper/querybuilder.php';

class DMZCollectionTest extends TestCase
{
    public function test_where_in_filters_collection(): void
    {
        $rows = array(
            array('id' => 1, 'role' => 'admin'),
            array('id' => 2, 'role' => 'user'),
            array('id' => 3, 'role' => 'editor'),
        );

        $mapper = new FakeDataMapper($rows);
        $filtered = $mapper->collect()->where_in('role', array('admin', 'editor'));

        $this->assertSame(array('admin', 'editor'), $filtered->pluck('role'));
    }

    public function test_flat_map_flattens_nested_results(): void
    {
        $collection = new DMZ_Collection(array(
            array('tags' => array('php', 'orm')),
            array('tags' => array('collections')),
        ));

        $tags = $collection->flat_map(function ($item) {
            return $item['tags'];
        });

        $this->assertSame(array('php', 'orm', 'collections'), $tags->to_array());
    }

    public function test_sort_by_desc_orders_items(): void
    {
        $rows = array(
            array('id' => 1, 'score' => 25),
            array('id' => 2, 'score' => 50),
            array('id' => 3, 'score' => 10),
        );

        $mapper = new FakeDataMapper($rows);
        $scores = $mapper->collect()->sort_by_desc('score')->pluck('score');

        $this->assertSame(array(50, 25, 10), $scores);
    }

    public function test_is_not_empty_flag(): void
    {
        $empty = new DMZ_Collection();
        $this->assertTrue($empty->is_empty());
        $this->assertFalse($empty->is_not_empty());

        $filled = new DMZ_Collection(array(1));
        $this->assertFalse($filled->is_empty());
        $this->assertTrue($filled->is_not_empty());
    }

    public function test_to_data_mapper_returns_cloned_instance(): void
    {
        $rows = array(
            array('id' => 1, 'name' => 'First'),
            array('id' => 2, 'name' => 'Second'),
        );

        $mapper = new FakeDataMapper($rows);
        $collection = $mapper->collect();
        $result = $collection->to_data_mapper();

        $this->assertInstanceOf(FakeDataMapper::class, $result);
        $this->assertCount(2, $result->all);
        $this->assertNotSame($collection->first(), $result->all[0]);
        $this->assertSame('First', $result->all[0]->name);
    }

    public function test_sum_with_field_name(): void
    {
        $collection = new DMZ_Collection(array(
            array('total' => 10),
            array('total' => 5),
            array('total' => 2),
        ));

        $this->assertSame(17, $collection->sum('total'));
    }
}
