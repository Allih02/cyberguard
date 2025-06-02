-- =====================================================
-- CyberGuard - Advanced Cybercrime Mapping System
-- Database Schema for MySQL/MariaDB
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS cyberguard_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE cyberguard_db;

-- =====================================================
-- 1. CRIME CATEGORIES TABLE
-- Stores predefined crime types for consistency
-- =====================================================
CREATE TABLE crime_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_icon VARCHAR(20) DEFAULT NULL,
    category_color VARCHAR(7) DEFAULT '#718096',
    description TEXT,
    severity_level ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. USERS TABLE
-- Stores reporter information (optional registration)
-- =====================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    user_type ENUM('reporter', 'admin', 'law_enforcement') DEFAULT 'reporter',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    password_hash VARCHAR(255) NULL, -- For registered users
    verification_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_user_type (user_type),
    INDEX idx_active (is_active)
);

-- =====================================================
-- 3. LOCATIONS TABLE
-- Stores geographical information for incidents
-- =====================================================
CREATE TABLE locations (
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
);

-- =====================================================
-- 4. INCIDENT REPORTS TABLE
-- Main table storing all cybercrime incident reports
-- =====================================================
CREATE TABLE incident_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_number VARCHAR(20) UNIQUE NOT NULL, -- Auto-generated: CG-YYYY-NNNNNN
    
    -- Reporter Information
    reporter_name VARCHAR(255) NOT NULL,
    reporter_email VARCHAR(255),
    reporter_phone VARCHAR(20),
    user_id INT NULL, -- Link to users table if registered
    
    -- Incident Details
    crime_category_id INT NOT NULL,
    custom_crime_type VARCHAR(100) NULL, -- For "Other" category
    incident_title VARCHAR(255),
    incident_description TEXT NOT NULL,
    incident_date DATE,
    incident_time TIME,
    
    -- Location Information
    location_id INT NOT NULL,
    
    -- Financial Impact
    estimated_loss DECIMAL(15, 2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'TZS',
    
    -- Evidence and Attachments
    evidence_description TEXT,
    has_screenshots BOOLEAN DEFAULT FALSE,
    has_documents BOOLEAN DEFAULT FALSE,
    
    -- Status and Processing
    status ENUM('pending', 'under_review', 'investigating', 'resolved', 'closed', 'duplicate') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    assigned_to INT NULL, -- Link to admin/law enforcement user
    
    -- Investigation Details
    investigation_notes TEXT,
    resolution_notes TEXT,
    resolution_date TIMESTAMP NULL,
    
    -- System Fields
    ip_address VARCHAR(45), -- IPv4 or IPv6
    user_agent TEXT,
    submission_source VARCHAR(50) DEFAULT 'web_form',
    is_anonymous BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE, -- Show on public map
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (crime_category_id) REFERENCES crime_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for Performance
    INDEX idx_report_number (report_number),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_crime_category (crime_category_id),
    INDEX idx_created_date (created_at),
    INDEX idx_reporter_email (reporter_email),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_public_reports (is_public, status),
    INDEX idx_location (location_id)
);

-- =====================================================
-- 5. REPORT ATTACHMENTS TABLE
-- Stores file attachments for incident reports
-- =====================================================
CREATE TABLE report_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100),
    file_size INT, -- Size in bytes
    mime_type VARCHAR(100),
    attachment_type ENUM('screenshot', 'document', 'audio', 'video', 'other') DEFAULT 'other',
    description TEXT,
    is_evidence BOOLEAN DEFAULT FALSE,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (report_id) REFERENCES incident_reports(id) ON DELETE CASCADE,
    INDEX idx_report_attachments (report_id),
    INDEX idx_attachment_type (attachment_type)
);

-- =====================================================
-- 6. REPORT UPDATES TABLE
-- Tracks all updates/changes to incident reports
-- =====================================================
CREATE TABLE report_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    updated_by INT, -- User who made the update
    update_type ENUM('status_change', 'assignment', 'note_added', 'priority_change', 'other') NOT NULL,
    old_value VARCHAR(255),
    new_value VARCHAR(255),
    update_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (report_id) REFERENCES incident_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_report_updates (report_id),
    INDEX idx_update_date (created_at)
);

-- =====================================================
-- 7. ADMIN SETTINGS TABLE
-- System configuration and settings
-- =====================================================
CREATE TABLE admin_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE, -- Can be accessed by non-admin users
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
);

