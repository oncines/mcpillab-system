<?php
require_once 'config.php';

echo "<h1>Purchase Order Functionality Test</h1>";

// Create a test user if not exists (store role)
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if test store user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = 'store@test.com'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "<p>Creating test store user...</p>";
        $hashed_password = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (email, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['store@test.com', $hashed_password, 'Test Store User', 'store']);
        echo "<p style='color: green;'>✓ Test store user created (store@test.com / test123)</p>";
    }
    
    // Get first supplier
    $stmt = $db->query("SELECT id, name FROM suppliers LIMIT 1");
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$supplier) {
        echo "<p style='color: red;'>No suppliers found. Please run test_po_setup.php first.</p>";
        exit;
    }
    
    echo "<p>Using supplier: {$supplier['name']} (ID: {$supplier['id']})</p>";
    
    // Test creating a PO
    echo "<h2>Testing Purchase Order Creation</h2>";
    
    $po_number = generate_po_number();
    $supplier_id = $supplier['id'];
    $order_date = date('Y-m-d');
    $expected_delivery_date = date('Y-m-d', strtotime('+7 days'));
    $items = [
        [
            'item_name' => 'Test Item 1',
            'quantity' => 10,
            'unit_price' => 25.50
        ],
        [
            'item_name' => 'Test Item 2',
            'quantity' => 5,
            'unit_price' => 100.00
        ]
    ];
    $notes = 'Test purchase order created by test script';
    $created_by = 1; // Assuming user ID 1 exists
    
    echo "<p>Creating PO with:</p>";
    echo "<ul>";
    echo "<li>PO Number: $po_number</li>";
    echo "<li>Supplier ID: $supplier_id</li>";
    echo "<li>Order Date: $order_date</li>";
    echo "<li>Items: " . count($items) . " items</li>";
    echo "</ul>";
    
    $po_id = create_purchase_order($po_number, $supplier_id, $order_date, $expected_delivery_date, $items, $notes, $created_by);
    
    if ($po_id) {
        echo "<p style='color: green;'>✓ Purchase Order created successfully! ID: $po_id</p>";
        
        // Verify the PO was created
        $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
        $stmt->execute([$po_id]);
        $po = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($po) {
            echo "<h3>Purchase Order Details:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($po as $key => $value) {
                echo "<tr><td>$key</td><td>$value</td></tr>";
            }
            echo "</table>";
            
            // Get items
            $stmt = $db->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
            $stmt->execute([$po_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Purchase Order Items:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Item Name</th><th>Quantity</th><th>Unit Price</th><th>Total Price</th></tr>";
            foreach ($items as $item) {
                echo "<tr>";
                echo "<td>{$item['item_name']}</td>";
                echo "<td>{$item['quantity']}</td>";
                echo "<td>₱" . number_format($item['unit_price'], 2) . "</td>";
                echo "<td>₱" . number_format($item['total_price'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Failed to create Purchase Order</p>";
        echo "<p>Check error logs for details.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='test_po_setup.php'>Run Setup Test</a></p>";
echo "<p><a href='purchase_order.php'>Go to Purchase Order Page</a></p>";
echo "<p><a href='index.php'>Go to Login</a></p>";
?>
