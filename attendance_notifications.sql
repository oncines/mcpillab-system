-- Add attendance notifications table for real-time admin updates
CREATE TABLE attendance_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    camera_attendance_id INT NOT NULL,
    notification_type ENUM('new_attendance', 'late_arrival', 'location_verification') DEFAULT 'new_attendance',
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (camera_attendance_id) REFERENCES camera_attendance(id) ON DELETE CASCADE,
    INDEX idx_unread_notifications (is_read, created_at)
);

-- Add notification settings for admin users
ALTER TABLE users ADD COLUMN attendance_notifications BOOLEAN DEFAULT TRUE;
