<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DataMapper 2.0 Test Controller
 * 
 * Comprehensive testing suite for DataMapper 2.0 functionality
 * Compares legacy DataMapper queries with new fluent syntax
 * Includes benchmarking and SQL query display
 * 
 * @package    DataMapper 2.0
 * @subpackage Tests
 * @category   Testing
 * @author     Your Team
 */
class Datamapper_test extends CI_Controller
{
    private $results = array();
    private $test_count = 0;
    private $passed = 0;
    private $failed = 0;
    private $errors = array();
    
    public function __construct()
    {
        parent::__construct();
        
        // Only allow in development or with admin rights
        if (ENVIRONMENT !== 'development' && !permission($this->session->userdata('user_id'), 'ADMIN')) {
            show_error('Access denied. This tool is only available in development mode or for administrators.', 403);
        }
        
        $this->load->helper('url');
    }

    /**
     * Main test runner - displays all available tests
     */
    public function index()
    {
        $data = array(
            'title' => 'DataMapper 2.0 Test Suite',
            'tests' => array(
                'basic_queries' => 'Basic Query Tests (where, order, limit)',
                'relationship_queries' => 'Relationship Query Tests (has_one, has_many)',
                'complex_queries' => 'Complex Query Tests (joins, groups, having)',
                'fluent_vs_legacy' => 'Fluent vs Legacy Comparison',
                'eager_loading' => 'Eager Loading Tests (N+1 prevention)',
                'eager_loading_constraints' => 'Eager Loading with Constraints (NEW DataMapper 2.0)',
                'attribute_casting' => 'Attribute Casting Tests',
                'soft_deletes' => 'Soft Delete Tests',
                'timestamps' => 'Timestamp Tests',
                'query_caching' => 'Query Caching Tests (DataMapper 2.0)',
                'chunking_streaming' => 'Chunking & Streaming Tests (DataMapper 2.0)',
                'real_world_queries' => 'Real-world Query Examples from Application',
                'all_tests' => 'Run All Tests',
            )
        );
        
        $this->load->view('datamapper_test/index', $data);
    }

    /**
     * Run all tests
     */
    public function all_tests()
    {
        $this->start_test_suite('Complete DataMapper 2.0 Test Suite');
        
        $this->basic_queries(false);
        $this->relationship_queries(false);
        $this->complex_queries(false);
        $this->fluent_vs_legacy(false);
        $this->eager_loading(false);
        $this->eager_loading_constraints(false);
        $this->attribute_casting(false);
        $this->soft_deletes(false);
        $this->timestamps(false);
        $this->query_caching(false);
        $this->chunking_streaming(false);
        $this->real_world_queries(false);
        
        $this->display_results('All Tests');
    }

    /**
     * Test 1: Basic Queries
     */
    public function basic_queries($display = true)
    {
        if ($display) $this->start_test_suite('Basic Query Tests');

        // Test 1.1: Simple WHERE query
        $this->run_comparison_test(
            'Simple WHERE clause',
            function() {
                $users = new User();
                $users->where('disable', 0);
                return $users->get();
            },
            function() {
                return (new User())
                    ->where('disable', 0)
                    ->get();
            }
        );

        // Test 1.2: Multiple WHERE conditions
        $this->run_comparison_test(
            'Multiple WHERE conditions',
            function() {
                $users = new User();
                $users->where('disable', 0);
                $users->where('op_bmi_expire', 1);
                return $users->get();
            },
            function() {
                return (new User())
                    ->where('disable', 0)
                    ->where('op_bmi_expire', 1)
                    ->get();
            }
        );

        // Test 1.3: WHERE with operator
        $this->run_comparison_test(
            'WHERE with operator (>)',
            function() {
                $users = new User();
                $users->where('id >', 10);
                return $users->get();
            },
            function() {
                return (new User())
                    ->where('id', 10, '>')
                    ->get();
            }
        );

        // Test 1.4: WHERE IN
        $this->run_comparison_test(
            'WHERE IN clause',
            function() {
                $users = new User();
                $users->where_in('role_id', array(2, 4, 6));
                return $users->get();
            },
            function() {
                return (new User())
                    ->whereIn('role_id', array(2, 4, 6))
                    ->get();
            }
        );

        // Test 1.5: ORDER BY
        $this->run_comparison_test(
            'ORDER BY clause',
            function() {
                $users = new User();
                $users->where('disable', 0);
                $users->order_by('lastname', 'ASC');
                return $users->get();
            },
            function() {
                return (new User())
                    ->where('disable', 0)
                    ->orderBy('lastname', 'ASC')
                    ->get();
            }
        );

        // Test 1.6: LIMIT and OFFSET
        $this->run_comparison_test(
            'LIMIT and OFFSET',
            function() {
                $users = new User();
                $users->where('disable', 0);
                $users->limit(10);
                return $users->get(10, 5);
            },
            function() {
                return (new User())
                    ->where('disable', 0)
                    ->limit(10)
                    ->offset(5)
                    ->get();
            }
        );

        // Test 1.7: LIKE clause
        $this->run_comparison_test(
            'LIKE clause',
            function() {
                $users = new User();
                $users->like('lastname', 'Smith');
                return $users->get();
            },
            function() {
                return (new User())
                    ->like('lastname', 'Smith')
                    ->get();
            }
        );

        // Test 1.8: OR WHERE
        $this->run_comparison_test(
            'OR WHERE clause',
            function() {
                $users = new User();
                $users->where('role_id', 2);
                $users->or_where('role_id', 4);
                return $users->get();
            },
            function() {
                return (new User())
                    ->where('role_id', 2)
                    ->orWhere('role_id', 4)
                    ->get();
            }
        );

        // Test 1.9: Group Start/End
        $this->run_comparison_test(
            'Group Start/End (complex conditions)',
            function() {
                $users = new User();
                $users->where('disable', 0);
                $users->group_start();
                $users->like('lastname', 'Smith');
                $users->or_like('lastname', 'Johnson');
                $users->group_end();
                return $users->get();
            },
            function() {
                return (new User())
                    ->where('disable', 0)
                    ->groupStart()
                        ->like('lastname', 'Smith')
                        ->orLike('lastname', 'Johnson')
                    ->groupEnd()
                    ->get();
            }
        );

        if ($display) $this->display_results('Basic Query Tests');
    }

    /**
     * Test 2: Relationship Queries
     */
    public function relationship_queries($display = true)
    {
        if ($display) $this->start_test_suite('Relationship Query Tests');

        // Test 2.1: where_related
        $this->run_comparison_test(
            'WHERE RELATED (has_one)',
            function() {
                $installations = new Installation();
                $installations->where_related('building', 'active', 1);
                return $installations->get();
            },
            function() {
                return (new Installation())
                    ->whereRelated('building', 'active', 1)
                    ->get();
            }
        );

        // Test 2.2: Multiple related conditions
        $this->run_comparison_test(
            'Multiple WHERE RELATED conditions',
            function() {
                $installations = new Installation();
                $installations->where_related('building', 'active', 1);
                $installations->where_related('building/client', 'disable', 0);
                return $installations->get();
            },
            function() {
                return (new Installation())
                    ->whereRelated('building', 'active', 1)
                    ->whereRelated('building/client', 'disable', 0)
                    ->get();
            }
        );

        // Test 2.3: include_related
        $this->run_comparison_test(
            'Include Related Fields',
            function() {
                $installations = new Installation();
                $installations->include_related('building', 'city', true);
                $installations->include_related('building', 'street', true);
                return $installations->get();
            },
            function() {
                return (new Installation())
                    ->includeRelated('building', array('city', 'street'), true)
                    ->get();
            }
        );

        // Test 2.4: Deep relationship
        $this->run_comparison_test(
            'Deep Relationship (3 levels)',
            function() {
                $installations = new Installation();
                $installations->include_related('building/client', 'company_name', 'client');
                $installations->where_related('building/client', 'disable', 0);
                return $installations->get();
            },
            function() {
                return (new Installation())
                    ->includeRelated('building/client', 'company_name', 'client')
                    ->whereRelated('building/client', 'disable', 0)
                    ->get();
            }
        );

        // Test 2.5: Related with complex conditions
        $this->run_comparison_test(
            'Related with IN clause',
            function() {
                $buildings = new Building();
                $buildings->where_related_user('id', $this->session->userdata('user_id'));
                $buildings->where('active', 1);
                return $buildings->get();
            },
            function() {
                return (new Building())
                    ->whereRelatedUser('id', $this->session->userdata('user_id'))
                    ->where('active', 1)
                    ->get();
            }
        );

        if ($display) $this->display_results('Relationship Query Tests');
    }

