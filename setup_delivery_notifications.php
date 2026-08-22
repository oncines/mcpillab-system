<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Setting up Delivery Notifications System</h2>";

// Read and execute the SQL file
$sql_file = 'delivery_notifications.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);

    try {
        $db->exec($sql);
        echo "<p class='text-success'>✓ delivery_notifications table created successfully!</p>";
    } catch (PDOException $e) {
        // Check if table already exists
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p class='text-info'>ℹ delivery_notifications table already exists.</p>";
        } else {
            echo "<p class='text-danger'>✗ Error creating table: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p class='text-danger'>✗ SQL file not found: $sql_file</p>";
}

echo "<h3>Setup Complete!</h3>";
echo "<p>The delivery notification system is now ready.</p>";
echo "<p><a href='delivery_tracking.php' class='btn btn-primary'>Go to Delivery Tracking</a></p>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Delivery Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <?php
                        // The PHP code above will be executed here
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
