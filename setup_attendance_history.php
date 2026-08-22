<?php
require_once 'config.php';

echo "<h2>Setup Attendance History</h2>";

// Check if user is logged in
if (!is_logged_in()) {
    echo "<p>Please <a href='index.php'>login</a> first.</p>";
    exit();
}

// Create sample attendance data if requested
if (isset($_POST['generate_data'])) {
    echo "<h3>Generating Sample Data...</h3>";
    
    if (create_sample_attendance_data()) {
        echo "<p>✅ Sample attendance data generated successfully!</p>";
        
        // Also create some camera attendance records
        $database = new Database();
        $db = $database->getConnection();
        
        // Get current employee
        $employee_info = get_employee_by_user_id($_SESSION['user_id']);
        if ($employee_info) {
            // Create some sample camera attendance records
            for ($day = 0; $day < 5; $day++) {
                $date = date('Y-m-d', strtotime("-$day days"));
                
                // Skip if weekend
                if (date('N', strtotime($date)) >= 6) continue;
                
                $photo_path = "/public/attendance_photos/attendance_{$employee_info['id']}_" . time() . "_$day.jpg";
                $capture_time = sprintf("%02d:%02d:00", rand(7, 9), rand(0, 59));
                
                $query = "INSERT INTO camera_attendance 
                         (employee_id, capture_date, capture_time, photo_path, verification_status, notes) 
                         VALUES (:employee_id, :capture_date, :capture_time, :photo_path, :verification_status, :notes)";
                
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':employee_id' => $employee_info['id'],
                    ':capture_date' => $date,
                    ':capture_time' => $capture_time,
                    ':photo_path' => $photo_path,
                    ':verification_status' => 'verified',
                    ':notes' => 'Camera attendance sample'
                ]);
            }
            echo "<p>✅ Sample camera attendance records created!</p>";
        }
        
        echo "<p><a href='attendance_history.php'>View Attendance History</a></p>";
    } else {
        echo "<p>❌ Failed to generate sample data</p>";
    }
} else {
    echo "<p>This will generate sample attendance data for testing.</p>";
    echo "<form method='POST'>
            <button type='submit' name='generate_data' class='btn btn-primary'>Generate Sample Data</button>
          </form>";
}

echo "<hr>";
echo "<p><a href='attendance_history.php'>Go to Attendance History</a> | <a href='dashboard.php'>Dashboard</a></p>";
?>

<style>
.btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    cursor: pointer;
}
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}
</style>
