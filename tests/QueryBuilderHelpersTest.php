<?php

namespace Tests;

use DMZ_Collection;
use DMZ_QueryBuilder;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeDataMapper;

require_once APPPATH . 'datamapper/querybuilder.php';

class InspectableQueryBuilder extends DMZ_QueryBuilder
{
    /**
     * @var array<int, string>
     */
    public $loadedRelations = array();

    /**
     * @var int
     */
    public $loadCallCount = 0;

    public function getEagerLoads(): array
    {
        return $this->eager_loads;
    }

    public function getEagerConstraints(): array
    {
        return $this->eager_constraints;
    }

    protected function _load_eager_relations($results)
    {
        $this->loadCallCount++;
        $this->loadedRelations = $this->eager_loads;
    }
}

class QueryBuilderHelpersTest extends TestCase
{
    public function testCollectReturnsCollection(): void
    {
        $rows = array(
            array('id' => 1, 'email' => 'alice@example.com'),
            array('id' => 2, 'email' => 'bob@example.com'),
        );

        $mapper = new FakeDataMapper($rows);
        $builder = new DMZ_QueryBuilder($mapper);

        $collection = $builder->collect();

        $this->assertInstanceOf(DMZ_Collection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertSame(array(1, 2), $collection->pluck('id'));
    }

    public function testPluckReturnsSimpleArray(): void
    {
        $rows = array(
            array('id' => 10, 'email' => 'first@example.com'),
            array('id' => 11, 'email' => 'second@example.com'),
        );

        $mapper = new FakeDataMapper($rows);
        $builder = new DMZ_QueryBuilder($mapper);

        $emails = $builder->pluck('email');

        $this->assertSame(array('first@example.com', 'second@example.com'), $emails);
    }

    public function testPluckCollectionReturnsCollection(): void
    {
        $rows = array(
            array('id' => 3, 'name' => 'Alpha'),
            array('id' => 4, 'name' => 'Beta'),
        );

        $mapper = new FakeDataMapper($rows);
        $builder = new DMZ_QueryBuilder($mapper);

        $names = $builder->pluck_collection('name');

        $this->assertInstanceOf(DMZ_Collection::class, $names);
        $this->assertSame(array('Alpha', 'Beta'), $names->to_array());
    }

    public function testPluckValuesReturnsPlainArray(): void
    {
        $rows = array(
            array('id' => 20, 'code' => 'X'),
            array('id' => 21, 'code' => 'Y'),
        );

        $mapper = new FakeDataMapper($rows);
        $builder = new DMZ_QueryBuilder($mapper);

        $codes = $builder->pluck_values('code');
        $this->assertSame(array('X', 'Y'), $codes);
    }

    public function testValueReturnsScalarAndDefault(): void
    {
        $rows = array(
            array('id' => 5, 'score' => 99),
            array('id' => 6, 'score' => 75),
        );

        $mapper = new FakeDataMapper($rows);
        $builder = new DMZ_QueryBuilder($mapper);

        $this->assertSame(99, $builder->value('score'));

        $mapper->setRows(array());
        $builderEmpty = new DMZ_QueryBuilder($mapper);
        $this->assertSame('fallback', $builderEmpty->value('score', 'fallback'));
    }

    public function testValueRestoresLimitAfterCall(): void
    {
        $rows = array(
            array('id' => 7),
            array('id' => 8),
            array('id' => 9),
        );

        $mapper = new FakeDataMapper($rows);
        $builder = new DMZ_QueryBuilder($mapper);

        $builder->limit(2);
        $builder->value('id');
        $collection = $builder->collect();

        $this->assertCount(2, $collection);
        $this->assertSame(array(7, 8), $collection->pluck('id'));
    }

    public function testFirstRestoresLimitAfterCall(): void
    {
        $rows = array(
            array('id' => 12),
            array('id' => 13),
        );

        $mapper = new FakeDataMapper($rows);
        $builder = new DMZ_QueryBuilder($mapper);

        $builder->limit(2);
        $first = $builder->first();

        $this->assertSame(12, $first->id);

        $collection = $builder->collect();
        $this->assertCount(2, $collection);
    }

    public function testWithRegistersRelationsAndConstraints(): void
    {
        $rows = array(
            array('id' => 100),
        );

        $mapper = new FakeDataMapper($rows);
        $builder = new InspectableQueryBuilder($mapper);

        $builder
            ->with('profile')
            ->with(array(
                'posts.comments',
                'roles' => function ($query) {
                    $query->where('active', 1);
                },
            ))
            ->get();

        $this->assertSame(array('profile', 'posts.comments', 'roles'), $builder->getEagerLoads());
        $this->assertArrayHasKey('roles', $builder->getEagerConstraints());
        $this->assertGreaterThanOrEqual(1, $builder->loadCallCount);
    }
}
