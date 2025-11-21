# Quick Reference

A comprehensive cheatsheet for DataMapper ORM. Bookmark this page for quick access to common methods and patterns.

## Model Creation

```php
// Basic model
class User extends DataMapper {
    function __construct($id = NULL) {
        parent::__construct($id);
    }
}

// With custom table name
class User extends DataMapper {
    var $table = 'app_users';
}

// With relationships
class User extends DataMapper {
    var $has_one = array('profile');
    var $has_many = array('post', 'comment');
}
```

## CRUD Operations

### Create

```php
// New record
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();

// Mass assignment with fillable whitelist
class User extends DataMapper {
    var $fillable = array('name', 'email');
}

$user = new User();
$user->fill($_POST)->save();

// With relationship
$user = new User();
$user->name = 'John';
$profile = new Profile();
$profile->bio = 'Developer';
$user->save($profile);
```

### Read

```php
// Get all
$user = new User();
$user->get();

// Get by ID
$user = new User(5);
// or
$user = new User();
$user->get_by_id(5);

// Get one
$user = new User();
$user->where('email', 'john@example.com')->get();

// Get many
$user = new User();
$user->where('status', 'active')->get();

// With limit
$user = new User();
$user->limit(10)->get();

// With offset
$user = new User();
$user->limit(10, 20)->get(); // 10 records, starting at 20
```

### Update

```php
// Update existing
$user = new User(5);
$user->name = 'Jane Doe';
$user->save();

// Update multiple fields
$user = new User(5);
$user->from_array(array(
    'name' => 'Jane',
    'email' => 'jane@example.com'
));
$user->save();
```

### Mass Assignment

```php
$user = new User();
$user->guarded = array('is_admin');

$user->fill($_POST);       // Respects $fillable / $guarded
$user->forceFill($seed);   // Skips guarding (trusted data only)

DataMapper::unguarded(function () use ($user, $payload) {
    $user->fill($payload);
});

$post = Post::create(array('title' => 'Hello', 'body' => '...'));
```

### Delete

```php
// Delete record
$user = new User(5);
$user->delete();

// Delete with query
$user = new User();
$user->where('status', 'inactive')
     ->where('last_login <', '2020-01-01')
     ->delete_all();
```

## Query Methods

### Where Clauses

```php
// Basic where
$user->where('status', 'active')

// With operator
$user->where('age >', 18)
$user->where('score >=', 80)
$user->where('name !=', 'Admin')

// Multiple where (AND)
$user->where('status', 'active')
     ->where('role', 'admin')

// OR where
$user->where('status', 'active')
     ->or_where('role', 'admin')

// Where IN
$user->where_in('id', array(1, 2, 3, 4, 5))

// Where NOT IN
$user->where_not_in('status', array('banned', 'deleted'))

// LIKE
$user->like('name', 'john')
$user->like('name', 'john', 'after') // john%
$user->like('name', 'john', 'before') // %john

// NOT LIKE
$user->not_like('email', '@spam.com')

// IS NULL
$user->where('deleted_at IS NULL')

// IS NOT NULL
$user->where('email_verified_at IS NOT NULL')
```

### Query Grouping

```php
// (a AND b) OR c
$user->group_start()
       ->where('status', 'active')
       ->where('role', 'admin')
     ->group_end()
     ->or_where('id', 1)

// a AND (b OR c)
$user->where('status', 'active')
     ->group_start()
       ->where('role', 'admin')
       ->or_where('role', 'moderator')
     ->group_end()
```

### Ordering

```php
// Order by
$user->order_by('created_at', 'desc')
$user->order_by('name', 'asc')

// Multiple order
$user->order_by('status', 'asc')
     ->order_by('created_at', 'desc')

// Random
$user->order_by('id', 'random')
```

### Limiting

```php
// Limit
$user->limit(10)

// Limit with offset
$user->limit(10, 20) // 10 records, skip first 20

// Pagination
$page = 2;
$per_page = 10;
$user->limit($per_page, ($page - 1) * $per_page)
```

### Selection

