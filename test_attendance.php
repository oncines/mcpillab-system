<?php
require_once 'config.php';

echo "<h2>Attendance System Test</h2>";

// Test 1: Check database connection
echo "<h3>1. Database Connection Test</h3>";
$database = new Database();
$db = $database->getConnection();
if ($db) {
    echo "<p>✅ Database connection successful</p>";
} else {
    echo "<p>❌ Database connection failed</p>";
}

// Test 2: Check employees table
echo "<h3>2. Employees Table Test</h3>";
$query = "SELECT COUNT(*) as count FROM employees";
$stmt = $db->prepare($query);
$stmt->execute();
$employee_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "<p>Found $employee_count employees</p>";

if ($employee_count > 0) {
    $query = "SELECT * FROM employees LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>ID</th><th>Employee ID</th><th>Name</th><th>Position</th></tr>";
    foreach ($employees as $emp) {
        echo "<tr><td>{$emp['id']}</td><td>{$emp['employee_id']}</td><td>{$emp['first_name']} {$emp['last_name']}</td><td>{$emp['position']}</td></tr>";
    }
    echo "</table>";
}

// Test 3: Check attendance table
echo "<h3>3. Attendance Table Test</h3>";
$query = "SELECT COUNT(*) as count FROM attendance";
$stmt = $db->prepare($query);
$stmt->execute();
$attendance_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "<p>Found $attendance_count attendance records</p>";

if ($attendance_count > 0) {
    $query = "SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_id 
              FROM attendance a 
              LEFT JOIN employees e ON a.employee_id = e.id 
              ORDER BY a.date DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>Date</th><th>Employee</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th></tr>";
    foreach ($attendance as $att) {
        echo "<tr>
                <td>{$att['date']}</td>
                <td>{$att['first_name']} {$att['last_name']}</td>
                <td>{$att['check_in']}</td>
                <td>{$att['check_out']}</td>
                <td>{$att['total_hours']}</td>
                <td>{$att['status']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>⚠️ No attendance records found. 
          <a href='attendance.php'>Go to Attendance Page</a> to generate sample data.</p>";
}

// Test 4: Test attendance functions
echo "<h3>4. Attendance Functions Test</h3>";

// Test get_attendance_records function
$records = get_attendance_records(null, date('Y-m-d'), date('Y-m-d'), 5);
echo "<p>get_attendance_records() returned " . count($records) . " records for today</p>";

// Test dashboard stats
$stats = get_dashboard_stats();
echo "<p>Dashboard stats: Today's attendance = {$stats['today_attendance']}</p>";

// Test 5: Test sample data generation
echo "<h3>5. Sample Data Generation Test</h3>";
if (isset($_POST['generate_data'])) {
    if (create_sample_attendance_data()) {
        echo "<p>✅ Sample data generated successfully!</p>";
        echo "<p><a href='test_attendance.php'>Refresh to see results</a></p>";
    } else {
        echo "<p>❌ Failed to generate sample data</p>";
    }
} else {
    echo "<form method='POST'>
            <button type='submit' name='generate_data'>Generate Sample Data</button>
          </form>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a> | <a href='attendance.php'>Go to Attendance</a></p>";
?>
