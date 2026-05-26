<?php

namespace Tests;

use DMZ_QueryBuilder;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeDataMapper;

require_once APPPATH . 'datamapper/querybuilder.php';

class DataMapperBackwardCompatibilityTest extends TestCase
{
    public function testLegacyWhereOrderAndGetStillReturnTheSameMapper(): void
    {
        $mapper = new FakeDataMapper(array(
            array('id' => 1, 'status' => 'active'),
            array('id' => 2, 'status' => 'inactive'),
        ));

        $result = $mapper
            ->where('status', 'active')
            ->order_by('id', 'desc')
            ->get(1);

        $this->assertSame($mapper, $result);
        $this->assertSame(array('where', 'status', 'active', true), $mapper->queryLog[0]);
        $this->assertSame(array('order_by', 'id', 'desc'), $mapper->queryLog[1]);
        $this->assertCount(1, $mapper->all);
        $this->assertSame(1, $mapper->all[0]->id);
    }

    public function testWithReturnsBuilderWithoutMutatingOriginalMapperQueryLog(): void
    {
        $mapper = new FakeDataMapper(array(array('id' => 1)));

        $builder = $mapper->with('profile');

        $this->assertInstanceOf(DMZ_QueryBuilder::class, $builder);
        $this->assertSame(array(), $mapper->queryLog);
    }

    public function testConditionalWrappersAreAdditiveAndChainable(): void
    {
        $mapper = new FakeDataMapper(array(array('id' => 1, 'status' => 'active')));

        $result = $mapper
            ->when(true, function ($query) {
                return $query->where('status', 'active');
            })
            ->unless(false, function ($query) {
                return $query->where_not_null('email');
            });

        $this->assertSame($mapper, $result);
        $this->assertSame(array('where', 'status', 'active', true), $mapper->queryLog[0]);
        $this->assertSame(array('where', 'email IS NOT NULL', null, false), $mapper->queryLog[1]);
    }

    public function testConditionalWrapperCanReturnBuilder(): void
    {
        $mapper = new FakeDataMapper(array(array('id' => 1)));

        $result = $mapper->when(true, function ($query) {
            return $query->with('profile');
        });

        $this->assertInstanceOf(DMZ_QueryBuilder::class, $result);
    }

    public function testOrderingShortcutsDelegateToLegacyOrderBy(): void
    {
        $mapper = new FakeDataMapper(array(array('id' => 1, 'created_at' => '2026-05-26')));

        $mapper
            ->order_by_desc('name')
            ->latest()
            ->oldest('updated_at');

        $this->assertSame(array('order_by', 'name', 'desc'), $mapper->queryLog[0]);
        $this->assertSame(array('order_by', 'created_at', 'desc'), $mapper->queryLog[1]);
        $this->assertSame(array('order_by', 'updated_at', 'asc'), $mapper->queryLog[2]);
    }

    public function testLatestUsesCustomTimestampColumnWhenAvailable(): void
    {
        $mapper = new CustomTimestampFakeDataMapper(array(array('id' => 1, 'created_on' => '2026-05-26')));

        $mapper->latest();

        $this->assertSame(array('order_by', 'created_on', 'desc'), $mapper->queryLog[0]);
    }
}

class CustomTimestampFakeDataMapper extends FakeDataMapper
{
    public function get_created_at_column(): string
    {
        return 'created_on';
    }
}