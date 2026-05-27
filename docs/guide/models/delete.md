# Delete

[Save](/guide/models/save) function.

***Important:*** Delete should only be used on existing objects.

## Delete on an Existing Object

Running Delete on an existing object will delete its corresponding record from the database.

***Note:*** When you delete an object, all its relations to other objects will also be deleted. Free house cleaning! :)

```php

// Get user foo
$u = new User();
$u->where('username', 'foo')->get();

// Delete user
$u->delete();

```

## Delete a Simple Relationship on an Existing Object

It's easy to delete the relationships your objects have with each other, and there are a few ways of doing it. It's

***Important:*** You can only delete relations from objects that already exist in the Database.

### Delete a Single Relation

To delete a relation, you pass the object you want to delete the relation to, into your current object.

```php

// Get user foo
$u = new User();
$u->where('username', 'foo')->get();

// Get country object for Australia
$c = new Country();
$c->where('name', 'Australia')->get();

// Delete relation between user foo and country Australia
$u->delete($c);

```

### Delete Multiple Relations

To delete multiple relations, you pass an object's all property or an array of objects.

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

// Delete relation between user foo and all the books
$u->delete($b->all);

// Or we could pass everything in one go (it's ok to have a mix of single objects and all lists from objects)
$u->delete(array($c, $b->all, $m));

```

## Delete an Advanced Relationship on an Existing Object

Just like the advanced saving, you use specialized methods to delete advanced relationships.

### $object->delete_{$relationship_key}( $related )

Deletes a single $related as a $relationship_key from $object.

- {$relationship_key}: Replace with the relationship key you want to delete from.
- $related: The object to delete.

```php

// Create Post
$post = new Post();
// delete $user from the creator
$post->delete_creator($user);

```

### $object->delete_{$relationship_key}( $array )

Deletes an $array of related objects as $relationship_keys from $object.

- {$relationship_key}: Replace with the relationship key you want to delete from.
- $array: The objects to delete.

```php

// Create Post
$post = new Post();
// Load in related posts.
$relatedposts = new Post();
$relatedposts->where_in($related_ids)->get();
// delete related posts
$post->delete_relatedpost($relatedposts->all);

```

### $object->delete( $related, $relationship_key )

Delete one or more $related as a $relationship_key from $object.

- $related: The object or objects to delete.
- $relationship_key: The relationship key you want to delete from.

```php

// Create Post
$post = new Post();
// Load in related posts.
$relatedposts = new Post();
$relatedposts->where_in($related_ids)->get();
// delete related posts
$post->delete($relatedposts, 'relatedpost');

```

### Deleting a variety of objects

Finally, you can use associative arrays to delete a variety of different relationshups

```php

// Create Post
$post = new Post();

// delete $user from the creator and editor, and delete related posts.
$post->delete(
    array(
        'creator' => $user,
        'editor' => $user,
        'relatedpost' => $relatedposts->all
    )
);

```

## See Also

- [Model Events](/guide/datamapper-2/model-events) – `before_delete` and `after_delete` hooks
- [Soft Deletes](/guide/datamapper-2/soft-deletes) – mark records as deleted instead of removing them
- [Model Utilities](/guide/datamapper-2/model-utilities) – `destroy()` for bulk deletion by ID