-- CREATE DATABASE lissential_manila_db;

-- LOCATIONS TABLE : Centralized locations (city + district combinations)
-- NOTES: DISTRICT can also be BARANGAY. Data is fixed by 'locations.sql'
CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(50) NOT NULL,
    district VARCHAR(100) NOT NULL,
    landmark VARCHAR(100), -- optional / if applicable
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Prevent duplicate city-district pairs
    UNIQUE KEY unique_location (city, district)
);

-- SAVED_LOCATIONS TABLE : Users can save additional locations for notifications
CREATE TABLE saved_locations (
    saved_location_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    location_id INT NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Prevent duplicate saves: one user can only save a location once
    UNIQUE KEY unique_saved_location (user_id, location_id),
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE
);

-- CATEGORIES TABLE : Centralized list of incident types
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE, 
    requires_description BOOLEAN DEFAULT FALSE, -- Only Other requires description
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert incident categories
INSERT INTO categories (category_name, requires_description) VALUES
('Vehicle Accident', FALSE),
('Traffic Congestion', FALSE),
('Flooding', FALSE),
('Road Blockage', FALSE),
('Construction', FALSE),
('Stalled Vehicle', FALSE),
('Traffic Light', FALSE),
('Public Transport', FALSE),
('Other', TRUE);

-- USERS TABLE : Stores user accounts for commuters, officials, and admins
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100),
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    
    -- User Level / Role: Student (commuters/drivers), Official (LGU/MMDA), Admin
    role ENUM('Student', 'Official', 'Admin') NOT NULL DEFAULT 'Student',
    
    -- For Officials: which area/district they manage
    assigned_area VARCHAR(100),
    
    -- For all users: home location (where they get notifications from)
    home_location_id INT NOT NULL,
    
    -- Phone re-verification: required whenever phone_number is changed
    pending_phone_number VARCHAR(20), -- new number awaiting verification
    is_phone_verified BOOLEAN DEFAULT TRUE, -- FALSE while pending_phone_number is unverified
    phone_verification_code VARCHAR(10),
    phone_verification_expires_at TIMESTAMP NULL,
    
    is_deleted BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (home_location_id) REFERENCES locations(location_id)
);

-- THREADS TABLE : Groups related reports about the same incident at the same location
-- - Only 'Official' or 'Admin' roles can CREATE threads
-- - Only 'Official' or 'Admin' can ADD/REMOVE reports from threads
-- - Users can only SUBMIT reports (not create threads)
CREATE TABLE threads (
    thread_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    location_id INT NOT NULL,
    category_id INT NOT NULL,
    
    created_by INT NOT NULL, -- must be Official or Admin    
    status ENUM('Active', 'Resolved', 'Archived') NOT NULL DEFAULT 'Active',
    
    description TEXT, -- Optional: Description/remarks from the official explaining the incident
    
    -- Metadata (keep in sync via triggers or recalculate on queries)
    total_reports INT DEFAULT 0,
    verified_reports INT DEFAULT 0,
    unverified_reports INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT
);

-- REPORTS TABLE : Individual reports submitted by users (Multiple reports can belong to one thread)
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- user who submitted this report
    thread_id INT, -- which thread this belongs to (if applicable)
    location_id INT NOT NULL, -- FK to locations table
    category_id INT NOT NULL,

    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    
    -- Engagement metrics
    upvote_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
 
    -- Verification status
    status ENUM('Pending', 'Verified', 'Resolved', 'Rejected') NOT NULL DEFAULT 'Pending',
    verification_remarks TEXT, -- Optional: why was this report rejected or resolved?
    verified_by INT, -- official that verified this report
    verified_at TIMESTAMP NULL,
        
    is_deleted BOOLEAN DEFAULT FALSE, -- Soft delete
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (thread_id) REFERENCES threads(thread_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (verified_by) REFERENCES users(user_id)
);

-- MEDIA_ATTACHMENTS TABLE
-- Photos/videos attached to reports
CREATE TABLE media_attachments (
    media_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_type ENUM('photo', 'video') DEFAULT 'photo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE CASCADE
);

-- COMMENTS TABLE
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- user who posted the comment
    report_id INT NOT NULL, -- which report is this comment on
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE, -- Soft delete

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE RESTRICT
);

-- UPVOTES TABLE : Track which users upvoted which reports (Using a junction table prevents duplicate upvotes)
CREATE TABLE upvotes (
    upvote_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Prevent duplicate upvotes: one user can only upvote a report once
    UNIQUE KEY unique_upvote (user_id, report_id),
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE CASCADE
);

-- NOTIFICATIONS TABLE : Track notifications sent to users about incidents near their routes
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    thread_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    -- Prevent duplicate notifications for the same user/thread
    UNIQUE KEY unique_notification (user_id, thread_id),
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (thread_id) REFERENCES threads(thread_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
DELIMITER $$

-- Notifies users whose HOME location or any SAVED location matches the new thread's location
CREATE TRIGGER notify_users_same_location 
AFTER INSERT ON threads
FOR EACH ROW
BEGIN
    INSERT IGNORE INTO notifications (user_id, thread_id)
    SELECT user_id FROM users
    WHERE home_location_id = NEW.location_id AND is_deleted = FALSE;

    INSERT IGNORE INTO notifications (user_id, thread_id)
    SELECT user_id FROM saved_locations
    WHERE location_id = NEW.location_id;
END$$

DELIMITER ;
-- ------------------------------------------------------------------------------


-- ADVISORIES TABLE : Official traffic advisories posted by LGU/MMDA officials
CREATE TABLE advisories (
    advisory_id INT AUTO_INCREMENT PRIMARY KEY,
    posted_by INT NOT NULL, -- must be Official or Admin
    location_id INT, -- optional: advisory can be city-wide (NULL) or location-specific

    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,

    is_active BOOLEAN DEFAULT TRUE, -- soft delete / unpublish

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (posted_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

-- AUDIT_LOG TABLE : Track admin/official actions for security and compliance
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,

    -- What action?
    action ENUM(
        
    -- Report Actions
    'Create Report',
    'Edit Report',
    'Verify Report',
    'Reject Report',
    'Resolve Report',
    'Delete Report',
    
    -- Thread Actions
    'Create Thread',
    'Update Thread',
    'Archive Thread',
    'Merge Thread',
    'Add Post to Thread',
    
    -- User Management
    'Create User',
    'Edit User',
    'Delete User',
    'Change User Role',
    'Assign Area', -- for admin
    
    -- Advisory Actions
    'Post Advisory',
    'Edit Advisory',
    'Delete Advisory',
    
    -- Moderation
    'Flag Report',
    'Delete Comment',
    'Suspend User',
    'Ban User',
    'Restore User'),
    
    -- Which entity was affected? (report_id, thread_id, etc.)
    entity_type VARCHAR(50),
    entity_id INT,
    
    -- Details about the change
    description TEXT,

    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT
);