```php
// Select specific fields
$user->select('id, name, email')

// Select with alias
$user->select('${parent}.*, country.name as country_name')

// Distinct
$user->distinct()->select('role')

// Aggregates
$user->select_max('score')
$user->select_min('age')
$user->select_avg('rating')
$user->select_sum('total_sales')
```

### Grouping & Having

```php
// Group by
$user->group_by('role')

// Having
$user->select('role, COUNT(*) as count')
     ->group_by('role')
     ->having('count >', 10)
```

## Relationships

### Has One

```php
// Definition
class User extends DataMapper {
    var $has_one = array('profile');
}

// Access
$user = new User(1);
$user->profile->get();
echo $user->profile->bio;

// Query
$user->where_related('profile', 'verified', 1)->get();
```

### Has Many

```php
// Definition
class User extends DataMapper {
    var $has_many = array('post');
}

// Access
$user = new User(1);
$user->post->get();
foreach ($user->post as $post) {
    echo $post->title;
}

// Query
$user->where_related('post', 'published', 1)->get();

// Count
$user->post->count(); // count related posts
```

### Many to Many

```php
// Definition
class Post extends DataMapper {
    var $has_many = array('tag');
}

// Add relationship
$post = new Post(1);
$tag = new Tag(5);
$post->save($tag);

// Add multiple
$post->save(array($tag1, $tag2, $tag3));

// Remove relationship
$post->delete($tag);

// Get related
$post->tag->get();
```

### Relationship Queries

```php
// Where related
$user->where_related('post', 'status', 'published')->get();

// Where related count
$user->where_related_post('status', 'published')->get();

// Include related fields
$user->include_related('country', 'name')->get();

::: info DataMapper 2.0
Prefer `(new User())->with('country')` for new code—`with()` eager loads the relation, supports constraints, and avoids manually selecting/prefixing columns. Use `include_related()` only when you expressly need the flattened column output for legacy responses.
:::
// Access: $user->country_name
```

## DataMapper 2.0 Features

### Query Builder

```php
// Chainable query builder syntax
$user = (new User())
    ->where('status', 'active')
    ->where('age >', 18)
    ->order_by('created_at', 'desc')
    ->limit(10)
    ->get();
```

### Result Helpers

```php
// Collection result
$users = (new User())
    ->where('status', 'active')
    ->collect();

// Simple arrays
$emails = (new User())
    ->where('newsletter', 1)
    ->pluck('email');

// Values with fallback
$latestSlug = (new Post())
    ->order_by('created_at', 'DESC')
    ->value('slug', 'draft');

// Collections from plucked values
$ids = (new Order())
    ->where('status', 'pending')
    ->pluck_collection('id');

// First model shortcut
$firstAdmin = (new User())
    ->where('role', 'admin')
    ->first();
```

### Eager Loading

```php
// Prevent N+1 queries
$user = new User();
$user->with('post')
     ->with('comment')
     ->get();

// With constraints
$user->with('post', function($query) {
    $query->where('published', 1);
})->get();
```

### Collections

```php
$users = new User();
$users->where('status', 'active')->get();

// Collection methods
$emails = $users->pluck('email');
$admins = $users->filter(function($u) {
    return $u->role === 'admin';
});
$names = $users->map(function($u) {
    return strtoupper($u->name);
});
$chunks = $users->chunk(100);
```

### Query Caching

```php
// Cache query for 1 hour
$user = new User();
$user->cache(3600)
     ->where('status', 'active')
     ->get();

// Clear cache
$user->clear_cache();

// Cache + helper
$emails = (new User())
    ->where('active', 1)
    ->cache(900)
    ->pluck('email');
```

### Soft Deletes

```php
use DataMapper\SoftDeletes;

class User extends DataMapper {
    use SoftDeletes;
}

// Soft delete
$user = new User(1);
$user->delete(); // Sets deleted_at

// Include soft deleted
$user->with_softdeleted()->get();

// Only soft deleted
$user->only_softdeleted()->get();

// Permanently delete
$user->force_delete();

// Restore
// Restore
$user->restore();
```

### Timestamps

```php
use DataMapper\HasTimestamps;

class User extends DataMapper {
    use HasTimestamps;
}

// Automatic created_at/updated_at
$user = new User();
$user->name = 'John';
$user->save(); // Sets created_at

$user->name = 'Jane';
$user->save(); // Updates updated_at
```

