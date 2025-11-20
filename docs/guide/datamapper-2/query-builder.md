# Query Builder & Collections (DataMapper 2.0)

DataMapper 2.0 brings a modern, chainable query experience directly onto your models. You keep the classic API when you need it, but gain a flexible query builder, eager loading, and rich collection helpers whenever you opt in.

::: tip TL;DR
- Keep your existing controllers intact while adding builder calls where you need them.
- Reach for `with()` to prevent N+1 queries and `collect()` when you want collection helpers.
- Tweak behaviour in `application/config/datamapper.php` if you need legacy array results or a custom collection class.
:::

> **Backward compatible:** Every example here co-exists with legacy controllers. Switch to the builder gradually, call by call if you like.

## Quick Start

### Classic DataMapper (still works)
```php
$users = new User();
$users->where('status', 'active');
$users->where('age >', 18);
$users->order_by('created_at', 'DESC');
$users->get();
```

### Modern Query Builder chain
```php
$users = (new User())
    ->where('status', 'active')
    ->where('age', 18, '>')
    ->orderBy('created_at', 'DESC')
    ->with('posts', 'comments') // eager load relationships
    ->limit(10)
    ->get();
```

### Mix & match safely
Need to keep legacy code untouched? Call builder helpers only where you need them; classic `get()` still returns `$this->all` for full compatibility.

## Core Concepts at a Glance
- Every `DataMapper` model can hand back a `DMZ_QueryBuilder` pipeline.
- CamelCase helpers are aliases of their snake_case counterparts (`orderBy` / `order_by`).
- Builder chains return the builder until `get()` or another terminal method executes the SQL.
- Result helpers (`collect()`, `first()`, `value()`, etc.) decide how the record set comes back.

## Filtering Records

### Where clauses
```php
$users = (new User())
    ->where('status', 'active')
    ->where('age', 18, '>')
    ->where('name', 'John%', 'LIKE')
    ->whereIn('id', [1, 2, 3])
    ->whereBetween('age', 18, 65)
    ->whereNull('deleted_at')
    ->whereNotNull('email_verified_at');
```

### Relation-aware filters
```php
$installations = (new Installation())
    ->whereRelated('building', 'active', 1)
    ->orWhereRelated('building/client', 'disable', 0)
    ->has('tasks', '>=', 3)
    ->whereHas('tasks', function ($tasks) {
        $tasks->where('status', 'open')->order_by('created_at', 'DESC');
    })
    ->whereDoesntHave('errors');
```

## Sorting, Limiting & Logical Grouping
```php
$users = (new User())
    ->orderBy('created_at', 'DESC')
    ->orderBy('name')
    ->limit(20)
    ->offset(40)
    ->groupStart()
        ->like('company_name', 'North%')
        ->orLike('email', 'north@example.com')
    ->groupEnd()
    ->get();
// `take()` and `skip()` are aliases for `limit()` and `offset()`.
```

## Grouping & Having
```php
$installations = (new Installation())
    ->select('building_id, COUNT(*) AS total')
    ->groupBy('building_id')
    ->having('total', 5, '>')
    ->orderBy('total', 'DESC')
    ->get();
```

## Selecting Columns
```php
$report = (new Installation())
    ->select('id, title, created_date')
    ->selectMin('temperature')
    ->selectMax('pressure')
    ->selectAvg('uptime')
    ->selectSum('energy_usage')
    ->get();
```

## Aggregate Helpers
```php
$totalActive = (new User())->where('status', 'active')->count();
$totalSpend  = (new Order())->sum('total');
$avgPrice    = (new Product())->avg('price');
$highest     = (new Product())->max('price');
$lowest      = (new Product())->min('price');
```

## Working with Relationships (Eager Loading)
```php
// Without eager loading (N+1 problem)
$posts = (new Post())->get();
foreach ($posts as $post) {
    echo $post->user->name; // one query per post
}

// With eager loading (two queries)
$posts = (new Post())
    ->with('user')
    ->orderBy('created_at', 'DESC')
    ->limit(25)
    ->get();

// Multiple relationships
$posts = (new Post())
    ->with('user', 'comments', 'tags')
    ->get();

// Nested relationships
$posts = (new Post())
    ->with('comments.author', 'user.profile')
    ->get();

// Constrained eager loading
$posts = (new Post())
    ->with('comments', function ($q) {
        $q->where('approved', 1)->limit(5);
    })
    ->get();
```
::: info Need deeper patterns?
See `guide/datamapper-2/eager-loading.md` for constraint callbacks, benchmarking tips, and troubleshooting N+1 issues.
:::

