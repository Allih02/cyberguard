<?php
/**
 * =====================================================
 * CyberGuard Helper Functions
 * File: includes/functions.php
 * 
 * Common utility functions used throughout the CyberGuard system
 * =====================================================
 */

// Prevent direct access
if (!defined('CYBERGUARD_SYSTEM')) {
    define('CYBERGUARD_SYSTEM', true);
}

// Ensure database config is loaded
if (!function_exists('getDatabase')) {
    require_once __DIR__ . '/../config/database.php';
}

/**
 * =====================================================
 * DATA FORMATTING FUNCTIONS
 * =====================================================
 */

/**
 * Format currency for display
 * @param float $amount The amount to format
 * @param string $currency Currency code (TZS, USD, etc.)
 * @return string Formatted currency string
 */
function formatCurrency($amount, $currency = 'TZS') {
    if (!is_numeric($amount)) {
        return 'N/A';
    }
    
    switch (strtoupper($currency)) {
        case 'TZS':
            return 'TSh ' . number_format($amount, 0);
        case 'USD':
            return '$' . number_format($amount, 2);
        case 'EUR':
            return '€' . number_format($amount, 2);
        case 'GBP':
            return '£' . number_format($amount, 2);
        default:
            return $currency . ' ' . number_format($amount, 2);
    }
}

/**
 * Format time ago in human readable format
 * @param string $datetime DateTime string
 * @return string Human readable time difference
 */
function formatTimeAgo($datetime) {
    if (empty($datetime)) {
        return 'Unknown';
    }
    
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
    } elseif ($time < 31536000) {
        $months = floor($time / 2592000);
        return $months . ' month' . ($months != 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', strtotime($datetime));
    }
}

/**
 * Format file size in human readable format
 * @param int $bytes File size in bytes
 * @param int $precision Number of decimal places
 * @return string Formatted file size
 */
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Format phone number for display
 * @param string $phone Raw phone number
 * @return string Formatted phone number
 */
function formatPhoneNumber($phone) {
    // Remove non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Tanzania phone number formatting
    if (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
        return '0' . substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6);
    } elseif (strlen($phone) == 10 && substr($phone, 0, 2) == '07') {
        return substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
    } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '255') {
        return '+255 ' . substr($phone, 3, 3) . ' ' . substr($phone, 6, 3) . ' ' . substr($phone, 9);
    }
    
    // Default formatting for other numbers
    return $phone;
}

/**
 * =====================================================
 * STATUS AND BADGE FUNCTIONS
 * =====================================================
 */

/**
 * Get CSS class for status badges
 * @param string $status Status value
 * @return string CSS class name
 */
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'pending';
        case 'under_review':
        case 'investigating':
            return 'investigating';
        case 'resolved':
        case 'closed':
            return 'resolved';
        case 'duplicate':
            return 'duplicate';
        case 'rejected':
            return 'rejected';
        default:
            return 'pending';
    }
}

/**
 * Get CSS class for priority badges
 * @param string $priority Priority level
 * @return string CSS class name
 */
function getPriorityClass($priority) {
    switch (strtolower($priority)) {
        case 'urgent':
        case 'critical':
        case 'high':
            return 'high';
        case 'medium':
        case 'normal':
            return 'medium';
        case 'low':
            return 'low';
        default:
            return 'medium';
    }
}

/**
 * Get human-readable status text
 * @param string $status Status value
 * @return string Human-readable status
 */
function getStatusText($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'Pending Review';
        case 'under_review':
            return 'Under Review';
        case 'investigating':
            return 'Under Investigation';
        case 'resolved':
            return 'Resolved';
        case 'closed':
            return 'Closed';
        case 'duplicate':
            return 'Duplicate';
        case 'rejected':
            return 'Rejected';
        default:
            return ucfirst($status);
    }
}

/**
 * Get priority color for display
 * @param string $priority Priority level
 * @return string Color hex code
 */
function getPriorityColor($priority) {
    switch (strtolower($priority)) {
        case 'urgent':
        case 'critical':
            return '#7c3aed'; // Purple
        case 'high':
            return '#ef4444'; // Red
        case 'medium':
        case 'normal':
            return '#f59e0b'; // Yellow
        case 'low':
            return '#10b981'; // Green
        default:
            return '#718096'; // Gray
    }
}

/**
 * =====================================================
 * VALIDATION FUNCTIONS
 * =====================================================
 */

/**
 * Sanitize input data
 * @param mixed $data Input data (string or array)
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email Email address to validate
 * @return bool True if valid email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Tanzania format)
 * @param string $phone Phone number to validate
 * @return bool True if valid phone number
 */
