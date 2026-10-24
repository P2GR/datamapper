<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once APPPATH . 'datamapper/cache/rediscache.php';

class RedisCacheTest extends TestCase
{
    protected function setUp(): void
    {
        RedisCacheClientStub::$outcomes = array();
        RedisCacheGadget::$woken = FALSE;
    }

    /** @dataProvider constructorFailures */
    public function test_constructor_rejects_false_and_sanitizes_client_exceptions($command, $throws, $message): void
    {
        $this->requireRedisExtension();
        RedisCacheClientStub::$outcomes[$command] = $throws ? new RedisException('synthetic-password') : FALSE;
        try {
            new RedisConstructorHarness(array('password' => 'synthetic-password', 'database' => 0));
            $this->fail('Expected constructor failure.');
        } catch (DataMapper_Exception $exception) {
            $this->assertSame($message, $exception->getMessage());
            $this->assertNull($exception->getPrevious(), 'Client exception secrets must not survive in the chain.');
        }
    }

    public function constructorFailures(): array
    {
        return array(
            array('auth', FALSE, 'Redis authentication failed'),
            array('auth', TRUE, 'Redis authentication failed'),
            array('select', FALSE, 'Redis database selection failed'),
            array('select', TRUE, 'Redis database selection failed'),
        );
    }

    public function test_constructor_authenticates_zero_password_and_selects_database_zero(): void
    {
        $this->requireRedisExtension();
        $cache = new RedisConstructorHarness(array('host' => 'synthetic-host', 'port' => 6385, 'timeout' => 1.5, 'password' => '0', 'database' => 0));
        $this->assertSame(array(
            array('connect', array('synthetic-host', 6385, 1.5)), array('auth', array('0')), array('select', array(0)),
        ), RedisCacheClientStub::$last->calls);
    }

    public function test_missing_extension_throws_a_recoverable_exception(): void
    {
        if (extension_loaded('redis')) {
            $this->assertTrue(class_exists('Redis'));
            return;
        }
        $this->expectException(DataMapper_Exception::class);
        $this->expectExceptionMessage('Redis extension not loaded');
        new DMZ_RedisCache(array('host' => 'must-never-connect'));
    }

    /** @dataProvider invalidAllowlists */
    public function test_constructor_rejects_invalid_class_allowlists($allowedClasses): void
    {
        $this->requireRedisExtension();
        $this->expectException(DataMapper_Exception::class);
        $this->expectExceptionMessage('Redis allowed_classes must be FALSE or an array of class names');
        new RedisConstructorHarness(array('allowed_classes' => $allowedClasses));
    }

    public function invalidAllowlists(): array
    {
        return array(array(TRUE), array(NULL), array('stdClass'), array(7), array(array(7)), array(array('')), array(array(new stdClass())));
    }

    public function test_configured_class_allowlist_replaces_defaults(): void
    {
        $this->requireRedisExtension();
        $cache = new RedisConstructorHarness(array('allowed_classes' => array(RedisCacheDto::class)));
        $client = RedisCacheClientStub::$last;
        $client->values['dmz:objects'] = serialize(array(new RedisCacheDto(), new stdClass()));
        foreach (array($cache->get('objects'), $cache->get_multiple(array('objects'))['objects']) as $decoded) {
            $this->assertInstanceOf(RedisCacheDto::class, $decoded[0]);
            $this->assertInstanceOf(__PHP_Incomplete_Class::class, $decoded[1]);
        }
    }

    public function test_false_class_allowlist_is_accepted_by_constructor(): void
    {
        $this->requireRedisExtension();
        $cache = new RedisConstructorHarness(array('allowed_classes' => FALSE));
        RedisCacheClientStub::$last->values['dmz:object'] = serialize(new stdClass());
        $this->assertInstanceOf(__PHP_Incomplete_Class::class, $cache->get('object'));
    }

