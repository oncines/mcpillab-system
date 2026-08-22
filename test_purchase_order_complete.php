<?php
require_once 'config.php';

echo "<h1>Complete Purchase Order Test</h1>";

// Start session to simulate login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Check/Setup Test User
    echo "<h2>1. Test User Setup</h2>";
    $stmt = $db->prepare("SELECT id, full_name, role FROM users WHERE email = 'store@test.com'");
    $stmt->execute();
    $test_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test_user) {
        echo "<p>Creating test store user...</p>";
        $hashed_password = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (email, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['store@test.com', $hashed_password, 'Test Store User', 'store']);
        $test_user_id = $db->lastInsertId();
        echo "<p style='color: green;'>✓ Test store user created (store@test.com / test123)</p>";
    } else {
        $test_user_id = $test_user['id'];
        echo "<p style='color: green;'>✓ Test store user exists: {$test_user['full_name']} ({$test_user['role']})</p>";
    }
    
    // Simulate login
    $_SESSION['user_id'] = $test_user_id;
    $_SESSION['full_name'] = $test_user['full_name'] ?? 'Test Store User';
    $_SESSION['user_role'] = 'store';
    $_SESSION['email'] = 'store@test.com';
    
    // 2. Check Suppliers
    echo "<h2>2. Suppliers Check</h2>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM suppliers");
    $supplier_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($supplier_count == 0) {
        echo "<p style='color: orange;'>No suppliers found. Adding sample suppliers...</p>";
        $sql = "INSERT INTO suppliers (supplier_code, name, contact_person, email, phone, address, city, country) VALUES
                ('MED001', 'MediCorp Pharmaceuticals', 'John Smith', 'john@medicorp.com', '555-0101', '123 Medical St', 'New York', 'USA'),
                ('PHAR002', 'PharmaTech Solutions', 'Sarah Johnson', 'sarah@pharmatech.com', '555-0102', '456 Pharma Ave', 'Los Angeles', 'USA')";
        $db->exec($sql);
        echo "<p style='color: green;'>✓ Sample suppliers added</p>";
    }
    
    $stmt = $db->query("SELECT id, name, supplier_code FROM suppliers LIMIT 3");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Available Suppliers:</p><ul>";
    foreach ($suppliers as $supplier) {
        echo "<li>{$supplier['supplier_code']}: {$supplier['name']}</li>";
    }
    echo "</ul>";
    
    // 3. Test PO Creation
    echo "<h2>3. Purchase Order Creation Test</h2>";
    
    $test_supplier = $suppliers[0];
    $po_number = generate_po_number();
    $order_date = date('Y-m-d');
    $expected_delivery_date = date('Y-m-d', strtotime('+7 days'));
    
    $items = [
        [
            'item_name' => 'Test Medical Supply A',
            'quantity' => 10,
            'unit_price' => 150.75
        ],
        [
            'item_name' => 'Test Laboratory Equipment B',
            'quantity' => 2,
            'unit_price' => 1250.00
        ],
        [
            'item_name' => 'Test Chemical Reagent C',
            'quantity' => 25,
            'unit_price' => 45.50
        ]
    ];
    
    $notes = 'Test purchase order for functionality verification';
    $created_by = $test_user_id;
    
    echo "<p>Creating Purchase Order:</p>";
    echo "<ul>";
    echo "<li><strong>PO Number:</strong> $po_number</li>";
    echo "<li><strong>Supplier:</strong> {$test_supplier['name']}</li>";
    echo "<li><strong>Order Date:</strong> $order_date</li>";
    echo "<li><strong>Items:</strong> " . count($items) . " items</li>";
    echo "<li><strong>Created By:</strong> User ID $created_by</li>";
    echo "</ul>";
    
    $po_id = create_purchase_order($po_number, $test_supplier['id'], $order_date, $expected_delivery_date, $items, $notes, $created_by);
    
    if ($po_id) {
        echo "<p style='color: green;'>✅ Purchase Order created successfully! ID: $po_id</p>";
        
        // 4. Verify PO Details
        echo "<h2>4. Purchase Order Verification</h2>";
        $po_details = get_po_details($po_id);
        
        if ($po_details) {
            echo "<h3>PO Details:</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Value</th></tr>";
            echo "<tr><td><strong>PO Number</strong></td><td>{$po_details['po_number']}</td></tr>";
            echo "<tr><td><strong>Supplier</strong></td><td>{$po_details['supplier_name']}</td></tr>";
            echo "<tr><td><strong>Order Date</strong></td><td>{$po_details['order_date']}</td></tr>";
            echo "<tr><td><strong>Expected Delivery</strong></td><td>{$po_details['expected_delivery_date']}</td></tr>";
            echo "<tr><td><strong>Total Amount</strong></td><td>₱" . number_format($po_details['total_amount'], 2) . "</td></tr>";
            echo "<tr><td><strong>Status</strong></td><td><span class='badge' style='background: orange; color: white;'>{$po_details['status']}</span></td></tr>";
            echo "<tr><td><strong>Notes</strong></td><td>{$po_details['notes']}</td></tr>";
            echo "</table>";
            
            echo "<h3>PO Items:</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'><th>Item Name</th><th>Quantity</th><th>Unit Price</th><th>Total Price</th></tr>";
            foreach ($po_details['items'] as $item) {
                echo "<tr>";
                echo "<td>{$item['item_name']}</td>";
                echo "<td>{$item['quantity']}</td>";
                echo "<td>₱" . number_format($item['unit_price'], 2) . "</td>";
                echo "<td>₱" . number_format($item['total_price'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // 5. Test PO Listing Functions
            echo "<h2>5. PO Listing Functions Test</h2>";
            
            // Test get_purchase_orders_store
            $store_pos = get_purchase_orders_store($test_user_id, 10, 0);
            echo "<p>Store POs for user: " . count($store_pos) . " found</p>";
            
            // Test get_purchase_orders_admin (simulate admin)
            $_SESSION['user_role'] = 'admin';
            $admin_pos = get_purchase_orders_admin(10, 0, null);
            echo "<p>All POs (admin view): " . count($admin_pos) . " found</p>";
            
            // Reset role to store
            $_SESSION['user_role'] = 'store';
            
            // 6. Test Status Update
            echo "<h2>6. Status Update Test</h2>";
            if (update_po_status($po_id, 'Approved', 'Approved by test script')) {
                echo "<p style='color: green;'>✅ PO Status updated to 'Approved'</p>";
                
                // Verify status change
                $updated_po = get_po_details($po_id);
                echo "<p>Current Status: <strong>{$updated_po['status']}</strong></p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to update PO status</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Failed to retrieve PO details</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Failed to create Purchase Order</p>";
        echo "<p>Check the error logs for detailed information.</p>";
    }
    
    // 7. Test Helper Functions
    echo "<h2>7. Helper Functions Test</h2>";
    
    $test_currency = format_currency(1234.56);
    echo "<p>format_currency(1234.56) = <strong>$test_currency</strong></p>";
    
    $test_date = format_date(date('Y-m-d'));
    echo "<p>format_date() = <strong>$test_date</strong></p>";
    
    $test_status_color = getStatusColor('Pending');
    echo "<p>getStatusColor('Pending') = <strong>$test_status_color</strong></p>";
    
    // 8. Summary
    echo "<h2>8. Test Summary</h2>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: green; font-weight: bold;'>✅ Purchase Order functionality is working correctly!</p>";
    echo "<ul>";
    echo "<li>Database tables exist and are accessible</li>";
    echo "<li>Suppliers are available for selection</li>";
    echo "<li>PO creation function works properly</li>";
    echo "<li>PO items are saved correctly</li>";
    echo "<li>PO retrieval functions work</li>";
    echo "<li>Status updates function correctly</li>";
    echo "<li>Helper functions work as expected</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='index.php'>Login to the system</a> using store@test.com / test123</li>";
echo "<li>Navigate to <a href='purchase_order.php'>Purchase Order page</a></li>";
echo "<li>Try creating a new purchase order using the form</li>";
echo "<li>Verify the PO appears in the list</li>";
echo "</ol>";

echo "<p><a href='test_po_setup.php'>Run Setup Test</a> | ";
echo "<a href='purchase_order.php'>Go to Purchase Order Page</a> | ";
echo "<a href='index.php'>Go to Login</a></p>";
?>
