<?php

namespace Tests;

use DMZ_Collection;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeDataMapper;
use Tests\Support\FakeQueryState;

class DataMapperIterationTest extends TestCase
{
    public function testChunkProcessesAllRecords(): void
    {
        $rows = array(
            array('id' => 1, 'email' => 'alpha@example.com'),
            array('id' => 2, 'email' => 'bravo@example.com'),
            array('id' => 3, 'email' => 'charlie@example.com'),
            array('id' => 4, 'email' => 'delta@example.com'),
            array('id' => 5, 'email' => 'echo@example.com'),
        );

        $mapper = new FakeDataMapper($rows);

        $chunks = array();
        $result = $mapper->chunk(2, function (DMZ_Collection $collection) use (&$chunks) {
            $chunks[] = $collection->pluck('id');
            return true;
        });

        $this->assertTrue($result);
        $this->assertSame(array(
            array(1, 2),
            array(3, 4),
            array(5),
        ), $chunks);
    }

    public function testChunkStopsWhenCallbackReturnsFalse(): void
    {
        $rows = array(
            array('id' => 1),
            array('id' => 2),
            array('id' => 3),
        );

        $mapper = new FakeDataMapper($rows);

        $processed = 0;
        $result = $mapper->chunk(2, function (DMZ_Collection $collection) use (&$processed) {
            $processed += $collection->count();
            return false;
        });

        $this->assertFalse($result);
        $this->assertSame(2, $processed);
    }

    public function testLazyCollectionAppliesOperationsInOrder(): void
    {
        $rows = array(
            array('id' => 1, 'email' => 'alpha@example.com'),
            array('id' => 2, 'email' => 'beta@example.com'),
            array('id' => 3, 'email' => 'gamma@example.com'),
            array('id' => 4, 'email' => 'delta@example.com'),
        );

        $mapper = new FakeDataMapper($rows);

        $lazy = $mapper
            ->lazy(2)
            ->filter(function ($user) {
                return strpos($user->email, 'example') !== false;
            })
            ->map(function ($user) {
                return strtoupper($user->email);
            })
            ->take(3);

        $results = $lazy->to_array();

        $this->assertSame(array(
            'ALPHA@EXAMPLE.COM',
            'BETA@EXAMPLE.COM',
            'GAMMA@EXAMPLE.COM',
        ), $results);
    }

    public function testLazyCollectionUsesIndependentUncachedQueryClones(): void
    {
        LazyCloneTrackingMapper::$cloneForces = array();
        LazyCloneTrackingMapper::$noCacheCalls = 0;
        $mapper = new LazyCloneTrackingMapper(array(
            array('id' => 1),
            array('id' => 2),
            array('id' => 3),
        ));

        $this->assertSame(array(1, 2, 3), $mapper->lazy(2)->pluck('id')->to_array());
        $this->assertSame(array(TRUE, TRUE), LazyCloneTrackingMapper::$cloneForces);
        $this->assertSame(2, LazyCloneTrackingMapper::$noCacheCalls);
    }

    public function testLazyByIdAdvancesWithConfiguredPrimaryKey(): void
    {
        LazyKeysetMapper::$thresholds = array();
        $mapper = new LazyKeysetMapper(array(
            array('user_id' => 10),
            array('user_id' => 20),
            array('user_id' => 30),
            array('user_id' => 40),
            array('user_id' => 50),
        ));
        $mapper->primary_key = 'user_id';

        $this->assertSame(array(10, 20, 30, 40, 50), $mapper->lazy_by_id(2)->pluck('user_id')->to_array());
        $this->assertSame(array(NULL, 20, 40), LazyKeysetMapper::$thresholds);
    }

    public function testLazyByIdRejectsPreExistingOrdering(): void
    {
        $mapper = new LazyKeysetMapper(array(array('id' => 1)));
        $mapper->db = new FakeQueryState(array(
            'qb_orderby' => array(array('field' => 'name', 'direction' => 'asc')),
        ));

        $this->expectException(\InvalidArgumentException::class);
        $mapper->lazy_by_id(10)->to_array();
    }
}

class LazyCloneTrackingMapper extends FakeDataMapper
{
    public static $cloneForces = array();
    public static $noCacheCalls = 0;

    public function get_clone($force_db = FALSE)
    {
        self::$cloneForces[] = $force_db;
        return clone $this;
    }

    public function no_cache()
    {
        self::$noCacheCalls++;
        return $this;
    }
}

class LazyKeysetMapper extends FakeDataMapper
{
    public static $thresholds = array();

    public function get_clone($force_db = FALSE)
    {
        return clone $this;
    }

    public function get($limit = NULL, $offset = NULL)
    {
        $threshold = NULL;
        foreach ($this->queryLog as $operation) {
            if ($operation[0] === 'where' && $operation[1] === $this->primary_key . ' >') {
                $threshold = $operation[2];
            }
        }
        self::$thresholds[] = $threshold;

        $results = array_filter($this->mockRows, function ($row) use ($threshold) {
            return $threshold === NULL || $row->{$this->primary_key} > $threshold;
        });
        $size = $this->lastLimit === NULL ? $limit : $this->lastLimit;
        $this->all = $size === NULL ? array_values($results) : array_slice(array_values($results), 0, $size);
        return $this;
    }
}
