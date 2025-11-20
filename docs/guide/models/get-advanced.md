# Get (Advanced)

DataMapper has extended versions of most of its query clauses that allow for advanced querying on relationships.

#### Subsections

  - [Example](#Get.Advanced.Example)
  - [Supported Query Clauses](#Supported.Query.Clauses)
  - [Query Related Models (Known Model Name)](#_related_model)
  - [Query Related Models (Dynamic Model Name)](#_related)
  - [Deep Relationship Queries](#Deep.Relationship.Queries)
  - [Query Related Models (Existing Object)](#_related_object)
  - [Query Join Fields](#_join_field)
  - [Including Related Columns](#include_related)
  - [Deep Relationship Queries](#Deep.Relationship.Include)
  - [Including the Number of Related Items](#include_related_count)
  - [Including Join Fields](#include_join_fields)

## Example

Let's go through an example to see the benefits. Let's say we have a User model and a Group model. A group can have many users but a user can only have one group. Here's how you would look up all users belonging to the Moderator group without the advanced query:

```php

// Create user object
$u = new User();

// Get all users
$u->get();

// Loop through all users
foreach ($u as $user)
{
    // Get the current user's group
    $user->group->get();

    // Check if user is related to the Moderator group
    if ($user->group->name == 'Moderator')
    {
        // ...
    }
}

```

Here's how you would do the above, but using an advanced query:

```php

// Create user object
$u = new User();

// Get users that are related to the Moderator group
$u->where_related_group('name', 'Moderator')->get();

// ...

```

As you can see, it's a big time saver but not just in the amount of code you write, but also in the number of database queries and overall processing time.

## Supported Query Clauses

The following are the normal query clauses that you can use in the advanced queries. One of these must replace *{query}* in the methods below:

- *where*
- *or_where*
- *where_in*
- *or_where_in*
- *where_not_in*
- *or_where_not_in*
- *where_between* - Requires two values to be specified
- *or_where_between* - Requires two values to be specified
- *where_not_between* - Requires two values to be specified
- *or_where_not_between* - Requires two values to be specified
- *like*
- *not_like*
- *or_like*
- *or_not_like*
- *ilike*
- *not_ilike*
- *or_ilike*
- *or_not_ilike*
- *group_by* - For grouping results
- *having* - For grouping results
- *or_having* - For grouping results
- *order_by* - For ordering the results

## $object->{query}_related_{model}($field, $value);

There are a number of ways you can use these advanced queries, and this is the first usage format. All examples are done with the User and Group objects scenario.

- *{query}* - Replace with supported query type.
- {model} - Replace with related model name OR the **relationship key** for advanced relationships.
- $field - First parameter for chosen query type.
- $value - Second parameter for chosen query type.

Here's an example using the *where* query:

```php

// Create user
$u = new User();

// Get all users relating to the Moderator group (goes by 'group', 'name', 'Moderator')
$u->where_related_group('name', 'Moderator')->get();

```

## $object->{query}_related($model, $field, $value);

Alternatively, rather than specifying the related model as part of the method, you could instead supply it as the first parameter. You must use this format when querying deep relationships.

- *{query}* - Replace with supported query type.
- $model - Supply related model name OR the **relationship key** for advanced relationships. Also accepts deep relationships.
- $field - First parameter for chosen query type.
- $value - Second parameter for chosen query type.

Here's an example using the *where* query:

```php

// Create user
$u = new User();

// Get all users relating to the Moderator group (goes by 'group', 'name', 'Moderator')
$u->where_related('group', 'name', 'Moderator')->get();

```

::: info

If the query clause is where, and $value is a Datamapper object, Datamapper will convert the query into where_in clause and use the id's of the results stored in the object as parameters.

Here's an example of such a query:

```php

// Get a list of all male users
$u = new User();
$u->where('gender', 'M')->get();

// Get all the messages these males have posted
$p = new Post();
$p->where_related('user', 'id', $u)->get();

```

## Deep Relationship Queries

This format also accepts **deep relationships**, so you can query objects that are indirectly related to the current object.

A deep relationships is simply the name of each related object, in order, separated by a forward slash (/).

Here's an example:

```php

$u = new User();

// Get all users that are associated with a :
// -> Project that have one or more ...
//   -> Tasks whose ...
//     -> Status is labeled 'completed'
$u->where_related('project/task/status', 'label', 'completed')->get();

```

The generated query for this simple request is surprisingly complex!

```php

SELECT `users`.*
FROM `users`
LEFT OUTER JOIN `projects_users` as `projects_users` ON `projects_users`.`user_id` = `users`.`id`
LEFT OUTER JOIN `projects` as `projects` ON `projects_users`.`project_id` = `project`.`id`
LEFT OUTER JOIN `tasks` as `project_tasks` ON `project_tasks`.`project_id` = `projects`.`id`
LEFT OUTER JOIN `statuses` as `project_task_statuses` ON `project_tasks`.`status_id` = `project_task_statuses`.`id`
WHERE `project_task_statuses`.`label` = 'completed'

```

::: info

For deep queries as the example above, you should almost always call distinct, to ensure that the database doesn't return duplicate rows.

## $object->{query}_related($related_object, $field, $value);

- *{query}* - Replace with supported query type.
- $related_object - Supply related object (may not work for advanced relationships).
- **Optional:**$field - First parameter for chosen query type.
- **Optional:**$value - Second parameter for chosen query type.

Both the $field and $value parameters are optional if the $related_object contains a valid **id**.

Here's an example using the *where* query:

```php

// Create and get the Moderator group
$g = new Group();
$g->get_by_name('Moderator');

// Create user
$u = new User();

// Get all users relating to the Moderator group (goes by 'group', 'id', $g->id)
$u->where_related($g)->get();

```

Here's a similar way of doing the above, but with an unpopulated related object (no id):

```php

// Create and get the Moderator group
$g = new Group();

// Create user
$u = new User();

// Get all users relating to the Moderator group (goes by 'group', 'name', 'Moderator')
$u->where_related($g, 'name', 'Moderator')->get();

```

Which of the available usage formats you use will depend on your personal preference, although you should be consistent with your choice. It also might depend on whether you have a related object already available to use.

To find records that do not have a relation, specify '**id**' as the $field and **NULL** as the $value.

## $object->{query}_join_field($model, $field, $value);

This method allows you to query extra columns on a join table.

- *{query}* - Replace with supported query type.
- $model - A related model name OR the **relationship key** for advanced relationships, or a related object.
- $field - First parameter for chosen query type.
- $value - Second parameter for chosen query type.

::: info

You always have to include **$related_field**, even if the query is coming from a relationship. In other words, you’ll often write code like this:

```php
$user->alarm->where_join_field($user, 'wasfired', FALSE)->get();
```

Here's an example using the *where* query:

```php

// Create alarm
$alarm = new Alarm();

// Get all alarms that have not been fired for one or more users
$alarm->where_join_field('user', 'wasfired', FALSE)->get();

```

[Working with Join Fields](/guide/models/get-advanced#include_join_fields) for more details.

# Get (Advanced Selection)

You can also perform some more advanced options when selecting columns, by including columns from related models or from the join table.

::: tip DataMapper 2.0
`include_related()` is still available for legacy code, but new applications should prefer the query builder `with()` eager-loading API introduced in 2.0:

```php
$posts = (new Post())
    ->with('user', fn($q) => $q->select('id', 'name'))
    ->get();

foreach ($posts as $post) {
    echo $post->user->name; // Relation already hydrated
}
```

`with()` loads full related models, supports constraints, and avoids column naming collisions. Use `include_related()` only when you explicitly need legacy-style column flattening.
:::

## $object->include_related($model, $fields = NULL, $prefix = TRUE, $instantiate = FALSE)

Includes the all or some of the columns from a related object. By default, this method adds a prefix based on $model to every column. If for some reason the included column overlaps with a field already in the $object, that column is skipped. This method can significantly reduce your query overhead.

- $model - A related model name OR the **relationship key** for advanced relationships, or a related object. Also accepts deep relationships.
- $fields - NULL or '*' to include all columns. To specify a subset of columns (recommended), replace with a single value, or an array of column names.
- $prefix - If TRUE, prepend "{$model}_" to the column names. If FALSE, don't prepend anything. If any string, prepend "{$prefix}_" to each column.
- $instantiate - If TRUE, then actual objects are instantiated and populated with the columns automatically.

Here's an example:

```php

// Create User
$u = new User();

// add the group id and name to all users returned
$u->include_related('group', array('id', 'name'))->get();

foreach($u as $user) {
    echo("{$user->group_name} ({$user->group_id})\n");
}

```

If you use $instantiate, then you can use the related objects directly, like so:

```php

// Create User
$u = new User();

// add the group id and name to all users returned
$u->include_related('group', array('id', 'name'), TRUE, TRUE)->get();

foreach($u as $user) {
    echo("{$user->group->name} ({$user->group->id})\n");
}

```

***Important:*** This method creates a full join on both tables. Make sure to use the appropriate where clauses, and/or use DISTINCT, to limit the number of rows in the result!

## Including Fields from Deep Relationships

This method also supports deep relationships. You can only include columns from objects that are related by single relationships all the way. The default column prefix for deep relationships is to replace all forward slashes with underscores. You can still override this to be whatever you want.

A deep relationship is simply the name of each related object, in order, separated by a forward slash (/).

Here's an example:

```php

// Create Post
$p = new Post();

// Include the user's name in the result:
$p->include_related('user', 'name');
// include the user's group's name in the result:
$p->include_related('user/group', 'name');
$p->get();

foreach($p as $post) {
    echo("{$post->user_name} ({$post->user_group_name})\n");
}

```

At this time, deep relationships **do not support instatiation**.

## $object->include_related_count($related_field, $alias = NULL)

This method can be used to include the number of related items. By default, this is stored in the alias **{$related_field}_count**, but you can override this alias using the second argument. This method also supports using deep relationships, although the operation may fail for relationships that are not has_one (excluding, of course, the last).

[subqueries](/guide/advanced/subqueries).

Example:

```php

$groups = new Group();

$groups->include_related_count('user')->get();

foreach($groups as $group) {
    echo("The group {$group->name} has {$group->user_count} User(s)\n");
}

```

## $object->include_join_fields()

There are no options for this method. Set it right **before** adding a relationship. You can either use it before a **{$query}_related_{$model}**, or before calling **get()** on a related item. All fields on the table that are not part of the relationship are included, and are prepended with **"join_"**.

This method may return unexpected results or throw errors with deep relationships.

Usage:

```php

// Create User
$u = new User();
$u->get_by_id($userid);

// get all alarms for this user, and include the extra 'wasfired' field
$u->alarm->include_join_fields()->get();

foreach($u->alarm as $alarm) {
    if($alarm->join_wasfired) {
        echo("{$alarm->name} was fired\n");
    } else {
        echo("{$alarm->name} was NOT fired\n");
    }
}

```

[Working with Join Fields](/guide/models/get-advanced#include_join_fields) for more details.