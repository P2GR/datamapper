# Query Builder & Collections (DataMapper 2.0)

DataMapper 2.0 introduces modern, chainable query methods directly on your models. Build elegant queries with **eager loading** to eliminate N+1 problems and **collections** for powerful data manipulation.

**New in DataMapper 2.0:** Methods like with(), orderBy(), find(), and collect() work directly on your models. No wrappers needed!

Key Features:

- **Eager Loading:** Use with() to load relationships efficiently (solve N+1 problems)
- **Collections:** Laravel-style collection methods on query results
- **Query Builder Methods:** Chainable orderBy(), find(), first() directly on models
- **100% Backward Compatible:** All classic DataMapper code works unchanged

## Quick Start

All methods work directly on your DataMapper models:

```php

// Traditional DataMapper (still works!)
$posts = new Post();
$posts->where('status', 'published')->get();

// New: Eager loading with with()
$posts = new Post();
$posts->where('status', 'published')
      ->with('user', 'comments')  // Eager load relationships!
      ->get();

// New: orderBy() alias - returns QueryBuilder for chaining
$posts = (new Post())
    ->where('status', 'published')
    ->orderBy('created_at', 'desc')  // Camel case alias
    ->get();

// New: Collections - powerful data manipulation
$titles = (new Post())
    ->where('published', 1)
    ->collect()
    ->pluck('title');

```

## Subsections

