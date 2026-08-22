<?php
require_once 'config.php';

echo "<h2>Setting up Camera Attendance System</h2>";

$database = new Database();
$db = $database->getConnection();

// Create camera_attendance table if not exists
$camera_attendance_sql = "
CREATE TABLE IF NOT EXISTS camera_attendance (
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
)";

try {
    $db->exec($camera_attendance_sql);
    echo "<p class='text-success'>✓ Camera attendance table created successfully</p>";
} catch (PDOException $e) {
    echo "<p class='text-danger'>✗ Error creating camera_attendance table: " . $e->getMessage() . "</p>";
}

// Create attendance_notifications table if not exists
$notifications_sql = "
CREATE TABLE IF NOT EXISTS attendance_notifications (
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
)";

try {
    $db->exec($notifications_sql);
    echo "<p class='text-success'>✓ Attendance notifications table created successfully</p>";
} catch (PDOException $e) {
    echo "<p class='text-danger'>✗ Error creating attendance_notifications table: " . $e->getMessage() . "</p>";
}

// Check if attendance table exists, if not create basic version
$attendance_sql = "
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    total_hours DECIMAL(4,2),
    overtime DECIMAL(4,2),
    status ENUM('present', 'absent', 'late', 'on_leave') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    INDEX idx_employee_date (employee_id, date),
    INDEX idx_status (status)
)";

try {
    $db->exec($attendance_sql);
    echo "<p class='text-success'>✓ Attendance table created successfully</p>";
} catch (PDOException $e) {
    echo "<p class='text-danger'>✗ Error creating attendance table: " . $e->getMessage() . "</p>";
}

// Add attendance_notifications column to users table if not exists
try {
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'attendance_notifications'");
    if ($result->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN attendance_notifications BOOLEAN DEFAULT TRUE");
        echo "<p class='text-success'>✓ Added attendance_notifications column to users table</p>";
    } else {
        echo "<p class='text-info'>ℹ attendance_notifications column already exists in users table</p>";
    }
} catch (PDOException $e) {
    echo "<p class='text-danger'>✗ Error adding attendance_notifications column: " . $e->getMessage() . "</p>";
}

// Create directories if they don't exist
$dirs = ['public/attendance_photos', 'public/attendance_videos'];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "<p class='text-success'>✓ Created directory: $dir</p>";
        } else {
            echo "<p class='text-danger'>✗ Failed to create directory: $dir</p>";
        }
    } else {
        echo "<p class='text-info'>ℹ Directory already exists: $dir</p>";
    }
}

echo "<h3>Setup Complete!</h3>";
echo "<p><a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a></p>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Camera Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        
    </div>
</body>
</html>
