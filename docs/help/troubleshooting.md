# Troubleshooting

Common issues and their solutions.

## Installation Issues

### "Class 'DataMapper' not found"

**Problem**: DataMapper class is not being loaded.

**Solutions**:

1. **Check autoload configuration**:
```php
// application/config/autoload.php
$autoload['libraries'] = ['database', 'datamapper'];
```

2. **Verify file locations**:
```
application/libraries/datamapper.php
application/libraries/DataMapperBackwardCompatibility.php
application/config/datamapper.php
```

3. **Manual load in controller**:
```php
$this->load->library('datamapper');
```

4. **Check file permissions** (Linux/Mac):
```bash
chmod 644 application/libraries/datamapper.php
```

### "Unable to locate the model you have specified"

**Problem**: Model file not found or named incorrectly.

**Solutions**:

1. **Check file naming**:
   - File: `User.php` (capitalized)
   - Class: `class User extends DataMapper`
   - Location: `application/models/User.php`

2. **Check class name matches filename**:
```php
// File: User.php
class User extends DataMapper {  // Correct
    ...
}

// NOT:
class user extends DataMapper {  // Incorrect: wrong case
class Users extends DataMapper { // Incorrect: plural form
```

3. **Load model before using**:
```php
$this->load->model('user');
$user = new User();
```

## Database Connection Issues

### "Unable to connect to your database server"

**Problem**: Database connection configuration is incorrect.

**Solutions**:

1. **Check database config**:
```php
// application/config/database.php
$db['default'] = array(
    'hostname' => 'localhost',      // Check this
    'username' => 'root',           // Check this
    'password' => 'your_password',  // Check this
    'database' => 'your_database',  // Check this
    'dbdriver' => 'mysqli',
);
```

2. **Test database connection**:
```php
$this->load->database();
if ($this->db->conn_id) {
    echo "Connected!";
} else {
    echo "Connection failed!";
}
```

3. **Check MySQL service is running**:
```bash
# Linux
sudo service mysql status

# Windows
# Check Services.msc for MySQL service
```

4. **Verify database exists**:
```sql
SHOW DATABASES;
```

### "Table 'database.users' doesn't exist"

**Problem**: Table hasn't been created or is named incorrectly.

**Solutions**:

1. **Create the table**:
```sql
CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

2. **Check table naming**:
   - Model: `User` → Table: `users` (plural, lowercase)
   - Model: `BlogPost` → Table: `blogposts`

3. **Override table name if needed**:
```php
class User extends DataMapper {
    public $table = 'app_users';  // Custom table name
}
```

4. **Check table prefix**:
```php
// config/datamapper.php
$config['prefix'] = 'app_';  // If you use prefixes

// Model: User → Table: app_users
```

## Query Issues

### "Unknown column in field list"

**Problem**: Trying to access a column that doesn't exist.

**Solutions**:

1. **Check column exists in database**:
```sql
DESCRIBE users;
```

2. **Check spelling**:
```php
$user->username;  // Correct
$user->user_name; // Incorrect: check database column name
```

3. **Refresh table info** (development):
```php
// Delete production cache
// application/cache/datamapper/
```

### "You must use the 'set' method to update an entry"

**Problem**: Trying to update without data.

**Solution**: Set properties before calling update():

```php
// Incorrect
$user = new User();
$user->where('id', 1)->update();

// Correct
$user = new User();
$user->where('id', 1)->update('active', 1);

// OR
$user = (new User())->find(1);
$user->active = 1;
$user->save();
```

## Relationship Issues

### Relationships return empty even when data exists

**Problem**: Foreign keys or relationship definitions are incorrect.

**Solutions**:

1. **Check both sides of relationship are defined**:
```php
class User extends DataMapper {
    public $has_many = ['post'];  // Defined
}

class Post extends DataMapper {
    public $has_one = ['user'];   // Also defined
}
```

2. **Verify foreign key column exists**:
```sql
DESCRIBE posts;
-- Should have user_id column
```

3. **Check foreign key naming**:
   - Default: `user_id` (singular model name + _id)
   - Custom:
```php
public $has_many = [
    'post' => [
        'other_field' => 'author_id'  // Custom foreign key
    ]
];
```

4. **Manually get relationship**:
```php
$user = (new User())->find(1);
$user->post->get();  // Explicit get()

foreach ($user->post as $post) {
    echo $post->title;
}
```

### N+1 Query Problem (slow performance)

**Problem**: Loading relationships in loops causes too many queries.

**Solution**: Use eager loading (DataMapper 2.0):

```php
// Inefficient N+1 problem (101 queries)
$users = (new User())->get();
foreach ($users as $user) {
    foreach ($user->post as $post) {  // +1 query per user
        echo $post->title;
    }
}

