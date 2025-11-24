# Roadmap

This page outlines the planned features and improvements for future versions of DataMapper ORM.

::: tip Community Input
We value your feedback! Suggest features or vote on existing proposals in our [GitHub Discussions](https://github.com/P2GR/datamapper/discussions).
:::

## Version 2.1 (Q2 2025) - Performance & DX

### Planned Features

#### 1. Query Performance Analyzer <Badge type="tip" text="new" />

Built-in query profiler to identify performance bottlenecks:

```php
$user = new User();
$user->with('posts')
     ->enableProfiler()
     ->get();

// View query performance
print_r($user->getProfilerStats());
```

**Benefits:**
- Identify slow queries
- Detect N+1 problems automatically
- Monitor eager loading efficiency
- Production-safe profiling

#### 2. Automatic Index Suggestions

DataMapper will analyze your queries and suggest missing indexes:

```php
// After running queries
$suggestions = DataMapper::getIndexSuggestions();
// [
//   "users table: Add index on 'status' column (used in 50 queries)",
//   "posts table: Add composite index on 'user_id, published_at'"
// ]
```

#### 3. Batch Operations <Badge type="tip" text="performance" />

Efficient bulk inserts and updates:

```php
// Batch insert
User::insert([
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com'],
    // ... 1000 more
]); // Single query!

// Batch update
User::whereIn('id', [1,2,3,4,5])
    ->update(['status' => 'active']); // Single query!
```

#### 4. Model Events <Badge type="tip" text="hooks" />

Laravel-style model events:

```php
class User extends DataMapper {
    
    protected function creating()
    {
        // Before creating
        $this->uuid = $this->generate_uuid();
    }
    
    protected function created()
    {
        // After created
        $this->send_welcome_email();
    }
    
    protected function updating()
    {
        // Before updating
    }
    
    protected function updated()
    {
        // After updated
        $this->clear_cache();
    }
}
```

#### 5. JSON Column Support

Native JSON column handling:

```php
class User extends DataMapper {
    protected $casts = [
        'preferences' => 'json'
    ];
}

$user = new User();
$user->preferences = ['theme' => 'dark', 'notifications' => true];
$user->save();

// Query JSON columns
$user->where('preferences->theme', 'dark')->get();
```

### Status: In Development 🚧

- [x] Planning complete
- [x] RFC published
- [ ] Implementation started (70%)
- [ ] Testing
- [ ] Beta release
- [ ] Stable release

**Expected Release:** June 2025

---

## Version 2.2 (Q4 2025) - Enterprise Features

### Planned Features

#### 1. Multi-Database Support <Badge type="tip" text="enterprise" />

Use different databases for different models:

```php
class User extends DataMapper {
    protected $connection = 'mysql_main';
}

class AnalyticsEvent extends DataMapper {
    protected $connection = 'postgres_analytics';
}

class CachedData extends DataMapper {
    protected $connection = 'redis_cache';
}
```

#### 2. Database Read/Write Splitting

Automatic read replica support:

```php
// Configure in config/database.php
$db['default']['write'] = 'mysql://master:3306/app';
$db['default']['read'] = [
    'mysql://replica1:3306/app',
    'mysql://replica2:3306/app',
    'mysql://replica3:3306/app'
];

// Automatic routing
$user->get(); // Read replica
$user->save(); // Master
```

#### 3. Database Migrations <Badge type="tip" text="devops" />

Built-in schema migrations:

```php
class CreateUsersTable extends DataMapper_Migration {
    
    public function up()
    {
        $this->create_table('users', [
            'id' => ['type' => 'int', 'auto_increment' => true],
            'name' => ['type' => 'varchar', 'length' => 255],
            'email' => ['type' => 'varchar', 'length' => 255, 'unique' => true],
            'created_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP']
        ]);
    }
    
    public function down()
    {
        $this->drop_table('users');
    }
}
```

#### 4. Database Seeding

Test data generation:

```php
class UserSeeder extends DataMapper_Seeder {
    
    public function run()
    {
        User::factory(100)->create();
        
        User::factory(10)->create([
            'role' => 'admin'
        ]);
    }
}
```

#### 5. Audit Logging

Automatic change tracking:

```php
use DataMapper\Auditable;

class User extends DataMapper {
    use Auditable;
}

// Automatic audit trail
$user->name = "New Name";
$user->save();

// View history
$history = $user->revisions();
// [
//   {column: 'name', old: 'Old Name', new: 'New Name', user_id: 5, timestamp: '...'}
// ]
```

### Status: Planning

- [ ] RFC open for feedback
- [ ] Community input period
- [ ] Design finalization
- [ ] Implementation

**Expected Release:** October 2025

---

## Version 3.0 (2026) - Modern PHP

### Major Changes

#### PHP 8.2+ Only

Leverage modern PHP features:

```php
// PHP 8 constructor property promotion
class User extends DataMapper {
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
    ) {
        parent::__construct();
    }
}

// Typed properties
class Post extends DataMapper {
    public string $title;
    public ?string $content = null;
    public PostStatus $status;
}

// Enums
enum PostStatus: string {
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
```

#### Attribute-Based Configuration

Replace arrays with PHP 8 attributes:

```php
use DataMapper\Attributes\{Table, HasMany, Validates};

#[Table('users')]
class User extends DataMapper {
    
    #[Validates(['required', 'email', 'unique'])]
    public string $email;
    
    #[HasMany(Post::class)]
    public Collection $posts;
    
    #[BelongsTo(Country::class)]
    public Country $country;
}
```

#### Async/Await Support

Non-blocking database operations:

```php
// Load multiple models in parallel
[$users, $posts, $comments] = await [
    User::where('active', true)->getAsync(),
    Post::where('published', true)->getAsync(),
    Comment::where('approved', true)->getAsync()
];
```

#### CodeIgniter 4 Support

Full compatibility with CodeIgniter 4.x:

```php
namespace App\Models;

use CodeIgniter\DataMapper\Model as DataMapper;

class User extends DataMapper {
    // CI4 features
}
```

### Status: Future Planning

- [ ] Community feedback
- [ ] Design phase
- [ ] Prototype

**Expected Release:** 2026

---

## Feature Requests

### Most Requested Features

Based on GitHub issues and community feedback:

| Feature | Votes | Status | Target Version |
|---------|-------|--------|----------------|
| Model Events | 45 | Planned | 2.1 |
| Multi-Database | 38 | Planned | 2.2 |
| JSON Columns | 35 | Planned | 2.1 |
| Migrations | 32 | Planned | 2.2 |
| Batch Operations | 28 | Planned | 2.1 |
| Audit Logging | 25 | Planned | 2.2 |
| Read/Write Split | 22 | Planned | 2.2 |
| Async Queries | 20 | Considering | 3.0 |
| GraphQL Support | 15 | Considering | TBD |
| MongoDB Support | 12 | Won't Add | - |

### Vote on Features

Want to influence the roadmap? 

1. Visit [GitHub Discussions](https://github.com/P2GR/datamapper/discussions)
2. Vote 👍 on existing proposals
3. Submit your own ideas

---

## Recently Completed

Features from the roadmap that have been completed:

### Version 2.0 (Released Dec 2024)

- [x] Query Builder
- [x] Eager Loading
- [x] Collections
- [x] Query Caching
- [x] Soft Deletes
- [x] Timestamps
- [x] Attribute Casting
- [x] Streaming Results
- [x] Advanced Query Building

---

## Long-Term Vision

### Goals for DataMapper ORM

1. **Best-in-class DX** - Make developers love using DataMapper
2. **Performance First** - Always optimize for speed and memory
3. **Modern PHP** - Embrace new PHP features and standards
4. **Enterprise Ready** - Support large-scale applications
5. **Community Driven** - Listen to and implement user feedback

### Principles

- Backward compatibility when possible
- Breaking changes only in major versions
- Comprehensive testing (95%+ coverage)
- Detailed documentation
- Active community support

---

## Get Involved

Help shape the future of DataMapper:

- [Discuss Features](https://github.com/P2GR/datamapper/discussions)
- [Report Bugs](https://github.com/P2GR/datamapper/issues)
- [Contribute Code](/help/contributing)
- [Improve Docs](https://github.com/P2GR/datamapper/tree/master/docs)
- [Star on GitHub](https://github.com/P2GR/datamapper)

## See Also

- [Changelog](/help/changelog) - Past releases
- [Contributing](/help/contributing) - How to help
- [GitHub Milestones](https://github.com/P2GR/datamapper/milestones) - Current progress
- [GitHub Discussions](https://github.com/P2GR/datamapper/discussions) - Join the conversation
