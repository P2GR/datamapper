# Upgrading DataMapper

Guide to upgrading DataMapper to the latest version safely.

## Version 2.0 Upgrade

### What's New in 2.0

DataMapper 2.0 introduces modern PHP features while maintaining **100% backward compatibility**.

::: tip Backward Compatible
All your existing DataMapper 1.x code will continue to work without changes!
:::

**New Features:**
- ✨ Modern query builder
- 🚀 Eager loading with constraints
- 📦 Collection methods
- ⚡ Query caching
- 🗑️ Soft deletes
- 🕐 Automatic timestamps
- 🔄 Attribute casting
- 📊 Streaming results

### Requirements

| Version | PHP | CodeIgniter |
|---------|-----|-------------|
| **2.0.x** | 7.4 - 8.3+ | 3.1.x |
| **1.8.x** | 5.6 - 7.4 | 2.x / 3.x |

### Upgrade Steps

#### 1. Backup Your Database

```sql
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

#### 2. Backup Your Files

```bash
# Backup models and libraries
cp -r application/models models_backup
cp -r application/libraries/datamapper.php datamapper_backup.php
```

#### 3. Download DataMapper 2.0

```bash
# Via Git
git clone https://github.com/P2GR/datamapper.git
cd datamapper
git checkout datamapper2

# Or download ZIP from GitHub
```

#### 4. Replace Core Files

Replace these files with 2.0 versions:

```
application/libraries/
├── datamapper.php                          # Core library (REPLACE)
├── DataMapperBackwardCompatibility.php     # New file (ADD)
└── datamapper/                             # Extensions folder
    ├── HasTimestamps.php                   # New  
    ├── SoftDeletes.php                     # New
    ├── attributecasting.php                # New
    └── ...

application/config/
└── datamapper.php                          # Config (UPDATE)
```

::: warning Don't Replace Models
Your model files in `application/models/` should NOT be replaced!
:::

#### 5. Test Your Application

Run your test suite or manually test key features:

```php
// Test basic CRUD
$user = new User();
$user->where('id', 1)->get();
echo $user->username;

// Test relationships
$user->post->get();
foreach ($user->post as $post) {
    echo $post->title;
}
```

#### 6. Gradually Adopt New Features

You can now use 2.0 features alongside old syntax:

```php
// Old syntax still works
$user = new User();
$user->where('active', 1);
$user->get();

// New query builder syntax available
$users = (new User())->where('active', 1)->get();
```

## Incremental Migration

### Phase 1: Drop-in Replacement

Just replace the library files. Everything works as before.

### Phase 2: Add Traits to Models

Add modern features to models one at a time:

```php
use HasTimestamps;
use SoftDeletes;

class User extends DataMapper {
    use HasTimestamps, SoftDeletes;
    
    public $has_many = ['post', 'comment'];
    
    // Rest of your existing code...
}
```

### Phase 3: Adopt the Query Builder

Gradually refactor to the new query builder syntax:

```php
// Before
$user = new User();
$user->where('active', 1);
$user->order_by('created_at', 'DESC');
$user->limit(10);
$user->get();

// After
$users = (new User())
    ->where('active', 1)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();
```

### Phase 4: Add Eager Loading

Optimize queries with eager loading:

```php
// Before (N+1 queries)
$users = new User();
$users->get();

foreach ($users as $user) {
    foreach ($user->post as $post) {  // Extra query!
        echo $post->title;
    }
}

// After (2 queries)
$users = (new User())
    ->with('post')
    ->get();

foreach ($users as $user) {
    foreach ($user->post as $post) {  // Already loaded!
        echo $post->title;
    }
}
```

### Key Differences from 1.x (What to Update)

| Legacy pattern | 2.0 replacement | Benefit |
|----------------|-----------------|---------|
| `$post->include_related('user', 'name')` to copy columns onto the base model | `(new Post())->with('user')` and read `$post->user->name` | Keeps models normalized, supports constraints, avoids column collisions |
| Chaining `include_related()` multiple times to join through relationships | Nested eager loading: `with('user.company', fn($q) => ...)` | One round-trip per relation, can filter/limit at the DB level |
| Setting `$config['auto_populate_has_one'] = TRUE` to always pull relations | Leave auto-populate disabled (default) and opt-in with `with()` | Eliminates hidden queries and memory spikes, makes loading explicit |
| Manually decoding JSON attributes in accessors | Enable `AttributeCasting` with `$casts = ['settings' => 'json']` | Automatic hydration/serialization in both directions |
| Writing log wrappers and calling `DMZ_Logger::debug()` | Call `dmz_log_message()` which proxies to CodeIgniter’s `log_message()` | Honors CI thresholds/handlers and removes duplicate log pipelines |
| Manually updating `created_at`/`updated_at` fields | Add the `HasTimestamps` trait | Consistent timestamp management without boilerplate |

The legacy APIs still work, but updating to the new patterns unlocks the biggest performance wins of 2.0.

## Breaking Changes

::: danger None!
DataMapper 2.0 has **ZERO breaking changes**. All 1.x code continues to work.
:::

### Deprecated Features

Some features are deprecated but still work:

| Deprecated | Use Instead |
|------------|-------------|
| `$user->stored` | `$user->exists()` |
| Manual timestamp handling | `HasTimestamps` trait |
| Manual soft delete logic | `SoftDeletes` trait |

## Database Changes

### Optional: Add Timestamp Columns

If using `HasTimestamps` trait:

```sql
ALTER TABLE users 
ADD COLUMN created_at DATETIME NULL DEFAULT NULL,
ADD COLUMN updated_at DATETIME NULL DEFAULT NULL;
```

### Optional: Add Soft Delete Column

If using `SoftDeletes` trait:

```sql
ALTER TABLE users 
ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL;
```

### Migration Script

Create a migration script:

```php
// application/migrations/001_add_datamapper_2_columns.php
class Migration_Add_datamapper_2_columns extends CI_Migration {
    
