<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once APPPATH . 'datamapper/cache/rediscache.php';

class RedisCacheTest extends TestCase
{
    public function test_delete_pattern_continues_after_empty_scan_page(): void
    {
        $redis = new RedisScanStub();
        $cache = new RedisCacheHarness($redis);

        $this->assertSame(2, $cache->delete_pattern('relation-query:*'));
        $this->assertSame(array('dmz:relation-query:one', 'dmz:relation-query:two'), $redis->deleted_keys);
        $this->assertSame(2, $redis->scan_calls);
    }

    public function test_get_stats_continues_after_empty_scan_page(): void
    {
        $redis = new RedisStatsScanStub();
        $cache = new RedisCacheHarness($redis);

        $stats = $cache->get_stats();

        $this->assertSame(1, $stats['entries']);
        $this->assertSame(2, $redis->scan_calls);
    }
}

class RedisCacheHarness extends DMZ_RedisCache
{
    public function __construct($redis)
    {
        $this->redis = $redis;
    }
}

class RedisScanStub
{
    public $deleted_keys = array();
    public $scan_calls = 0;

    public function scan(&$iterator, $pattern, $count)
    {
        $this->scan_calls++;
        if ($this->scan_calls === 1) {
            $iterator = 7;
            return array();
        }

        $iterator = 0;
        return array('dmz:relation-query:one', 'dmz:relation-query:two');
    }

    public function del($key)
    {
        $this->deleted_keys[] = $key;
        return 1;
    }

    public function close()
    {
        return TRUE;
    }
}

class RedisStatsScanStub
{
    public $scan_calls = 0;

    public function info()
    {
        return array();
    }

    public function scan(&$iterator, $pattern, $count)
    {
        $this->scan_calls++;
        if ($this->scan_calls === 1) {
            $iterator = 7;
            return array();
        }

        $iterator = 0;
        return array('dmz:query:user:one');
    }

    public function close()
    {
        return TRUE;
    }
}
