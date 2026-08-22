<?php
require_once 'config.php';

// Check if purchase orders exist
$database = new Database();
$db = $database->getConnection();

$query = "SELECT COUNT(*) as count FROM purchase_orders";
$stmt = $db->prepare($query);
$stmt->execute();
$count = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h1>Purchase Order Data Check</h1>";
echo "<p>Total Purchase Orders: " . $count['count'] . "</p>";

if ($count['count'] > 0) {
    // Get first 5 POs
    $query = "SELECT id, po_number, supplier_name, status FROM purchase_orders LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $pos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Sample Purchase Orders:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>PO Number</th><th>Supplier</th><th>Status</th><th>Test Link</th></tr>";
    
    foreach ($pos as $po) {
        echo "<tr>";
        echo "<td>" . $po['id'] . "</td>";
        echo "<td>" . $po['po_number'] . "</td>";
        echo "<td>" . $po['supplier_name'] . "</td>";
        echo "<td>" . $po['status'] . "</td>";
        echo "<td><a href='get_po_details.php?id=" . $po['id'] . "' target='_blank'>Test JSON</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No purchase orders found. Please create some test data.</p>";
    echo "<p><a href='setup_po_database.php'>Setup PO Database</a></p>";
}
?>
