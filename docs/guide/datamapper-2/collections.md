# Collections (DataMapper 2.0)

Transform query results into powerful Collection objects with query builder-style methods for filtering, mapping, sorting, and aggregating data. Think of Collections as **arrays on steroids**.

**New in DataMapper 2.0:** Collection API inspired by Laravel, providing 50+ methods for data manipulation with elegant, chainable syntax.

## Why Collections?

Traditional DataMapper returns arrays or iterators. Collections provide:

- **Query Builder API** - Chain methods elegantly
- **Type Safety** - Work with DataMapper objects
- **Memory Efficient** - Lazy evaluation where possible
- **Rich Functionality** - 50+ built-in methods
- **Developer Experience** - Intuitive, readable code

## Basic Usage

```php
$users = new User();
$collection = $users->where('active', 1)->collect();

// Collection methods
$filtered = $collection->filter(function($user) {
    return $user->age >= 18;
});

$names = $collection->pluck('username');
$total = $collection->sum('order_total');
```

## Creating Collections

### From Query Results

```php
// Automatically returns Collection
$posts = Post::where('status', 'published')->collect();

// Or use get() then convert
$users = new User();
$users->get();
$collection = $users->to_collection();
```

### From Array

```php
use DataMapper\Collection;

$collection = new Collection([$user1, $user2, $user3]);
```

### Empty Collection

```php
$collection = new Collection();
// or
$collection = collect([]);
```

## Query Builder Helpers

DataMapper 2.0 ships first-class helpers on the query builder so you can choose the return style that matches your workload without extra plumbing:

- `collect()` — returns a `DMZ_Collection` for query builder chaining.
- `pluck($column)` — returns a plain array of column values (ideal for IDs or emails).
- `pluck_collection($column)` — returns a collection seeded with the plucked values when you still want collection methods.
- `pluck_values($column)` — legacy-friendly alias that mirrors the classic DataMapper helper.
- `value($column, $default = null)` — fetch a single scalar from the first row, optionally supplying a fallback when nothing matches.
- `first()` — returns the first hydrated model while leaving existing limits/offsets intact.

All helpers respect the active query, eager-load hints (`with()`), and caching directives:

```php
$emails = (new User())
    ->where('active', 1)
    ->order_by('last_login', 'DESC')
    ->cache(300)
    ->pluck('email');

$topAuthors = (new Post())
    ->with('author')
    ->where('status', 'published')
    ->order_by('view_count', 'DESC')
    ->collect()
    ->take(5);

$latestSlug = (new Post())
    ->order_by('created_at', 'DESC')
    ->value('slug', 'draft-placeholder');
```

These shortcuts are also available from an instantiated model (`$posts->collect()`, `$posts->pluck('title')`) so you can upgrade legacy code incrementally while keeping existing `get()` flows running.

## Available Methods

### Filtering Methods

#### filter()

Filter collection by callback:

```php
$adults = $users->collect()->filter(function($user) {
    return $user->age >= 18;
});

// With key
$active = $users->collect()->filter(function($user, $key) {
    return $user->active && $key % 2 === 0;
});
```

#### where()

Filter by field value:

```php
$admins = $users->collect()->where('role', 'admin');

// Operators supported
$expensive = $products->collect()->where('price', '>', 100);
$recent = $posts->collect()->where('created_at', '>=', '2024-01-01');
```

#### whereIn() / whereNotIn()

```php
$selected = $users->collect()->whereIn('id', [1, 5, 10, 15]);
$excluded = $users->collect()->whereNotIn('status', ['banned', 'deleted']);
```

#### whereNull() / whereNotNull()

```php
$pending = $orders->collect()->whereNull('shipped_at');
$completed = $orders->collect()->whereNotNull('completed_at');
```

#### whereBetween()

```php
$midRange = $products->collect()->whereBetween('price', [10, 50]);
```

#### first() / last()

```php
$firstUser = $users->collect()->first();
$lastUser = $users->collect()->last();

// With callback
$firstAdmin = $users->collect()->first(function($user) {
    return $user->role === 'admin';
});
```

#### take() / skip()

```php
$first10 = $users->collect()->take(10);
$skip5 = $users->collect()->skip(5);

// Negative takes from end
$last5 = $users->collect()->take(-5);
```

### Transformation Methods

#### map()

Transform each item:

```php
$usernames = $users->collect()->map(function($user) {
    return $user->username;
});

$formatted = $products->collect()->map(function($product) {
    return [
        'name' => $product->name,
        'price' => '$' . number_format($product->price, 2)
    ];
});
```

