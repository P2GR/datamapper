# Timestamps (DataMapper 2.0)

Automatically track when records are created and updated. The `HasTimestamps` trait adds `created_at` and `updated_at` columns that are managed automatically.

**New in DataMapper 2.0:** Simply use the `HasTimestamps` trait in your model for zero-configuration automatic timestamps with full customization options.

## Why Timestamps?

Manual timestamp management is error-prone and repetitive. Automatic timestamps provide:

- **Audit Trail** - Know exactly when records changed
- **Debugging** - Track data lifecycle
- **Business Logic** - Filter by creation/update time
- **Compliance** - Meet regulatory requirements
- **Zero Maintenance** - Completely automatic

## Basic Setup

### 1. Add Database Columns

Add `created_at` and `updated_at` DATETIME columns:

```sql
ALTER TABLE users ADD COLUMN created_at DATETIME NULL;
ALTER TABLE users ADD COLUMN updated_at DATETIME NULL;
```

### 2. Use HasTimestamps Trait

```php
<?php
use DataMapper\Traits\HasTimestamps;

class User extends DataMapper {
    use HasTimestamps;
    
    var $has_many = array('post');
}
```

Done! Timestamps are now automatic.

## Basic Usage

### Creating Records

```php
$user = new User();
$user->username = 'john';
$user->email = 'john@example.com';
$user->save();

// created_at and updated_at are set automatically
echo $user->created_at; // "2025-01-15 10:30:00"
echo $user->updated_at; // "2025-01-15 10:30:00"
```

### Updating Records

```php
$user = new User();
$user->get_by_id(5);

$user->email = 'newemail@example.com';
$user->save();

// updated_at changes automatically
// created_at remains unchanged
echo $user->created_at; // "2025-01-15 10:30:00" (original)
echo $user->updated_at; // "2025-01-15 14:45:00" (current time)
```

### Querying by Timestamps

```php
// Get recent users
$users = new User();
$users->where('created_at >', date('Y-m-d', strtotime('-7 days')))->get();

// Get recently updated
$users = new User();
$users->where('updated_at >', date('Y-m-d H:i:s', strtotime('-1 hour')))->get();

// Order by creation date
$users = new User();
$users->order_by('created_at', 'desc')->limit(10)->get();
```

## Timestamp Behavior

### On Create (INSERT)

Both `created_at` and `updated_at` are set to current time:

```php
$post = new Post();
$post->title = 'My Post';
$post->save();

// Both timestamps set
$post->created_at; // "2025-01-15 10:30:00"
$post->updated_at; // "2025-01-15 10:30:00"
```

### On Update (UPDATE)

Only `updated_at` changes:

```php
$post->title = 'Updated Title';
$post->save();

// Only updated_at changes
$post->created_at; // "2025-01-15 10:30:00" (unchanged)
$post->updated_at; // "2025-01-15 11:45:00" (new time)
```

### On Delete

Timestamps remain unchanged (unless using SoftDeletes):

```php
$post->delete();

// Timestamps remain as they were
// (deleted_at is separate, from SoftDeletes trait)
```

## Customization

### Custom Column Names

```php
class User extends DataMapper {
    use HasTimestamps;
    
    protected $created_at_column = 'date_created';
    protected $updated_at_column = 'date_modified';
}
```

### Custom Timestamp Format

```php
class User extends DataMapper {
    use HasTimestamps;
    
    protected $timestamp_format = 'U'; // Unix timestamp
    // or
    protected $timestamp_format = 'c'; // ISO 8601
    // Default: 'Y-m-d H:i:s'
}
```

## Real-World Examples

### Activity Feed

```php
// Get recent activity
$activities = new Activity();
$activities->order_by('created_at', 'desc')->limit(50)->get();

foreach ($activities as $activity) {
    echo "{$activity->user->username} {$activity->action} ";
    echo time_ago($activity->created_at);
}

// Helper function
function time_ago($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return round($diff / 60) . ' minutes ago';
    if ($diff < 86400) return round($diff / 3600) . ' hours ago';
    return round($diff / 86400) . ' days ago';
}
```

