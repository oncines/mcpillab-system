<?php
require_once 'config.php';

echo "<h2>Reset Purchase Order Database</h2>";
echo "<p style='color: red;'><strong>WARNING:</strong> This will delete all existing purchase orders and related data!</p>";

if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        echo "<h3>Dropping existing tables...</h3>";
        
        // Drop tables in correct order (due to foreign key constraints)
        $tables = [
            'purchase_order_messages',
            'purchase_order_items',
            'purchase_orders',
            'suppliers'
        ];
        
        foreach ($tables as $table) {
            try {
                $db->exec("DROP TABLE IF EXISTS $table");
                echo "<p style='color: orange;'>✓ Dropped table: $table</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Error dropping $table: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h3>Recreating tables...</h3>";
        
        // Recreate suppliers table
        $sql = "CREATE TABLE suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            supplier_code VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            contact_person VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(50),
            country VARCHAR(50),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
        echo "<p style='color: green;'>✓ Created suppliers table</p>";
        
        // Recreate purchase_orders table
        $sql = "CREATE TABLE purchase_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            po_number VARCHAR(20) UNIQUE NOT NULL,
            supplier_id INT NOT NULL,
            store_name VARCHAR(100) NOT NULL,
            order_date DATE NOT NULL,
            expected_delivery_date DATE,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status ENUM('Pending', 'Approved', 'Rejected', 'Processing', 'Completed') DEFAULT 'Pending',
            notes TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
            INDEX idx_po_number (po_number),
            INDEX idx_supplier (supplier_id),
            INDEX idx_status (status),
            INDEX idx_created_by (created_by),
            INDEX idx_order_date (order_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
        echo "<p style='color: green;'>✓ Created purchase_orders table</p>";
        
        // Recreate purchase_order_items table
        $sql = "CREATE TABLE purchase_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            po_id INT NOT NULL,
            item_name VARCHAR(100) NOT NULL,
            description TEXT,
            quantity INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
            INDEX idx_po_id (po_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
        echo "<p style='color: green;'>✓ Created purchase_order_items table</p>";
        
        // Recreate purchase_order_messages table
        $sql = "CREATE TABLE purchase_order_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            po_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            message_type ENUM('admin', 'store') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
            INDEX idx_po_id (po_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
        echo "<p style='color: green;'>✓ Created purchase_order_messages table</p>";
        
        // Add sample suppliers
        echo "<h3>Adding sample data...</h3>";
        
        $suppliers = [
            ['MED001', 'MediCorp Pharmaceuticals', 'John Smith', 'john@medicorp.com', '555-0101', '123 Medical Street', 'New York', 'USA'],
            ['PHAR002', 'PharmaTech Solutions', 'Sarah Johnson', 'sarah@pharmatech.com', '555-0102', '456 Pharma Avenue', 'Los Angeles', 'USA'],
            ['LAB003', 'LabSupply Co.', 'Mike Wilson', 'mike@labsupply.com', '555-0103', '789 Laboratory Road', 'Chicago', 'USA'],
            ['CHEM004', 'ChemPro Industries', 'Dr. Robert Brown', 'robert@chempro.com', '555-0104', '321 Chemical Boulevard', 'Houston', 'USA'],
            ['EQP005', 'Equipment Plus', 'Lisa Davis', 'lisa@equipmentplus.com', '555-0105', '654 Equipment Way', 'Phoenix', 'USA']
        ];
        
        $sql = "INSERT INTO suppliers (supplier_code, name, contact_person, email, phone, address, city, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        foreach ($suppliers as $supplier) {
            $stmt->execute($supplier);
        }
        
        echo "<p style='color: green;'>✓ Added 5 sample suppliers</p>";
        
        echo "<h3>Database Reset Complete!</h3>";
        echo "<p><a href='test_po_form.php'>Test Purchase Order Form</a></p>";
        echo "<p><a href='purchase_order.php'>Go to Purchase Order Page</a></p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Are you sure you want to reset the database?</p>";
    echo "<p><a href='reset_po_database.php?confirm=yes'>Yes, Reset Database</a> | <a href='setup_po_database.php'>No, Just Setup</a></p>";
}
?>
