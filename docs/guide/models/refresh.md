# Refresh

Reload a DataMapper object's data from the database. Perfect for getting the latest data after external changes or long-running processes.

## Basic Usage

```php
$user = new User();
$user->get_by_id(1);

// ... some time passes, another process might have updated the record ...

$user->refresh();  // Reload fresh data from database
```

## Method Signature

```php
$object->refresh()
```

## Return Value

Returns the object itself for method chaining.

## Examples

### Simple Refresh

```php
$user = new User();
$user->get_by_id(1);

echo $user->email;  // "old@example.com"

// Another process updates the email in the database

$user->refresh();

echo $user->email;  // "new@example.com" (fresh from database)
```

### Discard Local Changes

```php
$product = new Product();
$product->get_by_id(5);

// Make changes in memory
$product->price = 999.99;
$product->name = "Changed Name";

echo $product->price;  // 999.99 (modified)

// Discard changes by refreshing
$product->refresh();

echo $product->price;  // 49.99 (original value from database)
```

### After Failed Save

```php
$user = new User();
$user->get_by_id(1);

$user->email = "invalid-email";  // Invalid format

if (!$user->save()) {
    // Validation failed, restore original values
    $user->refresh();
    echo "Save failed. Data restored.";
}
```

## Use Cases

### 1. Long-Running Processes

Check for external changes during long operations:

```php
$job = new Job();
$job->get_by_id($job_id);

while ($job->status === 'processing') {
    // Do some work...
    sleep(5);
    
    // Check if status changed externally (by another worker)
    $job->refresh();
    
    if ($job->status === 'cancelled') {
        echo "Job was cancelled by another process";
        break;
    }
}
```

### 2. Polling for Updates

Monitor record changes:

```php
$order = new Order();
$order->get_by_id($order_id);

// Poll for status changes
while ($order->status === 'pending') {
    sleep(10);
    $order->refresh();
    
    if ($order->status === 'confirmed') {
        echo "Order confirmed!";
        // Send notification
        break;
    }
}
```

### 3. Reset After Validation Failure

```php
$user = new User();
$user->get_by_id(1);

// Try update
$user->from_array($_POST);

if ($user->save()) {
    echo "Updated successfully!";
} else {
    // Validation failed, show form again with original values
    $user->refresh();
    
    // Display errors
    foreach ($user->error->all as $field => $errors) {
        echo "$field: " . implode(', ', $errors) . "<br>";
    }
    
    // Form now shows original values, not invalid input
    $this->load->view('edit_user', array('user' => $user));
}
```

### 4. Multi-Step Transactions

Ensure data is current between transaction steps:

```php
// Step 1: Reserve inventory
$product = new Product();
$product->get_by_id($product_id);
$product->reserved_quantity += $quantity;
$product->save();

// Step 2: Refresh to get latest data (in case of concurrent updates)
$product->refresh();

// Step 3: Check if we can fulfill
if ($product->available_quantity >= $quantity) {
    $product->available_quantity -= $quantity;
    $product->save();
} else {
    // Rollback reservation
    $product->reserved_quantity -= $quantity;
    $product->save();
    echo "Insufficient inventory";
}
```

### 5. Verify External Process

Confirm another process completed:

```php
// Trigger external process
exec("php background_task.php $record_id > /dev/null &");

$record = new Record();
$record->get_by_id($record_id);

// Wait for process to update record
$max_attempts = 10;
$attempts = 0;

while ($record->processed == 0 && $attempts < $max_attempts) {
    sleep(2);
    $record->refresh();
    $attempts++;
}

if ($record->processed == 1) {
    echo "Background task completed!";
} else {
    echo "Background task timeout";
}
```

## Refresh vs. Re-Query

::: tip Understanding the Difference

**refresh()** - Reloads the same record:
```php
$user->get_by_id(1);
$user->refresh();  // Still loads record ID 1
```

**get()** - New query, can get different record:
```php
$user->get_by_id(1);
$user->where('email', 'new@example.com')->get();  // Might get different record
```
:::

## Refresh with Relationships

`refresh()` only reloads the main object, not relationships:

```php
$user = new User();
$user->include_related('country')->get_by_id(1);

echo $user->country->name;  // "Australia"

// Refresh user (does NOT refresh country)
$user->refresh();

// To refresh relationships, re-query them
$user->country->refresh();  // Refresh country
// OR
$user->include_related('country', TRUE)->get();  // Reload with relationships
```

### Refresh Related Objects

```php
$post = new Post();
$post->include_related('user')->get_by_id(1);

// Refresh post
$post->refresh();

// Refresh related user
if ($post->user->exists()) {
    $post->user->refresh();
}

// Now both post and user have fresh data
```

## Refresh in Loops

::: warning Performance Warning
Avoid refreshing inside tight loops:

```php
// BAD: Inefficient, causes many database queries
$users = new User();
$users->get();

foreach ($users as $user) {
    $user->refresh();  // Unnecessary query
    echo $user->name;
}

// GOOD: Data is already fresh from get()
$users = new User();
$users->get();

foreach ($users as $user) {
    echo $user->name;  // No refresh needed
}
```

Only refresh when you suspect data has changed externally.
:::

## Refresh After Time Delay

```php
$cache_duration = 300;  // 5 minutes

$product = new Product();
$product->get_by_id($product_id);

$last_refresh = time();

while (true) {
    // Do work...
    
    // Refresh every 5 minutes
    if (time() - $last_refresh > $cache_duration) {
        $product->refresh();
        $last_refresh = time();
        echo "Data refreshed from database";
    }
    
    // Use $product data...
}
```

## Optimistic Locking Pattern

