# Frequently Asked Questions

Common questions and answers about DataMapper ORM.

## General

### What is DataMapper?

DataMapper is an Object-Relational Mapper (ORM) for CodeIgniter 3.x that provides an elegant Active Record implementation. It allows you to interact with your database using object-oriented syntax instead of writing raw SQL.

### Is DataMapper 2.0 backward compatible?

Yes. DataMapper 2.0 keeps the classic DataMapper API available while adding new opt-in helpers.

### Which PHP version do I need?

- **DataMapper 2.0**: PHP 7.4 - 8.3+
- **DataMapper 1.8**: PHP 5.6 - 7.4

### Does it work with CodeIgniter 4?

Not yet. DataMapper 2.0 is designed for CodeIgniter 3.x. CodeIgniter 4 support is planned for a future release.

## Installation & Setup

### How do I install DataMapper?

1. Download from [GitHub](https://github.com/P2GR/datamapper)
2. Copy files to your CodeIgniter application
3. Load the library in autoload.php
4. Create your first model

See the [Installation Guide](/guide/getting-started/installation) for details.

### Do I need Composer?

No, DataMapper works without Composer. However, Composer can be used for autoloading if you prefer.

### Can I use it with existing databases?

Yes! DataMapper works with existing databases. Just create models that match your table structure.

## Models & CRUD

### How do I create a model?

Create a class that extends DataMapper:

```php
<?php
class User extends DataMapper {
    public function __construct($id = NULL) {
        parent::__construct($id);
    }
}
```

See [Creating Models](/guide/models/creating) for details.

### What naming conventions does DataMapper use?

- **Model class**: Singular, capitalized (e.g., `User`)
- **Table name**: Plural, lowercase (e.g., `users`)
- **Foreign keys**: `{model}_id` (e.g., `user_id`)
- **Join tables**: `{table1}_{table2}` (alphabetical, e.g., `posts_tags`)

### Can I override naming conventions?

Yes! You can specify custom table names:

```php
class User extends DataMapper {
    public $table = 'app_users';
    public $model = 'user';
}
```

### How do I handle timestamps?

**DataMapper 2.0**: Use the `HasTimestamps` trait:

```php
use HasTimestamps;

class User extends DataMapper {
    use HasTimestamps;
}
```

This automatically manages `created_at` and `updated_at` columns.

## Relationships

### How do I define relationships?

Use `$has_one` and `$has_many` properties:

```php
class User extends DataMapper {
    public $has_many = ['post', 'comment'];
    public $has_one = ['profile'];
}
```

See [Relationships](/guide/relationships/) for details.

### What relationship types are supported?

- **One-to-One** (Has One)
- **One-to-Many** (Has Many)
- **Many-to-Many** (Has Many on both sides)

### How do I avoid N+1 queries?

**DataMapper 2.0**: Use eager loading:

```php
$users = (new User())
    ->with('post')
    ->get();
```

This loads users and their posts in just 2 queries instead of N+1.

See [Eager Loading](/guide/datamapper-2/eager-loading).

## DataMapper 2.0

### What's new in 2.0?

Major features:
- Modern query builder
- Eager loading with constraints
- Collection methods
- Query caching
- Soft deletes trait
- Timestamps trait
- Attribute casting
- Streaming results

See [What's New](/guide/datamapper-2/) for details.

### Should I upgrade to 2.0?

Yes, if you want the 2.0 helpers. You can upgrade existing code first and adopt query-builder, eager-loading, caching, casting, and streaming features gradually.

### How do I use the new query builder syntax?

Instead of:
```php
$user = new User();
$user->where('active', 1);
$user->get();
```

Use:
```php
$users = (new User())->where('active', 1)->get();
```

See [Query Builder](/guide/datamapper-2/query-builder).

## Performance

### How can I improve query performance?

1. **Use eager loading** to eliminate N+1 queries
2. **Enable query caching** for frequently-run queries
3. **Use production cache** for table structure
4. **Index your database** properly
5. **Use select()** to limit returned columns

### What is the N+1 problem?

```php
// N+1 problem (bad!)
$users = (new User())->get();  // 1 query

foreach ($users as $user) {
    $user->post->get();  // +1 query per user!
}
// Total: 1 + N queries
```

**Solution**: Use eager loading:

```php
$users = (new User())->with('post')->get();  // users query + posts query
```

### How do I enable caching?

**DataMapper 2.0**: Use the `cache()` method:

```php
$users = (new User())
    ->where('active', 1)
    ->cache(3600)  // Cache for 1 hour
    ->get();
```

## Validation

### How do I validate data?

Define validation rules in your model:

```php
public $validation = [
    'username' => [
        'label' => 'Username',
        'rules' => ['required', 'min_length' => 3, 'unique']
    ],
    'email' => [
        'label' => 'Email',
        'rules' => ['required', 'valid_email', 'unique']
    ]
];
```

DataMapper automatically validates when you call `save()`.

### How do I display validation errors?

```php
if (!$user->save()) {
    // Display all errors
    echo $user->error->string;
    
    // Or individual errors
    foreach ($user->error->all as $field => $error) {
        echo "$field: $error<br>";
    }
}
```

## Soft Deletes

### What are soft deletes?

Instead of permanently deleting records, soft deletes set a `deleted_at` timestamp. The record remains in the database but is excluded from normal queries.

### How do I use soft deletes?

**DataMapper 2.0**: Use the `SoftDeletes` trait:

```php
use SoftDeletes;

class User extends DataMapper {
    use SoftDeletes;
}

// Soft delete
$user->delete();  // Sets deleted_at

// Include deleted records
$users = (new User())->with_softdeleted()->get();

// Restore
$user->restore();
```

See [Soft Deletes](/guide/datamapper-2/soft-deletes).

## Troubleshooting

### "Class 'DataMapper' not found"

Make sure DataMapper is loaded in `application/config/autoload.php`:

```php
$autoload['libraries'] = ['database', 'datamapper'];
```

### "Table doesn't exist"

Check:
1. Table name follows conventions (`users` for `User` model)
2. Database connection is configured correctly
3. You've created the table in your database

### Relationships aren't loading

Check:
1. Relationship is defined on both sides
2. Foreign key column exists (`user_id` for User model)
3. Column naming follows conventions

### Timestamps aren't updating

**DataMapper 2.0**: Make sure you're using the trait:

```php
use HasTimestamps;

class User extends DataMapper {
    use HasTimestamps;
}
```

And that columns exist in database:
```sql
ALTER TABLE users 
ADD COLUMN created_at DATETIME,
ADD COLUMN updated_at DATETIME;
```

## Migrations

### Does DataMapper support migrations?

DataMapper works with CodeIgniter's migration system. You can use migrations to create and modify tables.

### How do I add timestamp columns?

```php
$this->dbforge->add_column('users', [
    'created_at' => [
        'type' => 'DATETIME',
        'null' => TRUE
    ],
    'updated_at' => [
        'type' => 'DATETIME',
        'null' => TRUE
    ]
]);
```

## Advanced

### Can I use raw SQL queries?

Yes:

```php
$user = new User();
$user->query('SELECT * FROM users WHERE active = 1');
```

Or use CodeIgniter's Query Builder:

```php
$this->db->query('SELECT * FROM users WHERE id = ?', array(1));
```

### How do I use transactions?

```php
$user = new User();
$user->trans_begin();

$user->username = 'john';
$user->save();

$post = new Post();
$post->title = 'My Post';
$post->save($user);

if ($user->trans_status() === FALSE) {
    $user->trans_rollback();
} else {
    $user->trans_commit();
}
```

See [Transactions](/guide/advanced/transactions).

### Can I add custom methods to models?

Yes! Models are regular PHP classes:

```php
class User extends DataMapper {
    
    public function activate() {
        $this->active = 1;
        $this->activated_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    public function getFullName() {
        return $this->first_name . ' ' . $this->last_name;
    }
}
```

## Getting Help

### Where can I get help?

- **Documentation**: You're reading it!
- **GitHub Discussions**: [Ask questions](https://github.com/P2GR/datamapper/discussions)
- **GitHub Issues**: [Report bugs](https://github.com/P2GR/datamapper/issues)
- **Troubleshooting**: [Common issues](/help/troubleshooting)

### How do I report a bug?

1. Check if it's already reported in [GitHub Issues](https://github.com/P2GR/datamapper/issues)
2. Create a minimal reproducible example
3. Open a new issue with details

### How can I contribute?

We welcome contributions! See [Contributing](/help/contributing).

---

## Still Have Questions?

- [Troubleshooting Guide](/help/troubleshooting)
- [GitHub Discussions](https://github.com/P2GR/datamapper/discussions)
- [Usage Guides](/guide/datamapper-2/index)

::: tip Can't Find Your Answer?
Ask on [GitHub Discussions](https://github.com/P2GR/datamapper/discussions) - our community is happy to help!
:::
