# Server Requirements

- [PHP](http://php.net/) version 5.1.2 or newer (necessary for autoloading models). (Some extensions may require a newer version of PHP to function correctly.)
- [CodeIgniter](http://codeigniter.com/) version 1.7.2 or newer.
- [database supported by CodeIgniter. Read [CodeIgniter's Server Requirements](http://codeigniter.com/user_guide/general/requirements). PostgreSQL and MySQL are tested and supported. Other DBs should work. (Not all databases support all features.)

### Using PHP older than 5.1.2

It is possible, by manually modifying the DataMapper library, to get Datamapper ORM to work on PHP older than 5.1.2. PHP **5.0.0 or newer** is still required, and it is not officially supported.

[[this forum post for instructions](http://codeigniter.com/forums/viewreply/728767/).

::: info

### CodeIgniter 2.0

Datamapper is tested with the latest CodeIgniter 2.0 (which has not yet been released at this time) from the Bitbucket repository and is proven to work.

However, until it is released, we can not guarantee it will work with any particular development version.

::: info

### Expression Engine

Please note: Expression Engine is not officially supported.

Patches and suggestions are welcome, however.

::: info

### Using Oracle

Oracle probably will not work 100% out-of-the-box.

[ on [how to get Oracle working](http://codeigniter.com/forums/viewreply/729302/).