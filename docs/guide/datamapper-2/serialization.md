# Serialization Control <Badge type="tip" text="2.0" />

Control which fields appear when a model is converted to an array or JSON. Hide sensitive data, whitelist public fields, and append computed attributes — all declaratively on the model.

## Why Serialization Control?

- **Security** – never accidentally expose passwords, tokens, or internal flags in API responses.
- **Convenience** – define output rules once on the model instead of listing fields on every `to_array()` / `to_json()` call.
- **Computed attributes** – include derived values (full name, avatar URL, age) in serialized output automatically.

## Quick Start

```php
class User extends DataMapper {
    // Never include these fields in to_array() / to_json()
    public $hidden = array('password', 'remember_token', 'api_secret');
}
```

```php
$user = new User();
$user->get_by_id(1);

$data = $user->to_array();
// array('id' => 1, 'name' => 'John', 'email' => 'john@example.com')
// 'password', 'remember_token', 'api_secret' are excluded automatically
```

## Properties

### $hidden

An array of field names to **exclude** from `to_array()` and `to_json()` output. All other fields are included.

```php
class User extends DataMapper {
    public $hidden = array('password', 'api_secret', 'remember_token');
}
```

### $visible

An array of field names to **include** in `to_array()` and `to_json()` output. All other fields are excluded. Acts as a whitelist.

```php
class User extends DataMapper {
    // Only these fields will appear in serialized output
    public $visible = array('id', 'name', 'email', 'avatar');
}
```

::: warning
When both `$visible` and `$hidden` are set, `$visible` is applied first (whitelist), then `$hidden` removes fields from the result. In practice, use one or the other — not both.
:::

### $appends

An array of computed attribute names to **add** to `to_array()` and `to_json()` output. Each name must have a corresponding accessor method named `get_{name}_attribute()`.

```php
class User extends DataMapper {
    public $appends = array('full_name', 'avatar_url');

    public function get_full_name_attribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function get_avatar_url_attribute()
    {
        return base_url('uploads/avatars/' . $this->avatar);
    }
}
```

```php
$user = new User();
$user->get_by_id(1);

$data = $user->to_array();
// array(
//     'id' => 1,
//     'first_name' => 'John',
//     'last_name' => 'Doe',
//     'full_name' => 'John Doe',           // ← appended
//     'avatar_url' => '/uploads/avatars/john.jpg', // ← appended
//     ...
// )
```

## Examples

### API Model

A typical API model that hides sensitive data and appends computed fields:

```php
class User extends DataMapper {
    public $hidden = array('password', 'remember_token', 'api_secret');
    public $appends = array('full_name', 'is_admin');

    public function get_full_name_attribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function get_is_admin_attribute()
    {
        return $this->role === 'admin';
    }
}
```

```php
// Controller
public function get_user($id)
{
    $user = new User();
    $user->get_by_id($id);

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(array(
            'success' => TRUE,
            'data'    => $user->to_array()
        )));
}

// Response:
// {
//     "success": true,
//     "data": {
//         "id": 1,
//         "first_name": "John",
//         "last_name": "Doe",
//         "email": "john@example.com",
//         "role": "admin",
//         "full_name": "John Doe",
//         "is_admin": true
//     }
// }
```

### Whitelist for Public Profiles

```php
class User extends DataMapper {
    // Only expose these fields on public profile endpoints
    public $visible = array('id', 'username', 'bio', 'avatar', 'created_at');
}
```

```php
$user = new User();
$user->get_by_id($id);

echo $user->to_json();
// {"id":1,"username":"john","bio":"Developer","avatar":"john.jpg","created_at":"2024-01-15"}
```

### Product with Computed Price

```php
class Product extends DataMapper {
    public $hidden = array('cost_price', 'supplier_id');
    public $appends = array('display_price', 'in_stock');

    public function get_display_price_attribute()
    {
        return '$' . number_format($this->price, 2);
    }

    public function get_in_stock_attribute()
    {
        return $this->stock_quantity > 0;
    }
}
```

```php
$product = new Product();
$product->get_by_id(1);

$data = $product->to_array();
// array(
//     'id' => 1,
//     'name' => 'Widget',
//     'price' => 29.99,
//     'stock_quantity' => 42,
//     'display_price' => '$29.99',  // ← appended
//     'in_stock' => true,           // ← appended
//     // cost_price and supplier_id are hidden
// )
```

### Collection Export

Serialization control works when iterating over multiple results:

```php
class User extends DataMapper {
    public $hidden = array('password');
    public $appends = array('full_name');

    public function get_full_name_attribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
```

```php
$users = new User();
$users->where('active', 1)->get();

$result = array();
foreach ($users as $user) {
    $result[] = $user->to_array();
}

echo json_encode($result);
// Each user in the array has hidden fields removed and appends included
```

## Works with to_json()

All serialization controls apply to both `to_array()` and `to_json()`:

```php
class User extends DataMapper {
    public $hidden = array('password');
}

$user = new User();
$user->get_by_id(1);

// Both respect $hidden / $visible / $appends
$array = $user->to_array();  // password excluded
$json  = $user->to_json();   // password excluded
```

## Combining with Field Parameters

You can still pass explicit field lists to `to_array()` and `to_json()`. Serialization controls are applied **after** the field selection:

```php
class User extends DataMapper {
    public $hidden = array('password');
    public $appends = array('full_name');
}

$user = new User();
$user->get_by_id(1);

// Explicit fields + serialization control
$data = $user->to_array(array('id', 'name', 'email', 'password'));
// 'password' is still excluded by $hidden
// 'full_name' is appended
```

## How It Works

When `to_array()` or `to_json()` runs, it first builds the normal field array, then applies serialization controls in this order:

1. **$visible** – if not empty, only keep fields in the whitelist.
2. **$hidden** – remove any fields in the blacklist.
3. **$appends** – for each name, call `get_{name}_attribute()` and add the result.

::: tip Backward Compatible
If `$hidden`, `$visible`, and `$appends` are all empty arrays (the default), serialization output is identical to previous versions. No existing code is affected.
:::

## See Also

- [To Array](/guide/models/to-array) – basic array export
- [To JSON](/guide/models/to-json) – basic JSON export
- [Attribute Casting](/guide/datamapper-2/casting) – automatic type casting for serialized values
