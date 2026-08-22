<?php
require_once 'config.php';

echo "<h2>Testing Inventory Save Function</h2>";

// Test data
$test_data = [
    'item_name' => 'Test Item',
    'item_code' => 'TEST001',
    'category' => 'Test Category',
    'description' => 'This is a test item',
    'unit' => 'pieces',
    'quantity' => 10,
    'unit_price' => 50.00,
    'supplier_name' => 'Test Supplier',
    'location' => 'Test Location',
    'min_stock_level' => 5
];

echo "<h3>Testing add_inventory_item function...</h3>";

// Call the function
$result = add_inventory_item(
    $test_data['item_name'],
    $test_data['item_code'],
    $test_data['category'],
    $test_data['description'],
    $test_data['unit'],
    $test_data['quantity'],
    $test_data['unit_price'],
    $test_data['supplier_name'],
    $test_data['location'],
    $test_data['min_stock_level']
);

if ($result) {
    echo "<p style='color: green;'>✓ SUCCESS: Inventory item saved successfully!</p>";
    
    // Verify it was saved
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT ii.*, s.name as supplier_name 
              FROM inventory_items ii 
              LEFT JOIN suppliers s ON ii.supplier_id = s.id 
              WHERE ii.barcode = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$test_data['item_code']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($item) {
        echo "<h4>Item Details:</h4>";
        echo "<pre>";
        print_r($item);
        echo "</pre>";
    }
    
} else {
    echo "<p style='color: red;'>✗ FAILED: Could not save inventory item</p>";
    echo "<p>Check the error logs for more details.</p>";
}

echo "<p><a href='inventory_form.php'>Go to Inventory Form</a></p>";
?>
