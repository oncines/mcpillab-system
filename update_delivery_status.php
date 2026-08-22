<?php
require_once 'config.php';

echo "<h2>Updating Delivery Status Options</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Add 'approved' status to the ENUM
    $query = "ALTER TABLE deliveries MODIFY status ENUM('pending', 'approved', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending'";
    
    if ($db->exec($query)) {
        echo "<div class='alert alert-success'>✅ Successfully updated delivery status options to include 'approved' status.</div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ No update needed - 'approved' status may already exist.</div>";
    }
    
    // Verify the update
    $query = "DESCRIBE deliveries";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            echo "<div class='alert alert-info'>";
            echo "<strong>Status field definition:</strong> " . $column['Type'];
            echo "</div>";
            break;
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Error updating database: " . $e->getMessage() . "</div>";
}

echo "<br><a href='delivery_tracking.php' class='btn btn-primary'>Go to Delivery Tracking</a>";
?>
