<?php
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Inventory Tables</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h2>Setting up Inventory Tables</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<div class='alert alert-info'>Connecting to database...</div>";
    
    // Create inventory_items table
    $sql1 = "CREATE TABLE IF NOT EXISTS inventory_items (
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
    )";
    
    $db->exec($sql1);
    echo "<div class='alert alert-success'>✓ inventory_items table created</div>";
    
    // Create inventory_stock table
    $sql2 = "CREATE TABLE IF NOT EXISTS inventory_stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT UNIQUE NOT NULL,
        beginning_stock DECIMAL(10,2) DEFAULT 0,
        bodega_stock DECIMAL(10,2) DEFAULT 0,
        shelves_stock DECIMAL(10,2) DEFAULT 0,
        delivery_stock DECIMAL(10,2) DEFAULT 0,
        total_stock DECIMAL(10,2) DEFAULT 0,
        total_amount DECIMAL(12,2) DEFAULT 0,
        suggested_order DECIMAL(10,2) DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES inventory_items(id)
    )";
    
    $db->exec($sql2);
    echo "<div class='alert alert-success'>✓ inventory_stock table created</div>";
    
    // Create inventory_transactions table
    $sql3 = "CREATE TABLE IF NOT EXISTS inventory_transactions (
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES inventory_items(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    
    $db->exec($sql3);
    echo "<div class='alert alert-success'>✓ inventory_transactions table created</div>";
    
    // Check if we have sample data
    $check = $db->query("SELECT COUNT(*) as count FROM inventory_items");
    $result = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Insert sample data
        $sample_items = [
            ['ACEITE DE ALCAMPORADO', 'BAR001', '100ml', 'bottle', 150.00, 'chemicals', 1, 'Bodega-A1', 10],
            ['ACETONE', 'BAR002', '500ml', 'bottle', 89.50, 'chemicals', 2, 'Bodega-A2', 15],
            ['AGUA OXIGENADA', 'BAR003', '250ml', 'bottle', 45.75, 'chemicals', 1, 'Shelves-B1', 20],
            ['ALCOHOL', 'BAR004', '1L', 'bottle', 120.00, 'chemicals', 2, 'Bodega-A3', 12],
            ['BENZALKONIUM CHLORIDE', 'BAR005', '500ml', 'bottle', 200.00, 'chemicals', 3, 'Shelves-B2', 8]
        ];
        
        foreach ($sample_items as $item) {
            $sql = "INSERT INTO inventory_items (item_name, barcode, size, unit, unit_price, category, supplier_id, location, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute($item);
        }
        
        echo "<div class='alert alert-success'>✓ Sample inventory items inserted</div>";
        
        // Create stock records
        $db->exec("INSERT INTO inventory_stock (item_id, beginning_stock, bodega_stock, shelves_stock, delivery_stock, total_stock, suggested_order) 
                   SELECT id, 25, 15, 8, 2, 50, 5 FROM inventory_items");
        
        echo "<div class='alert alert-success'>✓ Stock records created</div>";
    } else {
        echo "<div class='alert alert-warning'>⚠ Sample data already exists</div>";
    }
    
    echo "<div class='alert alert-success'>
        <h4>✓ Setup completed successfully!</h4>
        <p>Inventory tables are now ready for use.</p>
        <a href='reports.php' class='btn btn-primary'>Go to Reports</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
        <h4>Error: " . $e->getMessage() . "</h4>
    </div>";
}

echo "</div></body></html>";
?>
