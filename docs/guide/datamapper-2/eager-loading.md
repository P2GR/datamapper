# Eager Loading with Constraints

**New in DataMapper 2.0:** Apply WHERE conditions, ordering, and limits to eager-loaded relationships while maintaining N+1 prevention!

Eager loading constraints allow you to filter, sort, and limit related records at the database level, reducing data transfer and improving performance without losing the benefits of eager loading.

## Table of Contents

- [Why Use Constraints?](#Why.Constraints)
- [Basic Syntax](#Basic.Syntax)
- [Filtering Related Records](#Filter.Relations)
- [Multiple Constraints](#Multiple.Constraints)
- [Soft Delete Control](#Soft.Deletes)
- [Performance Benefits](#Performance)
- [Real-World Examples](#Real.World)

## Why Use Constraints?

**Without constraints:** You load ALL related records, then filter in PHP

```php

// Inefficient: Load all installations, filter in PHP
$users = (new User())
    ->with('installation')  // Loads ALL installations
    ->get();

foreach ($users as $user) {
    foreach ($user->installation as $installation) {
        if ($installation->active === 1) {  // Filter after loading
            echo $installation->title;
        }
    }
}

```

**With constraints:** Database filters before loading - only active installations!

```php

// Efficient: Load only active installations
$users = (new User())
    ->with('installation', function($q) {
        $q->where('active', 1);  // Filter at DATABASE level
    })
    ->get();

foreach ($users as $user) {
    foreach ($user->installation as $installation) {
        echo $installation->title;  // Already filtered
    }
}

```

## Basic Syntax

Pass a callback function as the second parameter to with():

```php

$model->with('relationship', function($query) {
    // $query is a DMZ_DB_Constraint_Wrapper
    // Apply any WHERE, ORDER BY, LIMIT, etc.
    $query->where('field', value);
    $query->order_by('field', 'ASC');
    $query->limit(10);
});

```

Load several relationships by passing multiple strings or an array:

```php
$users = (new User())
    ->with('profile', 'post', 'role')
    ->get();

$users = (new User())
    ->with(array('profile', 'post', 'role'))
    ->get();
```

## Filtering Related Records

### WHERE Conditions

```php

// Load users with only their ACTIVE installations
$users = (new User())
    ->with('installation', function($q) {
        $q->where('active', 1);
    })
    ->get();

```

### Multiple WHERE Conditions

```php

// Load users with active installations created this year
$users = (new User())
    ->with('installation', function($q) {
        $q->where('active', 1);
        $q->where('created_at >=', '2024-01-01');
    })
    ->get();

```

### Ordering Results

```php

// Load users with their 5 most recent posts
$users = (new User())
    ->with('post', function($q) {
        $q->order_by('created_at', 'DESC');
        $q->limit(5);
    })
    ->get();

```

## Multiple Relationships with Different Constraints

Apply different constraints to each relationship:

```php

$installations = (new Installation())
    ->with('building', function($q) {
        $q->where('active', 1);  // Only active buildings
    })
    ->with('installationtype', function($q) {
        $q->where('category', 'BMI');  // Only BMI types
    })
    ->get();

```

## Nested Eager Loading

Load deeply nested relationships using **dot notation**:

```php

// Load building AND the building's client
$installations = (new Installation())
    ->with('building.client')
    ->get();

// Access nested data
foreach ($installations as $install) {
    echo $install->building->client->name;
}

```

### Multiple Nested Relations

```php

// Load multiple nested relations
$installations = (new Installation())
    ->with([
        'building.client',
        'building.address',
        'installationtype'
    ])
    ->get();

```

### Constraints on Nested Relations

Apply constraints to the **deepest** relation in the chain:

```php

// Constraint applies to 'client', not 'building'
$installations = (new Installation())
    ->with('building.client', function($q) {
        $q->where('active', 1);  // Only active clients
    })
    ->get();

```

::: danger Common Mistake
**Do NOT nest `with()` calls inside constraint callbacks!**

```php
// WRONG - This will throw an error
->with('building', function($q) {
    $q->with('client');  // Cannot call with() here!
})

// CORRECT - Use dot notation instead
->with('building.client')
```

Constraint callbacks are for filtering (WHERE, ORDER BY, LIMIT), not for nesting relationships.
:::

## Soft Delete Control

**Automatic Soft Delete Filtering:** DataMapper 2.0 automatically excludes soft-deleted records from eager-loaded relationships!

### Default Behavior (Excludes Deleted)

```php

// Automatically excludes deleted installations
$users = (new User())
    ->with('installation')  // Only active (non-deleted)
    ->get();

```

### Include Soft-Deleted Records

```php

// Include soft-deleted installations
$users = (new User())
    ->with('installation', function($q) {
        $q->with_softdeleted();  // Include deleted
    })
    ->get();

```

### Only Soft-Deleted Records

```php

// Load ONLY deleted installations
$users = (new User())
    ->with('installation', function($q) {
        $q->only_softdeleted();  // Only deleted
    })
    ->get();

```

### Explicitly Exclude Deleted

```php

// Explicitly exclude (same as default)
$users = (new User())
    ->with('installation', function($q) {
        $q->without_softdeleted();
    })
    ->get();

```

## Performance Benefits

## Real-World Examples

### Dashboard: Recent Active Installations

```php

// Load user with their 10 most recent active installations
$user = (new User())
    ->with('installation', function($q) {
        $q->where('active', 1);
        $q->order_by('created_at', 'DESC');
        $q->limit(10);
    })
    ->find($user_id);

```

### Admin Panel: Users with Pending Items

```php

// Load users with pending approvals only
$users = (new User())
    ->with('post', function($q) {
        $q->where('status', 'pending');
        $q->order_by('created_at', 'ASC');
    })
    ->get();

```

### API: Paginated with Relationships

```php

// API endpoint with constrained relationships
$buildings = (new Building())
    ->where('active', 1)
    ->with('installation', function($q) {
        $q->where('active', 1);
        $q->limit(5);  // Max 5 installations per building
    })
    ->limit(20)
    ->get();

```

### Important Notes

- Constraints do NOT increase query count - still just 2 queries per relationship!
- Soft delete filtering is automatic unless you explicitly use with_softdeleted()
- **Use dot notation for nested relationships:** `with('building.client')` not `with('building', fn($q) => $q->with('client'))`
- Constraint callbacks are for filtering only (WHERE, ORDER BY, LIMIT) - not for nesting
- Constraint callback receives a DMZ_DB_Constraint_Wrapper instance
- All standard query methods available: where, or_where, like, order_by, limit, etc.

### See Also

- [Eager Loading Basics](query-builder.html#Eager.Loading)
- [Soft Delete Documentation](soft-deletes)
- [Streaming & Chunking](streaming)