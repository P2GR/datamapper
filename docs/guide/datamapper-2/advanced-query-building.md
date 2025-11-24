# Advanced Query Building (DataMapper 2.0)

Master complex database queries with advanced techniques including subqueries, unions, raw expressions, query scopes, and dynamic conditions.

**New in DataMapper 2.0:** Enhanced query builder with support for complex SQL operations while maintaining the elegant DataMapper syntax.

## Table of Contents

- [Subqueries](#Subqueries)
- [Unions](#Unions)
- [Raw Expressions](#Raw-Expressions)
- [Query Scopes](#Query-Scopes)
- [Dynamic Conditions](#Dynamic-Conditions)
- [Advanced Joins](#Advanced-Joins)
- [Window Functions](#Window-Functions)
- [Common Table Expressions (CTEs)](#Common-Table-Expressions)

## Subqueries

Use subqueries for complex filtering and calculations.

### WHERE with Subquery

```php
// Find users who have made orders
$users = new User();
$users->where('id IN (SELECT user_id FROM orders WHERE total > 100)')->get();

// Using query builder
$subquery = new Order();
$subquery->select('user_id')->where('total >', 100);

$users = new User();
$users->where_in_subquery('id', $subquery)->get();
```

### SELECT with Subquery

```php
// Get users with order count
$users = new User();
$users->select('*, (SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id) as order_count')
      ->get();

foreach ($users as $user) {
    echo "{$user->username}: {$user->order_count} orders";
}
```

### FROM Subquery

```php
// Query from derived table
$users = new User();
$users->from('(SELECT * FROM users WHERE active = 1) as active_users')
      ->where('age >', 18)
      ->get();
```

### EXISTS Subquery

```php
// Find users with orders
$users = new User();
$users->where('EXISTS (SELECT 1 FROM orders WHERE orders.user_id = users.id)')
      ->get();

// Using helper
$users->where_exists('orders', 'user_id')->get();
```

## Unions

Combine results from multiple queries.

### Basic Union

```php
// Get all active users and admins
$activeUsers = new User();
$activeUsers->where('status', 'active')->select('id, username, email');

$admins = new User();
$admins->where('role', 'admin')->select('id, username, email');

$combined = $activeUsers->union($admins)->get();
```

### Union All

```php
// Include duplicates
$result = $query1->union_all($query2)->get();
```

### Multiple Unions

```php
$result = $query1
    ->union($query2)
    ->union($query3)
    ->union($query4)
    ->order_by('created_at', 'desc')
    ->get();
```

## Raw Expressions

Use raw SQL when needed while maintaining security.

### Raw SELECT

```php
$users = new User();
$users->select_func('CONCAT(first_name, " ", last_name)', 'full_name')
      ->select_func('YEAR(created_at)', 'signup_year')
      ->get();

foreach ($users as $user) {
    echo "{$user->full_name} (joined {$user->signup_year})";
}
```

### Raw WHERE

```php
// Complex WHERE conditions
$products = new Product();
$products->where('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)', array($search_term))
         ->get();

// Math operations
$orders = new Order();
$orders->where('total * 0.1 > ?', array(10)) // 10% > $10
       ->get();
```

### Raw JOIN

```php
$users = new User();
$users->query = "
    SELECT users.*, 
           COUNT(DISTINCT orders.id) as order_count,
           SUM(orders.total) as total_spent
    FROM users
    LEFT JOIN orders ON orders.user_id = users.id
    WHERE users.active = 1
    GROUP BY users.id
    HAVING total_spent > 1000
    ORDER BY total_spent DESC
";
$users->get();
```

## Query Scopes

Create reusable query components.

### Defining Scopes

```php
class User extends DataMapper {
    
    public function scope_active($query) {
        return $query->where('status', 'active');
    }
    
    public function scope_admin($query) {
        return $query->where('role', 'admin');
    }
    
    public function scope_recent($query, $days = 7) {
        $date = date('Y-m-d', strtotime("-$days days"));
        return $query->where('created_at >', $date);
    }
    
    public function scope_with_orders($query) {
        return $query->where('EXISTS (SELECT 1 FROM orders WHERE orders.user_id = users.id)');
    }
}
```

### Using Scopes

```php
// Single scope
$users = new User();
$users->active()->get();

// Chaining scopes
$users = new User();
$users->active()->admin()->recent(30)->get();

// With parameters
$users = new User();
$users->recent(14)->with_orders()->get();
```

### Global Scopes

Apply scopes to all queries automatically:

```php
class User extends DataMapper {
    
    protected function boot() {
        parent::boot();
        
        // Apply to all queries
        $this->addGlobalScope('active', function($query) {
            $query->where('deleted_at', NULL);
        });
    }
    
    public function withInactive() {
        $this->removeGlobalScope('active');
        return $this;
    }
}

// Usage
$users = new User();
$users->get(); // Only active users

$users = new User();
$users->withInactive()->get(); // All users
```

## Dynamic Conditions

Build queries dynamically based on runtime conditions.

### Conditional Clauses

```php
$users = new User();

// Add conditions based on input
if (!empty($filters['role'])) {
    $users->where('role', $filters['role']);
}

if (!empty($filters['min_age'])) {
    $users->where('age >=', $filters['min_age']);
}

if (!empty($filters['search'])) {
    $users->group_start()
          ->like('username', $filters['search'])
          ->or_like('email', $filters['search'])
          ->group_end();
}

$users->get();
```

### when() Helper

```php
class User extends DataMapper {
    
    public function when($condition, $callback) {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }
}

// Usage
$users = new User();
$users->when($request->has('role'), function($query) use ($request) {
          $query->where('role', $request->get('role'));
      })
      ->when($request->has('status'), function($query) use ($request) {
          $query->where('status', $request->get('status'));
      })
      ->get();
```

### Query Builder Pattern

```php
class UserQueryBuilder {
    private $query;
    
    public function __construct() {
        $this->query = new User();
    }
    
    public function filterByRole($role) {
        if ($role) {
            $this->query->where('role', $role);
        }
        return $this;
    }
    
    public function filterByStatus($status) {
        if ($status) {
            $this->query->where('status', $status);
        }
        return $this;
    }
    
    public function search($term) {
        if ($term) {
            $this->query->group_start()
                        ->like('username', $term)
                        ->or_like('email', $term)
                        ->group_end();
        }
        return $this;
    }
    
    public function sortBy($field, $direction = 'asc') {
        $this->query->order_by($field, $direction);
        return $this;
    }
    
    public function paginate($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $this->query->limit($perPage, $offset);
        return $this;
    }
    
    public function get() {
        return $this->query->get();
    }
}

// Usage
$builder = new UserQueryBuilder();
$users = $builder
    ->filterByRole($request->role)
    ->filterByStatus($request->status)
    ->search($request->search)
    ->sortBy($request->sort_by, $request->sort_dir)
    ->paginate($request->page, 20)
    ->get();
```

## Advanced Joins

Complex join operations beyond basic relationships.

### Self Join

```php
// Find users and their referrers
$users = new User();
$users->select('users.*, referrer.username as referred_by')
      ->join('users as referrer', 'referrer.id = users.referrer_id', 'left')
      ->get();
```

### Multiple Joins

```php
$posts = new Post();
$posts->select('posts.*, users.username, categories.name as category_name')
      ->join('users', 'users.id = posts.user_id')
      ->join('categories', 'categories.id = posts.category_id')
      ->where('posts.status', 'published')
      ->get();
```

### Conditional Joins

```php
// Join with additional conditions
$users = new User();
$users->join('orders', 'orders.user_id = users.id AND orders.status = "completed"', 'left')
      ->get();
```

### Subquery in JOIN

```php
$users = new User();
$users->select('users.*, order_stats.total')
      ->join('(SELECT user_id, SUM(total) as total FROM orders GROUP BY user_id) as order_stats',
             'order_stats.user_id = users.id',
             'left')
      ->get();
```

## Window Functions

Use window functions for advanced analytics (MySQL 8.0+, PostgreSQL).

### ROW_NUMBER()

```php
// Rank users by points
$users = new User();
$users->select('*, ROW_NUMBER() OVER (ORDER BY points DESC) as rank')
      ->get();

foreach ($users as $user) {
    echo "#{$user->rank}: {$user->username} ({$user->points} points)";
}
```

### RANK() and DENSE_RANK()

```php
// Rank with ties
$products = new Product();
$products->select('*, RANK() OVER (ORDER BY sales DESC) as sales_rank')
         ->get();
```

### Partitioning

```php
// Rank within categories
$products = new Product();
$products->select('*, 
                   ROW_NUMBER() OVER (PARTITION BY category_id ORDER BY price DESC) as rank_in_category')
         ->get();
```

### Running Totals

```php
// Calculate running total of sales
$orders = new Order();
$orders->select('*,
                 SUM(total) OVER (ORDER BY created_at) as running_total')
       ->order_by('created_at')
       ->get();
```

## Common Table Expressions (CTEs)

Use CTEs for readable complex queries (MySQL 8.0+, PostgreSQL).

### Basic CTE

```php
$users = new User();
$users->query = "
    WITH active_users AS (
        SELECT * FROM users WHERE status = 'active'
    )
    SELECT * FROM active_users WHERE age > 18
";
$users->get();
```

### Recursive CTE

```php
// Build category tree
$categories = new Category();
$categories->query = "
    WITH RECURSIVE category_tree AS (
        SELECT id, name, parent_id, 1 as level
        FROM categories
        WHERE parent_id IS NULL
        
        UNION ALL
        
        SELECT c.id, c.name, c.parent_id, ct.level + 1
        FROM categories c
        INNER JOIN category_tree ct ON c.parent_id = ct.id
    )
    SELECT * FROM category_tree ORDER BY level, name
";
$categories->get();
```

### Multiple CTEs

```php
$stats = new User();
$stats->query = "
    WITH 
    user_orders AS (
        SELECT user_id, COUNT(*) as order_count, SUM(total) as total_spent
        FROM orders
        GROUP BY user_id
    ),
    user_reviews AS (
        SELECT user_id, COUNT(*) as review_count, AVG(rating) as avg_rating
        FROM reviews
        GROUP BY user_id
    )
    SELECT 
        users.*,
        COALESCE(uo.order_count, 0) as orders,
        COALESCE(uo.total_spent, 0) as spent,
        COALESCE(ur.review_count, 0) as reviews,
        COALESCE(ur.avg_rating, 0) as rating
    FROM users
    LEFT JOIN user_orders uo ON uo.user_id = users.id
    LEFT JOIN user_reviews ur ON ur.user_id = users.id
";
$stats->get();
```

## Complex Real-World Examples

### E-commerce Analytics Dashboard

```php
class DashboardStats {
    
    public function getSalesAnalytics($start_date, $end_date) {
        $stats = new Order();
        $stats->query = "
            WITH daily_sales AS (
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as order_count,
                    SUM(total) as revenue,
                    AVG(total) as avg_order_value
                FROM orders
                WHERE created_at BETWEEN ? AND ?
                AND status = 'completed'
                GROUP BY DATE(created_at)
            ),
            product_sales AS (
                SELECT 
                    p.id,
                    p.name,
                    SUM(oi.quantity) as units_sold,
                    SUM(oi.quantity * oi.price) as revenue
                FROM products p
                JOIN order_items oi ON oi.product_id = p.id
                JOIN orders o ON o.id = oi.order_id
                WHERE o.created_at BETWEEN ? AND ?
                AND o.status = 'completed'
                GROUP BY p.id, p.name
                ORDER BY revenue DESC
                LIMIT 10
            )
            SELECT * FROM daily_sales
        ";
        
        $stats->query = str_replace('?', $this->db->escape($start_date), $stats->query, 1);
        $stats->query = str_replace('?', $this->db->escape($end_date), $stats->query, 1);
        
        return $stats->get();
    }
}
```

### User Engagement Scoring

```php
// Calculate user engagement score
$users = new User();
$users->query = "
    SELECT 
        users.*,
        (
            (SELECT COUNT(*) FROM posts WHERE posts.user_id = users.id) * 10 +
            (SELECT COUNT(*) FROM comments WHERE comments.user_id = users.id) * 5 +
            (SELECT COUNT(*) FROM likes WHERE likes.user_id = users.id) * 1
        ) as engagement_score,
        (SELECT MAX(created_at) FROM posts WHERE posts.user_id = users.id) as last_post_date
    FROM users
    WHERE status = 'active'
    HAVING engagement_score > 0
    ORDER BY engagement_score DESC
    LIMIT 100
";
$users->get();
```

### Cohort Analysis

```php
// Monthly cohort retention
$cohorts = new User();
$cohorts->query = "
    WITH user_cohorts AS (
        SELECT 
            id,
            DATE_FORMAT(created_at, '%Y-%m') as cohort_month,
            created_at
        FROM users
    ),
    cohort_activity AS (
        SELECT 
            uc.cohort_month,
            COUNT(DISTINCT uc.id) as cohort_size,
            COUNT(DISTINCT CASE 
                WHEN o.created_at >= DATE_ADD(uc.created_at, INTERVAL 1 MONTH)
                AND o.created_at < DATE_ADD(uc.created_at, INTERVAL 2 MONTH)
                THEN o.user_id 
            END) as month_1_active,
            COUNT(DISTINCT CASE 
                WHEN o.created_at >= DATE_ADD(uc.created_at, INTERVAL 2 MONTH)
                AND o.created_at < DATE_ADD(uc.created_at, INTERVAL 3 MONTH)
                THEN o.user_id 
            END) as month_2_active
        FROM user_cohorts uc
        LEFT JOIN orders o ON o.user_id = uc.id
        GROUP BY uc.cohort_month
    )
    SELECT 
        cohort_month,
        cohort_size,
        month_1_active,
        ROUND(month_1_active / cohort_size * 100, 2) as month_1_retention,
        month_2_active,
        ROUND(month_2_active / cohort_size * 100, 2) as month_2_retention
    FROM cohort_activity
    ORDER BY cohort_month DESC
";
$cohorts->get();
```

## Performance Tips

::: tip Optimization Strategies
1. **Use indexes** on columns in WHERE, JOIN, and ORDER BY
2. **Limit result sets** with LIMIT and pagination
3. **Avoid SELECT *** in complex queries - specify needed columns
4. **Use EXPLAIN** to analyze query performance
5. **Cache complex query results** when appropriate
6. **Consider materialized views** for frequently-run complex queries
:::

## Troubleshooting

### Debug Complex Queries

```php
// View the generated SQL
$users = new User();
$users->where('status', 'active')->order_by('created_at', 'desc');

// Before get()
echo $users->check_last_query();

// After get()
echo $users->last_query;
```

### Query Profiling

```php
$start = microtime(true);

$users = new User();
$users->complex_query()->get();

$duration = microtime(true) - $start;
log_message('debug', "Query took {$duration} seconds");
```

## Related Documentation

- [Subqueries](../advanced/subqueries)
- [Joins](../advanced/joins)
- [Query Optimization](../../help/faq#Performance)
- [Database Indexing](../../help/troubleshooting#Performance)

## See Also

- [Basic Queries](../models/get)
- [Advanced Get](../models/get-advanced)
- [Collections](collections)
- [Eager Loading](eager-loading)