### Attribute Casting

```php
use DataMapper\AttributeCasting;

class User extends DataMapper {
    use AttributeCasting;
    
    protected $casts = array(
        'is_active'  => 'bool',
        'age'        => 'int',
        'metadata'   => 'json',
        'created_at' => 'datetime'
    );
}

// Automatic casting
$user = new User(1);
var_dump($user->is_active); // bool(true)
var_dump($user->metadata);   // array(...)
```

## Validation

```php
class User extends DataMapper {
    var $validation = array(
        'email' => array(
            'label' => 'Email Address',
            'rules' => array('required', 'valid_email', 'unique')
        ),
        'password' => array(
            'rules' => array('required', 'min_length' => 6, 'encrypt')
        ),
        'age' => array(
            'rules' => array('numeric', 'greater_than' => 0, 'less_than' => 150)
        )
    );
}

// Check validation
if ($user->save()) {
    // Success
} else {
    // Failed
    foreach ($user->error->all as $error) {
        echo $error;
    }
}
```

## Utility Methods

```php
// Check if exists
if ($user->exists()) {}

// Count results
$user->where('status', 'active')->get();
echo $user->result_count();

// All results as array
$user->get();
print_r($user->all);

// Clear/reset
$user->clear();

// Clone
$new_user = $user->get_clone();

// Refresh from database
$user->refresh();

// Check if field changed
if ($user->is_dirty('email')) {}

// Get original value
$original_email = $user->get_original('email');

// Convert to array
$data = $user->to_array();

// Convert to JSON
$json = $user->to_json();
```

## Transactions

```php
// Manual transaction
$this->db->trans_start();

$user = new User();
$user->name = 'John';
$user->save();

$profile = new Profile();
$profile->user_id = $user->id;
$profile->save();

$this->db->trans_complete();

if ($this->db->trans_status() === FALSE) {
    // Transaction failed
}
```

## Common Patterns

### Login System

```php
function login($email, $password) {
    $user = new User();
    $user->where('email', $email)->get();
    
    if (!$user->exists()) {
        return FALSE;
    }
    
    if (password_verify($password, $user->password)) {
        return $user;
    }
    
    return FALSE;
}
```

### Pagination

```php
function get_users($page = 1, $per_page = 10) {
    $user = new User();
    
    // Get total count
    $total = $user->count();
    
    // Get page results
    $user->limit($per_page, ($page - 1) * $per_page)
         ->order_by('created_at', 'desc')
         ->get();
    
    return array(
        'users' => $user,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    );
}
```

### Search

```php
function search_users($query) {
    $user = new User();
    $user->group_start()
           ->like('name', $query)
           ->or_like('email', $query)
           ->or_like('username', $query)
         ->group_end()
         ->where('status', 'active')
         ->get();
    
    return $user;
}
```

### Bulk Operations

```php
// Activate multiple users
$user = new User();
$user->where_in('id', $selected_ids)
     ->update('status', 'active');

// Delete multiple
$user = new User();
$user->where_in('id', $selected_ids)
     ->delete_all();
```

## Performance Tips

```php
// Use eager loading
$user->with('post')->get();

// Select only needed fields
$user->select('id, name, email')->get();

// Use indexes
$user->where('email', $email)->get(); // indexed column

// Cache queries
$user->cache(3600)->get();

// Use get_iterated() for large datasets
$user->get_iterated();
foreach ($user as $u) {
    // Process one at a time
}

// Avoid N+1
foreach ($user->all as $u) {
    $u->post->get(); // BAD: N queries
}

// Don't select * unnecessarily
$user->get(); // Loads all fields (including large TEXT columns)
```

## Debugging

```php
// Get last query
echo $user->check_last_query();

// Enable query profiling
$user->enableProfiler()->get();

// Print all SQL queries
echo $this->db->last_query();

// Debug validation errors
print_r($user->error);
```

## See Also

- [Full Documentation](/) - Complete guide
- [API Reference](/reference/functions) - All methods
- [Usage Guides](/guide/datamapper-2/index) - Real-world walkthroughs
- [FAQ](/help/faq) - Common questions
