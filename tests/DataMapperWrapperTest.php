<?php

namespace Tests;

use DMZ_Collection;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeDataMapper;
use Tests\Support\FillableFakeModel;

require_once APPPATH . 'datamapper/querybuilder.php';

class DataMapperWrapperTest extends TestCase
{
    public function testCollectWrapperReturnsCollection(): void
    {
        $rows = array(
            array('id' => 1, 'name' => 'One'),
            array('id' => 2, 'name' => 'Two'),
        );

        $mapper = new FakeDataMapper($rows);
        $collection = $mapper->collect();

        $this->assertInstanceOf(DMZ_Collection::class, $collection);
        $this->assertSame(array('One', 'Two'), $collection->pluck('name'));
    }

    public function testCollectWrapperHonorsLimit(): void
    {
        $rows = array(
            array('id' => 1),
            array('id' => 2),
            array('id' => 3),
        );

        $mapper = new FakeDataMapper($rows);
        $collection = $mapper->collect(2);

        $this->assertCount(2, $collection);
        $this->assertSame(array(1, 2), $collection->pluck('id'));
    }

    public function testPluckWrapperReturnsArray(): void
    {
        $rows = array(
            array('id' => 11, 'email' => 'first@example.com'),
            array('id' => 12, 'email' => 'second@example.com'),
        );

        $mapper = new FakeDataMapper($rows);
        $emails = $mapper->pluck('email');

        $this->assertSame(array('first@example.com', 'second@example.com'), $emails);
    }

    public function testValueWrapperDelegatesToBuilder(): void
    {
        $rows = array(
            array('id' => 31, 'status' => 'active'),
            array('id' => 32, 'status' => 'inactive'),
        );

        $mapper = new FakeDataMapper($rows);
        $this->assertSame('active', $mapper->value('status'));

        $mapper->setRows(array());
        $this->assertSame('none', $mapper->value('status', 'none'));
    }

    public function testFindOrFailThrowsWhenNoResultExists(): void
    {
        $mapper = new FakeDataMapper(array());

        $this->expectException(\DataMapper_Exception::class);
        $mapper->find_or_fail(404);
    }

    public function testQueryStyleWrappersUseLegacySnakeCaseMethods(): void
    {
        $mapper = new FakeDataMapper(array());

        $mapper
            ->where_null('deleted_at')
            ->where_not_null('email')
            ->take(5)
            ->skip(10);

        $this->assertSame(array('where', 'deleted_at', null, true), $mapper->queryLog[0]);
        $this->assertSame(array('where', 'email IS NOT NULL', null, false), $mapper->queryLog[1]);
        $this->assertSame(array('limit', 5, ''), $mapper->queryLog[2]);
        $this->assertSame(array('offset', 10), $mapper->queryLog[3]);
    }

    public function testAggregateWrappersReturnScalarValues(): void
    {
        $mapper = new FakeDataMapper(array(
            array('id' => 1, 'score' => 3),
            array('id' => 2, 'score' => 7),
        ));

        $this->assertSame(10, $mapper->sum('score'));
        $this->assertSame(5.0, $mapper->avg('score'));
        $this->assertSame(3, $mapper->min('score'));
        $this->assertSame(7, $mapper->max('score'));
    }

    public function testAllWrapperReturnsCollection(): void
    {
        $mapper = new FakeDataMapper(array(
            array('id' => 1),
            array('id' => 2),
        ));

        $collection = $mapper->all();

        $this->assertInstanceOf(DMZ_Collection::class, $collection);
        $this->assertSame(array(1, 2), $collection->pluck('id'));
    }

    public function testFirstOrNewReturnsUnsavedFilledModelWhenMissing(): void
    {
        $model = new FillableFakeModel();

        $result = $model->first_or_new(
            array('email' => 'ada@example.com'),
            array('name' => 'Ada')
        );

        $this->assertInstanceOf(FillableFakeModel::class, $result);
        $this->assertSame('ada@example.com', $result->email);
        $this->assertSame('Ada', $result->name);
        $this->assertNull($result->id);
    }

    public function testFirstOrCreateSavesNewModelWhenMissing(): void
    {
        $model = new FillableFakeModel();

        $result = $model->first_or_create(
            array('email' => 'grace@example.com'),
            array('name' => 'Grace')
        );

        $this->assertInstanceOf(FillableFakeModel::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertSame('grace@example.com', $result->email);
        $this->assertSame('Grace', $result->name);
    }

    public function testCollectionHelpersProxyAfterGet(): void
    {
        $mapper = new FakeDataMapper(array(
            array('id' => 1, 'score' => 2),
            array('id' => 2, 'score' => 4),
        ));

        $scores = $mapper->get()->map(function ($row) {
            return $row->score * 2;
        });

        $this->assertInstanceOf(DMZ_Collection::class, $scores);
        $this->assertSame(array(4, 8), $scores->all());
    }
}
