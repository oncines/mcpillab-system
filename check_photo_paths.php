<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query('SELECT id, photo_path FROM camera_attendance ORDER BY id DESC LIMIT 5');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo 'ID: ' . $row['id'] . ' - Photo Path: ' . $row['photo_path'] . PHP_EOL;
    
    // Check if file exists
    if ($row['photo_path'] && file_exists($row['photo_path'])) {
        echo '  File EXISTS: YES' . PHP_EOL;
    } else {
        echo '  File EXISTS: NO' . PHP_EOL;
    }
    echo PHP_EOL;
}
?>
