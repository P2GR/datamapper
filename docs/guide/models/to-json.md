# To JSON

Export a DataMapper object directly to a JSON string. Perfect for REST APIs, AJAX responses, and JavaScript applications.

## Basic Usage

```php
$user = new User();
$user->get_by_id(1);

$json = $user->to_json();
echo $json;

// Output: {"id":1,"username":"john","email":"john@example.com",...}
```

## Parameters

```php
$object->to_json($fields = '', $pretty_print = FALSE)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fields` | string/array | Optional. Specify which fields to include |
| `$pretty_print` | boolean | Optional. Format with indentation (requires `JSON_PRETTY_PRINT`, available in supported PHP versions) |

## Return Value

Returns a JSON-encoded string.

## Examples

### Export All Fields

```php
$user = new User();
$user->get_by_id(1);

echo $user->to_json();

// Output (compact):
// {"id":1,"username":"john","email":"john@example.com","created_at":"2024-01-15 10:30:00"}
```

### Export Specific Fields

```php
$user = new User();
$user->get_by_id(1);

// Only export safe fields
$json = $user->to_json(array('id', 'username', 'bio'));

echo $json;

// Output:
// {"id":1,"username":"john","bio":"Developer and writer"}
```

### Pretty Print for Debugging

```php
$user = new User();
$user->get_by_id(1);

echo $user->to_json('', TRUE);

// Output (formatted):
// {
//     "id": 1,
//     "username": "john",
//     "email": "john@example.com",
//     "created_at": "2024-01-15 10:30:00"
// }
```

::: tip Security Best Practice
**Always specify fields** for public APIs:

```php
// GOOD: Only expose safe fields
$safe_fields = array('id', 'username', 'bio', 'avatar');
$json = $user->to_json($safe_fields);

// BAD: Exposes all fields including passwords
$json = $user->to_json();  // Dangerous!
```
:::

## REST API Response

Perfect for API endpoints:

```php
// In your controller
public function get_user($id) {
    $user = new User();
    $user->get_by_id($id);
    
    $this->output
        ->set_content_type('application/json')
        ->set_output($user->to_json(array(
            'id', 'username', 'email', 'first_name', 
            'last_name', 'bio', 'avatar', 'created_at'
        )));
}
```

### With Error Handling

```php
public function get_user($id) {
    $user = new User();
    $user->get_by_id($id);
    
    if ($user->exists()) {
        $response = array(
            'success' => TRUE,
            'data' => json_decode($user->to_json(array(
                'id', 'username', 'email', 'created_at'
            )))
        );
    } else {
        $this->output->set_status_header(404);
        $response = array(
            'success' => FALSE,
            'error' => 'User not found'
        );
    }
    
    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
}
```

## AJAX Response

```php
public function save_user() {
    $user = new User();
    $user->from_array($_POST, array('username', 'email', 'bio'));
    
    if ($user->save()) {
        echo json_encode(array(
            'success' => TRUE,
            'message' => 'User saved successfully',
            'user' => json_decode($user->to_json(array('id', 'username', 'email')))
        ));
    } else {
        echo json_encode(array(
            'success' => FALSE,
            'errors' => $user->error->all
        ));
    }
}
```

## Including Relationships

### Single Relationship

```php
$post = new Post();
$post->include_related('user')->get_by_id(1);

// Manual nested JSON
$data = json_decode($post->to_json(array('id', 'title', 'content')), TRUE);
$data['author'] = json_decode($post->user->to_json(array('id', 'username')), TRUE);

echo json_encode($data);

// Output:
// {
//     "id": 1,
//     "title": "My Post",
//     "content": "Post content...",
//     "author": {
//         "id": 5,
//         "username": "john"
//     }
// }
```

::: tip DataMapper 2.0
With eager loading the same response becomes much simpler:

```php
$post = (new Post())
    ->with(['user' => fn($q) => $q->select('id', 'username')])
    ->find($id);

echo $post->to_json();
```

The `with()` API keeps relationships intact, lets you constrain the related query, and removes the need for manual JSON stitching.
:::

### Multiple Relationships

```php
$post = new Post();
$post->include_related('user')->include_related('category')->get_by_id(1);

$data = json_decode($post->to_json(array('id', 'title', 'content', 'created_at')), TRUE);
$data['author'] = json_decode($post->user->to_json(array('id', 'username', 'avatar')), TRUE);
$data['category'] = json_decode($post->category->to_json(array('id', 'name', 'slug')), TRUE);

echo json_encode($data, JSON_PRETTY_PRINT);
```

