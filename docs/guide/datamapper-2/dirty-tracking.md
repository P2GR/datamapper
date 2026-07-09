# Dirty Tracking <Badge type="tip" text="2.0" />

Track which attributes have been modified on a model since it was loaded from the database. Dirty tracking enables smarter saves, audit logging, conditional logic based on what changed, and undo workflows.

## Why Dirty Tracking?

- **Efficient updates** – only write changed columns to the database.
- **Audit trails** – log exactly which fields changed and their original values.
- **Conditional logic** – run side effects only when specific fields change.
- **Undo / rollback** – compare current values against originals.

## Quick Start

```php
$user = new User();
$user->get_by_id(1);

// Nothing changed yet
$user->is_dirty();   // FALSE
$user->is_clean();   // TRUE

// Modify a field
$user->email = 'new@example.com';

$user->is_dirty();          // TRUE
$user->is_dirty('email');   // TRUE
$user->is_dirty('name');    // FALSE

$user->is_clean();          // FALSE
$user->is_clean('name');    // TRUE
```

## Methods

### is_dirty()

Check whether any attribute (or specific attributes) have been modified since the model was loaded.

```php
$object->is_dirty($field = NULL)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$field` | string/array/null | Field name, array of field names, or `NULL` for any field |

**Returns:** `TRUE` if at least one of the specified fields has been modified.

```php
$user = new User();
$user->get_by_id(1);

$user->name = 'Jane';

$user->is_dirty();              // TRUE  – something changed
$user->is_dirty('name');        // TRUE  – name changed
$user->is_dirty('email');       // FALSE – email untouched
$user->is_dirty(['name', 'email']); // TRUE – at least one changed
```

### is_clean()

The inverse of `is_dirty()`. Returns `TRUE` when no modifications have been made.

```php
$object->is_clean($field = NULL)
```

```php
$user = new User();
$user->get_by_id(1);

$user->is_clean();        // TRUE – freshly loaded
$user->name = 'Jane';
$user->is_clean();        // FALSE – name was changed
$user->is_clean('email'); // TRUE – email still clean
```

### get_dirty()

Returns an associative array of all modified fields and their **current** (new) values.

```php
$object->get_dirty()
```

```php
$user = new User();
$user->get_by_id(1);

$user->name = 'Jane';
$user->email = 'jane@example.com';

$dirty = $user->get_dirty();
// array(
//     'name'  => 'Jane',
//     'email' => 'jane@example.com'
// )
```

### get_original()

Returns the original value(s) as loaded from the database, before any in-memory modifications.

```php
$object->get_original($field = NULL)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$field` | string/null | Specific field name, or `NULL` for all original values |

```php
$user = new User();
$user->get_by_id(1);

echo $user->email;                  // "john@example.com"
$user->email = 'new@example.com';

echo $user->email;                  // "new@example.com"
echo $user->get_original('email');  // "john@example.com"

$all = $user->get_original();
// array('id' => 1, 'name' => 'John', 'email' => 'john@example.com', ...)
```

### was_changed()

Check whether a field was modified during the **last successful `save()`** call. This differs from `is_dirty()` which tracks unsaved in-memory changes.

```php
$object->was_changed($field = NULL)
```

```php
$user = new User();
$user->get_by_id(1);

$user->was_changed();       // FALSE – nothing saved yet

$user->name = 'Jane';
$user->save();

$user->was_changed();       // TRUE  – name changed during save
$user->was_changed('name'); // TRUE
$user->was_changed('email');// FALSE – email was not part of the save
```

## Practical Examples

### Audit Logging

```php
class User extends DataMapper {
    protected function after_save()
    {
        // Log which fields were changed
        if ($this->was_changed()) {
            $changes = $this->_dm_changed;  // field => new_value
            log_message('info', 'User ' . $this->id . ' updated: ' . json_encode($changes));
        }
    }
}
```

### Conditional Side Effects

```php
$user = new User();
$user->get_by_id($id);

$user->from_array($this->input->post());

// Only send notification if email actually changed
if ($user->is_dirty('email')) {
    $old_email = $user->get_original('email');
    $this->notification->send_email_change_notice($old_email, $user->email);
}

$user->save();
```

### Preventing Unnecessary Saves

```php
$user = new User();
$user->get_by_id($id);

$user->from_array($this->input->post());

if ($user->is_dirty()) {
    $user->save();
    echo "Changes saved.";
} else {
    echo "No changes detected.";
}
```

### Comparing Before and After

```php
$product = new Product();
$product->get_by_id($id);

$product->price = $new_price;

if ($product->is_dirty('price')) {
    $old_price = $product->get_original('price');
    $diff = $product->price - $old_price;
    echo "Price changed by " . ($diff > 0 ? '+' : '') . $diff;
}

$product->save();
```

## How It Works

DataMapper stores a snapshot of all field values after loading from the database (or after a successful save). The `is_dirty()` family of methods compares current property values against this snapshot.

- After `get()`, `get_by_id()`, `find()`, or `save()`, the snapshot is refreshed.
- After `refresh()`, the snapshot is refreshed with fresh database values.
- After `clear()`, the snapshot is reset.

::: tip Backward Compatible
Dirty tracking is automatic and requires no changes to existing models. The snapshot mechanism is the same one DataMapper has always used internally — these methods simply expose it to your application code.
:::

## See Also

- [Model Events](/guide/datamapper-2/model-events) – fire side effects when fields change
- [Refresh](/guide/models/refresh) – discard in-memory changes
- [Save](/guide/models/save) – persist dirty fields to the database
