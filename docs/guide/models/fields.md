# Model Fields and Properties

DataMapper models automatically map database columns to object properties. Understanding how fields work is essential for effective data manipulation.

## Automatic Field Mapping

When you create a DataMapper model, all database columns become accessible as object properties:

```php
// Database table: users
// Columns: id, name, email, created_at, updated_at

$user = new User();
$user->get_by_id(1);

// All columns are now properties
echo $user->id;         // 1
echo $user->name;       // "John Doe"
echo $user->email;      // "john@example.com"
echo $user->created_at; // "2025-01-15 10:30:00"
```

## Setting Properties

Set properties directly before saving:

```php
$user = new User();
$user->name = "Jane Smith";
$user->email = "jane@example.com";
$user->password = "secret123";
$user->save();
```

## Special Properties

### ID Property

Every DataMapper model has an `id` property that corresponds to the primary key:

```php
$user = new User();
$user->get_by_id(5);

if ($user->exists()) {
    echo $user->id; // 5
}
```

::: info Custom Primary Key
By default, DataMapper uses `id` as the primary key. To use a different column, set `$primary_key` in your model:

```php
class User extends DataMapper {
    var $primary_key = 'user_id';
}
```
:::

### Table Property

The `$table` property specifies the database table name:

```php
class User extends DataMapper {
    var $table = 'users'; // Usually auto-detected
}
```

### Validation Property

The `$validation` property defines validation rules:

```php
class User extends DataMapper {
    var $validation = array(
        'email' => array(
            'rules' => array('required', 'valid_email', 'unique')
        )
    );
}
```

See [Validation](/guide/advanced/validation) for details.

## Virtual Properties <Badge type="tip" text="computed" />

Add computed properties via custom methods:

```php
class User extends DataMapper {
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
    
    // Virtual property: full_name
    function get_full_name()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    // Virtual property: is_admin
    function is_admin()
    {
        return $this->role === 'admin';
    }
}

// Usage
$user = new User(1);
echo $user->get_full_name(); // "John Doe"

if ($user->is_admin()) {
    // Grant admin access
}
```

## Property Access Patterns

### Direct Access

```php
$user = new User();
$user->name = "Alice";
$user->email = "alice@example.com";
echo $user->name; // "Alice"
```

### Array-Style Access

While properties are typically accessed directly, you can convert models to arrays:

```php
$user = new User();
$user->get_by_id(1);

// Convert to array
$data = $user->to_array();
// array('id' => 1, 'name' => 'John', 'email' => 'john@example.com', ...)

// Access array elements
echo $data['name']; // "John"
```

## Property Types and Casting <Badge type="tip" text="2.0" />

DataMapper 2.0 supports automatic attribute casting:

::: code-group

```php [With Casting]
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

$user = new User();
$user->get_by_id(1);

// Automatically casted types
var_dump($user->is_active);  // bool(true)
var_dump($user->age);         // int(25)
var_dump($user->metadata);    // array(...)
var_dump($user->created_at);  // DateTime object
```

```php [Without Casting]
class User extends DataMapper {
    // No casting trait
}

$user = new User();
$user->get_by_id(1);

// Raw database values
var_dump($user->is_active);  // string("1")
var_dump($user->age);         // string("25")
var_dump($user->metadata);    // string("{...}")
var_dump($user->created_at);  // string("2025-01-15 10:30:00")
```

:::

Learn more: [Attribute Casting](/guide/datamapper-2/casting)

## Reserved Property Names

Certain property names are reserved by DataMapper and should not be used as column names:

::: danger Reserved Names
- `db` - Database object
- `table` - Table name
- `error` - Validation errors
- `valid` - Validation status
- `all` - Query results array
- Many more - see [Reserved Names](/reference/reserved-names)
:::

```php
// Bad - 'error' is reserved
CREATE TABLE users (
    id INT PRIMARY KEY,
    error VARCHAR(255)  -- Don't use 'error' as column name
);

// Good - Use alternative names
CREATE TABLE users (
    id INT PRIMARY KEY,
    error_message VARCHAR(255)  -- OK
);
```

