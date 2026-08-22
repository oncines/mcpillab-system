<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

// Check for camera attendance records with photos
$query = 'SELECT id, employee_id, capture_date, photo_path, verification_status FROM camera_attendance WHERE photo_path IS NOT NULL AND photo_path != "" ORDER BY capture_date DESC LIMIT 10';
$stmt = $db->prepare($query);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<h2>Camera attendance records with photos:</h2>';
if (empty($records)) {
    echo '<p>No records found with photos.</p>';
} else {
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>ID</th><th>Employee ID</th><th>Date</th><th>Photo Path</th><th>File Exists</th><th>Photo Preview</th></tr>';
    foreach ($records as $record) {
        $full_path = __DIR__ . '/public' . $record['photo_path'];
        $file_exists = file_exists($full_path);
        
        echo '<tr>';
        echo '<td>' . $record['id'] . '</td>';
        echo '<td>' . $record['employee_id'] . '</td>';
        echo '<td>' . $record['capture_date'] . '</td>';
        echo '<td>' . $record['photo_path'] . '</td>';
        echo '<td>' . ($file_exists ? 'YES' : 'NO') . '</td>';
        echo '<td>';
        if ($file_exists) {
            echo '<img src="' . $record['photo_path'] . '" style="width: 50px; height: 50px; object-fit: cover;">';
        } else {
            echo 'No file';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Check all camera attendance records
$all_query = 'SELECT COUNT(*) as total, COUNT(photo_path) as with_photos FROM camera_attendance';
$all_stmt = $db->prepare($all_query);
$all_stmt->execute();
$all_stats = $all_stmt->fetch(PDO::FETCH_ASSOC);

echo '<h2>Camera Attendance Statistics:</h2>';
echo '<p>Total camera attendance records: ' . $all_stats['total'] . '</p>';
echo '<p>Records with photos: ' . $all_stats['with_photos'] . '</p>';
?>
