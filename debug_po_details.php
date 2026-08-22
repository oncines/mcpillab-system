<?php
require_once 'config.php';

echo "<h1>Debug PO Details</h1>";

// Check if there are any POs
$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, po_number, supplier_name FROM purchase_orders LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$pos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Available Purchase Orders:</h2>";
if ($pos) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>PO Number</th><th>Supplier</th><th>Test</th></tr>";
    foreach ($pos as $po) {
        echo "<tr>";
        echo "<td>" . $po['id'] . "</td>";
        echo "<td>" . $po['po_number'] . "</td>";
        echo "<td>" . $po['supplier_name'] . "</td>";
        echo "<td><a href='get_po_details.php?id=" . $po['id'] . "' target='_blank'>Test JSON</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test the first PO
    $test_po = $pos[0];
    echo "<h2>Testing PO ID: " . $test_po['id'] . "</h2>";
    
    // Test get_po_details function
    $po_details = get_po_details($test_po['id']);
    
    if ($po_details) {
        echo "<h3>✓ PO Details Found:</h3>";
        echo "<pre>";
        print_r($po_details);
        echo "</pre>";
    } else {
        echo "<h3>✗ PO Details Not Found</h3>";
    }
    
    // Test get_po_messages function
    $messages = get_po_messages($test_po['id']);
    echo "<h3>Messages:</h3>";
    echo "<pre>";
    print_r($messages);
    echo "</pre>";
    
} else {
    echo "<p>No purchase orders found in database.</p>";
    echo "<p><a href='setup_po_database.php'>Setup PO Database</a></p>";
}

// Check database tables
echo "<h2>Database Tables Check:</h2>";
$tables = ['purchase_orders', 'purchase_order_items', 'purchase_order_messages'];
foreach ($tables as $table) {
    $query = "SELECT COUNT(*) as count FROM $table";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✓ Table '$table': " . $count['count'] . " records</p>";
    } catch (Exception $e) {
        echo "<p>✗ Table '$table': Error - " . $e->getMessage() . "</p>";
    }
}
?>
