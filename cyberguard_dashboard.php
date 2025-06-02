<?php
// =====================================================
// CyberGuard Dashboard - Updated with Centralized DB Config
// File: cyberguard_dashboard.php
// =====================================================

session_start();

// Include centralized database configuration
require_once 'config/database.php';
require_once 'includes/functions.php';

// Dashboard data class
class DashboardData {
    private $db;
    
    public function __construct() {
        $this->db = getDatabase();
    }
    
    // Get dashboard statistics
    public function getDashboardStats() {
        $sql = "
            SELECT 
                COUNT(*) as total_reports,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
                SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_reports,
                COALESCE(SUM(estimated_loss), 0) as total_estimated_loss,
                COALESCE(AVG(estimated_loss), 0) as avg_estimated_loss,
                AVG(CASE 
                    WHEN resolution_date IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, created_at, resolution_date) 
                    ELSE NULL 
                END) as avg_resolution_time
            FROM incident_reports
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
        
        return $this->db->fetch($sql);
    }
    
    // Get recent reports with full details
    public function getRecentReports($limit = 10) {
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
                ir.updated_at,
                CONCAT(u.full_name) as assigned_to_name
            FROM incident_reports ir
            JOIN crime_categories cc ON ir.crime_category_id = cc.id
            JOIN locations l ON ir.location_id = l.id
            LEFT JOIN users u ON ir.assigned_to = u.id
            ORDER BY ir.created_at DESC
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    // Get reports for map display
    public function getMapReports() {
        $sql = "
            SELECT 
                ir.id,
                ir.report_number,
                cc.category_name,
                cc.category_color,
                ir.priority,
                ir.status,
                l.latitude,
                l.longitude,
                l.city,
                l.region,
                ir.incident_description,
                ir.created_at
            FROM incident_reports ir
            JOIN crime_categories cc ON ir.crime_category_id = cc.id
            JOIN locations l ON ir.location_id = l.id
            WHERE ir.is_public = TRUE 
            AND l.latitude IS NOT NULL 
            AND l.longitude IS NOT NULL
            ORDER BY ir.created_at DESC
            LIMIT 50
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Get crime trends data for charts
    public function getCrimeTrends() {
        $sql = "
            SELECT 
                cc.category_name,
                cc.category_color,
                COUNT(*) as count,
                DATE_FORMAT(ir.created_at, '%Y-%m') as month
            FROM incident_reports ir
            JOIN crime_categories cc ON ir.crime_category_id = cc.id
            WHERE ir.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY cc.category_name, cc.category_color, DATE_FORMAT(ir.created_at, '%Y-%m')
            ORDER BY month ASC, count DESC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Get crime type distribution
    public function getCrimeTypeDistribution() {
        $sql = "
            SELECT 
                cc.category_name,
                cc.category_icon,
                cc.category_color,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM incident_reports)), 2) as percentage
            FROM incident_reports ir
            JOIN crime_categories cc ON ir.crime_category_id = cc.id
            WHERE ir.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY cc.id, cc.category_name, cc.category_icon, cc.category_color
            ORDER BY count DESC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Get pending reports count for notifications
    public function getPendingReportsCount() {
        $sql = "SELECT COUNT(*) as count FROM incident_reports WHERE status = 'pending'";
        $result = $this->db->fetch($sql);
        return $result ? $result['count'] : 0;
    }
}

// Initialize dashboard data
try {
    $dashboard = new DashboardData();
    
    // Fetch all required data
    $stats = $dashboard->getDashboardStats();
    $recentReports = $dashboard->getRecentReports(10);
    $mapReports = $dashboard->getMapReports();
    $crimeTrends = $dashboard->getCrimeTrends();
    $crimeDistribution = $dashboard->getCrimeTypeDistribution();
    $pendingCount = $dashboard->getPendingReportsCount();
    
} catch (Exception $e) {
    // Handle database connection errors gracefully
    error_log("Dashboard Error: " . $e->getMessage());
    
    // Set default values to prevent page crashes
    $stats = ['total_reports' => 0, 'pending_reports' => 0, 'resolved_reports' => 0];
    $recentReports = [];
    $mapReports = [];
    $crimeTrends = [];
    $crimeDistribution = [];
    $pendingCount = 0;
    
    $database_error = true;
}

