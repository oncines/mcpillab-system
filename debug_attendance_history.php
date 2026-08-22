<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Attendance History</h2>";

// Check session
echo "<h3>Session Check</h3>";
if (is_logged_in()) {
    echo "<p>✅ User is logged in (ID: {$_SESSION['user_id']})</p>";
    echo "<p>User Role: " . ($_SESSION['user_role'] ?? 'not set') . "</p>";
    echo "<p>Full Name: " . ($_SESSION['full_name'] ?? 'not set') . "</p>";
} else {
    echo "<p>❌ User not logged in</p>";
    echo "<p><a href='index.php'>Please login</a></p>";
    exit();
}

if (is_employee()) {
    echo "<p>✅ User is employee</p>";
} else {
    echo "<p>❌ User is not employee (role: " . $_SESSION['user_role'] . ")</p>";
    echo "<p>This page is for employees only</p>";
    exit();
}

// Get employee info
echo "<h3>Employee Info</h3>";
$employee_info = get_employee_by_user_id($_SESSION['user_id']);
if ($employee_info) {
    echo "<p>✅ Employee found: {$employee_info['first_name']} {$employee_info['last_name']}</p>";
    echo "<p>Employee ID: {$employee_info['id']}</p>";
} else {
    echo "<p>❌ No employee record found for user ID {$_SESSION['user_id']}</p>";
    echo "<p>This might indicate a database issue where the user is not linked to an employee record</p>";
    exit();
}

// Test function call
echo "<h3>Function Test</h3>";
$date_from = date('Y-m-01');
$date_to = date('Y-m-d');

echo "<p>Testing get_employee_attendance_history({$employee_info['id']}, $date_from, $date_to)</p>";

try {
    $attendance_records = get_employee_attendance_history($employee_info['id'], $date_from, $date_to);
    echo "<p>✅ Function executed successfully</p>";
    echo "<p>Returned " . count($attendance_records) . " records</p>";
    
    if (empty($attendance_records)) {
        echo "<p>⚠️ No attendance records found</p>";
        
        // Check if there are any records at all
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT COUNT(*) as count FROM attendance WHERE employee_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$employee_info['id']]);
        $regular_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $query = "SELECT COUNT(*) as count FROM camera_attendance WHERE employee_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$employee_info['id']]);
        $camera_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p>Regular attendance records: $regular_count</p>";
        echo "<p>Camera attendance records: $camera_count</p>";
        
        if ($regular_count == 0 && $camera_count == 0) {
            echo "<p>⚠️ No attendance records exist for this employee</p>";
            echo "<p><a href='attendance.php'>Go to Attendance</a> to clock in/out</p>";
        }
    } else {
        echo "<p>✅ Found attendance records</p>";
        echo "<table border='1'><tr><th>Date</th><th>Type</th><th>Check In</th><th>Status</th></tr>";
        foreach ($attendance_records as $record) {
            echo "<tr>
                    <td>{$record['date']}</td>
                    <td>{$record['attendance_type']}</td>
                    <td>{$record['check_in']}</td>
                    <td>{$record['status']}</td>
                  </tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p>❌ Function failed: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

echo "<hr>";
echo "<p><a href='attendance_history.php'>Go to Attendance History</a> | <a href='dashboard.php'>Go to Dashboard</a></p>";
?>
