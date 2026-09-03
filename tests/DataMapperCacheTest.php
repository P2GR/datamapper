<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Support\CacheableModelStub;
use Tests\Support\CacheHarness;
use Tests\Support\FakeQueryState;
use DMZ_Collection;

require_once APPPATH . 'datamapper/querybuilder.php';

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

    public function testFileCacheFlushClearsEntriesAndReportsSuccess(): void
    {
        $state = new FakeQueryState(array('qb_where' => array(array('field' => 'id', 'value' => 1))));
        $harness = $this->createHarness($state);
        $driver = $harness->getCacheDriver();

        $this->assertTrue($driver->set('flush-regression', array('value' => TRUE)));
        $this->assertTrue($driver->has('flush-regression'));
        $this->assertTrue($driver->flush());
        $this->assertFalse($driver->has('flush-regression'));
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

    public function testHydrateCachedResultsIndexesByConfiguredPrimaryKey(): void
    {
        $state = new FakeQueryState(array('qb_select' => array('account_uuid', 'name')));
        $harness = new CustomKeyCacheHarness();
        $harness->setDbState($state);
        $models = array();
        foreach (array('account-a', 'account-b') as $key) {
            $model = new CustomKeyCacheHarness();
            $model->account_uuid = $key;
            $model->name = $key;
            $models[] = $model;
        }
        $harness->storeInCache($models);

        $freshHarness = new CustomKeyCacheHarness();
        $freshHarness->setDbState($state);
        $freshHarness->hydrateCachedResults($harness->fetchFromCache());

        $this->assertSame(array('account-a', 'account-b'), array_keys($freshHarness->all));
    }

    public function testQueryExceptionResetsRelationCacheWithoutStoringPartialResults(): void
    {
        $model = new ThrowingRelationCacheHarness();
        $model->cache_relations(60);
        $builder = new RelationCacheQueryBuilderHarness($model);

        try {
            $builder->get();
            $this->fail('Expected the query exception to be propagated.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('query failed', $exception->getMessage());
        }

        $this->assertFalse($builder->ownedModel()->relationCacheEnabled());
        $this->assertFalse($builder->ownedModel()->cacheEnabled());
    }

    public function testCacheStoreExceptionStillResetsRelationCacheState(): void
    {
        $model = new ThrowingCacheStoreHarness();
        $model->cache_relations(60);
        $model->_prepare_relation_cache(array('children'));

        try {
            $model->_finalize_relation_cache();
            $this->fail('Expected the cache store exception to be propagated.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('cache store failed', $exception->getMessage());
        }

        $this->assertFalse($model->relationCacheEnabled());
        $this->assertFalse($model->cacheEnabled());
    }

    public function testDirectRelationCacheGetResetsCacheState(): void
    {
        $model = new DirectRelationCacheHarness();
        $model->setDbState(new DirectCacheQueryState());

        $model->cache_relations(60)->get();

        $this->assertTrue($model->_query_was_successful());
        $this->assertFalse($model->relationCacheEnabled());
        $this->assertFalse($model->cacheEnabled());
    }

    public function testFailedDirectRelationCacheGetResetsCacheState(): void
    {
        $model = new DirectRelationCacheHarness();
        $model->setDbState(new FailedDirectCacheQueryState());

        $model->cache_relations(60)->get();

        $this->assertFalse($model->_query_was_successful());
        $this->assertFalse($model->relationCacheEnabled());
        $this->assertFalse($model->cacheEnabled());
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

    public function testRelationCacheStoresAndRestoresHydratedGraph(): void
    {
        $state = new FakeQueryState(array('qb_where' => array(array('field' => 'id', 'value' => 15))));
        $harness = new CacheRelationHarness();
        $harness->setDbState($state);
        $parent = new CacheRelationHarness();
        $parent->id = 15;
        $parent->name = 'Parent';
        $child = new CacheRelationChildStub();
        $child->id = 27;
        $child->name = 'Child';
        $parent->children = new DMZ_Collection(array($child));
        $parent->profile = NULL;

        $harness->storeRelationGraph(array($parent), array('children'));

        $freshHarness = new CacheRelationHarness();
        $freshHarness->setDbState($state);
        $freshHarness->prepareRelationFetch(array('children'));
        $payload = $freshHarness->fetchFromCache();
        $freshHarness->hydrateCachedResults($payload);

        $this->assertInstanceOf(DMZ_Collection::class, $freshHarness->all[0]->children);
        $this->assertSame(27, $freshHarness->all[0]->children->first()->id);
        $this->assertTrue(property_exists($freshHarness->all[0], 'profile'));
        $this->assertNull($freshHarness->all[0]->profile);
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

class CacheRelationHarness extends CacheHarness
{
    public function __construct()
    {
        parent::__construct();
        $this->has_many = array('children' => array('class' => CacheRelationChildStub::class));
        $this->has_one = array('profile' => array('class' => CacheRelationChildStub::class));
    }
}

class CacheRelationChildStub extends CacheableModelStub
{
}

class CustomKeyCacheHarness extends CacheHarness
{
    public function __construct()
    {
        parent::__construct();
        $this->primary_key = 'account_uuid';
        $this->fields = array('account_uuid', 'name');
        $this->all_array_uses_ids = TRUE;
    }
}

class ThrowingRelationCacheHarness extends CacheHarness
{
    public function get($limit = NULL, $offset = NULL)
    {
        throw new \RuntimeException('query failed');
    }

    public function relationCacheEnabled()
    {
        return $this->_cache_relations_enabled;
    }

    public function cacheEnabled()
    {
        return $this->_cache_enabled;
    }
}

class RelationCacheQueryBuilderHarness extends \DMZ_QueryBuilder
{
    public function ownedModel()
    {
        return $this->model;
    }
}

class ThrowingCacheStoreHarness extends ThrowingRelationCacheHarness
{
    public function get($limit = NULL, $offset = NULL)
    {
        return $this;
    }

    protected function _store_in_cache($results)
    {
        throw new \RuntimeException('cache store failed');
    }
}

class DirectRelationCacheHarness extends ThrowingRelationCacheHarness
{
    public function get($limit = NULL, $offset = NULL)
    {
        return \DataMapper::get($limit, $offset);
    }
}

class DirectCacheQueryState extends FakeQueryState
{
    public function get($table = NULL, $limit = NULL, $offset = NULL)
    {
        return new EmptyCacheQueryResult();
    }
}

class EmptyCacheQueryResult
{
    public function num_rows()
    {
        return 0;
    }
}

class FailedDirectCacheQueryState extends FakeQueryState
{
    public function get($table = NULL, $limit = NULL, $offset = NULL)
    {
        return FALSE;
    }
}
