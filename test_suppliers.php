<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

// Check suppliers table
$query = "SELECT COUNT(*) as count FROM suppliers";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Suppliers count: " . $result['count'] . PHP_EOL;

// Show suppliers if any exist
if ($result['count'] > 0) {
    $query = "SELECT id, name, supplier_code FROM suppliers";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Suppliers:" . PHP_EOL;
    foreach ($suppliers as $supplier) {
        echo "- ID: " . $supplier['id'] . ", Name: " . $supplier['name'] . ", Code: " . $supplier['supplier_code'] . PHP_EOL;
    }
} else {
    echo "No suppliers found. Adding sample suppliers..." . PHP_EOL;
    
    // Add sample suppliers
    $suppliers_data = [
        ['MED001', 'MediCorp Pharmaceuticals', 'John Smith', 'john@medicorp.com', '555-0101', '123 Medical St', 'New York', 'USA'],
        ['PHAR002', 'PharmaTech Solutions', 'Sarah Johnson', 'sarah@pharmatech.com', '555-0102', '456 Pharma Ave', 'Los Angeles', 'USA'],
        ['LAB003', 'LabSupply Co.', 'Mike Wilson', 'mike@labsupply.com', '555-0103', '789 Lab Road', 'Chicago', 'USA']
    ];
    
    foreach ($suppliers_data as $supplier) {
        $query = "INSERT INTO suppliers (supplier_code, name, contact_person, email, phone, address, city, country) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute($supplier);
    }
    
    echo "Sample suppliers added successfully!" . PHP_EOL;
}
?>