## Finding Individual Records
```php
$user = (new User())->find(42);            // by primary key
$user = (new User())->findOrFail(42);      // throws if missing
$post = (new Post())
    ->where('featured', 1)
    ->first();                             // first match

$invoice = (new Invoice())
    ->firstOrCreate(
        ['reference' => 'INV-2025-001'],
        ['status' => 'draft', 'currency' => 'EUR']
    );

$allUsers = (new User())->all();           // convenience alias
```

## Working with Collections
Collections wrap result sets in `DMZ_Collection`, giving you over 50 helpers while keeping DataMapper objects intact.

### How to obtain a collection
```php
// 1. Classic DataMapper, then convert
$posts = new Post();
$posts->where('status', 'published')->get();
$collection = $posts->collect();

// 2. Builder shortcut
$collection = (new Post())
    ->where('status', 'published')
    ->collect(10); // optional limit

// 3. Magic proxying (auto-converts)
$titles = (new Post())
    ->where('status', 'published')
    ->get()
    ->pluck('title');
```

### Common helpers
```php
$posts = (new Post())->where('published', 1)->collect();

// Data retrieval
$count   = $posts->count();
$first   = $posts->first();
$last    = $posts->last();
$isEmpty = $posts->is_empty();
$byId    = $posts->find(5);

// Filtering & transformation
$featured = $posts->filter(function ($post) {
    return $post->featured === 1;
});

$titles = $posts->map(function ($post) {
    return $post->title;
});

$ids = $posts->pluck('id');

$topTitles = $posts
    ->filter(fn ($p) => $p->views > 1000)
    ->pluck('title')
    ->to_array();

// Aggregation
$total = $posts->sum('views');
$avg   = $posts->avg('views');
$min   = $posts->min('views');
$max   = $posts->max('views');

// Bulk operations
$users = (new User())->where('active', 1)->collect();
$users->each(function ($user) {
    $user->last_seen = time();
    $user->save();
});

$users->save_all();
$users->delete_all();
```

## Configuration & Customising the Builder
Adjust behaviour in `application/config/datamapper.php`:
```php
$config['query_builder'] = array(
    'collection_class'     => 'App_Collection',
    'legacy_array_results' => FALSE,
    'auto_load_extension'  => TRUE,
);
```

| Setting | Purpose |
| --- | --- |
| `collection_class` | Swap in your own class (extend `DMZ_Collection`) to add project-specific helpers. |
| `legacy_array_results` | When `TRUE`, builder queries return plain arrays for older code. Call `$model->make_collection(NULL, TRUE)` to force a collection later. |
| `auto_load_extension` | Disable if you only want the builder on selected models. |

Because DataMapper now centralises collection creation through `make_collection()`, these settings apply consistently across classic and builder workflows.

## Tips for High-Performance Queries
```php
// 1. Always eager load relationships you touch in loops
$posts = (new Post())->with('user')->get();

// 2. Select only needed columns
$posts = (new Post())
    ->select('id, title, created_at')
    ->with('user:id,name')
    ->get();

// 3. Paginate or limit large datasets
$recent = (new Post())
    ->orderBy('created_at', 'DESC')
    ->limit(50)
    ->get();

// 4. Chain multiple `with()` calls for complex graphs
$posts = (new Post())->with('user', 'category', 'comments.user')->get();

// 5. Combine with caching helpers for expensive reads
$featured = (new Post())
    ->where('featured', 1)
    ->with('user', 'category')
    ->cache(3600)
    ->get();
```
More advanced scenarios (chunking, streaming, or cache management) are covered in the dedicated guides under `guide/datamapper-2/`.

## See Also
- `guide/datamapper-2/eager-loading.md` - In-depth eager loading & constraint patterns.
- `guide/datamapper-2/collections.md` - Deeper dive into collection helpers.
- `guide/datamapper-2/caching.md` - Query caching, cache busting, and `no_cache()`.
- `guide/datamapper-2/soft-deletes.md` - Working with `deleted_at` aware queries.
- `guide/datamapper-2/timestamps.md` - Automatic timestamp helpers.
