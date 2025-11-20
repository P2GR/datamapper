# Installation Instructions

## Short Version

Unzip and copy everything within application into your CodeIgniter installation's application folder, add the **bootstrap** to the index.php file, edit the config, and go map some data!

## Long Version

DataMapper is installed in seven steps, with two optional steps:

- Unzip the package.
- [application/config/datamapper.php file with a text editor and set your [preferred DataMapper settings](/guide/getting-started/configuration).
- Upload the application/config/datamapper.php file to your CodeIgniter application/config folder.
- Upload the application/libraries/datamapper.php file to your CodeIgniter application/libraries folder.
- Upload the application/third_party/datamapper folder to your CodeIgniter application/third_party folder.
- Upload the application/language folder to your CodeIgniter application/language folder.

```php
$autoload['libraries'] = ['database', 'datamapper'];
```

```php
$autoload['models'] = array();
```
- [application/config/autoload.php file with a text editor and add the database and datamapper libraries to the *autoload* libraries array.  Also, make sure you clear the models array, because DataMapper automatically loads these.  For further information on auto-loading, read [Auto-loading Resources](http://codeigniter.com/user_guide/general/autoloader).

```php
$db['default']['dbprefix'] = "";
```
- [application/config/database.php file with a text editor and set your database settings, ensuring you set the dbprefix to an empty string.  For information on using table prefixes with DataMapper, read [Setting up Table Prefixes](/guide/advanced/table-prefix).

```php
/* --------------------------------------------------------------------
 * LOAD THE DATAMAPPER BOOTSTRAP FILE
 * --------------------------------------------------------------------
 */
require_once APPPATH.'third_party/datamapper/bootstrap.php';
```
- Optionally, upload the application/helpers/inflector_helper.php file to your CodeIgniter application/helpers folder.

::: info

[**views**, **libraries**, **helpers**, or other items to function correctly. Please [check the extensions](/guide/extensions/) you plan on using.

That's it!

[[Getting Started](/guide/getting-started/introduction) section of the User Guide to begin learning how to use DataMapper. Enjoy!