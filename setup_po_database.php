<?php
require_once 'config.php';

echo "<h2>Setup Purchase Order Database</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    // Create suppliers table if not exists
    echo "<h3>Creating/Checking Suppliers Table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS suppliers (
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
    echo "<p style='color: green;'>✓ Suppliers table ready</p>";
    
    // Create purchase_orders table if not exists
    echo "<h3>Creating/Checking Purchase Orders Table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS purchase_orders (
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
    echo "<p style='color: green;'>✓ Purchase Orders table ready</p>";
    
    // Create purchase_order_items table if not exists
    echo "<h3>Creating/Checking Purchase Order Items Table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS purchase_order_items (
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
    echo "<p style='color: green;'>✓ Purchase Order Items table ready</p>";
    
    // Create purchase_order_messages table if not exists
    echo "<h3>Creating/Checking Purchase Order Messages Table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS purchase_order_messages (
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
    echo "<p style='color: green;'>✓ Purchase Order Messages table ready</p>";
    
    // Check if suppliers exist, add sample data if not
    echo "<h3>Checking Sample Data...</h3>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM suppliers");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "<p>Adding sample suppliers...</p>";
        
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
    } else {
        echo "<p style='color: green;'>✓ Suppliers table already has $count records</p>";
    }
    
    // Check if users exist for testing
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'store')");
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($user_count == 0) {
        echo "<p>Adding test users...</p>";
        
        // Add test admin user
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (email, password, full_name, role) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute(['admin@mcpil.com', $password_hash, 'Admin User', 'admin']);
        
        // Add test store user
        $password_hash = password_hash('store123', PASSWORD_DEFAULT);
        $stmt->execute(['store@mcpil.com', $password_hash, 'Store User', 'store']);
        
        echo "<p style='color: green;'>✓ Added test users (admin@mcpil.com / admin123, store@mcpil.com / store123)</p>";
    } else {
        echo "<p style='color: green;'>✓ Found $user_count admin/store users</p>";
    }
    
    // Show final status
    echo "<h3>Database Setup Complete!</h3>";
    
    // Show table counts
    $tables = ['suppliers', 'purchase_orders', 'purchase_order_items', 'purchase_order_messages', 'users'];
    echo "<table border='1' style='border-collapse: collapse; margin-top: 20px;'>";
    echo "<tr style='background: #f0f0f0;'><th>Table</th><th>Record Count</th></tr>";
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<tr><td>$table</td><td>$count</td></tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='test_po_form.php'>Test the Purchase Order form</a></li>";
    echo "<li><a href='purchase_order.php'>Go to Purchase Order page</a></li>";
    echo "<li>Log in with test credentials:
        <ul>
            <li>Admin: admin@mcpil.com / admin123</li>
            <li>Store: store@mcpil.com / store123</li>
        </ul>
    </li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
