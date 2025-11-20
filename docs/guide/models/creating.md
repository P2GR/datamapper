# Creating DataMapper Models

DataMapper models are the foundation of your application's data layer. Each model represents a database table and provides an object-oriented interface for interacting with your data.

::: tip Philosophy
DataMapper models are **very different** from traditional CodeIgniter models. They're automatically loaded when instantiated and should **never** be added to autoload.
:::

## Basic Template

DataMapper comes with a ready-to-use template at `application/models/_template.php`.

### Minimal Model

Here's the simplest DataMapper model you can create:

```php
<?php
class User extends DataMapper {
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}

/* End of file user.php */
/* Location: ./application/models/user.php */
```

### Constructor Best Practices

::: code-group

```php [Recommended]
// Use __construct() for PHP 7+ compatibility
function __construct($id = NULL)
{
    parent::__construct($id);
}
```

```php [Legacy]
// Old style (avoid in new code)
function User($id = NULL)
{
    parent::DataMapper($id);
}
```

:::

::: warning Constructor Parameter
If you define a constructor without the `$id` parameter, you won't be able to use the shorthand:
```php
$user = new User($user_id);
```
:::

## Naming Conventions

DataMapper follows strict naming conventions to map models to database tables automatically.

### Standard Naming

::: code-group

```php [Model]
// Singular, CamelCase
class Book extends DataMapper {
    // ...
}
```

```sql [Table]
-- Plural, lowercase
CREATE TABLE books (
    id INT PRIMARY KEY,
    title VARCHAR(255),
    -- ...
);
```

:::

### Common Examples

| Model Name | Table Name | Auto-Detected? |
|------------|------------|----------------|
| `User` | `users` | Yes |
| `Book` | `books` | Yes |
| `Category` | `categories` | Yes |
| `Author` | `authors` | Yes |

### Irregular Plurals

For irregular plurals, DataMapper includes a customized Inflector Helper that handles most English words:

| Model Name | Table Name | Auto-Detected? |
|------------|------------|----------------|
| `Country` | `countries` | Yes (with Inflector) |
| `Person` | `people` | Yes (with Inflector) |
| `Child` | `children` | Yes (with Inflector) |
| `Tooth` | `teeth` | Yes (with Inflector) |

## Custom Table Names

When auto-detection doesn't work, specify the table name explicitly:

```php
<?php
class Country extends DataMapper {
    
    var $table = 'countries';
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}
```

### Same Singular/Plural Names

Some words have identical singular and plural forms:

```php
<?php
class Sheep extends DataMapper {
    
    // "sheep" singular = "sheep" plural
    // But DataMapper will add 's' automatically
    // So we specify the correct table name
    var $table = 'sheep';
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}
```

Other examples: `fruit`, `fish`, `deer`, `series`, `species`

## Post Model Initialization

Use `post_model_init()` to add custom initialization logic after DataMapper loads the model:

```php
<?php
class Product extends DataMapper {
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
    
    function post_model_init($from_cache = FALSE)
    {
        // Custom initialization code here
        
        if ($from_cache) {
            // Configuration was loaded from production cache
            // Skip expensive initialization
        } else {
            // Configuration was generated
            // Perform full initialization
        }
        
        // Example: Set default values
        if (empty($this->status)) {
            $this->status = 'draft';
        }
    }
}
```

::: info Cache Parameter
The `$from_cache` parameter indicates whether the model configuration was loaded from the [production cache](/guide/advanced/production-cache) or generated fresh.
:::

## Complete Model Example

Here's a complete example with all common features:

