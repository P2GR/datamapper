# Get

[Active Record](http://codeigniter.com/user_guide/database/active_record) class. All the relevant query clauses from Active Record are available in DataMapper so you have the full power of retrieving data, in Active Record style!

**Note:** There are enough differences between CodeIgniter and DataMapper's Active Record like query clauses that you should read on to be able to take full advantage of it.

Now, let's look at all the available methods. We'll assume we have a DataMapper model setup, named Object.

## Subsections

- [Basic Get](#Get) (This Section)
- [Field Selection](#Field.Selection)
- [Limiting Results](#Limiting.Results)
- [Query Grouping](#Query.Grouping)
- [Other Features](#Other.Features)
- [Method Chaining](#Method.Chaining)
- [Active Record Caching](#Active.Record.Caching)

## $object->get();

Runs the selection query and returns the result. Can be used by itself to retrieve all records from a table:

```php

$o = new Object();
$o->get();

// The $o object is populated with all objects from its corresponding table

```

The first and second parameters enable you do set a limit and offset clause:

```php

$o = new Object();
$o->get(10, 20);

// The $o object is populated with 10 objects from its corresponding table, starting from record 20

```

You can view the results in a couple of ways. Viewing the first result:

```php

$o = new Object();
$o->get();

echo $o->title;

```

Viewing all results:

```php

$o = new Object();
$o->get();

foreach ($o as $obj)
{
    echo $obj->title;
}

```

[get_iterated](/guide/models/get-iterated#get_iterated).

## $object->validate->get();

Normally, get() will generate its query from building up any query clauses you have setup before calling get(). If none are setup, it will default to selecting all records from the objects corresponding table. However, there is a special situation where get() will use the values present within the current object. This happens if you run the validate() function before a get() call.

**Note:** When doing $object->validate()->get(); all other query clauses (such as select, where etc) will be ignored.

[Getting Started](/guide/getting-started/introduction) page. Taking part of the example from there, we see that the User model is setup to encrypt the password field with the salt from the matching users stored record (by username), when they attempt to login.

### User model (excerpt)

```php
105
106
107
108
109
110
111
112
113
114
115
116
117
118
119
120
121
122
123
124
125
126
127
128
129
130
131
132
133
134
135
136
137
138
139
140
141
142    function login()
    {
        // backup username for invalid logins
        $uname = $this->username;

        // Create a temporary user object
        $u = new User();

        // Get this users stored record via their username
        $u->where('username', $uname)->get();

        // Give this user their stored salt
        $this->salt = $u->salt;

        // Validate and get this user by their property values,
        // this will see the 'encrypt' validation run, encrypting the password with the salt
        $this->validate()->get();

        // If the username and encrypted password matched a record in the database,
        // this user object would be fully populated, complete with their ID.

        // If there was no matching record, this user would be completely cleared so their id would be empty.
        if ($this->exists())
        {
            // Login succeeded
            return TRUE;
        }
        else
        {
            // Login failed, so set a custom error message
            $this->error_message('login', 'Username or password invalid');

            // restore username for login field
            $this->username = $uname;

            return FALSE;
        }
    }

```

Here's how the models login function was called. You can see the username and unencrypted password is set on the user object before calling the login function.

### Controller (excerpt)

```php

        // Create user object
        $u = new User();

        // Put user supplied data into user object
        // (no need to validate the post variables in the controller,
        // if you've set your DataMapper models up with validation rules)
        $u->username = $this->input->post('username');
        $u->password = $this->input->post('password');

        // Attempt to log user in with the data they supplied, using the login function setup in the User model
        // You might want to have a quick look at that login function up the top of this page to see how it authenticates the user
        if ($u->login())
        {
                echo '<p>Welcome ' . $this->username . '!</p>';
                echo '<p>You have successfully logged in so now we know that your email is ' . $this->email . '.</p>';
        }
        else
        {
                // Show the custom login error message
                echo '<p>' . $this->error->login . '</p>';
        }

```

So, inside, the models login function, $object->validate->get(); is called which runs the validation functions, defined in the model, on the objects properties, and then it does a get using the validated properties.

## $object->get_where();

Identical to the above function except that it permits you to add a "where" clause in the first parameter, instead of using the $object->where() function:

```php

$o = new Object();
$o->get_where(array('id' => $id), $limit, $offset);

```

Please read the where function below for more information.

# Field Selection

Use the following methods to limit or change which fields are selected.

[ and [Subqueries](/guide/advanced/subqueries).

## $object->select();

Permits you to write the SELECT portion of your query:

```php

$o = new Object();
$o->select('title, description');

$o->get();

// The $o object is populated with all objects from its corresponding table, but with only the title and description fields populated

```

**Note:** If you are selecting all (*) from a table you do not need to use this function. When omitted, DataMapper assumes you wish to SELECT *

## $object->select_max();

Writes a "SELECT MAX(field)" portion for your query. You can optionally include a second parameter to rename the resulting field.

```php

$o = new Object();
$o->select_max('age');
$o->get();

// The $o object is populated with a single object from its corresponding table, but with only the age field populated, which contains the maximum age

```

## $object->select_min();

Writes a "SELECT MIN(field)" portion for your query. As with select_max(), You can optionally include a second parameter to rename the resulting field.

```php

$o = new Object();
$o->select_min('age');
$o->get();

// The $o object is populated with a signle object from its corresponding table, but with only the age field populated, which contains the minimum age

```

## $object->select_avg();

Writes a "SELECT AVG(field)" portion for your query. As with select_max(), You can optionally include a second parameter to rename the resulting field.

```php

$o = new Object();
$o->select_avg('age');
$o->get();

// The $o object is populated with a single object from its corresponding table, but with only the age field populated, which contains the average age

```

## $object->select_sum();

Writes a "SELECT SUM(field)" portion for your query. As with select_max(), You can optionally include a second parameter to rename the resulting field.

```php

$o = new Object();
$o->select_sum('age');
$o->get();

// The $o object is populated with a single object from its corresponding table, but with only the age field populated, which contains the sum of all ages

```

## $object->distinct();

Adds the "DISTINCT" keyword to a query

```php

$o = new Object();
$o->distinct();

// When $o->get() is called, a DISTINCT select of records will be made

```

# Limiting Results

Use the following methods to limit or change which rows are returned.

[ and [Subqueries](/guide/advanced/subqueries) in queries.

## $object->where();

This function enables you to set **WHERE** clauses using one of four methods:

**Note:** All values passed to this function are escaped automatically, producing safer queries.

```php

$o = new Object();
$o->where('name', $name);
// When $o->get() is called, the above where clause will be included in the get query
```

If you use multiple where function calls they will be chained together with AND between them:

```php

$o = new Object();
$o->where('name', $name);
$o->where('title', $title);
$o->where('status', $status);
// When $o->get() is called, all of the above where clause will be included in the get query

```

You can include an operator in the first parameter in order to control the comparison:

```php

$o = new Object();
$o->where('name !=', $name);
$o->where('id <', $id);
// When $o->get() is called, all of the above where clause will be included in the get query (with operators)

```

```php

$o = new Object();
$array = array('name' => $name, 'title' => $title, 'status' => $status);
$o->where($array);
// When $o->get() is called, the array of where clauses will be included in the get query

```

You can include your own operators using this method as well:

```php

$array = array('name !=' => $name, 'id <' => $id, 'date >' => $date);
$o = new Object();
$o>where($array);

```

You can write your own clauses manually:

```php

$where = "name='Joe' AND status='boss' OR status='active'";
$o = new Object();
$o->where($where);

```

## $object->or_where();

This function is identical to the one above, except that multiple instances are joined by OR:

```php

$o = new Object();
$o->where('name !=', $name);
$o->or_where('id >', $id);
// When $o->get() is called, all of the above where clause will be included in the get query separated by OR's

```

## $object->where_in();

Generates a WHERE field IN ('item', 'item') SQL query joined with AND if appropriate

```php

$o = new Object();
$names = array('Frank', 'Todd', 'James');
$o->where_in('username', $names);
// When $o->get() is called, all records where the username is Frank, Todd, or James will be returned

```

## $object->or_where_in();

Generates a WHERE field IN ('item', 'item') SQL query joined with OR if appropriate

```php

$o = new Object();
$firstnames = array('Frank', 'Todd', 'James');
$lastnames = array('Smith', 'Jones');
$o->where_in('firstname', $firstnames);
$o->or_where_in('lastname', $lastnames);
// When $o->get() is called, all records where the firstname is Frank, Todd, or James, or all records where the lastname is Smith or Jones, will be returned

```

## $object->where_not_in();

Generates a WHERE field NOT IN ('item', 'item') SQL query joined with AND if appropriate

```php

$o = new Object();
$names = array('Frank', 'Todd', 'James');
$o->where_not_in('username', $names);
// When $o->get() is called, all records where the username is not Frank, Todd, or James will be returned

```

## $object->or_where_not_in();

Generates a WHERE field NOT IN ('item', 'item') SQL query joined with OR if appropriate

```php

$o = new Object();
$firstnames = array('Frank', 'Todd', 'James');
$lastnames = array('Smith', 'Jones');
$o->where_not_in('firstname', $firstnames);
$o->or_where_not_in('lastname', $lastnames);
// When $o->get() is called, all records where the firstname is not Frank, Todd, or James, or all records where the lastname is not Smith or Jones, will be returned

```

## $object->like();

This function enables you to generate **LIKE** clauses, useful for doing searches.

[ilike](#ilike) below.

**Note:** All values passed to this function are escaped automatically.

```php

$o = new Object();
$o->like('title', 'match');
// When $o->get() is called, all records with a title like match will be returned

```

If you use multiple function calls they will be chained together with AND between them:

```php

$o = new Object();
$o->like('title', 'match');
$o->like('body', 'match');
// When $o->get() is called, all records with a title like match and a body like match will be returned

```

If you want to control where the wildcard (%) is placed, you can use an optional third argument. Your options are 'before', 'after' and 'both' (which is the default).

```php

$o = new Object();
$o->like('title', 'match', 'after');
// When $o->get() is called, all records with a title starting with match will be returned

```

```php

$array = array('title' => $match, 'page1' => $match, 'page2' => $match);
$o = new Object();
$o->like($array);
// When $o->get() is called, all records with the title, page1, and page2 like the specified matches will be returned

```
- **Associative array method:**

## $object->or_like();

This function is identical to the one above, except that multiple instances are joined by OR:

```php

$o = new Object();
$o->like('title', 'match');
$o->or_like('body', $match);
// When $o->get() is called, all records with a title like match or a body like match will be returned

```

## $object->not_like();

This function is identical to **like()**, except that it generates NOT LIKE statements:

```php

$o = new Object();
$o->not_like('title', 'match');
// When $o->get() is called, all records with a title not like match will be returned

```

## $object->or_not_like();

This function is identical to **not_like()**, except that multiple instances are joined by OR:

```php

$o = new Object();
$o->like('title', 'match');
$o->or_not_like('body', 'match');
// When $o->get() is called, all records with a title like match or a body not like match will be returned

```

## $object->ilike();

[like](#like) methods. However, they convert both the query and the column to upper case first, to ensure case-insensitive matching. This method is better than writing your own, because it can protect identifiers and the string properly.

Also available as or_ilike, not_ilike, and or_not_ilike.

# Query Grouping

You can create more advanced queries by grouping your clauses. This allows you to specify construct such as (a OR b) AND (c OR NOT d).

***Note:*** Every group_start must be balanced by exactly one group_end.

## $object->group_start()

Starts a group. Every statement generated until group_end will be joined by an AND to the rest of the query. Groups can be nested.

Example below.

## $object->or_group_start

Every statement generated until group_end will be joined by an OR to the rest of the query.

## $object->not_group_start

Every statement generated until group_end will be joined by an AND NOT to the rest of the query.

## $object->or_not_group_start

Every statement generated until group_end will be joined by an OR NOT to the rest of the query.

## $object->group_end

Ends the most recently started group.

### Grouping Example

```php

$o = new Object();

// Returns all objects where a, or where b AND c
// SQL: a OR b AND c
$o->where('a', TRUE)->or_where('b', TRUE)->where('c', TRUE)->get();

// Returns all objects where a, and where b or c
// SQL: a AND (b OR c)
$o->where('a', TRUE)->group_start()->where('b', TRUE)->or_where('c', TRUE)->group_end()->get();

// Returns all objects where a AND b, or where c
// SQL: (a AND b) OR c
$o->group_start()->where('a', TRUE)->where('b', TRUE)->group_end()->or_where('c', TRUE)->get();

```

### Nested Grouping Example

```php

// Generates:
// (a AND (b OR c)) AND d
$o->group_start()
    ->where('a', TRUE)
    ->group_start()
        ->where('b', TRUE)
        ->or_where('c', TRUE)
    ->group_end()
->group_end()
->where('d', TRUE)->get();

```

# Other Features

[Get (Advanced)](/guide/models/get-advanced#Get.Advanced).)

## $object->group_by();

Permits you to write the GROUP BY portion of your query:

```php

$o = new Object();
$o->group_by('title');
// When $o->get() is called, all returned records will be grouped by title

```

You can also pass an array of multiple values as well:

```php

$o = new Object();
$o->group_by('title', 'date');
// When $o->get() is called, all returned records will be grouped by title and then date

```

## $object->having();

Permits you to write the HAVING portion of your query. There are 2 possible syntaxe, 1 argument or 2:

```php

$o = new Object();
$o->having('user_id = 45');

// When $o->get() is called, all records having a user_id of 45 will be returned

$o->having('user_id',  45);
// As above, when $o->get() is called, all records having a user_id of 45 will be returned

```

You can also pass an array of multiple values as well:

```php

$o = new Object();
$o->having(array('title =' => 'My Title', 'id <' => $id));
// When $o->get() is called, all records having a title of My Title and an id less than 45 will be returned

```

If you are using a database that CodeIgniter escapes queries for, you can prevent escaping content by passing an optional third argument, and setting it to FALSE.

```php

$o = new Object();
$o->having('user_id',  45, FALSE);

```

## $object->or_having();

Identical to having(), only separates multiple clauses with "OR".

## $object->order_by();

Lets you set an ORDER BY clause. The first parameter contains the name of the column you would like to order by. The second parameter lets you set the direction of the result. Options are asc or desc, or random.

```php

$o = new Object();
$o->order_by("title", "desc");
// When $o->get() is called, all returned records will be ordered by title descending

```

You can also pass your own string in the first parameter:

```php

$o = new Object();
$o->order_by('title desc, name asc');
// When $o->get() is called, all returned records will be ordered by title descending, then name ascending

```

Or multiple function calls can be made if you need multiple fields.

```php

$o = new Object();
$o->order_by("title", "desc");
$o->order_by("name", "asc");
// When $o->get() is called, all returned records will be ordered by title descending, then name ascending

```

Note: random ordering is not currently supported in Oracle or MSSQL drivers. These will default to 'ASC'.

## Default Order By

You can specify a default order to your classes, by setting the variable *$default_order_by*.

```php

class Task extends DataMapper {
    ...
    // Default to sorting tasks with overdue tasks at the top, then priority, then title.
    var $default_order_by = array('overdue' => 'desc', 'priority' => 'desc', 'title');
    ...
}

```

Now whenever you call, for example, $task->get() or $user->tasks->get(), the results will automatically be sorted.

::: info

To prevent SQL errors, automatic sorting is disabled in these cases:

- If no default sort order has been specified.
- If you specify your own sort order, using a order_by method.
- The query does not have ***** or **table.*** selected. This would only be when you have overridden the default selection.

## $object->limit();

Lets you limit the number of rows you would like returned by the query:

```php

$o = new Object();
$o->limit(10);
// When $o->get() is called, the number of records returned will be limited to 10

```

The second parameter lets you set a result offset.

```php

$o = new Object();
$o->limit(10, 20);
// When $o->get() is called, the number of records returned will be limited to 10, starting from record 20

```

# Method Chaining

Method chaining allows you to simplify your syntax by connecting multiple functions. Consider this example:

```php

$o = new Object();
$o->where('id', $id)->limit(10, 20)->get();

```

The alternate of the above without method chaining would be:

```php

$o = new Object();
$o->where('id', $id);
$o->limit(10, 20);
$o->get();

```

# Active Record Caching

Since DataMapper uses Active Record for all its queries, it makes sense you should be able to access the Active Record caching methods. While not "true" caching, Active Record enables you to save (or "cache") certain parts of your queries for reuse later. Normally, when an Active Record call is completed, all stored information is reset for the next call. With caching, you can prevent this reset, and reuse information easily.

Cached calls are cumulative. If you make 2 cached select() calls, and then 2 uncached select() calls, this will result in 4 select() calls. There are three Caching functions available:

## $object->start_cache()

This function must be called to begin caching. All Active Record queries of the correct type (see below for supported queries) are stored for later use.

## $object->stop_cache()

This function can be called to stop caching.

## $object->flush_cache()

This function deletes all items from the Active Record cache.

Here's a usage example:

```php

$o = new Object();
$o->start_cache();
$o->select('field1');
$o->stop_cache();
$o->get();
// The $o object is populated with all records from its corresponding table, but with only the 'field1' field being populated

$o->select('field2');
$o->get();
// The $o object is populated with all records from its corresponding table, but with both the 'field1' and 'field2' fields being populated

$o->flush_cache();

$o->select('field2');
$o->get();
// The $o object is populated with all records from its corresponding table, but with only the 'field2' field being populated

```

***Note:*** The following fields can be cached: ‘select’, ‘from’, ‘join’, ‘where’, ‘like’, ‘group_by’, ‘having’, ‘order_by’, ‘set’