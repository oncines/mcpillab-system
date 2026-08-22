<?php
require_once 'config.php';

echo "<h2>Fixing Inventory System</h2>";

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Check if inventory_items table exists
    $result = $db->query("SHOW TABLES LIKE 'inventory_items'");
    if ($result->rowCount() == 0) {
        echo "<h3>Creating inventory tables...</h3>";
        
        // Read and execute the inventory tables SQL
        $sql_file = 'inventory_tables.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $index => $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        $db->exec($statement);
                        echo "<p style='color: green;'>✓ Created table/inserted data</p>";
                    } catch (PDOException $e) {
                        echo "<p style='color: orange;'>⚠ Statement failed: " . $e->getMessage() . "</p>";
                    }
                }
            }
        }
    } else {
        echo "<p style='color: blue;'>ℹ Inventory tables already exist</p>";
    }
    
    // Check if suppliers table has data
    $supplier_check = $db->query("SELECT COUNT(*) as count FROM suppliers");
    $supplier_count = $supplier_check->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($supplier_count == 0) {
        echo "<h3>Adding sample suppliers...</h3>";
        
        // Add sample suppliers
        $suppliers = [
            ['SUP001', 'ABC Medical Supplies', 'John Doe', 'john@abc.com', '123-456-7890'],
            ['SUP002', 'XYZ Pharmaceutical', 'Jane Smith', 'jane@xyz.com', '098-765-4321'],
            ['SUP003', 'Global Chemical Corp', 'Mike Johnson', 'mike@gcc.com', '555-123-4567']
        ];
        
        foreach ($suppliers as $supplier) {
            $query = "INSERT INTO suppliers (supplier_code, name, contact_person, email, phone, status) VALUES (?, ?, ?, ?, ?, 'active')";
            $stmt = $db->prepare($query);
            $stmt->execute($supplier);
            echo "<p style='color: green;'>✓ Added supplier: {$supplier[1]}</p>";
        }
    }
    
    echo "<h3 style='color: green;'>✓ Inventory system fixed successfully!</h3>";
    echo "<p><a href='inventory_form.php'>Test the Save Item button now</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
