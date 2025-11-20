# Introduction

Welcome to **DataMapper ORM 2.0** - a modern, powerful Active Record ORM for CodeIgniter 3.x that brings Laravel-style eloquence to your applications.

## What is DataMapper?

DataMapper is an Object-Relational Mapper (ORM) that provides an elegant Active Record implementation for working with your database. Each database table has a corresponding "Model" that interacts with that table.

```php
// Simple, expressive syntax
$users = (new User())
    ->where('active', 1)
    ->orderBy('name')
    ->get();

foreach ($users as $user) {
    echo $user->name;
}
```

## Why DataMapper?

### Built for CodeIgniter
Unlike generic ORMs, DataMapper is designed specifically for CodeIgniter 3.x. It integrates seamlessly with CI's ecosystem and follows CI conventions.

### Performance First
- **Query caching** - Automatic result caching
- **Eager loading** - Eliminate N+1 queries
- **Streaming** - Handle millions of records
- **Optimized SQL** - Efficient query generation

### Developer Experience
- **Modern query builder** - Chainable, readable queries
- **Type safety** - Attribute casting
- **Collections** - Rich array helpers
- **Validation** - Built-in validation rules

### Feature Rich
- **Relationships** - Has-many, belongs-to, many-to-many
- **Soft deletes** - Safe data removal
- **Timestamps** - Automatic tracking
- **Transactions** - ACID compliance
- **Subqueries** - Complex query building

## DataMapper 2.0 Highlights

::: info What's New in 2.0
Version 2.0 brings modern PHP patterns and performance optimizations to CodeMapper while maintaining full backward compatibility.
:::

### Modern Query Builder

```php
// Modern, chainable query builder syntax
$posts = (new Post())
    ->with(['user', 'comments'])
    ->where('published', 1)
    ->where('views >', 1000)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->cache(3600)
    ->get();
```

### Eager Loading with Constraints

```php
// Load only published posts with their latest 5 comments
$users = (new User())
    ->with([
        'post' => function($q) {
            $q->where('published', 1)
              ->orderBy('views', 'DESC');
        },
        'post.comment' => function($q) {
            $q->orderBy('created_at', 'DESC')
              ->limit(5);
        }
    ])
    ->get();
```

### Collection Methods

```php
$users = (new User())
    ->where('active', 1)
    ->get();

// Rich collection API
$emails = $users->pluck('email');
$adults = $users->filter(fn($u) => $u->age >= 18);
$names = $users->map(fn($u) => $u->first_name . ' ' . $u->last_name);
$total = $users->sum('credits');
```

### Soft Deletes

```php
use SoftDeletes;

class Post extends DataMapper {
    use SoftDeletes;
}

// Soft delete (sets deleted_at timestamp)
$post->delete();

// Query without deleted records (automatic)
$posts = (new Post())->get();

// Include deleted records
$allPosts = (new Post())->with_softdeleted()->get();

// Only deleted records
$deleted = (new Post())->only_softdeleted()->get();

// Restore
$post->restore();
```

## Quick Comparison

| Feature | DataMapper 2.0 | CodeIgniter Query Builder | Laravel Eloquent |
|---------|----------------|---------------------------|------------------|
| **Query Builder Syntax** | Yes | Basic | Yes |
| **Relationships** | Full | Manual | Full |
| **Eager Loading** | Advanced | No | Advanced |
| **Soft Deletes** | Trait | Manual | Trait |
| **Collections** | Rich | Arrays | Rich |
| **Caching** | Built-in | Manual | Manual |
| **Validation** | Built-in | No | Separate |
| **Learning Curve** | Easy | Easy | Medium |
| **CI3 Integration** | Perfect | Native | N/A |

## Philosophy

DataMapper follows these core principles:

### 1. Convention Over Configuration
```php
// Table name and relationships are auto-detected
class User extends DataMapper {
    public $has_many = ['post', 'comment'];  // That's it!
}
```

### 2. DRY (Don't Repeat Yourself)
```php
// Define validation rules once, use everywhere
public $validation = [
    'email' => [
        'rules' => ['required', 'valid_email', 'unique']
    ]
];
```

### 3. Backwards Compatibility
```php
// Old syntax still works!
$user = new User();
$user->where('id', 1);
$user->get();

// New syntax available when you want it
$user = (new User())->find(1);
```

## Who Uses DataMapper?

DataMapper powers thousands of CodeIgniter applications worldwide:

- **Enterprise apps** - Business management platforms
- **E-commerce** - Online stores with complex product catalogs
- **SaaS** - Multi-tenant applications
- **Healthcare** - Patient record systems
- **Education** - Learning management systems

## Next Steps

Ready to get started? Here's your path:

::: steps

### 1. Check Requirements
Make sure you have PHP 7.4+ and CodeIgniter 3.x installed.
[View Requirements →](/guide/getting-started/requirements)

### 2. Install DataMapper
Quick installation in under 5 minutes.
[Install Now →](/guide/getting-started/installation)

### 3. Build Your First Model
Create a model and start querying.
[Quick Start →](/guide/getting-started/quickstart)

### 4. Explore Features
Dive into advanced features like eager loading and caching.
[Browse Features →](/guide/datamapper-2/)

:::

## Community

- [GitHub Discussions](https://github.com/P2GR/datamapper/discussions) - Ask questions
- [Issue Tracker](https://github.com/P2GR/datamapper/issues) - Report bugs
- [Changelog](/help/changelog) - See what's new
- [Roadmap](/help/roadmap) - Future plans

---

<div style="text-align: center; margin-top: 3rem; padding: 2rem; background: var(--vp-c-bg-soft); border-radius: 12px;">

### Start Building Better Apps Today

DataMapper 2.0 makes database operations simple, fast, and enjoyable.

[Get Started](/guide/getting-started/installation){ .vp-button .brand style="margin: 0 0.5rem;" }
[View Usage Guides](/guide/datamapper-2/index){ .vp-button .alt style="margin: 0 0.5rem;" }

</div>
