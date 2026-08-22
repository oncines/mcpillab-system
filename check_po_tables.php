<?php
require_once 'config.php';

echo "<h2>Check Purchase Order Tables</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    // Check purchase_orders table
    echo "<h3>Purchase Orders Table:</h3>";
    $query = "DESCRIBE purchase_orders";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check purchase_order_items table
    echo "<h3>Purchase Order Items Table:</h3>";
    $query = "DESCRIBE purchase_order_items";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check existing POs
    echo "<h3>Existing Purchase Orders:</h3>";
    $query = "SELECT COUNT(*) as count FROM purchase_orders";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total POs: " . $count['count'] . "</p>";
    
    if ($count['count'] > 0) {
        $query = "SELECT id, po_number, supplier_id, status, created_at FROM purchase_orders ORDER BY created_at DESC LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $pos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>PO Number</th><th>Supplier ID</th><th>Status</th><th>Created</th></tr>";
        foreach ($pos as $po) {
            echo "<tr>";
            echo "<td>{$po['id']}</td>";
            echo "<td>{$po['po_number']}</td>";
            echo "<td>{$po['supplier_id']}</td>";
            echo "<td>{$po['status']}</td>";
            echo "<td>{$po['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