    public function test_get_and_get_multiple_preserve_native_classes_and_block_gadget_wakeup(): void
    {
        $client = new RedisCacheClientStub();
        $cache = new RedisCacheHarness($client);
        $payload = array('scalar' => 4, 'array' => array(TRUE, NULL), 'row' => (object) array('id' => 7),
            'date' => new DateTime('2026-10-22T12:00:00+02:00'), 'immutable' => new DateTimeImmutable('2026-10-22T12:00:00+02:00'), 'gadget' => new RedisCacheGadget());
        $client->values['dmz:value'] = serialize($payload);
        foreach (array($cache->get('value'), $cache->get_multiple(array('value', 'missing'))['value']) as $decoded) {
            foreach (array('scalar', 'array', 'row', 'date', 'immutable') as $key) {
                $this->assertEquals($payload[$key], $decoded[$key]);
            }
            $this->assertInstanceOf(__PHP_Incomplete_Class::class, $decoded['gadget']);
        }
        $this->assertFalse(RedisCacheGadget::$woken);
        $this->assertNull($cache->get('missing'));
        $this->assertSame(array(), $cache->get_multiple(array('missing')));
    }

    public function test_false_allowlist_keeps_arrays_and_scalars_but_blocks_objects_in_both_reads(): void
    {
        $client = new RedisCacheClientStub();
        $cache = new RedisCacheHarness($client, array('allowed_classes' => FALSE));
        $client->values['dmz:value'] = serialize(array('data' => array('id' => 1), 'object' => new stdClass()));
        foreach (array($cache->get('value'), $cache->get_multiple(array('value'))['value']) as $decoded) {
            $this->assertSame(array('id' => 1), $decoded['data']);
            $this->assertInstanceOf(__PHP_Incomplete_Class::class, $decoded['object']);
        }
    }

    public function test_single_and_multiple_writes_use_prefixed_setex_with_positive_ttl(): void
    {
        $client = new RedisCacheClientStub();
        $cache = new RedisCacheHarness($client, array('prefix' => 'custom:'));
        $this->assertTrue($cache->set('default', 1));
        $this->assertTrue($cache->set('single', array('id' => 2), 61));
        $this->assertTrue($cache->set_multiple(array('one' => 3, 'two' => 4), 62));
        $writes = array_values(array_filter($client->calls, function ($call) { return $call[0] === 'setex'; }));
        $this->assertSame(array(
            array('setex', array('custom:default', 3600)), array('setex', array('custom:single', 61)),
            array('setex', array('custom:one', 62)), array('setex', array('custom:two', 62)),
        ), $writes);
        $this->assertSame(array('id' => 2), $cache->get('single'));
        $this->assertSame(array('one' => 3, 'two' => 4), $cache->get_multiple(array('one', 'two')));
    }

    public function test_counters_atomically_set_sliding_one_hour_ttl(): void
    {
        $client = new RedisCacheClientStub();
        $cache = new RedisCacheHarness($client, array('prefix' => 'custom:'));
        $this->assertSame(3, $cache->increment('counter', 3));
        $this->assertSame(2, $cache->decrement('counter'));
        $this->assertSame(array(
            array('multi', array(NULL)), array('incrBy', array('custom:counter', 3)), array('expire', array('custom:counter', 3600)), array('exec', array()),
            array('multi', array(NULL)), array('incrBy', array('custom:counter', -1)), array('expire', array('custom:counter', 3600)), array('exec', array()),
        ), $client->calls);
        $client->expireResult = FALSE;
        $this->assertFalse($cache->increment('counter'));
    }

    /** @dataProvider falseCounterCommands */
    public function test_counter_returns_false_for_denied_commands_and_discards_queued_failures($command): void
    {
        $client = new RedisCacheClientStub();
        RedisCacheClientStub::$outcomes[$command] = FALSE;
        $cache = new RedisCacheHarness($client);
        $this->assertFalse($cache->increment('counter'));
        $this->assertSame(in_array($command, array('incrBy', 'expire'), TRUE), $client->discarded);
        $this->assertFalse($client->inTransaction);
        if ($command === 'multi') {
            $this->assertSame(array(array('multi', array(NULL))), $client->calls);
        }
    }

    public function falseCounterCommands(): array
    {
        return array(array('multi'), array('incrBy'), array('expire'), array('exec'));
    }

    /** @dataProvider throwingCounterCommands */
    public function test_counter_discards_on_throw_and_keeps_original_when_discard_also_throws($command, $discardThrows, $exceptionClass = RuntimeException::class): void
    {
        $client = new RedisCacheClientStub();
        $original = new $exceptionClass('original-command-failure');
        RedisCacheClientStub::$outcomes[$command] = $original;
        if ($discardThrows) { RedisCacheClientStub::$outcomes['discard'] = new RuntimeException('cleanup-failure'); }
        $cache = new RedisCacheHarness($client);
        try {
            $cache->increment('counter');
            $this->fail('Expected command exception.');
        } catch (Throwable $exception) {
            $this->assertSame($original, $exception);
            $this->assertTrue($client->discarded);
        }
        if (!$discardThrows) {
            $this->assertFalse($client->inTransaction);
            $this->assertArrayNotHasKey('dmz:counter', $client->values);
        }
    }

