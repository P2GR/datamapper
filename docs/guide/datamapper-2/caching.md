# Query Caching (DataMapper 2.0)

DataMapper can cache the result of a `get()` query through the configured cache driver. Caching is opt-in per query: call `cache()` before `get()` or before a result helper that executes the query.

The cache layer supports three drivers:

- `file`
- `redis`
- `memcached`

If a driver cannot be initialized, DataMapper logs the failure, disables caching for that query, and runs the database query normally.

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

`cache_relations($ttl = 3600)` enables the same query cache path and stores the hydrated result payload, including eager-loaded relation data that is present on the result set.

```php
$posts = (new Post())
    ->cache_relations(600)
    ->with('author', 'category')
    ->where('published', 1)
    ->get();
```

`cache_relations()` does not accept a relation list. Use `with()` to decide which relations are loaded.

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

DataMapper invalidates model query cache entries after writes such as `save()`, `delete()`, and `delete_all()`.

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