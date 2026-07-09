# Utility Methods

#### Subsections

- [Exists](#exists) - Does an object exist?
- [Clear](#clear) - Reset an object.
- [Reinitialize Model](#reinitialize_model) - Reload the configuration information for a model.
- [Query](#query) - Run a RAW SQL query.
- [Add Table Name](#add_table_name) - Add the table name to a field.
- [Check Last Query](#check_last_query) - Output the last query.

## Exists

Exists is a simple function that returns TRUE or FALSE depending on whether the object has a corresponding database record. For example:

This method works by looking at one of two variables:

- If the *$id* field is set, then this returns TRUE if the field is not empty().
- Otherwise, this field returns TRUE if the *$all* array contains at least one item.

This means that an existing record with an *$id* of 0**does not "exist"**. This is to be consistent with the idea that an empty *$id* implies a new record.

```php

$id = 42;

// Get user
$u = new User();
$u->get_by_id($id);

// Check if we actually got a user back from the database
if ($u->exists())
{
    // Yes, we did!
}
else
{
    // No, we didn't!
}

```

## Clear

Clear is used to clear the object of data.

```php

$id = 42;

// Get user
$u = new User();
$u->get_by_id($id);

// Show username
echo $u->username;

// Let's say it outputs "foo bar"

// Clear object
$u->clear();

// Try to show username again
echo $u->username;

// outputs nothing since the object has been cleared

```

## Reinitialize Model

This method is used to re-configure a model.

The initial configuration happens automatically the first time a model is used. Sometimes, however, it is necessary to re-initialize a model.

A specific example would be after a user's preferences have been loaded, and the localized language of the application has been changed. In this instance, we need to call reinitialize_model() on the user object to ensure that the correct language is loaded.

Note: this will only affect the object it is called on, and future objects created that are of the same model. Therefore, language changes should be handled as early as possible in the application, before ***any other models are accessed***

### Example

```php

// Custom Session class (application/libraries/MY_Session.php)
class MY_Session extends CI_Session {

    function MY_Session() {
        parent::CI_Session();
        $userid = $this->userdata['logged_in'];
        if(!empty($userid)) {
            $this->logged_in_user = new User($userid);
            $CI =& get_instance();
            if($this->logged_in_user->language != $CI->config->item('language')) {
                // override default language
                $CI->config->config['language'] = $this->logged_in_user->language;
                // reload the user model
                $this->logged_in_user->reinitialize_model();
            }
        }
    }

```

## Query

[Query](http://codeigniter.com/user_guide/database/queries) method except that the object is populated with the returned results.

Use this method at your own risk as it will only be as reliable as your query. I highly recommend using the binding approach so your data is automatically escaped.

The Query method will populate the object with the results so it is very important to remember that you should be querying for data from the objects table. For example:

```php

// Create user object
$u = new User();

// SQL query on users table
$sql = "SELECT * FROM `users` WHERE `username` = 'Fred Smith' AND `status` = 'active'";

// Run query to populate user object with the results
$u->query($sql);

```

[Get](/guide/models/get) method would be more appropriate.

As I mentioned before, it is recommended you use bindings when using the Query method. For example, doing the same as above but with bindings:

```php

// Create user object
$u = new User();

// SQL query on users table
$sql = "SELECT * FROM `users` WHERE `username` = ? AND `status` = ?";

// Binding values
$binds = array('Fred Smith', 'active');

// Run query to populate user object with the results
$u->query($sql, $binds);

```

The *question marks* in the query are automatically replaced with the values in the array in the second parameter of the Query method.

## Add Table Name

This method will add the object's table name to the provided field.

[query](#query) method, as well as when you need to run more complicated queries using the normal methods from get and get advanced.

### Arguments

- **$field**: A field or array of field names to prepend the table name to.

```php

$u = new User();
$u->where( 'UPPER(' . $u->add_table_name('name') . ') <>', 'SECRET')->get();

// Produces
SELECT * FROM `users`
WHERE UPPER(`users`.`name`) <> 'SECRET'

```

The benefit of this method is you are no longer hard-coding the table name. It may or may not be worth it for your application.

## Get SQL

[Moved here](/guide/models/get-iterated#get_sql).

## Check Last Query

This method allows you to debug the last query that was processed. In its simplest form, it outputs the last query, formatted and placed inside `<pre>` tags.

You can also pass as the first argument in a two-item array with alternative delimiters, or FALSE for no delimiters. The second argument, when TRUE, prevents the method from automatically outputting the query to the browser.

### Example

```php

$u = new User();
$u->where('name', 'Joe')->get();
$u->check_last_query();

```

```php

SELECT `users`.*
FROM `users`
WHERE `users`.`name` = 'Joe'

```