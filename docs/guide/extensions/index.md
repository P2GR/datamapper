# Using Extensions

Not everyone needs every feature all the time. Datamapper ORM has been designed to allow simple extensions that enable you to enhance DataMapper models. There are two primary ways to extend DataMapper, which can be used at the same time.

- [Using Shareable Extension Classes](#Extensions)
- [Extending DataMapper Directly](#DataMapperExt)

The techniques differ greatly, and will be described in brief below.

## Using Shareable Extension Classes

This is the recommended way of extending a DataMapper model. This technique allows you to add methods and custom validation rules to DataMapper models, without having to change any existing code.

It works by calling non-private methods on separate classes as needed. These classes are usually stored within the **application**/datamapper directory. You can change this directory by changing the DataMapper config item *'extensions_path'*.

An extension is automatically loaded either globally, through the DataMapper config, or on a per-class basis, through the *$extensions* array. The order you load the methods matters, as the first extensions loaded take precedence over later ones. (Per-class or local extensions will also override global extensions.) You can also load an extension on-the-fly using load_extension.

### Adding a Global Extension

```php

// In DataMapper Config
$config['extensions'] = array('json'); // Include the json extension

```

### Adding an Extension to the User Class Only

```php

class User extends DataMapper {

    // Include the json extension
    var $extensions = array('json');

    // ...

```

### Loading Global Extensions Dynamically with load_extension

```php

$user = new User();
// load csv, which is now available on all DataMapper objects.
$user->load_extension('csv');

```

You can also include other files that are stored relative to the **application** directory by including the path. For example, to include a library, you would use '**library/mylibary**'

Note that all three can coexist. You can load some extensions in globally and others locally, at the same time, and still others on-the-fly.

Some extensions include the ability to pass in options.

#### Adding a Global Extension with Options

```php

// In DataMapper Config
$config['extensions'] = array('htmlform' => array(
    'form_template' => 'my_form_template'
));

```

#### Dynamically Loading a Single (Global) Extension with Options

```php

$user = new User();
// load htmlform, which is now available on all DataMapper objects.
$user->load_extension('htmlform', array('row_template' => 'my_row_template'));

```

#### Dynamically Loading a Single (Local) Extension with Options

You can also dynamically load an extension for a single class. This allows you to provide different options for each model.

```php

$user = new User();
// load htmlform, which is now available on all DataMapper objects.
$user->load_extension('htmlform', array('row_template' => 'my_row_template'), TRUE);

```

### Using the Extension

The extensions work by adding methods directly to the DataMapper models. In the above example, the json extension adds several methods, including:

- to_json()
- from_json()

These methods would be called as a normal method:

```php

$u = new User();
$u->get_by_id($user_id);
echo $u->to_json();

```

[, or you can view the [list of included extensions](/guide/extensions/).

## Extending DataMapper Directly

Some features are not able to be added using the extensions mechanism. This includes those that need to override built-in DataMapper methods.

To handle these, it is recommended that you create a class that extends DataMapper, and use that as your base class for your models. You can call it whatever you like, but for the examples below, I named it DataMapperExt:

### application/models/datamapperext.php

```php

class DataMapperExt extends DataMapper {
    function __construct($id = NULL) {
        parent::__construct($id);
    }

    // Add your method(s) here, such as:

    // only get if $this wasn't already loaded
    function get_once()
    {
        if( ! $this->exists())
        {
            $this->get();
        }
        return $this;
    }

}

```

### application/models/user.php

```php

class User extends DataMapperExt {
    // Standard DataMapper definition
    function __construct($id = NULL) {
        parent::__construct($id);
    }
    // ...
}

```

Now you can add any methods or properties you want to DataMapperExt, and they will be visible to any model that subclasses DataMapperExt. You can even overwrite default DataMapper methods.

The drawbacks to this method is that it is very difficult to share this kind of extension, and it isn't very modular. In any case, I highly recommend it whenever you think you need to edit DataMapper directly.