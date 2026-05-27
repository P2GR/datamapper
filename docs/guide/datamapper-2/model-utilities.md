# Model Utilities <Badge type="tip" text="2.0" />

A collection of convenience methods that make common model operations more expressive. These methods complement the existing DataMapper API without changing any existing behavior.

## Increment & Decrement

Atomically increase or decrease a numeric column directly in the database, without loading-then-saving. Perfect for counters, scores, and balances.

```php
$object->increment($field, $amount = 1, $extra = array())
$object->decrement($field, $amount = 1, $extra = array())
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$field` | string | The numeric column to modify |
| `$amount` | int/float | How much to add/subtract (default `1`) |
| `$extra` | array | Additional fields to update in the same query |

```php
$post = new Post();
$post->get_by_id(1);

// +1 view
$post->increment('views');

// +5 votes
$post->increment('votes', 5);

// -1 stock, also update last_sold_at
$product = new Product();
$product->get_by_id($id);
$product->decrement('stock', 1, array('last_sold_at' => date('Y-m-d H:i:s')));
```

::: tip Why Atomic?
`increment()` and `decrement()` use a single `UPDATE ... SET field = field + N` query. This avoids race conditions where two requests read the same value, both add 1, and write the same result — losing one increment.
:::

### Practical Examples

```php
// Page view counter
$page = new Page();
$page->get_by_id($page_id);
$page->increment('view_count');

// User login counter
$user = new User();
$user->get_by_id($user_id);
$user->increment('login_count', 1, array(
    'last_login_at' => date('Y-m-d H:i:s'),
    'last_login_ip' => $this->input->ip_address()
));

// Decrease balance
$wallet = new Wallet();
$wallet->get_by_id($wallet_id);
$wallet->decrement('balance', $amount);
```

---

## Model Comparison

Compare two model instances to determine if they represent the same database record.

### is()

```php
$object->is($model)
```

Returns `TRUE` if both models have the same primary key and table name.

```php
$user_a = new User();
$user_a->get_by_id(1);

$user_b = new User();
$user_b->get_by_id(1);

$user_a->is($user_b);     // TRUE – same record
$user_a->is_not($user_b); // FALSE
```

### is_not()

```php
$object->is_not($model)
```

The inverse of `is()`.

```php
$user = new User();
$user->get_by_id(1);

$admin = new User();
$admin->get_by_id(2);

$user->is_not($admin); // TRUE – different records
$user->is(NULL);        // FALSE – comparing with NULL
$user->is_not(NULL);    // TRUE
```

### Practical Example

```php
// Check if the logged-in user is the post author
$user = new User();
$user->get_by_id($this->session->userdata('user_id'));

$post = new Post();
$post->get_by_id($post_id);
$post->user->get();

if ($user->is($post->user)) {
    echo "You are the author.";
}
```

---

## Replicate

Create an unsaved copy of a model, perfect for duplicating records.

```php
$object->replicate($except = array())
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$except` | array | Additional fields to exclude (besides `id`) |

The replica is a new instance of the same class with all field values copied, except the primary key (`id`) which is set to `NULL` so the next `save()` creates a new record.

```php
$template = new Product();
$template->get_by_id(1);

$copy = $template->replicate();
$copy->name = 'Copy of ' . $template->name;
$copy->save();  // INSERT – creates a new product
```

### Excluding Fields

```php
$original = new Post();
$original->get_by_id(1);

// Copy everything except slug and published_at
$draft = $original->replicate(array('slug', 'published_at'));
$draft->status = 'draft';
$draft->save();
```

### Practical Examples

```php
// Duplicate a product listing
$product = new Product();
$product->get_by_id($id);

$copy = $product->replicate(array('sku', 'slug'));
$copy->name = $product->name . ' (Copy)';
$copy->sku = generate_sku();
$copy->slug = url_title($copy->name, '-', TRUE);
$copy->save();

// Create a template-based record
$template = new EmailTemplate();
$template->where('is_default', 1)->get();

$custom = $template->replicate();
$custom->is_default = 0;
$custom->name = 'My Custom Template';
$custom->save();
```

---

## Fresh & Refresh

### fresh()

Returns a **new** model instance loaded from the database. The current model is not modified.

```php
$object->fresh()
```

```php
$user = new User();
$user->get_by_id(1);

$user->name = 'Modified';

$fresh_user = $user->fresh();

echo $user->name;       // "Modified" – unchanged
echo $fresh_user->name; // "John" – fresh from database
```

Returns `NULL` if the model has no primary key (not persisted).

### refresh()

Reloads the current model instance in-place from the database, discarding any unsaved changes. See [Refresh](/guide/models/refresh) for full documentation.

```php
$user = new User();
$user->get_by_id(1);

$user->name = 'Modified';
$user->refresh();

echo $user->name; // "John" – restored from database
```

---

## Bulk Destroy

Delete one or more records by their primary keys without loading them first. Respects soft deletes if enabled on the model.

```php
Model::destroy($ids)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | int/array | Single ID or array of IDs to delete |

```php
// Delete a single record
User::destroy(5);

// Delete multiple records
User::destroy(array(1, 2, 3));

// With soft deletes enabled, sets deleted_at instead of removing rows
```

::: warning
`destroy()` is a static method. It creates a temporary model instance for each ID and calls `delete()` on it, which means model events (`before_delete`, `after_delete`) fire for each record.
:::

---

## Tap

Pass the model to a callback and return the model. Useful for performing side effects in the middle of a method chain without breaking the fluent flow.

```php
$object->tap($callback)
```

```php
$user = new User();
$user->get_by_id(1);

$user->tap(function ($u) {
    log_message('info', 'Processing user: ' . $u->name);
})->save();
```

### Practical Examples

```php
// Debug in a chain
$order = new Order();
$order->get_by_id($id);

$order
    ->tap(function ($o) { echo "Before: " . $o->status; })
    ->from_array($this->input->post())
    ->tap(function ($o) { echo "After: " . $o->status; })
    ->save();

// Log before saving
$product = new Product();
$product->get_by_id($id);
$product->price = $new_price;

$product->tap(function ($p) {
    if ($p->is_dirty('price')) {
        log_message('info', 'Price changing from ' . $p->get_original('price') . ' to ' . $p->price);
    }
})->save();
```

---

## Summary

| Method | Purpose | Returns |
|--------|---------|---------|
| `increment($field, $n)` | Atomic counter increase | `$this` |
| `decrement($field, $n)` | Atomic counter decrease | `$this` |
| `is($model)` | Same record? | `bool` |
| `is_not($model)` | Different record? | `bool` |
| `replicate($except)` | Unsaved copy | New model |
| `fresh()` | New instance from DB | Model or `NULL` |
| `refresh()` | Reload in-place | `$this` |
| `destroy($ids)` | Delete by PK (static) | `void` |
| `tap($callback)` | Side effect, return self | `$this` |

## See Also

- [Dirty Tracking](/guide/datamapper-2/dirty-tracking) – check what changed
- [Model Events](/guide/datamapper-2/model-events) – hooks for save/delete lifecycle
- [Clone](/guide/models/clone) – the classic `get_clone()` method
- [Refresh](/guide/models/refresh) – detailed refresh documentation