-- =====================================================
-- 8. STATISTICS TABLE
-- Pre-calculated statistics for dashboard performance
-- =====================================================
CREATE TABLE statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stat_date DATE NOT NULL,
    total_reports INT DEFAULT 0,
    pending_reports INT DEFAULT 0,
    resolved_reports INT DEFAULT 0,
    reports_by_category JSON, -- {"Identity Theft": 5, "Phishing": 3, ...}
    reports_by_region JSON,
    average_resolution_time INT, -- In hours
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_stat_date (stat_date),
    INDEX idx_stat_date (stat_date)
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default crime categories
INSERT INTO crime_categories (category_name, category_icon, category_color, description, severity_level) VALUES
('Identity Theft', '🆔', '#e53e3e', 'Unauthorized use of personal information', 'High'),
('Online Fraud', '💳', '#dd6b20', 'Financial fraud conducted online', 'High'),
('Phishing', '🎣', '#d69e2e', 'Fraudulent attempts to obtain sensitive information', 'Medium'),
('Ransomware', '🔒', '#9f7aea', 'Malicious software that encrypts files for ransom', 'Critical'),
('Cyberbullying', '😢', '#ed64a6', 'Harassment or bullying using digital platforms', 'Medium'),
('Data Breach', '📊', '#38b2ac', 'Unauthorized access to confidential data', 'Critical'),
('Social Engineering', '🕵️', '#4299e1', 'Manipulation to divulge confidential information', 'High'),
('Malware', '🦠', '#f56565', 'Malicious software designed to damage systems', 'High'),
('DDoS Attack', '⚡', '#48bb78', 'Distributed Denial of Service attacks', 'Medium'),
('Other', '🔍', '#718096', 'Other types of cybercrime not listed above', 'Medium');

-- Insert default admin user
INSERT INTO users (full_name, email, user_type, password_hash, is_verified, is_active) VALUES
('System Administrator', 'admin@cyberguard.co.tz', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE);

-- Insert default admin settings
INSERT INTO admin_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'CyberGuard - Advanced Cybercrime Mapping System', 'string', 'Website title', TRUE),
('reports_per_page', '20', 'integer', 'Number of reports to display per page', FALSE),
('auto_assign_reports', 'false', 'boolean', 'Automatically assign new reports to available investigators', FALSE),
('public_map_enabled', 'true', 'boolean', 'Show incidents on public map', TRUE),
('email_notifications', 'true', 'boolean', 'Send email notifications for new reports', FALSE),
('max_file_upload_size', '10485760', 'integer', 'Maximum file upload size in bytes (10MB)', FALSE),
('allowed_file_types', '["jpg", "jpeg", "png", "pdf", "doc", "docx", "txt"]', 'json', 'Allowed file extensions for uploads', FALSE);

-- =====================================================
-- USEFUL VIEWS FOR REPORTING
-- =====================================================

-- View for public map display (only resolved and non-sensitive reports)
CREATE VIEW public_reports AS
SELECT 
    ir.id,
    ir.report_number,
    cc.category_name,
    cc.category_color,
    ir.incident_description,
    ir.reporter_name,
    l.latitude,
    l.longitude,
    l.city,
    l.region,
    ir.created_at,
    ir.estimated_loss
FROM incident_reports ir
JOIN crime_categories cc ON ir.crime_category_id = cc.id
JOIN locations l ON ir.location_id = l.id
WHERE ir.is_public = TRUE 
AND ir.status NOT IN ('pending', 'duplicate')
ORDER BY ir.created_at DESC;

-- View for dashboard statistics
CREATE VIEW dashboard_stats AS
SELECT 
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
    SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_reports,
    SUM(estimated_loss) as total_estimated_loss,
    AVG(estimated_loss) as avg_estimated_loss
FROM incident_reports
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- View for recent reports with full details
CREATE VIEW recent_reports_detailed AS
SELECT 
    ir.id,
    ir.report_number,
    ir.reporter_name,
    ir.reporter_email,
    cc.category_name,
    cc.category_color,
    ir.incident_description,
    ir.status,
    ir.priority,
    ir.estimated_loss,
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
ORDER BY ir.created_at DESC;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Procedure to generate unique report numbers
DELIMITER //
CREATE PROCEDURE GenerateReportNumber(OUT report_num VARCHAR(20))
BEGIN
    DECLARE next_id INT;
    DECLARE year_part VARCHAR(4);
    
    SET year_part = YEAR(CURDATE());
    
    SELECT COALESCE(MAX(id), 0) + 1 INTO next_id FROM incident_reports;
    
    SET report_num = CONCAT('CG-', year_part, '-', LPAD(next_id, 6, '0'));
