# From Array

Populate a DataMapper object from an array. This is perfect for processing form data, API requests, or bulk imports.

## Basic Usage

```php
$user = new User();
$user->from_array($_POST);
$user->save();
```

## Parameters

```php
$object->from_array($data, $fields = '', $save = FALSE)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | array | Associative array of field names and values |
| `$fields` | string/array | Optional. Specify which fields to populate |
| `$save` | boolean | Optional. If `TRUE`, automatically saves after populating |

## Return Value

Returns the object itself for method chaining.

## Examples

### Populate from POST Data

```php
$user = new User();

// Get data from form submission
$data = array(
    'username' => $_POST['username'],
    'email' => $_POST['email'],
    'password' => $_POST['password']
);

$user->from_array($data);

if ($user->save()) {
    echo "User created successfully!";
}
```

### Populate and Save in One Step

```php
$user = new User();

$user->from_array($_POST, '', TRUE);  // Automatically saves

if ($user->valid) {
    echo "User saved!";
} else {
    // Display validation errors
    echo $user->error->string;
}
```

### Selective Field Population (Whitelisting)

Only populate specific fields for security:

```php
$user = new User();

// Only populate these fields, ignore everything else in $_POST
$allowed_fields = array('username', 'email', 'bio');

$user->from_array($_POST, $allowed_fields);
$user->save();
```

::: tip Whitelist Pattern
**Always whitelist fields** from user input to prevent mass-assignment vulnerabilities:

```php
// GOOD: Only allow specific fields
$user->from_array($_POST, array('username', 'email', 'bio'));

// BAD: Allows any field in $_POST
$user->from_array($_POST);  // User could inject 'is_admin' => 1
```
:::

### Bulk Import from CSV/JSON

```php
// Import from JSON
$json_data = file_get_contents('users.json');
$users_array = json_decode($json_data, TRUE);

foreach ($users_array as $user_data) {
    $user = new User();
    $user->from_array($user_data);
    $user->save();
}
```

### Update Existing Record

```php
$user = new User();
$user->get_by_id(5);

// Update from form data
$updates = array(
    'email' => 'newemail@example.com',
    'bio' => 'Updated bio text'
);

$user->from_array($updates);
$user->save();
```

## Combining with Validation

`from_array()` respects your model's validation rules:

```php
class User extends DataMapper {
    var $validation = array(
        'username' => array(
            'rules' => array('required', 'min_length' => 3, 'max_length' => 20)
        ),
        'email' => array(
            'rules' => array('required', 'valid_email')
        )
    );
}

$user = new User();
$user->from_array($_POST);

if ($user->save()) {
    // Validation passed
    echo "User saved!";
} else {
    // Validation failed
    foreach ($user->error->all as $field => $errors) {
        echo "$field: " . implode(', ', $errors) . "<br>";
    }
}
```

## Attribute Casting Integration

::: tip New in DataMapper 2.0
Combine `from_array()` with attribute casting for automatic type conversion:

```php
class Post extends DataMapper {
    var $has_many = array('comment');
    
    var $casts = array(
        'published_at' => 'datetime',
        'view_count' => 'int',
        'is_featured' => 'bool',
        'metadata' => 'json'
    );
}

// Array with string values
$data = array(
    'title' => 'My Post',
    'published_at' => '2024-01-15 10:30:00',  // String
    'view_count' => '150',                      // String
    'is_featured' => '1',                       // String
    'metadata' => '{"tags":["php","coding"]}'   // JSON string
);

$post = new Post();
$post->from_array($data);

// Attributes are automatically cast to correct types
var_dump($post->published_at);  // DateTime object
var_dump($post->view_count);    // int(150)
var_dump($post->is_featured);   // bool(true)
var_dump($post->metadata);      // array(['tags' => ['php', 'coding']])
```
:::

## Working with Relationships

`from_array()` only populates the current model's fields. For relationships, use separate methods:

```php
$user = new User();
$user->from_array($user_data);
$user->save();

// Now handle relationships
$country = new Country();
$country->get_by_id($country_id);
$user->save($country);  // Create relationship
```

## Ignoring Unknown Fields

Unknown fields in the array are automatically ignored:

```php
$data = array(
    'username' => 'john',
    'email' => 'john@example.com',
    'unknown_field' => 'this will be ignored',  // Not in users table
    'another_bad_field' => 'also ignored'
);

$user = new User();
$user->from_array($data);  // Only username and email are set
$user->save();
```

## API Request Example

Perfect for REST API endpoints:

```php
// In your controller
public function create_user() {
    // Get JSON body
    $json = file_get_contents('php://input');
    $data = json_decode($json, TRUE);
    
    $user = new User();
    
    // Whitelist allowed fields
    $allowed = array('username', 'email', 'password', 'first_name', 'last_name');
    $user->from_array($data, $allowed);
    
    if ($user->save()) {
        $this->output
            ->set_status_header(201)
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => TRUE,
                'user_id' => $user->id
            )));
    } else {
        $this->output
            ->set_status_header(400)
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => FALSE,
                'errors' => $user->error->all
            )));
    }
}
```

## Common Patterns

### Pattern 1: Create from Form

```php
$user = new User();
$user->from_array($_POST, array('username', 'email', 'password'));
$user->save();
```

### Pattern 2: Update from Form

```php
$user = new User();
$user->where('id', $id)->get();
$user->from_array($_POST, array('email', 'bio'));
$user->save();
```

### Pattern 3: Bulk Import

```php
foreach ($import_data as $row) {
    $item = new Item();
    $item->from_array($row);
    $item->save();
}
```

### Pattern 4: API Request with Validation

```php
$model = new Model();
$model->from_array($json_data, $allowed_fields);

if ($model->save()) {
    return $this->json_success($model);
} else {
    return $this->json_error($model->error);
}
```

## Timestamps with from_array

::: tip Automatic Timestamps
If using the `HasTimestamps` trait (DataMapper 2.0), `created_at` and `updated_at` are managed automatically:

```php
$user = new User();
$user->from_array($_POST);
$user->save();

// created_at and updated_at are set automatically
// You don't need to include them in the array
```
:::

## Related Methods

- **[to_array()](to-array)** - Export object to array
- **[to_json()](to-json)** - Export object to JSON
- **[save()](/guide/models/save)** - Save the object
- **[validate()](../advanced/validation)** - Validate data before saving

## See Also

- [Model Saving](/guide/models/save)
- [Validation](../advanced/validation)
- [Attribute Casting](../datamapper-2/casting)
- [Security Best Practices](../../help/troubleshooting#Security)