- [Eager Loading (Solving N+1 Problems)](#Eager.Loading)
- [Query Builder Methods](#Query.Builder.Methods)
- [Working with Collections](#Collections)
- [Performance Optimization](#Performance)
- [Real-World Examples](#Examples)

## Eager Loading: Solving the N+1 Problem

**Performance Game-Changer:** Eager loading is the most impactful feature in DataMapper 2.0. It can reduce database queries from **100+ to just 2-3 queries**, dramatically improving application performance.

### What is the N+1 Problem?

The N+1 problem occurs when you load related data in a loop:

```php

// Inefficient: Creates 1 + 50 = 51 database queries
$posts = new Post();
$posts->limit(50)->get();

foreach ($posts->all as $post) {
    echo $post->title . ' by ' . $post->user->name;  // Each iteration triggers an extra query
}

```

### The Solution: with() Method

Eager loading fetches all related records in advance with just one additional query:

```php

// Efficient: Only 2 database queries total
$posts = new Post();
$posts->with('user')              // Eager load the user relationship
      ->limit(50)
      ->get();

foreach ($posts->all as $post) {
    echo $post->title . ' by ' . $post->user->name;  // No extra queries!
}

```

### Multiple Relationships

Load multiple relationships at once:

```php

$posts = new Post();
$posts->with('user', 'category', 'tags')  // Load all relationships
      ->get();

```

### Nested Relationships

Load relationships of relationships using dot notation:

```php

$posts = new Post();
$posts->with('comments.user')  // Load comments and their users
      ->get();

foreach ($posts->all as $post) {
    foreach ($post->comments as $comment) {
        echo $comment->user->name;  // All loaded efficiently!
    }
}

```

## Query Builder Methods

DataMapper 2.0 adds several methods that return a DMZ_QueryBuilder instance for chainable querying:

### with() - Eager Load Relationships

**NEW:** Most important performance feature!

```php

$posts = new Post();
$posts->with('user', 'comments', 'tags')->get();

```

### find() - Find by Primary Key

```php

// Find user with ID 42
$user = (new User())->find(42);

// With eager loading
$user = (new User())->find(42)->with('posts');

```

### first() - Get First Result

```php

$post = (new Post())
    ->where('featured', 1)
    ->first();

```

### orderBy() - Camel Case Alias

```php

// Classic method still works
$posts->order_by('created_at', 'desc')->get();

// New camel case alias returns QueryBuilder for chaining
$posts = (new Post())
    ->orderBy('created_at', 'desc')
    ->orderBy('title')
    ->get();

```

### has()/whereHas() - Relation-aware filtering

Filter parent models based on related records without dropping back to `where_related`:

```php

// Snake_case helper (DataMapper style)
$installations = (new Installation())
    ->where_has('building', function ($building) {
        $building->where('active', 1)
                 ->with('client');
    })
    ->get();

// CamelCase alias for Laravel-style ergonomics
$users = (new User())
    ->whereHas('posts', function ($posts) {
        $posts->where('published', 1)
              ->order_by('created_at', 'desc');
    })
    ->get();

// Count comparison
$authors = (new User())
    ->has('posts', '>=', 10)
    ->get();

// Negative variant
$drafts = (new Post())
    ->whereDoesntHave('comments')
    ->get();

```

All helpers accept either `snake_case` or `camelCase` naming, so existing DataMapper conventions stay intact while teams familiar with Eloquent can keep their muscle memory.

> **Tip:** Constraints receive a query builder instance for the related model. You can chain further `with()` calls inside the callback to eager load nested relations before the count check runs.

### collect() - Convert to Collection

```php

// Fetch and convert to collection in one step
$collection = (new Post())
    ->where('published', 1)
    ->collect(10);  // Limit 10

// Or convert existing results
$posts = new Post();
$posts->get();
$collection = $posts->collect();

```

**Note:** All classic DataMapper methods (where(), get(), save(), delete(), etc.) continue to work exactly as before. The new methods add functionality without breaking changes.

## Working with Collections

Query results can be converted to DMZ_Collection objects with powerful helper methods:

### Three Ways to Use Collections

#### 1. Classic DataMapper (Unchanged)

```php

$posts = new Post();
$posts->where('status', 'published')->get();

// Results in $all array - still works!
foreach ($posts->all as $post) {
    echo $post->title;
}

```

#### 2. Explicit Collection Conversion

```php

// Fetch and convert to collection
$collection = (new Post())
    ->where('published', 1)
    ->collect(10);  // Limit 10

// Or convert existing results
$posts = new Post();
$posts->get();
$collection = $posts->collect();

```

#### 3. Magic Method Proxying

```php

// Call collection methods directly on the model
$titles = (new Post())
    ->where('status', 'published')
    ->get()
    ->pluck('title');  // Auto-converts to Collection!

```

### Collection Methods

#### Data Retrieval

```php

$posts = new Post();
$posts->get();

// Get count
echo $posts->collect()->count();

// Get first/last
$first = $posts->collect()->first();
$last = $posts->collect()->last();

// Check if empty
if ($posts->collect()->is_empty()) { /* ... */ }

// Find by ID
$post = $posts->collect()->find(5);

```

#### Filtering and Transformation

```php

$posts = new Post();
$posts->get();

// Filter results
$featured = $posts->collect()->filter(function($post) {
    return $post->featured === 1;
});

// Map to new format
$titles = $posts->collect()->map(function($post) {
    return $post->title;
});

// Pluck specific field
$ids = $posts->collect()->pluck('id');

// Chain operations
$result = $posts->collect()
    ->filter(function($p) { return $p->views > 1000; })
    ->pluck('title')
    ->to_array();

```

#### Aggregation

```php

$orders = new Order();
$orders->get();

// Sum values
$total = $orders->collect()->sum('amount');

// Average
$avg = $orders->collect()->avg('amount');

// Min/Max
$min = $orders->collect()->min('amount');
$max = $orders->collect()->max('amount');

```

### Customising the collection experience

The query builder feature set is configurable. Open `application/config/datamapper.php` and locate the `query_builder` block to tweak behaviour:

- **`collection_class`** — swap in your own class (extending `DMZ_Collection`) to add project-specific helpers.
- **`legacy_array_results`** — when set to `TRUE`, builder queries return plain arrays for legacy compatibility. You can still force a collection at any time by calling `$model->make_collection(NULL, TRUE)`.
- **`auto_load_extension`** — disable to opt into builder capabilities on specific models only.

```php
$config['query_builder'] = array(
    'collection_class' => 'App_Collection',
    'legacy_array_results' => FALSE,
);
```

Because DataMapper now routes all collection creation through `make_collection()`, your configuration applies consistently—whether results come from classic `get()` calls, builder `collect()` chains, or eager-loading helpers.

#### Bulk Operations

```php

$users = new User();
$users->where('active', 1)->get();

// Save all
$users->collect()->save_all();

// Delete all
$users->collect()->delete_all();

// Iterate with callback
$users->collect()->each(function($user) {
    $user->last_seen = time();
    $user->save();
});

```

[[Get documentation](/guide/models/get).

## Performance Optimization Tips

### 1. Always Use Eager Loading for Relationships

```php

// Inefficient example - N+1 problem
$posts = new Post();
$posts->get();
foreach ($posts->all as $post) {
    echo $post->user->name;  // Each iteration triggers another query
}

// Efficient approach - eager load
$posts = new Post();
$posts->with('user')->get();
foreach ($posts->all as $post) {
    echo $post->user->name;  // Already loaded
}

```

### 2. Select Only Needed Fields

```php

$posts = new Post();
$posts->select('id, title, created_at')  // Don't load large text fields
      ->get();

```

### 3. Use Limit for Large Datasets

```php

$recent = new Post();
$recent->order_by('created_at', 'desc')
       ->limit(10)
       ->get();

```

### 4. Chain Multiple with() for Complex Queries

```php

$posts = new Post();
$posts->with('user', 'category', 'tags', 'comments.user')
      ->get();

```

### 5. Combine with Caching for Maximum Performance

```php

$posts = new Post();
$posts->where('featured', 1)
      ->with('user', 'category')
      ->cache(3600)  // Cache for 1 hour
      ->get();

```

[[Query Caching](caching) for more details.

## Real-World Examples

### Blog Post Listing with Author and Comments

```php

// Without eager loading: 1 + 50 + 50 = 101 queries
$posts = new Post();
$posts->where('published', 1)
      ->order_by('created_at', 'desc')
      ->limit(50)
      ->get();
?>

<?php foreach ($posts->all as $post): ?>
    <h2><?= $post->title ?></h2>
    <p>By <?= $post->user->name ?></p>  <!-- Extra query here -->
    <p><?= $post->comments->count() ?> comments</p>  <!-- And here -->
<?php endforeach; ?>

<?php
// With eager loading: Only 3 queries!
$posts = new Post();
$posts->where('published', 1)
      ->with('user', 'comments')  // Eager load!
      ->order_by('created_at', 'desc')
      ->limit(50)
      ->get();
?>

<?php foreach ($posts->all as $post): ?>
    <h2><?= $post->title ?></h2>
    <p>By <?= $post->user->name ?></p>  <!-- From cache! -->
    <p><?= $post->comments->count() ?> comments</p>  <!-- From cache! -->
<?php endforeach; ?>

```

### E-commerce Product Catalog

```php

$products = new Product();
$products->where('active', 1)
         ->where('stock >', 0)
         ->with('category', 'images', 'reviews.user')
         ->order_by('name')
         ->get();

// Use collections for filtering
$featured = $products->collect()
    ->filter(function($p) { return $p->featured === 1; })
    ->take(10);

```

### User Dashboard with Statistics

```php

$user = new User();
$user->where('id', $user_id)
     ->with('orders', 'orders.items', 'addresses')
     ->get();

// Calculate statistics using collections
$total_spent = $user->orders->collect()->sum('total');
$order_count = $user->orders->count();
$avg_order = $user->orders->collect()->avg('total');

```

### Admin Report with Complex Filtering

```php

$orders = new Order();
$orders->where('created_at >=', '2024-01-01')
       ->with('user', 'items.product')
       ->order_by('created_at', 'desc')
       ->get();

// Complex filtering with collections
$report = $orders->collect()
    ->filter(function($o) { return $o->total > 100; })
    ->group_by('user_id')
    ->map(function($group) {
        return array(
            'user' => $group->first()->user->name,
            'count' => $group->count(),
            'total' => $group->sum('total')
        );
    });

```

## Summary

DataMapper 2.0 brings powerful new features while maintaining 100% backward compatibility:

- **Eager Loading:** Use with() to solve N+1 problems and reduce queries by 90%+
- **Collections:** Use collect() for Laravel-style data manipulation
- **Query Builder Methods:** Use orderBy(), find(), first() for cleaner code
- **No Breaking Changes:** All classic DataMapper code works unchanged

**Pro Tip:** Focus on adding ->with() to your relationship queries first—it's the single most impactful performance improvement you can make!

### Quick Migration Path

## See Also

- [Get](/guide/models/get) - Retrieving records (classic method)
- [Get By](/guide/models/get#object-get_by) - Filtering with where clauses
- [Query Caching](caching) - Cache results for even better performance
- [Streaming & Chunking](streaming) - Handle large datasets efficiently