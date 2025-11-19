# Streaming & Chunking (DataMapper 2.0)

Process large datasets efficiently without loading all records into memory. Perfect for handling millions of records with minimal memory footprint.

**New in DataMapper 2.0:** These methods allow you to process large datasets in batches or one-by-one, reducing memory usage by up to **99%**.

Available Methods:

- **chunk()** - Process results in configurable batches
- **chunkById()** - ID-based chunking for better performance
- **cursor()** - Iterate one record at a time using PHP Generators
- **lazy()** - Lazy collection with chainable operations

## Why Use Streaming?

Traditional get() loads all records into memory at once. For large datasets, this causes:

- High memory usage (can exceed PHP's memory limit)
- Slow response times
- Server crashes on very large datasets

**Solution:** Process records in batches or streams.

## chunk() - Batch Processing

Process results in manageable batches. Ideal for bulk updates and processing large datasets.

Behind the scenes each chunk is fetched using `get_clone(TRUE)`, so the original query builder (filters, eager-load directives, cache flags, etc.) remains untouched. Cache metadata is also cleared on the clones, ensuring you always read fresh records even if the parent query is configured to cache results.

### Basic Usage

```php

$u = new User();
$u->chunk(1000, function($users) {
    foreach ($users as $user) {
        // Process each user
        $user->send_newsletter();
    }
});

```

### With Query Constraints

```php

$u = new User();
$u->where('active', 1)
  ->where('subscribed', 1)
  ->chunk(500, function($users) {
      foreach ($users as $user) {
          $user->last_contacted = date('Y-m-d H:i:s');
          $user->save();
      }
  });

```

### Early Termination

Return FALSE from the callback to stop processing:

```php

$u = new User();
$u->chunk(100, function($users) {
    foreach ($users as $user) {
        if (!$user->process()) {
            return false; // Stop chunking
        }
    }
    return true; // Continue to next chunk
});

```

## chunkById() - Fast ID-Based Chunking

More reliable and faster than offset-based chunking for large tables. Uses WHERE id > $lastId instead of OFFSET.

By default DataMapper now uses your model's `primary_key` when no column is provided, which means `chunkById()` works out of the box for custom keys—just make sure the key is monotonically increasing.

### Basic Usage

```php

$u = new User();
$u->chunkById(5000, function($users) {
    foreach ($users as $user) {
        $user->process();
    }
});

```

### Custom ID Column

```php

$o = new Order();
$o->where('status', 'pending')
  ->chunkById(1000, function($orders) {
      foreach ($orders as $order) {
          $order->status = 'processing';
          $order->save();
      }
  }, 'order_id'); // Custom ID column

```

### Performance Comparison

::: tip Immutable queries
Chunk clones never mutate your original instance. You can safely reuse `$u` after the loop or keep chaining additional scopes without worrying about residual limits, offsets, or cache settings.
:::

## cursor() - Memory-Efficient Iteration

Returns a PHP Generator that yields one record at a time. Extremely memory-efficient for large datasets.

Like the chunk helpers, each batch the cursor streams is loaded through a cloned query builder with caching disabled to avoid serving stale pages while you iterate.

### Basic Usage

```php

$u = new User();
foreach ($u->cursor() as $user) {
    // Only one user in memory at a time
    $user->process();
    $user->save();
}

```

### With Filters

```php

$u = new User();
$u->where('last_login <', '2020-01-01');

foreach ($u->cursor() as $user) {
    $user->delete(); // Clean up inactive users
}

```

### Memory Comparison

```php

// BAD - Loads all 1 million users (~500MB)
$u = new User();
$u->get();
foreach ($u->all as $user) {
    $user->process();
}

// GOOD - Only loads 1000 at a time (~500KB)
$u = new User();
foreach ($u->cursor() as $user) {
    $user->process();
}

```

## lazy() - Lazy Collections

Returns a DMZ_LazyCollection with chainable operations. Combines the power of collections with memory efficiency.

### Basic Usage

```php

$u = new User();
$lazy = $u->where('active', 1)->lazy();

// Operations are chained but not executed yet
$emails = $lazy
    ->map(function($user) { return $user->email; })
    ->filter(function($email) { return strpos($email, '@gmail.com'); })
    ->take(1000);

// Now iterate (executes in chunks)
foreach ($emails as $email) {
    send_email($email);
}

```

### Available Operations

### Complex Pipeline Example

```php

$u = new User();
$report = $u->where('created_at >', '2024-01-01')
    ->lazy(500)
    ->filter(function($user) {
        return $user->purchases->count() > 0;
    })
    ->map(function($user) {
        return array(
            'name' => $user->name,
            'total' => $user->purchases->sum('amount'),
            'count' => $user->purchases->count()
        );
    })
    ->filter(function($data) {
        return $data['total'] > 1000;
    })
    ->take(100);

// Convert to array for final report
$top_customers = $report->toArray();

```

## Best Practices

### Choose the Right Tool

### Optimal Chunk Sizes

```php

// Too small - too many queries
$u->chunk(10, $callback); // BAD

// Too large - memory issues
$u->chunk(100000, $callback); // BAD

// Just right
$u->chunk(1000, $callback);     // GOOD: Most cases
$u->chunkById(5000, $callback); // GOOD: Large tables
$u->lazy(2000);                 // GOOD: Pipelines

```

### Error Handling

```php

$u = new User();
$u->chunk(1000, function($users) {
    try {
        foreach ($users as $user) {
            $user->process();
        }
    } catch (Exception $e) {
        log_message('error', 'Chunk failed: ' . $e->getMessage());
        return false; // Stop processing
    }
});

```

## Function Reference

### $object->chunk($size, $callback)

Process results in batches of $size. Callback receives a DMZ_Collection.

**Returns:**bool - TRUE if all chunks processed, FALSE if stopped early

### $object->chunkById($size, $callback, $column, $alias)

Process results in batches using ID-based pagination. More efficient for large tables.

**Returns:**bool - TRUE if all chunks processed, FALSE if stopped early

### $object->cursor()

Returns a Generator that yields one record at a time.

**Returns:**Generator

### $object->lazy($chunkSize)

Returns a lazy collection with chainable operations.

**Returns:**DMZ_LazyCollection

## See Also

- [Query Caching](caching) - Cache query results for better performance
- [Get](/guide/models/get) - Traditional get() method
- [Query Builder](query-builder) - Modern query builder