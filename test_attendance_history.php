<?php
require_once 'config.php';

echo "<h2>Attendance History Test</h2>";

// Test database connection
$database = new Database();
$db = $database->getConnection();
if (!$db) {
    echo "<p>❌ Database connection failed</p>";
    exit();
}
echo "<p>✅ Database connection successful</p>";

// Test 1: Check if employee exists
echo "<h3>1. Employee Test</h3>";
$query = "SELECT * FROM employees LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if ($employee) {
    echo "<p>✅ Found employee: {$employee['first_name']} {$employee['last_name']} (ID: {$employee['id']})</p>";
    $test_employee_id = $employee['id'];
} else {
    echo "<p>❌ No employees found</p>";
    exit();
}

// Test 2: Check attendance table
echo "<h3>2. Attendance Table Test</h3>";
$query = "SELECT COUNT(*) as count FROM attendance WHERE employee_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$test_employee_id]);
$attendance_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "<p>Found $attendance_count regular attendance records for test employee</p>";

// Test 3: Check camera_attendance table
echo "<h3>3. Camera Attendance Table Test</h3>";
$query = "SELECT COUNT(*) as count FROM camera_attendance WHERE employee_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$test_employee_id]);
$camera_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "<p>Found $camera_count camera attendance records for test employee</p>";

// Test 4: Test the get_employee_attendance_history function directly
echo "<h3>4. Function Test</h3>";
$date_from = date('Y-m-01');
$date_to = date('Y-m-d');

try {
    $records = get_employee_attendance_history($test_employee_id, $date_from, $date_to);
    echo "<p>✅ Function executed successfully</p>";
    echo "<p>Returned " . count($records) . " records</p>";
    
    if (!empty($records)) {
        echo "<table border='1'><tr><th>Date</th><th>Type</th><th>Check In</th><th>Status</th><th>Photo</th></tr>";
        foreach ($records as $record) {
            echo "<tr>
                    <td>{$record['date']}</td>
                    <td>{$record['attendance_type']}</td>
                    <td>{$record['check_in']}</td>
                    <td>{$record['status']}</td>
                    <td>" . ($record['photo_path'] ? 'Yes' : 'No') . "</td>
                  </tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p>❌ Function failed: " . $e->getMessage() . "</p>";
}

// Test 5: Test individual queries
echo "<h3>5. Individual Query Tests</h3>";

// Test regular attendance query
$query = "SELECT a.id, a.employee_id, a.date, a.check_in, a.check_out, a.break_duration, 
                 a.total_hours, a.status, a.notes, a.created_at, 
                 e.first_name, e.last_name, e.employee_id as emp_id, 
                 'regular' as attendance_type, null as photo_path, null as verification_status
          FROM attendance a 
          LEFT JOIN employees e ON a.employee_id = e.id 
          WHERE a.employee_id = ? 
          AND a.date BETWEEN ? AND ?
          ORDER BY a.date DESC";

$stmt = $db->prepare($query);
$stmt->execute([$test_employee_id, $date_from, $date_to]);
$regular_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<p>Regular attendance query returned " . count($regular_records) . " records</p>";

// Test camera attendance query
$query = "SELECT ca.id, ca.employee_id, ca.capture_date as date, ca.capture_time as check_in, 
                 null as check_out, null as break_duration, null as total_hours, 'present' as status, 
                 ca.notes, ca.created_at, e.first_name, e.last_name, e.employee_id as emp_id,
                 'camera' as attendance_type, ca.photo_path, ca.verification_status
          FROM camera_attendance ca 
          LEFT JOIN employees e ON ca.employee_id = e.id 
          WHERE ca.employee_id = ? 
          AND ca.capture_date BETWEEN ? AND ?
          ORDER BY ca.capture_date DESC";

$stmt = $db->prepare($query);
$stmt->execute([$test_employee_id, $date_from, $date_to]);
$camera_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<p>Camera attendance query returned " . count($camera_records) . " records</p>";

echo "<hr>";
echo "<p><a href='attendance_history.php'>Go to Attendance History</a> | <a href='dashboard.php'>Go to Dashboard</a></p>";
?>
