# To Array

Export a DataMapper object to an associative array. Perfect for API responses, JSON exports, or debugging.

## Basic Usage

```php
$user = new User();
$user->get_by_id(1);

$array = $user->to_array();
print_r($array);
```

## Parameters

```php
$object->to_array($fields = '')
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fields` | string/array | Optional. Specify which fields to include |

## Return Value

Returns an associative array of field names and values.

## Examples

### Export All Fields

```php
$user = new User();
$user->get_by_id(1);

$data = $user->to_array();

// Output:
// array(
//     'id' => 1,
//     'username' => 'john',
//     'email' => 'john@example.com',
//     'password' => 'hashed_password',
//     'created_at' => '2024-01-15 10:30:00',
//     'updated_at' => '2024-01-15 10:30:00'
// )
```

### Export Specific Fields

```php
$user = new User();
$user->get_by_id(1);

// Only export specific fields
$data = $user->to_array(array('id', 'username', 'email'));

// Output:
// array(
//     'id' => 1,
//     'username' => 'john',
//     'email' => 'john@example.com'
// )
```

::: tip Security Best Practice
**Always specify fields** for public APIs to avoid exposing sensitive data:

```php
// GOOD: Only expose safe fields
$public_data = $user->to_array(array('id', 'username', 'bio'));

// BAD: Exposes password hash and sensitive data
$all_data = $user->to_array();  // Includes 'password', 'api_token', etc.
```
:::

### Export Multiple Objects

```php
$users = new User();
$users->get();

$result = array();
foreach ($users as $user) {
    $result[] = $user->to_array(array('id', 'username', 'email'));
}

print_r($result);
```

::: tip DataMapper 2.0
Prefer the new eager-loading syntax when you need related data in 2.0:

```php
$user = (new User())
    ->with('country')
    ->find($id);

// Country is already hydrated as a related model
echo json_encode($user->to_array());
```

`with()` keeps relations as rich objects, allows constraints, and avoids the column prefix juggling that `include_related()` required.
:::

## API Response Example

Perfect for REST API endpoints:

```php
// In your controller
public function get_user($id) {
    $user = new User();
    $user->get_by_id($id);
    
    if ($user->exists()) {
        // Define allowed fields for API
        $allowed = array('id', 'username', 'email', 'first_name', 'last_name', 'created_at');
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => TRUE,
                'data' => $user->to_array($allowed)
            )));
    } else {
        $this->output
            ->set_status_header(404)
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => FALSE,
                'error' => 'User not found'
            )));
    }
}
```

## Including Relationships

### Manual Relationship Export

```php
$user = new User();
$user->include_related('country')->get_by_id(1);

$data = $user->to_array();
$data['country'] = $user->country->to_array(array('id', 'name', 'code'));

// Output:
// array(
//     'id' => 1,
//     'username' => 'john',
//     'email' => 'john@example.com',
//     'country' => array(
//         'id' => 14,
//         'name' => 'Australia',
//         'code' => 'AU'
//     )
// )
```

### Multiple Relationships

```php
$post = new Post();
$post->include_related('user')->include_related('category')->get_by_id(1);

$data = $post->to_array(array('id', 'title', 'content', 'created_at'));
$data['author'] = $post->user->to_array(array('id', 'username'));
$data['category'] = $post->category->to_array(array('id', 'name'));

// Output:
// array(
//     'id' => 1,
//     'title' => 'My Post',
//     'content' => 'Post content...',
//     'created_at' => '2024-01-15 10:30:00',
//     'author' => array(
//         'id' => 5,
//         'username' => 'john'
//     ),
//     'category' => array(
//         'id' => 3,
//         'name' => 'Technology'
//     )
// )
```

### Has-Many Relationships

```php
$user = new User();
$user->get_by_id(1);

$data = $user->to_array(array('id', 'username', 'email'));

// Get related posts
$user->post->get();
$data['posts'] = array();

foreach ($user->post as $post) {
    $data['posts'][] = $post->to_array(array('id', 'title', 'created_at'));
}

// Output:
// array(
//     'id' => 1,
//     'username' => 'john',
//     'email' => 'john@example.com',
//     'posts' => array(
//         array('id' => 1, 'title' => 'First Post', 'created_at' => '2024-01-01'),
//         array('id' => 2, 'title' => 'Second Post', 'created_at' => '2024-01-02')
//     )
// )
```

## Attribute Casting Integration

::: tip New in DataMapper 2.0
`to_array()` exports **casted values**, not raw database values:

```php
class Post extends DataMapper {
    var $casts = array(
        'published_at' => 'datetime',
        'view_count' => 'int',
        'is_featured' => 'bool',
        'metadata' => 'json'
    );
}

$post = new Post();
$post->get_by_id(1);

$data = $post->to_array();

// Values are exported in their casted form:
// array(
//     'published_at' => DateTime object,  // Can be formatted as needed
//     'view_count' => 150,                 // int, not string '150'
//     'is_featured' => true,               // bool, not string '1'
//     'metadata' => array('tags' => [...]) // Array, not JSON string
// )
```

