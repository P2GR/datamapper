# Setting up Relationships

In order for your DataMapper models to know the relationships it has between other DataMapper models, you need to set its *$has_one* and *$has_many* variables. You do this by adding a class variable of *$has_one* and *$has_many*, both of which are arrays.

The values you add to these arrays is the related models name in lowercase. For example:

### User

```php

class User extends DataMapper {

    var $has_one = array("country");

    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}

/* End of file user.php */
/* Location: ./application/models/user.php */

```

### Country

```php

class Country extends DataMapper {

    var $table = "countries";

    var $has_many = array("user");

    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}

/* End of file country.php */
/* Location: ./application/models/country.php */

```

Looking above, we can see that a user can relate to only one country but a country can relate to many users. For example, I was born in the United States. It's not really possible for me to have been born in more than one country. That's where the *$has_one* setting in the User model comes into play. The U.S. however has lots of people (or users in this example) which is where the *$has_many* setting in the Country model comes into play.

## Multiple Relations

You can setup as many relationships as you need. You simply add more lowercase model names into the *$has_one* or *$has_many* variables as needed.

### User

```php

$has_one = array("country", "group");
$has_many = array("book", "setting");

```

## Populating Related Objects

[[Get](/guide/models/get) for more information). For example:

```php

// Create a Country object and get the record for Australia
$c = new Country();
$c->where('name', 'Australia')->get();

// Populate the related users object with all related records
// Note: get_iterated is used because we are only looping over the users list once.
$c->user->get_iterated();

// Loop through to see all related users
foreach ($c->user as $u)
{
    echo $u->username . '<br />';
}

```

An example of populating your related users with a more refined list could be paged results of users who are older than 18 years of age.

```php

// Create a Country object and get the record for Australia
$c = new Country();
$c->where('name', 'Australia')->get();

// How many related records we want to limit ourselves to
$limit = 20;

// The page we're looking at
$page = 2;

// Set the offset for our paging
$offset = $page * $limit;

// Populate the related users object
$c->user->where('age >', '18')->get_iterated($limit, $offset);

// Loop through to see all related users matching our related query above
foreach ($c->user as $u)
{
    echo $u->username . '<br />';
}

```

## Automatic Population of Related Objects

[*$auto_populate_has_many* and *$auto_populate_has_one* class variables in your DataMapper models to TRUE or by setting them to TRUE in the DataMapper [Configuration](/guide/getting-started/configuration). Obviously these will auto populate their respective relation type.

```php

var $auto_populate_has_many = TRUE;
var $auto_populate_has_one = TRUE;

```

With your model set to auto populate "has many" and/or "has one" related objects, you can go directly to looping through the related objects. For example:

```php

// Create a Country object and get the record for Australia
$c = new Country();
$c->where('name', 'Australia')->get();

// Loop through to see all related users
foreach ($c->user as $u)
{
    echo $u->username . '<br />';
}

```

The only downside of auto populating is that it will populate with all related records. So, looking at the above example, if we had a hundred thousand users related to Australia, all of those users would have to be read from the Database and loaded into memory, which is not good for performance, and why it is recommended you stick to manually populating with sensibly defined query clauses.