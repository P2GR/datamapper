<?php
/**
 * DataMapper 2.0 - Built-in Attribute Casting Examples
 * 
 * NO TRAIT NEEDED! Just define $casts array in your model.
 * 
 * @package     DataMapper 2.0
 * @category    Examples
 */

// ============================================================================
// EXAMPLE 1: Basic Type Casting (NO TRAIT NEEDED!)
// ============================================================================

class User extends DataMapper
{
    // Just define the $casts array - that's it!
    protected $casts = array(
        'id' => 'int',
        'age' => 'int',
        'is_active' => 'bool'
    );
}

// Usage
$user = new User();
$user->get_by_id(1);

echo $user->age;  // Returns integer, not string!
// No need for: (int) $user->age


// ============================================================================
// EXAMPLE 2: JSON/Array Casting
// ============================================================================

class Post extends DataMapper
{
    protected $casts = array(
        'id' => 'int',
        'tags' => 'array',  // Automatically handles JSON encode/decode
        'metadata' => 'json'  // Same as 'array'
    );
}

// Usage
$post = new Post();
$post->title = 'My Post';
$post->tags = array('php', 'datamapper', 'codeigniter');  // Set as array
$post->save();  // Stored as JSON in database

$post->get_by_id(1);
print_r($post->tags);  // Returns array automatically!
// Array
// (
//     [0] => php
//     [1] => datamapper
//     [2] => codeigniter
// )


// ============================================================================
// EXAMPLE 3: DateTime Casting
// ============================================================================

class Article extends DataMapper
{
    protected $casts = array(
        'id' => 'int',
        'views' => 'int',
        'created_at' => 'datetime',
        'published_at' => 'datetime'
    );
}

// Usage
$article = new Article();
$article->get_by_id(1);

// Returns DateTime object automatically!
echo $article->created_at->format('F j, Y');  // "January 15, 2024"
echo $article->created_at->format('Y-m-d');   // "2024-01-15"

// Can do date math
$interval = $article->created_at->diff(new DateTime());
echo $interval->days . ' days ago';


// ============================================================================
// EXAMPLE 4: Multiple Cast Types Together
// ============================================================================

class Product extends DataMapper
{
    protected $casts = array(
        'id' => 'int',
        'name' => 'string',
        'price' => 'float',
        'stock' => 'int',
        'is_available' => 'bool',
        'features' => 'array',
        'created_at' => 'datetime'
    );
}

// Usage
$product = new Product();
$product->name = 'Laptop';
$product->price = 999.99;  // Will be float
$product->stock = '50';     // Will be cast to int
$product->is_available = 1; // Will be cast to bool
$product->features = array('SSD', '16GB RAM', 'Intel i7');
$product->save();

$product->get_by_id(1);
var_dump($product->price);        // float(999.99)
var_dump($product->stock);        // int(50)
var_dump($product->is_available); // bool(true)
var_dump($product->features);     // array(3) {...}


// ============================================================================
// EXAMPLE 5: Accessors (Computed Properties) - Still Available!
// ============================================================================

class Customer extends DataMapper
{
    protected $casts = array(
        'id' => 'int',
        'age' => 'int'
    );
    
    // Accessor - computed property
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    // Another accessor
    public function getAgeGroupAttribute()
    {
        if ($this->age < 18) return 'Minor';
        if ($this->age < 65) return 'Adult';
        return 'Senior';
    }
}

// Usage
$customer = new Customer();
$customer->first_name = 'John';
$customer->last_name = 'Doe';
$customer->age = 35;

echo $customer->full_name;  // "John Doe" (computed!)
echo $customer->age_group;  // "Adult" (computed!)


// ============================================================================
// EXAMPLE 6: Mutators (Data Transformation) - Still Available!
// ============================================================================

class Account extends DataMapper
{
    protected $casts = array(
        'id' => 'int'
    );
    
    // Mutator - automatically transforms on set
    public function setEmailAttribute($value)
    {
        $this->email = strtolower(trim($value));
    }
    
    public function setPasswordAttribute($value)
    {
        $this->password = password_hash($value, PASSWORD_DEFAULT);
    }
}

// Usage
$account = new Account();
$account->email = '  ADMIN@EXAMPLE.COM  ';
$account->password = 'mysecretpass';
$account->save();

// Email is automatically lowercased and trimmed: "admin@example.com"
// Password is automatically hashed


// ============================================================================
// EXAMPLE 7: Legacy Model (No Casting) - Still Works!
// ============================================================================

class LegacyUser extends DataMapper
{
    // No $casts defined = works exactly as before
    // 100% backward compatible!
}

// Usage - traditional way
$user = new LegacyUser();
$user->get_by_id(1);
echo $user->age;  // String (like before)


// ============================================================================
// EXAMPLE 8: Works with Fluent API
// ============================================================================

class Order extends DataMapper
{
    protected $casts = array(
        'id' => 'int',
        'total' => 'float',
        'is_paid' => 'bool',
        'items' => 'array',
        'created_at' => 'datetime'
    );
}

// Fluent API + Casting = Perfect!
$orders = (new Order())
    ->where('is_paid', true)  // Bool cast automatically
    ->where('total >', 100.00)  // Float cast automatically
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($orders as $order) {
    echo $order->total;  // Already float!
    echo $order->created_at->format('Y-m-d');  // Already DateTime!
    print_r($order->items);  // Already array!
}


// ============================================================================
// EXAMPLE 9: Migration from Trait to Built-in (Easy!)
// ============================================================================

// OLD WAY (with trait):
class OldUser extends DataMapper
{
    use DMZ_AttributeCasting;  // ❌ No longer needed!
    
    protected $casts = array(
        'age' => 'int'
    );
}

// NEW WAY (built-in):
class NewUser extends DataMapper
{
    // Trait removed - works the same!
    protected $casts = array(
        'age' => 'int'
    );
}


// ============================================================================
// SUPPORTED CAST TYPES
// ============================================================================

/*
'int' or 'integer'     - Cast to integer
'float' or 'double'    - Cast to float
'bool' or 'boolean'    - Cast to boolean
'string'               - Cast to string
'array' or 'json'      - JSON encode/decode
'datetime'             - DateTime object
'date'                 - DateTime object (date only, time set to 00:00:00)
'timestamp'            - Unix timestamp to DateTime
*/


// ============================================================================
// BENEFITS
// ============================================================================

/*
✅ NO TRAIT NEEDED - Just define $casts array
✅ 100% BACKWARD COMPATIBLE - Models without $casts work unchanged
✅ AUTOMATIC TYPE CONVERSION - No more manual casting
✅ JSON HANDLING - Automatic encode/decode
✅ DATETIME OBJECTS - Easy date manipulation
✅ ACCESSORS - Computed properties
✅ MUTATORS - Data transformation
✅ WORKS WITH FLUENT API - Perfect integration
✅ ZERO PERFORMANCE OVERHEAD - Only activates when used
✅ CLEAN CODE - Type-safe and readable
*/
