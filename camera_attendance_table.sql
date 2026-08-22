-- Add camera attendance table to existing database
USE mcpillab;

-- Camera Attendance table for storing photo-based attendance records
CREATE TABLE camera_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    capture_date DATE NOT NULL,
    capture_time TIME NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    location_address TEXT,
    azimuth VARCHAR(10),
    temperature DECIMAL(5,2),
    device_info TEXT,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
    team_id INT DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    INDEX idx_employee_date (employee_id, capture_date),
    INDEX idx_verification (verification_status),
    INDEX idx_sync (sync_status)
);