### Content Management

```php
// Display post metadata
$post = new Post();
$post->get_by_id($post_id);

echo "Published: " . date('F j, Y', strtotime($post->created_at));

if ($post->created_at !== $post->updated_at) {
    echo " (Updated: " . date('F j, Y', strtotime($post->updated_at)) . ")";
}
```

### Audit Log

```php
class AuditLog extends DataMapper {
    use HasTimestamps;
    
    var $has_one = array('user');
    
    public static function log($action, $model, $model_id) {
        $log = new self();
        $log->action = $action;
        $log->model = get_class($model);
        $log->model_id = $model_id;
        $log->user_id = get_current_user_id();
        $log->save();
        
        // created_at automatically set
    }
}

// Usage
$user->save();
AuditLog::log('update', $user, $user->id);
```

### Data Freshness Check

```php
// Check if cache is stale
$cache_duration = 3600; // 1 hour

$data = new CachedData();
$data->get_by_key($key);

if ($data->exists()) {
    $age = time() - strtotime($data->updated_at);
    
    if ($age > $cache_duration) {
        // Cache is stale, refresh
        $data->refresh_data();
        $data->save(); // updated_at refreshes
    }
}
```

## Combining with Attribute Casting

::: tip DateTime Objects
Combine with attribute casting for DateTime objects:

```php
class Post extends DataMapper {
    use HasTimestamps;
    
    var $casts = array(
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    );
}

$post = new Post();
$post->get_by_id(1);

// Timestamps are DateTime objects
var_dump($post->created_at); // DateTime object

echo $post->created_at->format('F j, Y'); // "January 15, 2025"
echo $post->created_at->diffForHumans();  // "2 hours ago" (if Carbon installed)

// Compare timestamps
if ($post->updated_at > $post->created_at) {
    echo "Post has been updated";
}

// Add time
$future = $post->created_at->modify('+7 days');
```
:::

## Preventing Updates

### Touch Without Updating

Update `updated_at` without changing other fields:

```php
$post->touch();

// Only updated_at changes, no other modifications
```

## Soft Deletes Integration

Combine with SoftDeletes trait:

```php
use DataMapper\Traits\HasTimestamps;
use DataMapper\Traits\SoftDeletes;

class User extends DataMapper {
    use HasTimestamps, SoftDeletes;
}

$user = new User();
$user->username = 'john';
$user->save();

// You now have:
echo $user->created_at; // When created
echo $user->updated_at; // When last updated
echo $user->deleted_at; // When soft-deleted (NULL if not deleted)
```

## Querying Examples

### Recent Records

```php
// Posts from last 24 hours
$posts = new Post();
$posts->where('created_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))->get();

// Modified in last hour
$users = new User();
$users->where('updated_at >', date('Y-m-d H:i:s', strtotime('-1 hour')))->get();
```

### Date Ranges

```php
// Posts created in January 2025
$posts = new Post();
$posts->where('created_at >=', '2025-01-01 00:00:00')
      ->where('created_at <', '2025-02-01 00:00:00')
      ->get();

// Updated this week
$start_of_week = date('Y-m-d 00:00:00', strtotime('monday this week'));
$users = new User();
$users->where('updated_at >=', $start_of_week)->get();
```

### Unchanged Records

```php
// Records never updated (created_at equals updated_at)
$posts = new Post();
$posts->where('created_at = updated_at')->get();

// Records not updated in 30 days
$stale = new User();
$stale->where('updated_at <', date('Y-m-d', strtotime('-30 days')))->get();
```

### Sorting

```php
// Newest first
$posts->order_by('created_at', 'desc')->get();

// Oldest first
$posts->order_by('created_at', 'asc')->get();

// Most recently updated
$posts->order_by('updated_at', 'desc')->get();

// Complex sorting
$posts->order_by('updated_at', 'desc')
      ->order_by('created_at', 'desc')
      ->get();
```

## API Response Example

