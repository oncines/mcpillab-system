<?php
require_once 'config.php';

echo "<h2>🔐 Admin Access Test</h2>";

// Check current user role
echo "<div class='alert alert-info'>";
echo "<strong>Current User:</strong> " . $_SESSION['full_name'] . "<br>";
echo "<strong>Role:</strong> " . $_SESSION['user_role'] . "<br>";
echo "<strong>Is Admin:</strong> " . (is_admin() ? '✅ Yes' : '❌ No') . "<br>";
echo "<strong>Is Manager:</strong> " . (is_manager() ? '✅ Yes' : '❌ No') . "<br>";
echo "<strong>Can Access Admin Pages:</strong> " . ((is_admin() || is_manager()) ? '✅ Yes' : '❌ No');
echo "</div>";

// Test access to different pages
echo "<h3>📄 Page Access Test</h3>";

$pages = [
    'attendance.php' => 'Attendance Management',
    'admin_notifications.php' => 'Photo Notifications',
    'admin_attendance_dashboard.php' => 'Attendance Approval',
    'attendance_history.php' => 'Attendance History (Employee)',
    'attendance_camera.php' => 'Camera Attendance (Employee)'
];

echo "<table class='table table-bordered'>";
echo "<tr><th>Page</th><th>Purpose</th><th>Access Status</th><th>Test Link</th></tr>";

foreach ($pages as $page => $description) {
    echo "<tr>";
    echo "<td><code>$page</code></td>";
    echo "<td>$description</td>";
    
    // Check access based on page requirements
    $can_access = false;
    if ($page === 'attendance_history.php' || $page === 'attendance_camera.php') {
        $can_access = is_employee();
    } else {
        $can_access = is_admin() || is_manager();
    }
    
    if ($can_access) {
        echo "<td><span class='badge bg-success'>✅ Accessible</span></td>";
        echo "<td><a href='$page' class='btn btn-sm btn-primary'>Open</a></td>";
    } else {
        echo "<td><span class='badge bg-danger'>❌ Restricted</span></td>";
        echo "<td><span class='text-muted'>No Access</span></td>";
    }
    echo "</tr>";
}
echo "</table>";

// Check recent camera attendance
echo "<h3>📸 Recent Camera Attendance</h3>";

if (is_admin() || is_manager()) {
    $camera_attendance = get_camera_attendance_records(10, 0);
    
    if (!empty($camera_attendance)) {
        echo "<table class='table table-bordered'>";
        echo "<tr><th>Employee</th><th>Date/Time</th><th>Photo</th><th>Status</th><th>Location</th></tr>";
        
        foreach ($camera_attendance as $attendance) {
            echo "<tr>";
            echo "<td>{$attendance['first_name']} {$attendance['last_name']}</td>";
            echo "<td>" . format_date($attendance['capture_date']) . " {$attendance['capture_time']}</td>";
            echo "<td>";
            if ($attendance['photo_path'] && file_exists($attendance['photo_path'])) {
                echo "<img src='{$attendance['photo_path']}' width='60' height='60' style='border-radius: 8px;'>";
            } else {
                echo "❌ No Photo";
            }
            echo "</td>";
            echo "<td><span class='badge bg-warning'>{$attendance['verification_status']}</span></td>";
            echo "<td>" . substr($attendance['location_address'] ?: 'No location', 0, 30) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='text-info'>ℹ No camera attendance records found.</p>";
    }
} else {
    echo "<p class='text-warning'>⚠️ Admin/Manager access required to view camera attendance.</p>";
}

// Check notifications
echo "<h3>🔔 Recent Notifications</h3>";

if (is_admin() || is_manager()) {
    $notifications = get_unread_attendance_notifications(5);
    
    if (!empty($notifications)) {
        echo "<table class='table table-bordered'>";
        echo "<tr><th>Employee</th><th>Message</th><th>Time</th><th>Photo</th></tr>";
        
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['first_name']} {$notif['last_name']}</td>";
            echo "<td>{$notif['message']}</td>";
            echo "<td>" . date('M j, Y g:i A', strtotime($notif['created_at'])) . "</td>";
            echo "<td>";
            if ($notif['photo_path'] && file_exists($notif['photo_path'])) {
                echo "<img src='{$notif['photo_path']}' width='50' height='50' style='border-radius: 8px;'>";
            } else {
                echo "❌ No Photo";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='text-info'>ℹ No unread notifications.</p>";
    }
} else {
    echo "<p class='text-warning'>⚠️ Admin/Manager access required to view notifications.</p>";
}

// Employee attendance history test
echo "<h3>👤 Employee Attendance History Test</h3>";

if (is_employee()) {
    $employee_info = get_employee_by_user_id($_SESSION['user_id']);
    if ($employee_info) {
        $today_attendance = get_camera_attendance_by_employee($employee_info['id'], date('Y-m-d'));
        
        if (!empty($today_attendance)) {
            echo "<div class='alert alert-success'>";
            echo "✅ Found " . count($today_attendance) . " camera attendance record(s) for today.<br>";
            foreach ($today_attendance as $attendance) {
                echo "- {$attendance['capture_time']} - Status: {$attendance['verification_status']}<br>";
            }
            echo "</div>";
        } else {
            echo "<p class='text-info'>ℹ No camera attendance for today. <a href='attendance_camera.php'>Take Attendance</a></p>";
        }
    }
} else {
    echo "<p class='text-warning'>⚠️ Employee access required to test attendance history.</p>";
}

echo "<div class='mt-4'>";
echo "<h3>🧪 Quick Tests</h3>";
echo "<a href='attendance_camera.php' class='btn btn-primary me-2'>📸 Test Camera Attendance</a>";
echo "<a href='admin_notifications.php' class='btn btn-secondary me-2'>🔔 Check Notifications</a>";
echo "<a href='attendance.php' class='btn btn-info me-2'>⏰ View Attendance</a>";
echo "<a href='attendance_history.php' class='btn btn-success'>📜 View History</a>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.alert { padding: 15px; margin: 10px 0; border-radius: 8px; }
.alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.table { border-collapse: collapse; width: 100%; margin: 10px 0; }
.table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
.table th { background-color: #f2f2f2; }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; color: white; }
.bg-success { background-color: #28a745; }
.bg-danger { background-color: #dc3545; }
.bg-warning { background-color: #ffc107; color: #000; }
.btn { padding: 8px 16px; text-decoration: none; border-radius: 6px; color: white; display: inline-block; margin: 2px; font-size: 14px; }
.btn-primary { background-color: #007bff; }
.btn-secondary { background-color: #6c757d; }
.btn-info { background-color: #17a2b8; }
.btn-success { background-color: #28a745; }
.btn-sm { padding: 6px 12px; font-size: 12px; }
</style>
