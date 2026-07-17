-- CREATE DATABASE lissential_manila_db;
-- Lissential Manila: Metro Traffic Incident Logger
-- Improved Database Schema
-- ============================================================================

-- USERS TABLE
-- Stores user accounts for commuters, officials, and admins
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100),
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    
    -- Role-based access control: Student (regular user), Official (LGU/MMDA), Admin (system)
    role ENUM('Student', 'Official', 'Admin') NOT NULL DEFAULT 'Student',
    
    -- For Officials: which area/district they manage (optional)
    assigned_area VARCHAR(100),
    
    -- Soft delete: 1 = deleted, 0 = active
    is_deleted BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for faster queries
    INDEX idx_username (username),
    INDEX idx_phone_number (phone_number),
    INDEX idx_role (role)
);

-- CATEGORIES TABLE
-- Centralized list of incident types (better than ENUM for flexibility)
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    
    -- Examples: 'Car Crash', 'Flooding', 'Road Blockage', etc.
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- INCIDENT_THREADS TABLE
-- Groups related reports about the same incident at the same location
-- Think: "EDSA Traffic Incident on July 17" contains multiple reports
CREATE TABLE incident_threads (
    thread_id INT AUTO_INCREMENT PRIMARY KEY,
    
    title VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    
    -- FK to categories
    category_id INT NOT NULL,
    
    -- Status: Active (ongoing), Resolved (fixed), Archived (historical)
    status ENUM('Active', 'Resolved', 'Archived') NOT NULL DEFAULT 'Active',
    
    -- Metadata
    total_reports INT DEFAULT 0,
    verified_reports INT DEFAULT 0,
    unverified_reports INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    INDEX idx_location (location),
    INDEX idx_status (status),
    INDEX idx_category (category_id)
);


-- INCIDENT_REPORTS TABLE
-- Individual reports submitted by users
-- Multiple reports can belong to one thread
CREATE TABLE incident_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Who submitted this report
    user_id INT NOT NULL,
    
    -- Which thread does this belong to? (nullable until verified by official)
    thread_id INT,
    
    -- Report content
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    
    -- When did the incident happen?
    incident_date_time DATETIME NOT NULL,
    
    -- FK to categories
    category_id INT NOT NULL,
    
    -- Verification status
    -- Pending: Not verified by official yet
    -- Verified: Official confirmed this report
    -- Resolved: The incident has been resolved
    -- Rejected: Official determined this is false/irrelevant
    status ENUM('Pending', 'Verified', 'Resolved', 'Rejected') NOT NULL DEFAULT 'Pending',
    
    -- Optional: Why was this report rejected or resolved?
    verification_remarks TEXT,
    
    -- Who verified/rejected this report?
    verified_by INT,
    verified_at TIMESTAMP NULL,
    
    -- Engagement metrics
    upvote_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    
    -- Soft delete
    is_deleted BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (thread_id) REFERENCES incident_threads(thread_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (verified_by) REFERENCES users(user_id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_thread_id (thread_id),
    INDEX idx_status (status),
    INDEX idx_location (location),
    INDEX idx_category (category_id)
);


-- MEDIA_ATTACHMENTS TABLE
-- Photos/videos attached to reports
CREATE TABLE media_attachments (
    media_id INT AUTO_INCREMENT PRIMARY KEY,
    
    report_id INT NOT NULL,
    
    -- Where the file is stored (URL or file path)
    file_url VARCHAR(500) NOT NULL,
    
    -- File type: 'image/jpeg', 'image/png', 'video/mp4', etc.
    file_type VARCHAR(50),
    
    -- File size in bytes (for validation)
    file_size INT,
    
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (report_id) REFERENCES incident_reports(report_id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id)
);


-- COMMENTS TABLE
-- Comments on individual reports
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Who posted this comment
    user_id INT NOT NULL,
    
    -- Which report is this comment on
    report_id INT NOT NULL,
    
    content TEXT NOT NULL,
    
    is_deleted BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (report_id) REFERENCES incident_reports(report_id) ON DELETE CASCADE,
    
    INDEX idx_report_id (report_id),
    INDEX idx_user_id (user_id)
);


-- THREAD_COMMENTS TABLE
-- Comments at the thread level (separate from individual report comments)
CREATE TABLE thread_comments (
    thread_comment_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Who posted this comment
    user_id INT NOT NULL,
    
    -- Which thread is this comment on
    thread_id INT NOT NULL,
    
    content TEXT NOT NULL,
    
    is_deleted BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (thread_id) REFERENCES incident_threads(thread_id) ON DELETE CASCADE,
    
    INDEX idx_thread_id (thread_id),
    INDEX idx_user_id (user_id)
);


-- UPVOTES TABLE
-- Track which users upvoted which reports
-- Using a junction table prevents duplicate upvotes
CREATE TABLE upvotes (
    upvote_id INT AUTO_INCREMENT PRIMARY KEY,
    
    user_id INT NOT NULL,
    report_id INT NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Prevent duplicate upvotes: one user can only upvote a report once
    UNIQUE KEY unique_upvote (user_id, report_id),
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (report_id) REFERENCES incident_reports(report_id) ON DELETE CASCADE,
    
    INDEX idx_report_id (report_id)
);


-- THREAD_UPVOTES TABLE
-- Track upvotes on threads (separate from report upvotes)
CREATE TABLE thread_upvotes (
    thread_upvote_id INT AUTO_INCREMENT PRIMARY KEY,
    
    user_id INT NOT NULL,
    thread_id INT NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Prevent duplicate upvotes
    UNIQUE KEY unique_thread_upvote (user_id, thread_id),
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (thread_id) REFERENCES incident_threads(thread_id) ON DELETE CASCADE,
    
    INDEX idx_thread_id (thread_id)
);


-- SAVED_ROUTES TABLE
-- Users can save routes to receive notifications for nearby incidents
CREATE TABLE saved_routes (
    saved_route_id INT AUTO_INCREMENT PRIMARY KEY,
    
    user_id INT NOT NULL,
    
    route_name VARCHAR(100),
    -- Store as comma-separated locations or coordinates
    locations TEXT NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id)
);


-- NOTIFICATIONS TABLE
-- Track notifications sent to users about incidents near their routes
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    
    user_id INT NOT NULL,
    thread_id INT NOT NULL,
    
    message TEXT NOT NULL,
    
    -- Has the user seen this notification?
    is_read BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (thread_id) REFERENCES incident_threads(thread_id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
);


-- AUDIT_LOG TABLE (Optional but recommended)
-- Track admin/official actions for security and compliance
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Who performed the action?
    user_id INT NOT NULL,
    
    -- What action? (verify_report, reject_report, create_thread, etc.)
    action VARCHAR(100) NOT NULL,
    
    -- Which entity was affected? (report_id, thread_id, etc.)
    entity_type VARCHAR(50),
    entity_id INT,
    
    -- Details about the change
    description TEXT,
    
    ip_address VARCHAR(45),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);


-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Insert incident categories
INSERT INTO categories (category_name, description) VALUES
('Car Crash', 'Vehicle accident or collision'),
('Traffic Congestion', 'Heavy traffic or gridlock'),
('Flooding', 'Water overflow or flooded roads'),
('Road Blockage', 'Road closure or obstruction'),
('Construction', 'Road work or construction activity'),
('Stalled Vehicle', 'Broken down or disabled vehicle'),
('Debris', 'Debris, potholes, or road damage'),
('Traffic Light', 'Traffic signal malfunction'),
('Public Transport', 'MRT/LRT/bus disruption'),
('Other', 'Other incidents not listed above');