<?php

namespace Tests;

use DMZ_Collection;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeDataMapper;

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
}
