<?php
require_once 'config.php';

// Sample attendance data setup
echo "<h2>Setting up Attendance System</h2>";

// Get all employees
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM employees";
$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($employees)) {
    echo "<p>No employees found. Please add employees first.</p>";
} else {
    echo "<p>Found " . count($employees) . " employees. Adding sample attendance data...</p>";
    
    // Clear existing attendance data
    $delete_query = "DELETE FROM attendance";
    $db->exec($delete_query);
    echo "<p>Cleared existing attendance data.</p>";
    
    // Add sample attendance for the last 7 days
    $attendance_data = [];
    $today = date('Y-m-d');
    
    foreach ($employees as $employee) {
        for ($day = 0; $day < 7; $day++) {
            $date = date('Y-m-d', strtotime("-$day days", strtotime($today)));
            
            // Skip weekends
            if (date('N', strtotime($date)) >= 6) continue;
            
            // Random check-in times between 7:00 AM and 9:30 AM
            $check_in_hour = rand(7, 9);
            $check_in_minute = $check_in_hour == 7 ? rand(0, 59) : rand(0, 30);
            $check_in = sprintf("%02d:%02d:00", $check_in_hour, $check_in_minute);
            
            // Random check-out times between 4:00 PM and 7:00 PM
            $check_out_hour = rand(16, 19);
            $check_out_minute = rand(0, 59);
            $check_out = sprintf("%02d:%02d:00", $check_out_hour, $check_out_minute);
            
            // Calculate total hours
            $check_in_time = strtotime($check_in);
            $check_out_time = strtotime($check_out);
            $total_hours = round(($check_out_time - $check_in_time) / 3600, 2);
            
            // Random status (mostly present, some late)
            $statuses = ['present', 'present', 'present', 'present', 'late'];
            $status = $statuses[array_rand($statuses)];
            
            // Adjust check-in time for late employees
            if ($status === 'late') {
                $check_in_hour = rand(9, 10);
                $check_in_minute = rand(1, 59);
                $check_in = sprintf("%02d:%02d:00", $check_in_hour, $check_in_minute);
                $check_in_time = strtotime($check_in);
                $total_hours = round(($check_out_time - $check_in_time) / 3600, 2);
            }
            
            $attendance_data[] = [
                'employee_id' => $employee['id'],
                'date' => $date,
                'check_in' => $check_in,
                'check_out' => $check_out,
                'total_hours' => $total_hours,
                'status' => $status,
                'notes' => $status === 'late' ? 'Late arrival' : 'Regular attendance'
            ];
        }
    }
    
    // Insert attendance data
    $insert_query = "INSERT INTO attendance (employee_id, date, check_in, check_out, total_hours, status, notes) 
                     VALUES (:employee_id, :date, :check_in, :check_out, :total_hours, :status, :notes)";
    $insert_stmt = $db->prepare($insert_query);
    
    $success_count = 0;
    foreach ($attendance_data as $data) {
        try {
            $insert_stmt->bindParam(':employee_id', $data['employee_id']);
            $insert_stmt->bindParam(':date', $data['date']);
            $insert_stmt->bindParam(':check_in', $data['check_in']);
            $insert_stmt->bindParam(':check_out', $data['check_out']);
            $insert_stmt->bindParam(':total_hours', $data['total_hours']);
            $insert_stmt->bindParam(':status', $data['status']);
            $insert_stmt->bindParam(':notes', $data['notes']);
            
            if ($insert_stmt->execute()) {
                $success_count++;
            }
        } catch (PDOException $e) {
            echo "<p>Error inserting data: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p>Successfully added $success_count attendance records.</p>";
    
    // Add today's attendance specifically
    $today_attendance_count = 0;
    foreach ($employees as $employee) {
        $today_check_in = sprintf("%02d:%02d:00", rand(7, 9), rand(0, 59));
        $today_check_out = sprintf("%02d:%02d:00", rand(16, 19), rand(0, 59));
        $today_total_hours = round((strtotime($today_check_out) - strtotime($today_check_in)) / 3600, 2);
        
        $today_status = rand(0, 10) > 1 ? 'present' : 'late';
        
        try {
            $insert_stmt->bindParam(':employee_id', $employee['id']);
            $insert_stmt->bindParam(':date', $today);
            $insert_stmt->bindParam(':check_in', $today_check_in);
            $insert_stmt->bindParam(':check_out', $today_check_out);
            $insert_stmt->bindParam(':total_hours', $today_total_hours);
            $insert_stmt->bindParam(':status', $today_status);
            $insert_stmt->bindParam(':notes', $today_status === 'late' ? 'Late arrival today' : 'Present today');
            
            if ($insert_stmt->execute()) {
                $today_attendance_count++;
            }
        } catch (PDOException $e) {
            // Skip if today's attendance already exists
        }
    }
    
    echo "<p>Added $today_attendance_count attendance records for today.</p>";
}

echo "<p><a href='dashboard.php'>Go to Dashboard</a> | <a href='attendance.php'>Go to Attendance</a></p>";
?>
