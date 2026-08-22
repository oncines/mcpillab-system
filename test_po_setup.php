<?php
require_once 'config.php';

echo "<h1>Purchase Order Setup Test</h1>";

// Check if suppliers table exists and has data
echo "<h2>Checking Suppliers Table</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if suppliers table exists
    $stmt = $db->query("SHOW TABLES LIKE 'suppliers'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Suppliers table exists</p>";
        
        // Check supplier count
        $stmt = $db->query("SELECT COUNT(*) as count FROM suppliers");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Supplier count: " . $result['count'] . "</p>";
        
        if ($result['count'] == 0) {
            echo "<p style='color: orange;'>Adding sample suppliers...</p>";
            
            // Add sample suppliers
            $sql = "INSERT INTO suppliers (supplier_code, name, contact_person, email, phone, address, city, country) VALUES
                    ('MED001', 'MediCorp Pharmaceuticals', 'John Smith', 'john@medicorp.com', '555-0101', '123 Medical St', 'New York', 'USA'),
                    ('PHAR002', 'PharmaTech Solutions', 'Sarah Johnson', 'sarah@pharmatech.com', '555-0102', '456 Pharma Ave', 'Los Angeles', 'USA'),
                    ('LAB003', 'LabSupply Co.', 'Mike Wilson', 'mike@labsupply.com', '555-0103', '789 Lab Road', 'Chicago', 'USA'),
                    ('CHEM004', 'ChemPro Industries', 'Dr. Robert Brown', 'robert@chempro.com', '555-0104', '321 Chemical Blvd', 'Houston', 'USA'),
                    ('EQP005', 'Equipment Plus', 'Lisa Davis', 'lisa@equipmentplus.com', '555-0105', '654 Equipment Way', 'Phoenix', 'USA')";
            
            $db->exec($sql);
            echo "<p style='color: green;'>✓ Sample suppliers added successfully</p>";
        }
        
        // List suppliers
        $stmt = $db->query("SELECT id, supplier_code, name FROM suppliers ORDER BY name");
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Available Suppliers:</h3><ul>";
        foreach ($suppliers as $supplier) {
            echo "<li>ID: {$supplier['id']} - {$supplier['supplier_code']}: {$supplier['name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Suppliers table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check purchase orders tables
echo "<h2>Checking Purchase Orders Tables</h2>";
$tables = ['purchase_orders', 'purchase_order_items', 'purchase_order_messages'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ $table table exists</p>";
            
            // Check record count
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>$table record count: " . $result['count'] . "</p>";
        } else {
            echo "<p style='color: red;'>✗ $table table does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking $table: " . $e->getMessage() . "</p>";
    }
}

// Test PO number generation
echo "<h2>Testing PO Number Generation</h2>";
$po_number = generate_po_number();
echo "<p>Generated PO Number: <strong>$po_number</strong></p>";

// Check if functions exist
echo "<h2>Checking Required Functions</h2>";
$functions = [
    'create_purchase_order',
    'get_purchase_orders_admin',
    'get_purchase_orders_store',
    'get_suppliers',
    'get_po_details',
    'update_po_status',
    'delete_purchase_order',
    'archive_purchase_order'
];

foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "<p style='color: green;'>✓ Function $function exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Function $function missing</p>";
    }
}

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='purchase_order.php'>Go to Purchase Order Page</a></p>";
echo "<p><a href='index.php'>Go to Login</a></p>";
?>
