# Advanced Query Building (DataMapper 2.0)

DataMapper 2.0 adds modern query helpers while keeping the classic CodeIgniter/DataMapper syntax. This page focuses on features that are implemented in the current library: subquery helpers, relationship existence filters, conditional clauses, aggregate helpers, ordering shortcuts, and reusable model methods.

## Table of Contents

- [Subqueries](#subqueries)
- [Raw Expressions](#raw-expressions)
- [Relationship Filters](#relationship-filters)
- [Dynamic Conditions](#dynamic-conditions)
- [Ordering and Pagination](#ordering-and-pagination)
- [Aggregates](#aggregates)
- [Reusable Model Methods](#reusable-model-methods)
- [Notes on Scopes](#notes-on-scopes)

## Subqueries

Use subqueries when a filter depends on another table or a computed result.

### WHERE with a Raw Subquery

```php
$users = new User();
$users
    ->where('id IN (SELECT user_id FROM orders WHERE total > 100)')
    ->get();
```

### WHERE IN with a DataMapper Subquery

```php
$orders = new Order();
$orders->select('user_id')->where('total >', 100);

$users = new User();
$users
    ->where_in_subquery('id', $orders)
    ->get();
```

### SELECT with a Subquery

```php
$users = new User();
$users
    ->select('users.*, (SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id) AS order_count')
    ->get();

foreach ($users as $user) {
    echo $user->username . ': ' . $user->order_count;
}
```

For very custom SQL, use `query($sql, $binds)` so values can still be bound safely.

```php
$users = new User();
$users->query(
    'SELECT * FROM users WHERE active = ? AND created_at >= ?',
    array(1, '2026-01-01')
);
```

## Raw Expressions

Prefer DataMapper and CodeIgniter query methods first. When SQL functions are clearer, use the function helpers.

```php
$users = new User();
$users
    ->select_func('CONCAT', '@first_name', "' '", '@last_name', 'full_name')
    ->select_func('YEAR', '@created_at', 'signup_year')
    ->get();
```

Raw `where()` strings are available for database-specific predicates.

```php
$products = new Product();
$products
    ->where('MATCH(name, description) AGAINST(' . $products->db->escape($search) . ' IN BOOLEAN MODE)')
    ->get();
```

## Relationship Filters

Use relationship filters when you need parent rows based on related records.

```php
$users = (new User())
    ->where_related('profile', 'verified', 1)
    ->get();
```

`has()`, `where_has()`, and `where_doesnt_have()` provide Eloquent-style existence checks in snake_case.

```php
$users = (new User())
    ->has('post', '>=', 3)
    ->where_has('post', function ($posts) {
        $posts->where('status', 'published');
    })
    ->get();

$quietUsers = (new User())
    ->where_doesnt_have('comment')
    ->get();
```

## Dynamic Conditions

Build query clauses only when input is present.

```php
$users = (new User())
    ->when($role, function ($query, $role) {
        return $query->where('role', $role);
    })
    ->when($search, function ($query, $search) {
        return $query
            ->group_start()
                ->like('username', $search)
                ->or_like('email', $search)
            ->group_end();
    })
    ->unless($includeInactive, function ($query) {
        return $query->where('active', 1);
    })
    ->get();
```

Both `when()` and `unless()` accept an optional fallback callback.

```php
$users = (new User())
    ->when($sort, function ($query, $sort) {
        return $query->order_by($sort, 'ASC');
    }, function ($query) {
        return $query->latest();
    })
    ->get();
```

Use `first_where()` for a compact lookup.

```php
$admin = (new User())->first_where('role', 'admin');
```

## Ordering and Pagination

The classic methods still work, and DataMapper 2.0 adds aliases that match common builder terminology.

```php
$users = (new User())
    ->order_by_desc('score')
    ->latest()
    ->take(20)
    ->skip(40)
    ->get();
```

`latest()` and `oldest()` default to the model's created-at column. Pass a field when you want a different column.

```php
$oldestUpdated = (new User())
    ->oldest('updated_at')
    ->first();
```

## Aggregates

Aggregate helpers return scalar values without requiring manual `select_*()` calls.

```php
$total = (new Order())->where('status', 'paid')->sum('total');
$avg   = (new Product())->avg('price');
$min   = (new Product())->min('price');
$max   = (new Product())->max('price');
```

`average()` is an alias for `avg()`.

## Reusable Model Methods

For reusable constraints, define normal snake_case methods on the model and return `$this`. This keeps the API explicit and backward compatible.

```php
class User extends DataMapper {
    public function active()
    {
        return $this->where('status', 'active');
    }

    public function admins()
    {
        return $this->where('role', 'admin');
    }

    public function recent($days = 7)
    {
        return $this->where('created_at >', date('Y-m-d', strtotime('-' . (int) $days . ' days')));
    }
}

$users = (new User())
    ->active()
    ->admins()
    ->recent(30)
    ->get();
```

For larger filters, wrap the model in a small query object.

```php
class UserSearch {
    protected $query;

    public function __construct(User $query = NULL)
    {
        $this->query = $query ?: new User();
    }

    public function apply(array $filters)
    {
        return $this->query
            ->when(isset($filters['role']) ? $filters['role'] : NULL, function ($query, $role) {
                return $query->where('role', $role);
            })
            ->when(isset($filters['status']) ? $filters['status'] : NULL, function ($query, $status) {
                return $query->where('status', $status);
            });
    }
}

$users = (new UserSearch())->apply($filters)->get();
```

## Notes on Scopes

Laravel-style `scope_active()` discovery and `add_global_scope()` are not part of the current DataMapper 2.0 implementation. Use explicit model methods, `when()`/`unless()`, soft-delete helpers, or eager-loading constraints instead.

## See Also

- [Query Builder](query-builder) - Core builder and collection helpers
- [Eager Loading](eager-loading) - Relationship eager loading and constraints
- [Mass Assignment](/guide/models/mass-assignment) - `fill()`, `create()`, and guarding