Detect concurrent modifications:

```php
class Product extends DataMapper {
    // Add version column to database
    var $validation = array(
        'version' => array('rules' => array('required', 'integer'))
    );
}

// Load product
$product = new Product();
$product->get_by_id($id);
$original_version = $product->version;

// User makes changes
$product->name = $_POST['name'];
$product->price = $_POST['price'];

// Increment version
$product->version = $original_version + 1;

// Try to save with version check
$product->where('version', $original_version)->save();

if ($product->exists()) {
    echo "Saved successfully!";
} else {
    // Someone else modified the record
    echo "Record was modified by another user. Please refresh and try again.";
    
    $product->get_by_id($id);  // Get latest version
}
```

## Conditional Refresh

Only refresh if needed:

```php
class SmartModel extends DataMapper {
    private $last_refresh;
    
    public function smart_refresh($max_age = 60) {
        // Only refresh if data is older than max_age seconds
        if (!isset($this->last_refresh) || (time() - $this->last_refresh) > $max_age) {
            $this->refresh();
            $this->last_refresh = time();
            return TRUE;
        }
        return FALSE;
    }
}

// Usage:
$product = new SmartModel();
$product->get_by_id(1);

// Refresh only if data is older than 60 seconds
if ($product->smart_refresh(60)) {
    echo "Data was refreshed";
} else {
    echo "Data is still fresh, no refresh needed";
}
```

## Refresh and Attribute Casting

::: tip DataMapper 2.0
`refresh()` works seamlessly with attribute casting. Reloaded data is automatically casted:

```php
class Post extends DataMapper {
    var $casts = array(
        'published_at' => 'datetime',
        'metadata' => 'json',
        'is_featured' => 'bool'
    );
}

$post = new Post();
$post->get_by_id(1);

// Make changes
$post->is_featured = false;

// Refresh (reloads and re-casts)
$post->refresh();

var_dump($post->published_at);  // DateTime object
var_dump($post->metadata);      // Array (from JSON)
var_dump($post->is_featured);   // bool
```
:::

## Error Handling

```php
$user = new User();
$user->get_by_id($id);

// ... some time passes ...

try {
    $user->refresh();
    
    if (!$user->exists()) {
        echo "Record was deleted!";
    }
} catch (Exception $e) {
    echo "Error refreshing: " . $e->getMessage();
}
```

## Debugging Refresh

```php
$user = new User();
$user->get_by_id(1);

echo "Before refresh: " . $user->email . "\n";
var_dump($user->to_array());

$user->refresh();

echo "After refresh: " . $user->email . "\n";
var_dump($user->to_array());

// Check if data actually changed
if ($user->email !== $old_email) {
    echo "Email was updated externally!";
}
```

## Common Patterns

### Pattern 1: Discard Changes

```php
$model->field = "new value";
// Changed mind...
$model->refresh();  // Back to original
```

### Pattern 2: Verify External Update

```php
// Trigger external update
$this->trigger_update($id);

// Wait and verify
sleep(2);
$model->refresh();

if ($model->status === 'updated') {
    echo "External update completed";
}
```

### Pattern 3: Polling Loop

```php
while ($model->status === 'pending') {
    sleep(5);
    $model->refresh();
}
```

### Pattern 4: Reset After Failed Save

```php
if (!$model->save()) {
    $model->refresh();
    $this->show_form($model);
}
```

## Refresh vs. Clone vs. Get

| Method | Purpose | Use Case |
|--------|---------|----------|
| `refresh()` | Reload same record | Get latest data for current object |
| `get_clone()` | Copy object | Duplicate record or create snapshot |
| `get()` | New query | Find different record or apply filters |

```php
$user = new User();
$user->get_by_id(1);

// Refresh: Reload user ID 1
$user->refresh();

// Clone: Create copy of user
$clone = $user->get_clone();

// Get: New query (might get different user)
$user->where('email', 'other@example.com')->get();
```

## Performance Considerations

::: tip Best Practices
- **Only refresh when needed** - Don't refresh in every iteration
- **Batch operations** - Consider loading multiple records fresh instead of refreshing individually
- **Cache refresh time** - Track when data was last refreshed
- **Use get_iterated()** - For large datasets that need frequent updates

```php
// Efficient for large, changing datasets
$products = new Product();
$products->where('stock >', 0)->get_iterated();

foreach ($products as $product) {
    // Each iteration gets fresh data automatically
    if ($product->stock < 10) {
        echo "Low stock alert for: " . $product->name;
    }
}
```
:::

## Refresh with Timestamps

::: tip HasTimestamps Trait (DataMapper 2.0)
When using the `HasTimestamps` trait, check `updated_at` to see if refresh is needed:

```php
$post = new Post();
$post->get_by_id(1);

$last_updated = $post->updated_at;

// ... do some work ...

// Check if external update occurred
$post->refresh();

if ($post->updated_at > $last_updated) {
    echo "Post was updated externally!";
    // React to changes...
}
```
:::

## Related Methods

- **[get()](/guide/models/get)** - Query and retrieve objects
- **[get_clone()](clone)** - Create a copy of object
- **[save()](/guide/models/save)** - Save the object
- **[exists()](../../reference/utility#exists)** - Check if record exists

## See Also

- [get() - Querying](/guide/models/get)
- [clone() - Copy Objects](clone)
- [Model Fields](fields)
- [Dirty Tracking](/guide/datamapper-2/dirty-tracking) – `refresh()` resets the dirty state
- [Model Utilities](/guide/datamapper-2/model-utilities) – `fresh()` returns a new instance without modifying the original
- [Optimistic Locking](../../help/troubleshooting#Concurrency)