For API responses, you may want to format DateTime objects:

```php
$data = $post->to_array();

// Format datetime for JSON
if ($data['published_at'] instanceof DateTime) {
    $data['published_at'] = $data['published_at']->format('Y-m-d H:i:s');
}

echo json_encode($data);
```
:::

## Computed/Virtual Fields

Add computed fields to the export:

```php
$user = new User();
$user->get_by_id(1);

$data = $user->to_array(array('id', 'first_name', 'last_name', 'email'));

// Add computed field
$data['full_name'] = $user->first_name . ' ' . $user->last_name;
$data['is_admin'] = ($user->role === 'admin');

// Output:
// array(
//     'id' => 1,
//     'first_name' => 'John',
//     'last_name' => 'Doe',
//     'email' => 'john@example.com',
//     'full_name' => 'John Doe',
//     'is_admin' => false
// )
```

## Excluding Sensitive Data

Create a helper method in your model:

```php
class User extends DataMapper {
    
    public function to_public_array() {
        // Safe fields for public consumption
        $safe_fields = array('id', 'username', 'bio', 'avatar', 'created_at');
        return $this->to_array($safe_fields);
    }
    
    public function to_admin_array() {
        // Additional fields for admin views
        $admin_fields = array(
            'id', 'username', 'email', 'first_name', 'last_name',
            'is_active', 'last_login', 'created_at', 'updated_at'
        );
        return $this->to_array($admin_fields);
    }
}

// Usage:
$user = new User();
$user->get_by_id(1);

$public_data = $user->to_public_array();   // Safe for anyone
$admin_data = $user->to_admin_array();     // Admin only
```

## Pagination Example

Export paginated results:

```php
public function get_users_paginated($page = 1, $per_page = 20) {
    $users = new User();
    
    // Get total count
    $total = $users->count();
    
    // Get paginated results
    $offset = ($page - 1) * $per_page;
    $users->limit($per_page, $offset)->get();
    
    // Export to array
    $result = array();
    foreach ($users as $user) {
        $result[] = $user->to_array(array('id', 'username', 'email', 'created_at'));
    }
    
    return array(
        'data' => $result,
        'pagination' => array(
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page)
        )
    );
}
```

## Caching Exported Arrays

```php
// Cache expensive operations
$cache_key = 'user_' . $user_id . '_array';

if ($cached = $this->cache->get($cache_key)) {
    return $cached;
}

$user = new User();
$user->include_related('country')->get_by_id($user_id);

$data = $user->to_array(array('id', 'username', 'email'));
$data['country'] = $user->country->to_array(array('id', 'name'));

// Cache for 1 hour
$this->cache->save($cache_key, $data, 3600);

return $data;
```

## Common Patterns

### Pattern 1: Simple API Response

```php
$model = new Model();
$model->get_by_id($id);

return json_encode(array(
    'success' => TRUE,
    'data' => $model->to_array($safe_fields)
));
```

### Pattern 2: Collection Export

```php
$models = new Model();
$models->where('status', 'active')->get();

$result = array();
foreach ($models as $model) {
    $result[] = $model->to_array($fields);
}

return $result;
```

### Pattern 3: Nested Relationships

```php
$data = $parent->to_array($parent_fields);
$data['children'] = array();

foreach ($parent->child as $child) {
    $data['children'][] = $child->to_array($child_fields);
}

return $data;
```

### Pattern 4: CSV Export

```php
$users = new User();
$users->get();

$csv = array();
$csv[] = array('ID', 'Username', 'Email', 'Created');

foreach ($users as $user) {
    $data = $user->to_array(array('id', 'username', 'email', 'created_at'));
    $csv[] = array_values($data);
}

// Convert to CSV format
// ... output CSV
```

## Null Values

`to_array()` includes fields with NULL values:

```php
$user = new User();
$user->username = 'john';
$user->email = 'john@example.com';
$user->bio = NULL;  // Not set

$data = $user->to_array();

// Output:
// array(
//     'id' => NULL,           // Not saved yet
//     'username' => 'john',
//     'email' => 'john@example.com',
//     'bio' => NULL
// )
```

## Debugging

Use `to_array()` for debugging:

```php
$user = new User();
$user->where('status', 'active')->get();

// Quick debug
echo '<pre>';
print_r($user->to_array());
echo '</pre>';

// Better with var_dump
var_dump($user->to_array());

// Best with error_log
error_log(print_r($user->to_array(), TRUE));
```

## Related Methods

- **[from_array()](from-array)** - Populate object from array
- **[to_json()](to-json)** - Export object to JSON string
- **[get()](/guide/models/get)** - Query and retrieve objects
- **[save()](/guide/models/save)** - Save the object

## See Also

- [from_array() - Import from Array](from-array)
- [to_json() - Export to JSON](to-json)
- [Attribute Casting](../datamapper-2/casting)
- [API Development Best Practices](../../help/faq#API)
