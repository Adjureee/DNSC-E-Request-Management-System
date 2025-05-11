-- Create database
CREATE DATABASE IF NOT EXISTS `dnsc_E-Request`;
USE `dnsc_E-Request`;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stud_id VARCHAR(50),
    full_name VARCHAR(100),
    institute VARCHAR(100),
    program VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(255),
    role ENUM('student', 'alumni', 'admin') DEFAULT NULL,
    pre_select_role ENUM('student', 'alumni') DEFAULT NULL,
    uploadphoto VARCHAR(255),
    verification_status ENUM('pending', 'approved_student', 'approved_alumni', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    rejected_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);




-- Requests table
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_type VARCHAR(100) NOT NULL,
    institute VARCHAR(50) DEFAULT NULL,
    program VARCHAR(50) DEFAULT NULL,
    year_level VARCHAR(50) DEFAULT NULL,
    semester VARCHAR(50) DEFAULT NULL,
    details TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    tracking_number VARCHAR(20) DEFAULT NULL,
    pickup_datetime DATETIME DEFAULT NULL,
    is_seen BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS alumni_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_type VARCHAR(100) NOT NULL,
    institute VARCHAR(50) DEFAULT NULL,
    program VARCHAR(50) DEFAULT NULL,
    details TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    tracking_number VARCHAR(20) DEFAULT NULL,
    pickup_datetime DATETIME DEFAULT NULL,
    is_seen BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin account
INSERT INTO users (stud_id, password, email, full_name, verification_status, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@dnsc.edu.ph', 'System Administrator', 'approved_student', 'admin');
-- Note: Default password is 'password'
