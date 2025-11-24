<?php

namespace Tests\Support;

use DataMapper;
use ReflectionClass;

class FakeQueryState
{
    /**
     * @var array<string, mixed>
     */
    private $state;

    public function __construct(array $state = array())
    {
        $defaults = array(
            'qb_where' => array(),
            'qb_select' => array(),
            'qb_join' => array(),
            'qb_orderby' => array(),
            'qb_groupby' => array(),
            'qb_limit' => NULL,
            'qb_offset' => NULL,
        );

        $this->state = array_merge($defaults, $state);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function dm_get($key)
    {
        return array_key_exists($key, $this->state) ? $this->state[$key] : NULL;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setState($key, $value)
    {
        $this->state[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getState()
    {
        return $this->state;
    }
}

class CacheableModelStub extends DataMapper
{
    public function __construct()
    {
        $this->model = 'cacheable_model_stub';
        $this->table = 'cacheable_model_stubs';
        $this->fields = array('id', 'name');
        $this->has_many = array();
        $this->has_one = array();
        $this->db = new FakeQueryState();
    }
}

class CacheHarness extends DataMapper
{
    public function __construct()
    {
        $this->model = 'cache_harness';
        $this->table = 'cache_harnesses';
        $this->fields = array('id', 'name');
        $this->has_many = array();
        $this->has_one = array();
        $this->_cache_enabled = true;
        $this->_cache_ttl = 60;
        $this->db = new FakeQueryState();
    }

    public function setDbState(FakeQueryState $state)
    {
        $this->db = $state;
    }

    /**
     * @param array<int, DataMapper> $results
     * @return void
     */
    public function storeInCache(array $results)
    {
        $this->_store_in_cache($results);
    }

    /**
     * @return array<int, array>|null
     */
    public function fetchFromCache()
    {
        return $this->_get_from_cache();
    }

    public function hydrateCachedResults(array $payload)
    {
        $this->_hydrate_cached_results($payload);
    }

    public function invalidateCache()
    {
        $this->_invalidate_cache();
    }

    public function getCacheKey()
    {
        return $this->_generate_cache_key();
    }

    public function getCacheDriver()
    {
        return $this->_get_cache_driver();
    }

    public static function resetCacheDriver()
    {
        $reflection = new ReflectionClass('DataMapper');
        $defaults = array(
            '_cache_driver' => NULL,
            '_cache_driver_signature' => NULL,
            '_cache_driver_failure_until' => 0.0,
            '_cache_driver_last_error' => NULL,
        );

        foreach ($defaults as $property => $value) {
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setAccessible(true);
                $prop->setValue(NULL, $value);
            }
        }
    }
}
