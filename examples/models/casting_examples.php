<?php
/**
 * Example Model Classes - Legacy vs Modern
 * 
 * This file demonstrates both legacy DataMapper models (without casting)
 * and modern models (with casting and accessors/mutators) working side by side.
 * 
 * @package     DataMapper
 * @category    Examples
 * @version     2.0.0
 */

// Load the trait
require_once(APPPATH . 'datamapper/attributecasting.php');

/**
 * Legacy User Model
 * 
 * Traditional DataMapper model without any modern features.
 * Works exactly as it always has - full backward compatibility.
 */
class LegacyUser extends DataMapper
{
    public $table = 'users';
    
    public $has_many = [
        'post' => [
            'class' => 'LegacyPost',
            'other_field' => 'user'
        ]
    ];
    
    // No $casts property
    // No accessors or mutators
    // Just plain old DataMapper
}

/**
 * Modern User Model
 * 
 * Uses the new DMZ_AttributeCasting trait for advanced features:
 * - Automatic type casting
 * - Accessors for computed properties
 * - Mutators for data transformation
 */
class User extends DataMapper
{
    use DMZ_AttributeCasting;
    
    public $table = 'users';
    
    public $has_many = [
        'post' => [
            'class' => 'Post',
            'other_field' => 'user'
        ]
    ];
    
    /**
     * Attribute casting configuration
     * 
     * Automatically converts types when reading/writing
     */
    protected array $casts = [
        'id' => 'int',
        'age' => 'int',
        'salary' => 'float',
        'is_active' => 'bool',
        'is_admin' => 'bool',
        'settings' => 'array',      // JSON encode/decode
        'metadata' => 'json',        // Alias for array
        'created_at' => 'datetime',  // DateTime object
        'updated_at' => 'datetime',
        'birth_date' => 'date'       // Date-only DateTime
    ];
    
    /**
     * Accessor: full_name
     * 
     * Computed property - combines first and last name
     * Access as: $user->full_name
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
    
    /**
     * Accessor: display_name
     * 
     * Shows username with admin badge if applicable
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->username ?? 'Unknown';
        if ($this->is_admin) {
            return $name . ' [ADMIN]';
        }
        return $name;
    }
    
    /**
     * Accessor: age_group
     * 
     * Categorizes user by age
     */
    public function getAgeGroupAttribute(): string
    {
        $age = $this->age ?? 0;
        
        return match(true) {
            $age < 18 => 'Minor',
            $age < 30 => 'Young Adult',
            $age < 50 => 'Adult',
            $age < 65 => 'Middle Age',
            default => 'Senior'
        };
    }
    
    /**
     * Mutator: email
     * 
     * Automatically lowercases email addresses
     */
    public function setEmailAttribute(string $value): void
    {
        // Access the raw field through parent
        $this->email = strtolower(trim($value));
    }
    
    /**
     * Mutator: username
     * 
     * Strips whitespace and ensures minimum length
     */
    public function setUsernameAttribute(string $value): void
    {
        $username = trim($value);
        if (strlen($username) < 3) {
            throw new Exception('Username must be at least 3 characters');
        }
        $this->username = $username;
    }
    
    /**
     * Mutator: password
     * 
     * Automatically hashes passwords
     */
    public function setPasswordAttribute(string $value): void
    {
        // Only hash if not already hashed
        if (!password_get_info($value)['algo']) {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        $this->password = $value;
    }
}

/**
 * Modern Post Model
 * 
 * Example of a model with JSON casting and datetime handling
 */
class Post extends DataMapper
{
    use DMZ_AttributeCasting;
    
    public $table = 'posts';
    
    public $has_one = [
        'user' => [
            'class' => 'User',
            'other_field' => 'post'
        ]
    ];
    
    protected array $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'view_count' => 'int',
        'is_published' => 'bool',
        'tags' => 'array',           // Store tags as JSON
        'meta' => 'array',            // Store metadata as JSON
        'published_at' => 'datetime',
        'created_at' => 'datetime'
    ];
    
    /**
     * Accessor: excerpt
     * 
     * Returns first 100 characters of content
     */
    public function getExcerptAttribute(): string
    {
        $content = $this->content ?? '';
        if (strlen($content) <= 100) {
            return $content;
        }
        return substr($content, 0, 100) . '...';
    }
    
    /**
     * Accessor: status
     * 
     * Human-readable status
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_published) {
            return 'Draft';
        }
        
        if ($this->published_at && $this->published_at > new DateTime()) {
            return 'Scheduled';
        }
        
        return 'Published';
    }
    
    /**
     * Mutator: title
     * 
     * Capitalizes title
     */
    public function setTitleAttribute(string $value): void
    {
        $this->title = ucwords(strtolower($value));
    }
}

/**
 * Product Model - Demonstrates float/decimal casting
 */
class Product extends DataMapper
{
    use DMZ_AttributeCasting;
    
    public $table = 'products';
    
    protected array $casts = [
        'id' => 'int',
        'price' => 'float',
        'discount_price' => 'float',
        'stock_quantity' => 'int',
        'is_available' => 'bool',
        'specifications' => 'array',
        'dimensions' => 'json'
    ];
    
    /**
     * Accessor: final_price
     * 
     * Returns price after discount
     */
    public function getFinalPriceAttribute(): float
    {
        $price = $this->price ?? 0.0;
        $discount = $this->discount_price ?? 0.0;
        
        return $discount > 0 ? $discount : $price;
    }
    
    /**
     * Accessor: discount_percentage
     * 
     * Calculates discount as percentage
     */
    public function getDiscountPercentageAttribute(): float
    {
        $price = $this->price ?? 0.0;
        $discount = $this->discount_price ?? 0.0;
        
        if ($price <= 0 || $discount <= 0 || $discount >= $price) {
            return 0.0;
        }
        
        return round((($price - $discount) / $price) * 100, 2);
    }
    
    /**
     * Accessor: in_stock
     * 
     * Check if product is in stock
     */
    public function getInStockAttribute(): bool
    {
        return ($this->stock_quantity ?? 0) > 0 && $this->is_available;
    }
}
