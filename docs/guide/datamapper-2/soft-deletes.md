# Soft Deletes (DataMapper 2.0)

Safely "delete" records by marking them as deleted instead of removing them from the database. This keeps relational data intact, powers undo workflows, and satisfies audit/compliance requirements.

**New in DataMapper 2.0:** Soft deletes are now built in. The ORM automatically applies `deleted_at IS NULL` scopes, sets the timestamp on `delete()`, and exposes query builder helpers. The optional `SoftDeletes` trait adds convenience methods plus customization hooks when you need them.

## Why Soft Deletes?

- **Data recovery** – rollback accidental deletions without restoring backups.
- **Audit trail** – retain complete history for SOX/GDPR and internal reviews.
- **Relationship safety** – preserve foreign keys and prevent orphaned rows.
- **Product UX** – power trash bins, undo buttons, and staged approval flows.
- **Safer deploys** – undo migrations or seed jobs that delete too much.

## Quick Start

### 1. Add a `deleted_at` column

```sql
ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL;
```

### 2. Let DataMapper handle the plumbing

Once the column exists, DataMapper 2.0 automatically:

1. Sets `deleted_at` when you call `delete()`.
2. Adds `deleted_at IS NULL` to all queries.
3. Provides `with_softdeleted()`, `only_softdeleted()`, `restore()`, and `force_delete()` helpers.

No extra config is required.

### 3. (Optional) Pull in helper methods

```php
<?php
use DataMapper\Traits\SoftDeletes;

class User extends DataMapper {
    use SoftDeletes; // adds restore(), force_delete(), with_softdeleted(), only_softdeleted(), etc.
}
```

### 4. Configure per model

```php
class AuditLog extends DataMapper {
    public $soft_delete = FALSE; // hard-delete this model
}

class Project extends DataMapper {
    public $deleted_at_column = 'archived_at'; // custom column name
}

// Using the SoftDeletes trait? It will sync camelCase properties like
// $softDelete and $deletedAtColumn for you, so legacy configurations keep working.
```

::: tip CamelCase helpers
Prefer snake_case helpers (`with_softdeleted()`, `only_softdeleted()`, `without_softdeleted()`, `force_delete()`).
CamelCase aliases (`withSoftDeleted()`, `onlySoftDeleted()`, `withoutSoftDeleted()`, `forceDelete()`) remain available for query builder chaining.
:::

## Querying Patterns

### Normal reads (default scope)

```php
$users = (new User())->get();          // Excludes soft-deleted users
$user  = (new User())->get_by_id(5);   // NULL/empty if trashed
```

### Include soft-deleted rows

```php
$users = (new User())
    ->with_softdeleted()
    ->where('role', 'admin')
    ->get();
```

### Only soft-deleted rows

```php
$trashed = (new User())
    ->only_softdeleted()
    ->order_by('deleted_at', 'desc')
    ->get();

foreach ($trashed as $user) {
    $user->restore();
}
```

## Helper Methods

### delete()

```php
$user = new User();
$user->get_by_id(5);
$user->delete(); // sets deleted_at instead of removing the row
```

### restore()

```php
$user = new User();
$user->with_softdeleted()->get_by_id(5);
$user->restore();
```

### force_delete()

```php
$user = new User();
$user->with_softdeleted()->get_by_id(5);
$user->force_delete(); // hard delete
```

### trashed()

```php
if ($user->trashed()) {
    echo 'User is deleted';
}
```

### with_softdeleted() / only_softdeleted() / without_softdeleted()

```php
$posts = (new Post())
    ->with_softdeleted()   // include deleted posts
    ->only_softdeleted()   // focus on deleted posts
    ->limit(10)
    ->get();

$active = (new Post())
    ->with_softdeleted()
    ->without_softdeleted() // revert to deleted_at IS NULL
    ->get();
```

::: tip Legacy cache
Legacy projects may still use custom helpers such as `where_trashed()`. Migrate to the built-in
`with_softdeleted()` and `only_softdeleted()` helpers to stay forward compatible.
:::

## Working with Relationships

Soft-delete scopes also apply to eager-loaded relations. Opt-in per relation when you need trashed children:

