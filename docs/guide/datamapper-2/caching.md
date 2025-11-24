# Query Caching (DataMapper 2.0)

Dramatically improve performance by caching query results. Reduce database load by **90%+** for repeated queries.

**New in DataMapper 2.0:** Intelligent query caching with automatic invalidation on saves and deletes. Supports File, Redis, and Memcached backends.

Available Methods:

- **cache()** - Enable caching for a query
- **no_cache()** - Disable caching for a query
- **cache_relations()** - Cache relationship data
- **clear_cache()** - Manually clear cache

## Why Use Query Caching?

Many queries return the same data repeatedly:

- User profiles viewed multiple times per second
- Product catalogs rarely change
- Configuration data loaded on every page
- Dashboard statistics recalculated unnecessarily

**Solution:** Cache query results in memory (Redis/Memcached) or files.

## Quick Start

### Step 1: Enable Caching in Config

Edit application/config/datamapper.php:

```php

$config['cache_enabled'] = TRUE;
$config['cache_driver'] = 'file'; // or 'redis', 'memcached'
$config['cache_ttl'] = 3600; // Default: 1 hour
$config['cache_prefix'] = 'dmz_'; // Avoid key collisions

```

### Step 2: Use cache() Method

```php

$u = new User();
$u->where('id', 123)
  ->cache(300) // Cache for 5 minutes
  ->get();

```

That's it! Subsequent identical queries will be served from cache.

### Step 3 (Optional): Pick the ideal return helper

Caching works seamlessly with the new result helpers introduced in 2.0. You can stay on the query builder and decide whether you want a collection, array, or scalar without breaking the cached payload:

```php
$names = (new User())
  ->where('active', 1)
  ->cache(600)
  ->pluck('display_name');

$top = (new Post())
  ->where('status', 'published')
  ->cache(900)
  ->with('author')
  ->collect()
  ->take(10);

$latestSlug = (new Post())
  ->cache(120)
  ->order_by('created_at', 'DESC')
  ->value('slug');
```

Each helper still records cache hits/misses and triggers the automatic invalidation paths covered in the regression suite.

## Configuration

### File Cache (Default)

Stores cache in files. No additional dependencies required.

```php

$config['cache_driver'] = 'file';
$config['cache_path'] = APPPATH . 'cache/datamapper/';

```

**Pros:** No setup required, works everywhere**Cons:** Slower than Redis/Memcached, not shared across servers

### Redis Cache (Recommended)

High-performance in-memory caching with persistence.

```php

$config['cache_driver'] = 'redis';
$config['cache_redis_host'] = '127.0.0.1';
$config['cache_redis_port'] = 6379;
$config['cache_redis_password'] = ''; // If required
$config['cache_redis_database'] = 0;

```

**Pros:** Very fast, persistent, shared across servers**Cons:** Requires Redis server

