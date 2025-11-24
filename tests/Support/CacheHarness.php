<?php

namespace Tests\Support;

use DataMapper;
use ReflectionClass;

class CacheHarness extends DataMapper
{
    public function __construct()
    {
        $this->model = 'cache_harness';
        $this->table = 'cache_harnesses';
        $this->fields = array('id', 'name');
        $this->has_many = array();
        $this->has_one = array();
        $this->validation = array();
        $this->_field_tracking = array(
            'get_rules' => array(),
            'matches' => array(),
            'intval' => array('id'),
        );
        $this->_instantiations = array();
        $this->stored = new \stdClass();
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