// API endpoints for AJAX requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['api']) {
            case 'stats':
                echo json_encode($stats);
                break;
                
            case 'recent_reports':
                echo json_encode($recentReports);
                break;
                
            case 'map_data':
                echo json_encode($mapReports);
                break;
                
            case 'crime_trends':
                // Format data for Chart.js
                $trends = [];
                $categories = [];
                $months = [];
                
                foreach ($crimeTrends as $trend) {
                    if (!in_array($trend['month'], $months)) {
                        $months[] = $trend['month'];
                    }
                    
                    if (!isset($categories[$trend['category_name']])) {
                        $categories[$trend['category_name']] = [
                            'label' => $trend['category_name'],
                            'color' => $trend['category_color'],
                            'data' => []
                        ];
                    }
                    
                    $categories[$trend['category_name']]['data'][$trend['month']] = $trend['count'];
                }
                
                // Fill missing months with 0
                foreach ($categories as &$category) {
                    $filledData = [];
                    foreach ($months as $month) {
                        $filledData[] = isset($category['data'][$month]) ? $category['data'][$month] : 0;
                    }
                    $category['data'] = $filledData;
                }
                
                echo json_encode([
                    'months' => array_map(function($month) {
                        return date('M Y', strtotime($month . '-01'));
                    }, $months),
                    'datasets' => array_values($categories)
                ]);
                break;
                
            case 'crime_distribution':
                echo json_encode($crimeDistribution);
                break;
                
            case 'health_check':
                echo json_encode(checkDatabaseHealth());
                break;
                
            default:
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'API error: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberGuard Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.min.js"></script>
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-dark: #1a202c;
            --bg-darker: #2d3748;
            
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --text-white: #ffffff;
            
            --border-light: #e2e8f0;
            --border-medium: #cbd5e0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15);
            
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--border-light);
            z-index: 1000;
            padding: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .navbar-brand i {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            margin-right: 0.75rem;
        }

        .hero {
            margin-top: 80px;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .main-content {
            padding: 3rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .reports-table th,
        .reports-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }

        .reports-table th {
            background: var(--bg-primary);
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.investigating {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.resolved {
            background: #d1fae5;
            color: #065f46;
        }

        .priority-badge {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .priority-badge.high { background: #ef4444; }
        .priority-badge.medium { background: #f59e0b; }
        .priority-badge.low { background: #10b981; }

        .reports-section {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            margin-bottom: 3rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 14px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-medium);
        }

        .error-message {
            background: #fed7d7;
            color: #742a2a;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            border-left: 4px solid #fc8181;
        }

        .success-message {
            background: #f0fff4;
            color: #22543d;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            border-left: 4px solid #9ae6b4;
        }

        @media (max-width: 768px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="cyberguard_dashboard.php" class="navbar-brand">
                <i class="fas fa-shield-virus"></i>
                CyberGuard Pro
            </a>
            
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="position: relative; color: var(--text-secondary); font-size: 1.2rem; cursor: pointer;">
                    <i class="fas fa-bell"></i>
                    <?php if ($pendingCount > 0): ?>
                        <span style="position: absolute; top: -8px; right: -8px; background: var(--danger-gradient); color: white; font-size: 0.7rem; font-weight: 600; padding: 2px 6px; border-radius: 10px; min-width: 18px; text-align: center;">
                            <?php echo $pendingCount; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
    </nav>

    <?php if (isset($database_error)): ?>
        <div style="margin-top: 80px; padding: 2rem;">
            <div class="error-message">
                <h3><i class="fas fa-exclamation-triangle"></i> Database Connection Error</h3>  
                <p>Unable to connect to the database. Please check your database configuration and ensure the database server is running.</p>
                <p><strong>File:</strong> config/database.php</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hero Section with Live Data -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Cybercrime Command Center</h1>
                <p style="font-size: 1.25rem; opacity: 0.9; margin-bottom: 2rem; line-height: 1.6;">
                    Monitor, analyze, and respond to cybersecurity threats with real-time insights 
                    and advanced threat intelligence. Protecting Tanzania's digital infrastructure.
                </p>
                
                <div class="hero-stats">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo number_format($stats['total_reports'] ?? 0); ?></span>
                        <span class="stat-label">Total Reports</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo number_format($stats['pending_reports'] ?? 0); ?></span>
                        <span class="stat-label">Pending Cases</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">
                            <?php 
                            $total = $stats['total_reports'] ?? 1;
                            $resolved = $stats['resolved_reports'] ?? 0;
                            echo $total > 0 ? round(($resolved / $total) * 100) . '%' : '0%';
                            ?>
                        </span>
                        <span class="stat-label">Resolution Rate</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Dashboard Content -->
    <main class="main-content">
        <!-- Recent Reports Table with Real Data -->
        <div class="reports-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list-alt"></i>
                    Recent Incident Reports
                </h2>
                <div>
                    <button class="btn btn-primary" onclick="window.location.href='index.html'">
                        <i class="fas fa-plus"></i>
                        New Report
                    </button>
                </div>
            </div>

            <?php if (empty($recentReports)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>No Reports Yet</h3>
                    <p>No incident reports have been submitted yet. Once reports are submitted through the main form, they will appear here.</p>
                    <a href="index.html" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i>
                        Submit First Report
                    </a>
                </div>
            <?php else: ?>
                <div class="success-message">
                    <i class="fas fa-database"></i> Successfully connected to database. Showing <?php echo count($recentReports); ?> recent reports.
                </div>
                
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Type</th>
                            <th>Reporter</th>
                            <th>Location</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReports as $report): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($report['report_number']); ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <?php echo $report['category_icon']; ?>
                                        <span><?php echo htmlspecialchars($report['category_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                                <td><?php echo htmlspecialchars($report['city'] . ', ' . $report['region']); ?></td>
                                <td>
                                    <span class="priority-badge <?php echo getPriorityClass($report['priority']); ?>"></span>
                                    <?php echo ucfirst($report['priority']); ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($report['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo formatTimeAgo($report['created_at']); ?></td>
                                <td>
                                    <button class="btn btn-secondary" style="padding: 0.5rem; font-size: 0.75rem;" 
                                            onclick="viewReport(<?php echo $report['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Map Section with Real Data -->
        <div class="reports-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-map-marked-alt"></i>
                    Live Threat Map
                </h2>
                <div>
                    <button class="btn btn-primary" onclick="refreshMapData()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh Data
                    </button>
                </div>
            </div>
            <div id="adminMap" style="height: 400px; width: 100%; border-radius: var(--radius-md); border: 1px solid var(--border-light);"></div>
        </div>
    </main>

    <script>
        // Real map data from PHP
        const mapReportsData = <?php echo json_encode($mapReports); ?>;
        
        // Initialize map with real data
        function initializeMap() {
            const map = L.map('adminMap').setView([-6.369028, 34.888822], 6);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            const priorityColors = {
                low: '#10b981',
                medium: '#f59e0b', 
                high: '#ef4444',
                urgent: '#7c3aed'
            };

            // Add real incident markers
            mapReportsData.forEach(incident => {
                if (incident.latitude && incident.longitude) {
                    const color = priorityColors[incident.priority] || '#718096';
                    const radius = incident.priority === 'urgent' ? 12 : 
                                 incident.priority === 'high' ? 10 : 8;

                    const marker = L.circleMarker([incident.latitude, incident.longitude], {
                        radius: radius,
                        fillColor: color,
                        color: '#ffffff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(map);

                    marker.bindPopup(`
                        <div style="text-align: center; padding: 0.5rem; min-width: 200px;">
                            <strong>${incident.category_name.toUpperCase()}</strong><br>
                            <span style="color: ${color}; font-weight: 600;">
                                ${incident.priority.toUpperCase()} PRIORITY
                            </span><br>
                            <small><strong>ID:</strong> ${incident.report_number}</small><br>
                            <small><strong>Location:</strong> ${incident.city}, ${incident.region}</small><br>
                            <small><strong>Status:</strong> ${incident.status.replace('_', ' ').toUpperCase()}</small><br>
                            <small><strong>Date:</strong> ${new Date(incident.created_at).toLocaleDateString()}</small><br>
                            <button onclick="viewReport(${incident.id})" style="margin-top: 0.5rem; padding: 0.25rem 0.75rem; background: ${color}; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                View Details
                            </button>
                        </div>
                    `);
                }
            });

            // Store map globally for refreshing
            window.adminMap = map;
        }

        // Refresh map data
        function refreshMapData() {
            fetch('?api=map_data')
                .then(response => response.json())
                .then(data => {
                    // Clear existing markers
                    window.adminMap.eachLayer(layer => {
                        if (layer instanceof L.CircleMarker) {
                            window.adminMap.removeLayer(layer);
                        }
                    });

                    // Add new markers
                    const priorityColors = {
                        low: '#10b981',
                        medium: '#f59e0b', 
                        high: '#ef4444',
                        urgent: '#7c3aed'
                    };

                    data.forEach(incident => {
                        if (incident.latitude && incident.longitude) {
                            const color = priorityColors[incident.priority] || '#718096';
                            const radius = incident.priority === 'urgent' ? 12 : 
                                         incident.priority === 'high' ? 10 : 8;

                            const marker = L.circleMarker([incident.latitude, incident.longitude], {
                                radius: radius,
                                fillColor: color,
                                color: '#ffffff',
                                weight: 2,
                                opacity: 1,
                                fillOpacity: 0.8
                            }).addTo(window.adminMap);

                            marker.bindPopup(`
                                <div style="text-align: center; padding: 0.5rem; min-width: 200px;">
                                    <strong>${incident.category_name.toUpperCase()}</strong><br>
                                    <span style="color: ${color}; font-weight: 600;">
                                        ${incident.priority.toUpperCase()} PRIORITY
                                    </span><br>
                                    <small><strong>ID:</strong> ${incident.report_number}</small><br>
                                    <small><strong>Location:</strong> ${incident.city}, ${incident.region}</small><br>
                                    <small><strong>Status:</strong> ${incident.status.replace('_', ' ').toUpperCase()}</small><br>
                                    <small><strong>Date:</strong> ${new Date(incident.created_at).toLocaleDateString()}</small><br>
                                    <button onclick="viewReport(${incident.id})" style="margin-top: 0.5rem; padding: 0.25rem 0.75rem; background: ${color}; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                        View Details
                                    </button>
                                </div>
                            `);
                        }
                    });

                    showNotification('Map data refreshed successfully!', 'success');
                })
                .catch(error => {
                    console.error('Error refreshing map data:', error);
                    showNotification('Failed to refresh map data', 'error');
                });
        }

        // View report details
        function viewReport(reportId) {
            showNotification(`Opening report details for ID: ${reportId}`, 'info');
            // Here you would typically open a modal or navigate to a detailed view
            // For now, we'll just show a notification
        }

        // Show notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // Auto-refresh data every 30 seconds
        function startAutoRefresh() {
            setInterval(() => {
                // Refresh statistics
                fetch('?api=stats')
                    .then(response => response.json())
                    .then(data => {
                        // Update hero statistics
                        const totalReports = document.querySelector('.hero-stats .stat-card:nth-child(1) .stat-number');
                        const pendingReports = document.querySelector('.hero-stats .stat-card:nth-child(2) .stat-number');
                        const resolutionRate = document.querySelector('.hero-stats .stat-card:nth-child(3) .stat-number');

                        if (totalReports) totalReports.textContent = parseInt(data.total_reports || 0).toLocaleString();
                        if (pendingReports) pendingReports.textContent = parseInt(data.pending_reports || 0).toLocaleString();
                        
                        if (resolutionRate) {
                            const total = parseInt(data.total_reports) || 1;
                            const resolved = parseInt(data.resolved_reports) || 0;
                            const rate = total > 0 ? Math.round((resolved / total) * 100) : 0;
                            resolutionRate.textContent = rate + '%';
                        }
                    })
                    .catch(error => console.error('Error refreshing stats:', error));

                // Refresh map data
                refreshMapData();
            }, 30000); // 30 seconds
        }

        // Database health check
        function checkDatabaseHealth() {
            fetch('?api=health_check')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'healthy') {
                        showNotification('Database connection is healthy', 'success');
                    } else {
                        showNotification('Database health check failed: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Health check error:', error);
                    showNotification('Failed to check database health', 'error');
                });
        }

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            startAutoRefresh();
            
            // Add click handlers for buttons
            document.addEventListener('click', function(e) {
                if (e.target.closest('.notification-bell') || e.target.closest('[style*="fa-bell"]')) {
                    showNotification(`You have ${<?php echo $pendingCount; ?>} pending incident reports`, 'info');
                }
            });

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + R for refresh
                if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                    e.preventDefault();
                    refreshMapData();
                }
                
                // Ctrl/Cmd + H for health check
                if ((e.ctrlKey || e.metaKey) && e.key === 'h') {
                    e.preventDefault();
                    checkDatabaseHealth();
                }
            });

            console.log('🚀 CyberGuard Dashboard with centralized DB config loaded successfully!');
            console.log('📊 Real data loaded from database using config/database.php');
            console.log('🔄 Auto-refresh active (30s intervals)');
            console.log('⌨️ Keyboard shortcuts: Ctrl+R (refresh), Ctrl+H (health check)');
        });

        // Add CSS for notifications
        const notificationCSS = `
            .notification {
                position: fixed;
                top: 100px;
                right: 20px;
                background: white;
                padding: 1rem 1.5rem;
                border-radius: var(--radius-md);
                box-shadow: var(--shadow-xl);
                border-left: 4px solid #667eea;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                transform: translateX(400px);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 2000;
                max-width: 350px;
            }

            .notification.show {
                transform: translateX(0);
            }

            .notification-success {
                border-left-color: #10b981;
            }

            .notification-error {
                border-left-color: #ef4444;
            }

            .notification i {
                font-size: 1.2rem;
                color: #667eea;
            }

            .notification-success i {
                color: #10b981;
            }

            .notification-error i {
                color: #ef4444;
            }
        `;

        // Inject CSS
        const style = document.createElement('style');
        style.textContent = notificationCSS;
        document.head.appendChild(style);
    </script>
</body>
</html>