    public function throwingCounterCommands(): array
    {
        return array(array('incrBy', FALSE), array('expire', FALSE), array('exec', FALSE), array('incrBy', TRUE), array('expire', TRUE), array('exec', TRUE),
            array('incrBy', FALSE, Error::class), array('exec', TRUE, Error::class));
    }

    public function test_delete_pattern_continues_after_empty_scan_page(): void
    {
        $client = new RedisCacheClientStub();
        $client->values = array('dmz:relation-query:one' => 'one', 'dmz:relation-query:two' => 'two');
        $client->batches = array(array(7, array(), NULL), array(0, array_keys($client->values), NULL));
        $cache = new RedisCacheHarness($client);
        $this->assertSame(2, $cache->delete_pattern('relation-query:*'));
        $this->assertSame(array('dmz:relation-query:one', 'dmz:relation-query:two'), $client->deletedKeys);
        $this->assertSame(2, $client->scanCalls);
    }

    /** @dataProvider deletionMethods */
    public function test_pattern_deletion_and_flush_handle_sparse_scan_and_preserve_other_prefixes($method): void
    {
        $client = new RedisCacheClientStub();
        $client->values = array('custom:one' => 'one', 'custom:two' => 'two', 'session:keep' => 'keep', 'dmz:keep' => 'keep');
        $client->batches = array(array(7, FALSE, NULL), array(9, array('custom:one', 'session:keep'), NULL),
            array(11, array('custom:one', 'custom:two', 'custom:missing', 'dmz:keep'), NULL), array('0', FALSE, NULL));
        $cache = new RedisCacheHarness($client, array('prefix' => 'custom:'));
        $this->assertSame($method === 'flush' ? TRUE : 2, $method === 'flush' ? $cache->flush() : $cache->delete_pattern('*'));
        $this->assertSame(array('session:keep' => 'keep', 'dmz:keep' => 'keep'), $client->values);
        $this->assertSame(4, $client->scanCalls);
        foreach ($client->scanPatterns as $pattern) { $this->assertSame('custom:*', $pattern); }
    }

    public function deletionMethods(): array { return array(array('delete_pattern'), array('flush')); }

    /** @dataProvider deletionFailures */
    public function test_flush_reports_scan_or_delete_failure($command): void
    {
        $client = new RedisCacheClientStub();
        $client->values = array('dmz:one' => 'one');
        $client->batches = $command === 'scan' ? array(array(0, FALSE, 'NOPERM SCAN')) : array(array(0, array('dmz:one'), NULL));
        RedisCacheClientStub::$outcomes['del'] = $command === 'del' ? FALSE : TRUE;
        $cache = new RedisCacheHarness($client);
        $this->assertFalse($cache->flush());
        $this->assertArrayHasKey('dmz:one', $client->values);
    }

    public function deletionFailures(): array { return array(array('scan'), array('del')); }

    public function test_get_stats_continues_after_empty_scan_page(): void
    {
        $client = new RedisCacheClientStub();
        $client->batches = array(array(7, array(), NULL), array(9, FALSE, NULL), array(12, array('dmz:query:user:one'), NULL), array(0, FALSE, NULL));
        $cache = new RedisCacheHarness($client);
        $stats = $cache->get_stats();
        $this->assertSame(1, $stats['entries']);
        $this->assertSame(4, $client->scanCalls);
        $this->assertSame(array_fill(0, 4, 'dmz:*'), $client->scanPatterns);
    }

    /** @dataProvider deniedInfo */
    public function test_get_stats_returns_basic_counters_when_info_is_denied($throws): void
    {
        $client = new RedisCacheClientStub();
        RedisCacheClientStub::$outcomes['info'] = $throws ? new RuntimeException('NOPERM INFO') : FALSE;
        $cache = new RedisCacheHarness($client);
        $this->assertNull($cache->get('missing'));
        $this->assertSame(array('hits' => 0, 'misses' => 1, 'writes' => 0, 'deletes' => 0, 'driver' => 'redis'), $cache->get_stats());
        $this->assertSame(0, $client->scanCalls);
    }

    public function deniedInfo(): array { return array(array(FALSE), array(TRUE)); }

