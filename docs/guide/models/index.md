# DataMapper Models

In order for DataMapper to map your Database tables into objects, you first need to create a DataMapper model for each table. These models will extend DataMapper in order to gain the wonderful functionality of tables as objects.

[**very** different than [CodeIgniter Models](http://codeigniter.com/user_guide/general/models). Unlike CI models, there is no need to load them explicitly, Datamapper ORM handles that automatically. And they should never be added to **autoload**.

## Basic Template

#### Template Available

Datamapper ORM comes packaged with a ready-to-use base template:

Below is a basic template you can use to create DataMapper models.

- Name - Replace this value with the name of your object. For example: User
- DataMapper - Extending DataMapper is what makes your model a DataMapper model.
- __construct - (Optional) It is highly recommended that you use this standard PHP constructor, instead of the class name, for easier management later. If you want the ability to load a model by ID when it is created, make sure you include the $id parameter.

```php

class Name extends DataMapper {

    // Optionally, don't include a constructor if you don't need one.
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }

    // Optionally, you can add post model initialisation code
    function post_model_init($from_cache = FALSE)
    {
    }
}

/* End of file name.php */
/* Location: ./application/models/name.php */

```

::: info

If you define a constructor, but do not pass in the $id value, you will not be able to use the shorthand:

```php
$user = new User($user_id);
```

Instead, you will still need to use the original method:

```php

$user = new User();
$user->get_by_id($user_id);
```
- [ - (Optional) After Datamapper has loaded and initialized the model, it calls the post_model_init() method (if defined), where you can add initialisation code specific for this model. The $from_cache parameter indicates if the current model configuration was generated, or was loaded from the [production cache](/guide/advanced/production-cache).

## Rules

DataMapper models must be named the singular version of the object name, with an uppercase first letter. So for a user object, the DataMapper model would be named **User**. The model should have a corresponding table in the database named as the lowercase, pluralised version of the object name. So for a DataMapper model named **User**, the table would be named **users**. For a DataMapper model named **Country**, the table would be named **countries**.

In most cases, the difference between the singular and plural version of an object name is just a matter of adding the letter **s** on the end. For example:

However, some object names have completely different wording between the singular and plural. For example:

In this case, you will need to specify the table name in your DataMapper model. You do this by adding a class variable of *$table*, which should be the name of your table. For example:

```php

class Country extends DataMapper {

    var $table = 'countries';

    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}

/* End of file country.php */
/* Location: ./application/models/country.php */

```

If you don't supply the *$table* variable, DataMapper will automatically assume the table name is the same as your model name, in lowercase, with the letter **s** on the end (which will be the case most of the time).

However, with that said, I have included a customised version of CodeIgniter's **Inflector Helper** with DataMapper that should be able to correctly convert most irregular singular/plural words, if loaded.

[Troubleshooting](/help/troubleshooting)) and I'll try to update the inflector helper.

There is one other scenario to look at where the singular and plural name of an object can get a little confusing. What do you do if the singular name of an object is the same as the plural name? For example, the word **fruit** is used for both a single piece of fruit and multiple pieces of fruit. In this case, you will have to use the singular model name of **Fruit** and the plural table name of **fruits**. Alternatively, you can specify a different table name to the automatically determined name, in the same way as done above.