END //
DELIMITER ;

-- Procedure to update statistics
DELIMITER //
CREATE PROCEDURE UpdateDailyStatistics()
BEGIN
    DECLARE today_date DATE DEFAULT CURDATE();
    
    INSERT INTO statistics (
        stat_date, 
        total_reports, 
        pending_reports, 
        resolved_reports,
        reports_by_category,
        reports_by_region
    )
    SELECT 
        today_date,
        COUNT(*) as total_reports,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports,
        JSON_OBJECT() as reports_by_category, -- Simplified for now
        JSON_OBJECT() as reports_by_region    -- Simplified for now
    FROM incident_reports 
    WHERE DATE(created_at) = today_date
    ON DUPLICATE KEY UPDATE
        total_reports = VALUES(total_reports),
        pending_reports = VALUES(pending_reports),
        resolved_reports = VALUES(resolved_reports);
END //
DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger to auto-generate report numbers
DELIMITER //
CREATE TRIGGER before_insert_incident_report
BEFORE INSERT ON incident_reports
FOR EACH ROW
BEGIN
    DECLARE new_report_num VARCHAR(20);
    
    IF NEW.report_number IS NULL OR NEW.report_number = '' THEN
        CALL GenerateReportNumber(new_report_num);
        SET NEW.report_number = new_report_num;
    END IF;
END //
DELIMITER ;

-- Trigger to log report updates
DELIMITER //
CREATE TRIGGER after_update_incident_report
AFTER UPDATE ON incident_reports
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO report_updates (report_id, update_type, old_value, new_value, update_notes)
        VALUES (NEW.id, 'status_change', OLD.status, NEW.status, 'Status automatically updated');
    END IF;
    
    IF OLD.priority != NEW.priority THEN
        INSERT INTO report_updates (report_id, update_type, old_value, new_value, update_notes)
        VALUES (NEW.id, 'priority_change', OLD.priority, NEW.priority, 'Priority automatically updated');
    END IF;
END //
DELIMITER ;

-- =====================================================
-- INDEXES FOR OPTIMIZATION
-- =====================================================

-- Additional indexes for common queries
CREATE INDEX idx_reports_date_range ON incident_reports(created_at, status);
CREATE INDEX idx_reports_location_public ON incident_reports(location_id, is_public);
CREATE INDEX idx_categories_active ON crime_categories(is_active, category_name);

-- =====================================================
-- SAMPLE DATA FOR TESTING (Optional)
-- =====================================================

-- Sample locations (major Tanzanian cities)
INSERT INTO locations (latitude, longitude, city, region, country) VALUES
(-6.7924, 39.2083, 'Dar es Salaam', 'Dar es Salaam', 'Tanzania'),
(-3.3869, 36.6830, 'Arusha', 'Arusha', 'Tanzania'),
(-8.7832, 34.5085, 'Mbeya', 'Mbeya', 'Tanzania'),
(-5.0893, 39.2658, 'Tanga', 'Tanga', 'Tanzania'),
(-4.0435, 39.6682, 'Malindi', 'Kilifi', 'Tanzania'),
(-6.1659, 35.7497, 'Dodoma', 'Dodoma', 'Tanzania');

-- =====================================================
-- DATABASE MAINTENANCE
-- =====================================================

-- Events for automatic cleanup (if MySQL Event Scheduler is enabled)
-- Clean up old temporary data every day at 2 AM
-- SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS daily_maintenance
ON SCHEDULE EVERY 1 DAY STARTS '2025-01-01 02:00:00'
DO
BEGIN
    -- Update daily statistics
    CALL UpdateDailyStatistics();
    
    -- Clean up old unverified users (older than 30 days)
    DELETE FROM users 
    WHERE is_verified = FALSE 
    AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND user_type = 'reporter';
    
    -- Archive old resolved reports (optional - move to archive table)
    -- This would require creating an archive table structure
END;