# Getting Started

[install](installation) Datamapper ORM, then read all the topics in the **General Topics** section of the Table of Contents. You should read them in order as each topic builds on the previous one, and may include code examples that you are encouraged to try.

Once you understand the basics you'll be ready to explore the magic that is **DataMapper ORM**. Below is a glimpse of what's to come!

## Models

Here's a simple example of a few DataMapper models setup with relationships between each other. DataMapper models do the work of transforming your Database tables into easy to use objects. Further down in the Controllers section, you'll see just how easy it is to use them.

### User

```php
<?php

class User extends DataMapper {

    var $has_many = array('book');
    var $has_one = array('country');

    var $validation = array(
        'username' => array(
            'label' => 'Username',
            'rules' => array('required', 'trim', 'unique', 'alpha_dash', 'min_length' => 3, 'max_length' => 20),
        ),
        'password' => array(
            'label' => 'Password',
            'rules' => array('required', 'min_length' => 6, 'encrypt'),
        ),
        'confirm_password' => array(
            'label' => 'Confirm Password',
            'rules' => array('required', 'encrypt', 'matches' => 'password'),
        ),
        'email' => array(
            'label' => 'Email Address',
            'rules' => array('required', 'trim', 'valid_email')
        )
    );

    function login()
    {
        // Create a temporary user object
        $u = new User();

        // Get this users stored record via their username
        $u->where('username', $this->username)->get();

        // Give this user their stored salt
        $this->salt = $u->salt;

        // Validate and get this user by their property values,
        // this will see the 'encrypt' validation run, encrypting the password with the salt
        $this->validate()->get();

        // If the username and encrypted password matched a record in the database,
        // this user object would be fully populated, complete with their ID.

        // If there was no matching record, this user would be completely cleared so their id would be empty.
        if (empty($this->id))
        {
            // Login failed, so set a custom error message
            $this->error_message('login', 'Username or password invalid');

            return FALSE;
        }
        else
        {
            // Login succeeded
            return TRUE;
        }
    }

    // Validation prepping function to encrypt passwords
    // If you look at the $validation array, you will see the password field will use this function
    function _encrypt($field)
    {
        // Don't encrypt an empty string
        if (!empty($this->{$field}))
        {
            // Generate a random salt if empty
            if (empty($this->salt))
            {
                $this->salt = md5(uniqid(rand(), true));
            }

            $this->{$field} = sha1($this->salt . $this->{$field});
        }
    }
}

/* End of file user.php */
/* Location: ./application/models/user.php */

```

### Country

```php
<?php

class Country extends DataMapper {

    var $table = 'countries';

    var $has_many = array('user');

    var $validation = array(
        'name' => array(
            'label' => 'Country',
            'rules' => array('required', 'trim', 'unique', 'alpha_dash', 'min_length' => 1, 'max_length' => 50),
        );
}

/* End of file country.php */
/* Location: ./application/models/country.php */

```

### Book

```php
<?php

class Book extends DataMapper {

    var $has_many = array('user');

    var $validation = array(
        'title' => array(
            'label' => 'Title',
            'rules' => array('required', 'trim', 'unique', 'alpha_dash', 'min_length' => 1, 'max_length' => 50),
        ),
        'description' => array(
            'label' => 'Description',
            'rules' => array('required', 'trim', 'alpha_slash_dot', 'min_length' => 10, 'max_length' => 200),
        ),
        'year' => array(
            'label' => 'Year',
            'rules' => array('required', 'trim', 'numeric', 'exact_length' => 4),
        )
    );
}

/* End of file book.php */
/* Location: ./application/models/book.php */

```

## Controllers

Here's a quick example of a Controller handling the creation of a user, setting up and accessing some related objects, and logging a user in. To keep it simple, we'll echo the results from the Controller rather than setting up a View.

### Users

