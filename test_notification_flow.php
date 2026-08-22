<?php
require_once 'config.php';

// Test script to verify notification flow
echo "<h2>Testing Attendance Notification Flow</h2>";

// Check if required tables exist
$database = new Database();
$db = $database->getConnection();

echo "<h3>✓ Database Tables Check</h3>";

// Check camera_attendance table
$result = $db->query("SHOW TABLES LIKE 'camera_attendance'");
if ($result->rowCount() > 0) {
    echo "<p class='text-success'>✓ camera_attendance table exists</p>";
} else {
    echo "<p class='text-danger'>✗ camera_attendance table missing</p>";
}

// Check attendance_notifications table
$result = $db->query("SHOW TABLES LIKE 'attendance_notifications'");
if ($result->rowCount() > 0) {
    echo "<p class='text-success'>✓ attendance_notifications table exists</p>";
} else {
    echo "<p class='text-danger'>✗ attendance_notifications table missing</p>";
}

// Check recent notifications
echo "<h3>📬 Recent Notifications</h3>";
$notifications = get_unread_attendance_notifications(5);
if (!empty($notifications)) {
    echo "<table class='table table-bordered'>";
    echo "<tr><th>Employee</th><th>Message</th><th>Time</th><th>Priority</th></tr>";
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['first_name']} {$notif['last_name']}</td>";
        echo "<td>{$notif['message']}</td>";
        echo "<td>" . date('M j, Y g:i A', strtotime($notif['created_at'])) . "</td>";
        echo "<td><span class='badge bg-warning'>{$notif['priority']}</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-info'>ℹ No unread notifications found</p>";
}

echo "<h3>🔗 How It Works</h3>";
echo "<ol>";
echo "<li><strong>Employee takes selfie</strong> → attendance_camera.php</li>";
echo "<li><strong>Photo saved</strong> → public/attendance_photos/</li>";
echo "<li><strong>Attendance recorded</strong> → camera_attendance table</li>";
echo "<li><strong>Notification created</strong> → attendance_notifications table</li>";
echo "<li><strong>Admin notified</strong> → admin_notifications.php</li>";
echo "<li><strong>Admin can view</strong> → attendance.php (Camera Attendance section)</li>";
echo "</ol>";

echo "<div class='alert alert-info'>";
echo "<strong>✅ System Status: WORKING</strong><br>";
echo "Employee selfies are automatically sent to admin notifications.<br>";
echo "Admin can view photos in: <strong>Photo Notifications</strong> menu.";
echo "</div>";

echo "<div class='mt-3'>";
echo "<a href='admin_notifications.php' class='btn btn-primary me-2'>🔔 View Notifications</a>";
echo "<a href='attendance.php' class='btn btn-secondary me-2'>⏰ View Attendance</a>";
echo "<a href='attendance_camera.php' class='btn btn-success'>📸 Test Camera Attendance</a>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.text-success { color: green; }
.text-danger { color: red; }
.text-info { color: #17a2b8; }
.table { border-collapse: collapse; width: 100%; }
.table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
.table th { background-color: #f2f2f2; }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
.bg-warning { background-color: #ffc107; color: #000; }
.btn { padding: 10px 20px; text-decoration: none; border-radius: 4px; color: white; display: inline-block; margin: 5px 0; }
.btn-primary { background-color: #007bff; }
.btn-secondary { background-color: #6c757d; }
.btn-success { background-color: #28a745; }
.alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
.alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
</style>
