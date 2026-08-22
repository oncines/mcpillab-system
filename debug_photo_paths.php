<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

// Get camera attendance records
$query = 'SELECT id, employee_id, capture_date, photo_path FROM camera_attendance WHERE photo_path IS NOT NULL AND photo_path != "" ORDER BY capture_date DESC LIMIT 5';
$stmt = $db->prepare($query);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<h2>Debug Photo Paths</h2>';
echo '<table border="1" cellpadding="5">';
echo '<tr><th>ID</th><th>Date</th><th>Photo Path</th><th>DOCUMENT_ROOT</th><th>Full Path</th><th>File Exists</th><th>Test Path</th></tr>';

foreach ($records as $record) {
    $doc_root = $_SERVER['DOCUMENT_ROOT'];
    $full_path = $doc_root . $record['photo_path'];
    $test_path = __DIR__ . '/public' . $record['photo_path'];
    
    echo '<tr>';
    echo '<td>' . $record['id'] . '</td>';
    echo '<td>' . $record['capture_date'] . '</td>';
    echo '<td>' . $record['photo_path'] . '</td>';
    echo '<td>' . $doc_root . '</td>';
    echo '<td>' . $full_path . '</td>';
    echo '<td>' . (file_exists($full_path) ? 'YES' : 'NO') . '</td>';
    echo '<td>' . (file_exists($test_path) ? 'YES' : 'NO') . '</td>';
    echo '</tr>';
}
echo '</table>';

// Test with actual file names from directory
echo '<h2>Test with actual files</h2>';
$photo_dir = __DIR__ . '/public/attendance_photos/';
if (is_dir($photo_dir)) {
    $files = scandir($photo_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && strpos($file, '.jpg') !== false) {
            $test_path = '/attendance_photos/' . $file;
            $full_path = $doc_root . $test_path;
            $alt_path = __DIR__ . '/public' . $test_path;
            
            echo '<p>File: ' . $file . '<br>';
            echo 'Test path: ' . $test_path . '<br>';
            echo 'Full path: ' . $full_path . ' - Exists: ' . (file_exists($full_path) ? 'YES' : 'NO') . '<br>';
            echo 'Alt path: ' . $alt_path . ' - Exists: ' . (file_exists($alt_path) ? 'YES' : 'NO') . '</p>';
        }
    }
}
?>
