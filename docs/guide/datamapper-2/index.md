# What's New in DataMapper 2.0

DataMapper 2.0 brings modern PHP patterns and powerful new features while maintaining 100% backward compatibility with version 1.x.

## Overview

::: info Fully Backward Compatible
All your existing DataMapper 1.x code continues to work without any changes!
:::

DataMapper 2.0 focuses on three key areas:

1. **Developer Experience** - Modern, chainable syntax
2. **Performance** - Eager loading and caching
3. **Productivity** - Traits and collection methods

## Major Features

### ⚡ Native Query Builder

Write clean, chainable queries:

::: code-group

```php [Traditional (1.x)]
$user = new User();
$user->where('active', 1);
$user->where('age >', 18);
$user->order_by('created_at', 'DESC');
$user->limit(10);
$user->get();
```

```php [Query Builder (2.0) ✨]
$users = (new User())
    ->where('active', 1)
    ->where('age >', 18)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();
```

:::

[Learn More →](/guide/datamapper-2/query-builder)

---

### 🚀 Eager Loading with Constraints

Eliminate N+1 queries and optimize relationship loading:

```php
// Load users with their published posts
$users = (new User())
    ->with([
        'post' => function($q) {
            $q->where('published', 1)
              ->orderBy('views', 'DESC')
              ->limit(5);
        }
    ])
    ->get();

// Posts are already loaded - no extra queries!
foreach ($users as $user) {
    foreach ($user->post as $post) {
        echo $post->title;
    }
}
```

**Performance Impact:**
- Before: 101 queries (1 + 100 N+1)
- After: 2 queries
- **Improvement: 98% reduction!**

[Learn More →](/guide/datamapper-2/eager-loading)

---

### 📦 Collections

Work with query results using powerful collection methods:

```php
$users = (new User())
    ->where('active', 1)
    ->collect();

// Filter
$adults = $users->filter(fn($u) => $u->age >= 18);

// Map
$names = $users->map(fn($u) => $u->first_name . ' ' . $u->last_name);

// Pluck
$ids = $users->pluck('id');
$emails = $users->pluck('email');

// Aggregate
$totalCredits = $users->sum('credits');
$avgAge = $users->avg('age');

// First/Last
$first = $users->first();
$last = $users->last();
```

::: tip Migrating gradually
`get()` still returns the model instance (with `$this->all` populated) so legacy controllers keep working. When you're ready for the fluent API, swap `get()` for `collect()` or the other result helpers (`pluck()`, `value()`, `first()`) on a per-call basis.
:::

[Learn More →](/guide/datamapper-2/collections)

---

### ⚡ Query Caching

Cache expensive queries automatically:

```php
// Cache for 1 hour
$users = (new User())
    ->where('active', 1)
    ->cache(3600)
    ->get();

// Cache with custom key
$users = (new User())
    ->where('status', 'premium')
    ->cache(3600, 'premium_users')
    ->get();

// Clear cache
(new User())->clearCache('premium_users');
```

[Learn More →](/guide/datamapper-2/caching)

---

### 🗑️ Soft Deletes

Never lose data with soft delete support:

```php
use SoftDeletes;

class User extends DataMapper {
    use SoftDeletes;
}

// Soft delete (sets deleted_at timestamp)
$user = (new User())->find(1);
$user->delete();

// Query without deleted records (automatic)
$users = (new User())->get();

// Include deleted records
$allUsers = (new User())->with_softdeleted()->get();

// Only deleted records
$deleted = (new User())->only_softdeleted()->get();

// Restore soft-deleted record
$user->restore();

// Permanently delete
$user->force_delete();
```

[Learn More →](/guide/datamapper-2/soft-deletes)

---

### 🕐 Automatic Timestamps

Never manually manage created_at and updated_at again:

```php
use HasTimestamps;

class User extends DataMapper {
    use HasTimestamps;
}

// Automatically sets created_at
$user = new User();
$user->username = 'john';
$user->save();  // created_at = now()

// Automatically updates updated_at
$user->email = 'john@example.com';
$user->save();  // updated_at = now()
```

[Learn More →](/guide/datamapper-2/timestamps)

---

### 🔄 Attribute Casting

Automatically cast database values to proper PHP types:

```php
use AttributeCasting;

class User extends DataMapper {
    use AttributeCasting;
    
    protected $casts = [
        'id' => 'int',
        'active' => 'bool',
        'credits' => 'float',
        'settings' => 'json',
        'last_login' => 'datetime'
    ];
}

$user = (new User())->find(1);

// Automatic type casting
var_dump($user->active);     // bool(true)  not string "1"
var_dump($user->credits);    // float(99.99) not string "99.99"
var_dump($user->settings);   // array(...) not string "{...}"
var_dump($user->last_login); // DateTime object
```

[Learn More →](/guide/datamapper-2/casting)

---

### 📊 Streaming Results

Process massive datasets efficiently with generators:

```php
// Stream millions of records with minimal memory
(new User())->stream(function($user) {
    // Process each user
    echo $user->username . "\n";
    
    // Update user
    $user->last_processed = date('Y-m-d H:i:s');
    $user->save();
});

// Chunk processing
(new User())->chunk(1000, function($users) {
    foreach ($users as $user) {
        // Process batch of 1000 users
    }
});
```

[Learn More →](/guide/datamapper-2/streaming)

---

### 🔍 Advanced Query Building

Build complex queries with ease:

