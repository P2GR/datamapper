<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once APPPATH . 'datamapper/cache/memcachedcache.php';

class MemcachedCacheTest extends TestCase
{
    public function test_batch_and_counter_operations_use_generation_keys(): void
    {
        $client = new MemcachedCacheClientStub();
        $cache = new MemcachedCacheHarness($client);

        $this->assertTrue($cache->set('query:user:one', 'first'));
        $this->assertSame(
            array('query:user:one' => 'first'),
            $cache->get_multiple(array('query:user:one'))
        );

        $this->assertTrue($cache->set_multiple(array('query:user:two' => 'second')));
        $this->assertSame('second', $cache->get('query:user:two'));

        $this->assertTrue($cache->set('counter', 1));
        $this->assertSame(2, $cache->increment('counter'));
        $this->assertSame(1, $cache->decrement('counter'));

        $this->assertSame(1, $cache->delete_pattern('query:user:*'));
        $this->assertNull($cache->get('query:user:one'));
    }

    public function test_flush_invalidates_datamapper_keys_without_flushing_server(): void
    {
        $client = new MemcachedCacheClientStub();
        $cache = new MemcachedCacheHarness($client);

        $this->assertTrue($cache->set('query:user:one', 'first'));
        $this->assertTrue($cache->flush());
        $this->assertFalse($client->flush_called);
        $this->assertNull($cache->get('query:user:one'));
    }
}

class MemcachedCacheHarness extends DMZ_MemcachedCache
{
    public function __construct($client)
    {
        $this->memcached = $client;
        $this->memcached_class = 'MemcachedCacheClientStub';
        $this->prefix = 'dmz:';
    }
}

class MemcachedCacheClientStub
{
    public const RES_SUCCESS = 0;
    public const RES_NOTFOUND = 16;

    public $data = array();
    public $result_code = self::RES_SUCCESS;
    public $flush_called = FALSE;

    public function get($key)
    {
        if (!array_key_exists($key, $this->data)) {
            $this->result_code = self::RES_NOTFOUND;
            return FALSE;
        }

        $this->result_code = self::RES_SUCCESS;
        return $this->data[$key];
    }

    public function getResultCode()
    {
        return $this->result_code;
    }

    public function add($key, $value, $expiry = 0)
    {
        if (array_key_exists($key, $this->data)) {
            return FALSE;
        }

        $this->data[$key] = $value;
        return TRUE;
    }

    public function set($key, $value, $expiry = 0)
    {
        $this->data[$key] = $value;
        return TRUE;
    }

    public function setMulti($items, $expiry = 0)
    {
        foreach ($items as $key => $value) {
            $this->data[$key] = $value;
        }

        return TRUE;
    }

    public function getMulti($keys)
    {
        $values = array();
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->data)) {
                $values[$key] = $this->data[$key];
            }
        }

        return $values;
    }

    public function increment($key, $offset = 1, $initial_value = 0)
    {
        if (!array_key_exists($key, $this->data)) {
            $this->data[$key] = $initial_value;
        } else {
            $this->data[$key] += $offset;
        }

        return $this->data[$key];
    }

    public function decrement($key, $offset = 1, $initial_value = 0)
    {
        if (!array_key_exists($key, $this->data)) {
            $this->data[$key] = $initial_value;
        } else {
            $this->data[$key] -= $offset;
        }

        return $this->data[$key];
    }

    public function delete($key)
    {
        unset($this->data[$key]);
        return TRUE;
    }

    public function flush()
    {
        $this->flush_called = TRUE;
        $this->data = array();
        return TRUE;
    }
}
