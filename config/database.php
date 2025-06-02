<?php
/**
 * =====================================================
 * CyberGuard Database Configuration
 * File: config/database.php
 * 
 * Central database configuration and connection management
 * for the CyberGuard Cybercrime Mapping System
 * =====================================================
 */

// Prevent direct access
if (!defined('CYBERGUARD_SYSTEM')) {
    define('CYBERGUARD_SYSTEM', true);
}

/**
 * Database Configuration Settings
 * 
 * Configure these settings according to your environment:
 * - Development: localhost with default credentials
 * - Production: secure credentials with proper host
 * - Testing: separate test database
 */

// Environment Detection
$environment = $_SERVER['SERVER_NAME'] ?? 'localhost';
$isProduction = !in_array($environment, ['localhost', '127.0.0.1', 'cyberguard.local']);
$isDevelopment = !$isProduction;

// Database Configuration Arrays
$database_config = [
    'development' => [
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'cyberguard_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    ],
    
    'production' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'dbname' => $_ENV['DB_NAME'] ?? 'cyberguard_production',
        'username' => $_ENV['DB_USER'] ?? '',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    ],
    
    'testing' => [
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'cyberguard_test',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    ]
];

// Select configuration based on environment
$current_env = $isProduction ? 'production' : 'development';
$db_config = $database_config[$current_env];

/**
 * Database Connection Class
 * 
 * Singleton pattern implementation for database connections
 * Provides connection pooling and error handling
 */
class DatabaseConnection {
    private static $instance = null;
    private $connection = null;
    private $config = null;
    private $connection_attempts = 0;
    private $max_attempts = 3;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct($config) {
        $this->config = $config;
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance($config = null) {
        if (self::$instance === null) {
            if ($config === null) {
                throw new Exception('Database configuration required for first connection');
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection with retry logic
     */
    private function connect() {
        $this->connection_attempts++;
        
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['port'],
                $this->config['dbname'],
                $this->config['charset']
            );
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
            // Test connection
            $this->connection->query('SELECT 1');
            
            // Log successful connection (in development)
            if (defined('CYBERGUARD_DEBUG') && CYBERGUARD_DEBUG) {
                error_log("CyberGuard DB: Successfully connected to " . $this->config['dbname']);
            }
            
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }
    
    /**
     * Handle connection errors with retry logic
     */
    private function handleConnectionError($exception) {
        $error_message = "Database connection failed: " . $exception->getMessage();
        
        // Log the error
        error_log("CyberGuard DB Error (Attempt {$this->connection_attempts}): " . $error_message);
        
        // Retry connection if not exceeded max attempts
        if ($this->connection_attempts < $this->max_attempts) {
            sleep(1); // Wait 1 second before retry
            $this->connect();
            return;
        }
        
        // If all attempts failed, throw exception
        throw new Exception($error_message . " (After {$this->max_attempts} attempts)");
    }
    
    /**
     * Get the PDO connection
     */
    public function getConnection() {
        // Check if connection is still alive
        if ($this->connection === null) {
            $this->connect();
        }
        
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            // Connection lost, reconnect
            $this->connection = null;
            $this->connection_attempts = 0;
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Execute a prepared statement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("CyberGuard Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->getConnection()->rollback();
    }
    
    /**
     * Check if we're in a transaction
     */
    public function inTransaction() {
        return $this->getConnection()->inTransaction();
    }
    
    /**
     * Get database configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $result = $this->fetch("SELECT 'CyberGuard DB Connection Test' as test_message, NOW() as test_time");
            return [
                'success' => true,
                'message' => $result['test_message'],
                'timestamp' => $result['test_time'],
                'database' => $this->config['dbname'],
                'host' => $this->config['host']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get database statistics
     */
    public function getDatabaseStats() {
        try {
            $stats = [];
            
            // Get table information
            $tables = $this->fetchAll("SHOW TABLE STATUS");
            $stats['tables'] = [];
            $total_size = 0;
            
            foreach ($tables as $table) {
                $table_size = $table['Data_length'] + $table['Index_length'];
                $stats['tables'][$table['Name']] = [
                    'rows' => $table['Rows'],
                    'size' => $table_size,
                    'size_formatted' => $this->formatBytes($table_size)
                ];
                $total_size += $table_size;
            }
            
            $stats['total_size'] = $total_size;
            $stats['total_size_formatted'] = $this->formatBytes($total_size);
            $stats['table_count'] = count($tables);
            
            // Get MySQL version
            $version = $this->fetch("SELECT VERSION() as version");
            $stats['mysql_version'] = $version['version'];
            
            return $stats;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper Functions
 */

/**
 * Get database connection instance
 */
function getDatabase() {
    global $db_config;
    return DatabaseConnection::getInstance($db_config);
}

/**
 * Execute a database query
 */
function dbQuery($sql, $params = []) {
    return getDatabase()->query($sql, $params);
}

/**
 * Fetch single row from database
 */
function dbFetch($sql, $params = []) {
    return getDatabase()->fetch($sql, $params);
}

/**
 * Fetch all rows from database
 */
function dbFetchAll($sql, $params = []) {
    return getDatabase()->fetchAll($sql, $params);
}

/**
 * Get last insert ID
 */
function dbLastInsertId() {
    return getDatabase()->lastInsertId();
}

/**
 * Database Health Check
 */
function checkDatabaseHealth() {
    try {
        $db = getDatabase();
        $test_result = $db->testConnection();
        
        if ($test_result['success']) {
            return [
                'status' => 'healthy',
                'message' => 'Database connection is working properly',
                'details' => $test_result
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $test_result['error']
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'critical',
            'message' => 'Cannot establish database connection',
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Initialize Database Tables (for first-time setup)
 */
function initializeDatabaseTables() {
    try {
        $db = getDatabase();
        
        // Check if tables exist
        $tables = $db->fetchAll("SHOW TABLES LIKE 'incident_reports'");
        
        if (empty($tables)) {
            // Tables don't exist, need to run schema
            return [
                'status' => 'setup_required',
                'message' => 'Database tables need to be created. Please run the database schema SQL file.'
            ];
        }
        
        return [
            'status' => 'ready',
            'message' => 'Database tables are properly initialized'
        ];
        
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Cannot check database initialization',
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Configuration Constants
 */
define('DB_PREFIX', 'cyberguard_');
define('DB_VERSION', '1.0.0');
define('DB_CHARSET', 'utf8mb4');

// Development debug mode
if ($isDevelopment) {
    define('CYBERGUARD_DEBUG', true);
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    define('CYBERGUARD_DEBUG', false);
    ini_set('display_errors', 0);
    error_reporting(0);
}

/**
 * Export configuration for use in other files
 */
return [
    'config' => $db_config,
    'environment' => $current_env,
    'debug' => defined('CYBERGUARD_DEBUG') ? CYBERGUARD_DEBUG : false,
    'version' => DB_VERSION
];

/**
 * Auto-initialize connection (optional)
 * Uncomment the line below to establish connection immediately
 */
// $cyberguard_db = getDatabase();

?>