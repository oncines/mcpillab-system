<?php
require_once 'config.php';

echo "<h2>Debug Purchase Order Creation</h2>";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'Test User';
$_SESSION['user_role'] = 'store';

echo "<p>Session set: user_id={$_SESSION['user_id']}, role={$_SESSION['user_role']}</p>";

// Test database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connected</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check suppliers
try {
    $query = "SELECT COUNT(*) as count FROM suppliers";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Suppliers in database: " . $count['count'] . "</p>";
    
    if ($count['count'] == 0) {
        echo "<p style='color: red;'>✗ No suppliers found. Adding sample suppliers...</p>";
        
        // Add sample suppliers
        $suppliers = [
            ['MED001', 'MediCorp Pharmaceuticals', 'John Smith', 'john@medicorp.com', '555-0101', '123 Medical St', 'New York', 'USA'],
            ['PHAR002', 'PharmaTech Solutions', 'Sarah Johnson', 'sarah@pharmatech.com', '555-0102', '456 Pharma Ave', 'Los Angeles', 'USA'],
            ['LAB003', 'LabSupply Co.', 'Mike Wilson', 'mike@labsupply.com', '555-0103', '789 Lab Road', 'Chicago', 'USA']
        ];
        
        foreach ($suppliers as $supplier) {
            $query = "INSERT INTO suppliers (supplier_code, name, contact_person, email, phone, address, city, country) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute($supplier);
        }
        
        echo "<p style='color: green;'>✓ Sample suppliers added</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Supplier check failed: " . $e->getMessage() . "</p>";
}

// Test PO creation with sample data
if (isset($_GET['test_create'])) {
    echo "<h3>Testing PO Creation</h3>";
    
    $po_number = 'TEST-' . date('YmdHis');
    $supplier_id = 1; // First supplier
    $order_date = date('Y-m-d');
    $expected_delivery_date = date('Y-m-d', strtotime('+7 days'));
    $notes = 'Test PO creation';
    $created_by = $_SESSION['user_id'];
    
    $items = [
        ['item_name' => 'Test Item 1', 'quantity' => 10, 'unit_price' => 25.50],
        ['item_name' => 'Test Item 2', 'quantity' => 5, 'unit_price' => 15.75]
    ];
    
    echo "<p>Creating PO with:</p>";
    echo "<ul>";
    echo "<li>PO Number: $po_number</li>";
    echo "<li>Supplier ID: $supplier_id</li>";
    echo "<li>Order Date: $order_date</li>";
    echo "<li>Items: " . count($items) . "</li>";
    echo "</ul>";
    
    try {
        $po_id = create_purchase_order($po_number, $supplier_id, $order_date, $expected_delivery_date, $items, $notes, $created_by);
        
        if ($po_id) {
            echo "<p style='color: green;'>✓ PO created successfully with ID: $po_id</p>";
            
            // Verify creation
            $query = "SELECT * FROM purchase_orders WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$po_id]);
            $po = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($po) {
                echo "<h4>Created PO Details:</h4>";
                echo "<table border='1'>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                foreach ($po as $key => $value) {
                    echo "<tr><td>$key</td><td>$value</td></tr>";
                }
                echo "</table>";
            }
            
        } else {
            echo "<p style='color: red;'>✗ PO creation failed</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ PO creation error: " . $e->getMessage() . "</p>";
    }
}

// Check tables
echo "<h3>Table Status</h3>";
$tables = ['purchase_orders', 'purchase_order_items', 'suppliers'];

foreach ($tables as $table) {
    try {
        $query = "SELECT COUNT(*) as count FROM $table";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>$table: " . $count['count'] . " records</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ $table: " . $e->getMessage() . "</p>";
    }
}

?>

<p><a href="?test_create=1">Test PO Creation</a> | <a href="check_suppliers.php">Check Suppliers</a> | <a href="check_po_tables.php">Check PO Tables</a></p>
