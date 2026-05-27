# Server Requirements

DataMapper ORM 2.0 requires:

- **[PHP](http://php.net/)** version **7.4 or newer** (PHP 8.0, 8.1, 8.2, and 8.3 are fully supported)
- **[CodeIgniter](http://codeigniter.com/)** version **3.1.13 or newer**
- A database supported by CodeIgniter (MySQL, PostgreSQL, SQLite, etc.)

::: tip Recommended
- PHP 8.1+
- CodeIgniter 3.1.13 (latest stable version)
- 
- MySQL 5.7+ or PostgreSQL 10+
:::

## PHP Version Support

| PHP Version | Support Status |
|-------------|----------------|
| 7.4 - 8.3   |  Fully Supported |
| 7.0 - 7.3   |  Not Supported |
| 5.x         |  Not Supported |

## CodeIgniter Version Support

DataMapper ORM 2.0 is designed specifically for **CodeIgniter 3.x**. 

::: tip Recommended Fork
For modern PHP 8+ support and active maintenance, we recommend using the [pocketarc CodeIgniter 3 fork](https://github.com/pocketarc/codeigniter), which includes PHP 8.1 - PHP 8.5 compatibility and continued updates.
:::

::: warning CodeIgniter 4
CodeIgniter 4 is not supported. If you need an ORM for CI4, consider using CodeIgniter's built-in Entity/Model system.
:::

## Database Support

DataMapper has been tested and is fully compatible with:

- **MySQL** 5.7+ / MariaDB 10.2+
- **PostgreSQL** 10+
- **SQLite** 3.x

Other databases supported by CodeIgniter should work, but may have limited testing.

::: info Need Help?
See the [Installation Guide](/guide/getting-started/installation) for setup instructions or visit our [Troubleshooting](/help/troubleshooting) page if you encounter issues.
:::
