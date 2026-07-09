# Model Events <Badge type="tip" text="2.0" />

Hook into the model lifecycle to run logic before or after saving, creating, updating, or deleting records. Model events let you keep business rules inside the model without cluttering controllers.

## Why Model Events?

- **Encapsulation** – keep validation, notifications, and side effects inside the model.
- **Cancellation** – prevent a save or delete by returning `FALSE` from a `before_*` hook.
- **Consistency** – guarantee that certain logic always runs, regardless of which controller triggers the operation.
- **Audit / logging** – capture exactly when and how records change.

## Quick Start

Define event methods in your model. DataMapper calls them automatically:

```php
class User extends DataMapper {

    protected function before_save()
    {
        // Runs before every insert or update
        $this->username = strtolower($this->username);
    }

    protected function after_save()
    {
        // Runs after a successful insert or update
        log_message('info', 'User ' . $this->id . ' saved');
    }
}
```

## Available Events

| Event | Fires When | Can Cancel? |
|-------|-----------|-------------|
| `before_save` | Before any insert or update | Yes |
| `after_save` | After a successful insert or update | No |
| `before_create` | Before an insert (new record) | Yes |
| `after_create` | After a successful insert | No |
| `before_update` | Before an update (existing record) | Yes |
| `after_update` | After a successful update | No |
| `before_delete` | Before a delete (soft or hard) | Yes |
| `after_delete` | After a successful delete | No |

### Execution Order

**Creating a new record:**
1. `before_save`
2. `before_create`
3. *INSERT query*
4. `after_create`
5. `after_save`

**Updating an existing record:**
1. `before_save`
2. `before_update`
3. *UPDATE query*
4. `after_update`
5. `after_save`

**Deleting a record:**
1. `before_delete`
2. *DELETE / soft-delete query*
3. `after_delete`

## Cancelling Operations

Return `FALSE` from any `before_*` event to cancel the operation. The `save()` or `delete()` call will return `FALSE` and no database query is executed.

```php
class Order extends DataMapper {

    protected function before_save()
    {
        // Prevent saving orders with a zero total
        if ($this->total <= 0) {
            return FALSE;
        }
    }

    protected function before_delete()
    {
        // Prevent deleting orders that have been shipped
        if ($this->status === 'shipped') {
            return FALSE;
        }
    }
}
```

```php
// In your controller
$order = new Order();
$order->get_by_id($id);
$order->total = 0;

if ( ! $order->save()) {
    echo "Save was cancelled by the model.";
}
```

::: warning
Only `before_*` events support cancellation. Returning `FALSE` from an `after_*` event has no effect.
:::

## Examples

### Automatically Set Defaults

```php
class Post extends DataMapper {

    protected function before_create()
    {
        // Set defaults for new posts
        if (empty($this->status)) {
            $this->status = 'draft';
        }
        if (empty($this->slug)) {
            $this->slug = url_title($this->title, '-', TRUE);
        }
    }
}
```

### Send Notifications After Create

```php
class User extends DataMapper {

    protected function after_create()
    {
        // Send welcome email to new users
        $ci =& get_instance();
        $ci->load->library('email');
        $ci->email->to($this->email);
        $ci->email->subject('Welcome!');
        $ci->email->message('Hello ' . $this->name . ', welcome aboard!');
        $ci->email->send();
    }
}
```

### Audit Trail on Updates

```php
class Invoice extends DataMapper {

    protected function before_update()
    {
        // Log what changed before the update is persisted
        $dirty = $this->get_dirty();
        if ( ! empty($dirty)) {
            $ci =& get_instance();
            $ci->db->insert('audit_log', array(
                'model'     => 'invoice',
                'record_id' => $this->id,
                'changes'   => json_encode($dirty),
                'user_id'   => $ci->session->userdata('user_id'),
                'created_at'=> date('Y-m-d H:i:s'),
            ));
        }
    }
}
```

### Protect Critical Records from Deletion

```php
class Setting extends DataMapper {

    protected function before_delete()
    {
        // Never allow system settings to be deleted
        if ($this->is_system) {
            return FALSE;
        }
    }
}
```

### Cleanup After Delete

```php
class User extends DataMapper {

    protected function after_delete()
    {
        // Remove uploaded avatar file
        if ($this->avatar && file_exists(FCPATH . 'uploads/' . $this->avatar)) {
            unlink(FCPATH . 'uploads/' . $this->avatar);
        }
    }
}
```

### Combining Events with Dirty Tracking

```php
class Product extends DataMapper {

    protected function after_update()
    {
        // Only notify if the price actually changed
        if ($this->was_changed('price')) {
            $this->notify_price_watchers();
        }
    }

    private function notify_price_watchers()
    {
        // Send price-drop alerts to subscribed users
        $watchers = new PriceWatch();
        $watchers->where('product_id', $this->id)->get();

        foreach ($watchers as $watcher) {
            // ... send notification
        }
    }
}
```

## How It Works

DataMapper checks whether your model class defines any of the event methods (`before_save`, `after_create`, etc.). If the method exists, it is called at the appropriate point in the `save()` or `delete()` lifecycle.

- **before_*** methods are called *before* the database query. If they return `FALSE`, the operation is aborted.
- **after_*** methods are called *after* a successful database query. Their return value is ignored.
- Events are called on the model instance, so `$this` refers to the model being saved or deleted.
- If no event method is defined, the operation proceeds normally — there is zero overhead for models that don't use events.

::: tip Backward Compatible
Model events are completely opt-in. Existing models that do not define event methods continue to work exactly as before. Simply add the methods you need.
:::

## See Also

- [Dirty Tracking](/guide/datamapper-2/dirty-tracking) – check what changed before saving
- [Save](/guide/models/save) – the save lifecycle
- [Delete](/guide/models/delete) – the delete lifecycle
- [Soft Deletes](/guide/datamapper-2/soft-deletes) – events fire for soft deletes too
