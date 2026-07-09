# Relationships

DataMapper makes it incredibly easy to define and work with database relationships using an elegant, intuitive syntax.

## Overview

Define relationships once in your model, then access related data naturally:

```php
class User extends DataMapper {
    public $has_many = ['post', 'comment'];
    public $has_one = ['profile'];
}

// Access relationships
$user = (new User())->find(1);

// Has one
echo $user->profile->bio;

// Has many
foreach ($user->post as $post) {
    echo $post->title;
}
```

## Relationship Types

DataMapper supports all common relationship types:

### One-to-One (Has One)

A user has one profile:

```php
class User extends DataMapper {
    public $has_one = ['profile'];
}

class Profile extends DataMapper {
    public $has_one = ['user'];
}
```

### One-to-Many (Has Many)

A user has many posts:

```php
class User extends DataMapper {
    public $has_many = ['post'];
}

class Post extends DataMapper {
    public $has_one = ['user'];
}
```

### Many-to-Many

A post has many tags, and tags belong to many posts:

```php
class Post extends DataMapper {
    public $has_many = ['tag'];
}

class Tag extends DataMapper {
    public $has_many = ['post'];
}
```

## Quick Examples

### Accessing Related Data

```php
// Get user with ID 1
$user = (new User())->find(1);

// Access related profile (has_one)
echo $user->profile->bio;

// Access related posts (has_many)
foreach ($user->post as $post) {
    echo $post->title;
}

// Count related posts
echo $user->post->count();
```

### Creating Relationships

```php
$user = (new User())->find(1);
$post = new Post();

$post->title = 'My First Post';
$post->content = 'Hello World!';

// Save and associate with user
$post->save($user);
```

### Querying Relationships

```php
// Get user's published posts
$user = (new User())->find(1);
$user->post->where('published', 1)->get();

foreach ($user->post as $post) {
    echo $post->title;
}
```

## DataMapper 2.0: Eager Loading

Eliminate N+1 query problems with eager loading:

### Without Eager Loading (N+1 Problem)

```php
// 1 query to get users
$users = (new User())->get();

foreach ($users as $user) {
    // +1 query per user to get posts!
    foreach ($user->post as $post) {
        echo $post->title;
    }
}
// Total: 1 + N queries (bad for performance)
```

### With Eager Loading (2 Queries)

```php
// Load users with their posts in 2 queries
$users = (new User())
    ->with('post')
    ->get();

foreach ($users as $user) {
    // Posts already loaded!
    foreach ($user->post as $post) {
        echo $post->title;
    }
}
// Total: 2 queries (excellent performance)
```

### Eager Loading with Constraints

```php
// Load users with only their published posts
$users = (new User())
    ->with([
        'post' => function($q) {
            $q->where('published', 1)
              ->order_by('created_at', 'DESC')
              ->limit(5);
        }
    ])
    ->get();
```

### Nested Eager Loading

```php
// Load users -> posts -> comments
$users = (new User())
    ->with([
        'post' => [
            'comment'  // Load comments for each post
        ]
    ])
    ->get();

foreach ($users as $user) {
    foreach ($user->post as $post) {
        foreach ($post->comment as $comment) {
            echo $comment->content;
        }
    }
}
```

## Naming Conventions

DataMapper uses sensible naming conventions:

### Table Names

- Model: `User` → Table: `users`
- Model: `Post` → Table: `posts`
- Model: `Category` → Table: `categories`

### Foreign Keys

- User has many posts → `posts.user_id`
- Post belongs to user → `posts.user_id`

### Join Tables (Many-to-Many)

- Post has many tags → `posts_tags`
- Format: `{table1}_{table2}` (alphabetical order)
- Columns: `post_id`, `tag_id`

## Real-World Example

### Blog System

```php
class User extends DataMapper {
    public $has_many = ['post', 'comment'];
    public $has_one = ['profile'];
}

class Post extends DataMapper {
    public $has_one = ['user', 'category'];
    public $has_many = ['comment', 'tag'];
}

class Comment extends DataMapper {
    public $has_one = ['user', 'post'];
}

class Tag extends DataMapper {
    public $has_many = ['post'];
}

class Category extends DataMapper {
    public $has_many = ['post'];
}

class Profile extends DataMapper {
    public $has_one = ['user'];
}
```

### Usage

```php
// Get post with all related data
$post = (new Post())
    ->with([
        'user' => ['profile'],
        'category',
        'tag',
        'comment' => function($q) {
            $q->where('approved', 1)
              ->order_by('created_at', 'DESC');
        }
    ])
    ->find(1);

// Display
echo $post->title;
echo $post->user->username;
echo $post->user->profile->avatar;
echo $post->category->name;

foreach ($post->tag as $tag) {
    echo $tag->name;
}

foreach ($post->comment as $comment) {
    echo $comment->user->username . ': ' . $comment->content;
}
```

## Performance Comparison

### Before (N+1 Queries)

```php
$users = (new User())->get();  // 1 query

foreach ($users as $user) {
    echo $user->username;
    
    $user->post->get();  // +1 query per user
    
    foreach ($user->post as $post) {
        echo $post->title;
        
        $post->comment->get();  // +1 query per post
        
        foreach ($post->comment as $comment) {
            echo $comment->content;
        }
    }
}
// Total: 1 + 100 users + (100 users × 10 posts) = 1,101 queries!
```

### After (Eager Loading)

```php
$users = (new User())
    ->with([
        'post' => ['comment']
    ])
    ->get();

foreach ($users as $user) {
    echo $user->username;
    
    foreach ($user->post as $post) {
        echo $post->title;
        
        foreach ($post->comment as $comment) {
            echo $comment->content;
        }
    }
}
// Total: 3 queries (99.7% reduction!)
```

## Learn More

Dive deeper into relationships:

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 2rem;">

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
    <h3>Relationship Types</h3>
  <p>Has One, Has Many, Many-to-Many</p>
  <a href="./types">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
    <h3>Accessing Relations</h3>
  <p>Load and query related data</p>
  <a href="./accessing">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
    <h3>Setting Relations</h3>
  <p>Create and modify relationships</p>
  <a href="./setting">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
    <h3>Saving Relations</h3>
  <p>Persist related data</p>
  <a href="./saving">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
    <h3>Deleting Relations</h3>
  <p>Remove relationships safely</p>
  <a href="./deleting">Learn More →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
    <h3>Eager Loading</h3>
  <p>Optimize with DataMapper 2.0</p>
  <a href="/guide/datamapper-2/eager-loading">Optimize Queries →</a>
</div>

</div>

## Best Practices

::: tip Define Both Sides
Always define relationships on both sides for clarity:

```php
class User extends DataMapper {
    public $has_many = ['post'];
}

class Post extends DataMapper {
    public $has_one = ['user'];
}
```
:::

::: warning Use Eager Loading
Always use eager loading when accessing relationships in loops to avoid N+1 queries.
:::

::: tip Naming Consistency
Follow DataMapper naming conventions for automatic detection of table and column names.
:::

## Next Steps

- [Relationship Types](/guide/relationships/types) - Detailed guide to all types
- [Eager Loading](/guide/datamapper-2/eager-loading) - Optimize performance
- [Advanced Usage](/guide/relationships/advanced) - Complex relationships
