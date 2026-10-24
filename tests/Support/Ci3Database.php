<?php

namespace Tests\Support;

class Ci3Database
{
    public static function connect(array $params = array())
    {
        if (!is_file(BASEPATH . 'database/DB_driver.php')) {
            throw new \RuntimeException('Clone CI3 3.1.13 to tests/.ci3 or set CI3_SYSTEM_PATH.');
        }

        require_once __DIR__ . '/ci3_functions.php';
        if (!defined('DATAMAPPERPATH')) {
            define('DATAMAPPERPATH', APPPATH);
        }
        require_once APPPATH . 'third_party/datamapper/system/DB.php';
        require_once BASEPATH . 'database/DB_result.php';
        require_once BASEPATH . 'database/drivers/sqlite3/sqlite3_result.php';
        if (($params['dbdriver'] ?? 'sqlite3') === 'pdo') {
            require_once BASEPATH . 'database/drivers/pdo/pdo_result.php';
        }

        // CI3's supported legacy driver declares dynamic properties on PHP 8.2+.
        $reporting = error_reporting(E_ALL & ~E_DEPRECATED);
        try {
            return \DB(array_merge(array(
                'dbdriver' => 'sqlite3', 'database' => ':memory:',
                'db_debug' => FALSE, 'save_queries' => TRUE,
            ), $params), TRUE);
        } finally {
            error_reporting($reporting);
        }
    }
}
