<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('index.php');
}

echo "<h2>Camera Attendance System Test</h2>";

$database = new Database();
$db = $database->getConnection();

// Test 1: Check if tables exist
echo "<h3>Database Tables Check</h3>";

$tables = ['camera_attendance', 'attendance_notifications', 'attendance', 'employees', 'users'];
foreach ($tables as $table) {
    $result = $db->query("SHOW TABLES LIKE '$table'");
    if ($result->rowCount() > 0) {
        echo "<p class='text-success'>✓ Table '$table' exists</p>";
    } else {
        echo "<p class='text-danger'>✗ Table '$table' does not exist</p>";
    }
}

// Test 2: Check if directories exist
echo "<h3>Directories Check</h3>";
$dirs = ['public/attendance_photos', 'public/attendance_videos'];
foreach ($dirs as $dir) {
    if (file_exists($dir)) {
        echo "<p class='text-success'>✓ Directory '$dir' exists</p>";
    } else {
        echo "<p class='text-danger'>✗ Directory '$dir' does not exist</p>";
    }
}

// Test 3: Check if functions exist
echo "<h3>Functions Check</h3>";
$functions = [
    'record_camera_attendance',
    'get_camera_attendance_by_employee',
    'get_camera_attendance_records',
    'create_attendance_notification',
    'get_unread_attendance_notifications',
    'get_pending_camera_attendance',
    'approve_camera_attendance',
    'reject_camera_attendance'
];

foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "<p class='text-success'>✓ Function '$function' exists</p>";
    } else {
        echo "<p class='text-danger'>✗ Function '$function' does not exist</p>";
    }
}

// Test 4: Check employee data
echo "<h3>Employee Data Check</h3>";
$employees = get_employees(5, 0);
if (!empty($employees)) {
    echo "<p class='text-success'>✓ Found " . count($employees) . " employee(s)</p>";
    foreach ($employees as $emp) {
        echo "<p> - {$emp['first_name']} {$emp['last_name']} (ID: {$emp['employee_id']})</p>";
    }
} else {
    echo "<p class='text-danger'>✗ No employees found</p>";
}

// Test 5: Check camera attendance records
echo "<h3>Camera Attendance Records</h3>";
$camera_records = get_camera_attendance_records(10, 0);
if (!empty($camera_records)) {
    echo "<p class='text-success'>✓ Found " . count($camera_records) . " camera attendance record(s)</p>";
    foreach ($camera_records as $record) {
        echo "<p> - {$record['first_name']} {$record['last_name']} on {$record['capture_date']} at {$record['capture_time']}</p>";
    }
} else {
    echo "<p class='text-info'>ℹ No camera attendance records found</p>";
}

// Test 6: Check notifications
echo "<h3>Notifications Check</h3>";
$notifications = get_unread_attendance_notifications(10);
if (!empty($notifications)) {
    echo "<p class='text-success'>✓ Found " . count($notifications) . " unread notification(s)</p>";
} else {
    echo "<p class='text-info'>ℹ No unread notifications</p>";
}

// Test 7: Check pending attendance
echo "<h3>Pending Attendance Check</h3>";
$pending = get_pending_camera_attendance(10);
if (!empty($pending)) {
    echo "<p class='text-success'>✓ Found " . count($pending) . " pending attendance record(s)</p>";
} else {
    echo "<p class='text-info'>ℹ No pending attendance records</p>";
}

echo "<h3>System Status</h3>";
echo "<p><strong>User Role:</strong> " . $_SESSION['user_role'] . "</p>";
echo "<p><strong>User Name:</strong> " . $_SESSION['full_name'] . "</p>";

// Navigation links
echo "<div class='mt-4'>";
if (is_employee()) {
    echo "<a href='attendance_camera.php' class='btn btn-primary me-2'>📷 Camera Attendance</a>";
    echo "<a href='attendance_history.php' class='btn btn-secondary me-2'>📊 Attendance History</a>";
}
if (is_admin() || is_manager()) {
    echo "<a href='admin_attendance_dashboard.php' class='btn btn-success me-2'>✅ Attendance Approval</a>";
    echo "<a href='admin_notifications.php' class='btn btn-info me-2'>🔔 Notifications</a>";
    echo "<a href='attendance.php' class='btn btn-warning me-2'>📋 Attendance Management</a>";
}
echo "<a href='dashboard.php' class='btn btn-dark'>🏠 Dashboard</a>";
echo "</div>";

// Quick setup if needed
if (empty($employees)) {
    echo "<div class='alert alert-warning mt-4'>";
    echo "<h4>No Employees Found</h4>";
    echo "<p>You need to add employees before using the camera attendance system.</p>";
    echo "<a href='employee_profile.php' class='btn btn-primary'>Add Employees</a>";
    echo "</div>";
}

echo "<div class='mt-4'>";
echo "<a href='setup_camera_attendance.php' class='btn btn-outline-primary'>🔧 Run Setup Again</a>";
echo "</div>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera Attendance Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        
    </div>
</body>
</html>
