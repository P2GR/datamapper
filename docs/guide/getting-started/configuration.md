# Configuration

DataMapper uses configuration files to customize its behavior. This guide covers all configuration options.

## Configuration Files

DataMapper uses two main configuration files:

```
application/config/
├── datamapper.php           # Main DataMapper configuration
└── database.php             # Database configuration (CodeIgniter)
```

## datamapper.php

The main DataMapper configuration file controls behavior and default settings.

### Location

```
application/config/datamapper.php
```

### Basic Configuration

```php
<?php
// DataMapper Configuration
$config['prefix'] = '';           // Table prefix
$config['join_prefix'] = '';      // Join table prefix
$config['error_prefix'] = '<p>';  // Error message prefix
$config['error_suffix'] = '</p>'; // Error message suffix
$config['created_field'] = 'created_at';  // Timestamp field
$config['updated_field'] = 'updated_at';  // Timestamp field
$config['local_time'] = FALSE;    // Use local time for timestamps
$config['unix_timestamp'] = FALSE; // Use UNIX timestamps
$config['timestamp_format'] = 'Y-m-d H:i:s'; // Timestamp format
$config['lang_file_format'] = 'model_${model}'; // Language file format
$config['field_label_lang_format'] = '${model}_${field}'; // Field label format
$config['auto_transaction'] = FALSE; // Automatic transactions
$config['auto_populate_has_many'] = FALSE; // Auto-populate relationships
$config['auto_populate_has_one'] = TRUE;   // Auto-populate has_one
$config['all_array_uses_ids'] = FALSE; // all_to_array includes IDs
$config['db_params'] = array(); // Custom DB connection params
$config['extensions'] = array(); // Load extensions
$config['extensions_path'] = 'datamapper'; // Extensions folder
```

## Configuration Options

### Logging (DataMapper 2.0)

DataMapper now delegates all log output to CodeIgniter's native `log_message()` via the `dmz_log_message()` helper. No extra bootstrap is required—the log level, path, and thresholds are controlled by your standard CodeIgniter configuration.

```php
// Anywhere inside your models and libraries
dmz_log_message('debug', 'Fetching installations', array('installation_id' => $id));
```

To fine-tune verbosity, adjust `log_threshold`/`log_path` in `application/config/config.php`, or provide your own logger implementation that wraps `log_message()`.

