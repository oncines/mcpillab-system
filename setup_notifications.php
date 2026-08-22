<?php
require_once 'config.php';

// Create attendance notifications table
$database = new Database();
$db = $database->getConnection();

echo "<h2>Setting up Attendance Notifications System</h2>";

// Check if table already exists
$check_table = "SHOW TABLES LIKE 'attendance_notifications'";
$stmt = $db->prepare($check_table);
$stmt->execute();
$table_exists = $stmt->rowCount() > 0;

if (!$table_exists) {
    echo "<p>Creating attendance_notifications table...</p>";
    
    $create_table = "CREATE TABLE attendance_notifications (
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
        $db->exec($create_table);
        echo "<p class='text-success'>✓ attendance_notifications table created successfully!</p>";
    } catch (PDOException $e) {
        echo "<p class='text-danger'>✗ Error creating table: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='text-info'>ℹ attendance_notifications table already exists.</p>";
}

// Check if attendance_notifications column exists in users table
$check_column = "SHOW COLUMNS FROM users LIKE 'attendance_notifications'";
$stmt = $db->prepare($check_column);
$stmt->execute();
$column_exists = $stmt->rowCount() > 0;

if (!$column_exists) {
    echo "<p>Adding notification settings to users table...</p>";
    
    $alter_table = "ALTER TABLE users ADD COLUMN attendance_notifications BOOLEAN DEFAULT TRUE";
    
    try {
        $db->exec($alter_table);
        echo "<p class='text-success'>✓ attendance_notifications column added to users table!</p>";
    } catch (PDOException $e) {
        echo "<p class='text-danger'>✗ Error adding column: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='text-info'>ℹ attendance_notifications column already exists in users table.</p>";
}

echo "<h3>Setup Complete!</h3>";
echo "<p>The attendance photo notification system is now ready.</p>";
echo "<p><a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a> | <a href='attendance.php' class='btn btn-secondary'>Go to Attendance</a></p>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Attendance Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <?php
                        // The PHP code above will be executed here
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
