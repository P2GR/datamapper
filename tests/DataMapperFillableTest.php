<?php

namespace Tests;

use DataMapper;
use PHPUnit\Framework\TestCase;
use Tests\Support\FillableFakeModel;

class DataMapperFillableTest extends TestCase
{
    public function tearDown(): void
    {
        DataMapper::reguard();
    }

    public function testFillRespectsFillableWhitelist(): void
    {
        $model = new FillableFakeModel();
        $model->fillable = array('name', 'email');

        $model->fill(array(
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'is_admin' => TRUE,
            'secret' => 's3cr3t',
            'id' => 99,
        ));

        $this->assertSame('Ada', $model->name);
        $this->assertSame('ada@example.com', $model->email);
        $this->assertNull($model->is_admin);
        $this->assertNull($model->secret);
        $this->assertNull($model->id);
    }

    public function testIdFieldRemainsGuardedByDefault(): void
    {
        $model = new FillableFakeModel();

        $model->fill(array(
            'id' => 42,
            'name' => 'Default Guard',
        ));

        $this->assertSame('Default Guard', $model->name);
        $this->assertNull($model->id);
    }

    public function testFillHonorsGuardedWhenWhitelistEmpty(): void
    {
        $model = new FillableFakeModel();
        $model->guarded = array('is_admin', 'id');

        $model->fill(array(
            'name' => 'Grace',
            'is_admin' => TRUE,
            'id' => 5,
        ));

        $this->assertSame('Grace', $model->name);
        $this->assertNull($model->is_admin);
        $this->assertNull($model->id);
    }

    public function testForceFillBypassesGuarding(): void
    {
        $model = new FillableFakeModel();
        $model->guarded = array('*');

        $model->forceFill(array(
            'name' => 'Linus',
            'is_admin' => TRUE,
            'secret' => 'root',
        ));

        $this->assertSame('Linus', $model->name);
        $this->assertTrue($model->is_admin);
        $this->assertSame('root', $model->secret);
    }

    public function testUnguardedCallbackTemporarilyDisablesGuarding(): void
    {
        $model = new FillableFakeModel();
        $model->guarded = array('secret');

        DataMapper::unguarded(function () use ($model) {
            $model->fill(array('secret' => 'token'));
        });

        $this->assertSame('token', $model->secret);

        $model->secret = NULL;
        $model->fill(array('secret' => 'blocked'));

        $this->assertNull($model->secret);
    }

    public function testCreateUsesMassAssignmentAndReturnsModel(): void
    {
        $model = FillableFakeModel::create(array(
            'name' => 'Margaret',
            'email' => 'margaret@example.com',
        ));

        $this->assertInstanceOf(FillableFakeModel::class, $model);
        $this->assertSame('Margaret', $model->name);
        $this->assertSame('margaret@example.com', $model->email);
        $this->assertNotEmpty($model->savedPayload);
        $this->assertSame('Margaret', $model->savedPayload['name']);
    }
}