```php
$user = (new User())
    ->with([
        'post' => function ($q) {
            $q->with_softdeleted();
        }
    ])
    ->get_by_id(1);
```

Legacy `include_related()` supports the same flag:

```php
$user = new User();
$user->include_related('post', 'title', NULL, array(
    'with_trashed' => TRUE
))->get_by_id(1);
```

## Customization & Advanced Usage

### Custom column names

```php
class Company extends DataMapper {
    public $deleted_at_column = 'removed_on';
}
```

### Disable soft deletes for specific operations

```php
$project = new Project();
$project->with_softdeleted()->get_by_id($id);
$project->force_delete();
```

### Combine traits and casting

```php
use DataMapper\Traits\HasTimestamps;
use DataMapper\Traits\SoftDeletes;

class Customer extends DataMapper {
    use HasTimestamps, SoftDeletes;

    public $casts = array(
        'deleted_at' => 'datetime'
    );
}
```

## Bulk Operations

```php
// Soft delete stale users
$stale = new User();
$stale->where('last_login <', '2024-01-01')->get();
foreach ($stale as $user) {
    $user->delete();
}

// Restore anyone deleted in the past 48 hours
$recent = new User();
$recent->only_softdeleted()->where('deleted_at >', date('Y-m-d H:i:s', strtotime('-2 days')))->get();
foreach ($recent as $user) {
    $user->restore();
}

// Purge anything trashed for more than 90 days
$archive = new User();
$archive->only_softdeleted()->where('deleted_at <', date('Y-m-d', strtotime('-90 days')))->get();
foreach ($archive as $user) {
    $user->force_delete();
}
```

## API Example

```php
class Users extends CI_Controller {
    public function delete($id) {
        $user = new User();
        $user->get_by_id($id);

        if (!$user->exists()) {
            show_404();
            return;
        }

        $user->delete();
        $this->respond(200, array(
            'message'    => 'User moved to trash',
            'deleted_at' => $user->deleted_at,
        ));
    }

    public function restore($id) {
        $user = new User();
        $user->with_softdeleted()->get_by_id($id);

        if (!$user->exists() || !$user->trashed()) {
            show_404();
            return;
        }

        $user->restore();
        $this->respond(200, array('message' => 'User restored'));
    }

    public function destroy($id) {
        $user = new User();
        $user->with_softdeleted()->get_by_id($id);

        if (!$user->exists()) {
            show_404();
            return;
        }

        $user->force_delete();
        $this->respond(200, array('message' => 'User permanently deleted'));
    }

    private function respond($status, $payload) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode(array('success' => TRUE) + $payload));
    }
}
```

## Testing Checklist

```php
public function test_soft_deletes() {
    $user = new User();
    $user->username = 'soft-delete-demo';
    $user->email    = 'demo@example.com';
    $user->save();

    $id = $user->id;

    $user->delete();
    $this->assertTrue($user->trashed());

    $fresh = new User();
    $fresh->get_by_id($id);
    $this->assertFalse($fresh->exists());

    $trashed = new User();
    $trashed->with_softdeleted()->get_by_id($id);
    $this->assertTrue($trashed->exists());

    $trashed->restore();
    $this->assertFalse($trashed->trashed());

    $trashed->force_delete();
    $gone = new User();
    $gone->with_softdeleted()->get_by_id($id);
    $this->assertFalse($gone->exists());
}
```

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Deleted records still appear | Call `delete()` (not `force_delete()`) and confirm the model exposes `deleted_at`. |
| Need to query deleted data | Chain `with_softdeleted()` or `only_softdeleted()` before `get()`. |
| Want to bypass soft deletes temporarily | Retrieve the model with `with_softdeleted()` and call `force_delete()`. |

::: tip Performance
Add an index on the soft-delete column for large tables:

```sql
CREATE INDEX idx_users_deleted_at ON users(deleted_at);
```
:::

## Related Documentation

- [Attribute Casting](casting)
- [Timestamps Trait](timestamps)
- [Model Deletion](../models/delete)
- [Model Events](../models/index#events)

## See Also

- [Troubleshooting FAQ](../../help/troubleshooting)
- [Soft delete usage patterns](../datamapper-2/index#soft-deletes)