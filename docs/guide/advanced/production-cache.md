# Production Cache

***Important:***

You **must** clear the cache for any model you make changes to, whether that is in the database or in the file.

The **entire** production cache will need to be cleared if you make any changes to your datamapper config.

***Failure to do so will most likely result in errors, and could possibly lead to data corruption.***

To help make DataMapper a little more efficient per-page, Datamapper ORM offers the ability to cache certain dynamically loaded data when deployed to a production server.

### Tired of Seeing These Queries?

```php
SELECT * FROM tblname LIMIT 1
```

The first time a model is used on a request, DataMapper connects to the database server and loads in the columns for its table. This can create a few extra queries per page. Datamapper ORM also does a fair amount of set up on each class, determining things like relationship fields, tweaking the validation rules, and more. All of this can be cached to a file, which is included directly as PHP code.

## Enabling the Production Cache

There are three steps to enabling the production cache.

- Create a writeable folder on the production server that can serve as the cache. The default, and recommended folder, is **application**/datamapper/cache.

```php
$config['production_cache'] = 'datamapper/cache';
```
- Edit your **datamapper.php** config file, and uncomment or add this line:
- If necessary, change datamapper/cache to the directory you created. Remember, it must be relative to the **application** directory, and it shouldn't have a trailing slash (/).

Once enabled, the cache is created automatically, as models are first accessed. After the cache has been created, it will be used instead of the database queries.

Your cache directories might be outside the application directory. In that case, you can specify the fully qualified path to the production cache directory.

## What is Cached?

Datamapper ORM creates a file for each model. This allows it to be selective in what it loads. Each file contains:

- Generated Table Name
- Database Columns
- Modified Validation Array
- Modified Relationship Arrays
- Some Validation Meta Information

## Clearing the Cache

If you make any changes to a model, simply delete the cache file. The name of the file should be the same as the model's file name.

It is not recommended that you enable the production cache unless you are done testing or developing. The cache also may not provide a noticeable performance boost for small or simple websites, or when the database server is on the same host as the web server. It is worth testing your website with and without the cache before deciding whether or not to use it.

## Updating the Cache

As mentioned before, the cache is created automatically, and once it exists, it will be used, the database will not be checked for updates or modifications, for performance reasons.

However, there are occasions where you would like to be able to recreate the cache, without manually clearing it. For example:

- If your application contains code to dynamically update database tables
- If your application creates dynamic relations using the has_one() or has_many() methods

### Recreate the production cache

You can recreate the schema cache of a model by using

```php
$model->production_cache();
```

Calling this method while the production cache has been disabled in the configuration has no effect. No cache will be created.

## Disabling the Cache

To turn caching back off, comment out the line in the DataMapper config file. I also recommend immediately deleting all cache files when disabling the cache.