function isValidPhone($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Tanzania phone number patterns
    $patterns = [
        '/^0[67][0-9]{8}$/',          // 0712345678, 0622345678
        '/^\+255[67][0-9]{8}$/',      // +255712345678
        '/^255[67][0-9]{8}$/',        // 255712345678
        '/^[67][0-9]{8}$/',           // 712345678
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $phone)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Validate coordinates
 * @param float $latitude Latitude value
 * @param float $longitude Longitude value
 * @return bool True if valid coordinates
 */
function isValidCoordinates($latitude, $longitude) {
    return is_numeric($latitude) && is_numeric($longitude) && 
           $latitude >= -90 && $latitude <= 90 && 
           $longitude >= -180 && $longitude <= 180;
}

/**
 * Validate Tanzania coordinates (rough bounds)
 * @param float $latitude Latitude value
 * @param float $longitude Longitude value
 * @return bool True if coordinates are within Tanzania bounds
 */
function isWithinTanzania($latitude, $longitude) {
    // Rough bounds for Tanzania
    return $latitude >= -11.75 && $latitude <= -0.95 && 
           $longitude >= 29.34 && $longitude <= 40.46;
}

/**
 * =====================================================
 * SECURITY FUNCTIONS
 * =====================================================
 */

/**
 * Generate secure random token
 * @param int $length Token length (default 32 characters)
 * @return string Random token
 */
function generateToken($length = 32) {
    try {
        return bin2hex(random_bytes($length / 2));
    } catch (Exception $e) {
        // Fallback for older PHP versions
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', ceil($length/36))), 0, $length);
    }
}

/**
 * Hash password securely
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get client IP address
 * @return string Client IP address
 */
function getClientIP() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * =====================================================
 * LOGGING AND ACTIVITY FUNCTIONS
 * =====================================================
 */

/**
 * Log system activity
 * @param string $action Action performed
 * @param mixed $details Additional details (array or string)
 * @param int $user_id User ID (optional)
 * @return bool True if logged successfully
 */
function logActivity($action, $details = null, $user_id = null) {
    try {
        $db = getDatabase();
        
        // Create activity_log table if it doesn't exist
        $createTable = "
            CREATE TABLE IF NOT EXISTS activity_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NULL,
                action VARCHAR(255) NOT NULL,
                details TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_action (action),
                INDEX idx_created_at (created_at),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->query($createTable);
        
        $sql = "INSERT INTO activity_log (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $user_id,
            $action,
            is_array($details) ? json_encode($details) : $details,
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $db->query($sql, $params);
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Log error with context
 * @param string $error Error message
 * @param array $context Additional context
 * @return bool True if logged successfully
 */
function logError($error, $context = []) {
    $context['timestamp'] = date('Y-m-d H:i:s');
    $context['ip'] = getClientIP();
    $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logMessage = $error . ' | Context: ' . json_encode($context);
    error_log($logMessage);
    
    return logActivity('error_occurred', $context);
}

/**
 * =====================================================
 * DATABASE HELPER FUNCTIONS
 * =====================================================
 */

/**
 * Get crime category by name or create if not exists
 * @param string $categoryName Crime category name
 * @param string $icon Icon for the category
 * @param string $color Color for the category
 * @return int|null Category ID
 */
function getCrimeCategory($categoryName, $icon = '🔍', $color = '#718096') {
    try {
        $db = getDatabase();
        
        // Try to find existing category
        $sql = "SELECT id FROM crime_categories WHERE category_name = ? AND is_active = TRUE";
        $result = $db->fetch($sql, [$categoryName]);
        
        if ($result) {
            return $result['id'];
        }
        
        // Create new category
        $sql = "INSERT INTO crime_categories (category_name, category_icon, category_color, description, is_active) 
                VALUES (?, ?, ?, ?, TRUE)";
        
        $description = "Auto-created category for: " . $categoryName;
        
        if ($db->query($sql, [$categoryName, $icon, $color, $description])) {
            return $db->lastInsertId();
        }
        
        return null;
        
    } catch (Exception $e) {
        logError("Failed to get/create crime category", ['category' => $categoryName, 'error' => $e->getMessage()]);
        return null;
    }
}

/**
 * Get system statistics
 * @return array System statistics
 */
function getSystemStats() {
    try {
        $db = getDatabase();
        
        $stats = [];
        
        // Report statistics
        $sql = "SELECT 
                    COUNT(*) as total_reports,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reports,
                    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_reports,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_reports,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_reports
                FROM incident_reports";
        
        $stats['reports'] = $db->fetch($sql);
        
        // Crime category distribution
        $sql = "SELECT cc.category_name, COUNT(*) as count 
                FROM incident_reports ir 
                JOIN crime_categories cc ON ir.crime_category_id = cc.id 
                GROUP BY cc.category_name 
                ORDER BY count DESC 
                LIMIT 5";
        
        $stats['top_crimes'] = $db->fetchAll($sql);
        
        // Location distribution
        $sql = "SELECT l.city, l.region, COUNT(*) as count 
                FROM incident_reports ir 
                JOIN locations l ON ir.location_id = l.id 
                GROUP BY l.city, l.region 
                ORDER BY count DESC 
                LIMIT 5";
        
        $stats['top_locations'] = $db->fetchAll($sql);
        
        return $stats;
        
    } catch (Exception $e) {
        logError("Failed to get system statistics", ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * =====================================================
 * UTILITY FUNCTIONS
 * =====================================================
 */

/**
 * Generate pagination array
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param int $range Number of page links to show
 * @return array Pagination data
 */
function generatePagination($current_page, $total_pages, $range = 5) {
    $pagination = [];
    
    // Calculate start and end pages
    $start = max(1, $current_page - floor($range / 2));
    $end = min($total_pages, $start + $range - 1);
    
    // Adjust start if we're near the end
    if ($end - $start + 1 < $range) {
        $start = max(1, $end - $range + 1);
    }
    
    // Previous page
    if ($current_page > 1) {
        $pagination['prev'] = $current_page - 1;
    }
    
    // Page numbers
    $pagination['pages'] = [];
    for ($i = $start; $i <= $end; $i++) {
        $pagination['pages'][] = [
            'number' => $i,
            'current' => $i == $current_page
        ];
    }
    
    // Next page
    if ($current_page < $total_pages) {
        $pagination['next'] = $current_page + 1;
    }
    
    $pagination['current'] = $current_page;
    $pagination['total'] = $total_pages;
    
    return $pagination;
}

/**
 * Convert array to CSV string
 * @param array $data Array of data
 * @param array $headers Column headers
 * @return string CSV content
 */
function arrayToCSV($data, $headers = null) {
    if (empty($data)) {
        return '';
    }
    
    $output = '';
    
    // Add headers if provided
    if ($headers) {
        $output .= implode(',', array_map(function($header) {
            return '"' . str_replace('"', '""', $header) . '"';
        }, $headers)) . "\n";
    }
    
    // Add data rows
    foreach ($data as $row) {
        $csvRow = [];
        foreach ($row as $field) {
            $csvRow[] = '"' . str_replace('"', '""', $field) . '"';
        }
        $output .= implode(',', $csvRow) . "\n";
    }
    
    return $output;
}

/**
 * Send JSON response
 * @param mixed $data Data to send
 * @param int $status_code HTTP status code
 * @param array $headers Additional headers
 */
function sendJSONResponse($data, $status_code = 200, $headers = []) {
    http_response_code($status_code);
    
    // Set default headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Set additional headers
    foreach ($headers as $key => $value) {
        header($key . ': ' . $value);
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Redirect with message
 * @param string $url URL to redirect to
 * @param string $message Flash message
 * @param string $type Message type (success, error, info)
 */
function redirectWithMessage($url, $message, $type = 'info') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    
    header('Location: ' . $url);
    exit;
}

/**
 * Get and clear flash message
 * @return array|null Flash message data
 */
function getFlashMessage() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return $message;
    }
    
    return null;
}

/**
 * Debug function (only works in development)
 * @param mixed $data Data to debug
 * @param string $label Debug label
 */
function debug($data, $label = 'DEBUG') {
    if (defined('CYBERGUARD_DEBUG') && CYBERGUARD_DEBUG) {
        echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; margin: 10px 0;'>";
        echo "<strong>$label:</strong>\n";
        print_r($data);
        echo "</pre>";
    }
}

/**
 * Check if running in CLI mode
 * @return bool True if running in CLI
 */
function isCLI() {
    return php_sapi_name() === 'cli' || defined('STDIN');
}

/**
 * Get application version
 * @return string Application version
 */
function getAppVersion() {
    return defined('DB_VERSION') ? DB_VERSION : '1.0.0';
}

/**
 * Initialize error handler
 */
function initializeErrorHandler() {
    // Set custom error handler
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        logError("PHP Error: $message", [
            'severity' => $severity,
            'file' => $file,
            'line' => $line
        ]);
        
        if (defined('CYBERGUARD_DEBUG') && CYBERGUARD_DEBUG) {
            echo "<div style='background: #fed7d7; color: #742a2a; padding: 10px; margin: 10px 0; border-left: 4px solid #fc8181;'>";
            echo "<strong>Error:</strong> $message in <strong>$file</strong> on line <strong>$line</strong>";
            echo "</div>";
        }
        
        return true;
    });
    
    // Set exception handler
    set_exception_handler(function($exception) {
        logError("Uncaught Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        if (defined('CYBERGUARD_DEBUG') && CYBERGUARD_DEBUG) {
            echo "<div style='background: #fed7d7; color: #742a2a; padding: 10px; margin: 10px 0; border-left: 4px solid #fc8181;'>";
            echo "<strong>Uncaught Exception:</strong> " . $exception->getMessage();
            echo "<br><strong>File:</strong> " . $exception->getFile();
            echo "<br><strong>Line:</strong> " . $exception->getLine();
            echo "</div>";
        } else {
            echo "An error occurred. Please try again later.";
        }
    });
}

// Initialize error handler if not in CLI mode
if (!isCLI()) {
    initializeErrorHandler();
}

?>