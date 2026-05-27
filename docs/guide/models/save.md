# Save

# Save

There are a number of ways to run Save and its effect will be different depending on the condition of the object you run it on, and whether you pass in a parameter.

## Save on a New Object

Running Save on a new object, one without an ID, will see a new record created for it its relevant Database table. After saving, it will automatically populate itself with its new data, such as its ID and any changes its properties had after validation (such as an encrypted password).

```php

// Create new User
$u = new User();

// Enter values into required fields
$u->username = "foo";
$u->password = "bar";
$u->email = "foo@bar.com";

// Save new user
$u->save();

```

The new user **foo** will now have an ID and an encrypted password (as well as a salt for use later on when he logs in).

## Create in One Call <Badge type="tip" text="2.0" />

When you simply need to persist an array of attributes, use the new static `create()` helper. It fills the model, honours `$fillable` / `$guarded`, calls `save()`, and returns the model on success (or `FALSE` on failure).

```php
class User extends DataMapper {
    var $fillable = array('username', 'email', 'password');
}

$user = User::create($this->input->post());

if ($user) {
    return redirect('dashboard');
}

// Validation failed
return view('register', array('errors' => $user->error->all));
```

## Save on an Existing Object

Running Save on an existing object will update its corresponding record in the database.

```php

// Get user foo
$u = new User();
$u->where('username', 'foo')->get();

// Change the email
$u->email = "baz@qux.com";

// Save changes to existing user
$u->save();

```

As the only change is the email, the email will be updated.

## Saving new objects with an existing ID

By default, DataMapper uses the existence of the **id** field to determine whether an object exists or not. If the object exists, it is **UPDATE**d, otherwise it is **INSERT**ed.

This can cause a problem when importing new data into the system, as the data cannot be inserted with known **id**. To get around this, you can use the save_as_new method, which forces DataMapper to save the object as if it was new, but inserts the ID as well.

You might also choose to integrate this with the skip_validation method below.

***Warning:*** If the id of the object being saved is already in use in the database, this will cause a database error.

::: info

[ or [serial](http://www.postgresql.org/docs/8.3/static/sql-altersequence) for the **id** column yourself.

Failure to do this will throw an error the next time an object is saved. (For some databases, auto_increment may be corrected automatically.) An example is given below.

### Example

```php

$user = new User();
$user->id = 1;
$user->name = 'Admin';
$user->password = 'password';
$success = $user->save_as_new();
// Update MySQL AUTO_INCREMENT:
$user->db->query('ALTER TABLE `users` AUTO_INCREMENT = ' . ($user->id+1) .';');
// Update PostGreSQL SERIAL:
$user->db->query('ALTER SEQUENCE users_id_seq RESTART WITH ' . ($user->id+1) . ';');

```

## Skipping Validation

Occasionally you may want to force a save that skips validation. This might be, for example, for adminstrative purposes. To easily do this, call skip_validation before calling save.

To re-enable validation, either call get, save, or skip_validation(FALSE) on the $object.

### Example

```php

// set some invalid fields
$user->email = '';
$user->password = '';

// save without validating
$success = $user->skip_validation()->save();
if($success) // ...

```

As long as the database allows the fields, the object will be saved. Remember that database rules can still prevent the fields from being saved, and you might see database errors when saving this way.

## Check for failed validation

When you use validation on the object, validation rules are run before attempting to save the contents of the object.

### Example

```php

// set some invalid fields
$user->email = '';
$user->password = '';

// save
$success = $user->save();
if(! $success)
{
    // did validation fail?
    if ( $user->valid )
    {
        // insert or update failure
    } else {
         // validation failure, echo the errors
        foreach ( $user->error->all as $e)
        {
            echo $e . '<br />';
        }
    }
}

```

## Save a Simple Relationship

It's easy to save the relationships your objects have with each other, and there are a few ways of doing it.

***Important:*** When saving a relationship on an object, the object itself is also saved if it has changed.

### Save a Single Relation

To save a relation, you pass the object you want to relate to, into your current object.

```php

// Get user foo
$u = new User();
$u->where('username', 'foo')->get();

// Get country object for Australia
$c = new Country();
$c->where('name', 'Australia')->get();

// Relate user foo to country Australia
$u->save($c);

```

### Save Multiple Relations

To save multiple relations, you pass an object's all property or an array of objects.

```php

// Get user foo
$u = new User();
$u->where('username', 'foo')->get();

// Get country object for Australia
$c = new Country();
$c->where('name', 'Australia')->get();

// Get a number of books from the year 2000
$b = new Book();
$b->where('year', 2000)->get();

// Get a movie with ID of 5
$m = new Movie();
$m->where('id', 5)->get();

// Relate user foo to all the books
$u->save($b->all);

// Or we could pass everything in one go (it's ok to have a mix of single objects and all lists from objects)
$u->save(array($c, $b->all, $m));

```

### Save a New object and its Relations in a single call

It is important to note that you can save both an object's data and relationships with a single save call. For example, you could save a new object and its relationships all in one go like this:

```php

// Create new User
$u = new User();

// Enter values into required fields
$u->username = "foo";
$u->password = "bar";
$u->email = "foo@bar.com";

// Get country object for Australia
$c = new Country();
$c->where('name', 'Australia')->get();

// Save new user and also save a relationship to the country
$u->save($c);

```

### Save an Existing object and its Relations in a single call

In the same way, you can update an existing records data as well as its relationships with a single save call.

```php

// Get user foo
$u = new User();
$u->where('username', 'foo')->get();

// Change the email
$u->email = "baz@qux.com";

// Get country object for United States
$c = new Country();
$c->where('name', 'United States')->get();

// Update email and update the relationship to country United States
$u->save($c);

```

## Save an Advanced Relationship

The difference between saving a normal relationship and an advanced one is that you need to specify which relationship key to save the object to.

This can be handled in several ways

### $object->save_{$relationship_key}( $related )

Saves a single $related as a $relationship_key on $object.

- {$relationship_key}: Replace with the relationship key you want to save on.
- $related: The object to save.

```php

// Create Post
$post = new Post();
// save $user as the creator
$post->save_creator($user);

```

### $object->save_{$relationship_key}( $array )

Saves an $array of related objects as $relationship_keys on $object.

- {$relationship_key}: Replace with the relationship key you want to save on.
- $array: The objects to save.

```php

// Create Post
$post = new Post();
// Load in related posts.
$relatedposts = new Post();
$relatedposts->where_in($related_ids)->get();
// save related posts
$post->save_relatedpost($relatedposts->all);

```

### $object->save( $related, $relationship_key )

Saves one or more $related as a $relationship_key on $object.

- $related: The object or objects to save.
- $relationship_key: The relationship key you want to save on.

```php

// Create Post
$post = new Post();
// save $user as the creator
$post->save($user, 'creator');

```

### Saving a variety of objects

Finally, you can use associative arrays to save a variety of different relationshups

```php

// Create Post
$post = new Post();

// save $user as the creator and editor, and save related posts.
$post->save(
    array(
        'creator' => $user,
        'editor' => $user,
        'relatedpost' => $relatedposts->all
    )
);

```

## See Also

- [Model Events](/guide/datamapper-2/model-events) – `before_save`, `after_save`, `before_create`, `after_create`, `before_update`, `after_update` hooks
- [Dirty Tracking](/guide/datamapper-2/dirty-tracking) – check which fields changed before saving
- [Mass Assignment](/guide/models/mass-assignment) – safely populate models from request data
- [Timestamps](/guide/datamapper-2/timestamps) – automatic `created_at` / `updated_at`