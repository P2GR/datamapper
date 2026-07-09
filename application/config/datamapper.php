<?php	if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * DataMapper 2.0 Configuration
 *
 * Global configuration settings that apply to all DataMapper models.
 * This configuration includes both legacy settings and new DataMapper 2.0 features
 * like automatic timestamps, soft deletes, and enhanced caching.
 *
 * @package    DataMapper ORM
 * @category   Configuration
 * @author     DataMapper Development Team
 * @version    2.0.0
 */

/*
|--------------------------------------------------------------------------
| Table and Column Prefixes
|--------------------------------------------------------------------------
|
| Prefix to add to table names when generating model table names.
| join_prefix is added to relationship table names.
|
*/
$config['prefix'] = '';        // Table name prefix
$config['join_prefix'] = '';   // Join table prefix

/*
|--------------------------------------------------------------------------
| Error Display Configuration
|--------------------------------------------------------------------------
|
| HTML tags to wrap around validation error messages.
| Used in form validation and model error display.
|
*/
$config['error_prefix'] = '<p class="error">';
$config['error_suffix'] = '</p>';

/*
|--------------------------------------------------------------------------
| Legacy Timestamp Configuration
|--------------------------------------------------------------------------
|
| Default field names for created/updated timestamps (legacy system).
| For new projects, consider using the modern timestamps feature below.
|
*/
$config['created_field'] = 'created';     // Legacy created timestamp field
$config['updated_field'] = 'updated';     // Legacy updated timestamp field

/*
|--------------------------------------------------------------------------
| Time Configuration
|--------------------------------------------------------------------------
|
| Settings for how timestamps are handled and formatted.
| local_time: Use local timezone instead of UTC
| unix_timestamp: Store as Unix timestamp instead of datetime string
| timestamp_format: PHP date format for timestamp fields
|
*/
$config['local_time'] = FALSE;             // Use local timezone (recommended: false for UTC)
$config['unix_timestamp'] = FALSE;         // Use Unix timestamps (recommended: false for MySQL datetime)
$config['timestamp_format'] = 'Y-m-d H:i:s'; // MySQL datetime format

/*
|--------------------------------------------------------------------------
| Language File Configuration
|--------------------------------------------------------------------------
|
| Patterns for automatically loading language files and field labels.
| ${model} is replaced with the model name.
|
*/
$config['lang_file_format'] = 'model_${model}';
$config['field_label_lang_format'] = '${model}_${field}';

/*
|--------------------------------------------------------------------------
| Database Transaction Configuration
|--------------------------------------------------------------------------
|
| auto_transaction: Automatically wrap save operations in transactions
| Recommended: false (handle transactions manually for better control)
|
*/
$config['auto_transaction'] = FALSE;

/*
|--------------------------------------------------------------------------
| Relationship Auto-Population
|--------------------------------------------------------------------------
|
| Controls whether related objects are automatically loaded.
| has_many: Load all related records (can be memory intensive)
| has_one: Load single related record (can impact performance)
|
*/
$config['auto_populate_has_many'] = FALSE; // Don't auto-load has_many (performance)
$config['auto_populate_has_one'] = FALSE;  // Don't auto-load has_one (performance)

/*
|--------------------------------------------------------------------------
| Array Result Configuration
|--------------------------------------------------------------------------
|
| all_array_uses_ids: When getting all() as array, use IDs as array keys
| Recommended: false for consistent array indexing
|
*/
$config['all_array_uses_ids'] = FALSE;

/*
|--------------------------------------------------------------------------
| Database Connection Configuration
|--------------------------------------------------------------------------
|
| Database connection parameters. Set to FALSE to use the same DB instance
| across all models (may break subqueries). Set to array or string to
| specify different connection parameters.
|
*/
$config['db_params'] = '';

/*
|--------------------------------------------------------------------------
| Production Cache Configuration
|--------------------------------------------------------------------------
|
| Enable query result caching for production performance.
| Uncomment and set path to enable caching.
|
*/
// $config['production_cache'] = 'datamapper/cache';

/*
|--------------------------------------------------------------------------
| Extensions Configuration
|--------------------------------------------------------------------------
|
| Path to DataMapper extensions and list of extensions to auto-load.
| Extensions provide additional functionality.
|
*/
$config['extensions_path'] = 'datamapper';
$config['extensions'] = array('array', 'json');

/*
|--------------------------------------------------------------------------
| Cascade Delete Configuration
|--------------------------------------------------------------------------
|
| When deleting a record, automatically delete related records.
| Recommended: true for data integrity
|
*/
$config['cascade_delete'] = TRUE;

/*
|--------------------------------------------------------------------------
| DataMapper 2.0 - Modern Timestamps
|--------------------------------------------------------------------------
|
| Enable automatic created_at/updated_at timestamp management.
| Modern DataMapper projects should import the HasTimestamps trait on
| each model that requires timestamps. The global toggle remains for
| legacy configurations but trait usage is now the preferred pattern.
|
*/
$config['timestamps'] = FALSE;             // Global timestamp management (enable per-model)
$config['created_at_column'] = 'created_at';
$config['updated_at_column'] = 'updated_at';

/*
|--------------------------------------------------------------------------
| DataMapper 2.0 - Soft Deletes
|--------------------------------------------------------------------------
|
| Enable soft deletion functionality. Modern DataMapper projects should
| import the SoftDeletes trait on each model that requires soft delete
| behaviour. The global toggle remains for legacy configurations but
| traits now control activation.
|
*/
$config['soft_delete'] = FALSE;           // Global soft delete (enable per-model)
$config['deleted_at_column'] = 'deleted_at';

/*
|--------------------------------------------------------------------------
| Cache Driver Configuration
|--------------------------------------------------------------------------
|
| Configure the caching driver for DataMapper query results.
| Options: 'file', 'redis', 'memcached', 'none'
|
*/
$config['cache_driver'] = 'file';

/*
|--------------------------------------------------------------------------
| Cache Configuration
|--------------------------------------------------------------------------
|
| Driver-specific cache configuration.
| Adjust based on your chosen cache driver.
|
*/
$config['cache_config'] = array(
    'cache_dir' => APPPATH . 'cache/datamapper',
    'file_mode' => 0640
);

// Redis configuration example (uncomment if using redis driver)
/*
$config['cache_config'] = array(
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => '',
    'database' => 0,
    'prefix' => 'dm:',
    'timeout' => 2.5
);
*/

/* End of file datamapper.php */
/* Location: ./application/config/datamapper.php */
