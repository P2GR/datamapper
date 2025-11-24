# Clone

Create a copy of a DataMapper object. Perfect for duplicating records, creating templates, or working with snapshots.

## Basic Usage

```php
$user = new User();
$user->get_by_id(1);

$clone = $user->get_clone();

// $clone is an exact copy of $user
// But they are separate objects
```

## Method Signature

```php
$object->get_clone($force_db = FALSE)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$force_db` | boolean | If `TRUE`, refreshes from database before cloning |

## Return Value

Returns a new DataMapper object that is a copy of the current object.

## Examples

### Simple Clone

```php
$original = new Product();
$original->get_by_id(5);

$clone = $original->get_clone();

// Original and clone are independent
$original->name = "Original Name";
$clone->name = "Cloned Name";

echo $original->name;  // "Original Name"
echo $clone->name;     // "Cloned Name"
```

### Clone and Save as New Record

```php
$original = new Product();
$original->get_by_id(5);

$duplicate = $original->get_clone();

// Clear the ID to save as new
$duplicate->id = NULL;

// Modify the duplicate
$duplicate->name = $original->name . " (Copy)";
$duplicate->sku = $original->sku . "-COPY";

// Save as new record
$duplicate->save();

echo "Original ID: " . $original->id;    // 5
echo "Duplicate ID: " . $duplicate->id;  // 6 (new ID)
```

### Clone with Database Refresh

```php
$user = new User();
$user->get_by_id(1);

// Modify in memory
$user->email = "temp@example.com";

// Clone with fresh data from database
$fresh_clone = $user->get_clone(TRUE);

echo $user->email;         // "temp@example.com" (modified)
echo $fresh_clone->email;  // "original@example.com" (from database)
```

## Use Cases

### 1. Duplicate Record

Perfect for "Save As Copy" functionality:

```php
public function duplicate_post($id) {
    $original = new Post();
    $original->get_by_id($id);
    
    $duplicate = $original->get_clone();
    $duplicate->id = NULL;  // Clear ID for new record
    
    // Modify fields
    $duplicate->title = $original->title . " (Copy)";
    $duplicate->slug = $original->slug . "-copy";
    $duplicate->created_at = date('Y-m-d H:i:s');
    
    if ($duplicate->save()) {
        echo "Post duplicated! New ID: " . $duplicate->id;
    }
}
```

### 2. Create Template

Create records from templates:

```php
// Load template
$template = new Product();
$template->where('is_template', 1)->get();

// Create new product from template
$product = $template->get_clone();
$product->id = NULL;
$product->is_template = 0;
$product->name = "New Product Based on Template";

$product->save();
```

### 3. Snapshot for Comparison

Save a snapshot to detect changes:

```php
$user = new User();
$user->get_by_id(1);

// Create snapshot
$snapshot = $user->get_clone();

// User makes changes...
$user->email = "newemail@example.com";
$user->bio = "Updated bio";

// Compare changes
if ($user->email !== $snapshot->email) {
    echo "Email changed from {$snapshot->email} to {$user->email}";
}

if ($user->bio !== $snapshot->bio) {
    echo "Bio was updated";
}

// Save changes
$user->save();
```

### 4. Rollback Buffer

Keep original values for potential rollback:

```php
$product = new Product();
$product->get_by_id(1);

// Save original state
$backup = $product->get_clone();

// Try updating price
$product->price = $_POST['new_price'];
$product->stock = $_POST['new_stock'];

if ($product->save()) {
    echo "Update successful!";
} else {
    // Rollback to backup
    $product = $backup;
    echo "Update failed, rolled back to original values";
}
```

### 5. Batch Creation

Create multiple similar records:

```php
// Load base record
$base_course = new Course();
$base_course->get_by_name('Introduction to PHP');

// Create variations
$levels = array('Beginner', 'Intermediate', 'Advanced');

foreach ($levels as $level) {
    $course = $base_course->get_clone();
    $course->id = NULL;
    $course->name = $level . ' ' . $base_course->name;
    $course->level = strtolower($level);
    $course->save();
}
```

## Important Notes

::: warning Shallow Copy
`get_clone()` creates a **shallow copy**:
- Simple properties are copied
- Object references are NOT deep-copied
- Relationships are NOT automatically cloned

```php
$user = new User();
$user->get_by_id(1);

$clone = $user->get_clone();

// Relationships are NOT cloned
// $clone does not include $user's related posts, country, etc.
```
:::

::: tip Clone vs. New Instance
```php
// Clone: Copy existing object's data
$clone = $user->get_clone();

// New Instance: Empty object
$new = new User();
```
:::

## Cloning with Relationships

Relationships must be manually cloned:

### Clone with Has-One Relationship

```php
$user = new User();
$user->include_related('country')->get_by_id(1);

// Clone user
$user_clone = $user->get_clone();
$user_clone->id = NULL;

// Clone country relationship
$country_clone = $user->country->get_clone();

// Save user first
$user_clone->save();

// Then create relationship
$user_clone->save($country_clone);
```

### Clone with Has-Many Relationships