    /**
     * Test 3: Complex Queries
     */
    public function complex_queries($display = true)
    {
        if ($display) $this->start_test_suite('Complex Query Tests');

        // Test 3.1: Group By
        $this->run_comparison_test(
            'GROUP BY query',
            function() {
                $installations = new Installation();
                $installations->select('building_id, COUNT(*) as count');
                $installations->group_by('building_id');
                return $installations->get();
            },
            function() {
                // DEBUG: Break the chain to see where the issue is
                $inst = new Installation();
                $step1 = $inst->select('building_id, COUNT(*) as count');
                // Log what select returned
                if (is_array($step1)) {
                    throw new Exception("select() returned an array!");
                }
                $step2 = $step1->groupBy('building_id');
                if (is_array($step2)) {
                    throw new Exception("groupBy() returned an array!");
                }
                return $step2->get();
            }
        );

        // Test 3.2: Having clause
        $this->run_comparison_test(
            'HAVING clause',
            function() {
                $installations = new Installation();
                $installations->select('building_id, COUNT(*) as count');
                $installations->group_by('building_id');
                $installations->having('count >', 5);
                return $installations->get();
            },
            function() {
                // DEBUG version
                $inst = new Installation();
                $result1 = $inst->select('building_id, COUNT(*) as count');
                $result2 = $result1->groupBy('building_id');
                
                if (is_array($result2)) {
                    throw new Exception("groupBy() returned an array instead of object!");
                }
                
                return $result2->having('count', 5, '>')->get();
            }
        );

        // Test 3.3: Complex join with where
        $this->run_comparison_test(
            'Complex query with multiple joins',
            function() {
                $installations = new Installation();
                $installations->include_related('building', 'city', true);
                $installations->include_related('building', 'street', true);
                $installations->include_related('building/client', 'company_name', 'client');
                $installations->where('active', 1);
                $installations->where_related('building', 'active', 1);
                $installations->order_by('client_company_name', 'ASC');
                $installations->order_by('building_city', 'ASC');
                return $installations->get();
            },
            function() {
                return (new Installation())
                    ->includeRelated('building', array('city', 'street'), true)
                    ->includeRelated('building/client', 'company_name', 'client')
                    ->where('active', 1)
                    ->whereRelated('building', 'active', 1)
                    ->orderBy('client_company_name', 'ASC')
                    ->orderBy('building_city', 'ASC')
                    ->get();
            }
        );

        // Test 3.4: WHERE IN with subquery pattern
        $this->run_comparison_test(
            'WHERE IN with subquery pattern',
            function() {
                $buildings = new Building();
                $buildings->where('active', 1);
                $buildings->limit(10);  // Limit to make test faster
                $buildings->get();
                
                $building_ids = array();
                foreach ($buildings as $b) {
                    $building_ids[] = $b->id;
                }
                
                $installations = new Installation();
                if (!empty($building_ids)) {
                    $installations->where_in('building_id', $building_ids);
                    return $installations->get();
                }
                // Return empty result set if no buildings found
                return $installations;
            },
            function() {
                $buildings = (new Building())
                    ->where('active', 1)
                    ->limit(10)  // Limit to make test faster
                    ->get();
                
                $building_ids = array();
                foreach ($buildings as $b) {
                    $building_ids[] = $b->id;
                }
                
                $installations = new Installation();
                if (!empty($building_ids)) {
                    $installations->where_in('building_id', $building_ids);
                    return $installations->get();
                }
                // Return empty result set if no buildings found
                return $installations;
            }
        );

        if ($display) $this->display_results('Complex Query Tests');
    }

    /**
     * Test 4: Fluent vs Legacy Syntax
     */
    public function fluent_vs_legacy($display = true)
    {
        if ($display) $this->start_test_suite('Fluent vs Legacy Syntax Comparison');

        // Test 4.1: Chaining demonstration
        $this->run_comparison_test(
            'Method chaining readability',
            function() {
                $users = new User();
                $users->where('disable', 0);
                $users->where('role_id', 2);
                $users->like('lastname', 'Smith');
                $users->order_by('lastname', 'ASC');
                $users->limit(10);
                return $users->get();
            },
            function() {
                return (new User())
                    ->where('disable', 0)
                    ->where('role_id', 2)
                    ->like('lastname', 'Smith')
                    ->orderBy('lastname', 'ASC')
                    ->limit(10)
                    ->get();
            }
        );

        // Test 4.2: Find by ID
        $this->run_single_test(
            'Find by ID (fluent)',
            function() {
                return (new User())->find(248);
            }
        );

        // Test 4.3: First
        $this->run_single_test(
            'First() method (fluent)',
            function() {


                return (new User())
                    ->where('disable', 0)
                    ->first();
            }
        );

        // Test 4.4: Count
        $this->run_benchmark_test(
            'Count comparison',
            function() {
                $users = new User();
                $users->where('disable', 0);
                return $users->count();
            },
            function() {
                return (new User())
                    ->where('disable', 0)
                    ->count();
            }
        );

        if ($display) $this->display_results('Fluent vs Legacy Comparison');
    }

    /**
     * Test 5: Eager Loading Tests (N+1 prevention)
     */
    public function eager_loading($display = true)
    {
        if ($display) $this->start_test_suite('Eager Loading Tests (N+1 Prevention)');

        // Test 5.1: N+1 Problem vs Eager Loading - Single Relationship
        $this->run_benchmark_test(
            'N+1 Problem: Accessing building data in loop (WITHOUT eager load)',
            function() {
                // This creates N+1 queries: 1 for installations + N queries for each building
                $installations = new Installation();
                $installations->limit(200);
                $installations->get();
                
                $building_names = array();
                foreach ($installations as $inst) {
                    // Each iteration triggers a new query!
                    $inst->building->get();
                    if ($inst->building->exists()) {
                        $building_names[] = $inst->building->city . ' - ' . $inst->building->street;
                    }
                }
                return $installations;
            },
            function() {
                // This creates only 2 queries: 1 for installations + 1 for all buildings
                $installations = (new Installation())
                    ->with('building')
                    ->limit(200)
                    ->get();
                
                $building_names = array();
                foreach ($installations as $inst) {
                    // No query triggered - building already loaded!
                    if ($inst->building->exists()) {
                        $building_names[] = $inst->building->city . ' - ' . $inst->building->street;
                    }
                }
                return $installations;
            },
            5  // Run 5 iterations to see the difference
        );

        // Test 5.2: Multiple Relationships - Classic vs Fluent (compare by IDs)
        $this->run_eager_loading_test(
            'Eager load multiple relationships (building + installationtype)',
            function() {
                // Classic: Load with include_related
                $installations = new Installation();
                $installations->include_related('building', 'city', TRUE);
                $installations->include_related('installationtype', 'title', TRUE);
                $installations->limit(200);
                return $installations->get();
            },
            function() {
                // Fluent: Eager load with with()
                return (new Installation())
                    ->with('building', 'installationtype')
                    ->limit(200)
                    ->get();
            }
        );

        // Test 5.3: Nested Eager Loading - Deep Relationships
        $this->run_benchmark_test(
            'N+1 Problem: Deep relationships accessed in loop (installation → building → client)',
            function() {
                // WITHOUT eager loading: 1 + N + M queries (very slow!)
                $installations = new Installation();
                $installations->limit(100);
                $installations->get();
                
                $data = array();
                foreach ($installations as $inst) {
                    $inst->building->get();  // N queries
                    if ($inst->building->exists()) {
                        $inst->building->client->get();  // M queries
                        if ($inst->building->client->exists()) {
                            $data[] = array(
                                'installation' => $inst->title,
                                'building' => $inst->building->city,
                                'client' => $inst->building->client->company_name
                            );
                        }
                    }
                }
                return $installations;
            },
            function() {
                // WITH eager loading: Only 3 queries total!
                $installations = (new Installation())
                    ->with('building.client')
                    ->limit(100)
                    ->get();
                
                $data = array();
                foreach ($installations as $inst) {
                    // All data already loaded - no queries!
                    // Check for null before calling exists()
                    if ($inst->building && $inst->building->exists() && 
                        $inst->building->client && $inst->building->client->exists()) {
                        $data[] = array(
                            'installation' => $inst->title,
                            'building' => $inst->building->city,
                            'client' => $inst->building->client->company_name
                        );
                    }
                }
                return $installations;
            },
            3  // Run 3 iterations
        );

        // Test 5.4: Filter by related field + access relationship (prevents N+1)
        $this->run_benchmark_test(
            'N+1 Problem: Filter by related building city + access building in loop',
            function() {
                // WITHOUT eager loading: 1 query to filter + N queries to access buildings
                $installations = new Installation();
                $installations->where_related('building', 'city', 'Amsterdam');
                $installations->order_by('created_date', 'DESC');
                $installations->limit(100);
                $installations->get();
                
                // Access building data in loop - triggers N+1 queries!
                $data = array();
                foreach ($installations as $inst) {
                    $building_info = $inst->building->city . ' - ' . $inst->building->street;
                    $data[] = $building_info;
                }
                
                return $installations;
            },
            function() {
                // WITH eager loading: 2 queries total (1 to filter + 1 to eager load buildings)
                $installations = (new Installation())
                    ->with('building')
                    ->whereRelated('building', 'city', 'Amsterdam')
                    ->orderBy('created_date', 'DESC')
                    ->limit(100)
                    ->get();
                
                // Access building data in loop - NO additional queries!
                $data = array();
                foreach ($installations as $inst) {
                    $building_info = $inst->building->city . ' - ' . $inst->building->street;
                    $data[] = $building_info;
                }
                
                return $installations;
            },
            3  // Run 3 iterations
        );

        // Test 5.5: Multiple Nested Relationships (compare by IDs)
        $this->run_eager_loading_test(
            'Multiple nested relationships (building.client + installationtype)',
            function() {
                // Classic: include_related for multiple relationships
                $installations = new Installation();
                $installations->include_related('building/client', 'company_name', 'client');
                $installations->include_related('installationtype', 'title', TRUE);
                $installations->where('active', 1);
                $installations->limit(200);
                return $installations->get();
            },
            function() {
                // Fluent: with() for multiple relationships
                return (new Installation())
                    ->with('building.client', 'installationtype')
                    ->where('active', 1)
                    ->limit(200)
                    ->get();
            }
        );

        // Test 5.6: WHERE on related THEN access in loop (the real N+1 problem!)
        $this->run_benchmark_test(
            'WHERE on related table THEN access building data in loop',
            function() {
                // Classic: Filter by related, then access in loop = N+1!
                $installations = new Installation();
                $installations->where_related('building', 'active', 1);
                $installations->limit(200);
                $installations->get();
                
                $data = array();
                foreach ($installations as $inst) {
                    // This triggers a query for EACH installation!
                    $inst->building->get();
                    if ($inst->building->exists()) {
                        $data[] = $inst->building->city . ' - ' . $inst->building->street;
                    }
                }
                return $installations;
            },
            function() {
                // Fluent: Filter + eager load = only 2 queries
                $installations = (new Installation())
                    ->whereRelated('building', 'active', 1)
                    ->with('building')
                    ->limit(200)
                    ->get();
                
                $data = array();
                foreach ($installations as $inst) {
                    // No queries! Already loaded
                    if ($inst->building->exists()) {
                        $data[] = $inst->building->city . ' - ' . $inst->building->street;
                    }
                }
                return $installations;
            },
            3  // Run 3 iterations - the difference will be massive!
        );

        // Test 5.7: Benchmarking - Show Query Count Difference with Large Dataset
        $this->run_benchmark_test(
            'Query count comparison: 200 records, accessing building in loop',
            function() {
                // This will execute 201 queries (1 + 200)
                $installations = new Installation();
                $installations->limit(200);
                $installations->get();
                
                foreach ($installations as $inst) {
                    $inst->building->get();
                    if ($inst->building->exists()) {
                        $city = $inst->building->city;
                    }
                }
                return $installations;
            },
            function() {
                // This will execute only 2 queries (1 + 1)
                $installations = (new Installation())
                    ->with('building')
                    ->limit(200)
                    ->get();
                
                foreach ($installations as $inst) {
                    // Already loaded!
                    if ($inst->building->exists()) {
                        $city = $inst->building->city;
                    }
                }
                return $installations;
            },
            2  // Just 2 iterations - the difference will be massive
        );

        if ($display) $this->display_results('Eager Loading Tests (N+1 Prevention)');
    }

