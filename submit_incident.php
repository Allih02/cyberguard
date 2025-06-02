<?php
// =====================================================
// CyberGuard Form Submission Handler - CORRECTED VERSION
// File: submit_incident.php
// =====================================================

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Log all requests for debugging
error_log("submit_incident.php accessed at " . date('Y-m-d H:i:s') . " - Method: " . $_SERVER['REQUEST_METHOD']);

// Include required files with error handling
try {
    if (!file_exists('config/database.php')) {
        throw new Exception('config/database.php not found');
    }
    require_once 'config/database.php';
    
    if (!file_exists('includes/functions.php')) {
        throw new Exception('includes/functions.php not found');
    }
    require_once 'includes/functions.php';
    
} catch (Exception $e) {
    error_log("File include error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server configuration error: ' . $e->getMessage(),
        'error_code' => 'FILE_NOT_FOUND'
    ]);
    exit;
}

// Enhanced Report submission class
class ReportSubmission {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDatabase();
            error_log("Database connection established in ReportSubmission");
        } catch (Exception $e) {
            error_log("Database connection failed in ReportSubmission: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Get or create crime category
    private function getCrimeCategory($crimeType) {
        try {
            error_log("Getting crime category for: " . $crimeType);
            
            // First, try to find existing category
            $sql = "SELECT id FROM crime_categories WHERE category_name = ? AND is_active = TRUE";
            $result = $this->db->fetch($sql, [$crimeType]);
            
            if ($result) {
                error_log("Found existing category ID: " . $result['id']);
                return $result['id'];
            }
            
            // If not found, create new category
            error_log("Creating new category: " . $crimeType);
            $sql = "INSERT INTO crime_categories (category_name, category_icon, category_color, description, is_active) 
                    VALUES (?, '🔍', '#718096', ?, TRUE)";
            
            $description = "Auto-created category for: " . $crimeType;
            
            if ($this->db->query($sql, [$crimeType, $description])) {
                $newId = $this->db->lastInsertId();
                error_log("Created new category with ID: " . $newId);
                return $newId;
            }
            
            throw new Exception("Failed to create crime category");
            
        } catch (Exception $e) {
            error_log("Error in getCrimeCategory: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Create location record
    private function createLocation($latitude, $longitude) {
        try {
            error_log("Creating location: lat=$latitude, lng=$longitude");
            
            // Tanzania location mapping
            $locations = [
                ['lat' => -6.7924, 'lng' => 39.2083, 'city' => 'Dar es Salaam', 'region' => 'Dar es Salaam'],
                ['lat' => -3.3869, 'lng' => 36.6830, 'city' => 'Arusha', 'region' => 'Arusha'],
                ['lat' => -8.7832, 'lng' => 34.5085, 'city' => 'Mbeya', 'region' => 'Mbeya'],
                ['lat' => -5.0893, 'lng' => 39.2658, 'city' => 'Tanga', 'region' => 'Tanga'],
                ['lat' => -4.0435, 'lng' => 39.6682, 'city' => 'Malindi', 'region' => 'Kilifi'],
                ['lat' => -6.1659, 'lng' => 35.7497, 'city' => 'Dodoma', 'region' => 'Dodoma']
            ];
            
            // Find closest location
            $city = 'Unknown';
            $region = 'Unknown';
            $minDistance = PHP_FLOAT_MAX;
            
            foreach ($locations as $location) {
                $distance = sqrt(pow($latitude - $location['lat'], 2) + pow($longitude - $location['lng'], 2));
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    if ($distance < 1.0) { // Within ~100km
                        $city = $location['city'];
                        $region = $location['region'];
                    }
                }
            }
            
            $sql = "INSERT INTO locations (latitude, longitude, city, region, country, location_type) 
                    VALUES (?, ?, ?, ?, 'Tanzania', 'exact')";
            
            if ($this->db->query($sql, [$latitude, $longitude, $city, $region])) {
                $locationId = $this->db->lastInsertId();
                error_log("Created location with ID: $locationId in $city, $region");
                return $locationId;
            }
            
            throw new Exception("Failed to create location");
            
        } catch (Exception $e) {
            error_log("Error in createLocation: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Generate report number
    private function generateReportNumber() {
        try {
            $year = date('Y');
            $month = date('m');
            
            // Get next sequence number
            $sql = "SELECT COUNT(*) + 1 as next_id FROM incident_reports WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
            $result = $this->db->fetch($sql, [$year, $month]);
            $nextId = $result ? $result['next_id'] : 1;
            
            $reportNumber = 'CG-' . $year . $month . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            error_log("Generated report number: " . $reportNumber);
            return $reportNumber;
            
        } catch (Exception $e) {
            error_log("Error generating report number: " . $e->getMessage());
            return 'CG-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    // Submit incident report
    public function submitReport($data) {
        try {
            error_log("Starting report submission with data: " . json_encode($data));
            
            // Validate required fields
            $required = ['reporter_name', 'contact_info', 'crime_type', 'description', 'latitude', 'longitude'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Validate coordinates
            $latitude = floatval($data['latitude']);
            $longitude = floatval($data['longitude']);
            
            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                throw new Exception('Invalid coordinates provided');
            }
            
            // Sanitize input
            $reporterName = sanitizeInput($data['reporter_name']);
            $contactInfo = sanitizeInput($data['contact_info']);
            $crimeType = sanitizeInput($data['crime_type']);
            $description = sanitizeInput($data['description']);
            
            // Determine contact type
            $reporterEmail = null;
            $reporterPhone = null;
            
            if (filter_var($contactInfo, FILTER_VALIDATE_EMAIL)) {
                $reporterEmail = $contactInfo;
            } else {
                $reporterPhone = $contactInfo;
            }
            
            // Start transaction
            $this->db->beginTransaction();
            error_log("Started database transaction");
            
            // Get or create crime category
            $categoryId = $this->getCrimeCategory($crimeType);
            if (!$categoryId) {
                throw new Exception('Failed to process crime type');
            }
            
            // Create location
            $locationId = $this->createLocation($latitude, $longitude);
            if (!$locationId) {
                throw new Exception('Failed to save location');
            }
            
            // Generate report number
            $reportNumber = $this->generateReportNumber();
            
            // Get client info
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            // Insert incident report
            $sql = "INSERT INTO incident_reports (
                        report_number, reporter_name, reporter_email, reporter_phone,
                        crime_category_id, incident_description, location_id,
                        status, priority, ip_address, user_agent, submission_source,
                        is_public, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'medium', ?, ?, 'web_form', TRUE, NOW())";
            
            $params = [
                $reportNumber,
                $reporterName,
                $reporterEmail,
                $reporterPhone,
                $categoryId,
                $description,
                $locationId,
                $ipAddress,
                $userAgent
            ];
            
            error_log("Executing insert query with params: " . json_encode($params));
            
            if (!$this->db->query($sql, $params)) {
                throw new Exception('Failed to save incident report');
            }
            
            $reportId = $this->db->lastInsertId();
            error_log("Inserted report with ID: " . $reportId);
            
            // Commit transaction
            $this->db->commit();
            error_log("Transaction committed successfully");
            
            // Log activity
            logActivity('incident_report_submitted', [
                'report_id' => $reportId,
                'report_number' => $reportNumber,
                'crime_type' => $crimeType
            ]);
            
            return [
                'success' => true,
                'message' => 'Report submitted successfully',
                'report_number' => $reportNumber,
                'report_id' => $reportId,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($this->db->inTransaction()) {
                $this->db->rollback();
                error_log("Transaction rolled back due to error");
            }
            
            error_log("Report submission error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'SUBMISSION_FAILED',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
}

// Handle different request methods
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("Processing POST request");
        
        // Get input data
        $input = file_get_contents('php://input');
        error_log("Raw input: " . $input);
        
        $data = json_decode($input, true);
        
        if (!$data) {
            // Try form data instead
            $data = $_POST;
            error_log("Using POST data: " . json_encode($_POST));
        }
        
        if (empty($data)) {
            throw new Exception('No data received');
        }
        
        error_log("Parsed data: " . json_encode($data));
        
        // Basic rate limiting
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($clientIP !== 'unknown') {
            $recentSubmissions = dbFetch(
                "SELECT COUNT(*) as count FROM incident_reports 
                 WHERE ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                [$clientIP]
            );
            
            if ($recentSubmissions && $recentSubmissions['count'] >= 10) {
                http_response_code(429);
                echo json_encode([
                    'success' => false,
                    'message' => 'Too many submissions. Please wait before submitting again.',
                    'error_code' => 'RATE_LIMITED'
                ]);
                exit;
            }
        }
        
        // Process the report
        $submissionHandler = new ReportSubmission();
        $result = $submissionHandler->submitReport($data);
        
        // Set HTTP status code
        if (!$result['success']) {
            http_response_code(400);
        }
        
        error_log("Final result: " . json_encode($result));
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("General error in submit_incident.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Server error: ' . $e->getMessage(),
            'error_code' => 'SERVER_ERROR',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['stats'])) {
    // Optional: Provide submission statistics
    try {
        $db = getDatabase();
        $stats = $db->fetch(
            "SELECT 
                COUNT(*) as total_submissions,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_submissions,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_submissions
             FROM incident_reports"
        );
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error retrieving statistics'
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test'])) {
    // Test endpoint
    try {
        $db = getDatabase();
        $test = $db->fetch("SELECT 'Connection successful' as message, NOW() as timestamp");
        
        echo json_encode([
            'success' => true,
            'message' => 'submit_incident.php is working',
            'database_test' => $test,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]);
    }
    
} else {
    // Method not allowed or invalid request
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Method not allowed',
        'error_code' => 'METHOD_NOT_ALLOWED',
        'allowed_methods' => ['POST'],
        'current_method' => $_SERVER['REQUEST_METHOD']
    ]);
}
?>