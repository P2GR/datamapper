# Debugging & Benchmarking

DataMapper 2.0 includes built-in tools to inspect queries and measure performance.

## Quick Debug

After running a query, call `debug()` to see what happened:

```php
$user = new User();
$user->where('active', 1)->get();

// Get debug info as array
$info = $user->debug();

// Or pretty-print to screen
$user->debug(FALSE);
```

**Returns:**
- `model` — Class name
- `table` — Database table
- `sql` — The raw SQL query
- `result_count` — Number of rows returned
- `time` — Execution time in seconds
- `time_formatted` — Human-readable time (e.g., "2.45 ms")

## Benchmarking

Use `benchmark()` for comprehensive profiling:

```php
$user = new User();
$user->with('posts')->where('active', 1)->get();

// Get benchmark data as array
$report = $user->benchmark();

// Or pretty-print with color-coded output
$user->benchmark(FALSE);
```

**Returns:**
- `total_queries` — Number of queries executed
- `total_time` / `total_time_formatted` — Combined execution time
- `average_time` / `average_time_formatted` — Average per query
- `memory` / `memory_formatted` — Current memory usage
- `peak_memory` / `peak_memory_formatted` — Peak memory usage
- `queries` — Array of individual query details

## Measuring Specific Operations

To benchmark only your query (excluding earlier queries), use `get_query_index()`:

```php
$user = new User();

// Mark the starting point
$start = $user->get_query_index();

// Run your queries
$user->with('posts', 'comments')->where('status', 'active')->get();

// Benchmark only the queries since $start
$user->benchmark(FALSE, $start);
```

## With Query Builder

Works the same way with the query builder:

```php
$builder = (new User())->query();
$builder->with('posts')->where('active', 1)->get();

$builder->debug(FALSE);     // Shows eager loads too
$builder->benchmark(FALSE);
```

## Get Raw SQL

To see the SQL without executing:

```php
$user = new User();
$sql = $user->where('active', 1)->get_sql();
echo $sql;

// With QueryBuilder
$builder = (new User())->query()->where('active', 1);
echo $builder->to_sql();
```

## Output Colors

When using `benchmark(FALSE)`, query times are color-coded:

| Color | Meaning |
|-------|---------|
| 🟢 Green | Fast (< 10ms) |
| 🟡 Yellow | Moderate (10-100ms) |
| 🔴 Red | Slow (> 100ms) |

## Example Output

```
Query Debug Information
─────────────────────────

Model:       User
Table:       users
Results:     42 row(s)
Time:        1.23 ms

SQL:
SELECT * FROM `users` WHERE `active` = 1
```

```
Query Benchmark Report
─────────────────────────

Summary
  Total Queries:  3
  Total Time:     4.56 ms
  Average Time:   1.52 ms
  Memory:         2.5 MB
  Peak Memory:    4.0 MB

Queries
  [0] 1.20 ms SELECT * FROM `users` WHERE `active` = 1
  [1] 2.10 ms SELECT * FROM `posts` WHERE `user_id` IN (1, 2, 3...)
  [2] 1.26 ms SELECT * FROM `comments` WHERE `post_id` IN (...)
```
