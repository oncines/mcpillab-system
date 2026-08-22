<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

echo '<h2>Camera Attendance Records Test</h2>';

// Get some camera attendance records
$query = 'SELECT ca.id, ca.employee_id, ca.capture_date, ca.photo_path, e.first_name, e.last_name 
          FROM camera_attendance ca 
          LEFT JOIN employees e ON ca.employee_id = e.id 
          ORDER BY ca.capture_date DESC LIMIT 10';
$stmt = $db->prepare($query);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($records)) {
    echo '<p>No camera attendance records found.</p>';
} else {
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>ID</th><th>Employee</th><th>Date</th><th>Photo Path</th><th>Photo Display</th></tr>';
    
    foreach ($records as $record) {
        echo '<tr>';
        echo '<td>' . $record['id'] . '</td>';
        echo '<td>' . ($record['first_name'] ? $record['first_name'] . ' ' . $record['last_name'] : 'N/A') . '</td>';
        echo '<td>' . $record['capture_date'] . '</td>';
        echo '<td>' . $record['photo_path'] . '</td>';
        echo '<td>';
        
        if ($record['photo_path']) {
            // Test different path methods
            $photo_found = false;
            $display_path = $record['photo_path'];
            
            // Method 1: Direct path as stored
            if (file_exists(__DIR__ . '/public' . $record['photo_path'])) {
                $photo_found = true;
                echo '<img src="' . $record['photo_path'] . '" style="width: 50px; height: 50px; object-fit: cover;">';
            }
            // Method 2: Try with /mcpillab prefix
            elseif (file_exists(__DIR__ . '/public/mcpillab' . $record['photo_path'])) {
                $photo_found = true;
                $display_path = '/mcpillab' . $record['photo_path'];
                echo '<img src="' . $display_path . '" style="width: 50px; height: 50px; object-fit: cover;">';
            }
            // Method 3: Try without leading slash
            elseif (file_exists(__DIR__ . '/public/attendance_photos/' . basename($record['photo_path']))) {
                $photo_found = true;
                $display_path = '/attendance_photos/' . basename($record['photo_path']);
                echo '<img src="' . $display_path . '" style="width: 50px; height: 50px; object-fit: cover;">';
            }
            
            if (!$photo_found) {
                echo 'Photo not found - Path: ' . __DIR__ . '/public' . $record['photo_path'];
            }
        } else {
            echo 'No photo path';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Show actual files in directory
echo '<h2>Actual Photo Files</h2>';
$photo_dir = __DIR__ . '/public/attendance_photos/';
if (is_dir($photo_dir)) {
    $files = scandir($photo_dir);
    echo '<p>Directory: ' . $photo_dir . '</p>';
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && strpos($file, '.jpg') !== false) {
            $web_path = '/attendance_photos/' . $file;
            echo '<p>';
            echo 'File: ' . $file . '<br>';
            echo 'Web path: ' . $web_path . '<br>';
            echo '<img src="' . $web_path . '" style="width: 50px; height: 50px; object-fit: cover;">';
            echo '</p>';
        }
    }
} else {
    echo '<p>Photo directory not found: ' . $photo_dir . '</p>';
}
?>
