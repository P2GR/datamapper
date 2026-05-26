# Attribute Casting, Accessors and Mutators (DataMapper 2.0)

DataMapper 2.0 includes opt-in attribute casting, accessors and mutators directly in the core model. Existing models without `$casts`, accessors or mutators continue to behave as before.

## Table of Contents

- [Basic Setup](#basic-setup)
- [Supported Cast Types](#supported-cast-types)
- [Reading and Writing Casted Values](#reading-and-writing-casted-values)
- [Accessors](#accessors)
- [Mutators](#mutators)
- [Arrays and API Output](#arrays-and-api-output)
- [Backward Compatibility](#backward-compatibility)
- [Common Mistakes](#common-mistakes)

## Basic Setup

Casting is built into `DataMapper`; no casting trait is required. Define `$casts` on the model using CodeIgniter 3 compatible property syntax.

```php
class User extends DataMapper
{
    protected $casts = array(
        'id'         => 'int',
        'age'        => 'int',
        'salary'     => 'float',
        'is_active'  => 'bool',
        'settings'   => 'array',
        'profile'    => 'object',
        'created_at' => 'datetime'
    );
}
```

When the model is hydrated from the database, those fields are converted to PHP-friendly values.

```php
$user = new User();
$user->get_by_id(1);

echo $user->age;                 // int
var_dump($user->is_active);       // bool
print_r($user->settings);         // array
echo $user->created_at->format('Y-m-d');
```

## Supported Cast Types

| Cast | Runtime value |
|------|---------------|
| `int`, `integer` | Integer |
| `float`, `double`, `real` | Float |
| `bool`, `boolean` | Boolean |
| `string` | String |
| `array`, `json` | Associative array decoded from JSON |
| `object` | `stdClass` decoded from JSON |
| `datetime`, `timestamp` | `DateTime` |
| `date` | `DateTime` normalized to a date value |

## Reading and Writing Casted Values

Casted values remain application-facing while you work with the model. DataMapper converts them back to database storage format when the model is saved.

```php
$user = new User();
$user->settings = array('theme' => 'dark', 'mail' => true);
$user->is_active = '1';
$user->created_at = '2026-05-26 10:30:00';

var_dump($user->settings);   // array('theme' => 'dark', 'mail' => true)
var_dump($user->is_active);  // bool(true)

$user->save();               // settings is stored as JSON, created_at as a date string
```

This is different from the old pattern where applications often had to call `json_decode()` after reads and `json_encode()` before saves.

## Accessors

Accessors expose computed or transformed attributes. Name them `get{AttributeName}Attribute` using StudlyCase.

```php
class User extends DataMapper
{
    public function getFullNameAttribute()
    {
        $first = isset($this->first_name) ? $this->first_name : '';
        $last = isset($this->last_name) ? $this->last_name : '';

        return trim($first . ' ' . $last);
    }
}

$user = new User();
$user->first_name = 'Ada';
$user->last_name = 'Lovelace';

echo $user->full_name; // Ada Lovelace
```

Accessors can be used for virtual attributes or for transforming existing fields on read.

## Mutators

Mutators transform values when they are assigned from outside the model. Name them `set{AttributeName}Attribute` using StudlyCase.

```php
class User extends DataMapper
{
    public function setEmailAttribute($value)
    {
        $this->email = strtolower(trim($value));
    }

    public function setPasswordAttribute($value)
    {
        if (!password_get_info($value)['algo']) {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }

        $this->password = $value;
    }
}

$user = new User();
$user->email = 'ADMIN@EXAMPLE.COM';
$user->password = 'secret';

echo $user->email; // admin@example.com
```

Inside a mutator, assign the normalized value to the stored field name directly.

## Arrays and API Output

The Array extension's `to_array()` returns database fields by default. Because casted fields are stored on the model in their PHP-friendly form after hydration, those field values are included as casted values.

```php
$user = new User();
$user->get_by_id(1);

$data = $user->to_array();
```

Virtual accessors are included only when you explicitly request them in the field list.

```php
$data = $user->to_array(array('id', 'email', 'full_name', 'settings'));
```

For saves, DataMapper uses its internal database payload conversion and reverses JSON/date casts back to storage values.

## Backward Compatibility

Casting is opt-in.

```php
class LegacyUser extends DataMapper
{
    // No $casts defined; legacy values are unchanged.
}
```

You can adopt features gradually:

- Add `$casts` to models that need typed values.
- Add accessors for computed read-only values.
- Add mutators for normalization such as emails, slugs and password hashes.
- Leave legacy models unchanged until you are ready to modernize them.

## Common Mistakes

### Using the Old AttributeCasting Trait

Do not add `use AttributeCasting;` for new models. Casting is built into the core model.

```php
class User extends DataMapper
{
    protected $casts = array('settings' => 'json');
}
```

### Using Typed Properties in CI3 Models

Avoid typed property syntax in CodeIgniter 3 models because older CI loaders and supported PHP versions may not handle it consistently.

```php
class User extends DataMapper
{
    protected $casts = array('age' => 'int');
}
```

### Using the Wrong Method Names

```php
// Not detected
public function full_name() {}
public function setEmail($value) {}

// Detected
public function getFullNameAttribute() {}
public function setEmailAttribute($value) {}
```

## See Also

- [Query Builder](query-builder) - Modern query interface
- [Mass Assignment](/guide/models/mass-assignment) - `fill()`, `create()` and guarding
- [Save & Update](/guide/models/save) - Persisting data
- [Validation](/guide/advanced/validation) - Validation rules