```php
$post = new Post();
$post->get_by_id(1);

// Clone post
$post_clone = $post->get_clone();
$post_clone->id = NULL;
$post_clone->title .= " (Copy)";
$post_clone->save();

// Clone comments
$post->comment->get();
foreach ($post->comment as $comment) {
    $comment_clone = $comment->get_clone();
    $comment_clone->id = NULL;
    $comment_clone->save($post_clone);
}
```

## Clone and Modify Pattern

Common pattern for duplicating with modifications:

```php
public function clone_and_modify($id, $modifications) {
    $original = new Model();
    $original->get_by_id($id);
    
    $clone = $original->get_clone();
    $clone->id = NULL;
    
    // Apply modifications
    foreach ($modifications as $field => $value) {
        $clone->$field = $value;
    }
    
    if ($clone->save()) {
        return $clone;
    }
    
    return FALSE;
}

// Usage:
$new_product = $this->clone_and_modify(5, array(
    'name' => 'New Product Name',
    'sku' => 'NEW-SKU-123',
    'price' => 29.99
));
```

## Audit Trail with Clones

Keep history using clones:

```php
class Post extends DataMapper {
    var $has_many = array('revision');
}

class Revision extends DataMapper {
    var $has_one = array('post');
}

// When updating post, save revision
$post = new Post();
$post->get_by_id(1);

// Create revision from current state
$revision = new Revision();
$revision->post_id = $post->id;
$revision->title = $post->title;
$revision->content = $post->content;
$revision->revision_date = date('Y-m-d H:i:s');
$revision->save();

// Now update post
$post->title = $_POST['title'];
$post->content = $_POST['content'];
$post->save();
```

## Testing with Clones

Useful for testing without affecting original data:

```php
public function test_price_calculation() {
    $product = new Product();
    $product->get_by_id(1);
    
    // Clone for testing
    $test_product = $product->get_clone();
    
    // Test calculations
    $test_product->price = 100;
    $tax = $test_product->calculate_tax();
    $total = $test_product->calculate_total();
    
    $this->assertEquals(10, $tax);
    $this->assertEquals(110, $total);
    
    // Original is unchanged
    $this->assertEquals(50, $product->price);
}
```

## Clone Collection

Clone multiple objects:

```php
$products = new Product();
$products->where('category_id', 5)->get();

$clones = array();
foreach ($products as $product) {
    $clone = $product->get_clone();
    $clone->id = NULL;
    $clone->category_id = 10;  // Move to different category
    $clones[] = $clone;
}

// Save all clones
foreach ($clones as $clone) {
    $clone->save();
}
```

## Advanced: Deep Clone Helper

Create a custom deep clone method:

```php
class User extends DataMapper {
    
    public function deep_clone() {
        // Clone user
        $clone = $this->get_clone();
        $clone->id = NULL;
        $clone->save();
        
        // Clone has-one relationships
        if ($this->country->exists()) {
            $country = $this->country->get_clone();
            $clone->save($country);
        }
        
        // Clone has-many relationships
        $this->post->get();
        foreach ($this->post as $post) {
            $post_clone = $post->get_clone();
            $post_clone->id = NULL;
            $post_clone->save($clone);
        }
        
        return $clone;
    }
}

// Usage:
$user = new User();
$user->get_by_id(1);

$complete_copy = $user->deep_clone();
```

## Performance Considerations

::: tip Performance
- `get_clone()` is fast - it's a simple object copy
- `get_clone(TRUE)` requires a database query
- Cloning large collections can be memory-intensive
- Consider using [get_iterated()](get-iterated) for large datasets

```php
// Efficient for large datasets
$products = new Product();
$products->get_iterated();

foreach ($products as $product) {
    $clone = $product->get_clone();
    // Process clone...
}
```
:::

## Common Patterns

### Pattern 1: Duplicate with New Values

```php
$clone = $original->get_clone();
$clone->id = NULL;
$clone->field = "new value";
$clone->save();
```

### Pattern 2: Snapshot Before Changes

```php
$backup = $model->get_clone();
$model->field = "new value";

if (!$model->save()) {
    $model = $backup;  // Rollback
}
```

### Pattern 3: Template System

```php
$template->get_by_template_id($id);
$instance = $template->get_clone();
$instance->id = NULL;
$instance->is_template = 0;
$instance->save();
```

### Pattern 4: Versioning

```php
$version = $current->get_clone();
$version->id = NULL;
$version->version_of = $current->id;
$version->version_number++;
$version->save();
```

## Troubleshooting

**Clone shows old data:**
```php
// Use force_db parameter to refresh
$clone = $user->get_clone(TRUE);
```

**Clone has same ID:**
```php
// Clear ID before saving
$clone->id = NULL;
$clone->save();
```

**Relationships not cloned:**
```php
// Relationships must be manually cloned
// See "Cloning with Relationships" section above
```

## Related Methods

- **[refresh()](refresh)** - Reload data from database
- **[get()](/guide/models/get)** - Query and retrieve objects
- **[save()](/guide/models/save)** - Save the object
- **[from_array()](from-array)** - Populate from array

## See Also

- [refresh() - Reload from Database](refresh)
- [Saving Records](/guide/models/save)
- [Model Fields](fields)
- [Relationships](../relationships/)