```php
// Subqueries
$users = (new User())
    ->whereIn('id', function($subquery) {
        $subquery->select('user_id')
                 ->from('orders')
                 ->where('total >', 1000);
    })
    ->get();

// Complex joins
$users = (new User())
    ->select('users.*, COUNT(posts.id) as post_count')
    ->join('posts', 'posts.user_id = users.id', 'left')
    ->groupBy('users.id')
    ->having('post_count >', 10)
    ->get();

// Conditional queries
$query = (new User())->where('active', 1);

if ($searchTerm) {
    $query->where('username LIKE', "%{$searchTerm}%");
}

if ($minAge) {
    $query->where('age >=', $minAge);
}

$users = $query->get();
```

[Learn More →](/guide/datamapper-2/advanced-query-building)

---

## Comparison Table

| Feature | DataMapper 1.x | DataMapper 2.0 |
|---------|----------------|----------------|
| **Syntax** | Traditional | Modern query builder + Traditional |
| **Eager Loading** | Basic | With constraints |
| **Related Columns** | `include_related()` flattening | `with()` + accessors/attributes |
| **Collections** | ❌ No | ✅ Yes |
| **Query Caching** | ❌ No | ✅ Built-in |
| **Soft Deletes** | Manual | Trait |
| **Timestamps** | Manual | Trait |
| **Type Casting** | Manual | Automatic |
| **Streaming** | ❌ No | ✅ Yes |
| **PHP Version** | 5.6 - 7.4 | 7.4 - 8.3+ |
| **Performance** | Good | Excellent |

## Legacy API Quick Reference

| If you used this in 1.x… | Use this in 2.0 | Why it’s better |
|--------------------------|-----------------|-----------------|
| `$user->include_related('company')` | `(new User())->with('company')` | Loads full related objects, supports constraints, fewer queries |
| `$user->include_related('company', 'name')` | Access via accessor/attribute on eager-loaded relation (`$user->company->name`) | Keeps data normalized, no column collisions |
| `$config['auto_populate_has_one'] = TRUE` | Keep auto-populate disabled and call `with()` only when needed | Prevents hidden N+1 queries, reduces memory usage |
| Manual JSON decoding (`json_decode($user->settings)`) | `AttributeCasting` trait with `$casts = ['settings' => 'json']` | Automatic hydration + serialization |
| Manual timestamp fields (`$user->created_at = date(...)`) | `HasTimestamps` trait | Ensures consistent timestamps |
| Custom logger wrappers (`DMZ_Logger::debug`) | `dmz_log_message('debug', ...)` (delegates to CI `log_message`) | Single logging pipeline, respects CI thresholds |

These replacements are additive—you can adopt them gradually while legacy code continues to run.

## Migration Path

You can adopt 2.0 features gradually:

### Phase 1: Drop-in Replacement
```php
// Just replace library files
// Everything works as before
$user = new User();
$user->get();
```

### Phase 2: Add Traits
```php
use HasTimestamps, SoftDeletes;

class User extends DataMapper {
    use HasTimestamps, SoftDeletes;
}
```

### Phase 3: Modern Query Builder Syntax
```php
// Start using the chainable query builder
$users = (new User())->where('active', 1)->get();
```

### Phase 4: Eager Loading
```php
// Optimize with eager loading
$users = (new User())->with('post')->get();
```

## Real-World Impact

### Before DataMapper 2.0

```php
// E-commerce: Get customers with recent orders
$customers = new Customer();
$customers->where('status', 'premium');
$customers->order_by('total_spent', 'DESC');
$customers->limit(50);
$customers->get();

// N+1 problem!
foreach ($customers as $customer) {
    $customer->order->where('created_at >', date('Y-m-d', strtotime('-30 days')));
    $customer->order->get();  // +1 query per customer!
    
    foreach ($customer->order as $order) {
        echo $order->total;
    }
}
// Total: 51 queries (1 + 50)
```

### After DataMapper 2.0

```php
// Same functionality, 96% fewer queries!
$customers = (new Customer())
    ->with([
        'order' => function($q) {
            $q->where('created_at >', date('Y-m-d', strtotime('-30 days')))
              ->orderBy('created_at', 'DESC');
        }
    ])
    ->where('status', 'premium')
    ->orderBy('total_spent', 'DESC')
    ->limit(50)
    ->cache(1800)  // Cache for 30 minutes
    ->get();

foreach ($customers as $customer) {
    foreach ($customer->order as $order) {
        echo $order->total;
    }
}
// Total: 2 queries!
```

## Getting Started

Ready to upgrade? Follow our guide:

1. [Requirements](/guide/getting-started/requirements) - Check compatibility
2. [Upgrading](/guide/getting-started/upgrading) - Step-by-step upgrade guide
3. [Query Builder](/guide/datamapper-2/query-builder) - Learn modern syntax

## Feature Deep Dives

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 2rem;">

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
    <h3>⚡ Query Builder</h3>
    <p>Modern, chainable query syntax</p>
    <a href="./query-builder">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>🚀 Eager Loading</h3>
  <p>Eliminate N+1 query problems</p>
  <a href="./eager-loading">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>📦 Collections</h3>
  <p>Powerful result manipulation</p>
  <a href="./collections">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>⚡ Caching</h3>
  <p>Automatic query result caching</p>
  <a href="./caching">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>🗑️ Soft Deletes</h3>
  <p>Safe data removal with restore</p>
  <a href="./soft-deletes">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>🕐 Timestamps</h3>
  <p>Automatic timestamp management</p>
  <a href="./timestamps">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>🔄 Type Casting</h3>
  <p>Automatic attribute type conversion</p>
  <a href="./casting">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>📊 Streaming</h3>
  <p>Handle massive datasets efficiently</p>
  <a href="./streaming">Learn More →</a>
</div>

</div>

---

::: tip Start Small
You don't need to adopt everything at once! Start with the modern query builder, then gradually add eager loading and other features as needed.
:::

::: info Questions?
Check our [FAQ](/help/faq) or [GitHub Discussions](https://github.com/P2GR/datamapper/discussions)
:::