```php
<?php

class Users extends Controller {

    function Users()
    {
        parent::Controller();
    }

    function index()
    {
        // Let's create a user
        $u = new User();
        $u->username = 'Fred Smith';
        $u->password = 'apples';
        $u->email = 'fred@smith.com';

        // And save them to the database (validation rules will run)
        if ($u->save())
        {
            // User object now has an ID
            echo 'ID: ' . $u->id . '<br />';
            echo 'Username: ' . $u->username . '<br />';
            echo 'Email: ' . $u->email . '<br />';

            // Not that we'd normally show the password, but when we do, you'll see it has been automatically encrypted
            // since the User model is setup with an encrypt rule in the $validation array for the password field
            echo 'Password: ' . $u->password . '<br />';
        }
        else
        {
            // If validation fails, we can show the error for each property
            echo $u->error->username;
            echo $u->error->password;
            echo $u->error->email;

            // or we can loop through the error's all list
            foreach ($u->error->all as $error)
            {
                echo $error;
            }

            // or we can just show all errors in one string!
            echo $u->error->string;

            // Each individual error is automatically wrapped with an error_prefix and error_suffix, which you can change (default: <p>error message</p>)
        }

        // Shortcut: opt into expected fields and fill straight from input
        $user = new User();
        $user->fillable = array('username', 'email', 'password');

        if ($user->fill($this->input->post())->save())
        {
            echo 'Created with fill(): ' . $user->username;
        }

        // Let's now get the first 5 books from our database
        $b = new Book();
        $b->limit(5)->get();

        // Let's look at the first book
        echo 'ID: ' . $b->id . '<br />';
        echo 'Name: ' . $b->title . '<br />';
        echo 'Description: ' . $b->description . '<br />';
        echo 'Year: ' . $b->year . '<br />';

        // Now let's look through all of them
        foreach ($b as $book)
        {
            echo 'ID: ' . $book->id . '<br />';
            echo 'Name: ' . $book->title . '<br />';
            echo 'Description: ' . $book->description . '<br />';
            echo 'Year: ' . $book->year . '<br />';
            echo '<br />';
        }

        // Let's relate the user to these books
        $u->save($b->all);

        // Yes, it's as simple as that!  You can add relations in several ways, even different types of relations at the same time

        // Get the Country with an ID of 10
        $c = new Country();
        $c->where('id', 10)->get();

        // Get all Books from the year 2000
        $b = new Book();
        $b->where('year', 2000)->get();

        // Relate the user to them
        $u->save(array($c, $b->all));

        // Now let's access those relations from the user

        // First we'll get all related books
        $u->book->get();

        // You can just show the first related book
        echo 'ID: ' . $u->book->id . '<br />';
        echo 'Name: ' . $u->book->title . '<br />';
        echo 'Description: ' . $u->book->description . '<br />';
        echo 'Year: ' . $u->book->year . '<br />';

        // Or if you're expecting more than one, which we are, loop through all the books!
        foreach ($u->book as $book)
        {
            echo 'ID: ' . $book->id . '<br />';
            echo 'Name: ' . $book->title . '<br />';
            echo 'Description: ' . $book->description . '<br />';
            echo 'Year: ' . $book->year . '<br />';
            echo '<br />';

            // And there's no need to stop there,
            // we can see what other users are related to each book! (and you can chain the get() of related users if you don't want to do it on its own, before the loop)
            foreach ($book->user->get() as $user)
            {
                // Show user if it's not the original user as we want to show him the other users
                if ($user->id != $u->id)
                {
                    echo 'User ' . $user->username . ' also likes this book<br >';
                }
            }
        }

        // We know there was only one country so we'll access the first record rather than loop through $u->country->all

        // Get related country
        $u->country->get();

        echo 'User is from Country: ' . $u->country->name . '<br />';

        // One of the great things about related records is that they're only loaded when you access them!

        // Lets say the user no longer likes the first book from his year 2000 list, removing that relation is as easy as adding one!

        // This will remove the users relation to the first record in the $b object (supplying $b->all would remove relations to all books in the books current all list)
        $u->delete($b);

        // You can delete multiple relations of different types in the same way you can save them

        // Now that we're done with the user, let's delete him
        $u->delete();

        // When you delete the user, you delete all his relations with other objects.  DataMapper does all the tidying up for you :)
    }

    function register()
    {
        // Create user object
        $u = new User();

        // Put user supplied data into user object
        // (no need to validate the post variables in the controller,
        // if you've set your DataMapper models up with validation rules)
        $u->username = $this->input->post('username');
        $u->password = $this->input->post('password');
        $u->confirm_password = $this->input->post('confirm_password');
        $u->email = $this->input->post('email');

        // Attempt to save the user into the database
        if ($u->save())
        {
            echo '<p>You have successfully registered</p>';
        }
        else
        {
            // Show all error messages
            echo '<p>' . $u->error->string . '</p>';
        }
    }

    function login()
    {
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
            echo '<p>Welcome ' . $u->username . '!</p>';
            echo '<p>You have successfully logged in so now we know that your email is ' . $u->email . '.</p>';
        }
        else
        {
            // Show the custom login error message
            echo '<p>' . $u->error->login . '</p>';
        }
    }
}

/* End of file users.php */
/* Location: ./application/controllers/users.php */

```

## Cool huh?

I hope that's enough to wet your appetite! It's hard to show the full benefits of DataMapper in one simple page but I'm sure you've glimpsed the power DataMapper can give you and in such a simple and logical way!

Please continue on with the General Topics to learn more.