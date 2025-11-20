# Get Iterated <Badge type="tip" text="performance" />

When working with large datasets, loading all records into memory at once can be inefficient. The `get_iterated()` method provides a memory-efficient way to process large result sets by loading one record at a time.

## Basic Usage

Instead of loading all results into memory:

::: code-group

```php [get_iterated() - Memory Efficient]
$user = new User();
$user->get_iterated();

// Each iteration loads ONE record at a time
foreach ($user as $u) {
    echo $u->name . '<br/>';
}
// Low memory usage
```

```php [get() - Loads All]
$user = new User();
$user->get();

// All records loaded into memory at once
foreach ($user as $u) {
    echo $u->name . '<br/>';
}
// High memory usage with large datasets
```

:::

## When to Use get_iterated()

### Use get_iterated() When:

- Processing **thousands of records**
- Memory usage is a concern
- You only need to **loop through results once**
- Performing batch operations (exports, migrations, reports)
- Processing records sequentially

### Avoid get_iterated() When:

- Working with small result sets (< 100 records)
- You need random access to results (`$user->all[5]`)
- You need to count results before processing (`$user->result_count()`)
- You'll iterate multiple times over the same data

## Performance Comparison

```php
// Scenario: Processing 10,000 user records

// Traditional get() - Loads all at once
$user = new User();
$user->get();
// Memory: ~50MB (all 10,000 records)
// Time: Fast iteration

// get_iterated() - Loads one at a time
$user = new User();
$user->get_iterated();
// Memory: ~5KB per record (~5KB total)
// Time: Slightly slower iteration, but much lower memory
```

::: tip Memory Savings
For 10,000 records, `get_iterated()` can reduce memory usage by **90-99%** compared to regular `get()`.
:::

## Complete Example

### CSV Export with get_iterated()

```php
<?php
class Report extends CI_Controller {
    
    function export_users()
    {
        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write CSV header
        fputcsv($output, array('ID', 'Name', 'Email', 'Created'));
        
        // Process users one at a time
        $user = new User();
        $user->order_by('created_at', 'asc');
        $user->get_iterated();
        
        foreach ($user as $u) {
            fputcsv($output, array(
                $u->id,
                $u->name,
                $u->email,
                $u->created_at
            ));
        }
        
        fclose($output);
    }
}
```

### Batch Processing Example

```php
function process_inactive_users()
{
    $user = new User();
    $user->where('last_login <', date('Y-m-d', strtotime('-1 year')));
    $user->get_iterated();
    
    $count = 0;
    
    foreach ($user as $u) {
        // Send notification email
        $this->email->to($u->email);
        $this->email->subject('Account Inactive');
        $this->email->send();
        
        $count++;
        
        // Prevent timeout on large datasets
        if ($count % 100 == 0) {
            sleep(1); // Brief pause every 100 emails
        }
    }
    
    echo "Processed {$count} inactive users";
}
```

## DataMapper 2.0 Streaming <Badge type="tip" text="2.0" />

For even more advanced streaming capabilities, check out the new [Streaming Results](/guide/datamapper-2/streaming) feature in DataMapper 2.0:

```php
use DataMapper\Streaming;

$user = new User();
$user->where('status', 'active')
     ->stream()
     ->chunk(100, function($users) {
         foreach ($users as $user) {
             // Process in chunks of 100
         }
     });
```

## Limitations

### No Direct Array Access

```php
$user = new User();
$user->get_iterated();

// Does not support direct array access
echo $user->all[0]->name;
echo $user->all[5]->email;

// Use foreach iteration instead
foreach ($user as $u) {
    echo $u->name;
}
```

### No Result Count Before Iteration

```php
$user = new User();
$user->get_iterated();

// Count not available until after iteration
echo $user->result_count(); // Returns 0

// Use get() if you need count first
$user = new User();
$user->get();
echo $user->result_count(); // Returns actual count
```

### Single Iteration Only

```php
$user = new User();
$user->get_iterated();

// First iteration - works fine
foreach ($user as $u) {
    echo $u->name;
}

// Second iteration returns no results
foreach ($user as $u) {
    // Won't execute - iterator already exhausted
}

// Call get_iterated() again for another iteration
$user->get_iterated();
foreach ($user as $u) {
    echo $u->name; // Works
}
```

## With Query Methods

`get_iterated()` works with all standard query methods:

```php
$user = new User();
$user->where('status', 'active')
     ->where('role', 'admin')
     ->order_by('created_at', 'desc')
     ->limit(1000)
     ->get_iterated();

foreach ($user as $u) {
    // Process each admin user
}
```

## With Relationships

```php
// Load users with their country relationship
$user = new User();
$user->include_related('country')
     ->get_iterated();

foreach ($user as $u) {
    echo $u->name . ' - ' . $u->country_name . '<br/>';
}
```

::: warning N+1 with Iterated
Be cautious with relationships when using `get_iterated()`. Consider using DataMapper 2.0's [Eager Loading](/guide/datamapper-2/eager-loading) instead:

```php
$user = new User();
$user->with('country')  // Eager load to prevent N+1
     ->get();

foreach ($user as $u) {
    echo $u->country->name;
}
```
:::

## Best Practices

### 1. Use for Large Datasets Only

```php
// Overkill for small datasets
$user = new User();
$user->limit(10)
     ->get_iterated(); // Unnecessary for 10 records

// Good for large datasets
$user = new User();
$user->where('created_at >', '2020-01-01')
     ->get_iterated(); // Potentially thousands of records
```

### 2. Process and Discard Pattern

```php
$order = new Order();
$order->where('status', 'completed')
      ->where('exported', 0)
      ->get_iterated();

foreach ($order as $o) {
    // Export to accounting system
    $this->accounting->export($o);
    
    // Mark as exported
    $o->exported = 1;
    $o->save();
    
    // Record is immediately discarded from memory
}
```

### 3. Monitor Progress

```php
function migrate_old_data()
{
    $legacy = new LegacyUser();
    $legacy->get_iterated();
    
    $total = 0;
    $success = 0;
    
    foreach ($legacy as $old_user) {
        $total++;
        
        // Migrate to new format
        $new_user = new User();
        $new_user->name = $old_user->full_name;
        $new_user->email = $old_user->email_address;
        
        if ($new_user->save()) {
            $success++;
        }
        
        // Progress update every 100 records
        if ($total % 100 == 0) {
            echo "Processed {$total} records ({$success} successful)<br/>";
            flush();
        }
    }
    
    echo "Migration complete: {$success}/{$total} successful";
}
```

## Comparison Table

| Feature | get() | get_iterated() |
|---------|-------|----------------|
| Memory Usage | High (all records) | Low (one at a time) |
| Iteration Speed | Fast | Slightly slower |
| Array Access | Yes (`$user->all[0]`) | No |
| Count Available | Yes (immediate) | No (until after) |
| Multiple Iterations | Yes | No (need re-query) |
| Random Access | Yes | No |
| Best For | < 1000 records | > 1000 records |
| Use Case | General queries | Batch processing |

## See Also

- [Get Methods](/guide/models/get) - Standard data retrieval
- [Streaming Results](/guide/datamapper-2/streaming) - Advanced streaming (2.0)
- [Collections](/guide/datamapper-2/collections) - Working with result sets (2.0)
- [Query Caching](/guide/datamapper-2/caching) - Speed up repeated queries
