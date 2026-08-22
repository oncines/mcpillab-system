<?php
// Auto-setup inventory tables - this script will create tables automatically
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Auto Setup Inventory Tables</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h2><i class='fas fa-database'></i> Auto Setup Inventory Tables</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<div class='alert alert-info'>Connecting to database...</div>";
    
    // 1. Create inventory_items table
    $sql1 = "CREATE TABLE IF NOT EXISTS inventory_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(200) NOT NULL,
        barcode VARCHAR(100) UNIQUE,
        size VARCHAR(50),
        unit VARCHAR(20) NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        category VARCHAR(50),
        supplier_id INT,
        location VARCHAR(100),
        min_stock_level INT DEFAULT 0,
        content DECIMAL(5,2) DEFAULT 1.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql1);
    echo "<div class='alert alert-success'>✓ inventory_items table created successfully</div>";
    
    // 2. Create inventory_stock table
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
        FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql2);
    echo "<div class='alert alert-success'>✓ inventory_stock table created successfully</div>";
    
    // 3. Check if we have sample data
    $check = $db->query("SELECT COUNT(*) as count FROM inventory_items");
    $result = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo "<div class='alert alert-info'>Inserting sample inventory data...</div>";
        
        // Insert sample inventory items
        $sample_items = [
            ['ACEITE DE ALCAMPORADO', 'BAR001', '100ml', 'bottle', 150.00, 'chemicals', 1, 'Bodega-A1', 10, 1.00],
            ['ACETONE', 'BAR002', '500ml', 'bottle', 89.50, 'chemicals', 2, 'Bodega-A2', 15, 1.00],
            ['AGUA OXIGENADA', 'BAR003', '250ml', 'bottle', 45.75, 'chemicals', 1, 'Shelves-B1', 20, 1.00],
            ['ALCOHOL', 'BAR004', '1L', 'bottle', 120.00, 'chemicals', 2, 'Bodega-A3', 12, 1.00],
            ['BENZALKONIUM CHLORIDE', 'BAR005', '500ml', 'bottle', 200.00, 'chemicals', 3, 'Shelves-B2', 8, 1.00],
            ['GENTIAN VIOLET', 'BAR006', '50ml', 'bottle', 75.25, 'chemicals', 1, 'Bodega-A4', 10, 1.00],
            ['LOVELY BABY OIL', 'BAR007', '200ml', 'bottle', 95.50, 'consumables', 1, 'Shelves-B3', 15, 1.00],
            ['MEGADONE [POVIDONE IODINE]', 'BAR008', '500ml', 'bottle', 180.00, 'chemicals', 2, 'Bodega-A5', 10, 1.00],
            ['MCSON SCENT', 'BAR009', '100ml', 'bottle', 65.00, 'consumables', 3, 'Shelves-B4', 20, 1.00],
            ['OIL OF WINTERGREEN', 'BAR010', '50ml', 'bottle', 85.75, 'chemicals', 1, 'Bodega-A6', 12, 1.00],
            ['PURE TAWAS POWDER', 'BAR011', '100g', 'box', 35.50, 'consumables', 2, 'Shelves-B5', 25, 1.00],
            ['REFINED MINERAL OIL [CLASS-A]', 'BAR012', '1L', 'bottle', 110.00, 'chemicals', 3, 'Bodega-A7', 10, 1.00],
            ['SALICYLIC ACID 10%', 'BAR013', '250ml', 'bottle', 145.00, 'chemicals', 1, 'Shelves-B6', 8, 1.00],
            ['CARE BABY OIL', 'BAR014', '200ml', 'bottle', 88.00, 'consumables', 2, 'Bodega-A8', 15, 1.00],
            ['MURIATIC ACID', 'BAR015', '500ml', 'bottle', 95.00, 'chemicals', 3, 'Shelves-B7', 10, 1.00],
            ['MURIATICA', 'BAR016', '1L', 'bottle', 125.00, 'chemicals', 1, 'Bodega-A9', 8, 1.00],
            ['RUGBY', 'BAR017', '100ml', 'bottle', 55.50, 'consumables', 2, 'Shelves-B8', 20, 1.00]
        ];
        
        $sql_insert = "INSERT INTO inventory_items (item_name, barcode, size, unit, unit_price, category, supplier_id, location, min_stock_level, content) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql_insert);
        
        foreach ($sample_items as $item) {
            $stmt->execute($item);
        }
        
        echo "<div class='alert alert-success'>✓ " . count($sample_items) . " sample inventory items inserted</div>";
        
        // Create stock records for each item
        $sql_stock = "INSERT INTO inventory_stock (item_id, beginning_stock, bodega_stock, shelves_stock, delivery_stock, total_stock, suggested_order) 
                      SELECT id, 25, 15, 8, 2, 50, 5 FROM inventory_items";
        $db->exec($sql_stock);
        
        echo "<div class='alert alert-success'>✓ Stock records created for all items</div>";
        
        // Update total_amount
        $sql_update = "UPDATE inventory_stock 
                       SET total_amount = (SELECT total_stock * unit_price FROM inventory_items ii WHERE ii.id = inventory_stock.item_id)";
        $db->exec($sql_update);
        
        echo "<div class='alert alert-success'>✓ Total amounts calculated</div>";
        
    } else {
        echo "<div class='alert alert-warning'>⚠ Sample data already exists (" . $result['count'] . " items)</div>";
    }
    
    // 4. Verify tables exist and show summary
    $verify = $db->query("SELECT COUNT(*) as items FROM inventory_items");
    $items_count = $verify->fetch(PDO::FETCH_ASSOC)['items'];
    
    $verify_stock = $db->query("SELECT COUNT(*) as stocks FROM inventory_stock");
    $stocks_count = $verify_stock->fetch(PDO::FETCH_ASSOC)['stocks'];
    
    echo "<div class='alert alert-success'>
        <h4><i class='fas fa-check-circle'></i> Setup completed successfully!</h4>
        <div class='row'>
            <div class='col-md-6'>
                <strong>Database Summary:</strong><br>
                • Inventory Items: " . $items_count . "<br>
                • Stock Records: " . $stocks_count . "<br>
                • Tables: inventory_items, inventory_stock
            </div>
            <div class='col-md-6'>
                <strong>Next Steps:</strong><br>
                • Inventory reports are now available<br>
                • You can add more items via Inventory Form<br>
                • Stock levels can be updated
            </div>
        </div>
        <hr>
        <a href='reports.php' class='btn btn-primary me-2'>
            <i class='fas fa-chart-bar'></i> Go to Reports
        </a>
        <a href='inventory_form.php' class='btn btn-success'>
            <i class='fas fa-plus'></i> Add New Item
        </a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
        <h4><i class='fas fa-exclamation-triangle'></i> Error: " . $e->getMessage() . "</h4>
        <p>Please check your database connection and permissions.</p>
    </div>";
}

echo "</div></body></html>";
?>
