# Subqueries

Datamapper ORM supports creating and using subqueries to help refine your query, as well as selecting the results of subqueries.

::: info

Some notes on subqueries:

- The availability of subquery functions may depend on your database.
- If the *$db_params* configuration option is set to FALSE, subqueries will not work.
- Subqueries may have adverse effects on query performance.
- Subqueries are fairly difficult. If you are not comfortable writing subqueries in raw SQL, you will most likely have trouble using the DataMapper methods. As they are only usually necessary in very rare occasions, please use normal query methods whenever possible.

## Building Subqueries

Subqueries are built using **the exact same ActiveRecord and Datamapper ORM methods** used for normal query generation. (They can also be passed in as a manually generated string.) For creating a subquery, these methods must be called on a different object than the parent query. The object is then passed back into the main query, using one of the various supported methods.

### Working with the Parent Query

Subqueries can contain references to the parent query, using the special notation ${parent}.fieldname. Note that this notation must be written exactly, with the dollar-sign on the outside of the braces. Make sure that $escape is set to FALSE if ${parent} is used with a standard query clause.

Referencing the parent query by table name **will not work**, as the table name is automatically replaced throughout the query.

## $object->select_subquery($subquery, $alias)

A subquery can be used as a result column. In this format, the subquery is always first, and the alias is required.

CodeIgniter has an overly aggressive method for protecting identifiers, and it **cannot** be disabled. This may break any attempt to include subqueries in the SELECT statement.

However, with a simple adjustment to the _protect_identifiers method of the DB_driver class, you can get it working again.

[See the bottom of the functions page for the code modification.](/reference/functions#Protect.Identifiers.Fix)

### Example

```php

$u = new User();
$bugs = $u->bug;

// Select the number of open bugs for a user
// Build the subquery - but don't call get()!
$bugs->select_func('COUNT', '*', 'count')
$bugs->where_related_status('closed', FALSE)
$bugs->where_related('user', 'id', '${parent}.id');

// add to the users query
$u->select_subquery($bugs, 'bug_count');
$u->get();

```

[include_related_count](/guide/models/get-advanced#include_related_count)

## $object->{query}_subquery($subquery, [$value]) OR $object->{query}_subquery($field, $subquery)

where statements, ordering, and [other supported query clauses](/guide/models/get-advanced#Supported.Query.Clauses).

The subquery can either be first (such as for order_by statements) or second (such as where or where_in statements).

Example

```php

// This can much, much easier be queried using the normal where_related methods, but it provides an example
$u = new User();

$sub_u = new User();

$sub_u->select('id')->where_related_group('id', 1);

$u->where_in_subquery('id', $sub_u)->get();

```

## $object->{query}_related_subquery($related_model, $related_field = 'id', $subquery)

Works the same as above, except the column compared to can come from a related object, not just this object.

```php

// This can much, much easier be queried using the normal where_related methods, but it provides an example
$u = new User();
$g = $u->group;

$g->where('id', 1);

$u->where_in_related_subquery('group', $g);

```