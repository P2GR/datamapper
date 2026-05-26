<?php

namespace Tests;

use DataMapper;
use DateTime;
use PHPUnit\Framework\TestCase;

class DataMapperCastingTest extends TestCase
{
    public function testAssignedCastedValuesRemainApplicationFriendlyUntilSave(): void
    {
        $model = new CastingCompatibilityModelStub();

        $model->settings = array('theme' => 'dark', 'mail' => true);
        $model->is_active = '1';
        $model->score = '10.5';
        $model->published_at = '2026-05-26 10:30:00';

        $this->assertSame(array('theme' => 'dark', 'mail' => true), $model->settings);
        $this->assertTrue($model->is_active);
        $this->assertSame(10.5, $model->score);
        $this->assertInstanceOf(DateTime::class, $model->published_at);

        $payload = $model->databasePayload();

        $this->assertSame(array('theme' => 'dark', 'mail' => true), json_decode($payload['settings'], true));
        $this->assertTrue($payload['is_active']);
        $this->assertSame(10.5, $payload['score']);
        $this->assertSame('2026-05-26 10:30:00', $payload['published_at']);
    }

    public function testHydrationAppliesCastsAndNumericFieldTracking(): void
    {
        $source = new CastingCompatibilityModelStub();
        $model = $source->hydrate(array(
            'id' => '12',
            'settings' => '{"theme":"light"}',
            'is_active' => '1',
            'score' => '42.75',
            'published_at' => '2026-05-26 09:00:00',
            'email' => 'ada@example.com',
        ));

        $this->assertSame(12, $model->id);
        $this->assertSame(array('theme' => 'light'), $model->settings);
        $this->assertTrue($model->is_active);
        $this->assertSame(42.75, $model->score);
        $this->assertInstanceOf(DateTime::class, $model->published_at);
    }

    public function testAccessorsAndMutatorsRemainAvailable(): void
    {
        $model = new CastingCompatibilityModelStub();

        $model->first_name = 'Ada';
        $model->last_name = 'Lovelace';
        $model->email = 'ADA@EXAMPLE.COM';

        $this->assertSame('Ada Lovelace', $model->full_name);
        $this->assertSame('ada@example.com', $model->email);
    }
}

class CastingCompatibilityModelStub extends DataMapper
{
    public $model = 'casting_compatibility_model_stub';
    public $table = 'casting_compatibility_model_stubs';
    public $fields = array('id', 'settings', 'is_active', 'score', 'published_at', 'email');
    public $has_many = array();
    public $has_one = array();

    protected $casts = array(
        'settings' => 'array',
        'is_active' => 'bool',
        'score' => 'float',
        'published_at' => 'datetime',
    );

    public function __construct()
    {
        $this->all = array();
        $this->stored = new \stdClass();
        $this->db = (object) array('queries' => array(), 'query_times' => array());
        $this->_field_tracking = array(
            'get_rules' => array(),
            'matches' => array(),
            'intval' => array('id'),
            'floatval' => array('score'),
        );
    }

    public function databasePayload(): array
    {
        return $this->_to_array();
    }

    public function hydrate(array $row): self
    {
        $model = new self();
        $this->_to_object($model, (object) $row);
        return $model;
    }

    public function getFullNameAttribute(): string
    {
        $first = isset($this->first_name) ? $this->first_name : '';
        $last = isset($this->last_name) ? $this->last_name : '';
        return trim($first . ' ' . $last);
    }

    public function setEmailAttribute($value): void
    {
        $this->email = strtolower($value);
    }
}