// Eager loading (2 queries)
$users = (new User())->with('post')->get();
foreach ($users as $user) {
    foreach ($user->post as $post) {  // Already loaded
        echo $post->title;
    }
}
```

### Many-to-Many join table issues

**Problem**: Many-to-many relationships not working.

**Solutions**:

1. **Create join table** (alphabetical order):
```sql
-- For Post has many Tag
CREATE TABLE posts_tags (
    id INT(11) NOT NULL AUTO_INCREMENT,
    post_id INT(11) NOT NULL,
    tag_id INT(11) NOT NULL,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY tag_id (tag_id)
) ENGINE=InnoDB;
```

2. **Verify table name is alphabetical**:
    - `posts_tags` (p before t)
    - `tags_posts` (wrong order)

3. **Custom join table name**:
```php
public $has_many = [
    'tag' => [
        'join_table' => 'post_tag_relations'
    ]
];
```

## Validation Issues

### Validation always fails

**Problem**: Validation rules are incorrect or data doesn't meet requirements.

**Solutions**:

1. **Check error messages**:
```php
if (!$user->save()) {
    echo $user->error->string;  // See what failed
    
    // Or individual errors
    print_r($user->error->all);
}
```

2. **Verify validation rules**:
```php
public $validation = [
    'username' => [
        'label' => 'Username',
        'rules' => ['required', 'min_length' => 3]  // Check these
    ]
];
```

3. **Check unique validation**:
```php
'email' => [
    'rules' => ['required', 'unique']  // Fails if email exists
]
```

### "unique" validation always fails

**Problem**: Record already exists or validation is checking against itself.

**Solution**: Use `edit_unique` for updates:

```php
public $validation = [
    'email' => [
        'rules' => ['required', 'edit_unique']  // Allows same email when editing
    ]
];
```

## DataMapper 2.0 Issues

### Traits not working (HasTimestamps, SoftDeletes)

**Problem**: Trait not properly included or columns don't exist.

**Solutions**:

1. **Include trait at top of file**:
```php
use HasTimestamps;
use SoftDeletes;

class User extends DataMapper {
    use HasTimestamps, SoftDeletes;
}
```

2. **Add required columns**:
```sql
-- For HasTimestamps
ALTER TABLE users 
ADD COLUMN created_at DATETIME NULL,
ADD COLUMN updated_at DATETIME NULL;

-- For SoftDeletes
ALTER TABLE users 
ADD COLUMN deleted_at DATETIME NULL;
```

3. **Check PHP version**:
    - DataMapper 2.0 requires PHP 7.4+ (traits are available in all supported versions)

### Query builder chaining not working

**Problem**: Using old DataMapper version or syntax error.

**Solutions**:

1. **Verify DataMapper 2.0 installed**:
```php
// Confirm datamapper/querybuilder.php is present and autoloaded
```

2. **Wrap in parentheses**:
```php
// Incorrect
$users = new User()->where('active', 1)->get();

// Correct
$users = (new User())->where('active', 1)->get();
```

3. **Fall back to traditional syntax** (always works):
```php
$user = new User();
$user->where('active', 1);
$user->get();
```

### Eager loading not reducing queries

**Problem**: Relationship not actually being eager loaded.

**Solutions**:

1. **Verify you're using with()**:
```php
$users = (new User())->with('post')->get();  // Eager load
```

2. **Check relationship is defined**:
```php
class User extends DataMapper {
    public $has_many = ['post'];  // Must be defined
}
```

3. **Use DataMapper 2.0 query builder syntax**:
```php
// Traditional syntax doesn't support with()
$user = new User();
$user->with('post');  // Does not execute the query

// Use the chainable query builder
$users = (new User())->with('post')->get();  // Works
```

## Performance Issues

### Slow queries

**Solutions**:

1. **Enable query logging**:
```php
// config/database.php
$db['default']['save_queries'] = TRUE;

// In controller
$this->db->last_query();  // See generated SQL
print_r($this->db->queries);  // See all queries
```

2. **Add database indexes**:
```sql
-- Index foreign keys
ALTER TABLE posts ADD INDEX user_id (user_id);

-- Index frequently queried columns
ALTER TABLE users ADD INDEX active (active);
ALTER TABLE users ADD INDEX email (email);
```

3. **Use select() to limit columns**:
```php
$users = (new User())
    ->select('id, username, email')  // Don't fetch all columns
    ->get();
```

4. **Enable production cache**:
```php
// config/datamapper.php
$config['production_cache'] = TRUE;
```

5. **Use query caching** (DataMapper 2.0):
```php
$users = (new User())
    ->where('active', 1)
    ->cache(3600)
    ->get();
```

### Memory issues with large datasets

**Solution**: Use streaming (DataMapper 2.0):

```php
// Instead of loading all at once
$users = (new User())->get();  // Loads all into memory

// Use streaming
(new User())->stream(function($user) {
    // Process one user at a time
    echo $user->username;
});

// Or chunking
(new User())->chunk(1000, function($users) {
    // Process 1000 at a time
});
```

## Production Issues

### Works in development but not production

**Solutions**:

1. **Check error reporting**:
```php
// index.php or config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

2. **Check file permissions** (Linux):
```bash
chmod -R 755 application/
chmod -R 777 application/cache/
```

3. **Clear all caches**:
```bash
rm -rf application/cache/datamapper/*
```

4. **Check PHP extensions**:
```php
phpinfo();
// Verify mysqli or pdo_mysql is loaded
```

### Cache issues

**Solutions**:

1. **Clear production cache**:
```bash
rm -rf application/cache/datamapper/*
```

2. **Disable production cache** (temporarily):
```php
// config/datamapper.php
$config['production_cache'] = FALSE;
```

3. **Check cache directory permissions**:
```bash
chmod -R 777 application/cache/
```

## Getting More Help

### Enable detailed error messages

```php
// index.php
define('ENVIRONMENT', 'development');

// config/database.php
$db['default']['db_debug'] = TRUE;
```

### Debug DataMapper queries

```php
// See last query
echo $user->check_last_query();

// See all queries
$this->db->save_queries = TRUE;
print_r($this->db->queries);
```

### Still stuck?

- **Search issues**: [GitHub Issues](https://github.com/P2GR/datamapper/issues)
- **Ask for help**: [GitHub Discussions](https://github.com/P2GR/datamapper/discussions)
- **Report bugs**: [New Issue](https://github.com/P2GR/datamapper/issues/new)

::: tip Pro Tip
Always check `$this->db->last_query()` to see the actual SQL being generated!
:::
