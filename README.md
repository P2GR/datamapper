# DataMapper ORM v2.0.0-beta1

[![PHP Version](https://img.shields.io/badge/PHP-5.4--8.5%2B-blue)](https://php.net)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-3.x-orange)](https://codeigniter.com)
[![License](https://img.shields.io/badge/License-MIT-green)](license.txt)
[![GitHub](https://img.shields.io/badge/GitHub-P2GR%2Fdatamapper-blue)](https://github.com/P2GR/datamapper)

A powerful Object-Relational Mapper (ORM) for CodeIgniter 3 with modern features and 100% backward compatibility.

## About

DataMapper ORM provides an elegant Active Record implementation for CodeIgniter 3, allowing you to interact with your database using objects instead of writing SQL queries. Version 2.0 introduces modern features while maintaining full compatibility with existing DataMapper 1.x code.

## What's New in v2.0

### Core Features
- **Query Builder** - Modern chainable query syntax for elegant database queries
- **Eager Loading** - Eliminate N+1 query problems with the with() method (96%+ query reduction)
- **Eager Loading with Constraints** - Filter related records at the database level for maximum efficiency
- **Enhanced Collections** - Powerful result set manipulation with map, filter, reduce, and more
- **100% Backward Compatible** - All existing DataMapper 1.x code works unchanged

### Data Management
- **Attribute Casting** - Automatic type conversion (int, bool, float, array, json, datetime, etc.)
- **Soft Deletes Trait** - Soft deletion with deleted_at timestamps (withTrashed, onlyTrashed, restore)
- **Timestamps Trait** - Automatic created_at and updated_at management with customizable formats
- **Query Caching** - Built-in caching with File/Redis/Memcached support for improved performance

### Performance & Scalability
- **Streaming & Chunking** - Process large datasets with minimal memory usage via chunk() and lazy()
- **Lazy Collections** - Memory-efficient lazy evaluation for massive datasets
- **Database-Level Filtering** - Apply constraints in eager loading to reduce data transfer (up to 80% reduction)
- **Server-Sent Events (SSE)** - Real-time streaming for CSV exports and batch processing

## Installation

Download the latest release from [GitHub Releases](https://github.com/P2GR/datamapper/releases)

For detailed installation instructions, see [Installation](https://datamapper.mss54.com/pages/installation.html).

## Documentation

Complete documentation is available at: **[datamapper.mss54.com](http://datamapper.mss54.com)**

> **Note:** The legacy `manual/` HTML bundle has been removed from the repository. The docs are now maintained in the `docs/` directory and published to the site above.

### Quick Links

- [Getting Started Guide](http://datamapper.mss54.com/pages/gettingstarted.html)
- [Query Builder](https://datamapper.mss54.com/guide/datamapper-2/query-builder)
- [Relationships](http://datamapper.mss54.com/pages/relationtypes.html)
- [Validation](http://datamapper.mss54.com/pages/validation.html)
- [Quick Reference](http://datamapper.mss54.com/pages/quickref.html)

## Requirements

- PHP 7.4 or higher (PHP 8.4+ recommended)
- CodeIgniter 3.x
- MySQL, PostgreSQL, SQLite, or any database supported by CodeIgniter

## Quick Examples

### Soft Deletes
```php
class Post extends DataMapper {
    use SoftDeletes;
}

$post = (new Post())->find(1);
$post->delete(); // Soft delete (sets deleted_at)

// Query only non-deleted (default)
$posts = (new Post())->get();

// Include soft-deleted records
$all = (new Post())->withTrashed()->get();

// Only soft-deleted records
$trashed = (new Post())->onlyTrashed()->get();

// Restore soft-deleted
$post->restore();

// Permanently delete
$post->forceDelete();
```

### Automatic Timestamps
```php
class User extends DataMapper {
    use HasTimestamps;
}

$user = new User();
$user->name = 'John Doe';
$user->save(); // Automatically sets created_at and updated_at

$user->name = 'Jane Doe';
$user->save(); // Automatically updates updated_at
```

### Eager Loading with Constraints
```php
// Load users with only active installations
$users = (new User())
    ->with('installation', function($q) {
        $q->where('active', 1);
        $q->order_by('created_at', 'DESC');
        $q->limit(10);
    })
    ->get();

// Include soft-deleted relations
$users = (new User())
    ->with('posts', function($q) {
        $q->withTrashed(); // Include deleted posts
    })
    ->get();
```

### Memory-Efficient Streaming
```php
// Process 1 million records with minimal memory
(new User())
    ->where('active', 1)
    ->chunk(1000, function($users) {
        foreach ($users as $user) {
            // Process each batch
            $user->send_email();
        }
    });

// Lazy collections for chained operations
$emails = (new User())
    ->lazy(500)
    ->map(fn($user) => $user->email)
    ->filter(fn($email) => str_contains($email, '@gmail.com'))
    ->take(1000);
```

## Requirements

- PHP 7.4 or higher (tested through PHP 8.5)
- CodeIgniter 3.x
- MySQL, PostgreSQL, SQLite, or any database supported by CodeIgniter

## Credits

### Original Authors

- **Simon Stenhouse (Stensi)** - Original DataMapper creator
- **Harro Verton (WanWizard)** - DataMapper 1.x maintenance and improvements

### DataMapper 2.0

- **Maintained by [P2GR](https://github.com/P2GR)** - Version 2.0 development and maintenance
- **Maintained by [KayElliot](https://github.com/kayelliot)** - Version 2.0 development and maintenance

### Community

Special thanks to all contributors who have helped improve DataMapper over the years.

## License

DataMapper ORM is open-sourced software licensed under the [MIT License](license.txt).

## Links

- **Documentation**: [datamapper.mss54.com](http://datamapper.mss54.com)
- **Repository**: [github.com/P2GR/datamapper](https://github.com/P2GR/datamapper)
- **Download**: [Latest Release](https://github.com/P2GR/datamapper/releases)
- **Issues**: [Bug Reports & Feature Requests](https://github.com/P2GR/datamapper/issues)