#### pluck()

Extract single column:

```php
$names = $users->collect()->pluck('username');
$emails = $users->collect()->pluck('email');

// With keys
$emailsByName = $users->collect()->pluck('email', 'username');
// Result: ['john' => 'john@example.com', ...]
```

#### transform()

Transform collection in-place:

```php
$collection->transform(function($user) {
    $user->name = strtoupper($user->name);
    return $user;
});
```

#### flatMap()

Map and flatten results:

```php
$allTags = $posts->collect()->flatMap(function($post) {
    return $post->tags; // Returns array of tags
});
// Single flat array of all tags
```

### Aggregation Methods

#### sum()

```php
$totalRevenue = $orders->collect()->sum('total');
$totalPoints = $users->collect()->sum('points');

// With callback
$totalPrice = $items->collect()->sum(function($item) {
    return $item->price * $item->quantity;
});
```

#### avg() / average()

```php
$averageAge = $users->collect()->avg('age');
$averagePrice = $products->collect()->average('price');
```

#### min() / max()

```php
$youngest = $users->collect()->min('age');
$oldest = $users->collect()->max('age');
$cheapest = $products->collect()->min('price');
$expensive = $products->collect()->max('price');
```

#### count()

```php
$total = $users->collect()->count();
$adults = $users->collect()->filter(fn($u) => $u->age >= 18)->count();
```

#### median() / mode()

```php
$medianAge = $users->collect()->median('age');
$commonRole = $users->collect()->mode('role');
```

### Sorting Methods

#### sortBy() / sortByDesc()

```php
$byName = $users->collect()->sortBy('username');
$byAge = $users->collect()->sortByDesc('age');

// With callback
$sorted = $users->collect()->sortBy(function($user) {
    return $user->last_name . ' ' . $user->first_name;
});
```

#### sort() / sortDesc()

```php
$numbers->collect()->sort();        // Ascending
$numbers->collect()->sortDesc();    // Descending
```

#### reverse()

```php
$reversed = $users->collect()->reverse();
```

#### shuffle()

```php
$random = $users->collect()->shuffle();
```

### Grouping Methods

#### groupBy()

```php
$byRole = $users->collect()->groupBy('role');
// Result: ['admin' => [...], 'user' => [...]]

$byCountry = $users->collect()->groupBy('country_id');

// With callback
$byAgeGroup = $users->collect()->groupBy(function($user) {
    return $user->age < 18 ? 'minor' : 'adult';
});
```

#### keyBy()

Key collection by field:

```php
$byId = $users->collect()->keyBy('id');
// Result: [1 => User, 2 => User, ...]

$byEmail = $users->collect()->keyBy('email');
```

#### partition()

Split into two groups:

```php
[$adults, $minors] = $users->collect()->partition(function($user) {
    return $user->age >= 18;
});
```

### Chunking Methods

#### chunk()

Split into chunks:

```php
$chunks = $users->collect()->chunk(100);

foreach ($chunks as $chunk) {
    // Process 100 users at a time
    $this->processUsers($chunk);
}
```

#### split()

Split into N groups:

```php
$groups = $users->collect()->split(3);
// 3 roughly equal groups
```

### Combining Methods

#### merge()

```php
$combined = $collection1->merge($collection2);
```

#### concat()

```php
$all = $users->collect()->concat($admins->collect());
```

#### union()

```php
$unique = $collection1->union($collection2);
```

#### zip()

```php
$zipped = $names->zip($emails, $ages);
// Result: ['John', 'john@example.com', 30], ...]
```

### Checking Methods

#### contains()

```php
$hasAdmin = $users->collect()->contains('role', 'admin');
$hasUser = $users->collect()->contains(function($user) {
    return $user->id === 5;
});
```

#### isEmpty() / isNotEmpty()

```php
if ($users->collect()->isEmpty()) {
    echo "No users found";
}

if ($products->collect()->isNotEmpty()) {
    // Process products
}
```

#### every()

Check if all items pass test:

```php
$allActive = $users->collect()->every(function($user) {
    return $user->active === 1;
});
```

#### some() / contains()

Check if any item passes test:

```php
$hasAdmin = $users->collect()->some(function($user) {
    return $user->role === 'admin';
});
```

### Utility Methods

#### each()

Iterate over items:

```php
$users->collect()->each(function($user) {
    echo $user->name . "\n";
});

// Break early by returning false
$users->collect()->each(function($user) {
    if ($user->id === 10) {
        return false; // Stop iteration
    }
    echo $user->name;
});
```

