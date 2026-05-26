# Relationship Types

DataMapper provides powerful relationship management through four relationship types. Understanding these relationships is key to building well-structured applications.

## Overview

DataMapper supports four relationship types:

| Type | Description | Foreign Key Location | Example |
|------|-------------|---------------------|---------|
| **has_one** | One-to-one | Related table | User has one Profile |
| **has_many** | One-to-many | Related table | User has many Posts |
| **belongs_to** | Inverse of has_one/has_many | Current table | Post belongs to User |
| **has_and_belongs_to_many** | Many-to-many | Join table | Post has many Tags |

## Has One

A **has_one** relationship defines a one-to-one relationship where the foreign key is in the related table.

### Definition

```php
class User extends DataMapper {
    var $has_one = array('profile');
}

class Profile extends DataMapper {
    var $has_one = array('user');
}
```

### Database Schema

```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    name VARCHAR(255)
);

CREATE TABLE profiles (
    id INT PRIMARY KEY,
    user_id INT,  -- Foreign key
    bio TEXT,
    website VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Usage

::: code-group

```php [Accessing]
$user = new User();
$user->get_by_id(1);

// Access related profile
$user->profile->get();
echo $user->profile->bio;
```

```php [Creating]
$user = new User();
$user->name = "John Doe";
$user->save();

$profile = new Profile();
$profile->bio = "Web developer";
$profile->website = "johndoe.com";

// Save relationship
$user->save($profile);
```

```php [Querying]
$user = new User();
$user->where_related('profile', 'website', 'johndoe.com')
     ->get();
```

:::

## Has Many

A **has_many** relationship defines a one-to-many relationship where the foreign key is in the related table.

### Definition

```php
class User extends DataMapper {
    var $has_many = array('post');
}

class Post extends DataMapper {
    var $has_many = array();
}
```

### Database Schema

```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    name VARCHAR(255)
);

