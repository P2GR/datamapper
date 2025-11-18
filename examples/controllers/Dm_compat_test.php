<?php
/**
 * DataMapper 2.0 - CI3 Compatibility Test Controller
 * 
 * Use this controller to verify DataMapper 2.0 works with your CI3 installation
 * 
 * Installation:
 * 1. Copy this file to application/controllers/Dm_compat_test.php
 * 2. Visit: http://yoursite.com/dm_compat_test
 * 3. Check results
 * 
 * @package     DataMapper 2.0
 * @category    Testing
 * @author      DataMapper 2.0 Team
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Dm_compat_test extends CI_Controller 
{
    public function index()
    {
        $results = [
            'php' => $this->test_php_version(),
            'ci' => $this->test_ci_version(),
            'database' => $this->test_database(),
            'datamapper' => $this->test_datamapper(),
            'methods' => $this->test_db_methods()
        ];
        
        $this->display_results($results);
    }
    
    /**
     * Test PHP version
     */
    private function test_php_version()
    {
        $version = PHP_VERSION;
        $result = [
            'label' => 'PHP Version',
            'value' => $version,
            'status' => 'success'
        ];
        
        if (version_compare($version, '8.2.0', '>=')) {
            $result['message'] = 'PHP 8.2+ detected. Recommended: Use PocketArc fork for best compatibility.';
            $result['recommendation'] = 'https://github.com/pocketarc/codeigniter';
        } elseif (version_compare($version, '7.4.0', '>=')) {
            $result['message'] = 'PHP 7.4-8.1 detected. Native CI3 or PocketArc fork both work well.';
        } else {
            $result['message'] = 'PHP version is older. Consider upgrading.';
            $result['status'] = 'warning';
        }
        
        return $result;
    }
    
    /**
     * Test CodeIgniter version
     */
    private function test_ci_version()
    {
        $version = CI_VERSION;
        $result = [
            'label' => 'CodeIgniter Version',
            'value' => $version,
            'status' => 'success'
        ];
        
        // Try to detect fork
        $system_path = BASEPATH;
        if (strpos($system_path, 'pocketarc') !== false) {
            $result['variant'] = 'PocketArc Fork';
            $result['message'] = 'PocketArc fork detected. Excellent choice for modern PHP!';
        } elseif (version_compare($version, '3.2.0', '>=')) {
            $result['variant'] = 'CI 3.2.0+ (possibly PocketArc)';
            $result['message'] = 'Running CI 3.2.0+. Should have excellent compatibility.';
        } else {
            $result['variant'] = 'Native CI3';
            $result['message'] = 'Native CI3 detected. Works great!';
        }
        
        return $result;
    }
    
    /**
     * Test database connection and driver
     */
    private function test_database()
    {
        $result = [
            'label' => 'Database Driver',
            'status' => 'success'
        ];
        
        try {
            $this->load->database();
            
            $result['driver'] = get_class($this->db);
            $result['type'] = $this->db->dbdriver;
            $result['version'] = $this->db->version();
            $result['has_dm_methods'] = method_exists($this->db, 'dm_call_method') ? 'Yes' : 'No';
            $result['message'] = 'Database connection successful!';
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'Database connection failed: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Test DataMapper files exist
     */
    private function test_datamapper()
    {
        $result = [
            'label' => 'DataMapper Files',
            'status' => 'success',
            'files' => []
        ];
        
        $required_files = [
            'DataMapper Library' => APPPATH . 'libraries/datamapper.php',
            'DB_driver Extension' => APPPATH . 'third_party/datamapper/system/DB_driver.php',
            'QueryBuilder' => APPPATH . 'datamapper/querybuilder.php',
            'Collection' => APPPATH . 'datamapper/collection.php',
            'Attribute Casting' => APPPATH . 'datamapper/attributecasting.php',
            'CI3 Compat' => APPPATH . 'datamapper/ci3_compat.php'
        ];
        
        $missing = [];
        foreach ($required_files as $name => $path) {
            $exists = file_exists($path);
            $result['files'][$name] = $exists ? 'Found' : 'Missing';
            if (!$exists) {
                $missing[] = $name;
            }
        }
        
        if (!empty($missing)) {
            $result['status'] = 'warning';
            $result['message'] = 'Some files are missing: ' . implode(', ', $missing);
        } else {
            $result['message'] = 'All DataMapper 2.0 files found!';
        }
        
        return $result;
    }
    
    /**
     * Test critical database methods
     */
    private function test_db_methods()
    {
        $result = [
            'label' => 'Database Methods',
            'status' => 'success',
            'methods' => []
        ];
        
        try {
            $this->load->database();
            
            $critical_methods = [
                'where_in' => 'Public method (good)',
                'or_where_in' => 'Public method (good)',
                'where_not_in' => 'Public method (good)',
                'or_where_not_in' => 'Public method (good)',
                'dm_call_method' => 'DataMapper extension (required)',
                'dm_get' => 'DataMapper extension (required)',
                'dm_set' => 'DataMapper extension (required)'
            ];
            
            foreach ($critical_methods as $method => $desc) {
                $exists = method_exists($this->db, $method);
                $result['methods'][$method] = $exists ? "✓ $desc" : "✗ Missing";
                
                if (!$exists && strpos($method, 'dm_') === 0) {
                    $result['status'] = 'error';
                }
            }
            
            if ($result['status'] === 'success') {
                $result['message'] = 'All critical methods available!';
            } else {
                $result['message'] = 'Some DataMapper methods missing. Check DB_driver.php installation.';
            }
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'Could not test methods: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Display results as HTML
     */
    private function display_results($results)
    {
        echo '<!DOCTYPE html>
<html>
<head>
    <title>DataMapper 2.0 - Compatibility Test Results</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 900px; 
            margin: 40px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { 
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .test-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            margin-top: 0;
            color: #667eea;
            font-size: 18px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        .status.success { background: #10b981; color: white; }
        .status.warning { background: #f59e0b; color: white; }
        .status.error { background: #ef4444; color: white; }
        .detail { 
            margin: 10px 0; 
            padding: 10px;
            background: #f9fafb;
            border-left: 3px solid #667eea;
        }
        .detail strong { color: #374151; }
        .methods { margin-top: 10px; }
        .methods div { padding: 5px; margin: 2px 0; }
        .recommendation {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>🧪 DataMapper 2.0 - Compatibility Test Results</h1>';
        
        foreach ($results as $key => $result) {
            $status_class = $result['status'];
            echo "<div class='test-section'>";
            echo "<h2>{$result['label']} <span class='status $status_class'>{$status_class}</span></h2>";
            
            if (isset($result['value'])) {
                echo "<div class='detail'><strong>Value:</strong> {$result['value']}</div>";
            }
            
            if (isset($result['variant'])) {
                echo "<div class='detail'><strong>Variant:</strong> {$result['variant']}</div>";
            }
            
            if (isset($result['driver'])) {
                echo "<div class='detail'><strong>Driver Class:</strong> {$result['driver']}</div>";
                echo "<div class='detail'><strong>Type:</strong> {$result['type']}</div>";
                echo "<div class='detail'><strong>Version:</strong> {$result['version']}</div>";
                echo "<div class='detail'><strong>Has DM Methods:</strong> {$result['has_dm_methods']}</div>";
            }
            
            if (isset($result['files'])) {
                echo "<div class='methods'>";
                foreach ($result['files'] as $name => $status) {
                    $icon = $status === 'Found' ? '✓' : '✗';
                    echo "<div>$icon <strong>$name:</strong> $status</div>";
                }
                echo "</div>";
            }
            
            if (isset($result['methods'])) {
                echo "<div class='methods'>";
                foreach ($result['methods'] as $name => $desc) {
                    echo "<div>$desc</div>";
                }
                echo "</div>";
            }
            
            if (isset($result['message'])) {
                echo "<p><strong>💬 Message:</strong> {$result['message']}</p>";
            }
            
            if (isset($result['recommendation'])) {
                echo "<div class='recommendation'>";
                echo "<strong>📌 Recommendation:</strong><br>";
                echo "Consider migrating to: <a href='{$result['recommendation']}' target='_blank'>{$result['recommendation']}</a>";
                echo "</div>";
            }
            
            echo "</div>";
        }
        
        echo '<div class="footer">';
        echo '<p>🎉 <strong>DataMapper 2.0</strong> - Universal CI3 Compatibility</p>';
        echo '<p>For detailed compatibility information, see <strong>CI3_COMPATIBILITY.md</strong></p>';
        echo '<p><a href="https://github.com/pocketarc/codeigniter" target="_blank">PocketArc Fork</a> | ';
        echo '<a href="https://github.com/bcit-ci/CodeIgniter" target="_blank">Native CI3</a></p>';
        echo '</div>';
        
        echo '</body></html>';
    }
}
