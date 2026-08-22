<?php
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup and Test Inventory</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h2>Inventory Setup and Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<div class='alert alert-info'>🔧 Setting up inventory tables...</div>";
    
    // Drop existing tables to start fresh
    $db->exec("SET FOREIGN_KEY_CHECKS=0");
    $db->exec("DROP TABLE IF EXISTS inventory_transactions");
    $db->exec("DROP TABLE IF EXISTS inventory_stock");
    $db->exec("DROP TABLE IF EXISTS inventory_items");
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "<div class='alert alert-warning'>⚠️ Cleaned up existing tables</div>";
    
    // Create inventory_items table
    $sql1 = "CREATE TABLE inventory_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(200) NOT NULL,
        barcode VARCHAR(100) UNIQUE,
        size VARCHAR(50),
        unit VARCHAR(20) NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        category VARCHAR(50),
        supplier_id INT,
        location VARCHAR(100),
        min_stock_level INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql1);
    echo "<div class='alert alert-success'>✓ inventory_items table created</div>";
    
    // Create inventory_stock table
    $sql2 = "CREATE TABLE inventory_stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT UNIQUE NOT NULL,
        beginning_stock DECIMAL(10,2) DEFAULT 0,
        bodega_stock DECIMAL(10,2) DEFAULT 0,
        shelves_stock DECIMAL(10,2) DEFAULT 0,
        delivery_stock DECIMAL(10,2) DEFAULT 0,
        total_stock DECIMAL(10,2) DEFAULT 0,
        total_amount DECIMAL(12,2) DEFAULT 0,
        suggested_order DECIMAL(10,2) DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql2);
    echo "<div class='alert alert-success'>✓ inventory_stock table created</div>";
    
    // Create inventory_transactions table
    $sql3 = "CREATE TABLE inventory_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        transaction_type ENUM('beginning', 'delivery', 'adjustment', 'sale', 'return') NOT NULL,
        quantity DECIMAL(10,2) NOT NULL,
        bodega_quantity DECIMAL(10,2) DEFAULT 0,
        shelves_quantity DECIMAL(10,2) DEFAULT 0,
        delivery_quantity DECIMAL(10,2) DEFAULT 0,
        unit_price DECIMAL(10,2),
        reference_number VARCHAR(50),
        notes TEXT,
        transaction_date DATE NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql3);
    echo "<div class='alert alert-success'>✓ inventory_transactions table created</div>";
    
    echo "<div class='alert alert-info'>🧪 Testing inventory item addition...</div>";
    
    // Test adding an inventory item
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
    
    // Check if suppliers exist
    $supplier_check = $db->query("SELECT COUNT(*) as count FROM suppliers");
    $supplier_count = $supplier_check->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($supplier_count == 0) {
        echo "<div class='alert alert-warning'>⚠️ No suppliers found. Creating sample supplier...</div>";
        $db->exec("INSERT INTO suppliers (supplier_code, name, contact_person, email, phone, address, city, country) VALUES 
                  ('SUP001', 'Test Supplier', 'Test Person', 'test@example.com', '123-456-7890', '123 Test St', 'Test City', 'Test Country')");
        $supplier_id = 1;
    }
    
    // Simulate user session for testing
    $_SESSION['user_id'] = 1;
    
    $success = add_inventory_item($item_name, $item_code, $category, $description, $unit, $quantity, $unit_price, $supplier_id, $location, $min_stock_level);
    
    if ($success) {
        echo "<div class='alert alert-success'>✅ Inventory item added successfully!</div>";
        
        // Verify the item was added
        $check = $db->query("SELECT * FROM inventory_items WHERE barcode = 'TEST001'");
        $item = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            echo "<div class='alert alert-info'>
                <strong>Verification:</strong><br>
                Item ID: {$item['id']}<br>
                Name: {$item['item_name']}<br>
                Barcode: {$item['barcode']}<br>
                Unit: {$item['unit']}<br>
                Price: {$item['unit_price']}<br>
                Location: {$item['location']}
            </div>";
        }
        
        // Check stock record
        $stock_check = $db->query("SELECT * FROM inventory_stock WHERE item_id = {$item['id']}");
        $stock = $stock_check->fetch(PDO::FETCH_ASSOC);
        
        if ($stock) {
            echo "<div class='alert alert-info'>
                <strong>Stock Record:</strong><br>
                Beginning Stock: {$stock['beginning_stock']}<br>
                Total Stock: {$stock['total_stock']}<br>
                Total Amount: {$stock['total_amount']}
            </div>";
        }
        
    } else {
        echo "<div class='alert alert-danger'>❌ Failed to add inventory item.</div>";
    }
    
    echo "<div class='alert alert-success mt-4'>
        <h4>✅ Setup completed!</h4>
        <p>Inventory tables are now ready for use.</p>
        <a href='inventory.php' class='btn btn-primary'>Go to Inventory Management</a>
        <a href='create_inventory_tables.php' class='btn btn-secondary'>Run Original Setup</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
        <h4>Error: " . $e->getMessage() . "</h4>
        <pre>" . $e->getTraceAsString() . "</pre>
    </div>";
}

echo "</div></body></html>";
?>