[Install Redis:**sudo apt-get install redis-server (Linux) or [download](https://redis.io/download) for Windows/Mac

### Memcached Cache

Distributed memory caching system.

```php

$config['cache_driver'] = 'memcached';
$config['cache_memcached_servers'] = array(
    array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 1)
);

```

**Pros:** Fast, distributed, battle-tested**Cons:** No persistence, requires Memcached server

### Driver Health Checks

Every call to `cache()` now verifies that the configured driver can actually be loaded. If a driver cannot be created (for example the Redis extension is missing or the cache service is offline) DataMapper will log a warning, disable caching for that query, and continue hitting the database instead of failing mid-request.

::: tip Log visibility
Look for messages prefixed with `[DataMapper]` in your CodeIgniter logs:

- `DataMapper cache hit.` → payload served from cache (includes model, key, driver, and item count)
- `DataMapper cache miss.` → nothing stored yet; request fell through to the database
- `DataMapper cache store.` → fresh payload written to the configured driver (along with TTL and record count)
- `DataMapper cache disabled: cache driver unavailable.` → driver bootstrap failed, so the query ran uncached
- `Cache driver initialization failed:` → full stack trace + driver; DataMapper will now back off for 30 seconds before re-trying that configuration to avoid log spam
:::

## How cached results are stored

DataMapper 2.0 serializes cached records into lightweight arrays and rehydrates them when you read from the cache. The new pipeline:

- Stores only declared fields and eager-loaded relations, keeping payloads minimal and driver-friendly.
- Preserves relation graphs by caching `has_one` and `has_many` data alongside the primary models.
- Rebuilds `$object->all`, `$object->stored`, and related collections on hydration so callbacks and mutators behave exactly like fresh database results.

Because the payload is platform-neutral you can safely switch between File, Redis, or Memcached backends without cache corruption.

## cache() - Enable Caching

### Basic Usage

```php

$u = new User();
$u->where('id', 123)
  ->cache() // Use default TTL from config
  ->get();

```

### Custom TTL

```php

// Cache for 5 minutes
$u = new User();
$u->where('active', 1)
  ->cache(300)
  ->get();

// Cache for 1 hour
$p = new Product();
$p->cache(3600)->get();

// Cache for 1 day
$c = new Category();
$c->cache(86400)->get();

```

### Cache Tags

Group related cache entries for bulk invalidation:

```php

// Tag cache entries
$u = new User();
$u->where('role', 'admin')
  ->cache(3600, array('users', 'admins'))
  ->get();

$u2 = new User();
$u2->where('active', 1)
   ->cache(3600, array('users', 'active'))
   ->get();

// Clear all 'users' cache entries at once
$u->clear_cache(array('users'));

```

## Automatic Cache Invalidation

Cache is automatically cleared when data changes.

### On Save

```php

// First query - hits database
$u = new User();
$u->where('id', 123)->cache(300)->get();

// Modify and save
$u->email = 'new@email.com';
$u->save(); // Automatically clears cache for User 123

// Next query - hits database (cache was cleared)
$u2 = new User();
$u2->where('id', 123)->cache(300)->get();

```

### On Delete

```php

$u = new User();
$u->where('id', 123)->get();
$u->delete(); // Clears all cache entries for User model

```

### Bulk Operations

```php

$u = new User();
$u->where('last_login <', '2020-01-01')
  ->delete_all(); // Clears all User cache entries

```

## cache_relations() - Cache Relationships

Cache related objects to avoid N+1 query problems.

### Basic Usage

```php

$u = new User();
$u->where('active', 1)
  ->cache(300)
  ->cache_relations(array('group', 'profile'))
  ->get();

foreach ($u->all as $user) {
    // These don't trigger queries - served from cache
    echo $user->group->name;
    echo $user->profile->bio;
}

```

### Custom TTL for Relations

```php

$u = new User();
$u->cache(300) // Cache users for 5 min
  ->cache_relations(array(
      'group' => 3600,    // Cache groups for 1 hour
      'profile' => 1800   // Cache profiles for 30 min
  ))
  ->get();

```

### Nested Relations

```php

$p = new Post();
$p->cache(600)
  ->cache_relations(array(
      'author',                  // Cache post author
      'author.group',            // Cache author's group
      'comments',                // Cache all comments
      'comments.user'            // Cache comment authors
  ))
  ->get();

```

## no_cache() - Disable Caching

Bypass cache and force fresh database query.

### Basic Usage

```php

// Force fresh data from database
$u = new User();
$u->where('id', 123)
  ->no_cache()
  ->get();

```

### Use Cases

```php

// Admin dashboard - always show real-time data
if ($is_admin) {
    $u->no_cache();
}
$u->get();

// After payment - verify funds immediately
$account = new Account();
$account->where('user_id', $user_id)
        ->no_cache() // Don't trust cache for money!
        ->get();

```

## clear_cache() - Manual Cache Clearing

### Clear All Cache for Model

```php

$u = new User();
$u->clear_cache(); // Clears all User cache entries

```

### Clear Specific Tags

```php

$u = new User();
$u->clear_cache(array('admins', 'premium_users'));

```

### Clear Entire Cache

```php

// Clear everything (use sparingly!)
$u = new User();
$u->clear_cache('*');

```

### Scheduled Cache Clearing

```php

// In a cron job or scheduled task
class Maintenance extends CI_Controller {
    public function clear_stale_cache() {
        $models = array('User', 'Product', 'Order');
        
        foreach ($models as $model_name) {
            $model = new $model_name();
            $model->clear_cache();
        }
        
        echo "Cache cleared for " . count($models) . " models\n";
    }
}

```

## Performance Benchmarks

### Simple Query Performance

### Complex Query with Relations

```php

// Without cache: 850ms, 15 queries
$posts = new Post();
$posts->where('published', 1)
      ->get();
foreach ($posts->all as $post) {
    echo $post->author->name;      // +10 queries
    echo $post->category->name;    // +10 queries
}

// With cache: 15ms, 1 query (first run), 0 queries (subsequent)
$posts = new Post();
$posts->where('published', 1)
      ->cache(300)
      ->cache_relations(array('author', 'category'))
      ->get();
foreach ($posts->all as $post) {
    echo $post->author->name;      // From cache
    echo $post->category->name;    // From cache
}

```

**Result:** 56x faster, 99% fewer database queries

## Best Practices

### Choose Appropriate TTLs

### Tag Everything

```php

// BAD - hard to invalidate specific cache
$u = new User();
$u->where('role', 'admin')->cache(3600)->get();

// GOOD - can clear by tag
$u = new User();
$u->where('role', 'admin')
  ->cache(3600, array('users', 'admins', 'roles'))
  ->get();

// Later: clear all admin-related cache
$u->clear_cache(array('admins'));

```

### Cache Read-Heavy Queries

```php

// DON'T cache frequently changing data
$orders = new Order();
$orders->where('status', 'pending')
       ->cache(300) // BAD - changes constantly
       ->get();

// DO cache stable data
$products = new Product();
$products->where('active', 1)
         ->cache(3600) // GOOD - changes rarely
         ->get();

```

### Monitor Cache Hit Rates

```php

// Add logging to track cache effectiveness
$u = new User();
$start = microtime(true);
$u->where('id', 123)->cache(300)->get();
$time = microtime(true) - $start;

if ($time < 0.01) {
    log_message('debug', 'Cache HIT for User 123');
} else {
    log_message('debug', 'Cache MISS for User 123');
}

```

## Advanced Usage

### Conditional Caching

```php

$u = new User();
$u->where('id', $user_id);

// Cache for regular users, fresh data for admins
if (!$is_admin) {
    $u->cache(300);
}

$u->get();

```

### Cache Warming

```php

// Pre-populate cache during off-peak hours
class CacheWarmer extends CI_Controller {
    public function warm_user_cache() {
        $u = new User();
        $u->where('active', 1)
          ->cache(3600, array('users', 'active'))
          ->get();
        
        echo "Warmed cache for " . $u->result_count() . " users\n";
    }
}

```

### Multi-Level Caching

```php

// Cache both the query and the processed result
$cache_key = 'dashboard_stats_' . $user_id;
$stats = $this->cache->get($cache_key);

if (!$stats) {
    $orders = new Order();
    $orders->where('user_id', $user_id)
           ->cache(300) // Level 1: Query cache
           ->get();
    
    // Process data
    $stats = array(
        'total' => $orders->count(),
        'revenue' => $orders->sum('amount')
    );
    
    // Level 2: Result cache
    $this->cache->save($cache_key, $stats, 600);
}

return $stats;

```

## Troubleshooting

### Cache Not Working

```php

// Verify cache is enabled
$u = new User();
if (!$u->_cache_enabled) {
    die('Cache is disabled in config');
}

// Test cache driver connection
try {
    $u->cache(60)->where('id', 1)->get();
    echo "Cache working!";
} catch (Exception $e) {
    die('Cache error: ' . $e->getMessage());
}

```

### Stale Data

```php

// If seeing old data after updates, clear cache
$u = new User();
$u->clear_cache();

// Or reduce TTL
$u->cache(30); // Only cache for 30 seconds

```

### Memory Issues (Redis/Memcached)

```php

// Monitor cache size
// Redis: redis-cli INFO memory
// Memcached: telnet localhost 11211, then: stats

// Reduce TTLs if memory fills up
$config['cache_ttl'] = 600; // 10 minutes instead of 1 hour

```

### Cache driver unavailable

If the configured driver fails to initialise you'll see a log entry similar to:

```
[DataMapper] DataMapper cache disabled: cache driver unavailable. | context={"model":"User","key":"query:user:...","driver":"redis"}
```

Caching is skipped for that query only—fix the underlying service and the next request will resume caching automatically.

DataMapper throttles repeated bootstrap attempts for the same driver configuration. After a failure it waits 30 seconds before trying again, which keeps your logs clean even if Redis/Memcached are down for an extended period. Changing the cache configuration or driver forces an immediate retry.

### Production cache directory

When `production_cache` is configured, DataMapper verifies that the target directory exists and is writable before writing schema caches. You'll get a warning such as:

```
[DataMapper] DataMapper production cache directory is not writable: APPPATH/cache/datamapper (skipping cache write)
```

Update the directory permissions or path to restore the optimisation.

## Function Reference

### $object->cache($ttl, $tags)

Enable caching for the current query.

**Returns:**$this for method chaining

### $object->no_cache()

Disable caching for the current query.

**Returns:**$this for method chaining

### $object->cache_relations($relations)

Enable caching for relationship data.

**Returns:**$this for method chaining

### $object->clear_cache($tags)

Clear cached data.

**Returns:**bool - Success status

## See Also

- [Streaming & Chunking](streaming) - Process large datasets efficiently
- [Get](/guide/models/get) - Retrieve records from database
- [Save](/guide/models/save) - Persist changes (triggers cache invalidation)
- [Delete](/guide/models/delete) - Remove records (clears cache)