    private function requireRedisExtension(): void
    {
        if (!extension_loaded('redis')) { $this->markTestSkipped('Redis extension unavailable; constructor uses an in-memory client when installed.'); }
    }
}

class RedisCacheHarness extends DMZ_RedisCache
{
    protected $redis_class = 'RedisCacheClientStub';
    public function __construct($redis, $config = array())
    {
        $this->redis = $redis;
        if (isset($config['prefix'])) { $this->prefix = $config['prefix']; }
        if (array_key_exists('allowed_classes', $config)) { $this->allowed_classes = $config['allowed_classes']; }
    }
}

class RedisConstructorHarness extends DMZ_RedisCache
{
    protected $redis_class = 'RedisCacheClientStub';
}

class RedisCacheGadget
{
    public static $woken = FALSE;
    public function __wakeup() { self::$woken = TRUE; }
}

class RedisCacheDto { public $id = 7; }

class RedisCacheClientStub
{
    const PIPELINE = 2;
    public static $outcomes = array();
    public static $last;
    public $calls = array();
    public $values = array();
    public $batches = array();
    public $deletedKeys = array();
    public $scanCalls = 0;
    public $scanPatterns = array();
    public $error;
    public $inTransaction = FALSE;
    public $discarded = FALSE;
    public $expireResult = TRUE;
    private $results = array();
    private $beforeTransaction = array();

    public function __construct() { self::$last = $this; }
    private function outcome($command, $args = array())
    {
        $this->calls[] = array($command, $args);
        $result = self::$outcomes[$command] ?? TRUE;
        if ($result instanceof Throwable) { throw $result; }
        return $result;
    }
    public function connect(...$args) { return $this->outcome('connect', $args); }
    public function auth(...$args) { return $this->outcome('auth', $args); }
    public function select(...$args) { return $this->outcome('select', $args); }
    public function close() { return TRUE; }
    public function get($key) { return $this->values[$key] ?? FALSE; }
    public function mGet($keys) { return array_map(function ($key) { return $this->get($key); }, $keys); }
    public function setex($key, $ttl, $value)
    {
        $this->outcome('setex', array($key, $ttl));
        if ($ttl <= 0) { throw new RuntimeException('Non-positive TTL'); }
        $this->values[$key] = $value;
        if ($this->inTransaction) { $this->results[] = TRUE; return $this; }
        return TRUE;
    }
    public function multi($mode = NULL)
    {
        if ($this->outcome('multi', array($mode)) === FALSE) { return FALSE; }
        $this->inTransaction = TRUE;
        $this->results = array();
        $this->beforeTransaction = $this->values;
        return $this;
    }
    public function incrBy($key, $offset)
    {
        if ($this->outcome('incrBy', array($key, $offset)) === FALSE) { return FALSE; }
        $this->values[$key] = ($this->values[$key] ?? 0) + $offset;
        $this->results[] = $this->values[$key];
        return $this;
    }
    public function expire($key, $ttl)
    {
        if ($this->outcome('expire', array($key, $ttl)) === FALSE) { return FALSE; }
        $this->results[] = $this->expireResult;
        return $this;
    }
    public function exec()
    {
        $result = $this->outcome('exec');
        $this->inTransaction = FALSE;
        return $result === FALSE ? FALSE : $this->results;
    }
    public function discard()
    {
        $this->discarded = TRUE;
        $this->outcome('discard');
        $this->inTransaction = FALSE;
        $this->values = $this->beforeTransaction;
        return TRUE;
    }
    public function del($key)
    {
        if ($this->outcome('del', array($key)) === FALSE) { return FALSE; }
        $this->deletedKeys[] = $key;
        $exists = array_key_exists($key, $this->values);
        unset($this->values[$key]);
        return (int) $exists;
    }
    public function clearLastError() { $this->error = NULL; }
    public function getLastError() { return $this->error; }
    public function scan(&$iterator, $pattern, $count)
    {
        $this->scanCalls++;
        $this->scanPatterns[] = $pattern;
        if (!$this->batches) { throw new RuntimeException('SCAN did not terminate'); }
        list($iterator, $keys, $this->error) = array_shift($this->batches);
        return $keys === FALSE ? FALSE : array_values(array_filter($keys, function ($key) use ($pattern) { return fnmatch($pattern, $key); }));
    }
    public function info() { return $this->outcome('info') === FALSE ? FALSE : array(); }
}
