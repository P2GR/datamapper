# Joins

Master SQL JOIN operations in DataMapper ORM for complex queries across multiple tables.

## Basic Joins

### Inner Join

```php
$posts = new Post();
$posts->select('posts.*, users.username')
      ->join('users', 'users.id = posts.user_id')
      ->get();

foreach ($posts as $post) {
    echo "{$post->title} by {$post->username}";
}
```

### Left Join

```php
// Get all posts, include user info if available
$posts = new Post();
$posts->select('posts.*, users.username')
      ->join('users', 'users.id = posts.user_id', 'left')
      ->get();
```

### Right Join

```php
$posts = new Post();
$posts->join('users', 'users.id = posts.user_id', 'right')->get();
```

### Full Outer Join

```php
// PostgreSQL
$posts = new Post();
$posts->join('users', 'users.id = posts.user_id', 'full outer')->get();
```

## Advanced Join Patterns

### Multiple Joins

```php
$posts = new Post();
$posts->select('posts.*, users.username, categories.name as category_name')
      ->join('users', 'users.id = posts.user_id')
      ->join('categories', 'categories.id = posts.category_id')
      ->where('posts.status', 'published')
      ->order_by('posts.created_at', 'desc')
      ->get();
```

### Self Join

```php
// Find posts and their parent posts
$posts = new Post();
$posts->select('posts.*, parent.title as parent_title')
      ->join('posts as parent', 'parent.id = posts.parent_id', 'left')
      ->get();
```

### Join with Conditions

```php
// Join with additional WHERE conditions
$users = new User();
$users->join('orders', 'orders.user_id = users.id AND orders.status = "completed"', 'left')
      ->select('users.*, COUNT(orders.id) as order_count')
      ->group_by('users.id')
      ->get();
```

### Subquery in Join

```php
$users = new User();
$users->select('users.*, order_stats.total')
      ->join('(SELECT user_id, SUM(total) as total FROM orders GROUP BY user_id) as order_stats',
             'order_stats.user_id = users.id',
             'left')
      ->get();
```

## Join with Relationships

DataMapper automatically handles joins for relationships:

```php
// Automatic join through include_related
$posts = new Post();
$posts->include_related('user')
      ->include_related('category')
      ->where('status', 'published')
      ->get();

// Access related data without additional queries
foreach ($posts as $post) {
    echo "{$post->title} by {$post->user_username}";
    echo "Category: {$post->category_name}";
}
```

## Complex Join Examples

### E-commerce Order Summary

```php
$orders = new Order();
$orders->select('
        orders.*,
        users.username,
        users.email,
        COUNT(DISTINCT order_items.id) as item_count,
        SUM(order_items.quantity * order_items.price) as calculated_total
    ')
    ->join('users', 'users.id = orders.user_id')
    ->join('order_items', 'order_items.order_id = orders.id')
    ->where('orders.status', 'completed')
    ->group_by('orders.id')
    ->having('calculated_total >', 100)
    ->order_by('orders.created_at', 'desc')
    ->get();
```

### User Activity Report

```php
$users = new User();
$users->select('
        users.*,
        COUNT(DISTINCT posts.id) as post_count,
        COUNT(DISTINCT comments.id) as comment_count,
        MAX(posts.created_at) as last_post_date
    ')
    ->join('posts', 'posts.user_id = users.id', 'left')
    ->join('comments', 'comments.user_id = users.id', 'left')
    ->where('users.active', 1)
    ->group_by('users.id')
    ->order_by('post_count', 'desc')
    ->get();
```

## Performance Tips

::: tip Optimization
- **Index join columns** for better performance
- **Limit SELECT fields** - avoid SELECT *
- **Use EXPLAIN** to analyze query execution
- **Consider eager loading** instead of manual joins when working with DataMapper relationships
:::

## Related Documentation

- [Advanced Query Building](../datamapper-2/advanced-query-building)
- [Relationships](../relationships/)
- [Subqueries](/guide/advanced/subqueries)

## See Also

- [Get Advanced](../models/get-advanced)
- [Include Related](../relationships/accessing)