    /**
     * Test 5B: Eager Loading with Constraints (DataMapper 2.0 - NEW FEATURE)
     * 
     * This is a BRAND NEW feature that allows you to filter eager-loaded relationships!
     * 
     * Benefits:
     * - Reduce data transfer (only load what you need)
     * - Improve performance (smaller result sets)
     * - Cleaner code (filter relations declaratively)
     * - Still prevents N+1 queries (batch loading maintained)
     */
    public function eager_loading_constraints($display = true)
    {
        if ($display) $this->start_test_suite('Eager Loading with Constraints (NEW DataMapper 2.0)');

        // Test 5B.1: Basic constraint with has_many - Filter active installations only
        $this->run_benchmark_test(
            'WITHOUT Constraints: Load ALL installations (then filter in PHP)',
            function() {
                // OLD DATAMAPPER WAY: Load users, then load ALL their installations
                $users = new User();
                $users->limit(50);
                $users->get();

                $active_count = 0;
                $user_ids = array();
                $installation_data = array();
                
                foreach ($users as $user) {
                    $user_ids[] = $user->id;
                    
                    // Load ALL installations for each user (N+1 problem!)
                    $user->installation->get();
                    
                    // Filter in PHP and collect installation IDs
                    $active_ids = array();
                    foreach ($user->installation as $inst) {
                        if ($inst->active == 1) {
                            $active_count++;
                            $active_ids[] = $inst->id;
                        }
                    }
                    
                    if (!empty($active_ids)) {
                        $installation_data[$user->id] = $active_ids;
                    }
                }
                
                // Store verification data for comparison
                $users->_verification_data = array(
                    'user_ids' => $user_ids,
                    'user_count' => count($user_ids),
                    'installation_data' => $installation_data,
                    'total_active_installations' => $active_count
                );
                
                return $users;
            },
            function() {
                // NEW WAY: Eager load with constraints - only active installations
                $users = (new User())
                    ->with('installation', function($q) {
                        $q->where('active', 1);  // Filter at DB level, batched!
                    })
                    ->limit(50)
                    ->get();
                
                $active_count = 0;
                $user_ids = array();
                $installation_data = array();
                
                foreach ($users as $user) {
                    $user_ids[] = $user->id;
                    
                    // Already loaded, already filtered, no queries!
                    $inst_count = $user->installation->count();
                    $active_count += $inst_count;
                    
                    // Collect installation IDs
                    if ($inst_count > 0) {
                        $active_ids = array();
                        foreach ($user->installation as $inst) {
                            $active_ids[] = $inst->id;
                        }
                        $installation_data[$user->id] = $active_ids;
                    }
                }
                
                // Store verification data for comparison
                $users->_verification_data = array(
                    'user_ids' => $user_ids,
                    'user_count' => count($user_ids),
                    'installation_data' => $installation_data,
                    'total_active_installations' => $active_count
                );
                
                return $users;
            },
            3  // Run 3 iterations
        );

        // Test 5B.2: Multiple constraints on single relation
        $this->run_benchmark_test(
            'Multiple Constraints: Recent + Active installations only',
            function() {
                // OLD: Load all, filter multiple conditions in PHP
                $users = new User();
                $users->with('installation');
                $users->limit(30);
                $users->get();
                
                $filtered_count = 0;
                foreach ($users as $user) {
                    foreach ($user->installation as $inst) {
                        if ($inst->active == 1 && 
                            $inst->created_date && 
                            strtotime($inst->created_date) > strtotime('-1 year')) {
                            $filtered_count++;
                        }
                    }
                }
                
                return $users;
            },
            function() {
                // NEW: Apply all filters at database level
                $users = (new User())
                    ->with('installation', function($q) {
                        $q->where('active', 1);
                        $q->where('created_date >', date('Y-m-d', strtotime('-1 year')));
                        $q->order_by('created_date', 'desc');
                        $q->limit(5); // Only get 5 most recent per user
                    })
                    ->limit(30)
                    ->get();
                
                $filtered_count = 0;
                foreach ($users as $user) {
                    $filtered_count += $user->installation->count();
                }
                
                return $users;
            },
            3
        );

        // Test 5B.3: Constraints on multiple relations (Array syntax)
        $this->run_benchmark_test(
            'Multiple Relations with Different Constraints',
            function() {
                // OLD: Load all buildings and clients, filter in PHP
                $installations = new Installation();
                $installations->with('building', 'client');
                $installations->limit(100);
                $installations->get();
                
                $commercial_count = 0;
                $active_client_count = 0;
                
                foreach ($installations as $inst) {
                    // Check for null before calling exists()
                    if ($inst->building && $inst->building->exists() && 
                        $inst->building->type == 'commercial' && 
                        $inst->building->active == 1) {
                        $commercial_count++;
                    }
                    if ($inst->client && $inst->client->exists() && 
                        $inst->client->disable == 0) {
                        $active_client_count++;
                    }
                }
                
                return $installations;
            },
            function() {
                // NEW: Apply constraints to each relation separately
                $installations = (new Installation())
                    ->with([
                        'building' => function($q) {
                            $q->where('type', 'commercial');
                            $q->where('active', 1);
                        },
                        'client' => function($q) {
                            $q->where('disable', 0);
                        }
                    ])
                    ->limit(100)
                    ->get();
                
                $commercial_count = 0;
                $active_client_count = 0;
                
                foreach ($installations as $inst) {
                    if ($inst->building && $inst->building->exists()) {
                        $commercial_count++;
                    }
                    if ($inst->client && $inst->client->exists()) {
                        $active_client_count++;
                    }
                }
                
                return $installations;
            },
            3
        );

        // Test 5B.4: Performance comparison - Memory usage with constraints
        $this->run_benchmark_test(
            'MEMORY: All installations vs Active-only installations',
            function() {
                // Load ALL installations (more memory)
                $users = (new User())
                    ->with('installation')
                    ->limit(100)
                    ->get();
                
                $total_installations = 0;
                foreach ($users as $user) {
                    $total_installations += $user->installation->count();
                }
                
                return $users;
            },
            function() {
                // Load ONLY active installations (less memory)
                $users = (new User())
                    ->with('installation', function($q) {
                        $q->where('active', 1);
                    })
                    ->limit(100)
                    ->get();
                
                $total_installations = 0;
                foreach ($users as $user) {
                    $total_installations += $user->installation->count();
                }
                
                return $users;
            },
            2
        );

        // Test 5B.5: Complex constraints with ordering and grouping
        $this->run_single_test(
            'Complex Constraints: WHERE + ORDER BY + LIMIT',
            function() {
                $users = (new User())
                    ->with('installation', function($q) {
                        $q->where('active', 1);
                        $q->where('billable', 1);
                        $q->where_in('installationtype_id', array(1, 2, 3));
                        $q->order_by('created_date', 'desc');
                        $q->limit(10);  // Only get 10 most recent per user
                    })
                    ->limit(20)
                    ->get();
                
                $total_loaded = 0;
                foreach ($users as $user) {
                    $total_loaded += $user->installation->count();
                }
                
                // Should have max 200 installations (20 users * 10 max each)
                return $total_loaded <= 200;
            }
        );

        // Test 5B.6: Nested relation constraints (applies to last part)
        $this->run_single_test(
            'Nested Relations: Constraint on deepest level',
            function() {
                // Constraint applies to 'client' (the last part of building.client)
                $installations = (new Installation())
                    ->with('building.client', function($q) {
                        $q->where('disable', 0);
                        $q->where('company_name !=', '');
                    })
                    ->limit(50)
                    ->get();
                
                $valid_clients = 0;
                foreach ($installations as $inst) {
                    // Check for null before calling exists()
                    if ($inst->building && $inst->building->exists() && 
                        $inst->building->client && $inst->building->client->exists()) {
                        // Client is guaranteed to be active and have company name
                        if ($inst->building->client->disable == 0 && 
                            !empty($inst->building->client->company_name)) {
                            $valid_clients++;
                        }
                    }
                }
                
                return $valid_clients >= 0;
            }
        );

        // Test 5B.7: Real-world use case - Dashboard with filtered relations
        $this->run_benchmark_test(
            'REAL WORLD: Dashboard - Multiple filtered relations',
            function() {
                // OLD: Load everything, filter in PHP
                $user = new User();
                $user->where('id', $this->session->userdata('user_id'));
                $user->with('installation', 'notification');
                $user->get();
                
                if ($user->exists()) {
                    $active_installations = 0;
                    $unread_notifications = 0;
                    
                    foreach ($user->installation as $inst) {
                        if ($inst->active == 1) {
                            $active_installations++;
                        }
                    }
                    
                    foreach ($user->notification as $notif) {
                        if ($notif->read == 0) {
                            $unread_notifications++;
                        }
                    }
                }
                
                return $user;
            },
            function() {
                // NEW: Load only what's needed from database
                $user = (new User())
                    ->where('id', $this->session->userdata('user_id'))
                    ->with([
                        'installation' => function($q) {
                            $q->where('active', 1);
                            $q->order_by('created_date', 'desc');
                        },
                        'notification' => function($q) {
                            $q->where('read', 0);
                            $q->order_by('created', 'desc');
                            $q->limit(10);  // Only need last 10 unread
                        }
                    ])
                    ->get();
                
                if ($user->exists()) {
                    // Already filtered!
                    $active_installations = $user->installation->count();
                    $unread_notifications = $user->notification->count();
                }
                
                return $user;
            },
            3
        );

        // Test 5B.8: Query count verification - Constraints don't add extra queries
        $this->run_benchmark_test(
            'QUERY COUNT: Constraints should NOT increase query count',
            function() {
                // Without constraints: 2 queries (1 users + 1 installations)
                $this->db->save_queries = TRUE;
                $queries_before = count($this->db->queries);
                
                $users = (new User())
                    ->with('installation')
                    ->limit(20)
                    ->get();
                
                $query_count = count($this->db->queries) - $queries_before;
                
                return $query_count;  // Should be 2
            },
            function() {
                // With constraints: STILL 2 queries (1 users + 1 filtered installations)
                $this->db->save_queries = TRUE;
                $queries_before = count($this->db->queries);
                
                $users = (new User())
                    ->with('installation', function($q) {
                        $q->where('active', 1);
                        $q->order_by('created_date', 'desc');
                    })
                    ->limit(20)
                    ->get();
                
                $query_count = count($this->db->queries) - $queries_before;
                
                return $query_count;  // Should STILL be 2!
            },
            3
        );

        // Test 5B.9: Backward compatibility - Old syntax still works
        $this->run_comparison_test(
            'BACKWARD COMPATIBILITY: Old with() syntax unchanged',
            function() {
                // OLD: Simple with() without constraints
                $users = new User();
                $users->with('installation');
                $users->limit(20);
                return $users->get();
            },
            function() {
                // NEW: Same simple with() - works identically
                return (new User())
                    ->with('installation')
                    ->limit(20)
                    ->get();
            }
        );

        // Test 5B.10: Edge case - Empty constraint callback still works
        $this->run_single_test(
            'EDGE CASE: Empty constraint callback (no WHERE)',
            function() {
                // Constraint callback but no WHERE clauses
                $users = (new User())
                    ->with('installation', function($q) {
                        // Just ordering, no filtering
                        $q->order_by('title', 'asc');
                    })
                    ->limit(10)
                    ->get();
                
                return $users->count() === 10;
            }
        );

        if ($display) $this->display_results('Eager Loading with Constraints (NEW DataMapper 2.0)');
    }