    public function up() {
        $tables = ['users', 'posts', 'comments'];
        
        foreach ($tables as $table) {
            // Add timestamps
            $this->dbforge->add_column($table, [
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => TRUE,
                    'default' => NULL
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => TRUE,
                    'default' => NULL
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => TRUE,
                    'default' => NULL
                ]
            ]);
        }
    }
    
    public function down() {
        $tables = ['users', 'posts', 'comments'];
        
        foreach ($tables as $table) {
            $this->dbforge->drop_column($table, 'created_at');
            $this->dbforge->drop_column($table, 'updated_at');
            $this->dbforge->drop_column($table, 'deleted_at');
        }
    }
}
```

Run migration:

```php
$this->load->library('migration');
$this->migration->current();
```

## Performance Optimization

After upgrading, optimize performance:

### 1. Enable Production Cache

```php
// config/datamapper.php
$config['production_cache'] = TRUE;
```

### 2. Use Eager Loading

Replace N+1 queries with eager loading:

```php
// Instead of this
$users = (new User())->get();
foreach ($users as $user) {
    $user->post->get();  // N queries
}

// Do this
$users = (new User())->with('post')->get();  // 2 queries
```

### 3. Enable Query Caching

```php
$users = (new User())
    ->where('active', 1)
    ->cache(3600)  // Cache for 1 hour
    ->get();
```

## Rollback Plan

If you need to rollback:

### 1. Restore Backup Files

```bash
cp datamapper_backup.php application/libraries/datamapper.php
cp -r models_backup/* application/models/
```

### 2. Restore Database

```sql
mysql -u username -p database_name < backup_20251013.sql
```

### 3. Clear Cache

```bash
rm -rf application/cache/datamapper/*
```

## Testing Checklist

Before deploying to production:

- [ ] All existing queries work
- [ ] Relationships load correctly
- [ ] Validation rules function
- [ ] Save/update operations succeed
- [ ] Delete operations work
- [ ] Custom methods still function
- [ ] Performance is maintained or improved
- [ ] No PHP errors or warnings

## Common Issues

### "Class not found" Errors

```php
// Solution: Check autoload.php
$autoload['libraries'] = array('database', 'datamapper');
```

### Timestamps Not Updating

```php
// Solution: Use the trait
use HasTimestamps;

class User extends DataMapper {
    use HasTimestamps;
}
```

### Soft Deletes Not Working

```php
// Solution: Use the trait and add deleted_at column
use SoftDeletes;

class User extends DataMapper {
    use SoftDeletes;
}
```

## Version History

### Version 2.0.0 (2025)
- ✨ Modern query builder
- 🚀 Eager loading with constraints
- 📦 Collections
- ⚡ Query caching
- 🗑️ Soft deletes trait
- 🕐 Timestamps trait
- 🔄 Attribute casting
- 📊 Streaming results

### Version 1.8.x (2016-2024)
- Stable release for PHP 5.6-7.4
- CodeIgniter 2.x/3.x support

## Getting Help

If you encounter issues:

1. **Check documentation**: [Troubleshooting](/help/troubleshooting)
2. **Search issues**: [GitHub Issues](https://github.com/P2GR/datamapper/issues)
3. **Ask community**: [GitHub Discussions](https://github.com/P2GR/datamapper/discussions)
4. **Report bugs**: [New Issue](https://github.com/P2GR/datamapper/issues/new)

## Next Steps

After upgrading:

- [Query Builder](/guide/datamapper-2/query-builder) - Learn modern syntax
- [Eager Loading](/guide/datamapper-2/eager-loading) - Optimize queries
- [Collections](/guide/datamapper-2/collections) - Work with results
- [Soft Deletes](/guide/datamapper-2/soft-deletes) - Safe deletions
- [Timestamps](/guide/datamapper-2/timestamps) - Auto timestamps

::: tip Take Your Time
You don't need to adopt all features at once. Upgrade incrementally at your own pace!
:::
