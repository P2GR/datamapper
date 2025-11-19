# Accessing Relationships

[*$has_one* and *$has_many* relationships before it is possible to access them. Read [Setting up Relationships](/guide/relationships/setting) to see how.

[ and [Delete](/guide/models/delete) topics to see how you save and delete relationships. I'll do a quick summary now to setup the example of accessing our relationships.

## Models

Let's use the following Models for our example:

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

Looking above, we can see that a user can relate to only one country but a country can relate to many users.

## In a Controller

First we'll create some users:

```php

// Create Users
$u = new User();
$u->username = 'Fred Smith';
$u->email = 'fred@smith.com';
$u->password = 'apples';
$u->save();

$u = new User();
$u->username = 'Jayne Doe';
$u->email = 'jayne@doe.com';
$u->password = 'poppies';
$u->save();

$u = new User();
$u->username = 'Joe Public';
$u->email = 'joe@public.com';
$u->password = 'rockets';
$u->save();

```

Now a few groups:

```php

// Create Groups
$g = new Group();
$g->name = 'Administrator';
$g->save();

$g = new Group();
$g->name = 'Moderator';
$g->save();

$g = new Group();
$g->name = 'Member';
$g->save();

```

With data to play around with, we'll get user Fred Smith and relate him to the Administrator group:

```php

// Get Fred Smith
$u = new User();
$u->where('username', 'Fred Smith')->get();

// Get Administrator Group
$g = new Group();
$g->where('name', 'Administrator')->get();

// Here's where we make Fred an Administrator, and it's quite easy!
$u->save($g);

// We've decided Fred should be a Moderator instead so we'll change the Group to Moderator
$g->where('name', 'Moderator')->get();

// And then we'll update Fred's relation so he's a Moderator
// Since the User model "has one" Country, it will overwrite the existing relation
$u->save($g);

```

It's easy to add multiple relations as well. We'll add users Jayne Doe and Joe Public to the Member group:

```php

// Get users Jayne Doe and Joe Public
$u = new User();
$u->where('username', 'Jayne Doe')->or_where('username', 'Joe Public')->get();

// Get Member Group
$g = new Group();
$g->where('name', 'Member')->get();

// Now we'll add both Jayne and Joe to the Member Group
$g->save($u->all);

```

## Finally the Accessing

Now that we understand what our relationships currently are, we can look at how to access them.

To access a relationship, you use the singular name of the related object, in lowercase, as though it is a property of the current object. To demonstrate, we'll look at which group Fred is related to. From the user objects point of view we're expecting only one result so we can just grab all related groups.

```php

// Get Fred
$u = new User();
$u->where('username', 'Fred Smith')->get();

// Get the related group
$u->group->get();

// Show which Group Fred is in
echo '<p>' . $u->group->name . '</p>';

```

[[Get](/guide/models/get) for more information) before accessing the values themselves. Now we'll look at which users are related to the Member Group. From the groups point of view, there may be one or more users. We know it has 2 users since we added them. The related objects are fully functional DataMapper objects. You can do all the usual get, save and delete actions on them. Since we expect multiple related objects, we'll use the related all list.

```php

// Get Member Group
$g = new Group();
$g->where('name', 'Member')->get();

// Get the related users
$g->user->get();

// Loop through the Member groups related users
foreach ($g->user as $u)
{
    echo '<p>' . $u->username . '</p>';

    // We don't have to stop here, we can do any DataMapper functions we want on these objects
    if ($u->username == "Joe Public")
    {
        $u->username = "Joe Private";
        $u->save();
    }
}

```

You can dig as deep as you want with the related items. For example:

```php

// Get Fred and add him to the Member Group (yep, downgrading him again!)
$u = new User();
$u->where('username', 'Fred Smith')->get();

$g = new Group();
$g->where('name', 'Member')->get();

$u->save($g);

// Get Jayne Doe
$u->where('username', 'Jayne Doe')->get();

// Rather than populating our related group, and its related users outside of the loop,
// we can instead use chaining and do it inside.  Since our current user has one group,
// we wont need to loop through group->get() as we do the following related users.

// Look at which group she is related to and then what other users are related to the group
foreach ($u->group->get()->user->get() as $user)
{

    // Don't show if it is Jayne
    if ($user->id != $u->id)
    {
        // This will show Fred Smith the first time through, and then Joe Private
        echo '<p>' . $u->username . '</p>';
    }
}

```

[[Usage guides](../datamapper-2/index) as they go into further depth on Accessing Relationships.