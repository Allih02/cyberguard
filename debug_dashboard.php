<?php
/**
 * =====================================================
 * CyberGuard Debug Dashboard
 * File: debug_dashboard.php
 * 
 * Debug version to troubleshoot data display issues
 * =====================================================
 */

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
    <title>CyberGuard Debug Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8fafc;
        }
        
        .debug-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .success { background: #f0fff4; color: #22543d; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        .error { background: #fed7d7; color: #742a2a; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        .info { background: #e6fffa; color: #234e52; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        
        pre {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 4px solid #667eea;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        table th, table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        table th {
            background: #f7fafc;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <h1>🐛 CyberGuard Debug Dashboard</h1>
    <p><strong>Debug Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

    <!-- Database Connection Test -->
    <div class="debug-section">
        <h2>1. Database Connection Test</h2>
        <?php
        try {
            $db = getDatabase();
            echo '<div class="success">✅ Database connection successful</div>';
            
            $config = $db->getConfig();
            echo '<div class="info">Database: ' . $config['dbname'] . ' on ' . $config['host'] . '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Database connection failed: ' . $e->getMessage() . '</div>';
            echo '<div class="error">Check config/database.php settings</div>';
            exit;
        }
        ?>
    </div>

    <!-- Tables Check -->
    <div class="debug-section">
        <h2>2. Database Tables Check</h2>
        <?php
        try {
            $tables = $db->fetchAll("SHOW TABLES");
            echo '<div class="success">✅ Found ' . count($tables) . ' tables</div>';
            
            echo '<div class="info">Tables in database:<br>';
            foreach ($tables as $table) {
                $table_name = array_values($table)[0];
                echo '• ' . $table_name . '<br>';
            }
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error checking tables: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <!-- Data Count Check -->
    <div class="debug-section">
        <h2>3. Data Count Check</h2>
        <?php
        try {
            $tables_to_check = [
                'crime_categories' => 'Crime Categories',
                'locations' => 'Locations', 
                'users' => 'Users',
                'incident_reports' => 'Incident Reports'
            ];
            
            foreach ($tables_to_check as $table => $label) {
                try {
                    $count = $db->fetch("SELECT COUNT(*) as count FROM $table");
                    if ($count['count'] > 0) {
                        echo '<div class="success">✅ ' . $label . ': ' . $count['count'] . ' records</div>';
                    } else {
                        echo '<div class="error">⚠️ ' . $label . ': No records found</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="error">❌ ' . $label . ': Table does not exist or error - ' . $e->getMessage() . '</div>';
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error checking data counts: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <!-- Sample Data Display -->
    <div class="debug-section">
        <h2>4. Sample Data Display</h2>
        <?php
        try {
            // Check crime categories
            echo '<h3>Crime Categories:</h3>';
            $categories = $db->fetchAll("SELECT * FROM crime_categories LIMIT 5");
            if (!empty($categories)) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Icon</th><th>Color</th></tr>';
                foreach ($categories as $cat) {
                    echo '<tr>';
                    echo '<td>' . $cat['id'] . '</td>';
                    echo '<td>' . $cat['category_name'] . '</td>';
                    echo '<td>' . $cat['category_icon'] . '</td>';
                    echo '<td>' . $cat['category_color'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="error">No crime categories found</div>';
            }
            
            // Check locations
            echo '<h3>Locations:</h3>';
            $locations = $db->fetchAll("SELECT * FROM locations LIMIT 5");
            if (!empty($locations)) {
                echo '<table>';
                echo '<tr><th>ID</th><th>City</th><th>Region</th><th>Latitude</th><th>Longitude</th></tr>';
                foreach ($locations as $loc) {
                    echo '<tr>';
                    echo '<td>' . $loc['id'] . '</td>';
                    echo '<td>' . $loc['city'] . '</td>';
                    echo '<td>' . $loc['region'] . '</td>';
                    echo '<td>' . $loc['latitude'] . '</td>';
                    echo '<td>' . $loc['longitude'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="error">No locations found</div>';
            }
            
            // Check incident reports
            echo '<h3>Incident Reports:</h3>';
            $reports = $db->fetchAll("SELECT * FROM incident_reports LIMIT 5");
            if (!empty($reports)) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Report #</th><th>Reporter</th><th>Status</th><th>Priority</th><th>Created</th></tr>';
                foreach ($reports as $report) {
                    echo '<tr>';
                    echo '<td>' . $report['id'] . '</td>';
                    echo '<td>' . $report['report_number'] . '</td>';
                    echo '<td>' . $report['reporter_name'] . '</td>';
                    echo '<td>' . $report['status'] . '</td>';
                    echo '<td>' . $report['priority'] . '</td>';
                    echo '<td>' . $report['created_at'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="error">No incident reports found</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error displaying sample data: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <!-- Dashboard Query Test -->
    <div class="debug-section">
        <h2>5. Dashboard Query Test</h2>
        <?php
        try {
            echo '<h3>Testing Dashboard Statistics Query:</h3>';
            
            $sql = "
                SELECT 
                    COUNT(*) as total_reports,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
                    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_reports,
                    COALESCE(SUM(estimated_loss), 0) as total_estimated_loss,
                    COALESCE(AVG(estimated_loss), 0) as avg_estimated_loss
                FROM incident_reports
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";
            
            $stats = $db->fetch($sql);
            
            if ($stats) {
                echo '<div class="success">✅ Statistics query successful</div>';
                echo '<pre>' . print_r($stats, true) . '</pre>';
            } else {
                echo '<div class="error">❌ Statistics query returned no results</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Statistics query failed: ' . $e->getMessage() . '</div>';
            echo '<div class="error">SQL: ' . $sql . '</div>';
        }
        
        try {
            echo '<h3>Testing Recent Reports Query:</h3>';
            
            $sql = "
                SELECT 
                    ir.id,
                    ir.report_number,
                    ir.reporter_name,
                    ir.reporter_email,
                    cc.category_name,
                    cc.category_icon,
                    cc.category_color,
                    ir.incident_description,
                    ir.status,
                    ir.priority,
                    ir.estimated_loss,
                    ir.currency,
                    l.latitude,
                    l.longitude,
                    l.city,
                    l.region,
                    ir.created_at,
                    ir.updated_at
                FROM incident_reports ir
                JOIN crime_categories cc ON ir.crime_category_id = cc.id
                JOIN locations l ON ir.location_id = l.id
                ORDER BY ir.created_at DESC
                LIMIT 5
            ";
            
            $recent_reports = $db->fetchAll($sql);
            
            if (!empty($recent_reports)) {
                echo '<div class="success">✅ Recent reports query successful - Found ' . count($recent_reports) . ' reports</div>';
                echo '<pre>' . print_r($recent_reports[0], true) . '</pre>'; // Show first report structure
            } else {
                echo '<div class="error">❌ Recent reports query returned no results</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Recent reports query failed: ' . $e->getMessage() . '</div>';
            echo '<div class="error">SQL: ' . $sql . '</div>';
        }
        ?>
    </div>

    <!-- Dashboard Class Test -->
    <div class="debug-section">
        <h2>6. Dashboard Class Test</h2>
        <?php
        try {
            // Test the DashboardData class
            class TestDashboardData {
                private $db;
                
                public function __construct() {
                    $this->db = getDatabase();
                }
                
                public function getDashboardStats() {
                    $sql = "
                        SELECT 
                            COUNT(*) as total_reports,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
                            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports
                        FROM incident_reports
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    ";
                    return $this->db->fetch($sql);
                }
                
                public function getRecentReports($limit = 5) {
                    $sql = "
                        SELECT 
                            ir.id,
                            ir.report_number,
                            ir.reporter_name,
                            cc.category_name,
                            cc.category_icon,
                            ir.status,
                            ir.priority,
                            l.city,
                            l.region,
                            ir.created_at
                        FROM incident_reports ir
                        JOIN crime_categories cc ON ir.crime_category_id = cc.id
                        JOIN locations l ON ir.location_id = l.id
                        ORDER BY ir.created_at DESC
                        LIMIT ?
                    ";
                    return $this->db->fetchAll($sql, [$limit]);
                }
            }
            
            $testDashboard = new TestDashboardData();
            
            // Test statistics
            $stats = $testDashboard->getDashboardStats();
            if ($stats) {
                echo '<div class="success">✅ Dashboard statistics working</div>';
                echo '<div class="info">Total Reports: ' . ($stats['total_reports'] ?? 0) . '</div>';
                echo '<div class="info">Pending Reports: ' . ($stats['pending_reports'] ?? 0) . '</div>';
                echo '<div class="info">Resolved Reports: ' . ($stats['resolved_reports'] ?? 0) . '</div>';
            } else {
                echo '<div class="error">❌ Dashboard statistics failed</div>';
            }
            
            // Test recent reports
            $reports = $testDashboard->getRecentReports(3);
            if (!empty($reports)) {
                echo '<div class="success">✅ Recent reports working - Found ' . count($reports) . ' reports</div>';
                echo '<table>';
                echo '<tr><th>Report #</th><th>Reporter</th><th>Type</th><th>Status</th><th>Location</th></tr>';
                foreach ($reports as $report) {
                    echo '<tr>';
                    echo '<td>' . $report['report_number'] . '</td>';
                    echo '<td>' . $report['reporter_name'] . '</td>';
                    echo '<td>' . $report['category_icon'] . ' ' . $report['category_name'] . '</td>';
                    echo '<td>' . $report['status'] . '</td>';
                    echo '<td>' . $report['city'] . ', ' . $report['region'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="error">❌ Recent reports returned no data</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Dashboard class test failed: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <!-- Helper Functions Test -->
    <div class="debug-section">
        <h2>7. Helper Functions Test</h2>
        <?php
        try {
            // Test helper functions
            echo '<div class="success">✅ formatTimeAgo: ' . formatTimeAgo('2025-05-29 10:00:00') . '</div>';
            echo '<div class="success">✅ formatCurrency: ' . formatCurrency(1234567.89, 'TZS') . '</div>';
            echo '<div class="success">✅ getStatusBadgeClass: ' . getStatusBadgeClass('pending') . '</div>';
            echo '<div class="success">✅ getPriorityClass: ' . getPriorityClass('high') . '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Helper functions test failed: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <!-- Issue Detection -->
    <div class="debug-section">
        <h2>8. Common Issues Detection</h2>
        <?php
        $issues = [];
        
        // Check if tables exist and have data
        try {
            $report_count = $db->fetch("SELECT COUNT(*) as count FROM incident_reports");
            if ($report_count['count'] == 0) {
                $issues[] = "No incident reports in database - run setup_database.php";
            }
            
            $category_count = $db->fetch("SELECT COUNT(*) as count FROM crime_categories");
            if ($category_count['count'] == 0) {
                $issues[] = "No crime categories in database - run setup_database.php";
            }
            
            $location_count = $db->fetch("SELECT COUNT(*) as count FROM locations");
            if ($location_count['count'] == 0) {
                $issues[] = "No locations in database - run setup_database.php";
            }
            
        } catch (Exception $e) {
            $issues[] = "Database tables missing - run setup_database.php";
        }
        
        // Check file permissions
        if (!is_readable('config/database.php')) {
            $issues[] = "config/database.php is not readable";
        }
        
        if (!is_readable('includes/functions.php')) {
            $issues[] = "includes/functions.php is not readable";
        }
        
        // Display issues or success
        if (empty($issues)) {
            echo '<div class="success">🎉 No issues detected! Dashboard should work properly.</div>';
            echo '<div class="info"><strong>Next steps:</strong><br>';
            echo '1. Go to <a href="cyberguard_dashboard.php">cyberguard_dashboard.php</a><br>';
            echo '2. If data still doesn\'t appear, check browser console for JavaScript errors<br>';
            echo '3. Ensure all files are in the correct location</div>';
        } else {
            echo '<div class="error">❌ <strong>Issues found:</strong></div>';
            foreach ($issues as $issue) {
                echo '<div class="error">• ' . $issue . '</div>';
            }
            
            echo '<div class="info"><strong>Recommended fixes:</strong><br>';
            echo '1. Run <a href="setup_database.php">setup_database.php</a> to create tables and sample data<br>';
            echo '2. Check file permissions<br>';
            echo '3. Verify database connection settings</div>';
        }
        ?>
    </div>

    <!-- Quick Actions -->
    <div class="debug-section">
        <h2>9. Quick Actions</h2>
        <p>Use these links to fix common issues:</p>
        
        <a href="setup_database.php" style="display: inline-block; background: #667eea; color: white; padding: 0.75rem 1.5rem; border-radius: 5px; text-decoration: none; margin: 0.5rem;">
            🗄️ Setup Database
        </a>
        
        <a href="cyberguard_dashboard.php" style="display: inline-block; background: #48bb78; color: white; padding: 0.75rem 1.5rem; border-radius: 5px; text-decoration: none; margin: 0.5rem;">
            📊 Go to Dashboard
        </a>
        
        <a href="test_system.php" style="display: inline-block; background: #ed8936; color: white; padding: 0.75rem 1.5rem; border-radius: 5px; text-decoration: none; margin: 0.5rem;">
            🧪 System Tests
        </a>
        
        <a href="index.html" style="display: inline-block; background: #38b2ac; color: white; padding: 0.75rem 1.5rem; border-radius: 5px; text-decoration: none; margin: 0.5rem;">
            📝 Test Form
        </a>
    </div>

    <div style="text-align: center; margin-top: 2rem; padding: 2rem; background: #f7fafc; border-radius: 10px;">
        <p><strong>Debug Complete</strong></p>
        <p>If you still have issues after running setup_database.php, check:</p>
        <p>1. Database credentials in config/database.php</p>
        <p>2. PHP error logs</p>
        <p>3. Browser console for JavaScript errors</p>
    </div>
</body>
</html>