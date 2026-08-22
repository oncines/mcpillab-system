<?php
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Inventory Database Diagnostic</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h2><i class='fas fa-stethoscope'></i> Inventory Database Diagnostic</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<div class='alert alert-info'>Checking database connection...</div>";
    
    // Check if inventory_items table exists
    $check_items = $db->query("SHOW TABLES LIKE 'inventory_items'");
    $items_exists = $check_items->rowCount() > 0;
    
    // Check if inventory_stock table exists
    $check_stock = $db->query("SHOW TABLES LIKE 'inventory_stock'");
    $stock_exists = $check_stock->rowCount() > 0;
    
    echo "<div class='card mb-3'>
        <div class='card-header'>Table Status</div>
        <div class='card-body'>
            <div class='row'>
                <div class='col-md-6'>
                    <strong>inventory_items table:</strong> 
                    <span class='badge bg-" . ($items_exists ? 'success' : 'danger') . "'>" . ($items_exists ? 'EXISTS' : 'MISSING') . "</span>
                </div>
                <div class='col-md-6'>
                    <strong>inventory_stock table:</strong> 
                    <span class='badge bg-" . ($stock_exists ? 'success' : 'danger') . "'>" . ($stock_exists ? 'EXISTS' : 'MISSING') . "</span>
                </div>
            </div>
        </div>
    </div>";
    
    if ($items_exists && $stock_exists) {
        // Check data counts
        $items_count = $db->query("SELECT COUNT(*) as count FROM inventory_items")->fetch(PDO::FETCH_ASSOC)['count'];
        $stock_count = $db->query("SELECT COUNT(*) as count FROM inventory_stock")->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<div class='alert alert-success'>
            <h4>Tables Exist - Checking Data</h4>
            <div class='row'>
                <div class='col-md-6'>
                    <strong>Inventory Items:</strong> $items_count
                </div>
                <div class='col-md-6'>
                    <strong>Stock Records:</strong> $stock_count
                </div>
            </div>";
        
        if ($items_count == 0) {
            echo "<div class='alert alert-warning'>
                <strong>Tables exist but no data found.</strong><br>
                Inserting sample data now...
            </div>";
            
            // Insert sample data
            $sample_items = [
                ['ACEITE DE ALCAMPORADO', 'BAR001', '100ml', 'bottle', 150.00, 'chemicals', 1, 'Bodega-A1', 10, 1.00],
                ['ACETONE', 'BAR002', '500ml', 'bottle', 89.50, 'chemicals', 2, 'Bodega-A2', 15, 1.00],
                ['AGUA OXIGENADA', 'BAR003', '250ml', 'bottle', 45.75, 'chemicals', 1, 'Shelves-B1', 20, 1.00],
                ['ALCOHOL', 'BAR004', '1L', 'bottle', 120.00, 'chemicals', 2, 'Bodega-A3', 12, 1.00],
                ['BENZALKONIUM CHLORIDE', 'BAR005', '500ml', 'bottle', 200.00, 'chemicals', 3, 'Shelves-B2', 8, 1.00]
            ];
            
            $sql_insert = "INSERT INTO inventory_items (item_name, barcode, size, unit, unit_price, category, supplier_id, location, min_stock_level, content) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql_insert);
            
            foreach ($sample_items as $item) {
                try {
                    $stmt->execute($item);
                } catch (PDOException $e) {
                    echo "<div class='alert alert-warning'>Item already exists: " . $item[0] . "</div>";
                }
            }
            
            // Create stock records
            $db->exec("INSERT IGNORE INTO inventory_stock (item_id, beginning_stock, bodega_stock, shelves_stock, delivery_stock, total_stock, suggested_order) 
                       SELECT id, 25, 15, 8, 2, 50, 5 FROM inventory_items");
            
            // Update totals
            $db->exec("UPDATE inventory_stock SET total_amount = (SELECT total_stock * unit_price FROM inventory_items ii WHERE ii.id = inventory_stock.item_id)");
            
            echo "<div class='alert alert-success'>✓ Sample data inserted successfully!</div>";
        } else {
            echo "<div class='alert alert-success'>✓ Data exists and is ready!</div>";
        }
        
        echo "<div class='mt-3'>
            <a href='reports.php' class='btn btn-primary'>
                <i class='fas fa-chart-bar'></i> Test Inventory Report
            </a>
        </div>";
        
    } else {
        echo "<div class='alert alert-danger'>
            <h4>Missing Tables Detected</h4>
            <p>Creating missing tables now...</p>
        </div>";
        
        // Create inventory_items table
        if (!$items_exists) {
            $sql1 = "CREATE TABLE inventory_items (
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
            
            try {
                $db->exec($sql1);
                echo "<div class='alert alert-success'>✓ inventory_items table created</div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>✗ Failed to create inventory_items: " . $e->getMessage() . "</div>";
            }
        }
        
        // Create inventory_stock table
        if (!$stock_exists) {
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            try {
                $db->exec($sql2);
                echo "<div class='alert alert-success'>✓ inventory_stock table created</div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>✗ Failed to create inventory_stock: " . $e->getMessage() . "</div>";
            }
        }
        
        echo "<div class='alert alert-info'>
            <strong>Tables created!</strong><br>
            <a href='auto_setup_inventory.php' class='btn btn-primary'>Run Setup Script to Add Sample Data</a>
        </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
        <h4>Database Error</h4>
        <p>" . $e->getMessage() . "</p>
    </div>";
}

echo "</div></body></html>";
?>
