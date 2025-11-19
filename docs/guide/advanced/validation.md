# Validation

[[Form Validation](http://codeigniter.com/user_guide/libraries/form_validation) library. In fact, the validation is quite similar so you'll have no problems picking it up if you're already familiar with it. However, there are enough differences that you should read on to take full advantage of it!

**Note:** validate() is automatically run whenever you perform a save().

- [Setting Validation Rules](#Rules)
- [Setting Related Validation Rules](#Related.Rules)
- [Cascading Rules](#Multiple.Rules)
- [Custom Validation](#Custom.Rules)
- [Custom Related Validation](#Custom.Related.Rules)
- [Predefined Validation Functions](#Built-In)
- [Predefined Related Validation Functions](#Built-In.Related)
- [Error Messages](#Error.Messages)
- [Setting Custom Error Messages](#Custom.Error.Messages)
- [Changing the Error Delimiters](#Error.Delimiters)

## Setting Validation Rules

DataMapper lets you set as many validation rules as you need for a given field, cascading them in order, and it even lets you prep and pre-process the field data at the same time. Let's see it in action, we'll explain it afterwards.

[**Basic Template** from the [DataMapper Models](/guide/models/) page, create a **User** model and add this code just above the class constructor:

```php

var $validation = array(
    'username' => array(
        'label' => 'Username',
        'rules' => array('required')
    ),
    'password' => array(
        'label' => 'Password',
        'rules' => array('required')
    ),
    'email' => array(
        'label' => 'Email Address',
        'rules' => array('required')
    )
);

```

Your model should now look like this:

```php
<?php

class User extends DataMapper {

    var $validation = array(
        'username' => array(
            'label' => 'Username',
            'rules' => array('required')
        ),
        'password' => array(
            'label' => 'Password',
            'rules' => array('required')
        ),
        'email' => array(
            'label' => 'Email Address',
            'rules' => array('required')
        )
    );
}

/* End of file user.php */
/* Location: ./application/models/user.php */

```

In the above, we have specified that the username, password, and email fields are all required. When a developer attempts to save their user object to the database, these validation rules must be met in order for the save to be successful.

- **array key** - The field name in lowercase.
- **label** - The label you will give this field for use in error messages.
- **rules** - The validation rules the field value must pass in order to pass validation.

Also, you can add validation rules for non-Database Table fields, such as 'Confirm Email Address' or 'Confirm Password'. For example:

```php

var $validation = array(
    'username' => array(
        'label' => 'Username',
        'rules' => array('required')
    ),
    'password' => array(
        'label' => 'Password',
        'rules' => array('required', 'encrypt')
    ),
    'confirm_password' => array( // accessed via $this->confirm_password
        'label' => 'Confirm Password',
        'rules' => array('encrypt', 'matches' => 'password')
    ),
    'email' => array(
        'label' => 'Email Address',
        'rules' => array('required', 'valid_email')
    ),
    array( // accessed via $this->confirm_email
        'field' => 'confirm_email',
        'label' => 'Confirm Email Address',
        'rules' => array('matches' => 'email')
    )
);

```

You can also define the fieldname by specifying a 'field' element in the array, as 'confirm_email' shows.

## Setting Related Validation Rules

[[**save()**](/guide/models/save), you can save both an object and its relationships at the same time. This is useful if you, for example, have a requirement that a User must relate to a Group. To validate this requirement, you would add rules for the Group relationship to the User *$validation* array in this way:

```php

var $validation = array(
    'username' => array(
        'label' => 'Username',
        'rules' => array('required')
    ),
    'password' => array(
        'label' => 'Password',
        'rules' => array('required')
    ),
    'email' => array(
        'label' => 'Email Address',
        'rules' => array('required')
    ),
    'group' => array(
        'label' => 'Group',
        'rules' => array('required')
    )
);

```

Now, whenever you attempt to save a new User, you will only be able to successfully save it if you are also saving it with a Group relationship. If you are saving on an existing User, it will save if they are already related to a Group (otherwise you need to save with a Group relationship).

## Cascading Rules

DataMapper lets you set multiple rules on each field. Let's try it. Change your *$validation* array like this:

```php

var $validation = array(
    'username' => array(
        'label' => 'Username',
        'rules' => array('required', 'trim', 'unique', 'min_length' => 3, 'max_length' => 20)
    ),
    'password' => array(
        'label' => 'Password',
        'rules' => array('required', 'trim', 'min_length' => 3)
    ),
    'email' => array(
        'label' => 'Email Address',
        'rules' => array('required', 'trim', 'unique', 'valid_email')
    ),
    'group' => array(
        'label' => 'Group',
        'rules' => array('required')
    )
);

```

Now we have a mix of **pre-processing** and **prepping** validation functions.

***Important:*** When cascading rules, note that rules are **not** run on **empty** fields *unless* the required or always_validate rules are set.

This includes anything that evaluates to TRUE for the **empty**() function, including: '', FALSE, or 0.

### Pre-Processing

A pre-processing validation function is one that returns TRUE or FALSE depending on the field's value. For example, the required function checks if the field value is empty. If it is, it will return FALSE meaning the field value has not met the validation rule.

### Prepping

A prepping validation function is one that directly modifies the value of the field. For example, **trim** will remove any leading or trailing whitespace from the field value.

## Custom Validation

You can create custom validation functions specific to the DataMapper model you put it in. For example, here is an encrypt function which we'll put in our User model to encrypt the password.

### Encrypt (prepping example)

```php

// Validation prepping function to encrypt passwords
function _encrypt($field) // optional second parameter is not used
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

```

### Where to Store Custom Validation Rules

[[extension class](extensions). The naming and usage rules are different depending on where you store them. You should always put rules that are used in multiple places in an extension class.

### Rules

There are important rules you need to be aware of when setting up your custom validation functions.

For in-class rules:

- The function must be private and named in the format: _{rule}($field, $param = '')
- The function must never be called directly.
- The first parameter contains the field name to be validated.
- The optional second parameter contains a setting that can be used by the function. Whether you use this depends upon your function. For example, the **max_length** function uses the second parameter as a number signifying the maximum length to validate the field against.

The word 'private' is used in here in the CodeIgniter context, where you make a method private by prefixing it with an underscore, so it is not routeable. In a PHP context, the method must NOT be declared private, but must be declared either public or protected so it can be called from the controller.

For extension-based rules:

- The function must be named in the format: rule_{rule}($object, $field, $param = '')
- The first parameter contains the object being validated.
- The second parameter contains the field name to be validated.
- The optional third parameter contains a setting that can be used by the function. Whether you use this depends upon your function. For example, the **max_length** function uses the second parameter as a number signifying the maximum length to validate the field against.

[[Exact Length](#Extension.Rule)

DataMapper's validate function ensures the validation rules are only applied to a field if it has changed since the last time validate ran. This prevents a field from having prepping functions applied to it multiple times, such as encryption, and the main reason why you should not call the actual validation functions directly. Calling an object's validate() function is all that's needed to have the validation rules applied. Note that validate is automatically run whenever you perform a save() call without parameters. You can also run or validate()->get() on an object to get a matching record using the objects current field values.

Anyway, back to putting in our custom encrypt function.

Add the encrypt function to your user model and the **encrypt** rule to the *$validation* array for the **password** field. Your model should now look like this:

```php

<?php

class User extends DataMapper {

    var $validation = array(
        array(
            'field' => 'username',
            'label' => 'Username',
            'rules' => array('required', 'trim', 'unique', 'min_length' => 3, 'max_length' => 20)
        ),
        array(
            'field' => 'password',
            'label' => 'Password',
            'rules' => array('required', 'trim', 'min_length' => 3, 'encrypt')
        ),
        array(
            'field' => 'email',
            'label' => 'Email Address',
            'rules' => array('required', 'trim', 'unique', 'valid_email')
        )
    );

    function __construct($id = NULL)
    {
        parent::__construct($id);
    }

    // Validation prepping function to encrypt passwords
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

Now if you were to do the following:

```php

$u = new User();
$u->username = "foo";
$u->password = "bar";
$u->email = "foo@example.org";
$u->save();

```

You would have a new user named foo saved to the database, with an encrypted password!

### Exact Length (pre-processing example)

Here is an example of a custom pre-processing function using a parameter:

```php

// Validation prepping function to encrypt passwords
function _exact_length($field, $param)
{
    // Check if field value is the required length
    if (strlen($this->{$field}) == $param)
    {
        return TRUE;
    }

    // Field value is not the required length
    return FALSE;
}

```

And we would add it to the validation array like this:

```php

$validation = array(
    'word' => array(
        'label' => 'Your Word',
        'rules' => array('required', 'trim', 'exact_length' => 10)
    )
);

```

Now if **word** is not exactly 10 characters in length, it will fail validation.

Here's the same rule, but stored in an [Extension Class](extensions):

```php
class Custom_Rules {
    function __construct()
    {
        $CI =& get_instance();
        // load in the custom rules language file.
        $CI->lang->load('custom_rules');
    }

    // Validation prepping function to encrypt passwords
    function rule_exact_length($object, $field, $param)
    {
        // Check if field value is the required length
        if (strlen($object->{$field}) == $param)
        {
            return TRUE;
        }

        // Field value is not the required length
        return FALSE;
    }
}

```

**Note:** The **exact_length** validation function is already included in DataMapper.

## Custom Related Validation

You can create custom related validation functions specific to the DataMapper model you put it in. For example, here is a max_size function which we'll put in our Group model to restrict the size of each Group.

### Max Size (pre-processing example)

```php
// Checks if the value of a property is at most the maximum size.
function _related_max_size($object, $model, $param = 0)
{
    return ($this->_count_related($model, $object) > $size) ? FALSE : TRUE;
}
```

**Note:** The **max_size** related validation function is already included in DataMapper.

### Rules

There are important rules you need to be aware of when setting up your custom validation functions.

- The function must be private and named in the format: _related_{rule}($related_objects, $related_field, $param = '')
- The function should never be called directly.
- The first parameter contains the related objects.
- The second parameter contains the related field name for the related object. (ie: 'user', 'creator', or 'editor')
- The optional third parameter contains a setting that can be used by the function. Whether you use this depends upon your function. For example, the **max_size** function uses the third parameter as a number signifying the maximum size to validate against.

Finally, you can also store related validation functions in an extension class, with the these rules:

- The function must be public and named in the format: rule_related_{rule}($object, $related_objects, $related_field, $param = '')
- The first parameter contains the object being validated.
- The second parameter contains the related object.
- The third parameter contains the related field name for the related object. (ie: 'user', 'creator', or 'editor')
- The optional fourth parameter contains a setting that can be used by the function. Whether you use this depends upon your function. For example, the **max_size** function uses the third parameter as a number signifying the maximum size to validate against.

## Predefined Validation Functions

[ library, as well as any native [PHP](http://php.net/) function that accepts one parameter.

As well as those, DataMapper provides a few extra validation functions.

Any custom validation functions you would like to add, can be added to your DataMapper models, such as the example of the **encrypt** function.

## Predefined Related Validation Functions

DataMapper has some specific validation rules used to validate relationships. These are:

Any custom related validation functions you would like to add, can be added to your DataMapper models, such as the example of the **max_size** function above.

## Error Messages

If any of the field values fail validation, the object will have its error property populated. You can view loop through and show each error in the error's all list, show the specific error for each field, or show all errors in one string. For example:

### Viewing All Errors

```php

foreach ($object->error->all as $e)
{
    echo $e . "<br />";
}

```

### Viewing Specific Field Errors

```php

echo $object->error->fieldname;
echo $object->error->otherfieldname;

```

### Viewing All Errors as a Single String

```php

echo $object->error->string;

```

The save function will return FALSE if validation fails, so if that happens you can check the error object for the errors.

Calling the validate() function will see a **valid** flag set to true or false. For example:

```php

$this->validate();

if ($this->valid)
{
    // Validation Passed
}
else
{
    // Validation Failed
}

```

## Setting Custom Error Messages

With the option of creating custom validation functions or having custom methods specific to each DataMapper model, you'll at one time or another want to raise an error message. There are three ways to handle custom error message.

### Using the error_message function

The most generic, which works from anywhere, is to use the error_message() method. This method accepts accepts two parameters.

**$field** - This is the name by which you'll access the error in the error object.

**$error** - This is the error message itself.

If you are using this from within a validation rule, don't return FALSE, as setting the error message is enough. Here is an example of setting a custom error message and accessing it.

```php

$u = new User();

$u->error_message('custom', 'This is a custom error message.');

echo $u->error->custom;

```

### Using Language Files

From within custom validation rules, you can return a FALSE value if an error occurs. If Datamapper ORM receives a FALSE value, it will attempt to look up the error based on the validation rule's name (ie: the min_size rule, stored under _min_size, needs a language string called min_size. This string will be passed into **sprintf**, with two string arguments, the field label and (if available) the rule's parameters.

For example, the min_size message looks like this:

```php

$lang['min_size'] = 'The %s field must be at least %s.';

```

Which, with a parameter of 1 on the field user might render like this:

```php
The User must be at least 1.
```

### Returning Highly-Customized Messages

If you need to manipulate the error message more than the label and parameter, you can build the error message from within the custom validation rule, and return it instead of FALSE. It will still be passed to **sprintf**.

```php

function _special_rule($field, $params)
{
    $valid = ... // validate the field
    if( ! $valid)
    {
        $result = 'For your account, you can have no more than ' . $useraccount->max_widgets . ' widgets at a time.';
        return $result;
    }
}

```

## Changing the Error Delimiters

By default, DataMapper adds a paragraph tag (`<p>`) around each individual error message. You can easily change these delimiters by setting the *$error_prefix* and *$error_suffix* class variables in your DataMapper model. For example, we'll set them in our User model:

```php
<?php

class User extends DataMapper {

    var $error_prefix = '<div class="error">';
    var $error_suffix = '</div>';

    var $validation = array(

    [...]

```