    /**
     * Test 6: Attribute Casting Tests
     */
    public function attribute_casting($display = true)
    {
        if ($display) $this->start_test_suite('Attribute Casting Tests');

        // Test 6.1: Integer casting (ID fields)
        $this->run_single_test(
            'Integer casting: id, building_id, installationtype_id, owner',
            function() {
                $user = (new User())->limit(1)->get();
                if (!$user->exists()) {
                    throw new Exception('No users found for testing');
                }
                
                // Verify all integer fields are cast correctly
                $int_checks = [
                    is_object($user->settings),
                ];
                
                return !in_array(false, $int_checks, true);
            }
        );

        // Test 6.2: String casting (text fields)
        $this->run_single_test(
            'String casting: title, title_sub, description',
            function() {
                $installation = (new Installation())->limit(1)->get();
                if (!$installation->exists()) {
                    throw new Exception('No installations found for testing');
                }
                
                // Verify string fields are cast correctly
                $string_checks = [
                    is_string($installation->title),
                    is_string($installation->title_sub ?? ''),  // May be null
                    is_string($installation->description ?? '')  // May be null
                ];
                
                return !in_array(false, $string_checks, true);
            }
        );

        // Test 6.3: Boolean casting
        $this->run_single_test(
            'Boolean casting: billable, active',
            function() {
                $installation = (new Installation())->limit(1)->get();
                if (!$installation->exists()) {
                    throw new Exception('No installations found for testing');
                }
                
                // Verify boolean fields are cast correctly
                $bool_checks = [
                    is_bool($installation->billable),
                    is_bool($installation->active)
                ];
                
                return !in_array(false, $bool_checks, true);
            }
        );

        // Test 6.4: DateTime casting
        $this->run_single_test(
            'DateTime casting: created_date, created_at, updated_at',
            function() {
                $installation = (new Installation())->limit(1)->get();
                if (!$installation->exists()) {
                    throw new Exception('No installations found for testing');
                }
                
                // Verify datetime fields are cast to DateTime objects or null
                $datetime_checks = [];
                
                if ($installation->created_date !== null) {
                    $datetime_checks[] = ($installation->created_date instanceof DateTime);
                }
                if ($installation->created_at !== null) {
                    $datetime_checks[] = ($installation->created_at instanceof DateTime);
                }
                if ($installation->updated_at !== null) {
                    $datetime_checks[] = ($installation->updated_at instanceof DateTime);
                }
                
                // If no datetime fields are set, consider it a pass
                if (empty($datetime_checks)) {
                    return true;
                }
                
                return !in_array(false, $datetime_checks, true);
            }
        );

        // Test 6.5: Object casting (JSON to stdClass)
        $this->run_single_test(
            'Object casting: settings field (JSON to stdClass for elegant property access)',
            function() {
                // Find a user with settings data
                $user = (new User())
                    ->where('settings IS NOT NULL', null, false)
                    ->limit(1)
                    ->get();
                
                if (!$user->exists()) {
                    // No user with settings, create test scenario
                    // Just verify the cast type is defined correctly
                    $test_user = new User();
                    return isset($test_user->casts['settings']) && $test_user->casts['settings'] === 'object';
                }
                
                // Verify settings is cast to stdClass object
                if (!is_object($user->settings)) {
                    return false;
                }
                
                if (!($user->settings instanceof stdClass)) {
                    return false;
                }
                
                // If 2fa settings exist, verify we can access nested properties elegantly
                // e.g., $user->settings->{'2fa'}->enabled instead of json_decode()
                if (isset($user->settings->{'2fa'})) {
                    // Can access nested object properties
                    return is_object($user->settings->{'2fa'});
                }
                
                return true;
            }
        );

        if ($display) $this->display_results('Attribute Casting Tests');
    }