### Has-Many Relationships

```php
$user = new User();
$user->get_by_id(1);

$data = json_decode($user->to_json(array('id', 'username', 'email')), TRUE);

// Add posts array
$user->post->order_by('created_at', 'desc')->get();
$data['posts'] = array();

foreach ($user->post as $post) {
    $data['posts'][] = json_decode($post->to_json(array(
        'id', 'title', 'excerpt', 'created_at'
    )), TRUE);
}

echo json_encode($data, JSON_PRETTY_PRINT);

// Output:
// {
//     "id": 1,
//     "username": "john",
//     "email": "john@example.com",
//     "posts": [
//         {"id": 1, "title": "First Post", "excerpt": "...", "created_at": "2024-01-01"},
//         {"id": 2, "title": "Second Post", "excerpt": "...", "created_at": "2024-01-02"}
//     ]
// }
```

## Attribute Casting Integration

::: tip New in DataMapper 2.0
`to_json()` works seamlessly with attribute casting:

```php
class Post extends DataMapper {
    var $casts = array(
        'published_at' => 'datetime',
        'view_count' => 'int',
        'is_featured' => 'bool',
        'metadata' => 'json',
        'tags' => 'json'
    );
}

$post = new Post();
$post->get_by_id(1);

echo $post->to_json();

// Output:
// {
//     "id": 1,
//     "title": "My Post",
//     "published_at": "2024-01-15T10:30:00+00:00",
//     "view_count": 150,
//     "is_featured": true,
//     "metadata": {"author_note": "Important post"},
//     "tags": ["php", "coding", "tutorial"]
// }
```

**DateTime Formatting:**
DateTime objects are automatically formatted as ISO 8601 strings.

**JSON Casting:**
JSON-casted fields are automatically decoded to arrays/objects before being re-encoded to JSON.
:::

## Collection Export

Export multiple objects:

```php
$users = new User();
$users->where('status', 'active')->get();

$result = array();
foreach ($users as $user) {
    $result[] = json_decode($user->to_json(array('id', 'username', 'email')), TRUE);
}

echo json_encode($result, JSON_PRETTY_PRINT);

// Output:
// [
//     {"id": 1, "username": "john", "email": "john@example.com"},
//     {"id": 2, "username": "jane", "email": "jane@example.com"},
//     {"id": 3, "username": "bob", "email": "bob@example.com"}
// ]
```

## Paginated API Response

```php
public function get_users() {
    $page = $this->input->get('page') ?: 1;
    $per_page = 20;
    
    $users = new User();
    $total = $users->count();
    
    $offset = ($page - 1) * $per_page;
    $users->limit($per_page, $offset)->order_by('created_at', 'desc')->get();
    
    $data = array();
    foreach ($users as $user) {
        $data[] = json_decode($user->to_json(array(
            'id', 'username', 'email', 'created_at'
        )), TRUE);
    }
    
    $response = array(
        'data' => $data,
        'pagination' => array(
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page),
            'has_more' => ($page * $per_page) < $total
        )
    );
    
    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
}
```

## Custom JSON Structure

Create custom JSON structures:

```php
class User extends DataMapper {
    
    public function to_api_json() {
        $data = array(
            'id' => $this->id,
            'profile' => array(
                'username' => $this->username,
                'full_name' => $this->first_name . ' ' . $this->last_name,
                'bio' => $this->bio,
                'avatar' => $this->avatar_url
            ),
            'stats' => array(
                'post_count' => $this->post->count(),
                'follower_count' => $this->follower->count(),
                'joined' => $this->created_at
            )
        );
        
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

// Usage:
$user = new User();
$user->get_by_id(1);

echo $user->to_api_json();

// Output:
// {
//     "id": 1,
//     "profile": {
//         "username": "john",
//         "full_name": "John Doe",
//         "bio": "Developer and writer",
//         "avatar": "https://example.com/avatars/john.jpg"
//     },
//     "stats": {
//         "post_count": 42,
//         "follower_count": 150,
//         "joined": "2024-01-15 10:30:00"
//     }
// }
```

## JSON Options

Use PHP's JSON constants for fine control:

```php
$user = new User();
$user->get_by_id(1);

// Get array first
$data = json_decode($user->to_json(array('id', 'username', 'bio')), TRUE);

// Encode with specific options
$json = json_encode($data, 
    JSON_PRETTY_PRINT | 
    JSON_UNESCAPED_SLASHES | 
    JSON_UNESCAPED_UNICODE
);

echo $json;
```

## JSONP Support

For cross-domain AJAX requests:

```php
public function get_user_jsonp($id) {
    $user = new User();
    $user->get_by_id($id);
    
    $callback = $this->input->get('callback') ?: 'callback';
    
    $json = $user->to_json(array('id', 'username', 'bio'));
    
    $this->output
        ->set_content_type('application/javascript')
        ->set_output($callback . '(' . $json . ');');
}

// Request: /api/user/1?callback=handleUser
// Response: handleUser({"id":1,"username":"john","bio":"..."});
```

## Caching JSON Responses

```php
public function get_user_cached($id) {
    $cache_key = 'user_json_' . $id;
    
    // Try cache first
    $cached = $this->cache->get($cache_key);
    if ($cached !== FALSE) {
        $this->output
            ->set_content_type('application/json')
            ->set_output($cached);
        return;
    }
    
    // Generate JSON
    $user = new User();
    $user->get_by_id($id);
    
    $json = $user->to_json(array('id', 'username', 'email', 'bio', 'avatar'));
    
    // Cache for 1 hour
    $this->cache->save($cache_key, $json, 3600);
    
    $this->output
        ->set_content_type('application/json')
        ->set_output($json);
}
```

## Common Patterns

### Pattern 1: Simple API Endpoint

```php
public function api_get($id) {
    $model = new Model();
    $model->get_by_id($id);
    
    if ($model->exists()) {
        echo $model->to_json($safe_fields);
    } else {
        $this->output->set_status_header(404);
        echo json_encode(array('error' => 'Not found'));
    }
}
```

### Pattern 2: Collection Endpoint

```php
public function api_list() {
    $models = new Model();
    $models->get();
    
    $result = array();
    foreach ($models as $model) {
        $result[] = json_decode($model->to_json($fields), TRUE);
    }
    
    echo json_encode($result);
}
```

### Pattern 3: Nested Resources

```php
$data = json_decode($parent->to_json($parent_fields), TRUE);
$data['children'] = array();

foreach ($parent->child as $child) {
    $data['children'][] = json_decode($child->to_json($child_fields), TRUE);
}

echo json_encode($data, JSON_PRETTY_PRINT);
```

### Pattern 4: API with Metadata

```php
$response = array(
    'success' => TRUE,
    'timestamp' => time(),
    'data' => json_decode($model->to_json($fields), TRUE)
);

echo json_encode($response);
```

## Error Handling

Handle JSON encoding errors:

```php
$user = new User();
$user->get_by_id($id);

$json = $user->to_json($fields);

if (json_last_error() !== JSON_ERROR_NONE) {
    // Handle error
    log_message('error', 'JSON encoding failed: ' . json_last_error_msg());
    
    $this->output
        ->set_status_header(500)
        ->set_output(json_encode(array(
            'success' => FALSE,
            'error' => 'Failed to encode response'
        )));
}
```

## Content Negotiation

Support multiple formats:

```php
public function get_user($id) {
    $user = new User();
    $user->get_by_id($id);
    
    $format = $this->input->get('format') ?: 'json';
    $fields = array('id', 'username', 'email');
    
    switch ($format) {
        case 'json':
            $this->output
                ->set_content_type('application/json')
                ->set_output($user->to_json($fields));
            break;
            
        case 'xml':
            $array = json_decode($user->to_json($fields), TRUE);
            $this->output
                ->set_content_type('application/xml')
                ->set_output($this->array_to_xml($array));
            break;
            
        default:
            $this->output->set_status_header(400);
            echo json_encode(array('error' => 'Unsupported format'));
    }
}
```

## Related Methods

- **[to_array()](to-array)** - Export to array format
- **[from_array()](from-array)** - Import from array
- **[get()](/guide/models/get)** - Query and retrieve objects
- **[save()](/guide/models/save)** - Save the object

## See Also

- [to_array() - Export to Array](to-array)
- [from_array() - Import from Array](from-array)
- [Serialization Control](../datamapper-2/serialization) – `$hidden`, `$visible`, and `$appends` for automatic field filtering
- [Attribute Casting](../datamapper-2/casting)
- [REST API Best Practices](../../help/faq#API)
- [JSON Handling](../../help/troubleshooting#JSON)
