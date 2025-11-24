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
    ->find($user_id)
    ->with('installation', function($q) {
        $q->where('active', 1);
        $q->order_by('created_at', 'DESC');
        $q->limit(10);
    })
    ->get();

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
- Works with nested relationships: with('building.client', function($q) {...})
- Constraint callback receives a DMZ_DB_Constraint_Wrapper instance
- All standard query methods available: where, or_where, like, order_by, limit, etc.

### See Also

- [Eager Loading Basics](query-builder.html#Eager.Loading)
- [Soft Delete Documentation](soft-deletes)
- [Streaming & Chunking](streaming)