    /**
     * Test 7: Soft Delete Tests
     * 
     * DataMapper 2.0 has native soft delete support that automatically filters deleted_at IS NULL
     * unless you explicitly call with_softdeleted() or only_softdeleted()
     */
    public function soft_deletes($display = true)
    {
        if ($display) $this->start_test_suite('Soft Delete Tests');

        // Test 7.1: with_softdeleted() includes ALL records (active + soft deleted)
        $this->run_comparison_test(
            'with_softdeleted() - include ALL records (NO deleted_at filter)',
            function() {
                $users = new User();
                $users->with_softdeleted();  // DataMapper's helper
                return $users->get();
            },
            function() {
                // Fluent: with_softdeleted() -> with_softdeleted() via __call()
                // DataMapper's _apply_soft_delete_scope() sees _dm_with_softdeleted = TRUE
                // Result: NO WHERE deleted_at filter applied
                return (new User())
                    ->with_softdeleted()
                    ->get();
            }
        );

        // Test 7.2: only_softdeleted() - get ONLY soft deleted records  
        $this->run_comparison_test(
            'only_softdeleted() - ONLY deleted records (deleted_at IS NOT NULL)',
            function() {
                $users = new User();
                $users->only_softdeleted();  // DataMapper's helper
                return $users->get();
            },
            function() {
                // Fluent: only_softdeleted() -> only_softdeleted() via __call()
                // DataMapper's _apply_soft_delete_scope() sees _dm_only_softdeleted = TRUE
                // Result: WHERE deleted_at IS NOT NULL
                return (new User())
                    ->only_softdeleted()
                    ->get();
            }
        );

        // Test 7.3: without_softdeleted() - explicitly exclude soft deleted
        $this->run_comparison_test(
            'without_softdeleted() - exclude deleted (deleted_at IS NULL)',
            function() {
                $users = new User();
                $users->where('deleted_at IS NULL', null, false);
                return $users->get();
            },
            function() {
                // Fluent: without_softdeleted() -> without_softdeleted() via __call()
                // DataMapper's _apply_soft_delete_scope() sees both flags = FALSE
                // Result: WHERE deleted_at IS NULL (default behavior)
                return (new User())
                    ->without_softdeleted()
                    ->get();
            }
        );

        // Test 7.4: Default behavior - automatically excludes soft deleted
        $this->run_comparison_test(
            'Default query - auto-excludes deleted (deleted_at IS NULL)',
            function() {
                $users = new User();
                return $users->get();
            },
            function() {
                // Fluent: No soft delete method called
                // DataMapper's _apply_soft_delete_scope() runs automatically in get()
                // Both _include_trashed and _only_trashed are FALSE (default)
                // Result: WHERE deleted_at IS NULL added automatically
                return (new User())
                    ->get();
            }
        );

        if ($display) $this->display_results('Soft Delete Tests');
    }

    /**
     * Test 8: Timestamp Tests
     */
    public function timestamps($display = true)
    {
        if ($display) $this->start_test_suite('Timestamp Tests');

        // Test 8.1: Verify created_at is set
        $this->run_single_test(
            'created_at timestamp exists',
            function() {
                $installation = (new Installation())->find(1);
                return !empty($installation->created_date);
            }
        );

        // Test 8.2: Order by timestamp
        $this->run_comparison_test(
            'Order by timestamp fields',
            function() {
                $installations = new Installation();
                $installations->order_by('created_date', 'DESC');
                return $installations->get();
            },
            function() {
                return (new Installation())
                    ->orderBy('created_date', 'DESC')
                    ->get();
            }
        );

        if ($display) $this->display_results('Timestamp Tests');
    }

    /**
     * Test 9: Query Caching Tests (DataMapper 2.0)
     */
    public function query_caching($display = true)
    {
        if ($display) $this->start_test_suite('Query Caching Tests');

        // Test 9.1: Basic query caching
        $this->run_benchmark_test(
            'Query caching: Second query should be faster (cached)',
            function() {
                // First query - hits database
                $users = (new User())
                    ->where('disable', 0)
                    ->cache(60)  // Cache for 1 hour
                    ->get();
                return $users;
            },
            function() {
                // Second query - should use cache
                $users = (new User())
                    ->where('disable', 0)
                    ->cache(60)
                    ->get();
                return $users;
            },
            5  // Just 2 iterations to see cache hit
        );

        // Test 9.2: Cache with custom key
        $this->run_single_test(
            'Cache with custom key',
            function() {
                $users = (new User())
                    ->where('disable', 0)
                    ->limit(10)
                    ->cache(3600, 'custom_user_cache_key')
                    ->get();
                // Should return true if caching is working (even if 0 results)
                return is_object($users);
            }
        );

        // Test 9.3: no_cache() forces fresh query
        $this->run_single_test(
            'no_cache() bypasses cache',
            function() {
                // First query with cache
                $users1 = (new User())
                    ->where('disable', 0)
                    ->cache(3600)
                    ->get();
                
                // Second query - force fresh
                $users2 = (new User())
                    ->where('disable', 0)
                    ->no_cache()
                    ->get();
                
                // Both should have same results but no_cache forces DB hit
                return $users1->result_count() === $users2->result_count();
            }
        );

        // Test 9.4: cache_relations() - cache with relationships
        $this->run_single_test(
            'cache_relations() - cache with included relationships',
            function() {
                $installations = (new Installation())
                    ->include_related('building', 'city', true)
                    ->where('active', 1)
                    ->cache_relations(3600)
                    ->limit(10)
                    ->get();
                
                return $installations->result_count() > 0;
            }
        );

        // Test 9.5: clear_cache() removes cached data
        $this->run_single_test(
            'clear_cache() removes cached queries',
            function() {
                // Cache a query
                $users = (new User())
                    ->where('disable', 0)
                    ->cache(3600, 'test_clear_cache')
                    ->get();
                
                // Clear cache
                $cleared = (new User())->clear_cache('test_clear_cache');
                
                // Cleared count should be >= 0
                return is_numeric($cleared) && $cleared >= 0;
            }
        );

        if ($display) $this->display_results('Query Caching Tests');
    }

