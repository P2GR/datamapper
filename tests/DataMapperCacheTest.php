<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Support\CacheableModelStub;
use Tests\Support\CacheHarness;
use Tests\Support\FakeQueryState;

class DataMapperCacheTest extends TestCase
{
    /**
     * @var string
     */
    private $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'datamapper_cache_' . uniqid();
        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0777, true) && !is_dir($this->cacheDir)) {
            $this->fail('Failed to create cache directory for tests.');
        }

        \DataMapper::$config['cache_driver'] = 'file';
        \DataMapper::$config['cache_config'] = array('cache_dir' => $this->cacheDir);

        CacheHarness::resetCacheDriver();
    }

    protected function tearDown(): void
    {
        CacheHarness::resetCacheDriver();
        $this->removeDirectory($this->cacheDir);
        parent::tearDown();
    }

    public function testFetchFromCacheReturnsNullWhenMiss(): void
    {
        $state = new FakeQueryState(array('qb_where' => array(array('field' => 'id', 'value' => 1))));
        $harness = $this->createHarness($state);

        $this->assertNull($harness->fetchFromCache());
    }

    public function testStoreAndFetchRoundTripsCachedPayload(): void
    {
        $state = new FakeQueryState(array(
            'qb_where' => array(array('field' => 'status', 'value' => 'active')),
            'qb_orderby' => array(array('field' => 'id', 'direction' => 'asc')),
        ));

        $harness = $this->createHarness($state);
        $models = $this->makeModels(array(
            array('id' => 1, 'name' => 'Alice'),
            array('id' => 2, 'name' => 'Bob'),
        ));
        $harness->storeInCache($models);

        $key = $harness->getCacheKey();
        $driver = $harness->getCacheDriver();
        $this->assertNotNull($driver, 'Cache driver should be available once configured.');
        $this->assertTrue($driver->has($key), 'Cache entry should exist after storing results.');

        $freshHarness = $this->createHarness($state);
        $payload = $freshHarness->fetchFromCache();

        $this->assertIsArray($payload);
        $this->assertCount(2, $payload);
        $this->assertSame(CacheableModelStub::class, $payload[0]['class']);
        $this->assertSame(array('id' => 1, 'name' => 'Alice'), $payload[0]['data']);
        $this->assertSame(array('id' => 2, 'name' => 'Bob'), $payload[1]['data']);
    }

    public function testHydrateCachedResultsRestoresModels(): void
    {
        $state = new FakeQueryState(array('qb_select' => array('id', 'name')));
        $harness = $this->createHarness($state);

        $models = $this->makeModels(array(
            array('id' => 5, 'name' => 'Eve'),
            array('id' => 6, 'name' => 'Mallory'),
        ));
        $harness->storeInCache($models);
        $payload = $harness->fetchFromCache();
        $this->assertIsArray($payload);

        $freshHarness = $this->createHarness($state);
        $freshHarness->hydrateCachedResults($payload);

        $this->assertSame(5, $freshHarness->id);
        $this->assertSame('Eve', $freshHarness->name);
        $this->assertCount(2, $freshHarness->all);
        $this->assertInstanceOf(CacheHarness::class, $freshHarness->all[0]);
        $this->assertInstanceOf(CacheableModelStub::class, $freshHarness->all[1]);
        $this->assertSame('Mallory', $freshHarness->all[1]->name);
    }

    public function testInvalidateCacheRemovesStoredEntries(): void
    {
        $state = new FakeQueryState(array('qb_where' => array(array('field' => 'role', 'value' => 'admin'))));
        $harness = $this->createHarness($state);

        $models = $this->makeModels(array(array('id' => 9, 'name' => 'Root')));
        $harness->storeInCache($models);
        $key = $harness->getCacheKey();
        $driver = $harness->getCacheDriver();
        $this->assertTrue($driver->has($key));

        $harness->invalidateCache();
        $this->assertFalse($driver->has($key));
    }

    private function createHarness(FakeQueryState $state): CacheHarness
    {
        $harness = new CacheHarness();
        $harness->setDbState($state);
        return $harness;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, CacheableModelStub>
     */
    private function makeModels(array $rows)
    {
        $models = array();
        foreach ($rows as $row) {
            $model = new CacheableModelStub();
            foreach ($row as $field => $value) {
                $model->{$field} = $value;
            }
            $model->all = array($model);
            $models[] = $model;
        }

        return $models;
    }

    private function removeDirectory($directory)
    {
        if (!$directory || !is_dir($directory)) {
            return;
        }

        $items = glob($directory . DIRECTORY_SEPARATOR . '*');
        if ($items !== false) {
            foreach ($items as $item) {
                if (is_dir($item)) {
                    $this->removeDirectory($item);
                } else {
                    @unlink($item);
                }
            }
        }

        @rmdir($directory);
    }
}