CREATE TABLE posts (
    id INT PRIMARY KEY,
    user_id INT,  -- Foreign key
    title VARCHAR(255),
    content TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Usage

::: code-group

```php [Accessing]
$user = new User();
$user->get_by_id(1);

// Get all posts
$user->post->get();

foreach ($user->post as $post) {
    echo $post->title;
}
```

```php [Creating]
$user = new User();
$user->get_by_id(1);

$post = new Post();
$post->title = "My Article";
$post->content = "Content here...";

// Save relationship
$user->save($post);
```

```php [Querying]
$user = new User();
$user->where_related('post', 'published', 1)
     ->get();

echo "Users with published posts: " . $user->result_count();
```

:::

### Multiple Has Many

You can have multiple has_many relationships:

```php
class User extends DataMapper {
    var $has_many = array(
        'post',
        'comment',
        'like'
    );
}
```

## Belongs To <Badge type="tip" text="inverse" />

**belongs_to** is the inverse of has_one and has_many. It's optional but recommended for clarity.

### Definition

```php
class Post extends DataMapper {
    var $belongs_to = array('user');
}

class User extends DataMapper {
    var $has_many = array('post');
}
```

::: info Foreign Key Location
With `belongs_to`, the foreign key (`user_id`) is in the **current table** (posts), not the related table (users).
:::

### Usage

```php
$post = new Post();
$post->get_by_id(1);

// Access parent user
$post->user->get();
echo "Posted by: " . $post->user->name;
```

## Has and Belongs to Many

A **has_and_belongs_to_many** (or **many-to-many**) relationship uses a join table to connect two models.

### Definition

```php
class Post extends DataMapper {
    var $has_many = array('tag');
}

class Tag extends DataMapper {
    var $has_many = array('post');
}
```

### Database Schema

```sql
-- Primary tables
CREATE TABLE posts (
    id INT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT
);

CREATE TABLE tags (
    id INT PRIMARY KEY,
    name VARCHAR(100)
);

-- Join table (automatically detected)
CREATE TABLE posts_tags (
    id INT PRIMARY KEY,
    post_id INT,
    tag_id INT,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (tag_id) REFERENCES tags(id),
    UNIQUE KEY (post_id, tag_id)
);
```

::: tip Join Table Naming
DataMapper auto-detects join tables named: `{table1}_{table2}` (alphabetically sorted)
- `posts_tags` (p comes before t)
- `tags_posts` (wrong order)
:::

### Usage

::: code-group

```php [Adding Tags]
$post = new Post();
$post->get_by_id(1);

$tag1 = new Tag();
$tag1->where('name', 'PHP')->get();

$tag2 = new Tag();
$tag2->where('name', 'CodeIgniter')->get();

// Save relationships
$post->save(array($tag1, $tag2));
```

```php [Getting Tags]
$post = new Post();
$post->get_by_id(1);

// Get all tags for this post
$post->tag->get();

foreach ($post->tag as $tag) {
    echo $tag->name;
}
```

```php [Finding Posts by Tag]
$tag = new Tag();
$tag->where('name', 'PHP')->get();

// Get all posts with this tag
$tag->post->get();

foreach ($tag->post as $post) {
    echo $post->title;
}
```

:::

## Advanced Relationship Keys

When table names don't follow conventions, specify custom keys:

```php
class User extends DataMapper {
    var $has_many = array(
        'post' => array(
            'other_field' => 'author_id',  // Foreign key in posts table
            'join_other_as' => 'author'    // Alias in queries
        )
    );
}
```

Usage:

```php
$user = new User();
$user->get_by_id(1);

// Uses 'author_id' foreign key
$user->post->get();
```

## Self-Referential Relationships

Models can relate to themselves:

```php
class User extends DataMapper {
    var $has_many = array(
        'friend' => array(
            'class' => 'user',
            'other_field' => 'friend_id',
            'join_self_as' => 'friend',
            'join_other_as' => 'user',
            'join_table' => 'user_friends'
        )
    );
}
```

Database:

```sql
CREATE TABLE user_friends (
    id INT PRIMARY KEY,
    user_id INT,
    friend_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (friend_id) REFERENCES users(id)
);
```

Usage:

```php
$user = new User();
$user->get_by_id(1);

// Get all friends
$user->friend->get();

foreach ($user->friend as $friend) {
    echo $friend->name;
}
```

## Relationship Cardinality

### One-to-One (has_one)

```
User (1) ←→ (1) Profile
```

Each user has exactly one profile, and each profile belongs to exactly one user.

### One-to-Many (has_many)

```
User (1) ←→ (∞) Posts
```

Each user can have many posts, but each post belongs to only one user.

### Many-to-Many (has_many through join table)

```
Post (∞) ←→ (∞) Tags
```

Each post can have many tags, and each tag can belong to many posts.

## Relationship Examples

### Blog System

```php
class User extends DataMapper {
    var $has_many = array('post', 'comment');
}

class Post extends DataMapper {
    var $has_one = array('user');
    var $has_many = array('comment', 'tag');
}

class Comment extends DataMapper {
    var $has_one = array('user', 'post');
}

class Tag extends DataMapper {
    var $has_many = array('post');
}
```

### E-commerce System

```php
class Customer extends DataMapper {
    var $has_many = array('order', 'address');
}

class Order extends DataMapper {
    var $has_one = array('customer');
    var $has_many = array('orderitem');
}

class OrderItem extends DataMapper {
    var $has_one = array('order', 'product');
}

class Product extends DataMapper {
    var $has_many = array('orderitem', 'category');
}

class Category extends DataMapper {
    var $has_many = array('product');
}
```

## Performance Considerations

### N+1 Query Problem

::: danger Avoid N+1
```php
// Inefficient: 1 query for users plus N queries for each user's posts
$user = new User();
$user->get();

foreach ($user as $u) {
    $u->post->get(); // N queries
    foreach ($u->post as $post) {
        echo $post->title;
    }
}
```
:::

::: tip Solution: Eager Loading (DataMapper 2.0)
```php
// Efficient for this one relation: users query + posts query
$user = new User();
$user->with('post')  // Eager load posts
     ->get();

foreach ($user as $u) {
    foreach ($u->post as $post) {
        echo $post->title;
    }
}
```
:::

Learn more: [Eager Loading](/guide/datamapper-2/eager-loading)

## See Also

- [Accessing Relations](/guide/relationships/accessing) - How to use relationships
- [Setting Relations](/guide/relationships/setting) - Creating relationships
- [Saving Relations](/guide/relationships/saving) - Persisting relationships
- [Deleting Relations](/guide/relationships/deleting) - Removing relationships
- [Eager Loading](/guide/datamapper-2/eager-loading) - Prevent N+1 (2.0)
- [Advanced Relations](/guide/relationships/advanced) - Complex scenarios
