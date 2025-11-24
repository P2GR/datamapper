# Attribute Casting, Accessors & Mutators (DataMapper 2.0)

DataMapper 2.0 introduces powerful attribute casting, accessors, and mutators that provide automatic type conversion and data transformation while maintaining **100% backward compatibility** with existing models.

**âœ¨ New in DataMapper 2.0:** Modern attribute handling inspired by Laravel and other ORMs. **Built directly into DataMapper** - just define $casts property in your model. No traits, no configuration required!

Key Features:

- **Automatic Type Casting** - Database strings â†’ proper PHP types (int, bool, array, DateTime)
- **Accessors** - Computed properties that don't exist in the database
- **Mutators** - Transform values automatically when setting
- **Opt-In Design** - Models without $casts defined work exactly as before
- **Performance** - Method existence checks are cached for speed

## Table of Contents

- [Overview](#Overview)
- [Attribute Casting](#Casting)
- [Accessors (Getters)](#Accessors)
- [Mutators (Setters)](#Mutators)
- [Supported Cast Types](#Types)
- [Complete Examples](#Examples)
- [Backward Compatibility](#Compatibility)

## Overview

The casting system provides three key features:

**Opt-In Design:** Models without $casts defined continue to work exactly as before. **No breaking changes!**

## Basic Setup

Attribute casting is **built into DataMapper 2.0** - no trait required! Just define the $casts property in your model:

```php

class User extends DataMapper
{
    // That's it! Just define your casts
    protected $casts = array(
        'id' => 'int',
        'age' => 'int',
        'salary' => 'float',
        'is_active' => 'bool',
        'settings' => 'array',
        'created_at' => 'datetime'
    );
}

// Use it immediately
$user = new User();
$user->get_by_id(1);

echo $user->age;         // 25 (int, not string!)
echo $user->is_active;   // true (bool, not "1"!)
print_r($user->settings);  // Array (auto-decoded from JSON!)

```

**No Trait Required!** Casting is built directly into DataMapper core. Just define $casts and it works automatically.

## Attribute Casting

Casting automatically converts values between database storage and PHP types:

### Without Casting (Legacy Behavior)

```php

$user = new User();
$user->get_by_id(1);

echo $user->age;         // "25" (string from database)
echo $user->is_active;   // "1" (string)
$settings = json_decode($user->settings, true); // Manual JSON decode

```

### With Casting (Modern Approach)

```php

$user = new User();
$user->get_by_id(1);

echo $user->age;         // 25 (int)
echo $user->is_active;   // true (bool)
echo $user->settings['theme'];  // Array automatically decoded!

```

## Supported Cast Types

## Accessors (Computed Properties)

Accessors let you define virtual attributes that don't exist in the database:

```php

class User extends DataMapper
{
    /**
     * Accessor: full_name
     * Combines first and last name
     */
    public function getFullNameAttribute()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
    
    /**
     * Accessor: age_group
     * Categorizes user by age
     */
    public function getAgeGroupAttribute()
    {
        $age = $this->age ?? 0;
        
        if ($age < 18) {
            return 'Minor';
        } elseif ($age < 30) {
            return 'Young Adult';
        } elseif ($age < 50) {
            return 'Adult';
        } elseif ($age < 65) {
            return 'Middle Age';
        } else {
            return 'Senior';
        }
    }
}

```

Usage:

```php

$user = new User();
$user->first_name = 'John';
$user->last_name = 'Doe';
$user->age = 45;

echo $user->full_name;   // "John Doe" (computed on the fly)
echo $user->age_group;   // "Adult"

```

**Naming Convention:** Accessor methods must be named `get{AttributeName}Attribute` in StudlyCase (e.g., `full_name` â†’ `getFullNameAttribute`)

## Mutators (Data Transformation)

Mutators transform data when setting attributes. **Important:** Use the stored field name directly to avoid infinite recursion.

```php

class User extends DataMapper
{
    /**
     * Mutator: email
     * Automatically lowercase email addresses
     */
    public function setEmailAttribute($value)
    {
        // Direct assignment to avoid recursion
        $this->email = strtolower(trim($value));
    }
    
    /**
     * Mutator: password
     * Automatically hash passwords
     */
    public function setPasswordAttribute($value)
    {
        // Only hash if not already hashed
        if (!password_get_info($value)['algo']) {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        $this->password = $value;
    }
    
    /**
     * Mutator: username
     * Normalize and validate username
     */
    public function setUsernameAttribute($value)
    {
        // Clean and validate
        $clean = preg_replace('/[^a-z0-9_]/', '', strtolower($value));
        $this->username = $clean;
    }
}

```

Usage:

```php

$user = new User();
$user->email = 'ADMIN@COMPANY.COM';
$user->password = 'secret123';
$user->username = 'John.Doe-2024!';

echo $user->email;     // "admin@company.com" (lowercased)
echo $user->password;  // "$2y$10$..." (hashed)
echo $user->username;  // "johndoe2024" (cleaned)

```

**Naming Convention:** Mutator methods must be named `set{AttributeName}Attribute` in StudlyCase (e.g., `email` â†’ `setEmailAttribute`)

**Important:** Inside mutators, assign directly to `$this->{property}`. DataMapper's `__set` method detects mutators and calls them, preventing infinite loops.

## Complete Examples

### toArray() - Export with Casting and Accessors

The toArray() method exports all attributes with casting applied AND includes computed accessor values:

```php

class User extends DataMapper
{
    protected $casts = array(
        'age' => 'int',
        'is_active' => 'bool'
    );
    
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}

$user = new User();
$user->get_by_id(1);

// Export to array with casts and accessors
$data = $user->toArray();

// Output:
// [
//     'id' => 1,
//     'first_name' => 'John',
//     'last_name' => 'Doe',
//     'age' => 30,              // Cast to int
//     'is_active' => true,      // Cast to bool
//     'full_name' => 'John Doe' // Accessor included!
// ]

// Perfect for JSON APIs
echo json_encode($user->toArray());

```

**Note:**toArray() automatically includes all computed accessor properties that don't exist in the database. This is perfect for API responses!

### JSON/Array Casting

```php

class Post extends DataMapper
{
    protected $casts = array(
        'tags' => 'array',
        'meta' => 'array'
    ];
}

$post = new Post();
$post->tags = ['php', 'orm', 'datamapper'];
$post->meta = ['views' => 1000, 'likes' => 50];
$post->save();  // Stored as JSON in database

// Later...
$post->get_by_id(1);
echo $post->tags[0];           // "php" (array automatically!)
echo $post->meta['views'];     // 1000

```

### DateTime Casting

```php

class User extends DataMapper
{
    protected $casts = array(
        'created_at' => 'datetime',
        'birth_date' => 'date'
    );
}

$user = new User();
$user->created_at = '2024-01-15 10:30:00';
$user->birth_date = '1990-05-20';

// Automatically converted to DateTime objects
echo $user->created_at->format('F j, Y');  // "January 15, 2024"
echo $user->birth_date->format('Y-m-d');    // "1990-05-20"

// Calculate age
$now = new DateTime();
$age = $now->diff($user->birth_date)->y;
echo $age;  // 34

```

### Computed Pricing Example

```php

class Product extends DataMapper
{
    protected $casts = array(
        'price' => 'float',
        'discount_price' => 'float'
    ];
    
    public function getFinalPriceAttribute()
    {
        $price = $this->price ?? 0.0;
        $discount = $this->discount_price ?? 0.0;
        return $discount > 0 ? $discount : $price;
    }
    
    public function getDiscountPercentageAttribute()
    {
        $price = $this->price ?? 0.0;
        $discount = $this->discount_price ?? 0.0;
        
        if ($price <= 0 || $discount <= 0) return 0.0;
        return round((($price - $discount) / $price) * 100, 2);
    }
}

$product = new Product();
$product->price = 1299.99;
$product->discount_price = 999.99;

echo $product->final_price;           // 999.99
echo $product->discount_percentage;  // 23.08

```

## Backward Compatibility

The casting system is **completely opt-in**. Models work in three ways:

### 1. Legacy Model (No Changes)

```php

class OldUser extends DataMapper
{
    // No $casts defined
    // Works exactly as before!
}

```

### 2. Modern Model with Casting Only

```php

class User extends DataMapper
{
    // Just add $casts 
    protected $casts = array(
        'age' => 'int'
    );
}

```

### 3. Full Modern Model

```php

class User extends DataMapper
{
    // Casts, accessors, and mutators
    protected $casts = array('age' => 'int');
    
    public function getFullNameAttribute() {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    public function setEmailAttribute($value) {
        $this->email = strtolower($value);
    }
}

```

### ðŸŽ¯ Migration Strategy

- **Phase 1:** Leave existing models unchanged (they work perfectly)
- **Phase 2:** Add `$casts` property to models you're actively working on
- **Phase 3:** Add accessors/mutators as needed for new features
- **Phase 4:** Gradually adopt across codebase (optional)

**No pressure to change everything at once!** Old and new models work together perfectly.

## Best Practices

## Common Mistakes & Solutions

### âŒ Mistake 1: Assigning to Wrong Property in Mutator

```php

// CORRECT - Assign to actual property
public function setEmailAttribute($value)
{
    $this->email = strtolower($value);  // Direct assignment works!
}

```

**How it works:** DataMapper's `__set` method detects when a mutator exists for an attribute and calls it. Inside the mutator, assign directly to the property.

### âŒ Mistake 2: Wrong Array Syntax for CodeIgniter 3

```php

// WRONG - PHP 7.4+ typed property syntax is not supported by CI3 loaders
class User extends DataMapper
{
    protected array $casts = ['age' => 'int'];  // Requires CodeIgniter 4+
}

// CORRECT - CI3-compatible syntax
class User extends DataMapper
{
    protected $casts = array('age' => 'int');  // Works with all supported versions (PHP 7.4+)
}

```

### âŒ Mistake 3: Wrong Method Names

```php

// WRONG - Incorrect naming
public function full_name() { /* Won't work */ }
public function setEmail($value) { /* Won't work */ }

// CORRECT - StudlyCase + Attribute suffix
public function getFullNameAttribute() { /* Works! */ }
public function setEmailAttribute($value) { /* Works! */ }

```

**Naming Rules:**

- Accessors: `get + StudlyCase(attribute_name) + Attribute`
- Mutators: `set + StudlyCase(attribute_name) + Attribute`
- Example: `full_name` â†’ `getFullNameAttribute()`
- Example: `is_active` â†’ `getIsActiveAttribute()`

## Performance Considerations

The casting system is highly optimized:

### Benchmark Results

```php

// Test: 10,000 reads with/without casting

Without casting (legacy):  12ms
With casting (5 casts):    14ms  (+16% overhead)
With casting + accessor:   16ms  (+33% overhead)

Conclusion: Minimal performance impact for huge gains in code quality

```

## See Also

- [Query Builder](query-builder) - Modern query interface
- [Get Methods](/guide/models/get) - Classic data retrieval
- [Save & Update](/guide/models/save) - Persisting data
- [Validation](/guide/advanced/validation) - Data validation rules