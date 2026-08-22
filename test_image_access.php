<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Testing Image Access</h2>";

$stmt = $db->query('SELECT id, photo_path FROM camera_attendance ORDER BY id DESC LIMIT 5');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<h3>Record ID: {$row['id']}</h3>";
    echo "<p>Database Path: {$row['photo_path']}</p>";
    
    if ($row['photo_path']) {
        // Check if file exists on filesystem
        $filesystem_path = __DIR__ . '/' . $row['photo_path'];
        echo "<p>Filesystem Path: $filesystem_path</p>";
        echo "<p>File Exists: " . (file_exists($filesystem_path) ? "YES" : "NO") . "</p>";
        
        // Show image attempt
        echo "<p>Image Display Test:</p>";
        echo "<img src='{$row['photo_path']}' alt='Test' style='max-width: 100px; border: 1px solid red;' onerror=\"this.style.border='2px solid red'; this.alt='FAILED TO LOAD';\">";
        echo "<hr>";
    }
}

// Also show what files actually exist
echo "<h2>Files in attendance_photos directory:</h2>";
$photo_dir = __DIR__ . '/public/attendance_photos/';
if (is_dir($photo_dir)) {
    $files = scandir($photo_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<p>Found file: $file</p>";
            echo "<img src='public/attendance_photos/$file' alt='Actual file' style='max-width: 100px; border: 1px solid green;' onerror=\"this.style.border='2px solid red'; this.alt='FAILED TO LOAD';\">";
        }
    }
}
?>
