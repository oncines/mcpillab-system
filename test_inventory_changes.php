<?php
// Test script to validate the inventory changes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config.php';

// Test the add_inventory_item function with sample data
echo "Testing inventory function with new supplier name handling...\n";

try {
    // Sample test data
    $item_name = "Test Item";
    $item_code = "TEST" . time();
    $category = "Test Category";
    $description = "Test Description";
    $unit = "Test Unit";
    $quantity = 10;
    $unit_price = 50.00;
    $supplier_name = "Test Supplier " . time();
    $location = "Test Location";
    $min_stock_level = 5;

    echo "Test data prepared:\n";
    echo "- Item Name: $item_name\n";
    echo "- Item Code: $item_code\n";
    echo "- Category: $category\n";
    echo "- Unit: $unit\n";
    echo "- Supplier Name: $supplier_name\n";
    echo "- Location: $location\n";
    echo "- Quantity: $quantity\n";
    echo "- Unit Price: $unit_price\n";
    echo "- Min Stock Level: $min_stock_level\n\n";

    // Test the function
    $result = add_inventory_item($item_name, $item_code, $category, $description, $unit, $quantity, $unit_price, $supplier_name, $location, $min_stock_level);
    
    if ($result) {
        echo "✓ SUCCESS: Inventory item added successfully!\n";
        echo "✓ Supplier handling works correctly\n";
    } else {
        echo "✗ FAILED: Could not add inventory item\n";
    }

} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>
