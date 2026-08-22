<?php
require_once 'config.php';

// Test page to verify the send attendance functionality
echo "<h2>📸 Test Send Attendance Functionality</h2>";

// Check if user is logged in as employee
if (!is_logged_in() || !is_employee()) {
    echo "<div class='alert alert-warning'>";
    echo "⚠️ Please log in as an employee to test this functionality.<br>";
    echo "<a href='index.php'>Login Here</a>";
    echo "</div>";
    exit();
}

$employee_info = get_employee_by_user_id($_SESSION['user_id']);

if (!$employee_info) {
    echo "<div class='alert alert-danger'>❌ Employee information not found.</div>";
    exit();
}

echo "<div class='alert alert-success'>";
echo "✅ Logged in as: <strong>{$employee_info['first_name']} {$employee_info['last_name']}</strong> (ID: {$employee_info['id']})<br>";
echo "📱 Ready to test camera attendance submission";
echo "</div>";

// Check recent notifications
echo "<h3>🔔 Recent Admin Notifications</h3>";
$notifications = get_unread_attendance_notifications(5);

if (!empty($notifications)) {
    echo "<table class='table table-bordered'>";
    echo "<tr><th>Employee</th><th>Message</th><th>Time</th><th>Status</th></tr>";
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['first_name']} {$notif['last_name']}</td>";
        echo "<td>{$notif['message']}</td>";
        echo "<td>" . date('M j, Y g:i A', strtotime($notif['created_at'])) . "</td>";
        echo "<td><span class='badge bg-warning'>Unread</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-info'>ℹ No unread notifications found. Admin side is clear.</p>";
}

// Check today's attendance
echo "<h3>📊 Today's Attendance Records</h3>";
$today_attendance = get_camera_attendance_by_employee($employee_info['id'], date('Y-m-d'));

if (!empty($today_attendance)) {
    echo "<table class='table table-bordered'>";
    echo "<tr><th>Time</th><th>Location</th><th>Photo</th><th>Status</th></tr>";
    foreach ($today_attendance as $attendance) {
        echo "<tr>";
        echo "<td>{$attendance['capture_time']}</td>";
        echo "<td>" . substr($attendance['location_address'], 0, 30) . "...</td>";
        echo "<td>";
        if ($attendance['photo_path'] && file_exists($attendance['photo_path'])) {
            echo "<img src='{$attendance['photo_path']}' width='60' height='60' style='border-radius: 8px;'>";
        } else {
            echo "❌ No photo";
        }
        echo "</td>";
        echo "<td><span class='badge bg-{$attendance['verification_status'] == 'pending' ? 'warning' : 'success'}'>{$attendance['verification_status']}</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-info'>ℹ No attendance records for today. Ready for testing!</p>";
}

echo "<div class='test-actions'>";
echo "<h3>🧪 Test Actions</h3>";
echo "<a href='attendance_camera.php' class='btn btn-primary btn-lg'>";
echo "📸 Open Camera Attendance (Test Send Button)";
echo "</a><br><br>";

echo "<a href='admin_notifications.php' class='btn btn-secondary'>";
echo "🔔 Check Admin Notifications";
echo "</a><br><br>";

echo "<a href='attendance.php' class='btn btn-info'>";
echo "⏰ View Attendance Page";
echo "</a>";
echo "</div>";

echo "<div class='instructions'>";
echo "<h3>📋 Testing Instructions</h3>";
echo "<ol>";
echo "<li>Click 'Open Camera Attendance' button</li>";
echo "<li>Take a photo using the camera</li>";
echo "<li>Click 'Send Attendance' button</li>";
echo "<li>Check if success message appears</li>";
echo "<li>Come back to this page and refresh</li>";
echo "<li>Check 'Admin Notifications' to see if it appears</li>";
echo "</ol>";
echo "</div>";

echo "<div class='troubleshooting'>";
echo "<h3>🔧 Troubleshooting</h3>";
echo "<p>If Send Attendance button doesn't work:</p>";
echo "<ul>";
echo "<li>✅ Check browser console for errors (F12 → Console)</li>";
echo "<li>✅ Ensure camera permissions are granted</li>";
echo "<li>✅ Check if employee_id is properly set</li>";
echo "<li>✅ Verify photo is captured before sending</li>";
echo "</ul>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.alert { padding: 15px; margin: 10px 0; border-radius: 8px; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.table { border-collapse: collapse; width: 100%; margin: 10px 0; }
.table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
.table th { background-color: #f2f2f2; }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; color: white; }
.bg-warning { background-color: #ffc107; color: #000; }
.bg-success { background-color: #28a745; }
.btn { padding: 12px 24px; text-decoration: none; border-radius: 8px; color: white; display: inline-block; margin: 5px 0; font-weight: bold; }
.btn-primary { background-color: #007bff; }
.btn-secondary { background-color: #6c757d; }
.btn-info { background-color: #17a2b8; }
.btn-lg { padding: 16px 32px; font-size: 16px; }
.test-actions { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
.instructions { background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0; }
.troubleshooting { background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; }
</style>
