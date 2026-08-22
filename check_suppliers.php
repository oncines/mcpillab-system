<?php
require_once 'config.php';

echo "<h2>Check Suppliers Table</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    // Check if suppliers table exists
    $query = "SHOW TABLES LIKE 'suppliers'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $table_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($table_exists) {
        echo "<p style='color: green;'>✓ Suppliers table exists</p>";
        
        // Check count
        $query = "SELECT COUNT(*) as count FROM suppliers";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Suppliers count: " . $count['count'] . "</p>";
        
        if ($count['count'] == 0) {
            echo "<p style='color: orange;'>Adding sample suppliers...</p>";
            
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
        
        // Show suppliers
        $query = "SELECT * FROM suppliers";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Suppliers:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Contact</th><th>Email</th></tr>";
        foreach ($suppliers as $supplier) {
            echo "<tr>";
            echo "<td>{$supplier['id']}</td>";
            echo "<td>{$supplier['supplier_code']}</td>";
            echo "<td>{$supplier['name']}</td>";
            echo "<td>{$supplier['contact_person']}</td>";
            echo "<td>{$supplier['email']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>✗ Suppliers table does not exist</p>";
        echo "<p>Please run the database.sql script to create the table.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