```php
<?php
class Article extends DataMapper {
    
    // Custom table name (if needed)
    var $table = 'articles';
    
    // Relationships
    var $has_one = array('user');
    var $has_many = array('comment', 'tag');
    
    // Validation rules
    var $validation = array(
        'title' => array(
            'label' => 'Article Title',
            'rules' => array('required', 'min_length' => 3, 'max_length' => 255)
        ),
        'slug' => array(
            'label' => 'URL Slug',
            'rules' => array('required', 'alpha_dash', 'unique')
        ),
        'content' => array(
            'label' => 'Article Content',
            'rules' => array('required', 'min_length' => 10)
        ),
        'status' => array(
            'rules' => array('required', 'in_list' => array('draft', 'published', 'archived'))
        )
    );
    
    // Default sorting
    var $default_order_by = array('published_at' => 'desc', 'title' => 'asc');
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
    
    function post_model_init($from_cache = FALSE)
    {
        // Set default status for new articles
        if (!$this->exists()) {
            $this->status = 'draft';
        }
    }
    
    // Custom method: Publish article
    function publish()
    {
        $this->status = 'published';
        $this->published_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    // Custom method: Get published articles
    function get_published($limit = 10)
    {
        return $this->where('status', 'published')
                    ->order_by('published_at', 'desc')
                    ->limit($limit)
                    ->get();
    }
}

/* End of file article.php */
/* Location: ./application/models/article.php */
```

## Loading by ID <Badge type="tip" text="convenience" />

The constructor accepts an optional ID parameter for quick loading:

::: code-group

```php [Shorthand]
// Load user with ID 5
$user = new User(5);

if ($user->exists()) {
    echo $user->name;
}
```

```php [Traditional]
// Equivalent traditional approach
$user = new User();
$user->get_by_id(5);

if ($user->exists()) {
    echo $user->name;
}
```

:::

::: warning ID Parameter Required
To use the shorthand `new User($id)`, your constructor **must** include the `$id` parameter and pass it to `parent::__construct()`.
:::

## Model Location

DataMapper models must be placed in the standard CodeIgniter models directory:

```
application/
└── models/
    ├── _template.php
    ├── user.php
    ├── article.php
    ├── comment.php
    └── ...
```

::: danger Never Autoload
Do **NOT** add DataMapper models to CodeIgniter's autoload configuration. DataMapper handles loading automatically.
:::

## File Naming Convention

Model files should follow CodeIgniter's naming convention:

- **Filename**: lowercase version of class name
- **Class name**: CamelCase, singular
- **Example**: `User` class → `user.php` file

```php
// Correct
File: user.php
Class: User

// Correct
File: blog_post.php
Class: Blog_post

// Incorrect
File: User.php      // Should be lowercase
Class: Users        // Should be singular
```

## Troubleshooting

### Model Not Found

If you see "Unable to locate the model you have specified: User":

1. **Check filename**: Must be lowercase (e.g., `user.php`, not `User.php`)
2. **Check location**: Must be in `application/models/`
3. **Check class name**: Must extend `DataMapper`
4. **Check autoload**: Remove DataMapper models from autoload

### Table Not Found

If you see "Table 'database.user' doesn't exist":

1. **Check table name**: Should be plural lowercase (e.g., `users`, not `user`)
2. **Use Inflector**: Load the Inflector helper for irregular plurals
3. **Specify manually**: Use `var $table = 'your_table_name';`

### Wrong Table Selected

If DataMapper is using the wrong table:

```php
// Explicitly set the table name
class Person extends DataMapper {
    var $table = 'people';  // Not 'persons'
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}
```

## Next Steps

Now that you know how to create models, learn about:

- [Validation Rules](/guide/advanced/validation) - Protect your data
- [Relationships](/guide/relationships/) - Connect your models
- [Get Methods](/guide/models/get) - Retrieve data
- [Save Methods](/guide/models/save) - Create and update records

## See Also

- [Model Fields & Properties](/guide/models/fields)
- [Model Events & Hooks](/guide/advanced/usage#model-events-and-hooks)
- [Reserved Names](/reference/reserved-names) - Avoid conflicts
- [Inflector Helper](http://codeigniter.com/user_guide/helpers/inflector_helper.html)
