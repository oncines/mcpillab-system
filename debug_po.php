<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'Test User';
$_SESSION['user_role'] = 'store';

echo "<h2>Debug Purchase Order Creation</h2>";

// Check database connection
echo "<h3>1. Database Connection</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check suppliers table
echo "<h3>2. Suppliers Table</h3>";
try {
    $query = "SELECT COUNT(*) as count FROM suppliers";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "<p style='color: green;'>✓ Found {$result['count']} suppliers</p>";
        
        // Show first supplier
        $query = "SELECT id, name, supplier_code FROM suppliers LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Using supplier: ID={$supplier['id']}, Name={$supplier['name']}</p>";
        $supplier_id = $supplier['id'];
    } else {
        echo "<p style='color: red;'>✗ No suppliers found</p>";
        
        // Add a test supplier
        echo "<p>Adding test supplier...</p>";
        $query = "INSERT INTO suppliers (supplier_code, name, contact_person, email, phone, address, city, country) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $result = $stmt->execute(['TEST001', 'Test Supplier', 'Test Contact', 'test@example.com', '123-456-7890', '123 Test St', 'Test City', 'Test Country']);
        
        if ($result) {
            $supplier_id = $db->lastInsertId();
            echo "<p style='color: green;'>✓ Test supplier added with ID: $supplier_id</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to add test supplier</p>";
            exit;
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking suppliers: " . $e->getMessage() . "</p>";
    exit;
}

// Check purchase_orders table structure
echo "<h3>3. Purchase Orders Table Structure</h3>";
try {
    $query = "DESCRIBE purchase_orders";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $column) {
        echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking table structure: " . $e->getMessage() . "</p>";
}

// Test purchase order creation
echo "<h3>4. Testing Purchase Order Creation</h3>";
try {
    $test_po_number = 'TEST-' . date('YmdHis');
    $order_date = date('Y-m-d');
    $expected_delivery_date = date('Y-m-d', strtotime('+7 days'));
    $items = [
        [
            'item_name' => 'Test Item',
            'quantity' => 5,
            'unit_price' => 10.50
        ]
    ];
    $notes = 'Test PO';
    $created_by = $_SESSION['user_id'];
    
    echo "<p>Creating PO with:</p>";
    echo "<ul>";
    echo "<li>PO Number: $test_po_number</li>";
    echo "<li>Supplier ID: $supplier_id</li>";
    echo "<li>Order Date: $order_date</li>";
    echo "<li>Items: " . count($items) . " item(s)</li>";
    echo "</ul>";
    
    // Call the function
    $po_id = create_purchase_order($test_po_number, $supplier_id, $order_date, $expected_delivery_date, $items, $notes, $created_by);
    
    if ($po_id) {
        echo "<p style='color: green;'>✓ Purchase Order created successfully! ID: $po_id</p>";
        
        // Verify it was created
        $query = "SELECT * FROM purchase_orders WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$po_id]);
        $po = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($po) {
            echo "<p>Verification successful:</p>";
            echo "<ul>";
            echo "<li>PO Number: {$po['po_number']}</li>";
            echo "<li>Supplier ID: {$po['supplier_id']}</li>";
            echo "<li>Total Amount: {$po['total_amount']}</li>";
            echo "<li>Status: {$po['status']}</li>";
            echo "</ul>";
        }
        
        // Check items
        $query = "SELECT COUNT(*) as count FROM purchase_order_items WHERE po_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$po_id]);
        $items_count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Items created: {$items_count['count']}</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Failed to create Purchase Order</p>";
        
        // Check for duplicate PO number
        $query = "SELECT COUNT(*) as count FROM purchase_orders WHERE po_number = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$test_po_number]);
        $duplicate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($duplicate['count'] > 0) {
            echo "<p style='color: orange;'>Note: PO Number already exists</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception during PO creation: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>Debug complete!</strong></p>";
?>
