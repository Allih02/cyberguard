<?php
/**
 * =====================================================
 * CyberGuard Database Setup Script
 * File: setup_database.php
 * 
 * This script will:
 * 1. Create the database and tables
 * 2. Insert sample data
 * 3. Verify everything is working
 * =====================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include our database configuration
require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberGuard Database Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
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
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin: 0.5rem 0.5rem 0.5rem 0;
        }
        
        pre {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🗄️ CyberGuard Database Setup</h1>
        <p>Initialize database tables and sample data</p>
    </div>

    <?php
    $setup_steps = [];
    $errors = [];

    try {
        // Step 1: Test database connection
        echo '<div class="section"><h2>Step 1: Testing Database Connection</h2>';
        
        $db = getDatabase();
        echo '<div class="success">✅ Database connection successful!</div>';
        $setup_steps[] = 'Database connection established';
        
        // Get database info
        $config = $db->getConfig();
        echo '<div class="info">📊 <strong>Database Info:</strong><br>';
        echo 'Host: ' . $config['host'] . '<br>';
        echo 'Database: ' . $config['dbname'] . '<br>';
        echo 'Port: ' . $config['port'] . '</div>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="error">❌ Database connection failed: ' . $e->getMessage() . '</div>';
        echo '<div class="info">💡 <strong>Fix:</strong> Check your database credentials in <code>config/database.php</code></div>';
        $errors[] = 'Database connection failed';
        echo '</div>';
    }

    if (empty($errors)) {
        // Step 2: Create tables
        echo '<div class="section"><h2>Step 2: Creating Database Tables</h2>';
        
        try {
            // Create crime_categories table
            $sql = "
                CREATE TABLE IF NOT EXISTS crime_categories (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    category_name VARCHAR(100) NOT NULL UNIQUE,
                    category_icon VARCHAR(20) DEFAULT NULL,
                    category_color VARCHAR(7) DEFAULT '#718096',
                    description TEXT,
                    severity_level ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $db->query($sql);
            echo '<div class="success">✅ Created crime_categories table</div>';
            
            // Create locations table
            $sql = "
                CREATE TABLE IF NOT EXISTS locations (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    latitude DECIMAL(10, 8) NOT NULL,
                    longitude DECIMAL(11, 8) NOT NULL,
                    address VARCHAR(500),
                    city VARCHAR(100),
                    region VARCHAR(100),
                    country VARCHAR(100) DEFAULT 'Tanzania',
                    postal_code VARCHAR(20),
                    location_type ENUM('exact', 'approximate', 'general_area') DEFAULT 'exact',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    INDEX idx_coordinates (latitude, longitude),
                    INDEX idx_city_region (city, region)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $db->query($sql);
            echo '<div class="success">✅ Created locations table</div>';
            
            // Create users table
            $sql = "
                CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    full_name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE,
                    phone VARCHAR(20),
                    user_type ENUM('reporter', 'admin', 'law_enforcement') DEFAULT 'reporter',
                    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_verified BOOLEAN DEFAULT FALSE,
                    is_active BOOLEAN DEFAULT TRUE,
                    last_login TIMESTAMP NULL,
                    password_hash VARCHAR(255) NULL,
                    verification_token VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    INDEX idx_email (email),
                    INDEX idx_user_type (user_type),
                    INDEX idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $db->query($sql);
            echo '<div class="success">✅ Created users table</div>';
            
            // Create incident_reports table
            $sql = "
                CREATE TABLE IF NOT EXISTS incident_reports (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    report_number VARCHAR(20) UNIQUE NOT NULL,
                    
                    reporter_name VARCHAR(255) NOT NULL,
                    reporter_email VARCHAR(255),
                    reporter_phone VARCHAR(20),
                    user_id INT NULL,
                    
                    crime_category_id INT NOT NULL,
                    custom_crime_type VARCHAR(100) NULL,
                    incident_title VARCHAR(255),
                    incident_description TEXT NOT NULL,
                    incident_date DATE,
                    incident_time TIME,
                    
                    location_id INT NOT NULL,
                    
                    estimated_loss DECIMAL(15, 2) DEFAULT 0.00,
                    currency VARCHAR(3) DEFAULT 'TZS',
                    
                    evidence_description TEXT,
                    has_screenshots BOOLEAN DEFAULT FALSE,
                    has_documents BOOLEAN DEFAULT FALSE,
                    
                    status ENUM('pending', 'under_review', 'investigating', 'resolved', 'closed', 'duplicate') DEFAULT 'pending',
                    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                    assigned_to INT NULL,
                    
                    investigation_notes TEXT,
                    resolution_notes TEXT,
                    resolution_date TIMESTAMP NULL,
                    
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    submission_source VARCHAR(50) DEFAULT 'web_form',
                    is_anonymous BOOLEAN DEFAULT FALSE,
                    is_verified BOOLEAN DEFAULT FALSE,
                    is_public BOOLEAN DEFAULT TRUE,
                    
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (crime_category_id) REFERENCES crime_categories(id) ON DELETE RESTRICT,
                    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
                    
                    INDEX idx_report_number (report_number),
                    INDEX idx_status (status),
                    INDEX idx_priority (priority),
                    INDEX idx_crime_category (crime_category_id),
                    INDEX idx_created_date (created_at),
                    INDEX idx_reporter_email (reporter_email),
                    INDEX idx_assigned_to (assigned_to),
                    INDEX idx_public_reports (is_public, status),
                    INDEX idx_location (location_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $db->query($sql);
            echo '<div class="success">✅ Created incident_reports table</div>';
            
            $setup_steps[] = 'Database tables created successfully';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error creating tables: ' . $e->getMessage() . '</div>';
            $errors[] = 'Table creation failed';
        }
        
        echo '</div>';
        
        // Step 3: Insert sample data
        echo '<div class="section"><h2>Step 3: Inserting Sample Data</h2>';
        
        try {
            // Insert crime categories
            $categories = [
                ['Identity Theft', '🆔', '#e53e3e', 'Unauthorized use of personal information', 'High'],
                ['Online Fraud', '💳', '#dd6b20', 'Financial fraud conducted online', 'High'],
                ['Phishing', '🎣', '#d69e2e', 'Fraudulent attempts to obtain sensitive information', 'Medium'],
                ['Ransomware', '🔒', '#9f7aea', 'Malicious software that encrypts files for ransom', 'Critical'],
                ['Cyberbullying', '😢', '#ed64a6', 'Harassment or bullying using digital platforms', 'Medium'],
                ['Data Breach', '📊', '#38b2ac', 'Unauthorized access to confidential data', 'Critical'],
                ['Social Engineering', '🕵️', '#4299e1', 'Manipulation to divulge confidential information', 'High'],
                ['Malware', '🦠', '#f56565', 'Malicious software designed to damage systems', 'High'],
                ['DDoS Attack', '⚡', '#48bb78', 'Distributed Denial of Service attacks', 'Medium'],
                ['Other', '🔍', '#718096', 'Other types of cybercrime not listed above', 'Medium']
            ];
            
            foreach ($categories as $category) {
                $sql = "INSERT IGNORE INTO crime_categories (category_name, category_icon, category_color, description, severity_level) 
                        VALUES (?, ?, ?, ?, ?)";
                $db->query($sql, $category);
            }
            echo '<div class="success">✅ Inserted crime categories</div>';
            
            // Insert sample locations
            $locations = [
                [-6.7924, 39.2083, 'Dar es Salaam', 'Dar es Salaam'],
                [-3.3869, 36.6830, 'Arusha', 'Arusha'],
                [-8.7832, 34.5085, 'Mbeya', 'Mbeya'],
                [-5.0893, 39.2658, 'Tanga', 'Tanga'],
                [-4.0435, 39.6682, 'Malindi', 'Kilifi'],
                [-6.1659, 35.7497, 'Dodoma', 'Dodoma']
            ];
            
            foreach ($locations as $location) {
                $sql = "INSERT INTO locations (latitude, longitude, city, region, country, location_type) 
                        VALUES (?, ?, ?, ?, 'Tanzania', 'exact')";
                $db->query($sql, $location);
            }
            echo '<div class="success">✅ Inserted sample locations</div>';
            
            // Insert default admin user
            $sql = "INSERT IGNORE INTO users (full_name, email, user_type, password_hash, is_verified, is_active) 
                    VALUES ('System Administrator', 'admin@cyberguard.co.tz', 'admin', ?, TRUE, TRUE)";
            $db->query($sql, [password_hash('admin123', PASSWORD_DEFAULT)]);
            echo '<div class="success">✅ Created default admin user</div>';
            
            // Insert sample incident reports
            $reports = [
                [
                    'report_number' => 'CG-2025-000001',
                    'reporter_name' => 'John Doe',
                    'reporter_email' => 'john.doe@email.com',
                    'crime_type' => 'Online Fraud',
                    'description' => 'Sophisticated investment scam targeting mobile money users through fake trading platforms',
                    'location_id' => 1,
                    'status' => 'pending',
                    'priority' => 'high'
                ],
                [
                    'report_number' => 'CG-2025-000002',
                    'reporter_name' => 'Jane Smith',
                    'reporter_phone' => '+255 712 345 678',
                    'crime_type' => 'Identity Theft',
                    'description' => 'Unauthorized access to personal banking information through compromised ATM systems',
                    'location_id' => 2,
                    'status' => 'investigating',
                    'priority' => 'high'
                ],
                [
                    'report_number' => 'CG-2025-000003',
                    'reporter_name' => 'Mike Wilson',
                    'reporter_phone' => '+255 754 987 321',
                    'crime_type' => 'Phishing',
                    'description' => 'Fake bank emails requesting account verification with malicious links',
                    'location_id' => 3,
                    'status' => 'resolved',
                    'priority' => 'medium'
                ],
                [
                    'report_number' => 'CG-2025-000004',
                    'reporter_name' => 'Sarah Johnson',
                    'reporter_email' => 'sarah.j@email.com',
                    'crime_type' => 'Cyberbullying',
                    'description' => 'Systematic harassment campaign through multiple social media platforms',
                    'location_id' => 4,
                    'status' => 'pending',
                    'priority' => 'medium'
                ],
                [
                    'report_number' => 'CG-2025-000005',
                    'reporter_name' => 'Tech Solutions Ltd',
                    'reporter_email' => 'security@techsolutions.co.tz',
                    'crime_type' => 'Ransomware',
                    'description' => 'Computer systems encrypted by advanced malicious software demanding payment',
                    'location_id' => 5,
                    'status' => 'investigating',
                    'priority' => 'urgent'
                ],
                [
                    'report_number' => 'CG-2025-000006',
                    'reporter_name' => 'David Brown',
                    'reporter_email' => 'david.brown@email.com',
                    'crime_type' => 'Data Breach',
                    'description' => 'Customer database compromised exposing personal and financial information',
                    'location_id' => 6,
                    'status' => 'resolved',
                    'priority' => 'high'
                ]
            ];
            
            foreach ($reports as $report) {
                // Get crime category ID
                $cat_sql = "SELECT id FROM crime_categories WHERE category_name = ?";
                $cat_result = $db->fetch($cat_sql, [$report['crime_type']]);
                $category_id = $cat_result['id'];
                
                $sql = "INSERT INTO incident_reports (
                            report_number, reporter_name, reporter_email, reporter_phone,
                            crime_category_id, incident_description, location_id,
                            status, priority, ip_address, user_agent, submission_source,
                            is_public, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '127.0.0.1', 'Setup Script', 'setup_script', TRUE, NOW())";
                
                $db->query($sql, [
                    $report['report_number'],
                    $report['reporter_name'],
                    $report['reporter_email'] ?? null,
                    $report['reporter_phone'] ?? null,
                    $category_id,
                    $report['description'],
                    $report['location_id'],
                    $report['status'],
                    $report['priority']
                ]);
            }
            
            echo '<div class="success">✅ Inserted sample incident reports</div>';
            $setup_steps[] = 'Sample data inserted successfully';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error inserting sample data: ' . $e->getMessage() . '</div>';
            $errors[] = 'Sample data insertion failed';
        }
        
        echo '</div>';
        
        // Step 4: Verify data
        echo '<div class="section"><h2>Step 4: Verifying Data</h2>';
        
        try {
            $report_count = $db->fetch("SELECT COUNT(*) as count FROM incident_reports");
            $category_count = $db->fetch("SELECT COUNT(*) as count FROM crime_categories");
            $location_count = $db->fetch("SELECT COUNT(*) as count FROM locations");
            
            echo '<div class="success">✅ <strong>Data Verification:</strong><br>';
            echo 'Crime Categories: ' . $category_count['count'] . '<br>';
            echo 'Locations: ' . $location_count['count'] . '<br>';
            echo 'Incident Reports: ' . $report_count['count'] . '</div>';
            
            if ($report_count['count'] > 0) {
                echo '<div class="success">🎉 <strong>Setup Complete!</strong> Your database now has sample data.</div>';
            } else {
                echo '<div class="error">⚠️ <strong>Warning:</strong> No incident reports found in database.</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error verifying data: ' . $e->getMessage() . '</div>';
        }
        
        echo '</div>';
    }
    
    // Summary
    echo '<div class="section"><h2>Setup Summary</h2>';
    
    if (empty($errors)) {
        echo '<div class="success">🎉 <strong>Database Setup Successful!</strong></div>';
        echo '<div class="info"><strong>Completed Steps:</strong><br>';
        foreach ($setup_steps as $step) {
            echo '✅ ' . $step . '<br>';
        }
        echo '</div>';
        
        echo '<div class="info"><strong>Default Admin Login:</strong><br>';
        echo 'Email: admin@cyberguard.co.tz<br>';
        echo 'Password: admin123<br>';
        echo '<em>(Change this password in production!)</em></div>';
        
        echo '<p><strong>Next Steps:</strong></p>';
        echo '<a href="cyberguard_dashboard.php" class="btn">📊 Go to Dashboard</a>';
        echo '<a href="index.html" class="btn">📝 Test Public Form</a>';
        echo '<a href="test_system.php" class="btn">🧪 Run System Tests</a>';
        
    } else {
        echo '<div class="error">❌ <strong>Setup Failed!</strong></div>';
        echo '<div class="error"><strong>Errors:</strong><br>';
        foreach ($errors as $error) {
            echo '❌ ' . $error . '<br>';
        }
        echo '</div>';
        
        echo '<div class="info"><strong>Troubleshooting:</strong><br>';
        echo '1. Check database credentials in config/database.php<br>';
        echo '2. Ensure MySQL server is running<br>';
        echo '3. Verify database permissions<br>';
        echo '4. Check error logs for detailed information</div>';
    }
    
    echo '</div>';
    ?>

    <div style="text-align: center; margin-top: 2rem; padding: 2rem; background: #f7fafc; border-radius: 10px;">
        <p><strong>Database Setup Complete</strong></p>
        <p>Setup run at: <?php echo date('Y-m-d H:i:s'); ?></p>
        <p style="margin-top: 1rem;">
            <a href="?" class="btn">🔄 Run Setup Again</a>
            <a href="cyberguard_dashboard.php" class="btn">📊 View Dashboard</a>
        </p>
    </div>
</body>
</html>