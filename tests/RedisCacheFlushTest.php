<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once APPPATH . 'datamapper/cache/rediscache.php';

class RedisCacheFlushTest extends TestCase
{
    public function test_flush_passes_unprefixed_pattern_to_delete_pattern(): void
    {
        $cache = new RedisFlushCacheHarness();

        $this->assertTrue($cache->flush());
        $this->assertSame(array('*'), $cache->patterns);
    }

    public function test_flush_reports_pattern_delete_failure(): void
    {
        $cache = new RedisFlushFailureCacheHarness();

        $this->assertFalse($cache->flush());
    }
}

class RedisFlushCacheHarness extends DMZ_RedisCache
{
    public $patterns = array();

    public function __construct()
    {
    }

    public function delete_pattern($pattern)
    {
        $this->patterns[] = $pattern;
        return 0;
    }
}

class RedisFlushFailureCacheHarness extends DMZ_RedisCache
{
    public function __construct()
    {
    }

    public function delete_pattern($pattern)
    {
        $this->last_pattern_delete_failed = TRUE;
        return FALSE;
    }
}
