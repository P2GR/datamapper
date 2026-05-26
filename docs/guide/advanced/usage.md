# Advanced Usage

Advanced DataMapper ORM techniques, patterns, and best practices for power users. Master these topics to build robust, maintainable applications.

## Table of Contents

- [Advanced Relationship Patterns](#Advanced-Relationship-Patterns)
- [Model Events and Hooks](#Model-Events-and-Hooks)
- [Custom Validation Rules](#Custom-Validation-Rules)
- [Query Optimization](#Query-Optimization)
- [Transaction Management](#Transaction-Management)
- [Extensions and Traits](#Extensions-and-Traits)
- [Performance Patterns](#Performance-Patterns)

## Advanced Relationship Patterns

### Self-Referencing Relationships

```php
class User extends DataMapper {
    var $has_one = array('referrer' => array(
        'class' => 'user',
        'other_field' => 'referred_users'
    ));
    
    var $has_many = array('referred_users' => array(
        'class' => 'user',
        'other_field' => 'referrer'
    ));
}

// Get user and their referrer
$user = new User();
$user->include_related('referrer')->get_by_id(5);
echo "Referred by: " . $user->referrer->username;

// Get all users this user referred
$user->referred_users->get();
foreach ($user->referred_users as $referred) {
    echo $referred->username . "\n";
}
```

### Multiple Relationships Between Same Models

```php
class Post extends DataMapper {
    var $has_one = array(
        'author' => array(
            'class' => 'user',
            'other_field' => 'authored_posts'
        ),
        'editor' => array(
            'class' => 'user',
            'other_field' => 'edited_posts'
        )
    );
}

class User extends DataMapper {
    var $has_many = array(
        'authored_posts' => array(
            'class' => 'post',
            'other_field' => 'author'
        ),
        'edited_posts' => array(
            'class' => 'post',
            'other_field' => 'editor'
        )
    );
}
```

### Polymorphic Relationships

```php
class Comment extends DataMapper {
    var $table = 'comments';
    
    // commentable_id and commentable_type columns
    public function commentable() {
        $type = $this->commentable_type;
        $model = new $type();
        $model->get_by_id($this->commentable_id);
        return $model;
    }
}

// Usage
$comment = new Comment();
$comment->get_by_id(1);

$commentable = $comment->commentable();
// Returns Post, Video, or other model based on commentable_type
```

## Model Events and Hooks

### Available Hooks

```php
class User extends DataMapper {
    
    // Before validation
    protected function pre_validate($object) {
        // Modify data before validation
        $this->email = strtolower($this->email);
    }
    
    // After validation
    protected function post_validate($object) {
        // Custom validation logic
        if ($this->age < 13) {
            $this->error_message('age', 'Must be 13 or older');
            return FALSE;
        }
    }
    
    // Before save (INSERT or UPDATE)
    protected function pre_save($object) {
        // Hash password before saving
        if (!empty($this->password)) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }
    }
    
    // After save
    protected function post_save($object, $success) {
        if ($success) {
            // Send welcome email for new users
            if (!$object->id) {
                $this->send_welcome_email();
            }
        }
    }
    
    // Before delete
    protected function pre_delete($object) {
        // Prevent deletion of admin users
        if ($this->role === 'admin') {
            $this->error_message('delete', 'Cannot delete admin users');
            return FALSE;
        }
    }
    
    // After delete
    protected function post_delete($object, $success) {
        if ($success) {
            // Clean up related data
            $this->delete_user_files();
        }
    }
}
```

### Observer Pattern

```php
class UserObserver {
    public function creating($user) {
        // Before user is created
        $user->uuid = $this->generateUuid();
    }
    
    public function created($user) {
        // After user is created
        log_message('info', "User created: {$user->id}");
    }
    
    public function updating($user) {
        // Before user is updated
        $user->updated_by = get_current_user_id();
    }
    
    public function updated($user) {
        // After user is updated
        cache_clear("user_{$user->id}");
    }
}

// Register observer
User::observe(new UserObserver());
```

## Custom Validation Rules

### Custom Rule Functions

```php
class User extends DataMapper {
    
    var $validation = array(
        'username' => array(
            'rules' => array('required', 'valid_username', 'unique')
        ),
        'email' => array(
            'rules' => array('required', 'valid_email', 'unique')
        ),
        'age' => array(
            'rules' => array('required', 'integer', 'min_age' => 18)
        )
    );
    
    // Custom validation: valid_username
    protected function _valid_username($field) {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $this->$field)) {
            $this->error_message($field, 'Username can only contain letters, numbers, underscores, and hyphens');
            return FALSE;
        }
        return TRUE;
    }
    
    // Custom validation with parameter: min_age
    protected function _min_age($field, $min_age) {
        if ($this->$field < $min_age) {
            $this->error_message($field, "Must be at least $min_age years old");
            return FALSE;
        }
        return TRUE;
    }
}
```

### Conditional Validation

```php
class Order extends DataMapper {
    
    var $validation = array(
        'shipping_address' => array(
            'rules' => array('required_if_shipping')
        )
    );
    
    protected function _required_if_shipping($field) {
        if ($this->requires_shipping && empty($this->$field)) {
            $this->error_message($field, 'Shipping address is required');
            return FALSE;
        }
        return TRUE;
    }
}
```

## Query Optimization

### Eager Loading

```php
// Bad: N+1 query problem
$posts = new Post();
$posts->get();

foreach ($posts as $post) {
    echo $post->user->username; // Query for each post!
}

// Good: Eager load users
$posts = new Post();
$posts->include_related('user')->get();

foreach ($posts as $post) {
    echo $post->user->username; // No additional queries!
}
```

### Select Only Needed Columns

```php
// Bad: Select all columns
$users = new User();
$users->get();

// Good: Select only what you need
$users = new User();
$users->select('id, username, email')->get();
```

### Use Indexes

```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_posts_status_created ON posts(status, created_at);
```

### Query Caching

```php
// Cache expensive queries
$cache_key = 'top_users_' . date('Y-m-d');

if (!$users = cache_get($cache_key)) {
    $users = new User();
    $users->select('id, username, points')
          ->order_by('points', 'desc')
          ->limit(100)
          ->get();
    
    cache_save($cache_key, $users, 3600); // Cache for 1 hour
}
```

## Transaction Management

### Manual Transactions

```php
$this->db->trans_start();

try {
    $user = new User();
    $user->username = 'john';
    $user->save();
    
    $profile = new Profile();
    $profile->user_id = $user->id;
    $profile->bio = 'My bio';
    $profile->save();
    
    $this->db->trans_complete();
    
    if ($this->db->trans_status() === FALSE) {
        throw new Exception('Transaction failed');
    }
    
    echo "Success!";
    
} catch (Exception $e) {
    $this->db->trans_rollback();
    echo "Error: " . $e->getMessage();
}
```

### Transaction Helper Method

```php
class User extends DataMapper {
    
    public function createWithProfile($user_data, $profile_data) {
        return $this->transaction(function() use ($user_data, $profile_data) {
            // Create user
            $this->from_array($user_data);
            if (!$this->save()) {
                throw new Exception('Failed to create user');
            }
            
            // Create profile
            $profile = new Profile();
            $profile->from_array($profile_data);
            $profile->user_id = $this->id;
            if (!$profile->save()) {
                throw new Exception('Failed to create profile');
            }
            
            return $this;
        });
    }
}
```

## Extensions and Traits

### Creating Custom Traits

```php
trait Sluggable {
    
    protected function pre_save($object) {
        if (empty($this->slug) && !empty($this->title)) {
            $this->slug = $this->generateSlug($this->title);
        }
        
        return parent::pre_save($object);
    }
    
    protected function generateSlug($text) {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $base_slug = $slug;
        $counter = 1;
        
        while ($this->slug_exists($slug)) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    protected function slug_exists($slug) {
        $check = new static();
        $check->where('slug', $slug);
        
        if ($this->exists()) {
            $check->where('id !=', $this->id);
        }
        
        return $check->count() > 0;
    }
}

// Usage
class Post extends DataMapper {
    use Sluggable;
}
```

### Repository Pattern

```php
class UserRepository {
    
    public function find($id) {
        $user = new User();
        $user->get_by_id($id);
        return $user->exists() ? $user : null;
    }
    
    public function findByEmail($email) {
        $user = new User();
        $user->where('email', $email)->get();
        return $user->exists() ? $user : null;
    }
    
    public function active() {
        $users = new User();
        return $users->where('status', 'active')->get();
    }
    
    public function create(array $data) {
        $user = new User();
        $user->from_array($data);
        return $user->save() ? $user : null;
    }
    
    public function update($id, array $data) {
        $user = $this->find($id);
        if ($user) {
            $user->from_array($data);
            return $user->save();
        }
        return false;
    }
    
    public function delete($id) {
        $user = $this->find($id);
        return $user ? $user->delete() : false;
    }
}
```

## Performance Patterns

### Lazy Loading

```php
class Post extends DataMapper {
    private $_comments_cache;
    
    public function comments() {
        if ($this->_comments_cache === null) {
            $this->_comments_cache = $this->comment->get();
        }
        return $this->_comments_cache;
    }
}

// Comments only loaded when accessed
$post = new Post();
$post->get_by_id(1);

// No query yet
if ($show_comments) {
    // Query runs here
    foreach ($post->comments() as $comment) {
        echo $comment->content;
    }
}
```

### Chunking Large Datasets

```php
// Process 10,000 users in batches of 1000
$query = new User();
$query->chunk(1000, function($users) {
    foreach ($users as $user) {
        $user->process_something();
    }
});
```

### Result Caching

```php
class Post extends DataMapper {
    
    public function getFeatured($force_refresh = FALSE) {
        $cache_key = 'featured_posts';
        
        if (!$force_refresh) {
            $cached = cache_get($cache_key);
            if ($cached !== FALSE) {
                return $cached;
            }
        }
        
        $this->where('featured', 1)
             ->order_by('created_at', 'desc')
             ->limit(10)
             ->get();
        
        cache_save($cache_key, $this->all, 3600);
        
        return $this->all;
    }
}
```

## Related Documentation

- [Subqueries](/guide/advanced/subqueries)
- [Transactions](transactions)
- [Validation](/guide/advanced/validation)
- [Extensions](../extensions/)

## See Also

- [Best Practices](../../help/faq#BestPractices)
- [Performance Tips](../../help/troubleshooting#Performance)
- [Advanced Query Building](../datamapper-2/advanced-query-building)