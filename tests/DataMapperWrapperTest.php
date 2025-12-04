<?php

namespace Tests;

use DMZ_Collection;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeDataMapper;

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
}
