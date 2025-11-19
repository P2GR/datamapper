# SQL Functions

If you want to include SQL functions — including user-defined SQL functions — it is easier that ever with Datamapper ORM. There are several ways to access custom SQL functions.

## $object->func($function_name, $arg1, $arg2, ...)

The first is by directly creating one using the func method. This method builds a SQL function, and processes a variety of arguments.

- **Operators**: Mathematical and String operators, such as +, &, or || are inserted directly.
- **Pre-Escaped Strings**: If a string starts and ends with a single quote mark ('), or is the special string '*', it is added directly.
- **Raw Strings**: If a string starts and ends with square brackets ([ ]), the string (without brackets) is inserted directly without escaping.
- **Non-Strings**: Non strings are included in the SQL directly, such as numbers and boolean values.
- **Column Names**: Column names, or fields on a model, are strings that start with an at-symbol (@). These are replaced with properly protected names.
- **Related Column Names**: Related column names start with an @, but contain forward slashes to reference one or more relationships.
- **Formulas**: Passing in a set of arguments in an array is concatenated as a formula. In a formula, common operators are not escaped. Formulas can also recusively reference functions, as seen below.
- **Simple Strings**: Normal strings are escaped to be used in the function as SQL strings.

Please note that if user-provided content starts and stops with single-quote marks, or starts with an @ sign, the input **may be inserted into the query without escaping**

If you are planning on working with user-provided input, it may be wise to pre-escape this content with $object->db->escape_str().

### Random Examples

```php

$u = new User();

// UPPER('hello')
$u->func('UPPER', 'hello');

// round(365 * `users`.`age`)
$u->func('round', array(365, '*', '@age'));

// round(sqrt(`users`.`id`))
$u->func('round', array('sqrt' => '@id'));

// COALESCE(`users`.`name`, '')
$u->func('COALESCE', '@name', '');

//Adds `group` table, and returns UPPER(`groups`.`name`)
$u->func('UPPER', '@group/name');

// Trick to get a formula with no function
// (365 * `users`.`age`)
$u->func('', array(365, '*', '@age'));

```

Where the method is really powerful is that you can combine column names from either the direct table *or* from related models with functions and properties.

## $object->select_func($function_name, [$arg1, [...]], $alias)

In this format, the result of the function is added to the select statement. The last argument is always used as the alias, and is required.

CodeIgniter has an overly aggressive method for protecting identifiers, and it **cannot** be disabled. This may break any attempt to include functions in the SELECT statement.

However, with a simple adjustment to the _protect_identifiers method of the DB_driver class, you can get it working again.

[See the bottom of this page for the code modification.](#Protect.Identifiers.Fix)

### Examples

```php

$u = new User();

// SELECT `users`.*, UPPER(`users`.`name`) as uppercase_name
// FROM `users`
$u->select_func('UPPER', '@name', 'uppercase_name')->get();

// SELECT `users`.*, (`groups`.`name` = 'Administrators') as is_admin
// FROM `users`
// LEFT OUTER JOIN `groups` as groups ON `groups`.`id` = `users`.`group_id`
$u->select_func('', array('@group/name', '=', 'Administrators'), 'is_admin')->get();

```

## $object->{query}_func($function_name, [$arg1, [$arg2, [...]], $value)

[**required**, and is passed to the [supported query clause](/guide/models/get-advanced#Supported.Query.Clauses).

### Example

```php

$u = new User();

// SELECT `users`.*
// FROM `users`
// ORDER BY LOWER(`users`.`lastname` & ', ' & `users`.`firstname`) ASC
$u->order_by_func('LOWER', array('@lastname', '&', ', ', '&', '@firstname'), 'ASC');
$u->get();

```

## $object->{query}_field_func($field, $function_name, [$arg1, [$arg2, [...]])

[[supported query clause](/guide/models/get-advanced#Supported.Query.Clauses).

### Example

```php

$u = new User();

// SELECT `users`.*
// FROM `users`
// WHERE `users`.`birthdate` <= getLimitBirthdate(21)
$u->where_field_func('birthdate <=', 'getLimitBirthdate', 21);
$u->get();

```

# Fixing the Protect Identifiers Method

Modifying the CI_DB_driver::_protect_identifiers method as directed will help fix most problems with AR changing data. You can also "escape" any possibly protected data by wrapping it in parentheses.

***Please Note:*** If you upgrade your CodeIgniter installation, you'll have to make this change again!

In the file system/database/DB_driver.php, simply move the highlighted section, and remove .$alias from the return line.

#### system/database/DB_driver.php - v1.7.2 (Original)

```php
1235
1236
1237
1238
1239
1240
1241
1242
1243
1244
1245
1246
1247
1248
1249
1250
1251
1252
1253
1254        // Convert tabs or multiple spaces into single spaces
        $item = preg_replace('/[\t ]+/', ' ', $item);

        // If the item has an alias declaration we remove it and set it aside.
        // Basically we remove everything to the right of the first space
        $alias = '';
        if (strpos($item, ' ') !== FALSE)
        {
            $alias = strstr($item, " ");
            $item = substr($item, 0, - strlen($alias));
        }

        // This is basically a bug fix for queries that use MAX, MIN, etc.
        // If a parenthesis is found we know that we do not need to
        // escape the data or add a prefix.  There's probably a more graceful
        // way to deal with this, but I'm not thinking of it -- Rick
        if (strpos($item, '(') !== FALSE)
        {
            return $item.$alias;
        }

```

#### system/database/DB_driver.php - v1.7.2 (Modified)

```php
1235
1236
1237
1238
1239
1240
1241
1242
1243
1244
1245
1246
1247
1248
1249
1250
1251
1252
1253
1254        // This is basically a bug fix for queries that use MAX, MIN, etc.
        // If a parenthesis is found we know that we do not need to
        // escape the data or add a prefix.  There's probably a more graceful
        // way to deal with this, but I'm not thinking of it -- Rick
        if (strpos($item, '(') !== FALSE)
        {
            return $item; // Note this is different!
        }

        // Convert tabs or multiple spaces into single spaces
        $item = preg_replace('/[\t ]+/', ' ', $item);

        // If the item has an alias declaration we remove it and set it aside.
        // Basically we remove everything to the right of the first space
        $alias = '';
        if (strpos($item, ' ') !== FALSE)
        {
            $alias = strstr($item, " ");
            $item = substr($item, 0, - strlen($alias));
        }

```