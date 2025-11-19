# Setting up Table Prefixes

[[Installation Instructions](installation) asks you to make sure you set the dbprefix in your database settings to an empty string. The reason for this is because DataMapper has its own way of managing prefixing, giving some added flexibility as well.

[[Relationship Types](/guide/relationships/types) section.

## Prefix Settings

There's a few ways you can define your prefixes, with the use of the *$prefix* and *$join_prefix* class variables.

- *$prefix* - If set, will require all tables (both normal and joining tables) to have this prefix.
- *$join_prefix* - If set, will require all joining tables to have this prefix (overrides *$prefix*).

[[DataMapper config](/guide/getting-started/configuration), rather than setting the same prefixes in all of them. If you do this, you can still override the prefix for individual models by setting the prefix within them.

## Prefix Only

Let's go with the assumption that we've set our prefix up like so, and it applies to **all** of our models:

```php

var $prefix = "ci_";
var $join_prefix = "";

```

[[Database Tables](/guide/getting-started/database) section, those being **countries**, **countries_users** and **users**, this is how they would be changed to work with the above set prefix:

### ci_countries

### ci_countries_users

### ci_users

You'll notice that only the table names were affected, including the joining table's name, and that prefixing has no affect on the field names.

## Both Prefixes

Let's change our prefixes so we're setting a different prefix for our joining tables:

```php

var $prefix = "normal_";
var $join_prefix = "join_";

```

### normal_countries

### join_countries_users

### normal_users

## Join Prefix Only

Now let's change it so we're only prefixing our joining table's, leaving our normal tables without a prefix:

```php

var $prefix = "";
var $join_prefix = "join_";

```

### countries

### join_countries_users

### users

## Combination Prefix

[**all** of our models, by setting it in the [DataMapper config](/guide/getting-started/configuration):

```php

var $prefix = "normal_";
var $join_prefix = "join_";

```

And then had the following in our **users** model:

```php

var $prefix = "special_";

```

***Important:*** All joining tables must use the same prefix, so you should not override the **$join_prefix** with a different value if it is already set.

The tables would end up as:

### normal_countries

### join_countries_users

### special_users