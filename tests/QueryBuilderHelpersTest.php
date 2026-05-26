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

    public function testWithAcceptsMultipleStringRelations(): void
    {
        $mapper = new FakeDataMapper(array(array('id' => 101)));
        $builder = new InspectableQueryBuilder($mapper);

        $builder->with('profile', 'posts', 'roles');

        $this->assertSame(array('profile', 'posts', 'roles'), $builder->getEagerLoads());
    }

    public function testWithKeepsStringCallableConstraintSyntax(): void
    {
        $mapper = new FakeDataMapper(array(array('id' => 102)));
        $builder = new InspectableQueryBuilder($mapper);
        $constraint = function ($query) {
            $query->where('active', 1);
        };

        $builder->with('profile', $constraint);

        $this->assertSame(array('profile'), $builder->getEagerLoads());
        $this->assertSame($constraint, $builder->getEagerConstraints()['profile']);
    }

    public function testCacheHelpersProxyToWrappedModel(): void
    {
        $mapper = new FakeDataMapper(array(array('id' => 103)));
        $mapper->clearCacheReturn = 3;
        $builder = new DMZ_QueryBuilder($mapper);

        $result = $builder
            ->with('profile')
            ->cache(900, 'profiled-users')
            ->no_cache()
            ->cache_relations(1200);

        $this->assertSame($builder, $result);
        $this->assertSame(3, $builder->clear_cache('query:fake:*'));
        $this->assertSame(array(
            array('cache', 900, 'profiled-users'),
            array('no_cache'),
            array('cache_relations', 1200),
            array('clear_cache', 'query:fake:*'),
        ), $builder->get_model()->cacheLog);
    }

    public function testFindUsesConfiguredPrimaryKey(): void
    {
        $mapper = new FakeDataMapper(array(array('uuid' => 'abc-123')));
        $mapper->primary_key = 'uuid';
        $builder = new DMZ_QueryBuilder($mapper);

        $result = $builder->find('abc-123');

        $this->assertSame('abc-123', $result->uuid);
        $this->assertSame(array('where', 'uuid', 'abc-123', true), $builder->get_model()->queryLog[0]);
    }

    public function testFindReturnsNullForNullPrimaryKey(): void
    {
        $mapper = new FakeDataMapper(array(array('id' => 1)));
        $builder = new DMZ_QueryBuilder($mapper);

        $this->assertNull($builder->find(NULL));
        $this->assertSame(array(), $builder->get_model()->queryLog);
    }

    public function testAggregateHelpersFallbackToCollectionForTestDoubles(): void
    {
        $mapper = new FakeDataMapper(array(
            array('id' => 1, 'score' => 10),
            array('id' => 2, 'score' => 20),
            array('id' => 3, 'score' => 30),
        ));
        $builder = new DMZ_QueryBuilder($mapper);

        $this->assertSame(60, $builder->sum('score'));
        $this->assertSame(20.0, $builder->avg('score'));
        $this->assertSame(10, $builder->min('score'));
        $this->assertSame(30, $builder->max('score'));
    }

    public function testConditionalHelpersApplyExpectedCallbacks(): void
    {
        $mapper = new FakeDataMapper(array(array('id' => 1)));
        $builder = new DMZ_QueryBuilder($mapper);

        $result = $builder
            ->when('active', function ($query, $status) {
                return $query->where('status', $status);
            })
            ->unless(false, function ($query) {
                return $query->where_not_null('email');
            });

        $this->assertSame($builder, $result);
        $this->assertSame(array('where', 'status', 'active', true), $builder->get_model()->queryLog[0]);
        $this->assertSame(array('where', 'email IS NOT NULL', null, false), $builder->get_model()->queryLog[1]);
    }

    public function testPaginationAliasesPreserveOffsetInEitherOrder(): void
    {
        $first = new DMZ_QueryBuilder(new FakeDataMapper(array(array('id' => 1))));
        $first->skip(10)->take(5)->get();

        $second = new DMZ_QueryBuilder(new FakeDataMapper(array(array('id' => 1))));
        $second->take(5)->skip(10)->get();

        $this->assertSame(5, $first->get_model()->lastLimit);
        $this->assertSame(10, $first->get_model()->lastOffset);
        $this->assertSame(5, $second->get_model()->lastLimit);
        $this->assertSame(10, $second->get_model()->lastOffset);
    }

    public function testOrderingShortcutsUseLegacyOrderByOnModelClone(): void
    {
        $mapper = new FakeDataMapper(array(array('id' => 1, 'created_at' => '2026-05-26')));
        $builder = new DMZ_QueryBuilder($mapper);

        $builder
            ->order_by_desc('name')
            ->latest()
            ->oldest('updated_at');

        $this->assertSame(array('order_by', 'name', 'desc'), $builder->get_model()->queryLog[0]);
        $this->assertSame(array('order_by', 'created_at', 'desc'), $builder->get_model()->queryLog[1]);
        $this->assertSame(array('order_by', 'updated_at', 'asc'), $builder->get_model()->queryLog[2]);
    }

    public function testOrderingShortcutsUseCustomTimestampColumnOnModelClone(): void
    {
        $mapper = new QueryBuilderCustomTimestampFakeDataMapper(array(array('id' => 1, 'created_on' => '2026-05-26')));
        $builder = new DMZ_QueryBuilder($mapper);

        $builder->latest();

        $this->assertSame(array('order_by', 'created_on', 'desc'), $builder->get_model()->queryLog[0]);
    }

    public function testFirstWhereAddsConditionBeforeFetchingFirstResult(): void
    {
        $mapper = new FakeDataMapper(array(
            array('id' => 1, 'status' => 'active'),
            array('id' => 2, 'status' => 'inactive'),
        ));
        $builder = new DMZ_QueryBuilder($mapper);

        $first = $builder->first_where('status', 'active');

        $this->assertSame(1, $first->id);
        $this->assertSame(array('where', 'status', 'active', true), $builder->get_model()->queryLog[0]);
    }
}

class QueryBuilderCustomTimestampFakeDataMapper extends FakeDataMapper
{
    public function get_created_at_column(): string
    {
        return 'created_on';
    }
}