## Null vs Empty Values

Understanding null vs empty is important:

```php
$user = new User();

// Check if property exists and has a value
if (!empty($user->name)) {
    echo $user->name;
}

// Check specifically for NULL
if ($user->name === NULL) {
    echo "Name is NULL";
}

// Check if object exists in database
if ($user->exists()) {
    echo "User exists in database";
}
```

## Default Values

Set default values in the constructor or `post_model_init()`:

::: code-group

```php [post_model_init]
class User extends DataMapper {
    
    function post_model_init($from_cache = FALSE)
    {
        // Set defaults for new records only
        if (!$this->exists()) {
            $this->status = 'active';
            $this->role = 'user';
            $this->created_at = date('Y-m-d H:i:s');
        }
    }
}
```

```php [Database Default]
-- Better: Use database defaults
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status VARCHAR(20) DEFAULT 'active',
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

:::

::: tip Best Practice
Use database-level defaults when possible. They ensure consistency even if records are inserted outside your application.
:::

## Property Visibility

All DataMapper properties are public by default:

```php
class User extends DataMapper {
    // These are public (accessible)
    var $validation = array();
    var $has_many = array('post');
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}

$user = new User();
// Can access public properties
print_r($user->has_many);
```

## Checking Property Existence

```php
$user = new User();
$user->get_by_id(1);

// Check if property exists
if (property_exists($user, 'name')) {
    echo "Property 'name' exists";
}

// Check if property is set and not null
if (isset($user->name)) {
    echo "Property 'name' is set";
}

// Check if property is not empty
if (!empty($user->name)) {
    echo "Property 'name' has a value";
}
```

## Working with Related Properties

Relationship properties are accessible after loading:

```php
$user = new User();
$user->include_related('country')
     ->get_by_id(1);

// Related properties available
echo $user->country_id;    // Foreign key
echo $user->country_name;  // Included field
```

::: tip DataMapper 2.0
Use eager loading for better performance:

```php
$user = new User();
$user->with('country')
     ->get();

foreach ($user as $u) {
    echo $u->country->name; // No N+1 queries!
}
```
:::

## Common Patterns

### Bulk Assignment

```php
function create_user($data)
{
    $user = new User();
    
    // Bulk assignment from array
    $user->from_array($data, array(
        'name',
        'email',
        'password'
    ));
    
    return $user->save();
}

// Usage
$data = array(
    'name' => 'Bob',
    'email' => 'bob@example.com',
    'password' => 'secret',
    'admin_note' => 'malicious' // Ignored - not in whitelist
);

create_user($data);
```

### Property Blacklisting

```php
$user = new User();
$user->from_array($_POST, array(), array(
    'id',           // Exclude ID
    'created_at',   // Exclude timestamps
    'updated_at'
));
```

### Selective Export

```php
$user = new User();
$user->get_by_id(1);

// Export only specific fields
$safe_data = $user->to_array(array(
    'id',
    'name',
    'email'
    // Password excluded
));

echo json_encode($safe_data);
```

## Performance Considerations

### Select Only Needed Fields

```php
// Loads all fields
$user = new User();
$user->get();

// Loads only needed fields
$user = new User();
$user->select('id, name, email')
     ->get();
```

### Avoid Loading Large Fields

```php
// If you have large TEXT/BLOB columns
$user = new User();
$user->select('id, name, email') // Exclude 'bio' TEXT column
     ->get();

// Load large fields only when needed
$user = new User();
$user->select('bio')
     ->get_by_id($user_id);
```

## See Also

- [Creating Models](/guide/models/creating) - Model basics
- [From Array](/guide/models/from-array) - Bulk assignment
- [To Array](/guide/models/to-array) - Export to array
- [Attribute Casting](/guide/datamapper-2/casting) - Type casting (2.0)
- [Validation](/guide/advanced/validation) - Data validation
- [Reserved Names](/reference/reserved-names) - Avoid conflicts
