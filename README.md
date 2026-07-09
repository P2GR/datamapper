# DataMapper ORM v2.0.0-beta1

[![CI](https://img.shields.io/github/actions/workflow/status/P2GR/datamapper/ci.yml?branch=2.0.0-beta1&label=CI&logo=github)](https://github.com/P2GR/datamapper/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-7.4--8.5%2B-blue)](https://php.net)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-3.x-orange)](https://codeigniter.com)
[![License](https://img.shields.io/badge/License-MIT-green)](license.txt)

A powerful Object-Relational Mapper (ORM) for CodeIgniter 3 with modern features and 100% backward compatibility.

## About

DataMapper ORM provides an elegant Active Record implementation for CodeIgniter 3, allowing you to interact with your database using objects instead of writing SQL queries. Version 2.0 introduces modern features while maintaining full compatibility with existing DataMapper 1.x code.

## What's New in v2.0

### Query & Performance
- **[Query Builder](https://datamapper.mss54.com/guide/datamapper-2/query-builder)** — Modern chainable query syntax
- **[Eager Loading](https://datamapper.mss54.com/guide/datamapper-2/eager-loading)** — Eliminate N+1 problems with `with()` (96%+ query reduction)
- **[Collections](https://datamapper.mss54.com/guide/datamapper-2/collections)** — `collect()`, `pluck()`, `value()`, `first()`, map/filter/reduce
- **[Streaming & Chunking](https://datamapper.mss54.com/guide/datamapper-2/streaming)** — Process millions of rows with `chunk()` and `lazy()`
- **[Query Caching](https://datamapper.mss54.com/guide/datamapper-2/caching)** — Built-in File, Redis, and Memcached support
- **[Query Scopes](https://datamapper.mss54.com/guide/datamapper-2/query-scopes)** — Reusable query constraints via `scope_` methods

### Data Management
- **[Attribute Casting](https://datamapper.mss54.com/guide/datamapper-2/casting)** — Automatic type conversion (int, bool, float, array, json, datetime)
- **[Soft Deletes](https://datamapper.mss54.com/guide/datamapper-2/soft-deletes)** — Trait-based soft deletion with `deleted_at` timestamps
- **[Timestamps](https://datamapper.mss54.com/guide/datamapper-2/timestamps)** — Automatic `created_at`/`updated_at` management
- **[Dirty Tracking](https://datamapper.mss54.com/guide/datamapper-2/dirty-tracking)** — `is_dirty()`, `is_clean()`, `get_dirty()`, `get_original()`, `was_changed()`
- **[Serialization Control](https://datamapper.mss54.com/guide/datamapper-2/serialization)** — `$hidden`, `$visible`, `$appends` for API-safe output

### Model Lifecycle
- **[Model Events](https://datamapper.mss54.com/guide/datamapper-2/model-events)** — `before_save`, `after_save`, `before_create`, `after_create`, `before_delete`, etc.
- **[Model Utilities](https://datamapper.mss54.com/guide/datamapper-2/model-utilities)** — `increment()`, `decrement()`, `replicate()`, `fresh()`, `tap()`, `destroy()`
- **Mass Assignment Protection** — `$fillable` / `$guarded` with `fill()` and `create()`

## Requirements

- PHP 7.4 or higher (tested through PHP 8.5)
- CodeIgniter 3.x
- MySQL, PostgreSQL, SQLite, or any CI-supported database

## Installation

```bash
# Clone or download into your application directory
git clone https://github.com/P2GR/datamapper.git
```

Copy the contents of `application/` into your CodeIgniter `application/` folder. See the [Installation Guide](https://datamapper.mss54.com/guide/getting-started/installation) for details.

## Quick Examples

### Basic CRUD
```php
class User extends DataMapper {
    use HasTimestamps, SoftDeletes;

    public $has_many = array('post', 'comment');
}

// Create
$user = new User();
$user->name = 'Jane Doe';
$user->email = 'jane@example.com';
$user->save(); // created_at and updated_at set automatically

// Read
$user = (new User())->get_by_id(1);
echo $user->name;

// Update
$user->name = 'Jane Smith';
$user->save(); // updated_at refreshed automatically

// Delete
$user->delete(); // soft-deleted (sets deleted_at)
$user->restore(); // undo soft delete
$user->force_delete(); // permanent removal
```

### Eager Loading with Constraints
```php
$users = (new User())
    ->with('post', function($q) {
        $q->where('status', 'published');
        $q->order_by('created_at', 'DESC');
        $q->limit(5);
    })
    ->where('active', 1)
    ->get();
```

### Query Scopes
```php
class Post extends DataMapper {
    public function scope_published() {
        return $this->where('status', 'published');
    }

    public function scope_recent($days = 7) {
        return $this->where('created_at >', date('Y-m-d', strtotime("-{$days} days")));
    }
}

// Chain scopes naturally
$posts = (new Post())->published()->recent(30)->get();
```

### Dirty Tracking & Model Events
```php
class Article extends DataMapper {
    protected function before_save() {
        if ($this->is_dirty('title')) {
            $this->slug = url_title($this->title, '-', TRUE);
        }
    }

    protected function after_save() {
        log_message('info', 'Saved: ' . implode(', ', array_keys($this->get_changes())));
    }
}
```

### Serialization Control
```php
class User extends DataMapper {
    public $hidden = array('password', 'api_token');
    public $appends = array('full_name');

    public function get_full_name_attribute() {
        return $this->first_name . ' ' . $this->last_name;
    }
}

$user->to_array();
// ['id' => 1, 'first_name' => 'Jane', 'last_name' => 'Doe', 'full_name' => 'Jane Doe']
// password and api_token are excluded
```

### Collections & Streaming
```php
// Fluent collection pipeline
$emails = (new User())
    ->where('active', 1)
    ->collect()
    ->map(function($u) { return $u->email; })
    ->filter(function($e) { return str_contains($e, '@gmail.com'); });

// Process millions of rows with constant memory
(new User())->chunk(1000, function($batch) {
    foreach ($batch as $user) {
        $user->send_reminder();
    }
});
```

### Model Utilities
```php
// Atomic counters (no race conditions)
$post->increment('views');
$post->decrement('stock', 5);

// Clone a record
$draft = $post->replicate(['id', 'published_at']);
$draft->status = 'draft';
$draft->save();

// Bulk delete by ID
Post::destroy(array(1, 2, 3));
```

## Documentation

Full documentation: **[datamapper.mss54.com](https://datamapper.mss54.com)**

- [Getting Started](https://datamapper.mss54.com/guide/getting-started/installation)
- [Query Builder](https://datamapper.mss54.com/guide/datamapper-2/query-builder)
- [Eager Loading](https://datamapper.mss54.com/guide/datamapper-2/eager-loading)
- [DataMapper 2.0 Features](https://datamapper.mss54.com/guide/datamapper-2/)
- [Quick Reference](https://datamapper.mss54.com/reference/quick-reference)

## Credits

### Original Authors

- **Simon Stenhouse (Stensi)** — Original DataMapper creator
- **Harro Verton (WanWizard)** — DataMapper 1.x maintenance and improvements

### DataMapper 2.0

- **[P2GR](https://github.com/P2GR)** — Version 2.0 development and maintenance
- **[KayElliot](https://github.com/kayelliot)** — Version 2.0 development and maintenance

## License

DataMapper ORM is open-sourced software licensed under the [MIT License](license.txt).


