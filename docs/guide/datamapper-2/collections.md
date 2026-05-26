# Collections (DataMapper 2.0)

`DMZ_Collection` wraps a set of models or arrays and gives you a predictable set of filtering, mapping, sorting, grouping, and aggregate helpers. Classic `get()` still returns the model instance with `$object->all` populated; use `collect()` when you want a collection object.

## Getting a Collection

### From a Query

```php
$posts = new Post();
$posts->where('status', 'published')->get();

$collection = $posts->collect();
```

Pass a limit to `collect()` when the query has not been executed yet.

```php
$posts = new Post();
$collection = $posts->where('status', 'published')
                    ->collect(10);
```

The query-builder wrapper also has `collect()` as a terminal method.

```php
$posts = (new Post())
    ->with('author')
    ->where('status', 'published')
    ->collect();
```

### From an Array

```php
$collection = new DMZ_Collection(array($user1, $user2, $user3));
```

## Result Helpers on Models

For common read patterns you can use the model-level helpers directly:

```php
$emails = (new User())
    ->where('active', 1)
    ->pluck('email');

$latestSlug = (new Post())
    ->order_by('created_at', 'DESC')
    ->value('slug', 'draft');

$firstAdmin = (new User())
    ->where('role', 'admin')
    ->first();
```

Use `collect()->pluck()` when you need a collection chain before plucking. `pluck()` itself returns a plain PHP array.

## Reading Items

```php
$users = (new User())->where('active', 1)->collect();

$count = $users->count();
$first = $users->first();
$last = $users->last();
$isEmpty = $users->is_empty();
$hasItems = $users->is_not_empty();
$fifth = $users->get(4);
$byId = $users->find(15);
$ids = $users->ids();
```

Collections implement `IteratorAggregate` and `Countable`, so they work naturally in `foreach` and `count()`.

```php
foreach ($users as $user) {
    echo $user->name;
}
```

## Filtering

```php
$users = (new User())->collect();

$admins = $users->where('role', 'admin');
$adults = $users->where('age', '>=', 18);
$selected = $users->where_in('id', array(1, 5, 10));
$visible = $users->where_not_in('status', array('banned', 'deleted'));
$pending = $users->where_null('approved_at');
$approved = $users->where_not_null('approved_at');
$midRange = $users->where_between('score', 50, 80);

$recent = $users->filter(function($user) {
    return $user->created_at >= '2026-01-01';
});
```

`where_between()` also accepts an array with start and end values.

```php
$midRange = $users->where_between('score', array(50, 80));
```

## Mapping and Iterating

```php
$names = $users->map(function($user) {
    return $user->first_name . ' ' . $user->last_name;
});

$users->each(function($user) {
    $user->last_seen = date('Y-m-d H:i:s');
    $user->save();
});
```

Return `FALSE` from `each()` to stop early.

```php
$users->each(function($user) {
    if ($user->id > 1000) {
        return FALSE;
    }
});
```

Use `flat_map()` when each item returns an array or collection that should be flattened into one collection.

```php
$tags = $posts->flat_map(function($post) {
    return $post->tags;
});
```

## Plucking and Values

```php
$emails = $users->pluck('email');
$items = $users->values();
$array = $users->to_array();
$json = $users->to_json();
```

## Aggregates

```php
$orders = (new Order())->where('status', 'paid')->collect();

$total = $orders->sum('total');
$average = $orders->avg('total');
$min = $orders->min('total');
$max = $orders->max('total');
$median = $orders->median('total');
$mode = $orders->mode('status');
```

Aggregate helpers accept a field name or a callback.

```php
$gross = $orders->sum(function($order) {
    return $order->quantity * $order->price;
});
```

## Sorting and Grouping

```php
$byName = $users->sort_by('name');
$byScore = $users->sort_by_desc('score');
$reversed = $users->reverse();
$random = $users->shuffle();
$uniqueEmails = $users->unique('email');

$byRole = $users->group_by('role');
$byId = $users->key_by('id');
```

`group_by()` returns a collection whose values are `DMZ_Collection` instances.

```php
$totalsByUser = $orders->group_by('user_id')->map(function($group) {
    return $group->sum('total');
});
```

## Combining Collections

```php
$all = $active->merge($inactive);
$combined = $active->concat($invited);
$unique = $active->union($invited);
$pairs = $names->zip($emails);
```

## Slicing

```php
$firstTen = $users->take(10);
$afterTen = $users->skip(10);
$chunks = $users->chunk(100);
$groups = $users->split(3);
```

`chunk()` is an in-memory collection operation. For large tables, use the model-level streaming methods instead.

```php
$users = new User();
$users->where('active', 1)
      ->chunk(1000, function($chunk) {
          $chunk->each(function($user) {
              $user->sync_profile();
          });
      });
```

## Checks

```php
$hasAdmin = $users->contains('admin', 'role');

$allActive = $users->every(function($user) {
    return $user->active == 1;
});

$hasLockedUser = $users->some(function($user) {
    return $user->locked_at !== NULL;
});
```

## Bulk Operations

Collections that contain DataMapper models can persist or delete every item.

```php
$users->each(function($user) {
    $user->status = 'archived';
});

$users->save_all();
$users->delete_all();
```

## Converting Back to DataMapper

```php
$result = $users->to_data_mapper();
```

`to_data_mapper()` rebuilds a DataMapper result object when the collection was created with a source model.

## Notes

- `get()` returns a DataMapper model instance; `collect()` returns `DMZ_Collection`.
- `pluck()` returns a plain array.
- Collection helpers operate on already loaded data.
- Model-level `chunk()`, `chunk_by_id()`, `cursor()`, and `lazy()` fetch data in batches and are better suited to large tables.

## See Also

- [Query Builder](query-builder)
- [Streaming & Chunking](streaming)
- [Quick Reference](/reference/quick-reference)