<?php
/**
 * =====================================================
 * CyberGuard System Test Script
 * File: test_system.php
 * 
 * Test all components of the CyberGuard system
 * Run this file to verify everything is working properly
 * =====================================================
 */

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberGuard System Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8fafc;
            color: #2d3748;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .test-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .test-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #667eea;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        
        .success {
            background: #f0fff4;
            color: #22543d;
            padding: 0.75rem;
            border-radius: 5px;
            border-left: 4px solid #48bb78;
            margin: 0.5rem 0;
        }
        
        .error {
            background: #fed7d7;
            color: #742a2a;
            padding: 0.75rem;
            border-radius: 5px;
            border-left: 4px solid #fc8181;
            margin: 0.5rem 0;
        }
        
        .info {
            background: #e6fffa;
            color: #234e52;
            padding: 0.75rem;
            border-radius: 5px;
            border-left: 4px solid #38b2ac;
            margin: 0.5rem 0;
        }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        code {
            background: #f7fafc;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .stats-table th,
        .stats-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .stats-table th {
            background: #f7fafc;
            font-weight: 600;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin: 0.5rem 0.5rem 0.5rem 0;
            transition: transform 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛡️ CyberGuard System Test</h1>
        <p>Comprehensive system health check and component testing</p>
        <p><strong>Version:</strong> <?php echo getAppVersion(); ?> | <strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="test-grid">
        <!-- Database Connection Test -->
        <div class="test-section">
            <h2 class="test-title">🗄️ Database Connection</h2>
            <?php
            try {
                $healthCheck = checkDatabaseHealth();
                
                if ($healthCheck['status'] === 'healthy') {
                    echo '<div class="success">✅ <strong>Database Connection:</strong> Successful</div>';
                    echo '<div class="info">📊 <strong>Details:</strong><br>';
                    echo 'Database: ' . $healthCheck['details']['database'] . '<br>';
                    echo 'Host: ' . $healthCheck['details']['host'] . '<br>';
                    echo 'Test Time: ' . $healthCheck['details']['timestamp'] . '</div>';
                } else {
                    echo '<div class="error">❌ <strong>Database Connection Failed:</strong> ' . $healthCheck['message'] . '</div>';
                    if (isset($healthCheck['error'])) {
                        echo '<div class="error">🔍 <strong>Error Details:</strong> ' . $healthCheck['error'] . '</div>';
                    }
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ <strong>Database Test Failed:</strong> ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>

        <!-- Database Tables Test -->
        <div class="test-section">
            <h2 class="test-title">📋 Database Tables</h2>
            <?php
            try {
                $initCheck = initializeDatabaseTables();
                
                if ($initCheck['status'] === 'ready') {
                    echo '<div class="success">✅ <strong>Tables Status:</strong> All tables exist and ready</div>';
                    
                    // Get table statistics
                    $db = getDatabase();
                    $stats = $db->getDatabaseStats();
                    
                    if (!isset($stats['error'])) {
                        echo '<div class="info">📊 <strong>Database Statistics:</strong><br>';
                        echo 'Total Tables: ' . $stats['table_count'] . '<br>';
                        echo 'Total Size: ' . $stats['total_size_formatted'] . '<br>';
                        echo 'MySQL Version: ' . $stats['mysql_version'] . '</div>';
                        
                        // Show table details
                        echo '<table class="stats-table">';
                        echo '<tr><th>Table</th><th>Rows</th><th>Size</th></tr>';
                        foreach ($stats['tables'] as $table => $info) {
                            echo '<tr><td>' . $table . '</td><td>' . number_format($info['rows']) . '</td><td>' . $info['size_formatted'] . '</td></tr>';
                        }
                        echo '</table>';
                    }
                } else {
                    echo '<div class="error">❌ <strong>Tables Status:</strong> ' . $initCheck['message'] . '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ <strong>Table Check Failed:</strong> ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>

        <!-- Helper Functions Test -->
        <div class="test-section">
            <h2 class="test-title">🔧 Helper Functions</h2>
            <?php
            $function_tests = [
                'formatCurrency' => function() {
                    return formatCurrency(1234567.89, 'TZS') === 'TSh 1,234,568';
                },
                'formatTimeAgo' => function() {
                    return formatTimeAgo(date('Y-m-d H:i:s', time() - 3600)) !== 'Unknown';
                },
                'isValidEmail' => function() {
                    return isValidEmail('test@example.com') === true && isValidEmail('invalid-email') === false;
                },
                'isValidPhone' => function() {
                    return isValidPhone('+255712345678') === true && isValidPhone('invalid') === false;
                },
                'sanitizeInput' => function() {
                    return sanitizeInput('<script>alert("test")</script>') === '&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;';
                },
                'generateToken' => function() {
                    $token = generateToken(16);
                    return strlen($token) === 16 && ctype_alnum($token);
                },
                'getStatusBadgeClass' => function() {
                    return getStatusBadgeClass('pending') === 'pending';
                },
                'getPriorityClass' => function() {
                    return getPriorityClass('high') === 'high';
                }
            ];

            $passed = 0;
            $total = count($function_tests);

            foreach ($function_tests as $function => $test) {
                try {
                    if ($test()) {
                        echo '<div class="success">✅ <code>' . $function . '()</code> - Working correctly</div>';
                        $passed++;
                    } else {
                        echo '<div class="error">❌ <code>' . $function . '()</code> - Test failed</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="error">❌ <code>' . $function . '()</code> - Error: ' . $e->getMessage() . '</div>';
                }
            }

            echo '<div class="info">📊 <strong>Function Tests:</strong> ' . $passed . '/' . $total . ' passed</div>';
            ?>
        </div>

        <!-- System Statistics Test -->
        <div class="test-section">
            <h2 class="test-title">📊 System Statistics</h2>
            <?php
            try {
                $stats = getSystemStats();
                
                if (!empty($stats)) {
                    echo '<div class="success">✅ <strong>Statistics:</strong> Retrieved successfully</div>';
                    
                    if (isset($stats['reports'])) {
                        echo '<div class="info">📈 <strong>Reports Summary:</strong><br>';
                        echo 'Total Reports: ' . number_format($stats['reports']['total_reports']) . '<br>';
                        echo 'Pending Reports: ' . number_format($stats['reports']['pending_reports']) . '<br>';
                        echo 'Resolved Reports: ' . number_format($stats['reports']['resolved_reports']) . '<br>';
                        echo 'Today\'s Reports: ' . number_format($stats['reports']['today_reports']) . '<br>';
                        echo 'This Week: ' . number_format($stats['reports']['week_reports']) . '</div>';
                    }
                    
                    if (!empty($stats['top_crimes'])) {
                        echo '<div class="info">🔍 <strong>Top Crime Types:</strong><br>';
                        foreach ($stats['top_crimes'] as $crime) {
                            echo '• ' . $crime['category_name'] . ': ' . $crime['count'] . ' reports<br>';
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<div class="info">ℹ️ <strong>Statistics:</strong> No data available (empty database)</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ <strong>Statistics Error:</strong> ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>

        <!-- Activity Logging Test -->
        <div class="test-section">
            <h2 class="test-title">📝 Activity Logging</h2>
            <?php
            try {
                $testLog = logActivity('system_test', 'Testing activity logging functionality');
                
                if ($testLog) {
                    echo '<div class="success">✅ <strong>Activity Logging:</strong> Working correctly</div>';
                    echo '<div class="info">📋 Test log entry created successfully</div>';
                } else {
                    echo '<div class="error">❌ <strong>Activity Logging:</strong> Failed to create log entry</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ <strong>Activity Logging Error:</strong> ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>

        <!-- File System Test -->
        <div class="test-section">
            <h2 class="test-title">📁 File System</h2>
            <?php
            $files_to_check = [
                'config/database.php' => 'Database Configuration',
                'includes/functions.php' => 'Helper Functions',
                'index.html' => 'Public Interface',
                'cyberguard_dashboard.php' => 'Dashboard',
                'submit_incident.php' => 'Form Handler'
            ];

            $files_exist = 0;
            foreach ($files_to_check as $file => $description) {
                if (file_exists($file)) {
                    echo '<div class="success">✅ <strong>' . $description . ':</strong> <code>' . $file . '</code> exists</div>';
                    $files_exist++;
                } else {
                    echo '<div class="error">❌ <strong>' . $description . ':</strong> <code>' . $file . '</code> missing</div>';
                }
            }

            echo '<div class="info">📊 <strong>Files Status:</strong> ' . $files_exist . '/' . count($files_to_check) . ' files found</div>';

            // Check permissions
            $writable_dirs = ['logs', 'uploads', 'cache'];
            foreach ($writable_dirs as $dir) {
                if (is_dir($dir)) {
                    if (is_writable($dir)) {
                        echo '<div class="success">✅ <strong>Directory:</strong> <code>' . $dir . '</code> is writable</div>';
                    } else {
                        echo '<div class="error">❌ <strong>Directory:</strong> <code>' . $dir . '</code> is not writable</div>';
                    }
                } else {
                    echo '<div class="info">ℹ️ <strong>Directory:</strong> <code>' . $dir . '</code> does not exist (optional)</div>';
                }
            }
            ?>
        </div>

        <!-- PHP Environment Test -->
        <div class="test-section">
            <h2 class="test-title">🐘 PHP Environment</h2>
            <?php
            echo '<div class="success">✅ <strong>PHP Version:</strong> ' . PHP_VERSION . '</div>';
            
            $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
            $loaded_extensions = 0;
            
            foreach ($required_extensions as $ext) {
                if (extension_loaded($ext)) {
                    echo '<div class="success">✅ <strong>Extension:</strong> ' . $ext . ' loaded</div>';
                    $loaded_extensions++;
                } else {
                    echo '<div class="error">❌ <strong>Extension:</strong> ' . $ext . ' missing (required)</div>';
                }
            }
            
            echo '<div class="info">📊 <strong>Extensions:</strong> ' . $loaded_extensions . '/' . count($required_extensions) . ' loaded</div>';
            
            // Check PHP settings
            $settings_check = [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit')
            ];
            
            echo '<div class="info">⚙️ <strong>PHP Settings:</strong><br>';
            foreach ($settings_check as $setting => $value) {
                echo $setting . ': ' . $value . '<br>';
            }
            echo '</div>';
            ?>
        </div>
    </div>

    <!-- Test Actions -->
    <div class="test-section">
        <h2 class="test-title">🧪 Test Actions</h2>
        <p>Use these buttons to test specific functionality:</p>
        
        <a href="index.html" class="btn">📝 Test Public Form</a>
        <a href="cyberguard_dashboard.php" class="btn">📊 Test Dashboard</a>
        <a href="cyberguard_dashboard.php?api=health_check" class="btn">🔍 API Health Check</a>
        <a href="?action=test_submission" class="btn">📤 Test Form Submission</a>
        <a href="?action=clear_logs" class="btn">🗑️ Clear Test Logs</a>
        
        <?php
        // Handle test actions
        if (isset($_GET['action'])) {
            echo '<div style="margin-top: 1rem;">';
            
            switch ($_GET['action']) {
                case 'test_submission':
                    echo '<div class="info">🧪 <strong>Form Submission Test:</strong><br>';
                    echo 'You can test form submission by:</div>';
                    echo '<div class="info">1. Go to <a href="index.html">index.html</a><br>';
                    echo '2. Click on the map to select a location<br>';
                    echo '3. Fill out the form with test data<br>';
                    echo '4. Submit the form<br>';
                    echo '5. Check the dashboard for the new report</div>';
                    break;
                    
                case 'clear_logs':
                    try {
                        $db = getDatabase();
                        $db->query("DELETE FROM activity_log WHERE action = 'system_test'");
                        echo '<div class="success">✅ <strong>Test logs cleared successfully</strong></div>';
                    } catch (Exception $e) {
                        echo '<div class="error">❌ <strong>Failed to clear logs:</strong> ' . $e->getMessage() . '</div>';
                    }
                    break;
            }
            
            echo '</div>';
        }
        ?>
    </div>

    <!-- Overall Status -->
    <div class="test-section">
        <h2 class="test-title">🎯 Overall System Status</h2>
        <?php
        $overall_status = 'healthy';
        $status_messages = [];
        
        // Check database
        $db_health = checkDatabaseHealth();
        if ($db_health['status'] !== 'healthy') {
            $overall_status = 'error';
            $status_messages[] = 'Database connection issues';
        }
        
        // Check tables
        $table_status = initializeDatabaseTables();
        if ($table_status['status'] !== 'ready') {
            $overall_status = 'warning';
            $status_messages[] = 'Database tables need setup';
        }
        
        // Check required files
        $required_files = ['config/database.php', 'includes/functions.php', 'index.html', 'cyberguard_dashboard.php', 'submit_incident.php'];
        foreach ($required_files as $file) {
            if (!file_exists($file)) {
                $overall_status = 'error';
                $status_messages[] = 'Missing required file: ' . $file;
            }
        }
        
        // Display overall status
        if ($overall_status === 'healthy') {
            echo '<div class="success">🎉 <strong>System Status: HEALTHY</strong><br>';
            echo 'All components are working correctly. The CyberGuard system is ready for use!</div>';
        } elseif ($overall_status === 'warning') {
            echo '<div class="info">⚠️ <strong>System Status: WARNING</strong><br>';
            echo 'System is mostly functional but has some issues:<br>';
            foreach ($status_messages as $message) {
                echo '• ' . $message . '<br>';
            }
            echo '</div>';
        } else {
            echo '<div class="error">🚨 <strong>System Status: ERROR</strong><br>';
            echo 'System has critical issues that need to be resolved:<br>';
            foreach ($status_messages as $message) {
                echo '• ' . $message . '<br>';
            }
            echo '</div>';
        }
        ?>
        
        <div class="info">
            <strong>Next Steps:</strong><br>
            1. If all tests pass, your system is ready to use<br>
            2. Visit <a href="index.html">index.html</a> to test the public interface<br>
            3. Visit <a href="cyberguard_dashboard.php">cyberguard_dashboard.php</a> to test the admin dashboard<br>
            4. Submit test reports to verify end-to-end functionality<br>
            5. Delete this test file (<code>test_system.php</code>) in production
        </div>
    </div>

    <div style="text-align: center; margin-top: 2rem; padding: 2rem; background: #f7fafc; border-radius: 10px;">
        <p><strong>CyberGuard System Test Complete</strong></p>
        <p>Test run at: <?php echo date('Y-m-d H:i:s'); ?></p>
        <p style="margin-top: 1rem;">
            <a href="?" class="btn">🔄 Run Tests Again</a>
            <a href="index.html" class="btn">🏠 Go to Public Interface</a>
            <a href="cyberguard_dashboard.php" class="btn">📊 Go to Dashboard</a>
        </p>
    </div>
</body>
</html>