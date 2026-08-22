<?php
require_once 'config.php';

// Simple test page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple History Test</title>
</head>
<body>
    <h1>Attendance History Test</h1>
    
    <?php if (is_logged_in()): ?>
        <p>✅ You are logged in as: <?php echo $_SESSION['full_name']; ?></p>
        
        <?php if (is_employee()): ?>
            <p>✅ You are an employee</p>
            
            <?php 
            $employee_info = get_employee_by_user_id($_SESSION['user_id']);
            if ($employee_info): 
            ?>
                <p>✅ Employee found: <?php echo $employee_info['first_name'] . ' ' . $employee_info['last_name']; ?></p>
                
                <?php 
                $date_from = date('Y-m-01');
                $date_to = date('Y-m-d');
                $records = get_employee_attendance_history($employee_info['id'], $date_from, $date_to);
                ?>
                
                <p>Found <?php echo count($records); ?> attendance records</p>
                
                <?php if (!empty($records)): ?>
                    <ul>
                        <?php foreach ($records as $record): ?>
                            <li><?php echo $record['date']; ?> - <?php echo $record['status']; ?> - <?php echo $record['attendance_type']; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No records found. <a href="setup_attendance_history.php">Generate sample data</a></p>
                <?php endif; ?>
                
            <?php else: ?>
                <p>❌ No employee record found</p>
            <?php endif; ?>
            
        <?php else: ?>
            <p>❌ You are not an employee</p>
        <?php endif; ?>
        
    <?php else: ?>
        <p>❌ You are not logged in</p>
        <p><a href="index.php">Please login</a></p>
    <?php endif; ?>
    
    <hr>
    <p><a href="attendance_history.php">Go to Full Attendance History</a></p>
    <p><a href="dashboard.php">Go to Dashboard</a></p>
</body>
</html>