```php
public function get_post($id) {
    $post = new Post();
    $post->include_related('user')->get_by_id($id);
    
    if ($post->exists()) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content,
                'author' => $post->user->username,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
                'last_modified' => strtotime($post->updated_at),
                'is_edited' => ($post->created_at !== $post->updated_at)
            )));
    }
}
```

## Statistics and Analytics

```php
// User registration trends
$stats = array();
$months = 12;

for ($i = 0; $i < $months; $i++) {
    $start = date('Y-m-01 00:00:00', strtotime("-$i months"));
    $end = date('Y-m-t 23:59:59', strtotime("-$i months"));
    
    $users = new User();
    $count = $users->where('created_at >=', $start)
                   ->where('created_at <=', $end)
                   ->count();
    
    $stats[date('M Y', strtotime($start))] = $count;
}

// Content activity
$activity = array(
    'posts_today' => Post::where('created_at >', date('Y-m-d 00:00:00'))->count(),
    'posts_this_week' => Post::where('created_at >', date('Y-m-d', strtotime('monday this week')))->count(),
    'posts_this_month' => Post::where('created_at >', date('Y-m-01 00:00:00'))->count(),
    'recently_updated' => Post::where('updated_at >', date('Y-m-d H:i:s', strtotime('-1 hour')))->count()
);
```

## Testing

```php
public function test_timestamps() {
    $user = new User();
    $user->username = 'test';
    $user->email = 'test@example.com';
    $user->save();
    
    // created_at should be set
    $this->assertNotNull($user->created_at);
    $this->assertNotNull($user->updated_at);
    
    // Both should be equal on creation
    $this->assertEquals($user->created_at, $user->updated_at);
    
    $created_time = $user->created_at;
    
    // Wait a moment
    sleep(2);
    
    // Update
    $user->email = 'updated@example.com';
    $user->save();
    
    // created_at should not change
    $this->assertEquals($created_time, $user->created_at);
    
    // updated_at should change
    $this->assertNotEquals($created_time, $user->updated_at);
    $this->assertGreaterThan($created_time, $user->updated_at);
}
```

## Performance Considerations

::: tip Indexing
Add indexes for better query performance:

```sql
CREATE INDEX idx_created_at ON users(created_at);
CREATE INDEX idx_updated_at ON users(updated_at);

-- Compound index for common queries
CREATE INDEX idx_status_created ON posts(status, created_at DESC);
```
:::

## Troubleshooting

**Timestamps not being set:**
```php
// Make sure trait is used
class User extends DataMapper {
    use HasTimestamps; // Must be present
}

// Check database columns exist
// created_at DATETIME NULL
// updated_at DATETIME NULL
```

**Timestamps not updating:**
```php
// Make sure you're calling save()
$user->email = 'new@example.com';
$user->save(); // Required for timestamp update

// Not this:
$this->db->update('users', array('email' => 'new@example.com'));
```

**Wrong timestamp format:**
```php
// Check your database column type
// Should be DATETIME, not VARCHAR

// MySQL:
ALTER TABLE users MODIFY created_at DATETIME NULL;
```

## Migration from Manual Timestamps

If you have existing manual timestamp code:

```php
// OLD WAY (manual)
$user = new User();
$user->username = 'john';
$user->created_at = date('Y-m-d H:i:s'); // Manual
$user->updated_at = date('Y-m-d H:i:s'); // Manual
$user->save();

// NEW WAY (automatic with trait)
class User extends DataMapper {
    use HasTimestamps;
}

$user = new User();
$user->username = 'john';
$user->save(); // Timestamps automatic!
```

To migrate:
1. Add `use HasTimestamps;` to your model
2. Remove manual timestamp assignments
3. Test thoroughly

## Related Documentation

- [Soft Deletes](soft-deletes)
- [Attribute Casting](casting)
- [Model Saving](../models/save)
- [Date/Time Casting](casting#DateTime)

## See Also

- [Model Fields](../models/fields)
- [Database Schema](../getting-started/database)
- [Best Practices](../../help/faq#BestPractices)