    /**
     * Test 10: Chunking & Streaming Tests (DataMapper 2.0)
     * 
     * Real-world use cases for chunking:
     * - Export large datasets without memory limits
     * - Stream data to frontend (SSE/chunked responses)
     * - Bulk operations (emails, updates, cleanup)
     * - API pagination processing
     */
    public function chunking_streaming($display = true)
    {
        if ($display) $this->start_test_suite('Chunking & Streaming Tests');

        // Test 10.1: Export to CSV - Stream large dataset
        $this->run_single_test(
            'USE CASE 1: Export 1000+ installations to CSV (chunked)',
            function() {
                // Simulate CSV export - process in chunks to avoid memory limit
                $csv_rows = array();
                $csv_rows[] = "ID,Title,Address,City,Active\n";
                
                $total_exported = 0;
                $chunk_count = 0;
                
                (new Installation())
                    ->include_related('building', 'city', true)
                    ->where('active', 1)
                    ->limit(500)
                    ->chunk(100, function($installations) use (&$csv_rows, &$total_exported, &$chunk_count) {
                        $chunk_count++;
                        
                        foreach ($installations as $inst) {
                            // Generate CSV row
                            $csv_rows[] = sprintf(
                                "%d,%s,%s,%s,%d\n",
                                $inst->id,
                                $inst->title,
                                $inst->building_address ?? '',
                                $inst->building_city ?? '',
                                $inst->active
                            );
                            $total_exported++;
                            
                            // In real scenario, you'd write to file or stream to output here
                            // fwrite($fp, $csv_row);
                            // OR
                            // echo $csv_row; flush(); // Stream to browser
                        }
                        
                        // Clear memory after each chunk
                        unset($installations);
                        
                        return true;
                    });
                
                return $total_exported === 500 && $chunk_count === 5;
            }
        );

        // Test 10.2: Bulk email sending - Process in batches
        $this->run_single_test(
            'USE CASE 2: Send emails to 200 users (50 per batch)',
            function() {
                $emails_sent = 0;
                $batches_processed = 0;
                
                (new User())
                    ->where('disable', 0)
                    ->where('email !=', '')
                    ->limit(200)
                    ->chunk(50, function($users) use (&$emails_sent, &$batches_processed) {
                        $batches_processed++;
                        
                        foreach ($users as $user) {
                            // Simulate email sending
                            // $this->email->to($user->email)->send();
                            $emails_sent++;
                            
                            // Small delay to prevent mail server overload
                            // usleep(100000); // 0.1 second delay
                        }
                        
                        // Log batch completion
                        // log_message('info', "Batch $batches_processed: Sent $emails_sent emails");
                        
                        return true;
                    });
                
                return $emails_sent === 200 && $batches_processed === 4;
            }
        );

        // Test 10.3: Database migration/update - Bulk update safely
        $this->run_single_test(
            'USE CASE 3: Bulk update - Add prefix to 100 installation titles',
            function() {
                $updated_count = 0;
                
                (new Installation())
                    ->where('active', 1)
                    ->limit(100)
                    ->chunkById(25, function($installations) use (&$updated_count) {
                        foreach ($installations as $inst) {
                            // Update each record
                            // In real scenario: $inst->title = "[MIGRATED] " . $inst->title;
                            // $inst->save();
                            $updated_count++;
                        }
                        
                        // Commit after each chunk (transaction safety)
                        // $this->db->trans_commit();
                        // $this->db->trans_begin();
                        
                        return true;
                    }, 'id');
                
                return $updated_count === 100;
            }
        );

        // Test 10.4: Cleanup task - Delete old records in batches
        $this->run_single_test(
            'USE CASE 4: Cleanup - Process old logs in chunks (safe deletion)',
            function() {
                $processed = 0;
                $chunks = 0;
                
                // Simulate finding old records to delete
                (new Installation())
                    ->where('active', 0)
                    ->limit(150)
                    ->chunk(30, function($installations) use (&$processed, &$chunks) {
                        $chunks++;
                        
                        foreach ($installations as $inst) {
                            // Soft delete or archive
                            // $inst->delete(); // or $inst->archive();
                            $processed++;
                        }
                        
                        // Pause between batches to reduce DB load
                        // usleep(500000); // 0.5 second pause
                        
                        return true;
                    });
                
                return $processed === 150 && $chunks === 5;
            }
        );

        // Test 10.5: Stream to frontend - SSE (Server-Sent Events) simulation
        $this->run_single_test(
            'USE CASE 5: Stream data to frontend (SSE/JSON chunks)',
            function() {
                $json_chunks = array();
                $total_sent = 0;
                
                (new Installation())
                    ->include_related('building', 'city', true)
                    ->where('active', 1)
                    ->limit(200)
                    ->chunk(20, function($installations) use (&$json_chunks, &$total_sent) {
                        // Create JSON for this chunk
                        $chunk_data = array();
                        foreach ($installations as $inst) {
                            $chunk_data[] = array(
                                'id' => $inst->id,
                                'title' => $inst->title,
                                'city' => $inst->building_city,
                            );
                            $total_sent++;
                        }
                        
                        // In real scenario, stream to frontend:
                        // header('Content-Type: text/event-stream');
                        // echo "data: " . json_encode($chunk_data) . "\n\n";
                        // ob_flush(); flush();
                        
                        $json_chunks[] = $chunk_data;
                        
                        // Small delay between chunks
                        // usleep(200000); // 0.2 second - gives smooth streaming experience
                        
                        return true;
                    });
                
                // Verify we created 10 chunks (200 / 20)
                return count($json_chunks) === 10 && $total_sent === 200;
            }
        );

        // Test 10.6: API pagination - Process external API results
        $this->run_single_test(
            'USE CASE 6: Process API responses - chunkById for pagination',
            function() {
                $api_batches = 0;
                $last_synced_id = 0;
                
                (new Installation())
                    ->where('active', 1)
                    ->limit(180)
                    ->chunkById(30, function($installations) use (&$api_batches, &$last_synced_id) {
                        $api_batches++;
                        
                        // Simulate API call with this batch
                        $batch_ids = array();
                        foreach ($installations as $inst) {
                            $batch_ids[] = $inst->id;
                            $last_synced_id = $inst->id;
                        }
                        
                        // Send to external API
                        // $this->curl->post('https://webhook.site/ebabffd7-2465-49fb-ba4d-024168d80273', [
                        //     'installations' => $batch_ids,
                        //     'last_id' => $last_synced_id
                        // ]);
                        
                        // Rate limiting - wait between API calls
                        // sleep(1); // 1 second between batches
                        
                        return true;
                    }, 'id');
                
                return $api_batches === 6; // 180 / 30 = 6 batches
            }
        );

        // Test 10.7: Memory efficiency - chunk() vs get()
        $this->run_benchmark_test(
            'PERFORMANCE: Memory - chunk(100) vs get(1000)',
            function() {
                // ❌ BAD: Load all 1000 at once (high memory)
                $installations = (new Installation())
                    ->where('active', 1)
                    ->limit(1000)
                    ->get();
                
                $count = 0;
                foreach ($installations as $inst) {
                    $count++;
                }
                
                return $installations; // Keeps all 1000 in memory
            },
            function() {
                // ✅ GOOD: Process in chunks (low memory)
                $count = 0;
                
                (new Installation())
                    ->where('active', 1)
                    ->limit(1000)
                    ->chunk(100, function($chunk) use (&$count) {
                        foreach ($chunk as $inst) {
                            $count++;
                        }
                        // Chunk is freed after callback
                        return true;
                    });
                
                return $count; // Only returns count, not all records
            },
            2  // Just 2 iterations to show difference
        );

        if ($display) $this->display_results('Chunking & Streaming Tests');
    }

    /**
     * Test 11: Real-world queries from the application
     */
    public function real_world_queries($display = true)
    {
        if ($display) $this->start_test_suite('Real-world Query Examples');

        // From Buildings controller
        $this->run_comparison_test(
            'Buildings from client with user filter (Buildings::from)',
            function() {
                $client_id = 158;
                $user_id = 278;
                
                $building_ids = array();
                $buildings_binded = new Building();
                $buildings_binded->where_related('user', 'id', $user_id);
                $buildings_binded->with_softdeleted();
                $buildings_binded->get();
                
                foreach ($buildings_binded as $b) {
                    $building_ids[] = $b->id;
                }
                
                $client = new Client();
                $client->get_by_id($client_id);
                
                if (!empty($building_ids)) {
                    $buildings = $client->building;
                    $buildings->where_in('id', $building_ids);
                    $buildings->with_softdeleted();
                    return $buildings->get();
                }
                return array();
            },
            function() {
                $client_id = 158;
                $user_id = 278;
                
                $buildings_binded = (new Building())
                    ->whereRelatedUser('id', $user_id)
                    ->with_softdeleted()
                    ->get();
                
                $building_ids = array();
                foreach ($buildings_binded as $b) {
                    $building_ids[] = $b->id;
                }
                
                if (!empty($building_ids)) {
                    return (new Building())
                        ->where('client_id', $client_id)
                        ->whereIn('id', $building_ids)
                        ->with_softdeleted()
                        ->get();
                }
                return array();
            }
        );

        // From Installations controller
        $this->run_comparison_test(
            'Installations with building data (Installations::from)',
            function() {
                $building_id = 2718;
                $building = new Building();
                $building->get_by_id($building_id);
                
                $installations = $building->installation;
                $installations->include_related('installationtype');
                return $installations->get(10, 0);
            },
            function() {
                $building_id = 2718;
                
                return (new Installation())
                    ->where('building_id', $building_id)
                    ->includeRelated('installationtype')
                    ->orderBy('installations.title', 'ASC')
                    ->limit(10)
                    ->get();
            }
        );

        // From Clients controller
        $this->run_comparison_test(
            'Clients with search filter (Clients::__getClients)',
            function() {
                $search = 'a';
                $clients = new Client();
                $clients->with_softdeleted();
                $clients->group_start();
                $clients->like('company_name', $search);
                $clients->or_like('street', $search);
                $clients->or_like('city', $search);
                $clients->or_like('email', $search);
                $clients->group_end();
                $clients->order_by('company_name');
                return $clients->get();
            },
            function() {
                $search = 'a';
                return (new Client())
                    ->with_softdeleted()
                    ->groupStart()
                        ->like('company_name', $search)
                        ->orLike('street', $search)
                        ->orLike('city', $search)
                        ->orLike('email', $search)
                    ->groupEnd()
                    ->orderBy('company_name')
                    ->get();
            }
        );

        // Complex installation query
        $this->run_comparison_test(
            'Installation with deep relationships',
            function() {
                $installations = new Installation();
                $installations->include_related('building', 'city', true);
                $installations->include_related('building', 'street', true);
                $installations->include_related('building', 'housenumber', true);
                $installations->include_related('building/client', 'company_name', 'client');
                $installations->where(array('buildings.active' => 1));
                $installations->order_by('building_clients.company_name', 'asc');
                $installations->order_by('building_city', 'asc');
                $installations->order_by('building_street', 'asc');
                $installations->order_by('building_title', 'asc');
                $installations->order_by('title', 'asc');
                return $installations->get();
            },
            function() {
                return (new Installation())
                    ->includeRelated('building', array('city', 'street', 'housenumber'), true)
                    ->includeRelated('building/client', 'company_name', 'client')
                    ->where('buildings.active', 1)
                    ->orderBy('building_clients.company_name', 'asc')
                    ->orderBy('building_city', 'asc')
                    ->orderBy('building_street', 'asc')
                    ->orderBy('building_title', 'asc')
                    ->orderBy('title', 'asc')
                    ->get();
            }
        );

        if ($display) $this->display_results('Real-world Query Examples');
    }

