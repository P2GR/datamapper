# Query Caching (DataMapper 2.2.0)

DataMapper can cache the result of a `get()` query through the configured cache driver. Caching is opt-in per query: call `cache()` before `get()` or before a result helper that executes the query.

The cache layer supports three drivers:

- `file`
- `redis`
- `memcached`

If a driver cannot be initialized, DataMapper logs the failure, disables caching for that query, and runs the database query normally.

Queries inside a database transaction bypass the shared cache. Successful writes
invalidate affected entries after the outermost transaction commits; rollback
does not publish uncommitted results. This also applies when transactions are
completed through CI3's database object. Deploy the bundled DataMapper database
driver together with the model library so connection clones share this state.

## Configuration

Configure the driver in `application/config/datamapper.php`.

### File Cache

```php
$config['cache_driver'] = 'file';
$config['cache_config'] = array(
    'cache_dir' => APPPATH . 'cache/datamapper',
    'file_mode' => 0640
);
```

The file driver creates the cache directory if it does not exist. The directory must be writable by PHP.

### Redis

```php
$config['cache_driver'] = 'redis';
$config['cache_config'] = array(
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => '',
    'database' => 0,
    'prefix' => 'dmz:',
    'timeout' => 2.5
);
```

The Redis driver requires the PHP Redis extension.

Provide credentials through your application's configuration or secret store. `password` accepts a password string for the default Redis user, or an array containing the ACL username and password.

Redis values are decoded with an object allowlist. The defaults are `stdClass`, `DateTime`, and `DateTimeImmutable`. If custom accessors or cached values contain your own value objects, list their concrete classes explicitly:

```php
$config['cache_config']['allowed_classes'] = array(
    'stdClass', 'DateTime', 'DateTimeImmutable', Money::class
);
```

This array replaces the default allowlist. Use `FALSE` to prevent all object instantiation. Unlisted object types are restored as `__PHP_Incomplete_Class`; their magic methods are not run. Only allow classes you trust to instantiate. Model cache payloads store model names as strings, and hydration accepts only DataMapper model classes.

The application Redis user needs `GET`, `MGET`, `SETEX`, `DEL`, `EXISTS`, `SCAN`, and `SELECT`, plus authentication when configured. Counter helpers also need `MULTI`, `EXEC`, `DISCARD`, `INCRBY`, and `EXPIRE`. `INFO` is optional: when denied, `get_stats()` returns basic local counters. Invalidation and `flush()` use prefix-matched `SCAN` and `DEL`, so no `KEYS`, `FLUSHDB`, or `FLUSHALL` permission is required.

`set()` and `set_multiple()` use `SETEX` with the supplied positive TTL (default 3,600 seconds). Redis driver `increment()` and `decrement()` atomically update the counter and refresh its TTL to 3,600 seconds, including counters that previously had no expiry. This expiry policy applies to cache-driver counters, not model-column counters.

### Memcached

```php
$config['cache_driver'] = 'memcached';
$config['cache_config'] = array(
    'servers' => array(
        array('127.0.0.1', 11211, 100)
    ),
    'prefix' => 'dmz:',
    'compression' => TRUE
);
```

The Memcached driver requires the PHP Memcached extension.

Memcached invalidation uses DataMapper namespace generations, so `flush()` and supported pattern clears do not flush unrelated applications sharing the same server. Batch reads/writes and atomic counters use the same generated namespace as ordinary cache operations.

## Basic Usage

```php
$users = new User();
$users->where('active', 1)
      ->cache(300)
      ->get();
```

`cache($ttl, $key = NULL)` enables caching for the next query. The first argument is the time-to-live in seconds. The second argument is an optional cache key; when omitted, DataMapper generates a key from the model and SQL query.

```php
$settings = new Setting();
$settings->cache(3600, 'settings:all')
         ->get();
```

## Result Helpers

Result helpers such as `pluck()`, `value()`, and `collect()` execute a query internally. Call `cache()` before the helper.

```php
$emails = (new User())
    ->where('newsletter', 1)
    ->cache(900)
    ->pluck('email');

$latestSlug = (new Post())
    ->cache(120)
    ->order_by('created_at', 'DESC')
    ->value('slug', 'draft');

$topPosts = (new Post())
    ->cache(900)
    ->where('status', 'published')
    ->collect(10);
```

When combining caching with eager loading, call `cache()` anywhere in the chain before the terminal method.

```php
$posts = (new Post())
    ->with('author')
    ->where('status', 'published')
    ->cache(600)
    ->get();
```

## Bypassing Cache

Use `no_cache()` when a model instance may already have caching enabled but the next query must read directly from the database.

```php
$account = new Account();
$account->where('user_id', $user_id)
        ->no_cache()
        ->get();
```

## Relationship Payloads

`cache_relations($ttl = 3600)` defers cache storage until eager loading completes, then stores the hydrated graph under a `relation-query:{model}:...` key. The eager relation paths are part of the generated cache identity.

```php
$posts = (new Post())
    ->cache_relations(600)
    ->with('author', 'category')
    ->where('published', 1)
    ->get();
```

`cache_relations()` does not accept a relation list. Use `with()` to decide which relations are loaded.

Constrained eager loads bypass relation caching because callback behavior cannot be represented safely in a portable cache key. Ordinary `cache()` remains base-result caching and does not suppress eager queries on a cache hit.

## Clearing Cache

Use `clear_cache($pattern = NULL)` to remove cached entries for a model. With no pattern, DataMapper clears query cache entries for that model.

```php
$users = new User();
$removed = $users->clear_cache();
```

Pass a driver pattern when you need a narrower delete. For generated query keys, model entries use the `query:{model}:...` prefix.

```php
$users = new User();
$users->clear_cache('query:user:*');
```

The number returned is the count reported by the configured driver.

## Automatic Invalidation

DataMapper invalidates generated model query cache entries after `save()`, `delete()`, `delete_all()`, `update()`, `update_all()`, counters, `destroy()`, and `truncate()`. It also invalidates all `relation-query:*` entries because the changed model may be nested in any cached graph.

Explicit custom keys remain literal. Clear them yourself after writes, for example
`$settings->clear_cache('settings:all')`, or choose generated keys for automatic invalidation.

```php
$user = new User();
$user->where('id', 123)->cache(300)->get();

$user->email = 'new@example.com';
$user->save();
```

The next matching cached query will read from the database and store a fresh payload.

## Operational Notes

- Cache only reads that are repeated often enough to justify serialization and storage.
- Prefer short TTLs for data that changes frequently.
- Use explicit keys only for stable, well-known queries.
- Make sure the file cache directory or external cache service is available in every environment that enables caching.
- Watch CodeIgniter logs for messages prefixed with `[DataMapper]`; cache hits, misses, stores, and driver initialization failures are logged there.

## API Reference

These helpers are available on DataMapper model instances and on the `DMZ_QueryBuilder` wrapper returned by methods such as `with()`.

### `$object->cache($ttl = 3600, $key = NULL)`

Enable caching for the next query.

### `$object->no_cache()`

Disable caching for the next query.

### `$object->cache_relations($ttl = 3600)`

Enable query caching for a result that may include eager-loaded relations.

### `$object->clear_cache($pattern = NULL)`

Clear cached query entries and return the number removed.

## See Also

- [Query Builder](query-builder)
- [Eager Loading](eager-loading)
- [Configuration](/guide/getting-started/configuration)