#### tap()

Perform action without modifying collection:

```php
$users->collect()
    ->tap(function($collection) {
        error_log("Processing " . $collection->count() . " users");
    })
    ->filter(fn($u) => $u->active)
    ->each(fn($u) => $u->send_email());
```

#### pipe()

Pass collection to callback:

```php
$result = $users->collect()->pipe(function($collection) {
    return $collection->filter(fn($u) => $u->active)->count();
});
```

#### dd() / dump()

Debug and die:

```php
$users->collect()
    ->where('active', 1)
    ->dd(); // Dump and die

$users->collect()->dump(); // Dump and continue
```

## Chaining Methods

Collections excel at chaining:

```php
$result = Post::where('status', 'published')
    ->collect()
    ->filter(fn($p) => $p->view_count > 1000)
    ->sortByDesc('created_at')
    ->take(10)
    ->map(fn($p) => [
        'title' => $p->title,
        'url' => site_url('posts/' . $p->slug),
        'views' => number_format($p->view_count)
    ])
    ->toArray();
```

## Real-World Examples

### E-commerce Order Summary

```php
$orders = Order::where('user_id', $userId)
    ->where('status', 'completed')
    ->collect();

$summary = [
    'total_orders' => $orders->count(),
    'total_spent' => $orders->sum('total'),
    'average_order' => $orders->avg('total'),
    'largest_order' => $orders->max('total'),
    'by_month' => $orders->groupBy(function($order) {
        return $order->created_at->format('Y-m');
    })->map(fn($group) => $group->sum('total'))
];
```

### User Analytics

```php
$users = User::all()->collect();

$analytics = [
    'total' => $users->count(),
    'active' => $users->where('active', 1)->count(),
    'by_role' => $users->groupBy('role')->map->count(),
    'by_country' => $users->groupBy('country')->map->count(),
    'average_age' => $users->avg('age'),
    'top_contributors' => $users->sortByDesc('contribution_score')->take(10)
];
```

### Product Catalog

```php
$products = Product::where('active', 1)->collect();

$catalog = $products
    ->groupBy('category_id')
    ->map(function($categoryProducts) {
        return [
            'count' => $categoryProducts->count(),
            'price_range' => [
                'min' => $categoryProducts->min('price'),
                'max' => $categoryProducts->max('price'),
                'avg' => $categoryProducts->avg('price')
            ],
            'products' => $categoryProducts->sortBy('name')->values()
        ];
    });
```

## Performance Considerations

::: tip Memory vs Speed
- **Small datasets (< 1000 records)**: Collections are perfect
- **Medium datasets (1000-10000)**: Use collections with chunk()
- **Large datasets (> 10000)**: Consider get_iterated() or chunk queries

```php
// GOOD: Small dataset
$users = User::limit(100)->collect()->filter(...);

// BETTER: Large dataset
User::chunk(1000, function($users) {
    $users->filter(...)->each(...);
});

// BEST: Huge dataset
$users = User::get_iterated();
foreach ($users as $user) {
    // Process one at a time
}
```
:::

## Converting Collections

### To Array

```php
$array = $collection->toArray();
$array = $collection->all();
```

### To JSON

```php
$json = $collection->toJson();
$json = $collection->toJson(JSON_PRETTY_PRINT);
```

### To Query Result

```php
// Get back to DataMapper result
$result = $collection->toDataMapper();
```

## Custom Collections

Create custom collection classes for your models:

```php
use DataMapper\Collection;

class UserCollection extends Collection {
    public function active() {
        return $this->filter(fn($user) => $user->active === 1);
    }
    
    public function admins() {
        return $this->where('role', 'admin');
    }
    
    public function sendEmail($subject, $message) {
        return $this->each(function($user) use ($subject, $message) {
            $user->sendEmail($subject, $message);
        });
    }
}

// Use in model
class User extends DataMapper {
    public function newCollection(array $models = []) {
        return new UserCollection($models);
    }
}

// Now your queries return UserCollection
$users = User::where('active', 1)->collect();
$users->admins()->sendEmail('Update', 'System update tonight');
```

## Related Documentation

- [Query Builder](query-builder)
- [Eager Loading](eager-loading)
- [Streaming & Chunking](streaming)
- [Get Iterated](../models/get-iterated)

## See Also

- [Query Basics](../models/get)
- [Advanced Queries](advanced-query-building)
- [Performance Tips](../../help/faq#Performance)