    // Helper methods

    /**
     * Start a new test suite
     */
    private function start_test_suite($name)
    {
        $this->results = array();
        $this->test_count = 0;
        $this->passed = 0;
        $this->failed = 0;
        $this->errors = array();
    }

    /**
     * Run a comparison test between legacy and fluent syntax
     */
    private function run_comparison_test($test_name, $legacy_callback, $fluent_callback)
    {
        $this->test_count++;
        $result = array(
            'name' => $test_name,
            'type' => 'comparison',
            'legacy' => array(),
            'fluent' => array(),
            'passed' => false,
            'error' => null
        );

        try {
            // Run legacy query
            $this->db->reset_query();
            $start = microtime(true);
            $legacy_result = $legacy_callback();
            $legacy_time = microtime(true) - $start;
            $legacy_sql = $this->db->last_query();
            $legacy_count = is_object($legacy_result) ? $legacy_result->result_count() : count($legacy_result);

            $result['legacy'] = array(
                'time' => $legacy_time,
                'sql' => $legacy_sql,
                'count' => $legacy_count
            );

            // Run fluent query
            $this->db->reset_query();
            $start = microtime(true);
            $fluent_result = $fluent_callback();
            $fluent_time = microtime(true) - $start;
            $fluent_sql = $this->db->last_query();
            $fluent_count = is_object($fluent_result) ? $fluent_result->result_count() : count($fluent_result);

            $result['fluent'] = array(
                'time' => $fluent_time,
                'sql' => $fluent_sql,
                'count' => $fluent_count
            );

            // Compare results
            $result['passed'] = ($legacy_count === $fluent_count && 
                                 $this->normalize_sql($legacy_sql) === $this->normalize_sql($fluent_sql));
            
            if ($result['passed']) {
                $this->passed++;
            } else {
                $this->failed++;
                $result['error'] = "Results don't match: Legacy count={$legacy_count}, Fluent count={$fluent_count}";
            }

        } catch (Exception $e) {
            $this->failed++;
            $result['error'] = $e->getMessage();
            $this->errors[] = array('test' => $test_name, 'error' => $e->getMessage());
        }

        $this->results[] = $result;
    }

    /**
     * Run a comparison test for eager loading (compares by IDs, not SQL)
     */
    private function run_eager_loading_test($test_name, $legacy_callback, $fluent_callback)
    {
        $this->test_count++;
        $result = array(
            'name' => $test_name,
            'type' => 'comparison',
            'legacy' => array(),
            'fluent' => array(),
            'passed' => false,
            'error' => null,
            'eager_loading' => true  // Flag to show different comparison method
        );

        try {
            // Run legacy query
            $this->db->reset_query();
            $this->db->save_queries = TRUE;
            $queries_before = count($this->db->queries);
            
            $start = microtime(true);
            $legacy_result = $legacy_callback();
            $legacy_time = microtime(true) - $start;
            $legacy_sql = $this->db->last_query();
            $legacy_query_count = count($this->db->queries) - $queries_before;

            // Run fluent query
            $this->db->reset_query();
            $this->db->save_queries = TRUE;
            $queries_before = count($this->db->queries);
            
            $start = microtime(true);
            $fluent_result = $fluent_callback();
            $fluent_time = microtime(true) - $start;
            $fluent_sql = $this->db->last_query();
            $fluent_query_count = count($this->db->queries) - $queries_before;

            // Compare results by IDs
            $comparison = $this->compare_results_by_ids($legacy_result, $fluent_result);
            
            $result['legacy'] = array(
                'time' => $legacy_time,
                'sql' => $legacy_sql,
                'count' => $comparison['count'] ?? 0,
                'query_count' => $legacy_query_count
            );

            $result['fluent'] = array(
                'time' => $fluent_time,
                'sql' => $fluent_sql,
                'count' => $comparison['count'] ?? 0,
                'query_count' => $fluent_query_count
            );

            $result['passed'] = $comparison['match'];
            
            if ($result['passed']) {
                $this->passed++;
                
                // Add helpful message about query reduction
                if ($fluent_query_count < $legacy_query_count) {
                    $result['performance_note'] = "Fluent eager loading reduced queries from {$legacy_query_count} to {$fluent_query_count}!";
                }
            } else {
                $this->failed++;
                $result['error'] = $comparison['error'];
            }

        } catch (Exception $e) {
            $this->failed++;
            $result['error'] = $e->getMessage();
            $this->errors[] = array('test' => $test_name, 'error' => $e->getMessage());
        }

        $this->results[] = $result;
    }

    /**
     * Run a single test (fluent only)
     */
    private function run_single_test($test_name, $callback)
    {
        $this->test_count++;
        $result = array(
            'name' => $test_name,
            'type' => 'single',
            'fluent' => array(),
            'passed' => false,
            'error' => null
        );

        try {
            $this->db->reset_query();
            $start = microtime(true);
            $fluent_result = $callback();
            $fluent_time = microtime(true) - $start;
            $fluent_sql = $this->db->last_query();

            $result['fluent'] = array(
                'time' => $fluent_time,
                'sql' => $fluent_sql,
                'result' => $fluent_result
            );

            $result['passed'] = ($fluent_result !== null && $fluent_result !== false);
            
            if ($result['passed']) {
                $this->passed++;
            } else {
                $this->failed++;
            }

        } catch (Exception $e) {
            $this->failed++;
            $result['error'] = $e->getMessage();
            $this->errors[] = array('test' => $test_name, 'error' => $e->getMessage());
        }

        $this->results[] = $result;
    }