See [Logging & Error Handling](#logging-datamapper-20) for complete documentation.

### Table Prefix

Automatically prefix all table names:

```php
$config['prefix'] = 'app_';
```

```php
// Model: User
// Table: app_users (automatic)
```

### Join Table Prefix

Prefix for many-to-many relationship tables:

```php
$config['join_prefix'] = 'rel_';
```

```php
// User has many Post
// Join table: rel_posts_users (automatic)
```

### Timestamps

Configure automatic timestamp fields:

```php
$config['created_field'] = 'created_at';
$config['updated_field'] = 'updated_at';
$config['timestamp_format'] = 'Y-m-d H:i:s';
```

**DataMapper 2.0**: Use the `HasTimestamps` trait:

```php
use HasTimestamps;

class User extends DataMapper {
    use HasTimestamps;
}
```

### Error Messages

Customize validation error formatting:

```php
$config['error_prefix'] = '<div class="error">';
$config['error_suffix'] = '</div>';
```

```php
// In your view
echo $user->error->string;
// Outputs: <div class="error">Username is required</div>
```

### Auto Transactions

Enable automatic transactions for save operations:

```php
$config['auto_transaction'] = TRUE;
```

::: warning Performance Impact
Auto transactions can impact performance. Use manual transactions for better control.
:::

### Extensions

Load DataMapper extensions globally:

```php
$config['extensions'] = array('json', 'csv', 'array');
$config['extensions_path'] = 'datamapper';
```

### Relationship Auto-Population

```php
$config['auto_populate_has_one'] = FALSE;
$config['auto_populate_has_many'] = FALSE;
```

::: info DataMapper 2.0
Leave auto-populate switched off and opt in with the chainable `with()` eager-loading API when you actually need related data. This keeps N+1 queries under control and lets you add per-relation constraints.
:::

## Per-Model Configuration

Override configuration in individual models:

```php
class User extends DataMapper {
    
    // Custom table name
    public $table = 'app_users';
    
    // Custom timestamp fields
    public $created_field = 'created_date';
    public $updated_field = 'modified_date';
    
    // Custom error delimiters
    public $error_prefix = '<span class="error">';
    public $error_suffix = '</span>';
    
    // Enable timestamps
    public $auto_timestamps = TRUE;
    
    public function __construct($id = NULL) {
        parent::__construct($id);
    }
}
```

## Database Configuration

DataMapper uses CodeIgniter's database configuration.

### application/config/database.php

```php
$db['default'] = array(
    'dsn'   => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'your_database',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);
```

## Environment-Specific Configuration

### Development vs Production

```php
// config/datamapper.php
if (ENVIRONMENT === 'development') {
    $config['db_params']['save_queries'] = TRUE;
    $config['auto_transaction'] = FALSE;

} else {
    $config['db_params']['save_queries'] = FALSE;
    $config['auto_transaction'] = TRUE;
}
```

> Adjust `log_threshold`/`log_path` in `application/config/config.php` to control how CodeIgniter records DataMapper log output in each environment.

## Advanced Configuration

### Custom Database Connection

Use a different database connection for specific models:

```php
class LogEntry extends DataMapper {
    
    public $db_params = array(
        'hostname' => 'logs.example.com',
        'username' => 'logger',
        'password' => 'secret',
        'database' => 'application_logs'
    );
    
    public function __construct($id = NULL) {
        parent::__construct($id);
    }
}
```

### Multiple Databases

```php
// config/database.php
$db['default'] = [...]; // Main database
$db['logging'] = [...]; // Logging database

// Model
class AuditLog extends DataMapper {
    public $db_params = 'logging'; // Use logging connection
}
```

## Validation Configuration

Configure default validation settings:

```php
class User extends DataMapper {
    
    public $validation = array(
        'username' => array(
            'label' => 'Username',
            'rules' => array('required', 'min_length' => 3, 'max_length' => 20)
        ),
        'email' => array(
            'label' => 'Email Address',
            'rules' => array('required', 'valid_email', 'unique')
        )
    );
}
```

## Caching Configuration

### Query Caching (DataMapper 2.0)

```php
// Enable query result caching
$user = (new User())
    ->where('active', 1)
    ->cache(3600) // Cache for 1 hour
    ->get();
```

### Production Cache

Enable production table structure caching:

```php
// config/datamapper.php
$config['production_cache'] = TRUE;
```

See [Production Cache](/guide/advanced/production-cache) for details.

## Common Configurations

### Blog Application

```php
// config/datamapper.php
$config['prefix'] = 'blog_';
$config['created_field'] = 'created_at';
$config['updated_field'] = 'updated_at';
$config['timestamp_format'] = 'Y-m-d H:i:s';
$config['extensions'] = array('json');
```

### Multi-Tenant Application

```php
// config/datamapper.php
$config['prefix'] = 'tenant_' . get_tenant_id() . '_';
$config['auto_transaction'] = TRUE;
```

### API Application

```php
// config/datamapper.php
$config['extensions'] = array('json', 'array');
$config['all_array_uses_ids'] = TRUE;
$config['auto_transaction'] = FALSE;
```

## Troubleshooting

### Configuration Not Loading

::: warning Check File Location
Ensure `datamapper.php` is in `application/config/` directory.
:::

```php
// Verify configuration is loaded
$CI =& get_instance();
print_r($CI->config->item('prefix'));
```

### Timestamps Not Working

```php
// Enable in config
$config['created_field'] = 'created_at';
$config['updated_field'] = 'updated_at';

// Or use trait (DataMapper 2.0)
use HasTimestamps;

class User extends DataMapper {
    use HasTimestamps;
}
```

### Table Prefix Issues

```php
// Check prefix is set
$config['prefix'] = 'app_';

// Verify table name
$user = new User();
echo $user->table; // Should be 'app_users'
```

## Next Steps

- [Database Setup](/guide/getting-started/database) - Configure your database
- [Controllers](/guide/getting-started/controllers) - Use DataMapper in controllers
- [Logging & Error Handling](#logging-datamapper-20) - Configure logging (2.0)
- [Production Cache](/guide/advanced/production-cache) - Optimize for production

::: tip Best Practices
- Use environment-specific configuration
- Enable auto-transactions in production
- Configure proper character encoding (utf8mb4)
- Use production cache in live environments
:::
