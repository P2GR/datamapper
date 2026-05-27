# Local Query Scopes <Badge type="tip" text="2.0" />

Encapsulate reusable query constraints inside your model. Instead of repeating the same `where()` calls across controllers, define a scope once and call it like a first-class method.

## Why Scopes?

- **DRY** – write common filters once, reuse everywhere.
- **Readable** – `$user->active()` is clearer than `$user->where('active', 1)`.
- **Chainable** – scopes return the model, so you can chain them with other query methods.
- **Encapsulated** – query logic lives in the model, not scattered across controllers.

## Quick Start

### 1. Define a Scope

Prefix the method name with `scope_`:

```php
class User extends DataMapper {

    public function scope_active()
    {
        return $this->where('active', 1);
    }

    public function scope_admins()
    {
        return $this->where('role', 'admin');
    }
}
```

### 2. Use the Scope

Call the scope **without** the `scope_` prefix:

```php
$users = new User();
$users->active()->get();

// Equivalent to:
$users = new User();
$users->where('active', 1)->get();
```

## Scopes with Parameters

Pass arguments to scope methods for dynamic filtering:

```php
class Post extends DataMapper {

    public function scope_of_status($status)
    {
        return $this->where('status', $status);
    }

    public function scope_popular($min_views = 1000)
    {
        return $this->where('views >', $min_views);
    }

    public function scope_recent($days = 7)
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->where('created_at >', $cutoff);
    }
}
```

```php
// Published posts
$posts = new Post();
$posts->of_status('published')->get();

// Popular posts (custom threshold)
$posts = new Post();
$posts->popular(5000)->get();

// Recent posts (last 30 days)
$posts = new Post();
$posts->recent(30)->get();
```

## Chaining Scopes

Scopes return `$this`, so they chain naturally with other scopes and query methods:

```php
$posts = new Post();
$posts->of_status('published')
      ->popular(500)
      ->recent(14)
      ->order_by('views', 'desc')
      ->limit(10)
      ->get();
```

This builds the query:

```sql
SELECT * FROM posts
WHERE status = 'published'
  AND views > 500
  AND created_at > '2024-01-01 00:00:00'
ORDER BY views DESC
LIMIT 10
```

## Real-World Examples

### User Model

```php
class User extends DataMapper {

    public function scope_active()
    {
        return $this->where('active', 1);
    }

    public function scope_verified()
    {
        return $this->where_not_null('email_verified_at');
    }

    public function scope_with_role($role)
    {
        return $this->where('role', $role);
    }

    public function scope_created_after($date)
    {
        return $this->where('created_at >', $date);
    }

    public function scope_search($term)
    {
        return $this->group_start()
                    ->like('name', $term)
                    ->or_like('email', $term)
                ->group_end();
    }
}
```

```php
// Active, verified admins
$admins = new User();
$admins->active()->verified()->with_role('admin')->get();

// Search active users
$results = new User();
$results->active()->search($this->input->get('q'))->get();
```

### Order Model

```php
class Order extends DataMapper {

    public function scope_pending()
    {
        return $this->where('status', 'pending');
    }

    public function scope_completed()
    {
        return $this->where('status', 'completed');
    }

    public function scope_high_value($min = 100)
    {
        return $this->where('total >', $min);
    }

    public function scope_placed_between($start, $end)
    {
        return $this->where('created_at >=', $start)
                    ->where('created_at <=', $end);
    }
}
```

```php
// High-value pending orders from this month
$orders = new Order();
$orders->pending()
       ->high_value(500)
       ->placed_between('2024-01-01', '2024-01-31')
       ->order_by('total', 'desc')
       ->get();
```

### Content Management

```php
class Article extends DataMapper {

    public function scope_published()
    {
        return $this->where('status', 'published')
                    ->where('published_at <=', date('Y-m-d H:i:s'));
    }

    public function scope_draft()
    {
        return $this->where('status', 'draft');
    }

    public function scope_by_category($category_id)
    {
        return $this->where('category_id', $category_id);
    }

    public function scope_featured()
    {
        return $this->where('is_featured', 1);
    }
}
```

```php
// Featured published articles in a category
$articles = new Article();
$articles->published()
         ->featured()
         ->by_category(3)
         ->order_by('published_at', 'desc')
         ->limit(5)
         ->get();
```

## How It Works

When you call a method that doesn't exist on the model, DataMapper's `__call()` magic method checks whether a `scope_` prefixed version exists. If `scope_active()` is defined, calling `$model->active()` invokes `scope_active()` and returns the result for chaining.

```
$model->active()
  → __call('active', [])
  → method_exists($this, 'scope_active') ? YES
  → $this->scope_active()
  → returns $this (with where clause applied)
```

::: tip Naming Convention
Use **snake_case** for scope names. The scope `scope_of_type` is called as `$model->of_type()`. CamelCase scope names work too, but snake_case is the DataMapper convention.
:::

::: tip Backward Compatible
Scopes are completely opt-in. They use the existing `__call()` mechanism and do not affect models that don't define any `scope_` methods. Existing method names take priority over scope resolution.
:::

## See Also

- [Query Builder](/guide/datamapper-2/query-builder) – the query methods available inside scopes
- [Advanced Queries](/guide/datamapper-2/advanced-query-building) – subqueries and joins
- [Collections](/guide/datamapper-2/collections) – process scope results with collection methods
