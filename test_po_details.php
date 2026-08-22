<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get a sample PO ID for testing
$database = new Database();
$db = $database->getConnection();

$query = "SELECT id FROM purchase_orders LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$po = $stmt->fetch(PDO::FETCH_ASSOC);

if ($po) {
    $po_id = $po['id'];
    
    // Test get_po_details function
    $po_details = get_po_details($po_id);
    
    echo "<h1>Test PO Details</h1>";
    echo "<h2>PO ID: " . $po_id . "</h2>";
    
    if ($po_details) {
        echo "<h3>PO Details Found:</h3>";
        echo "<pre>";
        print_r($po_details);
        echo "</pre>";
    } else {
        echo "<h3>No PO Details Found</h3>";
    }
    
    // Test JSON response
    echo "<h2>Testing JSON Response:</h2>";
    echo "<a href='get_po_details.php?id=" . $po_id . "' target='_blank'>Click here to test JSON response</a>";
    
} else {
    echo "<h1>No Purchase Orders Found</h1>";
    echo "<p>Please create a purchase order first.</p>";
}
?>
