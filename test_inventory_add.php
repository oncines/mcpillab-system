<?php
require_once 'config.php';

// Test adding an inventory item
echo "Testing inventory item addition...\n";

$item_name = "Test Item";
$item_code = "TEST001";
$category = "chemicals";
$description = "Test description";
$unit = "bottle";
$quantity = 10;
$unit_price = 50.00;
$supplier_id = 1; // Use first supplier
$location = "Test Location";
$min_stock_level = 5;

echo "Attempting to add item: $item_name\n";
echo "Supplier ID: $supplier_id\n";

$success = add_inventory_item($item_name, $item_code, $category, $description, $unit, $quantity, $unit_price, $supplier_id, $location, $min_stock_level);

if ($success) {
    echo "✓ Inventory item added successfully!\n";
} else {
    echo "✗ Failed to add inventory item.\n";
    
    // Check for common issues
    echo "\nChecking for issues...\n";
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if inventory_items table exists
    $result = $db->query("SHOW TABLES LIKE 'inventory_items'");
    echo "inventory_items table exists: " . ($result->rowCount() > 0 ? "Yes" : "No") . "\n";
    
    // Check if inventory_stock table exists
    $result = $db->query("SHOW TABLES LIKE 'inventory_stock'");
    echo "inventory_stock table exists: " . ($result->rowCount() > 0 ? "Yes" : "No") . "\n";
    
    // Check if inventory_transactions table exists
    $result = $db->query("SHOW TABLES LIKE 'inventory_transactions'");
    echo "inventory_transactions table exists: " . ($result->rowCount() > 0 ? "Yes" : "No") . "\n";
    
    // Check if suppliers exist
    $result = $db->query("SELECT COUNT(*) as count FROM suppliers");
    $supplier_count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Suppliers available: $supplier_count\n";
    
    if ($supplier_count == 0) {
        echo "❌ No suppliers found! This is likely the issue.\n";
    }
}
?>
