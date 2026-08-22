<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Fixing Photo Paths</h2>";

// Get all camera attendance records
$stmt = $db->query('SELECT id, photo_path FROM camera_attendance ORDER BY id DESC');
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get actual files in the directory
$photo_dir = __DIR__ . '/public/attendance_photos/';
$actual_files = [];
if (is_dir($photo_dir)) {
    $files = scandir($photo_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $actual_files[] = $file;
        }
    }
}

echo "<h3>Actual files found:</h3>";
foreach ($actual_files as $file) {
    echo "<p>$file</p>";
}

echo "<h3>Database records vs actual files:</h3>";
foreach ($records as $record) {
    echo "<h4>Record ID: {$record['id']}</h4>";
    echo "<p>Database path: {$record['photo_path']}</p>";
    
    if ($record['photo_path']) {
        // Extract filename from path
        $filename = basename($record['photo_path']);
        echo "<p>Filename from DB: $filename</p>";
        
        // Check if this file exists
        if (in_array($filename, $actual_files)) {
            echo "<p style='color: green;'>✓ File exists!</p>";
        } else {
            echo "<p style='color: red;'>✗ File NOT found!</p>";
            
            // Try to find a matching file by timestamp or pattern
            $possible_matches = [];
            foreach ($actual_files as $actual_file) {
                if (strpos($actual_file, 'attendance__') === 0) {
                    $possible_matches[] = $actual_file;
                }
            }
            
            if (!empty($possible_matches)) {
                echo "<p>Possible matches: " . implode(', ', $possible_matches) . "</p>";
                
                // Update the database with the first available file
                $new_path = '/attendance_photos/' . $possible_matches[0];
                $update_stmt = $db->prepare('UPDATE camera_attendance SET photo_path = :photo_path WHERE id = :id');
                $update_stmt->bindParam(':photo_path', $new_path);
                $update_stmt->bindParam(':id', $record['id']);
                
                if ($update_stmt->execute()) {
                    echo "<p style='color: blue;'>Updated record ID {$record['id']} with path: $new_path</p>";
                }
            }
        }
    }
    echo "<hr>";
}

echo "<h2>Verification - Updated records:</h2>";
$verify_stmt = $db->query('SELECT id, photo_path FROM camera_attendance ORDER BY id DESC LIMIT 5');
while($row = $verify_stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<p>ID: {$row['id']} - Path: {$row['photo_path']}</p>";
}
?>