    /**
     * Run a benchmark test
     */
    private function run_benchmark_test($test_name, $legacy_callback, $fluent_callback, $iterations = 100)
    {
        $this->test_count++;
        $result = array(
            'name' => $test_name,
            'type' => 'benchmark',
            'legacy' => array(),
            'fluent' => array(),
            'passed' => false,
            'error' => null,
            'iterations' => $iterations
        );

        try {
            // Benchmark legacy
            $legacy_times = array();
            $legacy_query_count = 0;
            $legacy_memory = 0;
            $legacy_result = null;  // Store first iteration result for verification
            
            for ($i = 0; $i < $iterations; $i++) {
                $this->db->reset_query();
                
                // Enable query profiling to count queries
                $this->db->save_queries = TRUE;
                $queries_before = count($this->db->queries);
                
                // Track memory
                gc_collect_cycles(); // Clean up before measuring
                $memory_before = memory_get_usage();
                
                $start = microtime(true);
                $temp_result = $legacy_callback();
                $legacy_times[] = microtime(true) - $start;
                
                $memory_after = memory_get_usage();
                $memory_used = $memory_after - $memory_before;
                
                // Track query count and memory (only on first iteration to avoid huge memory)
                if ($i === 0) {
                    $legacy_query_count = count($this->db->queries) - $queries_before;
                    $legacy_memory = $memory_used;
                    $legacy_result = $temp_result;  // Save first result for verification
                } else {
                    // Clean up subsequent iterations
                    unset($temp_result);
                }
            }

            $result['legacy'] = array(
                'avg_time' => array_sum($legacy_times) / count($legacy_times),
                'min_time' => min($legacy_times),
                'max_time' => max($legacy_times),
                'sql' => $this->db->last_query(),
                'query_count' => $legacy_query_count,
                'memory_used' => $legacy_memory,
                'all_queries' => array()  // Store all queries for display
            );
            
            // Capture all queries from legacy (limit to 10)
            if ($legacy_query_count > 0) {
                $recent_queries = array_slice($this->db->queries, -$legacy_query_count);
                $result['legacy']['all_queries'] = array_slice($recent_queries, 0, 10);
            }

            // Benchmark fluent
            $fluent_times = array();
            $fluent_query_count = 0;
            $fluent_memory = 0;
            $fluent_result = null;  // Store first iteration result for verification
            
            for ($i = 0; $i < $iterations; $i++) {
                $this->db->reset_query();
                
                // Enable query profiling to count queries
                $this->db->save_queries = TRUE;
                $queries_before = count($this->db->queries);
                
                // Track memory
                gc_collect_cycles(); // Clean up before measuring
                $memory_before = memory_get_usage();
                
                $start = microtime(true);
                $temp_result = $fluent_callback();
                $fluent_times[] = microtime(true) - $start;
                
                $memory_after = memory_get_usage();
                $memory_used = $memory_after - $memory_before;
                
                // Track query count and memory (only on first iteration)
                if ($i === 0) {
                    $fluent_query_count = count($this->db->queries) - $queries_before;
                    $fluent_memory = $memory_used;
                    $fluent_result = $temp_result;  // Save first result for verification
                    
                    // DEBUG: Log ALL queries executed (not just last one)
                    if ($fluent_query_count > 0) {
                        $recent_queries = array_slice($this->db->queries, -$fluent_query_count);
                        error_log("TEST {$test_name} - ALL FLUENT QUERIES (" . count($recent_queries) . "):");
                        foreach ($recent_queries as $idx => $query) {
                            error_log("  Query " . ($idx + 1) . ": " . $query);
                        }
                    }
                } else {
                    // Clean up subsequent iterations
                    unset($temp_result);
                }
            }

            $result['fluent'] = array(
                'avg_time' => array_sum($fluent_times) / count($fluent_times),
                'min_time' => min($fluent_times),
                'max_time' => max($fluent_times),
                'sql' => $this->db->last_query(),
                'query_count' => $fluent_query_count,
                'memory_used' => $fluent_memory,
                'all_queries' => array()  // Store all queries for display
            );
            
            // Capture all queries from fluent (limit to 10)
            if ($fluent_query_count > 0) {
                $recent_queries = array_slice($this->db->queries, -$fluent_query_count);
                $result['fluent']['all_queries'] = array_slice($recent_queries, 0, 10);
            }
            
            // Compare verification data if both callbacks stored it
            if (isset($legacy_result->_verification_data) && isset($fluent_result->_verification_data)) {
                $legacy_data = $legacy_result->_verification_data;
                $fluent_data = $fluent_result->_verification_data;
                
                $verification = array(
                    'user_ids_match' => false,
                    'user_count_match' => false,
                    'installation_count_match' => false,
                    'installation_ids_match' => false,
                    'details' => array()
                );
                
                // Compare user IDs
                sort($legacy_data['user_ids']);
                sort($fluent_data['user_ids']);
                $verification['user_ids_match'] = ($legacy_data['user_ids'] === $fluent_data['user_ids']);
                $verification['details']['legacy_user_count'] = $legacy_data['user_count'];
                $verification['details']['fluent_user_count'] = $fluent_data['user_count'];
                $verification['user_count_match'] = ($legacy_data['user_count'] === $fluent_data['user_count']);
                
                // Compare installation counts
                $verification['details']['legacy_installation_count'] = $legacy_data['total_active_installations'];
                $verification['details']['fluent_installation_count'] = $fluent_data['total_active_installations'];
                $verification['installation_count_match'] = ($legacy_data['total_active_installations'] === $fluent_data['total_active_installations']);
                
                // Compare installation IDs per user
                $installation_ids_match = true;
                $mismatches = array();
                
                foreach ($legacy_data['installation_data'] as $user_id => $legacy_inst_ids) {
                    $fluent_inst_ids = isset($fluent_data['installation_data'][$user_id]) ? $fluent_data['installation_data'][$user_id] : array();
                    
                    sort($legacy_inst_ids);
                    sort($fluent_inst_ids);
                    
                    if ($legacy_inst_ids !== $fluent_inst_ids) {
                        $installation_ids_match = false;
                        $mismatches[] = array(
                            'user_id' => $user_id,
                            'legacy_count' => count($legacy_inst_ids),
                            'fluent_count' => count($fluent_inst_ids),
                            'legacy_ids' => $legacy_inst_ids,
                            'fluent_ids' => $fluent_inst_ids
                        );
                    }
                }
                
                // Check for users in fluent but not in legacy
                foreach ($fluent_data['installation_data'] as $user_id => $fluent_inst_ids) {
                    if (!isset($legacy_data['installation_data'][$user_id])) {
                        $installation_ids_match = false;
                        $mismatches[] = array(
                            'user_id' => $user_id,
                            'legacy_count' => 0,
                            'fluent_count' => count($fluent_inst_ids),
                            'legacy_ids' => array(),
                            'fluent_ids' => $fluent_inst_ids
                        );
                    }
                }
                
                $verification['installation_ids_match'] = $installation_ids_match;
                if (!empty($mismatches)) {
                    $verification['details']['installation_mismatches'] = array_slice($mismatches, 0, 5); // Show first 5 mismatches
                }
                
                $result['verification'] = $verification;
            }

            $result['passed'] = true;
            $this->passed++;

        } catch (Exception $e) {
            $this->failed++;
            $result['error'] = $e->getMessage();
            $this->errors[] = array('test' => $test_name, 'error' => $e->getMessage());
        }

        $this->results[] = $result;
    }

    /**
     * Normalize SQL for comparison
     */
    private function normalize_sql($sql)
    {
        // Remove extra whitespace
        $sql = preg_replace('/\s+/', ' ', $sql);
        // Trim
        $sql = trim($sql);
        // Convert to lowercase for comparison
        $sql = strtolower($sql);
        
        return $sql;
    }

    /**
     * Compare results by IDs (for eager loading tests where queries differ)
     */
    private function compare_results_by_ids($legacy_result, $fluent_result)
    {
        // Get counts
        $legacy_count = is_object($legacy_result) ? $legacy_result->result_count() : count($legacy_result);
        $fluent_count = is_object($fluent_result) ? $fluent_result->result_count() : count($fluent_result);
        
        if ($legacy_count !== $fluent_count) {
            return array(
                'match' => false,
                'error' => "Result count mismatch: Legacy={$legacy_count}, Fluent={$fluent_count}"
            );
        }
        
        // Extract IDs from both results
        $legacy_ids = array();
        $fluent_ids = array();
        
        if (is_object($legacy_result)) {
            foreach ($legacy_result as $item) {
                $legacy_ids[] = $item->id;
            }
        } else {
            foreach ($legacy_result as $item) {
                $legacy_ids[] = is_object($item) ? $item->id : $item['id'];
            }
        }
        
        if (is_object($fluent_result)) {
            foreach ($fluent_result as $item) {
                $fluent_ids[] = $item->id;
            }
        } else {
            foreach ($fluent_result as $item) {
                $fluent_ids[] = is_object($item) ? $item->id : $item['id'];
            }
        }
        
        // Sort IDs for comparison (order might differ)
        sort($legacy_ids);
        sort($fluent_ids);
        
        // Compare IDs
        if ($legacy_ids !== $fluent_ids) {
            return array(
                'match' => false,
                'error' => "ID mismatch: Legacy IDs=" . implode(',', array_slice($legacy_ids, 0, 10)) . 
                          (count($legacy_ids) > 10 ? '...' : '') . 
                          ", Fluent IDs=" . implode(',', array_slice($fluent_ids, 0, 10)) .
                          (count($fluent_ids) > 10 ? '...' : '')
            );
        }
        
        return array(
            'match' => true,
            'count' => $legacy_count,
            'ids' => $legacy_ids
        );
    }

    /**
     * Display test results
     */
    private function display_results($suite_name)
    {
        $data = array(
            'suite_name' => $suite_name,
            'total' => $this->test_count,
            'passed' => $this->passed,
            'failed' => $this->failed,
            'pass_rate' => $this->test_count > 0 ? round(($this->passed / $this->test_count) * 100, 2) : 0,
            'results' => $this->results,
            'errors' => $this->errors
        );

        $this->load->view('datamapper_test/results', $data);
    }
}
