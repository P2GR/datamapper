# Update

# Update

If you want to update multiple objects or rows at the same time, you can do that easily using the update method. This method accepts one or more field-value pairs, and can use many of the existing DataMapper functions to determine which columns to update.

Be careful with this method. Without having limited it with where statements or similar methods it will modify **every single row on the table**!

Also, this method **bypasses validation**, and can also operate on in-table foreign keys, so please be aware of the risks.

## Basic Updates

### Set a Field in Every Row to the Same Value

The simplest form of update is to update every single row in a table at once.

```php

// Mark all users as new
$user = new User();
$success = $user->update('isnew', TRUE);

```

```php

UPDATE `users`
SET `isnew` = TRUE

```

### Limiting Which Rows Are Updated

[ or [Get (Advanced)](/guide/models/get-advanced) sections.

```php

// Mark all users that have expired for deletion.
$user = new User();
$year = 365*24*60*60;
$user->where('last_accessed <', time()-$year)->update('mark_for_deletion', TRUE);

```

## Updating Multiple Columns

You can pass an array in as the first parameter if you need to update more than one column at a time.

```php

// Reset Changes
$user = new User();
$user->update(array('mark_for_deletion' => FALSE, 'isnew' => FALSE));

```

## Using Formulas in Updates

The update method accepts a third parameter that, when FALSE, allows you to specify formulas.

```php

// Added a new column, set it to the all upper-case version of the user's name.
$user = new User();
$user->update('ucase_name', 'UPPER(name)', FALSE);

```

### Using formulas with multiple columns

You can also use formulas with multiple columns, just pass FALSE as the second parameter.

```php

$pet = new VirtualPet();
$pet->update(array('hunger' => 'hunger + 1', 'tiredness' => 'tiredness + 1'), FALSE);

```

Datamapper ORM will attempt to add the table name to values when using formulas. The table name is only added when the value is in the form "field ...", where field is a field on the table, and ... is anything. The space is required. In the example above, the value would become virtualpets.hunger + 1. The identifiers are **not** protected.

## Getting the Number of Affected Rows

[[existing CodeIgniter method](http://codeigniter.com/user_guide/database/helpers):

```php

$user = new User();
$year = 365*24*60*60;
$user->where('last_accessed <', time()-$year)->update('mark_for_deletion', TRUE);
$affected = $user->db->affected_rows();
echo("$affected user accounts were marked for deletion.");

```

Please note that not all databases support this feature on all methods.

# Update All

Because CodeIgniter's AcitveRecord methods do not allow for joins within UPDATE queries, it is not possible to simply update related items.

To help with this, there's an additonal method called update_all, which will use the ids of the objects in the all array. Use it like this:

```php

$group = new Group();
$group->where('name', 'Administrators')->get();
// You only need to select the ID column, however the select() is optional.
$group->user->select('id')->get();
$group->user->update_all('is_all_powerful', TRUE);

```

You can use any set of objects for this method. It uses where_in on the backside to filter the results.